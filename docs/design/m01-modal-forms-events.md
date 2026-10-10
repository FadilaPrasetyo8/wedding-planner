# Desain: M01 — Pola Modal Form Reusable (FormDialog) + Modul Events

Penulis: Chris (UX). Dibaca oleh: Paul (frontend-dev), Albert (backend, hanya bagian flash/redirect), George (QA).
Rujukan: `docs/analysis/m01-modal-forms-events.md` (AC-M01-001 s.d. 018), `docs/decision-log.md` D-030 (Vintage Analog), D-031, D-032.

Dokumen ini adalah spesifikasi desain, bukan kode. Sketsa JSX hanya menggambarkan kontrak props dan struktur; keputusan implementasi final ada pada frontend-dev selama kontrak dan AC tetap terpenuhi.

---

## 1. Ringkasan keputusan desain

1. Satu komponen generik `FormDialog` di `resources/js/components/form-dialog.tsx`, dibangun di atas `resources/js/components/ui/dialog.tsx` (Radix). Komponen ini tidak mengimpor apa pun dari modul Events dan tidak mengetahui field apa pun (AC-M01-001).
2. Modal create dan edit Events memakai `FormDialog` yang sama; hanya `title`, `description`, data awal, dan endpoint yang berbeda (AC-M01-002).
3. Semua trigger (Tambah/Edit) berupa tombol yang mengubah state React, bukan `<Link>` — URL tidak berubah, tidak ada history entry, tidak ada page load (AC-M01-003/005).
4. Aksi hapus acara beserta konfirmasi dua langkah pindah ke dalam modal edit, di sisi kiri footer (D-032a, AC-M01-007b).
5. Tombol "Edit acara" di show.tsx membuka modal edit yang sama (D-032b, AC-M01-017).
6. Semua visual memakai token tema D-030 yang sudah ada (`--background`, `--card`, `--primary`, `--radius`, `--shadow-*`, `--font-display`, dsb.). Tidak ada hex baru, tidak ada token baru.

---

## 2. Kontrak FormDialog (generik)

### 2.1 Penempatan file

- Komponen: `resources/js/components/form-dialog.tsx`
- Semua styling memakai token Tailwind semantik proyek (`bg-card`, `text-muted-foreground`, `rounded-[var(--radius)]`, `shadow-lg`, `min-h-11`, `font-display`). Tidak boleh ada hex literal.

### 2.2 Props (kontrak deklaratif)

```tsx
type FormDialogProps = {
    /** Controlled: open state dimiliki halaman pemanggil. */
    open: boolean;
    /** Dipanggil saat modal diminta tutup (X, Batal, Escape, klik overlay). */
    onOpenChange: (open: boolean) => void;

    /** Judul modal. Dirender sebagai DialogTitle (aria-labelledby otomatis via Radix). */
    title: string;
    /** Deskripsi singkat opsional. Jika diisi → DialogDescription (aria-describedby otomatis). */
    description?: string;

    /** Label tombol submit. Wajib diisi (dari halaman pemanggil). */
    submitLabel: string;
    /** Label tombol submit saat processing. Default: "Menyimpan…". */
    submittingLabel?: string;

    /** True selama request Inertia berlangsung → tombol submit disabled + label berubah. */
    processing: boolean;
    /** Handler submit form (dipasang pada elemen <form>). */
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;

    /** Isi form — field apa pun, milik halaman pemanggil. */
    children: React.ReactNode;

    /** Elemen yang difokuskan saat modal terbuka. Default: field pertama (elemen focusable pertama). */
    initialFocusRef?: React.RefObject<HTMLElement | null>;

    /** Lebar maksimum modal. Default: 'md' (max-w-lg). */
    maxWidth?: 'sm' | 'md';

    /** Slot kiri footer: aksi sekunder/destruktif (mis. blok hapus di modal edit Events). */
    footerExtra?: React.ReactNode;

    /** id elemen <form>, default dibangkitkan otomatis. */
    formId?: string;
};
```

Catatan kontrak:

- `FormDialog` TIDAK menerima field Events, endpoint, atau `WeddingEventController`. Ia hanya menyediakan shell dialog + form + footer. Generik untuk tasks, guests, dst. (AC-M01-001, Out of Scope analysis).
- `open`/`onOpenChange` controlled: halaman (index/show) memegang state `open` dan data awal (`null` = create, `event = X` = edit). Satu instance `FormDialog` bisa melayani create dan edit sekaligus dengan mengganti props saat `open` berubah — perbedaan create vs edit hanya data awal + copy (AC-M01-002).
- Submit: `FormDialog` merender satu elemen `<form onSubmit={onSubmit}>` yang membungkus body DAN footer, sehingga tombol Simpan di footer tetap `type="submit"` di dalam form yang sama. Enter di field mana pun men-submit form ini (AC-M01-013).

