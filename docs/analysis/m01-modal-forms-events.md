# Analisis: M01 — Pola Modal Form Reusable + Events

## Tujuan & Scope

Permintaan eksplisit pengguna (prioritas tertinggi menurut CLAUDE.md §2): setiap alur "tambah data" dan "edit ringkas" harus memakai **modal**, bukan navigasi ke halaman terpisah, karena navigasi saat ini terasa berantakan. Ronde pertama:

1. **Pola modal form reusable** — komponen dasar (selanjutnya disebut `FormDialog`) di atas `resources/js/components/ui/dialog.tsx` (Radix) yang nanti dipakai ulang semua modul (tasks, guests, dst.).
2. **Penerapan pada modul Events** — tambah dan edit acara dari halaman index Events tanpa navigasi.

Catatan konflik arah UX: CLAUDE.md §9 memuat "avoid using modals for every interaction". Instruksi eksplisit pengguna menang atas CLAUDE.md, jadi tambah/edit via modal tetap masuk scope. Konsekuensinya, kualitas modal dijaga lewat AC aksesibilitas dan state (AC-M01-008 s.d. AC-M01-011) agar tidak merusak UX. Halaman detail (show), destructive flow, dan navigasi antar-halaman besar tetap bukan modal.

Status quo yang relevan (hasil pembacaan kode):

- `resources/js/pages/weddings/events/index.tsx` — tombol "Tambah acara" dan "Edit" memakai `<Link>` navigasi ke halaman create/edit.
- `resources/js/pages/weddings/events/event-form.tsx` — form memakai helper Inertia `<Form>` dengan render-prop `{ processing, errors }`; submit button sudah `disabled={processing}` dengan label "Menyimpan…".
- `app/Http/Controllers/Weddings/WeddingEventController.php` — `store`/`update` memvalidasi via Form Request, memakai `ChangePrimaryWeddingEventAction`, lalu redirect ke `weddings.events.index` dengan flash `success`. `create`/`edit` merender halaman terpisah.
- Halaman edit saat ini juga menampung **aksi hapus acara** (dengan konfirmasi dua langkah) dan **checkbox konfirmasi hitung ulang tugas relatif** (`confirm_recalculate_relative_tasks`) — keduanya tidak boleh hilang saat halaman edit dialihkan.
- `resources/js/pages/weddings/events/show.tsx` punya tombol "Edit acara" yang menavigasi ke halaman edit.
- Flash success konvensi aplikasi: `Inertia::flash('toast', ...)` ditampilkan via `useFlashToast` (sonner). Events saat ini masih pakai `->with('success', ...)`; penyatuan konvensi dicatat di Asumsi.

## Kriteria Penerimaan

### Pola reusable

- **AC-M01-001: Komponen modal form reusable tersedia.**
  Terdapat satu komponen modal form generik (mis. `resources/js/components/form-dialog.tsx`) yang dibangun di atas `ui/dialog.tsx` dan menerima API deklaratif minimal: trigger, judul, deskripsi, konten form, state submit (loading), dan penutupan. Komponen ini TIDAK berisi field khusus Events (nama, tanggal, lokasi, dsb.).
  *Verifikasi: code review — file komponen ada, tidak mengimpor `WeddingEventController`, dan mengekspos props generik (trigger/title/description/onSuccess dsb.).*

- **AC-M01-002: Create dan edit Events memakai komponen yang sama.**
  Modal tambah acara dan modal edit acara di Events keduanya memakai `FormDialog` dari AC-M01-001 tanpa menduplikasi markup dialog/overlay/footer. Perbedaan create vs edit hanya di data awal dan endpoint.
  *Verifikasi: code review — grep pemakaian `FormDialog` di pages events; tidak ada blok `DialogContent`/`DialogHeader` yang diduplikasi antar dua alur.*

### Alur create (tambah acara)

