# Design Refresh — Warm Wedding Redesign

**Status:** Approved by user (14 September 2026): "semuanya di redesain ya, terus lanjut ke m4" — semua halaman, arah hangat & modern untuk pasangan.

## 1. Tujuan

Mengganti tampilan seluruh aplikasi dari nuansa admin generik menjadi ruang kerja perencanaan pernikahan yang **hangat, modern, tenang, dan premium** — sesuai CLAUDE.md §9 — tanpa mengubah struktur informasi, kepadatan data, atau aturan bisnis apa pun. Ini murni lapisan presentasi.

## 2. Arah Desain — "Blush & Rose, dinaikkan kelas"

Dari tiga arah yang dipertimbangkan (Blush & Rose, Sage & Terracotta, Champagne & Espresso), dipilih **Blush & Rose** dengan eksekusi lebih halus:

- Romantis klasik yang langsung terbaca "wedding", tanpa menjadi norak
- Palet blush-ivory + dusty rose + aksen gold tua yang lebih lembut dari token sekarang
- **Perubahan terbesar: tipografi.** Semua judul/heading pakai serif display (Fraunces); data & body tetap Instrument Sans agar tabel tetap rapat dan terbaca

Yang TIDAK berubah (tetap patuh CLAUDE.md §9): tidak ada glassmorphism, tidak ada gradien dekoratif, tidak ada animasi berlebihan, kartu tetap kompak (kepadatan informasi dijaga), status tetap selalu dengan label teks, fokus keyboard tetap terlihat.

## 3. Perubahan Konkret

### 3.1 Font display

- `vite.config.ts`: tambah `bunny('Fraunces', { weights: [400, 500, 600, 700], styles: ['normal','italic'] })` (Bunny Fonts, sudah dipakai untuk Instrument Sans — tanpa CDN baru)
- `resources/css/app.css`: tambah token `--font-display: 'Fraunces', Georgia, 'Times New Roman', serif;` dan expose sebagai `--font-display` di `@theme` (utility `font-display`)

### 3.2 Palet (resources/css/app.css)

Light (disaring agar lebih lembut dari sekarang):

- `--background: #fdf8f4` (ivory blush)
- `--foreground: #43333a` (espresso keunguan hangat)
- `--card / --popover: #fffcfa`
- `--primary: #9d5561`, `--primary-hover: #8a4751`, `--primary-foreground: #fffcfa`
- `--secondary / --muted: #f6e7e4`, `--muted-foreground: #8b7580`
- `--accent: #c99a5b` (gold tua lebih hangat)
- `--border / --input: #eedfe0`, `--ring: #9d5561`
- `--sidebar: #faf3f1`, `--sidebar-accent: #f1e2e2`, `--sidebar-border: #eadadb`
- Chart colors mengikuti nuansa rose/gold/espresso

Dark (warm charcoal, tanpa permukaan maroon berat):

- `--background: #2b2528`, `--foreground: #fdf6f2`
- `--card / --popover: #362e31`
- `--primary: #d696a0`, `--primary-hover: #e3abb3`
- `--secondary / --muted: #45383c`, `--muted-foreground: #d9c6cb`
- `--accent: #d9ad72`, `--border / --input: #57484d`
- `--sidebar: #322b2e`, `--sidebar-accent: #45383c`

Token semantik status (success/warning/info/destructive) **tidak diubah** — arti finansial tidak boleh terikat warna brand.

`app.blade.php`: warna latar html inline diganti ke nilai background baru (light & dark) agar tidak ada flash putih dingin.

### 3.3 Tipografi halaman

- `Heading` (`components/heading.tsx`): title pakai `font-display tracking-tight`
- Semua `<h1>` halaman (pola `text-3xl font-semibold tracking-tight`): tambah `font-display`
- `<h2>` section: biarkan sans-medium — hierarki h1 serif vs h2 sans menjaga kepadatan; kecuali heading besar section ("Ringkasan keuangan" dsb.) boleh `font-display` bila sudah `text-lg`
- `welcome.tsx` dan auth pages: hero/heading pakai `font-display` dengan tracking lebih ketat

### 3.4 Shell & komponen

- `app-logo.tsx`: wordmark pakai `font-display`; ikon tetap
- `nav-main.tsx` (state aktif sidebar): latar `sidebar-accent` + teks `sidebar-accent-foreground` (sudah ada via token, cukup pastikan terlihat jelas di palet baru)
- `card.tsx`: border default `border` (warna token baru), radius tetap `rounded-xl` — tidak ada perubahan struktur
- Tidak ada komponen baru; tidak ada layout baru; ukuran/spacing tidak berubah

### 3.5 Halaman yang disentuh

Semua file di `resources/js/Pages/**` yang punya `<h1>`/`Heading` (±36 file): hanya menambah kelas `font-display` pada judul. Struktur, logika, dan props TIDAK berubah sama sekali.

## 4. Testing & Verifikasi

- Tidak ada test baru (perubahan murni styling; M2 konvensi: jangan menguji styling statis)
- Gerbang: `npm run check`, `npm run types:check`, `npm run test`, `npm run build`, `php artisan test` (memastikan tidak ada yang rusak)
- Verifikasi visual manual di layar representatif: welcome, login, dashboard, ringkasan wedding, budget show

## 5. Risiko

- Bunny Fonts availability untuk Fraunces: Fraunces tersedia di Bunny (font open source, Google Fonts mirror). Fallback serif Georgia selalu ada di stack.
- Perubahan token warna bisa membuat kontras turun — semua kombinasi teks/latar baru dipilih dengan rasio kontras yang tetap aman (muted-foreground di atas background tetap ≥ 4.5:1).
