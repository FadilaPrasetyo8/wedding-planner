# M6 — Flexibility and Files Spec

**Status:** Authorized by user (17 September 2026). Sources: PRD FR-15, FR-16, ERD §12–13, implementation-plan M6, D-003, D-007, D-008, architecture file-storage sections.

## 1. Scope

Dua subsistem wedding-scoped, masing-masing vertical slice (migrasi → model → policy → controller/request → UI → test):

1. **Custom fields terbatas** (FR-16, D-007) — guest dulu, lalu task, checklist item, budget item
2. **Lampiran privat** (FR-15, D-003) — upload, signed URL, hapus, lifecycle

Di luar scope M6: export (M7), QR check-in, RSVP publik, vendor custom fields (D-005), formula/script builder (non-goal).

## 2. Keputusan Desain (menyelesaikan unresolved decisions ERD §12, §13, dan storage adapter)

### D-027 — Custom fields: satu tabel nilai JSONB bertipe definisi

ERD §13 menyisarkan bentuk penyimpanan nilai. **Diputuskan:**

- `custom_field_definitions`: `id, uuid, wedding_id (FK cascade), module enum string ('guest'|'task'|'checklist_item'|'budget_item'), field_type enum ('text','textarea','number','currency','date','select','multi_select','checkbox','url','file_reference'), label, field_key string, is_required bool default false, options jsonb nullable, sort_order int, is_active bool default true, timestamps`. Unique `(wedding_id, module, field_key)`, unique `(wedding_id, module, label)`, index `(wedding_id, module, sort_order)`.
- `custom_field_values`: `id, uuid, wedding_id (FK cascade), custom_field_definition_id (composite FK cascade), module enum string, subject_id bigint unsigned, value jsonb nullable, timestamps`. Composite FK `(wedding_id, custom_field_definition_id)` → `(wedding_id, id)`; unique `(wedding_id, custom_field_definition_id, subject_id)`; index `(wedding_id, module, subject_id)`.
- `field_key` dibuat otomatis dari label (slug, unik per module) dan tidak dapat bentrok dengan kolom inti; key digunakan untuk rendering, bukan kolom.
- **Nilai disimpan sebagai JSONB bertipe**: `{"v": <typed-value>}` di dalam `value`. Number/currency → JSON numeric; currency disimpan whole-rupiah integer (D-008); multi_select → array string; checkbox → boolean; date → ISO string; file_reference → UUID attachment (divalidasi same-wedding). Kolom JSONB tunggal dipilih agar perubahan tipe definisi tidak memerlukan migrasi per-tipe dan agar tipe tetap dapat divalidasi berdasarkan definisi.
- **Perubahan tipe definisi dilarang setelah definisi aktif memiliki nilai** (menjaga validitas historis; nonaktifkan dan buat baru jika tipe berbeda diperlukan). Ini adalah aturan server-side.
- **Subject link = composite tenant-enforced**: nilai merujuk subject melalui `(wedding_id, subject_id)` yang divalidasi aplikasi terhadap tabel subject yang sesuai module-nya; tidak ada polymorphic identifier tanpa penegakan (ERD §15). Definisi menyimpan `module`, dan setiap mutasi nilai memvalidasi `subject_id` dengan `Rule::exists` scoped `wedding_id` pada tabel subject modul itu.
- Required-ness divalidasi server-side pada create/update subject terkait: nilai required harus terisi (nilai existing tetap valid saat definisi dinonaktifkan — PRD: "Menonaktifkan field tidak menghapus nilai historis").
- Definisi yang dinonaktifkan: nilainya tetap tersimpan; tidak dirender di UI; tidak lagi divalidasi required-nya.
- Definisi tidak dapat dihapus selama masih memiliki nilai (`restrict`); nonaktifkan sebagai gantinya (PRD: nilai historis tidak dihapus permanen).
- **Tidak ada vendor module** (D-005) — CHECK constraint menolak module lain.