- **AC-M01-003: Tombol "Tambah acara" membuka modal, bukan navigasi.**
  Sebagai member dengan role owner/editor (`canEdit = true`) di halaman index Events, menekan tombol "Tambah acara" membuka modal berisi form acara kosong; URL browser tidak berubah (tetap halaman index Events), tidak ada load halaman penuh, dan tidak ada entri baru di history browser.
  *Verifikasi: manual — klik tombol, cek URL tidak berubah; cek tombol Back tidak melompat ke halaman create.*

- **AC-M01-004: Sukses create menutup modal, daftar ter-update, ada feedback.**
  Mengisi form valid (nama, jenis, tanggal) lalu submit: modal tertutup, acara baru muncul di daftar (urutan sesuai query index: primary dulu, lalu tanggal), dan pengguna melihat feedback sukses (toast/flash) berisi pesan dari backend. Tidak terjadi navigasi halaman penuh; daftar ter-update dari data server, bukan mutasi lokal React.
  *Verifikasi: manual + feature test — submit data valid, cek `wedding_events` baru baru, cek modal tertutup dan baris baru muncul dengan nilai dari DB.*

### Alur edit

- **AC-M01-005: Tombol Edit per baris membuka modal berisi data existing.**
  Sebagai owner/editor, tombol "Edit" pada baris acara membuka modal yang semua fieldnya terisi dengan nilai acara tersebut (nama, jenis, tanggal, waktu mulai/selesai, lokasi, catatan, checkbox "acara utama"). URL tidak berubah.
  *Verifikasi: manual — buka edit untuk acara dengan semua field terisi; bandingkan nilai field dengan halaman detail acara.*

- **AC-M01-006: Sukses edit menutup modal dan daftar menampilkan nilai baru.**
  Mengubah satu atau lebih field lalu submit: modal tertutup, baris acara menampilkan nilai terbaru, feedback sukses tampil.
  *Verifikasi: manual + feature test — ubah nama/venue, submit, assert DB dan tampilan daftar ter-update.*

- **AC-M01-007: Perilaku khusus edit tetap utuh.**
  (a) Edit acara non-utama memunculkan checkbox "Saya memahami tanggal tugas relatif akan dihitung ulang" (`confirm_recalculate_relative_tasks`) persis seperti form lama; validasi servernya tetap berjalan. (b) Kemampuan hapus acara (non-utama) dengan konfirmasi tetap tersedia setelah halaman edit dialihkan — penempatannya (di modal edit atau di baris list) ditetapkan saat implementasi dan dicatat di decision log. (c) Mencoba menghapus acara utama tetap ditolak server dengan pesan error, bukan crash.
  *Verifikasi: manual + feature test existing — jalankan ulang test hapus acara dan test hitung ulang tugas relatif; hasil tidak berubah dari sebelum modul ini.*

### State form dalam modal

- **AC-M01-008: Validasi error server tampil di dalam modal dan field tetap terisi.**
  Submit form dengan data invalid (mis. nama kosong, format tanggal salah): modal TETAP terbuka, pesan error per-field tampil di samping field yang salah (memakai `errors` dari Inertia form seperti form lama), dan seluruh nilai yang sudah diketik pengguna tetap terisi — pengguna tidak perlu mengetik ulang.
  *Verifikasi: manual — submit dengan nama kosong; cek pesan error tampil, field lain tetap terisi; isi nama, submit ulang, sukses.*

- **AC-M01-009: State loading saat submit, tidak bisa double-submit.**
  Selama request berlangsung: tombol submit disabled + label berubah (pola lama "Menyimpan…" dipertahankan atau indikator setara), tombol tutup/batal/overlay-click yang bisa memicu submit ganda dinonaktifkan, dan tidak ada request POST/PUT kedua saat tombol diklik berulang.
  *Verifikasi: manual dengan throttling jaringan lambat — klik Simpan beberapa kali; cek Network hanya ada satu request store/update.*