### 2.3 Perilaku (bukan visual)

| Perilaku | Spesifikasi | AC |
|---|---|---|
| Focus trap | Bawaan Radix `DialogContent` — Tab/Shift+Tab tidak pernah keluar modal. | 011 |
| Fokus awal | Saat open: `initialFocusRef` difokuskan (Events: field "Nama acara"). Jika tidak disediakan, elemen focusable pertama. | 011 |
| Focus restore | Bawaan Radix — saat close, fokus kembali ke tombol trigger yang membukanya. | 011 |
| Scroll lock | Bawaan Radix (overflow hidden pada body saat open). | 011 |
| Escape | Bawaan Radix `onEscapeKeyDown` → `onOpenChange(false)`. | 012 |
| Klik overlay | `onInteractOutside` → `onOpenChange(false)`. | 010 |
| Selama `processing` | Submit disabled; tombol Batal, X, Escape, dan klik overlay dicegah (`preventDefault` pada `onEscapeKeyDown`/`onInteractOutside`, tombol `disabled`). Tidak ada request kedua. | 009 |
| Error validasi | Modal TETAP terbuka (tidak ada navigasi — error datang sebagai props Inertia, bukan redirect). Nilai field dipertahankan (form uncontrolled `defaultValue` tidak di-unmount saat error). Pesan per-field dirender oleh children (pola `Field` + `InputError` form lama). | 008 |
| Reset saat close bersih | Saat `open` false→true, konten form di-remount (halaman memasang `key` pada children yang berubah per siklus open, atau `FormDialog` merender children hanya saat open). Efek: create dibuka ulang bersih; edit dibuka ulang dengan data terbaru dari server. | 010 |
| Sukses | Controller redirect → props halaman ter-update; halaman menutup modal (`setOpen(false)` setelah submit sukses / saat `processing` selesai tanpa error) → toast sonner via `useFlashToast`. | 004, 006 |
| Tanpa dirty guard | Menutup modal dengan isian memang membuang input (diterima sebagai risiko D-032c; tidak ada konfirmasi). | 010 |

### 2.4 Sketsa struktur (bukan kode final)

```tsx
<Dialog open={open} onOpenChange={onOpenChange}>
    <DialogContent className="flex max-h-[calc(100svh-2rem)] w-full flex-col ...">
        <DialogHeader>
            <DialogTitle className="font-display ..." />       // aria-labelledby (Radix)
            {description && <DialogDescription />}               // aria-describedby (Radix)
        </DialogHeader>
        <form id={formId} onSubmit={onSubmit} className="flex min-h-0 flex-1 flex-col">
            <div className="min-h-0 flex-1 overflow-y-auto ...">{children}</div>
            <DialogFooter className="shrink-0 ...">
                {footerExtra}                                    // slot kiri (hapus)
                <Button type="button" variant="outline" disabled={processing}>Batal</Button>
                <Button type="submit" disabled={processing}>
                    {processing ? submittingLabel : submitLabel}
                </Button>
            </DialogFooter>
        </form>
        {/* tombol X: aria-label "Tutup", disabled saat processing */}
    </DialogContent>
</Dialog>
```

Satu penyesuaian kecil pada primitive: teks `sr-only` tombol tutup di `ui/dialog.tsx` ("Close") diganti "Tutup" agar label screen reader berbahasa Indonesia (AC-M01-012). Perubahan satu string, tanpa perubahan visual.

---

## 3. Anatomi modal

### 3.1 Wireframe