### D-028 — Attachments: typed nullable FK per modul, bukan polymorphic

ERD §12 menyisarkan strategi asosiasi. **Diputuskan: kolom FK nullable bertipe per modul** pada `attachments`, mengikuti pola `budget_items.vendor_id` M4 (D-023):

- `attachments`: `id, uuid, wedding_id (FK cascade), module enum string, budget_item_id? (composite FK nullOnDelete), payment_term_id? (composite FK nullOnDelete), checklist_item_id? (composite FK nullOnDelete), vendor_id? (composite FK nullOnDelete), guest_id? (composite FK nullOnDelete), task_id? (composite FK nullOnDelete), uploaded_by_member_id? (FK wedding_members nullOnDelete), disk string, path string, filename_original string, filename_stored, mime_type string, extension string, size_bytes bigint CHECK > 0, timestamps`.
- CHECK: tepat satu `*_id` non-null per baris (lampiran selalu milik satu subject).
- Path: `weddings/{wedding_uuid}/{module}/{uuid}.{ext}` — identifier immutable + namespace modul (CLAUDE.md §5.6), tidak pernah nama file asli.
- Metadata aktif hanya tercipta setelah objek tersimpan di storage — upload menyimpan objek dulu, lalu metadata dalam transaksi; kegagalan menyimpan metadata menghapus objek (kompensasi, FR-15: "Upload gagal tidak meninggalkan metadata palsu"). Sebaliknya, metadata tanpa objek (detected saat akses) ditandai dan tidak menghasilkan URL aktif.
- Hapus: hapus objek storage dulu; sukses → hapus metadata; gagal → metadata tetap dengan objek tetap ada (konsisten; tidak ada metadata aktif tanpa objek). Retensi: `attachments` cascade saat wedding dihapus di database; pembersihan objek storage menjadi prosedur rekonsiliasi.
- **Tidak ada attachment-operation record** (ERD §12: "only if reconciliation requires it") — rekonsiliasi M6 cukup membandingkan metadata dengan daftar objek storage via command.

### D-029 — Storage adapter: S3-compatible disk untuk Supabase, local private disk untuk dev/test

D-003 menetapkan Supabase Storage private behind Laravel. **Diputuskan:**

- Supabase Storage terekspos sebagai disk `supabase` Laravel (flysystem S3 v3 adapter, `endpoint = SUPABASE_URL + /storage/v3/s3`, `SUPABASE_BUCKET` private, `use_path_style_endpoint = true`). Service key hanya di server; **React tidak pernah menerima URL langsung maupun kredensial** (D-003, CLAUDE.md §5.6).
- Penambahan dependensi `league/flysystem-aws-s3-v3` disetujui (adapter resmi flysystem, bukan SDK penuh AWS) — ini satu-satunya dependensi PHP baru M6.
- Development dan automated test memakai disk `local` private yang sama (`storage/app/private`) — adapter sama (Laravel Storage facade), disk berbeda via config. Tidak ada koneksi Supabase di CI/test.
- Akses file selalu: request → policy cek membership + subject scope → `Storage::disk(...)->temporaryUrl($path, now()->addMinutes(10))` — signed URL berumur pendek, tidak pernah URL permanen (FR-15).
- Upload route memakai middleware `verified` (PRD §136: verifikasi email menggate fitur upload) di samping `wedding.member` + policy.
- Validasi server-side: extension whitelist (pdf, jpg, jpeg, png, webp), MIME terdeteksi (`UploadedFile::getMimeType` / guessExtension dari konten), ukuran ≤ 10 MB, nama file asli disanitasi untuk display saja (CLAUDE.md §5.6).

### Konvensi lintas-modul (mengikuti M2–M5)