- **AC-M01-010: Menutup modal tanpa menyimpan tidak mengubah data.**
  Menutup modal (tombol X, tombol Batal, Escape, atau klik overlay) pada form yang sudah terisi sebagian: modal tertutup, tidak ada request yang dikirim, tidak ada data tersimpan, dan form dibuka ulang bersih saat trigger create ditekan lagi (tidak menyimpan sisa input sebelumnya secara tidak sengaja). Tidak ada error JS di console.
  *Verifikasi: manual — isi sebagian form, tutup, cek DB tidak berubah; buka ulang, cek field kosong (create) / sesuai data (edit).*

### Aksesibilitas & keyboard

- **AC-M01-011: Fokus terkelola di dalam modal.**
  Saat modal terbuka: fokus berpindah ke elemen pertama yang relevan di modal, Tab/Shift+Tab tidak keluar ke halaman belakang (focus trap), dan saat modal ditutup fokus kembali ke tombol trigger yang membukanya. Halaman di belakang modal tidak bisa di-scroll (scroll lock).
  *Verifikasi: manual + keyboard-only — Tab berulang tidak pernah mencapai sidebar/header; tutup modal, verifikasi document.activeElement adalah tombol trigger.*

- **AC-M01-012: Semantik ARIA dan Escape benar.**
  Modal memiliki `role="dialog"` (dari Radix), `aria-labelledby` menunjuk judul, `aria-describedby` menunjuk deskripsi, tombol tutup memiliki label yang dapat dibaca screen reader, dan menekan Escape menutup modal.
  *Verifikasi: manual dengan inspect element + screen reader/axe DevTools; pastikan judul modal terbaca dan Escape menutup.*

- **AC-M01-013: Keyboard: Enter submit, Tab urut natural.**
  Fokus di field mana pun dalam form, menekan Enter men-submit form (sekali, tunduk pada AC-M01-009); urutan Tab mengikuti urutan visual field (nama → jenis → tanggal → waktu mulai → waktu selesai → lokasi → catatan → checkbox → tombol); fokus keyboard selalu terlihat (focus ring sesuai konvensi `focus-visible` proyek).
  *Verifikasi: manual keyboard-only — isi seluruh form hanya dengan keyboard, submit dengan Enter.*

- **AC-M01-014: Modal usable pada layar kecil dan konten panjang.**
  Pada viewport sempit (mis. 375px) dan saat konten form lebih tinggi dari viewport: area konten modal dapat di-scroll di dalam modal, judul dan tombol aksi tetap dapat dijangkau, dan tidak terpotong keluar layar. Touch target tetap ≥ 44px sesuai konvensi proyek.
  *Verifikasi: manual — responsive dev tools 375px; buka modal edit dengan catatan panjang, scroll dan submit.*

### Backend, route, dan isolasi

- **AC-M01-015: Route backend tidak berubah secara perilaku.**
  `POST weddings/{wedding}/events` (store) dan `PUT .../events/{event}` (update) tetap memakai endpoint, Form Request, Action, dan redirect yang sama seperti sebelum modul ini; hanya UX frontend yang berubah. Halaman create.tsx/edit.tsx lama tidak lagi dipakai: file React dihapus dan route GET `create`/`edit` menjadi redirect 302 ke `weddings.events.index` (bukan 404) supaya bookmark/URL lama tetap berfungsi.
  *Verifikasi: `php artisan route:list` — path/verb/method tidak berubah; feature test existing Events lulus tanpa modifikasi; GET URL create/edit via curl menghasilkan redirect ke index.*

- **AC-M01-016: Wedding isolation dan authorization tidak berubah.**
  Tidak ada perubahan Policy, middleware, atau validasi `wedding_id`. User tanpa membership tetap ditolak 403 pada store/update; viewer (`canEdit = false`) tidak melihat tombol Tambah/Edit di UI DAN ditolak server bila memaksa POST/PUT. Semua test isolation/authorization existing tetap hijau tanpa diubah.
  *Verifikasi: jalankan `php artisan test` — seluruh test Events/authorization lulus; manual login sebagai viewer, cek tombol tidak tampil dan request langsung ditolak.*