```
┌──────────────────────────────────────────────────────┐
│ Tambah acara                                   [X]   │  header: shrink-0, judul Fraunces
│ Tambahkan acara penting ke rangkaian Anda.           │  description, muted, 1 baris
├──────────────────────────────────────────────────────┤ ↕ border-b
│  Nama acara *                Jenis acara              │
│  ┌──────────────────┐        ┌──────────────────┐    │  body: overflow-y-auto
│  │ Lamaran ▾        │        │                  │    │  (scroll di dalam modal)
│  └──────────────────┘        └──────────────────┘    │
│  Tanggal *     Waktu mulai   Waktu selesai           │  desktop: 3 kolom
│  ┌──────────┐  ┌──────────┐  ┌──────────┐            │  mobile (375px): stack
│  └──────────┘  └──────────┘  └──────────┘            │
│  Lokasi                                                │
│  ┌──────────────────────────────────────────┐         │
│  └──────────────────────────────────────────┘         │
│  Catatan                                               │
│  ┌──────────────────────────────────────────┐         │
│  │                                          │         │
│  └──────────────────────────────────────────┘         │
│  ┌──────────────────────────────────────────┐         │  blok "acara utama"
│  │ [✓] Jadikan acara utama                   │        │  (reuse markup form lama)
│  │     ... [✓] Saya memahami tanggal tugas   │        │  (checkbox recalc, khusus
│  │         relatif akan dihitung ulang.      │         │   edit non-utama)
│  └──────────────────────────────────────────┘         │
├──────────────────────────────────────────────────────┤ ↕ border-t
│ [Hapus acara]                     [Batal] [Simpan acara]│ footer: shrink-0
└──────────────────────────────────────────────────────┘
   ↑ slot footerExtra (hanya modal edit, acara non-utama)
```

### 3.2 Header

- Judul: `DialogTitle` + kelas `font-display` (Fraunces), ukuran lebih besar dari body (mis. `text-xl font-semibold`), warna `text-foreground` (ink `#171512` via token).
- Deskripsi: `text-muted-foreground text-sm`, maksimal satu baris.
- Tombol tutup: ikon X `lucide`, posisi absolute kanan-atas (posisi primitive saat ini dipertahankan), `aria-label="Tutup"` + `sr-only` text "Tutup", target ≥ 44px, `disabled` saat processing.

### 3.3 Body

- Konten field dipakai ulang dari `event-form.tsx` yang ada (nama, jenis, tanggal, waktu mulai/selesai, lokasi, catatan, checkbox "Jadikan acara utama", dan di edit non-utama: checkbox konfirmasi hitung ulang) — AC-M01-005/007a. Markup field tidak dirancang ulang.
- Grid field: `grid gap-5`; `sm:grid-cols-2` (nama, jenis), `sm:grid-cols-3` (tanggal, waktu mulai, waktu selesai), full-width (lokasi, catatan). Di bawah `sm` (termasuk 375px): stack satu kolom. Modal `maxWidth` default `md` = `sm:max-w-lg` (32rem) — 3 kolom tanggal/jam masih lega pada breakpoint `sm`; di lebar < 640px semua stack (AC-M01-014).
- Scroll konten: keseluruhan `DialogContent` `flex flex-col max-h-[calc(100svh-2rem)]`; body `min-h-0 flex-1 overflow-y-auto`; header dan footer `shrink-0`. Judul dan tombol aksi selalu terjangkau pada konten panjang dan layar 375px.
- Lebar responsif: `w-full max-w-[calc(100%-2rem)]` dari primitive dipertahankan (margin 1rem kiri-kanan di mobile).

### 3.4 Footer

- Layout: `flex flex-col-reverse gap-2 sm:flex-row sm:justify-between` — kiri slot `footerExtra`, kanan grup `[Batal] [Simpan acara]`.
- `footerExtra` kosong pada create (ruang kiri tetap dipertahankan dengan spacer agar Simpan tetap rata kanan, pola yang sama seperti form lama).
- Semua tombol `min-h-11` (44px).

### 3.5 Token D-030 yang dipakai (tidak ada nilai baru)

| Aspek | Token |
|---|---|
| Permukaan modal | `bg-card` (`#fbf3df` via token), border `border-border` |
| Overlay | `bg-black/80` dari primitive (dipertahankan) |
| Bayangan modal | `shadow-[0_12px_32px_rgb(23_21_18_/_0.2)]` dari primitive = skala `--shadow-lg` D-030 |
| Radius | `rounded-[var(--radius)]` = 0.28rem editorial |
| Judul | `font-display` (Fraunces), `text-foreground` |
| Deskripsi & label sekunder | `text-muted-foreground` |
| Tombol Simpan | `Button` default (primary teal `--primary`, `--primary-hover` saat hover) |
| Tombol Batal | `Button variant="outline"` |
| Tombol hapus | `Button variant="destructive"` |
| Fokus | `focus-visible:ring-ring` / ring 3px sesuai konvensi `button.tsx`/`input` proyek |

---

## 4. Copy (Bahasa Indonesia)

