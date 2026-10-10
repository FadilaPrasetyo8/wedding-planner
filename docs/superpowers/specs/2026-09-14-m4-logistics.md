# M4 — Logistics Spec

**Status:** Authorized by user (14 September 2026). Sources: PRD FR-09–FR-12, ERD §8–10, implementation-plan M4, D-005, D-008, D-021.

## 1. Scope

Empat modul wedding-scoped, masing-masing vertical slice (migrasi → model → policy → controller/request → UI → test):

1. **Checklist seserahan** (FR-09)
2. **Checklist administrasi** (FR-10)
3. **Kontak vendor privat** (FR-11, D-005)
4. **Daftar tamu** (FR-12)

Di luar scope M4: import/export tamu (pasca-CRUD stabil, M7 per D-009), upload attachment (M6), custom field (M6), QR check-in / RSVP publik (post-V1).

## 2. Keputusan Desain (menyelesaikan unresolved decisions ERD)

### D-022 — Satu struktur checklist bertipe untuk seserahan & administrasi

ERD §8 menyisakan pilihan: satu struktur bertipe atau dua tabel khusus. **Diputuskan: satu struktur.** Kedua modul punya bentuk identik (item + kategori + status + biaya + deadline + assignee + note); memisahnya hanya menggandakan migrasi, policy, controller, dan UI tanpa perilaku berbeda. Pembeda: kolom `type` (`seserahan` | `administration`) pada `checklist_groups`, `checklist_categories`, `checklist_statuses`, dan `checklist_items`, dengan unique `(wedding_id, type, name)` — sehingga label dua modul tidak saling bocor. Perilaku khusus modul (URL pembelian seserahan, completed_at administrasi) jadi kolom nullable pada `checklist_items` yang hanya divalidasi/dipakai sesuai type.

- `checklist_items` kolom: `id, uuid, wedding_id, type, checklist_group_id?, checklist_category_id?, checklist_status_id, name, detail/notes?, quantity (default 1, ≥1), estimated_cost BIGINT ≥0?, actual_cost BIGINT ≥0?, deadline date?, completed_at timestamp?, assignee_member_id? (FK wedding_members, nullOnDelete), purchase_url? (valid URL, khusus seserahan), timestamps`
- Keluarga status: **controlled rows, bukan enum** (PRD FR-09: "Status default dapat dikustomisasi") — mengikuti pola task_statuses M2. Default seserahan: Belum Dibeli, Sudah Dibeli, Dikirim, Selesai. Default administrasi: Belum Diproses, Diproses, Selesai.
- Kelompok & kategori default seserahan dari workbook (PRD §169): perhiasan, pakaian, perlengkapan ibadah, makanan ringan, perlengkapan rumah tangga, lainnya. Administrasi dikelompokkan per penanggung jawab / event (group optional).
- Status "selesai" menyimpan `completed_at` (FR-10): kolom `is_completed` pada `checklist_statuses` menandai status final; item yang berpindah ke status completed mengisi `completed_at` otomatis di server.
- Item overdue = deadline < hari ini DAN status belum completed — dihitung server-side dan/atau flag turunan, tidak pernah hanya dari UI.
- Ringkasan: total estimasi (estimated_cost × quantity), total aktual, jumlah item per status.

### D-023 — Vendor: PIC sebagai child records `vendor_contacts`

ERD §9 menyisarkan PIC on `vendors` vs `vendor_contacts`. **Diputuskan: child records.** PRD FR-11 meminta PIC, telepon, email — vendor nyatanya punya beberapa orang kontak (owner studio, admin WA, dll). Maka:

- `vendor_categories`: `id, uuid, wedding_id, name, sort_order?, timestamps`, unique `(wedding_id, name)`. Default: Venue, Katering, Dokumentasi, Dekorasi, Musik & Hiburan, Pakaian & Rias, Cetak & Souvenir, Lainnya.
- `vendors`: `id, uuid, wedding_id, vendor_category_id (FK composite, restrict), name, status enum string, internal_rating tinyint 0–5 nullable, address?, website?, social_media?, notes?, timestamps`. Index `(wedding_id, status)`, `(wedding_id, vendor_category_id)`.
- `vendor_contacts`: `id, uuid, wedding_id, vendor_id (FK composite cascade), name, phone?, email?, role/label?, is_primary bool, timestamps`.
- `status` enum aplikasi (`App\Enums\VendorStatus`): `candidate, contacted, negotiating, selected, rejected, done` (PRD: Kandidat, Dihubungi, Negosiasi, Terpilih, Ditolak, Selesai). Enum karena daftar ini fix di PRD dan tidak dikustomisasi — berbeda dari status checklist.
- Telepon selalu `string` (PRD: leading zero tidak boleh hilang).
- **Tautan ke budget item**: kolom nullable `vendor_id` (+ `wedding_id` composite FK) ditambahkan ke `budget_items` lewat migrasi M4 — vendor terpilih direlasikan langsung, tidak ada tabel duplikat "selected vendors" (FR-11, D-005). Satu vendor boleh menaut banyak budget item; satu budget item menaut satu vendor.
- **Tanpa** akun vendor, listing publik, marketplace, portal, atau vendor custom field (D-005).