- **AC-M01-017: Halaman detail (show) tetap fungsional.**
  Halaman detail acara (show.tsx) tetap dapat diakses dari daftar. Tombol "Edit acara" di halaman detail tidak boleh mati: minimal tetap membawa pengguna ke alur edit yang bekerja (idealnya membuka modal edit yang sama). Pilihan final dicatat di Asumsi.
  *Verifikasi: manual — buka detail acara, tekan "Edit acara", pastikan alur edit berfungsi dan data tersimpan.*

- **AC-M01-018: Gerbang kualitas frontend lulus.**
  `npm run typecheck` (atau `types:check` sesuai skrip proyek), lint, build, dan test frontend yang terkonfigurasi lulus; tidak ada regresi pada test backend.
  *Verifikasi: jalankan perintah gerbang dan laporkan hasil aktual; tidak boleh mengklaim lulus tanpa menjalankannya.*

## Out of Scope

- Modul selain Events (tasks, guests, budget, checklist, vendor, rundown, playlist, savings, custom fields) — modul berikutnya; scope modul ini hanya memastikan pola `FormDialog` siap dipakai ulang.
- Perubahan backend: route method/path baru, policy baru, perubahan validasi Form Request, perubahan `ChangePrimaryWeddingEventAction`.
- Mengubah halaman detail acara menjadi modal; halaman tetap halaman penuh.
- Delete via modal sebagai fitur baru (hanya memastikan kemampuan hapus existing tidak hilang, AC-M01-007b).
- Dark mode/tema baru; modal memakai token tema existing (D-030).
- Fitur "unsaved changes warning" saat menutup modal (dicatat sebagai pertanyaan terbuka).

## Asumsi & Pertanyaan

1. **Konvensi flash success.** Events masih memakai `->with('success', ...)` sementara modul lain memakai `Inertia::flash('toast', ...)` + `useFlashToast`. Implementer perlu memastikan feedback sukses (AC-M01-004/006) tampil konsisten — kemungkinan beralih ke `Inertia::flash('toast', ...)` di controller Events. Perubahan ini bersifat presentasi feedback, bukan perubahan aturan bisnis; mohon konfirmasi bahwa mengubah cara flash di controller Events diperbolehkan dalam modul ini.
2. **Halaman create/edit lama: redirect, bukan 404.** Saya memilih redirect 302 ke index (AC-M01-015) agar URL lama tidak mati. Perlu persetujuan karena ini menambah satu baris perubahan controller (mengganti `Inertia::render` menjadi `redirectRoute`) meskipun bukan perubahan route/verb.
3. **Penempatan aksi hapus.** Aksi hapus acara + konfirmasi saat ini hidup di halaman edit yang akan dialihkan. Opsi: (a) pindahkan ke dalam modal edit, atau (b) jadikan aksi baris di index. Saya cenderung (a) agar dekat dengan konteks edit, tapi ini keputusan UX yang perlu dikonfirmasi sebelum implementasi.
4. **Tombol "Edit acara" di halaman show.** Opsi terbaik: membuka modal edit yang sama (perlu data event yang sudah ada di props show). Kalau dianggap di luar scope ronde ini, minimal tombol tetap berfungsi (AC-M01-017). Mohon konfirmasi apakah show.tsx termasuk ronde pertama.
5. **Unsaved changes guard.** Kehilangan input karena menekan Escape/klik overlay adalah risiko UX nyata pada form panjang. Radix tidak menyediakannya; ini bisa jadi fitur kecil `FormDialog` (deteksi dirty state → konfirmasi). Belum masuk scope; ditunda ke keputusan implementasi.
6. **Nama komponen.** "FormDialog" dipakai sebagai nama kerja; implementer bebas menamai lain selama kontrak AC-M01-001/002 terpenuhi.
7. **Test frontend.** Repository menargetkan Vitest/RTL "untuk focused frontend behavior tests" (D-013). Focus trap dan penutupan modal adalah kandidat test RTL; QA manual tetap menjadi verifikasi utama aksesibilitas.