| Konteks | Teks |
|---|---|
| Judul modal create | Tambah acara |
| Deskripsi modal create | Tambahkan acara penting ke rangkaian pernikahan Anda. |
| Judul modal edit | Ubah acara |
| Deskripsi modal edit | Perbarui detail acara ini. |
| Tombol submit | Simpan acara |
| Tombol submit (processing) | Menyimpan… |
| Tombol batal | Batal |
| Tombol tutup (aria-label) | Tutup |
| Trigger create (index header) | Tambah acara |
| Trigger edit (baris index) | Edit |
| Trigger edit (show.tsx) | Edit acara |
| Toast sukses create | Acara berhasil ditambahkan. |
| Toast sukses edit | Acara berhasil diperbarui. |
| Toast sukses hapus | Acara berhasil dihapus. |
| Langkah 1 hapus | Hapus acara |
| Langkah 2 hapus (konfirmasi) | Yakin, hapus |
| Batal konfirmasi hapus | Batal |
| Empty state index (judul) | Belum ada acara |
| Empty state index (body) | Mulai dengan menambahkan acara penting seperti lamaran, akad, atau resepsi. |
| Empty state CTA | Tambah acara |
| Mode hanya lihat (viewer) | Mode hanya lihat (dipertahankan dari index saat ini) |

Catatan:
- Copy toast dihasilkan controller (backend, kesepakatan D-031: `Inertia::flash('toast', ...)` + sonner). Teks di atas adalah copy yang ditetapkan desain; Albert menyesuaikan controller Events agar flash-nya menghasilkan teks ini.
- Pesan error per-field tetap berasal dari validasi Form Request (mis. pesan untuk `name`, `event_date`); FormDialog dan field tidak mengarang pesan sendiri. Error non-field (mis. gagal hapus acara utama) tampil sebagai `Alert variant="destructive"` di bagian atas body modal (`role="alert"` sudah di primitive alert.tsx).
- Copy validasi konfirmasi hitung ulang dipertahankan persis: "Saya memahami tanggal tugas relatif akan dihitung ulang." (AC-M01-007a).

---

## 5. State per komponen