- Semua tabel: `id, uuid unique, wedding_id FK cascade, composite FK (wedding_id, parent_id)`, index diawali `wedding_id`.
- Uang (custom field currency): BIGINT whole-rupiah (D-008).
- Policy per model (owner/editor mutasi, viewer read-only; signed URL: semua membership aktif boleh membaca).
- Route model binding tidak cukup — policy selalu.
- Mutasi file & custom-field definition dicek auditable via activity_logs untuk delete.

## 3. Rute & Navigasi

Di dalam prefix `weddings/{wedding:uuid}` + `scopeBindings()`:

- `custom-fields?module={guest|task|checklist_item|budget_item}` — index (definisi per module), store, update, destroy (restricted saat masih ada nilai; nonaktifkan via update `is_active=false`)
- Nilai custom field disimpan bersama subject: `custom-values` store/update/destroy menerima `{module, subject_uuid, values: {field_key: value}}` dan divalidasi per definisi aktif module itu
- `attachments` — `POST attachments` (upload multipart: module + subject_uuid + file), `GET attachments/{attachment}/download` (redirect ke signed URL), `DELETE attachments/{attachment}`; upload + destroy memakai middleware `verified`

Sidebar: entri **Kustomisasi** (Settings2 icon) di grup Ruang kerja, mengarah ke halaman kelola definisi per module dengan SectionTabs (Tamu/Tugas/Checklist/Anggaran).

## 4. UI

- Halaman `custom-fields` mengikuti pola configuration M2: SectionTabs per module, tabel definisi (label, tipe, key, wajib, aktif, urutan), form inline tambah/ubah, nonaktifkan (bukan hapus) sebagai aksi utama, hapus restricted dengan pesan.
- Renderer nilai custom field pada form subject: input sesuai `field_type` (text/textarea/number/currency/date/select/multi_select/checkbox/url/file_reference) — komponen `CustomFieldValueInput` reusable; nilai dirender pada detail subject.
- Upload: tombol "Unggah" per subject (budget item detail, payment term, checklist item, vendor, guest, task) dengan input file; daftar attachment (nama asli, ukuran, tanggal) dengan tautan "Unduh" (signed URL via controller, bukan tautan langsung) dan hapus dua-langkah (ConfirmSubmit). Gagal upload menampilkan error dan tidak meninggalkan baris metadata.
- Viewer: melihat daftar + mengunduh; tidak bisa upload/hapus (badge Mode hanya lihat).

## 5. Testing

Backend (PHPUnit):
- Definisi CRUD + validasi (module whitelist, tipe whitelist, key unik per module per wedding, label unik)
- Isolasi: definisi/nilai Wedding A tidak muncul di B; subject UUID lintas wedding ditolak
- Nilai per tipe divalidasi benar (number, currency whole-rupiah, date, select dari options, multi_select subset options, checkbox bool, url, file_reference = attachment UUID same-wedding)
- Required custom field menolak create/update subject tanpa nilai; nonaktif tidak divalidasi lagi
- Perubahan tipe definisi dengan nilai existing ditolak
- Hapus definisi dengan nilai → restrict; nonaktif → boleh, nilai tetap ada
- Attachment: upload menyimpan objek + metadata; MIME/extension/size tidak valid ditolak; unverified user ditolak upload; signed URL hanya untuk membership aktif (non-member 403, lintas wedding 403/404); delete menghapus objek+metadata; delete objek gagal → metadata tetap tanpa URL palsu
- Upload multipart yang gagal di storage → tidak ada metadata tersisa (kompensasi)

Frontend (Vest, hanya logic bermakna):
- Helper renderer custom-field value (format per tipe) bila non-trivial

## 6. Exit Criteria (dari implementation-plan M6)

Custom fields tidak pernah menggantikan kolom inti finansial/ownership/authorization/relasi/tanggal terindeks; tidak ada vendor custom field; definisi & nilai tidak bisa lintas wedding; tipe & ukuran file ditegakkan server-side; user tak berwenang tidak bisa mendapat signed URL; upload/hapus gagal tidak meninggalkan metadata aktif palsu atau state tak terkelola.