### D-024 — Guest: sides/groups sebagai baris kontrol, invitation & RSVP sebagai enum

ERD §10 menyisarkan pembagian controlled-table vs enum. **Diputuskan:**

- `guest_sides` dan `guest_groups` = baris kontrol wedding-scoped (PRD: label dapat disesuaikan; default side: Mempelai 1, Mempelai 2, Bersama; default group: Teman, Kolega, Keluarga, Tetangga, Teman Orang Tua, VIP). Kolom: `id, uuid, wedding_id, name, sort_order, timestamps`; unique `(wedding_id, name)`.
- `guest_invitation_statuses` **tidak** dibuat tabel: PRD tidak menetapkan daftar invitation status yang bisa dikustomisasi. Invitation state = enum aplikasi `App\Enums\GuestInvitationStatus`: `not_sent, sent, delivered, failed` (Belum dikirim, Terkirim, Diterima, Gagal). RSVP = enum `App\Enums\GuestRsvpStatus`: `pending, attending, not_attending, tentative` — nullable/opsional.
- `guests`: `id, uuid, wedding_id, guest_side_id (FK composite nullOnDelete), guest_group_id (FK composite nullOnDelete), name (wajib), phone? (string), invitation_count int ≥0 default 1, pax_count int ≥0 default 1, invitation_status enum default not_sent, rsvp_status enum nullable, notes?, timestamps`. Index `(wedding_id, guest_side_id)`, `(wedding_id, guest_group_id)`, `(wedding_id, invitation_status)`.
- Agregat per side & per group dihitung server-side (total tamu, total pax, total undangan) dan konsisten dengan filter aktif (PRD AC: "Total grup sama dengan agregasi guest pada filter yang aktif").

### Konvensi lintas-modul (mengikuti M2/M3)

- Semua tabel: `id, uuid unique, wedding_id FK cascade, composite FK `(wedding_id, parent_id)` → `(wedding_id, id)`, `unique(wedding_id, id)`, index diawali `wedding_id`.
- Uang: BIGINT whole-rupiah dengan CHECK ≥ 0 (D-008), diformat hanya di presentation boundary.
- Policy per model (owner/editor boleh mutasi, viewer read-only); route model binding tidak cukup.
- Money/count totals selalu server-authoritative.
- UI M4 langsung memakai desain hangat baru (font-display heading, palet blush-rose).

## 3. Rute & Navigasi

Di dalam prefix `weddings/{wedding:uuid}` + `scopeBindings()`, mengikuti pola finance:

- `checklists/{type}` (seserahan|administration) — index/show/store/update/destroy + `statuses` & `categories`/`groups` CRUD ringan (pola configuration M2)
- `vendors` — index/store/update/destroy, `vendors/categories` CRUD ringan; `vendors/{vendor}/contacts` store/update/destroy; `vendors/{vendor}/status` via update biasa
- `guests` — index/store/update/destroy; `guests/sides` & `guests/groups` CRUD ringan
- Migrasi budget_items: `vendors` update memilih vendor pada budget item (kolom vendor_id di budget show/edit)

Sidebar nav baru (setelah "Keuangan"): **Seserahan**, **Administrasi**, **Vendor**, **Tamu** (dengan ikon lucide: Gift, FileCheck/ClipboardCheck, Store/Building2, Users → tamu pakai UserRound/UsersRound agar beda dari "Anggota").

## 4. UI

Mengikuti pola M2/M3 yang sudah ada: tabel desktop + article mobile, pill status dengan label teks, `tabular-nums` untuk uang, form inline (bukan modal untuk CRUD utama), filter/search/sort/pagination via query string, badge "Mode hanya lihat" untuk viewer, empty/loading/error/success/destructive-confirm states. Ringkasan checklist (estimasi vs aktual, per status) dan agregat tamu (per side/group) di halaman index masing-masing.

## 5. Testing

Backend (PHPUnit, pola M3):
- Feature CRUD per modul + validasi (URL pembelian valid, quantity ≥ 1, pax ≥ 0, phone string, nama wajib)
- Policy matrix (owner/editor/viewer; viewer ditolak mutasi; user tanpa membership 403)
- Isolasi 3-wedding untuk keluarga checklist, vendor, guest (data Wedding A tidak muncul di B; composite FK menolak relasi lintas wedding)
- Item overdue & completed_at benar; ringkasan estimasi (× quantity) & aktual benar
- Vendor status enum valid; vendor–budget_item link; hapus kategori vendor di-restrict selama masih dipakai
- Agregat guest per side/group konsisten dengan filter

Frontend (Vitest, hanya logic bermakna — pola M2/M3):
- Helper murni checklist (mis. overdue determination, summary math) bila non-trivial
- Helper murni guest aggregate formatting bila non-trivial

## 6. Exit Criteria (dari implementation-plan M4)

Semua record & relasi wedding-scoped; total checklist dan status overdue benar; vendor terpilih lewat status+relasi tanpa tabel duplikat; tidak ada akun/listing/marketplace/portal/custom-field vendor; hitungan & label tamu terisolasi antar wedding; viewer tidak bisa mutasi.