| Komponen | Default | Hover / focus-visible | Loading | Error | Disabled | Empty | Success |
|---|---|---|---|---|---|---|---|
| `FormDialog` shell | Tertutup; trigger memegang state. Terbuka: overlay fade-in + zoom-in-95 dari primitive (durasi 200ms, halus, menjelaskan state). | — | Selama `processing`: Batal/X/Escape/overlay-click dicegah (AC-M01-009). | Saat error validasi: TETAP terbuka, field terisi, pesan per-field tampil (AC-M01-008). | Overlay tidak bisa menutup modal saat processing. | — | Modal tertutup, trigger kembali difokuskan (Radix). |
| Trigger "Tambah acara" (index) | `Button` default, ikon `Plus`. | Konvensi hover/focus `button.tsx` (primary-hover, ring 3px). | Tidak berubah saat modal diproses (tidak perlu disabled — modal mengunci interaksi). | — | Tidak dirender saat `canEdit = false` (viewer); diganti badge "Mode hanya lihat" (AC-M01-016). | — | — |
| Trigger "Edit" (per baris) | `Button variant="outline"`, ikon `Pencil", teks "Edit". | Sama seperti outline: `hover:bg-accent`. | Sama seperti di atas. | — | Tidak dirender saat viewer. | — | — |
| Trigger "Edit acara" (show) | `Button` default, ikon `Pencil`. | Sama seperti default. | Sama seperti di atas. | — | Tidak dirender saat viewer (badge "Mode hanya lihat" dipertahankan). | — | — |
| Field form (nama, jenis, tanggal, waktu, lokasi, catatan) | Dipakai ulang dari `event-form.tsx`: `Label` + input + `InputError`. | `focus-visible:ring-ring 2px` (input) sesuai konvensi proyek. | Tidak disabled saat processing (form lama tidak menonaktifkannya; nilai harus tetap terlihat). | `InputError` merah `text-destructive` tepat di bawah field; field tidak berubah warna (pola lama dipertahankan, konsisten dengan task-form). | — | Nilai default: create kosong; edit terisi data acara (AC-M01-005). | — |
| Checkbox "Jadikan acara utama" + deskripsi | Dipakai ulang persis dari form lama, di dalam blok ber-border. | Ring focus sesuai `checkbox.tsx`. | — | `InputError` untuk `is_primary` dan `confirm_recalculate_relative_tasks` persis seperti form lama. | — | — | — |
| Tombol Simpan | `type="submit"`, label "Simpan acara". | Konvensi default button. | `disabled` + label "Menyimpan…" + `disabled:opacity-50` dari `button.tsx`; klik berulang hanya menghasilkan satu request (AC-M01-009). | — | `disabled={processing}`. | — | — |
| Tombol Batal | `type="button" variant="outline"`, menutup modal tanpa request. | Konvensi outline. | `disabled` saat processing. | — | `disabled={processing}`. | — | — |
| Tombol tutup X | Ikon X. | Opacity naik saat hover (primitive). | `disabled` saat processing. | — | `disabled={processing}`. | — | — |
| Blok hapus (modal edit, non-utama) | Satu tombol "Hapus acara" (destructive) di `footerExtra`. | Konvensi destructive. | Saat request hapus berjalan: tombol "Yakin, hapus" disabled + "Menghapus…". | Gagal hapus (mis. acara utama dipaksakan): `Alert destructive` di atas body modal, modal tetap terbuka (AC-M01-007c). | Tidak dirender untuk acara utama dan di modal create. | — | Redirect ke index + toast "Acara berhasil dihapus." |
| Konfirmasi dua langkah | Klik "Hapus acara" → tombol berubah jadi grup `[Yakin, hapus] [Batal]` (pola yang sama persis seperti form lama — tidak ada dialog kedua di atas dialog). | — | — | — | "Batal" mengembalikan ke langkah 1. | — | — |
| Daftar acara (index) | Baris kartu `bg-card` dari index saat ini, urutan server (primary dulu, lalu tanggal). | — | Ter-update dari props server setelah redirect Inertia — bukan mutasi lokal (AC-M01-004/006). | — | — | Empty state baru: blok di dalam kartu daftar — ikon kalender, judul "Belum ada acara", body, CTA "Tambah acara" (membuka modal yang sama; hanya jika `canEdit`). | — |
| Toast sukses | — | — | — | — | — | — | Sonner via `useFlashToast`, teks dari controller, muncul saat modal tertutup. |

---

## 6. Aksesibilitas — pemenuhan AC-M01-011 s.d. 014

| AC | Desain pemenuhan |
|---|---|
| AC-M01-011 Fokus terkelola | (a) Fokus awal: `initialFocusRef` Events menunjuk ke field "Nama acara"; (b) focus trap bawaan Radix `DialogContent`; (c) focus restore otomatis Radix ke trigger (verifikasi QA: `document.activeElement` = tombol trigger); (d) scroll lock bawaan Radix. |
| AC-M01-012 ARIA + Escape | `role="dialog"` bawaan Radix; `aria-labelledby` → DialogTitle, `aria-describedby` → DialogDescription (wired otomatis oleh Radix saat Title/Description dirender — description Events selalu diisi); tombol X `sr-only` "Tutup"; Escape menutup (dicegah hanya saat processing). |
| AC-M01-013 Enter + Tab | Enter di field mana pun men-submit karena tombol Simpan `type="submit"` berada dalam `<form>` yang sama. Urutan Tab mengikuti DOM field: nama → jenis → tanggal → waktu mulai → waktu selesai → lokasi → catatan → checkbox utama → (checkbox recalc, jika tampil) → footerExtra (hapus, jika tampil) → Batal → Simpan → X. Catatan: X adalah elemen focusable terakhir dalam siklus (posisi DOM primitive); hal ini disengaja — X tetap terjangkau via Shift+Tab dari field nama. Focus ring mengikuti konvensi `focus-visible` proyek (ring 3px / 2px, token `--ring`). |
| AC-M01-014 Layar kecil + konten panjang | `max-h-[calc(100svh-2rem)]` + body `overflow-y-auto` → judul dan footer selalu terlihat; lebar `max-w-[calc(100%-2rem)]` aman di 375px; field stack satu kolom di bawah `sm`; semua tombol dan field `min-h-11` (≥ 44px). |

Pengujian yang disarankan untuk George: axe DevTools pada modal terbuka; penuhi 18 AC dengan fokus manual keyboard-only untuk 011–014. RTL test fokus pada perilaku close/submit sesuai D-032d.

---

## 7. Penempatan trigger

1. **Index — header**: tombol "Tambah acara" (ikon Plus) tetap di posisi header saat ini (kanan pada desktop, di bawah judul pada mobile), menggantikan `<Link>` — membuka modal create dengan form kosong (AC-M01-003).
2. **Index — per baris**: tombol "Edit" (outline, ikon Pencil) di kolom aksi baris yang ada sekarang, menggantikan `<Link>` — membuka modal edit terisi data baris tersebut (AC-M01-005). Hanya dirender saat `canEdit`.
3. **Show — header**: tombol "Edit acara" menggantikan `<Link>` ke halaman edit — membuka modal edit yang sama memakai `event` yang sudah ada di props show (D-032b, AC-M01-017).
4. **Index — empty state**: CTA "Tambah acara" membuka modal create yang sama.

State modal di index: satu pasangan `useState` — `editing: EventValue | null` untuk edit, boolean terpisah (atau union `create | edit`) untuk create. Data acara untuk modal edit diambil dari baris (index) atau props (show) yang sudah ada; tidak ada fetch baru.

---

## 8. Halaman lama dan route (AC-M01-015)

- `resources/js/pages/weddings/events/create.tsx` dan `edit.tsx` dihapus; `event-form.tsx` dipertahankan sebagai sumber field yang dirender di dalam `FormDialog` (field tanpa footer form lama — footer "Batal/Link + Simpan" dan blok navigasi lama tidak ikut, karena `FormDialog` yang menyediakan footer; blok hapus pindah ke `footerExtra`).
- Route GET `events/create` dan `events/{event}/edit` menjadi redirect 302 ke `weddings.events.index` (bukan 404) supaya bookmark lama tetap hidup. Perubahan ini di controller (Albert), satu baris per aksi, sesuai Asumsi #2 analysis yang sudah diterima lewat D-031.
- `show.tsx` tetap halaman penuh, tidak menjadi modal (Out of Scope).
- Endpoint POST/PUT, Form Request, Action, Policy tidak berubah (AC-M01-015/016).

---

## 9. Risiko dan catatan untuk implementer

1. **Edit dari halaman show mengakhiri di index.** Controller `update` redirect ke `weddings.events.index` (perilaku existing, tidak boleh berubah di modul ini). Setelah menyimpan dari modal di halaman show, pengguna akan mendarat di index dengan toast sukses. Berfungsi (AC-M01-017 terpenuhi), tapi ada pergeseran konteks. Jika dirasa mengganggu, opsi "redirect back" adalah perubahan controller yang harus jadi keputusan terpisah — jangan diubah diam-diam.
2. **Tanpa dirty guard.** Escape/klik overlay pada form terisi membuang input (D-032c). Copy description singkat dan fokus awal ke field nama mengurangi friksi, tapi risiko kehilangan input tetap ada dan sudah diterima untuk M01.
3. **Form remount saat toggle open.** Perilaku "reset saat dibuka ulang" (AC-M01-010) bergantung pada remount children saat close→open. Frontend-dev harus memastikan error Inertia tidak ikut mereset nilai saat modal tetap terbuka (error datang sebagai partial reload, bukan remount) — ini justru alasan state `open` tidak boleh diturunkan dari keberadaan error.
4. **Checkbox recalc hanya di edit non-utama.** Verifikasi server tetap satu-satunya penjaga (AC-M01-007a); UI hanya menampilkan checkbox pada kondisi yang sama seperti form lama.
5. **Label X dalam siklus Tab di akhir.** Dipertahankan agar tidak mengubah primitive; dicatat di §6. Jika QA menemukan masalah nyata, alternatifnya prop `hideClose` pada FormDialog — keputusan kecil yang boleh diambil implementer tanpa mengubah desain ini.
6. **Toast convention.** Pengalihan flash Events ke `Inertia::flash('toast')` adalah prasyarat copy sukses (D-031 sudah menyetujui); pastikan dilakukan di ronde yang sama agar AC-M01-004/006 lolos.

## Laporan

- Status: DONE
- Ringkasan: Kontrak FormDialog generik (controlled `open`/`onOpenChange`, `title`, `description`, `submitLabel`, `processing`, `onSubmit`, `children`, `initialFocusRef`, `footerExtra`) didefinisikan di atas primitive Radix, dengan penerapan penuh pada create/edit Events: trigger tanpa navigasi, hapus dua langkah pindah ke footer modal edit, copy Indonesia lengkap, state per komponen, dan pemetaan AC aksesibilitas 011–014.
- File: `D:\Portofolio\wedding-planner\docs\design\m01-modal-forms-events.md`
- Test/Build: tidak berlaku (tidak menyentuh kode)
