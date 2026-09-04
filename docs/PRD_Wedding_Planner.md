**PRODUCT REQUIREMENTS DOCUMENT**

Wedding Planner

Internal V1 yang siap tumbuh menjadi SaaS multi-user

**Dokumen:** Product Requirements Document (PRD)

**Versi:** 1.0 — Baseline siap persetujuan implementasi

**Tanggal:** 2 September 2026

**Product owner:** Fadila Prasetyo

**Target rilis:** Internal/public early access tanpa billing

**Sumber kebutuhan:** Wedding Planner ‘Bismillah Nikah’ Zaldi & Vita.xlsx

> **Keputusan produk:** Aplikasi menerima registrasi banyak akun sejak rilis awal. Setiap pengguna dapat membuat wedding workspace sendiri dengan data terisolasi. Seluruh fitur utama pada workbook masuk ke internal V1; subscription, payment gateway, marketplace vendor, dan white-label belum termasuk.

# Ringkasan Eksekutif

Wedding Planner adalah aplikasi web untuk membantu pasangan mengelola seluruh persiapan pernikahan dalam satu workspace: timeline, skenario budget, anggaran aktual, target tabungan, seserahan, administrasi, vendor, tamu, rundown, dan playlist. Produk pertama-tama dipakai oleh Fadila dan lingkaran pengguna awal, tetapi registrasi tidak dibatasi sehingga teman lain dapat membuat akun dan wedding mereka sendiri.

Sistem dibangun sebagai Laravel monolith dengan React melalui Inertia.js. PostgreSQL menjadi source of truth untuk seluruh data aplikasi, sedangkan Supabase Storage digunakan untuk file privat. Fondasi multi-user dan membership disiapkan sejak hari pertama agar penambahan monetisasi di masa depan tidak membutuhkan perombakan data inti.

# Daftar Isi

1.  Konteks, masalah, dan tujuan produk

2.  Pengguna, role, dan model workspace

3.  Ruang lingkup internal V1

4.  User journey dan struktur navigasi

5.  Kebutuhan fungsional per modul

6.  Strategi customization dan dynamic field

7.  Model data tingkat tinggi

8.  Arsitektur, file storage, dan keamanan

9.  Non-functional requirements

10. Milestone implementasi dan acceptance gate

11. Pengembangan menuju bisnis SaaS

# 1. Konteks Produk

## 1.1 Latar Belakang

Perencanaan pernikahan umumnya tersebar di spreadsheet, chat, catatan, marketplace, dan dokumen. Workbook referensi sudah menggabungkan banyak kebutuhan penting, tetapi mempunyai keterbatasan: sulit dipakai bersama lintas perangkat, data antar-pengguna tidak terisolasi secara aman, format pembayaran vendor kaku, dan beberapa fungsi bergantung pada kemampuan aplikasi spreadsheet tertentu.

Produk ini memindahkan struktur tersebut menjadi aplikasi domain-specific. Modulnya tetap jelas sehingga laporan dan perhitungan dapat dipercaya, tetapi item, kategori, acara, status, jumlah pembayaran, dan field tambahan dapat disesuaikan per wedding.

## 1.2 Problem Statement

> **Masalah utama:** Pasangan membutuhkan satu tempat yang aman, fleksibel, dan mudah dipakai untuk melihat apa yang harus dikerjakan, berapa uang yang direncanakan dan sudah dibayar, siapa vendor dan tamunya, serta apa yang terjadi pada hari acara.

- Task persiapan mudah terlupakan karena tidak terhubung dengan tanggal acara.

- Budget awal, harga final, pembayaran bertahap, dan sisa tagihan sering tercampur.

- Dokumen, bukti pembayaran, serta kontak vendor tersebar di berbagai tempat.

- Spreadsheet dengan kolom tetap tidak mampu menangani jumlah termin atau kategori yang berbeda.

- Template yang sama perlu digunakan oleh banyak pasangan tanpa membuat data mereka saling terlihat.

## 1.3 Tujuan Produk

- Menyediakan registrasi dan login untuk banyak pengguna sejak rilis awal.

- Memisahkan seluruh data berdasarkan wedding workspace dan membership.

- Mengimplementasikan seluruh modul yang terdapat pada workbook referensi.

- Mendukung data yang fleksibel tanpa mengorbankan perhitungan dan reporting.

- Menjadi aplikasi yang benar-benar dipakai untuk wedding aktual, bukan sekadar demo portofolio.

- Menyiapkan fondasi agar paket berbayar dapat ditambahkan setelah produk tervalidasi.

## 1.4 Non-Goals Internal V1

- Subscription, payment gateway, invoice SaaS, trial berbayar, dan pembatasan paket.

- Marketplace vendor dan transaksi antara pasangan dengan vendor.

- Website undangan publik, QR check-in, dan RSVP otomatis dari tamu.

- White-label khusus wedding organizer.

- Aplikasi mobile native; V1 berupa responsive web application.

- Generic no-code form builder dengan formula atau conditional logic bebas.

## 1.5 Prinsip Produk

- Private by default: data wedding hanya terlihat oleh anggota yang sah.

- Structured but flexible: data inti terstruktur, detail tambahan dapat dikustomisasi.

- Actual usage first: keputusan fitur didasarkan pada pemakaian wedding nyata.

- Financial clarity: nominal rencana, aktual, dibayar, dan sisa harus selalu dapat dibedakan.

- Simple collaboration: anggota workspace memahami apa yang boleh mereka lakukan.

# 2. Pengguna dan Model Workspace

## 2.1 Target Pengguna Awal

| **Persona**    | **Kebutuhan utama**                                     | **Perilaku yang diharapkan**                    |
|----------------|---------------------------------------------------------|-------------------------------------------------|
| Wedding Owner  | Membuat dan mengatur workspace wedding.                 | Mengelola seluruh modul dan mengundang anggota. |
| Pasangan       | Berkolaborasi dalam budget, task, dan keputusan vendor. | Menambah atau memperbarui data sesuai izin.     |
| Keluarga/teman | Membantu bagian tertentu dari persiapan.                | Mengakses workspace melalui undangan.           |
| WO/Planner     | Memantau task, vendor, dan rundown.                     | Mengakses data yang dibagikan owner.            |

## 2.2 Role dan Hak Akses

| **Role** | **Hak akses**                                                  | **Batasan**                                                       |
|----------|----------------------------------------------------------------|-------------------------------------------------------------------|
| Owner    | Kelola seluruh data, anggota, pengaturan, dan hapus workspace. | Tidak dapat meninggalkan workspace sebelum memindahkan ownership. |
| Editor   | Tambah, ubah, dan hapus data operasional.                      | Tidak dapat menghapus workspace atau memindahkan owner.           |
| Viewer   | Melihat dashboard dan data yang diizinkan.                     | Tidak dapat melakukan mutation.                                   |

> **Aturan isolasi:** Satu akun boleh menjadi anggota lebih dari satu wedding. Semua query harus divalidasi melalui membership; mengetahui ID atau URL wedding lain tidak memberikan akses.

## 2.3 Model Registrasi

1.  Pengunjung membuka halaman register dan membuat akun dengan nama, email, serta password.

2.  Sistem melakukan verifikasi email sebelum fitur sensitif seperti invitation dan upload file digunakan.

3.  User baru masuk ke onboarding dan memilih membuat wedding baru atau menerima invitation.

4.  Saat membuat wedding, user otomatis menjadi owner dan memilih template awal.

5.  User dapat membuat wedding tambahan atau bergabung ke wedding lain tanpa akun baru.

# 3. Ruang Lingkup Internal V1

Internal V1 berarti fitur produk lengkap untuk perencanaan wedding, tersedia bagi banyak akun tanpa billing. Label internal menunjukkan fase validasi bisnis, bukan pembatasan jumlah pengguna.

| **Area**               | **Status V1** | **Catatan**                                                |
|------------------------|---------------|------------------------------------------------------------|
| Registrasi dan login   | Must          | Terbuka untuk banyak akun; session-based auth.             |
| Wedding workspace      | Must          | Multi-wedding dan membership sejak awal.                   |
| Seluruh modul workbook | Must          | Semua fitur Excel masuk secara bertahap.                   |
| Customization          | Must          | Kategori, acara, status, grup, termin, dan custom field terbatas. |
| File privat            | Must          | Supabase Storage; metadata di PostgreSQL.                  |
| Export                 | Should        | Excel lebih dulu setelah CRUD stabil; PDF menyusul.        |
| Billing/subscription   | Excluded      | Dirancang sebagai extension masa depan.                    |

## 3.1 Pemetaan Workbook ke Produk

| **Sheet sumber**    | **Modul aplikasi** | **Output utama**                                      |
|---------------------|--------------------|-------------------------------------------------------|
| Dashboard           | Dashboard          | Countdown, progres, ringkasan finansial dan task.     |
| Timeline            | Timeline Planning  | Task, status, durasi, tanggal dan tampilan Gantt.     |
| Skenario Budget     | Budget Scenario    | Preset dan skenario budget yang dapat disalin.        |
| Anggaran Pernikahan | Wedding Budget     | Budget per acara, pembayaran fleksibel, sisa tagihan. |
| Target Tabungan     | Savings            | Target, kontribusi, progres per kontributor.          |
| List Seserahan      | Gift Checklist     | Item, harga, link, kategori, status.                  |
| List Administrasi   | Document Checklist | Dokumen, biaya, deadline, status, attachment.         |
| Kontak Vendor       | Vendor CRM         | Kandidat, PIC, kontak, status dan vendor terpilih.    |
| List Tamu           | Guest List         | Pihak, grup, pax, kontak dan status undangan.         |
| Rundown Acara       | Event Rundown      | Waktu, aktivitas, PIC, keterangan, highlights.        |
| List Lagu           | Playlist           | Lagu, penyanyi, link dan momen pemutaran.             |

# 4. User Journey dan Navigasi

## 4.1 Primary Journey

1.  Register atau login.

2.  Membuat wedding workspace dan mengisi nama pasangan, tanggal, zona waktu, serta budget target.

3.  Memilih template default ‘Bismillah Nikah’, template lain, atau mulai kosong.

4.  Sistem menyalin item template menjadi data milik wedding tersebut.

5.  User mengatur timeline, budget, tabungan, checklist, vendor, tamu, rundown, dan playlist.

6.  User mengundang pasangan atau kolaborator melalui email.

7.  Dashboard terus memperbarui progres dan ringkasan dari seluruh modul.

## 4.2 Struktur Navigasi

- Public: landing, login, register, forgot password, reset password.

- Account: profile, password, logout, daftar invitation.

- Workspace: dashboard, timeline, budget scenario, wedding budget, savings, seserahan, administrasi, vendor, guests, rundown, playlist, files, members, settings.

- Switcher: berpindah wedding bila user memiliki lebih dari satu membership.

- Future-only: pricing, billing, admin SaaS, subscription management.

## 4.3 Route Konseptual

| **Route**                     | **Tujuan**                                       |
|-------------------------------|--------------------------------------------------|
| /register, /login             | Authentication publik.                           |
| /onboarding                   | Membuat wedding atau menerima invitation.        |
| /weddings                     | Daftar wedding milik/diikuti user.               |
| /weddings/{wedding}/dashboard | Ringkasan satu wedding.                          |
| /weddings/{wedding}/{module}  | Halaman modul yang selalu scoped ke wedding.     |
| /weddings/{wedding}/settings  | Identitas, customization, anggota, dan template. |

# 5. Kebutuhan Fungsional

## FR-01 — Authentication & Account

**Tujuan:** Memungkinkan banyak pengguna mendaftar dan mengakses data mereka dengan aman.

**Kebutuhan fungsional**

- Register menggunakan nama, email unik, password, dan konfirmasi password.

- Login, logout, forgot password, reset password, serta verifikasi email.

- Profile dapat memperbarui nama, avatar opsional, email, dan password.

- Session tetap aktif sesuai konfigurasi keamanan Laravel dan dapat diakhiri dari perangkat saat ini.

**Aturan bisnis**

- Email bersifat unik case-insensitive.

- Guest tidak boleh mengakses route workspace.

- Perubahan email memerlukan verifikasi ulang.

**Acceptance criteria**

- Dua user berbeda dapat login bersamaan dan hanya melihat wedding yang menjadi membership mereka.

- Reset password menghasilkan token satu kali yang memiliki masa berlaku.

## FR-02 — Wedding Workspace & Membership

**Tujuan:** Menjadi boundary utama seluruh data wedding dan kolaborasi.

**Kebutuhan fungsional**

- User dapat membuat lebih dari satu wedding.

- Wedding menyimpan nama workspace, nama pasangan, tanggal acara utama, tanggal mulai persiapan, timezone, mata uang, dan status.

- Owner dapat mengundang anggota melalui email sebagai editor atau viewer.

- User dapat menerima, menolak, atau membatalkan invitation yang masih pending.

- Workspace switcher menampilkan semua wedding yang dapat diakses user.

**Aturan bisnis**

- Creator otomatis menjadi owner.

- Kombinasi wedding dan user pada membership harus unik.

- Semua operasi menggunakan Wedding Policy atau authorization layer setara.

- Penghapusan wedding memerlukan konfirmasi eksplisit dan bersifat soft delete pada fase awal.

**Acceptance criteria**

- User tanpa membership mendapatkan respons forbidden ketika mengakses URL wedding lain.

- Perubahan pada satu wedding tidak memengaruhi wedding lain walaupun template awalnya sama.

## FR-03 — Onboarding & Template

**Tujuan:** Membuat setup awal cepat tanpa mengunci user pada isi template.

**Kebutuhan fungsional**

- Onboarding mengumpulkan identitas wedding, tanggal, event awal, dan target budget.

- User dapat memilih template default, template alternatif, atau blank workspace.

- Template default memuat task, skenario budget, checklist administrasi, kategori seserahan, rundown contoh, dan playlist dari workbook.

- Task template menggunakan offset relatif terhadap wedding date jika memungkinkan.

**Aturan bisnis**

- Proses cloning harus atomic; kegagalan tidak boleh menghasilkan workspace setengah terisi.

- Setelah dicloning, semua item menjadi milik wedding dan bebas diubah tanpa memodifikasi master template.

**Acceptance criteria**

- Dua wedding yang memilih template sama memperoleh salinan data independen.

- Mengubah template master di masa depan tidak mengubah data wedding yang sudah dibuat.

## FR-04 — Dashboard

**Tujuan:** Memberikan keadaan wedding dalam satu layar yang mudah dipahami.

**Kebutuhan fungsional**

- Menampilkan nama wedding, tanggal acara utama, dan countdown hari.

- Menampilkan progres task selesai dibanding total task aktif.

- Menampilkan budget target, total estimasi/final, total dibayar, dan sisa tagihan.

- Menampilkan target tabungan, total terkumpul, serta gap.

- Menampilkan task terdekat, pembayaran jatuh tempo, dan dokumen yang belum selesai.

- Grafik budget dan savings menggunakan data aktual, bukan angka hardcoded.

**Aturan bisnis**

- Semua agregat wajib scoped ke wedding aktif.

- Countdown menggunakan timezone wedding.

- Card dengan data kosong menampilkan empty state dan CTA yang relevan.

**Acceptance criteria**

- Setelah payment atau task diperbarui, dashboard menampilkan angka terbaru pada request berikutnya.

- Tidak ada angka dari wedding lain yang masuk ke agregasi.

## FR-05 — Timeline Planning

**Tujuan:** Mengelola seluruh aktivitas persiapan dengan tampilan daftar dan timeline.

**Kebutuhan fungsional**

- CRUD task dengan judul, deskripsi, event, kategori, start date, due date, status, priority, assignee, dan notes.

- List view mendukung search, filter, sort, dan pagination.

- Gantt/timeline view memperlihatkan durasi task terhadap rentang tanggal.

- User dapat menambahkan status dan kategori sendiri.

- Task dapat dibuat dari offset relatif wedding date saat template dicloning.

**Aturan bisnis**

- Due date tidak boleh lebih awal dari start date.

- Status default minimal: Belum Mulai, Proses, Selesai, Dibatalkan.

- Task selesai memiliki completed_at; mengembalikan status membuka kembali task.

**Acceptance criteria**

- User dapat membuat, memindahkan tanggal, menyelesaikan, dan memfilter task.

- Tampilan Gantt konsisten dengan tanggal pada list view.

## FR-06 — Budget Scenario

**Tujuan:** Membantu user membandingkan alternatif alokasi budget sebelum menetapkan anggaran aktual.

**Kebutuhan fungsional**

- Menyediakan skenario awal Rp10 juta, Rp20 juta, Rp30 juta, Rp50 juta, Rp75 juta, dan Rp100 juta dari workbook.

- User dapat membuat, menyalin, mengganti nama, dan menghapus skenario sendiri.

- Setiap skenario berisi item, kategori, event, nominal, dan catatan.

- Skenario dapat diterapkan menjadi draft Wedding Budget tanpa menghapus data aktual yang sudah ada.

**Aturan bisnis**

- Total skenario adalah penjumlahan item aktif.

- Nominal menggunakan `BIGINT` whole-rupiah di PostgreSQL; bukan floating-point atau string format tampilan.

**Acceptance criteria**

- Perubahan item langsung mengubah total skenario.

- Menyalin skenario menghasilkan record independen.

## FR-07 — Wedding Budget & Flexible Payments

**Tujuan:** Mengelola anggaran lamaran, akad, resepsi, atau event custom serta pembayaran tanpa jumlah termin tetap.

**Kebutuhan fungsional**

- CRUD budget item dengan event, kategori, vendor, nama kebutuhan, estimasi, harga final, status, dan notes.

- Satu budget item dapat memiliki payment term tanpa batas: booking fee, DP, Termin 1–N, atau pelunasan.

- Payment term menyimpan nama, urutan, nominal rencana, due date, status, nominal dibayar, paid_at, note, dan bukti pembayaran.

- Sistem menghitung total tagihan, total dibayar, dan outstanding per item, kategori, event, serta wedding.

- User dapat reorder payment term dan mengubah labelnya.

**Aturan bisnis**

- Tidak ada kolom database tetap bernama DP, Termin 1, atau Termin 2.

- Total dibayar berasal dari payment term berstatus paid atau nominal aktual yang tervalidasi.

- Overpayment menampilkan warning; sistem tidak diam-diam mengubah final amount.

- Perubahan nominal finansial dicatat pada activity log minimal untuk actor dan timestamp.

**Acceptance criteria**

- User dapat membuat sepuluh termin pada satu vendor tanpa perubahan schema.

- Sisa tagihan sama dengan final amount dikurangi total pembayaran valid.

- Bukti pembayaran tersimpan privat dan hanya dapat diakses anggota wedding.

## FR-08 — Target Tabungan

**Tujuan:** Memantau kontribusi dana wedding dari satu atau beberapa pihak.

**Kebutuhan fungsional**

- User dapat menetapkan target tabungan dan tanggal target.

- CRUD kontribusi dengan kontributor, tanggal, nominal, sumber dana, dan catatan.

- Kontributor dapat berupa anggota workspace atau label custom.

- Dashboard menampilkan total terkumpul, gap, persentase, dan tren bulanan.

**Aturan bisnis**

- Kontribusi tidak dicampur dengan pembayaran vendor.

- Penghapusan kontribusi memperbarui agregat tanpa menghapus histori lain.

**Acceptance criteria**

- Total kontribusi sama dengan jumlah semua kontribusi aktif pada wedding.

- User dapat melihat breakdown per kontributor dan per bulan.

## FR-09 — List Seserahan

**Tujuan:** Mengelola kebutuhan seserahan sebagai checklist yang dapat disesuaikan.

**Kebutuhan fungsional**

- CRUD item dengan nama kebutuhan, kategori, link pembelian, estimasi harga, harga aktual, quantity, status, assignee, dan note.

- Kategori default mengambil referensi workbook dan dapat ditambah atau diubah per wedding.

- User dapat membuka link pembelian dan menandai item selesai.

- Ringkasan menampilkan total estimasi, aktual, dan jumlah item per status.

**Aturan bisnis**

- Link harus divalidasi sebagai URL ketika diisi.

- Status default dapat dikustomisasi tanpa menghapus status yang masih digunakan.

**Acceptance criteria**

- Total biaya berubah sesuai item dan quantity.

- Filter kategori dan status hanya menampilkan item wedding aktif.

## FR-10 — Administrasi Pernikahan

**Tujuan:** Memastikan dokumen dan persyaratan administratif dapat dilacak sampai selesai.

**Kebutuhan fungsional**

- CRUD checklist dokumen dengan nama, detail, pihak penanggung jawab, deadline, biaya, status, dan catatan.

- User dapat mengunggah scan/foto dokumen yang relevan.

- Template awal memuat daftar persyaratan administrasi dari workbook.

- List dapat dikelompokkan berdasarkan status, penanggung jawab, atau event.

**Aturan bisnis**

- Attachment administrasi selalu private.

- Status selesai menyimpan completed_at.

- Dokumen sensitif tidak boleh terekspos melalui public URL permanen.

**Acceptance criteria**

- Anggota berizin dapat membuka file melalui URL sementara.

- Item overdue dapat dikenali dari deadline dan status.

## FR-11 — Vendor Management

**Tujuan:** Mencatat kandidat vendor, membandingkan pilihan, dan menautkan vendor terpilih ke budget.

**Kebutuhan fungsional**

- CRUD vendor dengan kategori, nama, PIC, telepon, email, alamat, sosial media, website, status, rating internal, dan notes.

- Status minimal: Kandidat, Dihubungi, Negosiasi, Terpilih, Ditolak, Selesai.

- Vendor dapat ditautkan ke satu atau lebih budget item.

- Attachment vendor dapat berisi quotation, kontrak, invoice, atau moodboard.

**Aturan bisnis**

- Vendor terpilih tidak disalin ke tabel kedua; status dan relasi menjadi source of truth.

- Vendor adalah catatan privat yang dibuat anggota wedding. V1 tidak memiliki akun vendor, registrasi vendor, listing publik, marketplace, portal vendor, atau vendor custom field.

- Nomor telepon disimpan sebagai text agar format dan leading zero tidak hilang.

**Acceptance criteria**

- Mengubah status kandidat menjadi Terpilih langsung tercermin pada filter vendor terpilih.

- Vendor dapat memiliki beberapa budget item dan payment term berbeda.

## FR-12 — Guest List

**Tujuan:** Mengelola daftar tamu dari kedua pihak dan menghitung kapasitas undangan.

**Kebutuhan fungsional**

- CRUD guest dengan nama, side/pihak, guest group, nomor kontak, jumlah undangan, jumlah pax, status undangan, RSVP status opsional, dan notes.

- Side default: Mempelai 1, Mempelai 2, Bersama; label dapat disesuaikan.

- Guest group default: Teman, Kolega, Keluarga, Tetangga, Teman Orang Tua, VIP; user dapat membuat grup lain.

- Menampilkan total tamu dan pax per side serta group.

- Mendukung import dan export setelah CRUD stabil.

**Aturan bisnis**

- Jumlah undangan dan pax tidak boleh negatif.

- Guest name wajib; kontak opsional pada fase awal.

**Acceptance criteria**

- Total grup sama dengan agregasi guest pada filter yang aktif.

- Data side satu wedding tidak memengaruhi label side wedding lain.

## FR-13 — Rundown Acara

**Tujuan:** Menyusun urutan acara lamaran, akad, resepsi, dan event custom.

**Kebutuhan fungsional**

- CRUD rundown item dengan event, urutan, start time, end time, aktivitas, PIC, keterangan, highlights, dan status.

- User dapat reorder item dengan drag-and-drop atau kontrol urutan.

- Template awal memuat rundown lamaran dan resepsi dari workbook.

- Tampilan print/export harus mudah dibaca oleh panitia dan vendor.

**Aturan bisnis**

- End time tidak boleh lebih awal dari start time ketika keduanya diisi.

- Reorder dilakukan dalam satu event, kecuali user memindahkan event secara eksplisit.

**Acceptance criteria**

- Urutan di layar konsisten dengan hasil export.

- Dua item yang waktunya overlap diberi warning tanpa selalu diblokir.

## FR-14 — Playlist Pernikahan

**Tujuan:** Menyimpan pilihan lagu dan konteks pemutarannya.

**Kebutuhan fungsional**

- CRUD lagu dengan judul, artis, link, event, momen, urutan, requested_by, dan note.

- User dapat reorder playlist dan membuka link eksternal.

- Template awal memuat daftar lagu dari workbook sebagai data yang dapat dihapus.

**Aturan bisnis**

- Judul lagu wajib; artis dan link opsional.

- Duplikasi lagu diperbolehkan tetapi sistem boleh menampilkan warning.

**Acceptance criteria**

- Playlist dapat difilter per event atau momen.

- Urutan tersimpan setelah user melakukan reorder.

## FR-15 — File & Attachment

**Tujuan:** Menyimpan bukti pembayaran, dokumen, kontrak, invoice, dan file pendukung secara privat.

**Kebutuhan fungsional**

- Upload file dari modul yang mendukung attachment.

- Metadata file disimpan di PostgreSQL; objek file disimpan di Supabase Storage.

- User dapat preview atau download menggunakan signed URL berumur pendek.

- User berizin dapat menghapus file; operasi menghapus metadata dan objek storage secara konsisten.

**Aturan bisnis**

- Bucket bersifat private.

- Service role/storage secret hanya tersedia pada server Laravel.

- Path mengikuti wedding UUID dan module agar isolasi mudah diaudit.

- Validasi extension, MIME type, ukuran, dan nama file dilakukan di backend.

**Acceptance criteria**

- User wedding A tidak dapat memperoleh signed URL file wedding B.

- Upload gagal tidak meninggalkan metadata palsu sebagai file aktif.

## FR-16 — Customization & Custom Fields

**Tujuan:** Memberikan fleksibilitas lintas wedding tanpa mengubah aplikasi menjadi generic form builder.

**Kebutuhan fungsional**

- User dapat menambah/mengubah event, kategori, status, guest group, side label, dan payment term.

- Custom field terbatas dibuat bertahap untuk module terpilih: guest lebih dulu, lalu task, checklist, dan budget item. Vendor tidak mendukung custom field pada V1.

- Tipe awal: text, textarea, number, currency, date, select, multi-select, checkbox, URL, dan file reference.

- Custom field memiliki label, key, module, required, options, sort order, serta active state.

**Aturan bisnis**

- Kolom inti finansial, tanggal, ownership, dan authorization tidak boleh digantikan custom field.

- Tidak ada arbitrary formula, script, conditional workflow, atau nested form builder pada V1.

- Menonaktifkan field tidak menghapus nilai historis secara permanen.

**Acceptance criteria**

- Wedding A dapat memiliki custom field guest yang tidak muncul di Wedding B.

- Custom field required divalidasi server-side pada entity terkait.

## FR-17 — Search, Filter, Sort & Export

**Tujuan:** Membuat data operasional tetap mudah dipakai ketika jumlah record bertambah.

**Kebutuhan fungsional**

- Modul list menyediakan search, filter, sort, dan pagination sesuai field relevan.

- Filter penting dapat direpresentasikan di query string agar halaman dapat dibagikan di dalam workspace.

- Export Excel diprioritaskan untuk budget, guest, rundown, timeline, dan checklist setelah alur utama stabil. PDF menyusul untuk output print-oriented.

- Export selalu mengikuti wedding aktif dan izin user.

**Aturan bisnis**

- Search menggunakan input yang disanitasi dan query terindeks untuk field utama.

- Export besar dapat dipindahkan ke queue pada fase optimasi.

**Acceptance criteria**

- Filter tidak hilang ketika berpindah halaman pagination.

- File export tidak berisi data wedding lain.

# 6. Strategi Customization

> **Pendekatan hybrid:** Yang bersifat domain utama disimpan sebagai kolom dan relasi normal; yang jumlahnya berulang disimpan sebagai child rows; yang benar-benar opsional disimpan sebagai custom field. Ini menjaga fleksibilitas tanpa merusak reporting.

| **Jenis kebutuhan**    | **Implementasi**              | **Contoh**                                  |
|------------------------|-------------------------------|---------------------------------------------|
| Core structured data   | Kolom database normal         | final_amount, due_date, wedding_id, status. |
| Configurable reference | Tabel scoped ke wedding       | event, kategori, status, guest group.       |
| Repeatable data        | Child table one-to-many       | payment terms, kontribusi, attachment.      |
| Optional attribute     | Custom field definition/value | Ukuran baju, nomor meja, kebutuhan aksesibilitas. |
| Presentation setting   | Wedding settings              | label pasangan, warna, urutan modul.        |

## 6.1 Batas Dynamic Form

- Custom field tidak boleh menentukan akses user atau menggantikan field authorization.

- Custom field tidak dipakai untuk nominal yang harus masuk agregasi utama sebelum dukungan reporting tersedia.

- Opsi select disimpan dalam JSONB pada definition, sedangkan nilai entity disimpan terpisah.

- Perubahan key atau tipe field harus memiliki strategi migrasi dan tidak diam-diam merusak nilai lama.

- UI harus membedakan field bawaan dan field buatan user.

# 7. Model Data Tingkat Tinggi

Bagian ini adalah logical model untuk PRD; detail foreign key, index, enum strategy, dan migration ditentukan pada ERD/technical design.

| **Domain**        | **Entity utama**                                                                 |
|-------------------|----------------------------------------------------------------------------------|
| Identity & access | users, weddings, wedding_members, wedding_invitations                            |
| Template & setup  | templates, template_sections, template_items                                     |
| Events & timeline | wedding_events, task_categories, task_statuses, tasks                            |
| Finance           | budget_scenarios, scenario_items, budget_categories, budget_items, payment_terms |
| Savings           | saving_goals, saving_contributors, saving_contributions                          |
| Checklists        | checklist_groups, checklist_categories, checklist_items                          |
| Vendors           | vendor_categories, vendors, vendor_contacts                                      |
| Guests            | guest_sides, guest_groups, guests                                                |
| Event day         | rundown_items, playlist_items                                                    |
| Files             | attachments                                                                      |
| Customization     | custom_field_definitions, custom_field_values                                    |
| Audit             | activity_logs                                                                    |

## 7.1 Data Ownership Rules

- Setiap entity operasional memiliki wedding_id langsung atau dapat ditelusuri secara tegas ke wedding parent.

- Route model binding tidak cukup sebagai authorization; policy tetap wajib dijalankan.

- Unique constraint yang bersifat per wedding harus memasukkan wedding_id.

- Penghapusan parent menggunakan constraint dan service/action terkontrol agar orphan tidak terbentuk.

- Aggregate dashboard tidak boleh menjalankan query global tanpa scope wedding.

## 7.2 Financial Model

**Planned amount:** Estimasi sebelum vendor atau keputusan final tersedia.

**Final amount:** Nilai komitmen/tagihan aktual yang menjadi dasar outstanding.

**Paid amount:** Akumulasi payment term valid yang sudah dibayar.

**Outstanding:** Final amount dikurangi paid amount.

**Saving balance:** Akumulasi kontribusi tabungan; bukan kas transaksi vendor.

# 8. Arsitektur Teknis

| **Layer**    | **Pilihan**                            | **Tanggung jawab**                                                           |
|--------------|----------------------------------------|------------------------------------------------------------------------------|
| Frontend     | React + TypeScript + Inertia.js        | Halaman, interaksi, form, chart, dan responsive UI.                          |
| Application  | Laravel stable release                 | Auth, authorization, validation, business logic, export, signed file access. |
| Database     | PostgreSQL                             | Source of truth relational dan JSONB terkontrol.                             |
| File storage | Supabase Storage private bucket        | Dokumen, bukti pembayaran, kontrak, dan attachment.                          |
| UI system    | Tailwind CSS + shadcn-style components | Konsistensi visual dan komponen aksesibel.                                   |
| Deployment   | Container/VPS atau managed platform    | Runtime web, worker opsional, dan observability.                             |

> **Boundary penting:** React tidak mengakses service key Supabase. Browser mengirim request ke Laravel; Laravel melakukan authorization lalu membuat signed URL atau menjalankan operasi storage. Laravel Auth tetap menjadi satu-satunya sistem identitas aplikasi.

## 8.1 Pola Backend

- Inertia controller/action mengirim page props dan menerima mutation form.

- Form Request menangani validation; Policy menangani authorization.

- Service/Action class menangani operasi kompleks seperti cloning template, pembayaran, dan penghapusan attachment.

- Database transaction digunakan untuk operasi multi-record yang harus atomic.

- Queue disiapkan kemudian untuk export besar, email invitation, dan pekerjaan background.

## 8.2 Struktur File

**Format path yang disarankan:**

- weddings/{wedding_uuid}/administration/{file_uuid}.{ext}

- weddings/{wedding_uuid}/vendors/{file_uuid}.{ext}

- weddings/{wedding_uuid}/payments/{file_uuid}.{ext}

- weddings/{wedding_uuid}/inspirations/{file_uuid}.{ext}

# 9. Non-Functional Requirements

## 9.1 Security

- Session authentication, CSRF protection, password hashing, rate limit auth, dan email verification.

- Server-side authorization pada setiap mutation dan file access.

- Secret database/storage hanya berada di environment server.

- Private bucket dan signed URL singkat untuk download/preview.

- Validation MIME, extension, dan file size; nama asli tidak digunakan sebagai storage key.

- Log aktivitas untuk perubahan finansial, membership, dan file sensitif.

## 9.2 Performance

- Dashboard menggunakan query aggregate yang terindeks dan menghindari N+1 query.

- Daftar besar menggunakan server-side pagination.

- Search utama memiliki index yang sesuai setelah pola query tervalidasi.

- Target awal halaman utama usable dalam \<= 3 detik pada koneksi normal dan data early access.

- Upload menampilkan progress, error yang dapat dipahami, dan retry yang aman.

## 9.3 Reliability & Data Integrity

- Operasi cloning template dan mutation finansial penting berjalan dalam transaction.

- Database backup terjadwal dan prosedur restore diuji sebelum penggunaan bisnis berbayar.

- Delete file menangani kegagalan storage tanpa menghilangkan referensi secara diam-diam.

- Money disimpan sebagai `BIGINT` whole-rupiah tanpa pembulatan floating-point.

- Date/time disimpan dan ditampilkan berdasarkan timezone wedding.

## 9.4 UX, Accessibility & Responsiveness

- Desktop menjadi pengalaman utama untuk tabel dan timeline; mobile tetap mendukung CRUD penting.

- Form memiliki label, error state, loading state, empty state, dan confirmation untuk destructive action.

- Warna status tidak menjadi satu-satunya penyampai informasi.

- Navigasi keyboard dan focus state tersedia pada komponen interaktif utama.

- Currency dan tanggal diformat konsisten untuk pengguna Indonesia.

# 10. Milestone Implementasi

| **Milestone**      | **Cakupan**                                                      | **Exit criteria**                            |
|--------------------|------------------------------------------------------------------|----------------------------------------------|
| M0 — Foundation    | Project setup, auth, PostgreSQL, layout, testing baseline.       | Register/login dan protected route berjalan. |
| M1 — Workspace     | Wedding, membership, invitation, onboarding, template clone.     | Data antar-wedding terisolasi.               |
| M2 — Planning      | Dashboard dasar, events, timeline, task, Gantt.                  | Task lifecycle dan countdown valid.          |
| M3 — Finance       | Budget scenario, budget items, unlimited payment terms, savings. | Agregat finansial tervalidasi.               |
| M4 — Logistics     | Seserahan, administrasi, vendor, guests.                         | Seluruh CRUD dan filter inti stabil.         |
| M5 — Event Day     | Rundown, playlist, template data.                                | Urutan dan export-ready view valid.          |
| M6 — Flexibility   | Custom categories/statuses/fields dan attachments.               | Custom data terisolasi per wedding.          |
| M7 — Stabilization | Export, responsive pass, security, E2E, deployment.              | Internal V1 release gate terpenuhi.          |

## 10.1 Test Strategy

- Feature test Laravel untuk auth, policy, membership, validation, template clone, dan finansial.

- Unit test untuk kalkulasi outstanding, savings, progress, dan date offset.

- Component test untuk form yang kompleks dan state penting.

- E2E minimal: register → create wedding → add task → add budget item → add ten payment terms → invite member → verify isolation.

- File test: unauthorized download ditolak; authorized signed URL berhasil dibuat.

# 11. Internal V1 Release Gate

- Registrasi dapat digunakan oleh banyak akun tanpa hardcoded allowlist.

- Setiap user dapat membuat wedding dan berpindah workspace.

- Seluruh modul workbook tersedia dan memiliki CRUD yang usable.

- Wedding berbeda dapat memakai kategori, status, payment term, dan custom field berbeda.

- Satu budget item mendukung minimal sepuluh payment term tanpa schema change.

- Dashboard menghitung data dari wedding aktif secara akurat.

- User tanpa membership tidak dapat membaca, mengubah, mengekspor, atau mengunduh file wedding lain.

- Attachment privat bekerja melalui Laravel dan signed URL.

- Critical flow lulus automated test dan manual UAT oleh pengguna awal.

- Deployment mempunyai backup database, logging error, dan konfigurasi environment yang terdokumentasi.

# 12. Pengembangan Menuju Bisnis SaaS

Monetisasi ditambahkan setelah pemakaian nyata membuktikan fitur yang paling bernilai. Karena user, wedding, membership, dan isolation sudah tersedia, fase bisnis dapat ditambahkan sebagai lapisan entitlement tanpa mengganti struktur seluruh modul.

| **Tahap berikutnya** | **Tambahan utama**                                                        |
|----------------------|---------------------------------------------------------------------------|
| Public beta          | Onboarding analytics, feedback, onboarding email, usage monitoring.       |
| Monetization         | plans, subscriptions/purchases, transactions, feature limits, webhook.    |
| B2C maturity         | Public invitation, RSVP, reminder, branded export.                        |
| B2B WO               | Organization/team, multi-client dashboard, project capacity, white-label. |

> **Pricing hypothesis:** Pasangan kemungkinan lebih cocok membeli akses per wedding karena masa penggunaan terbatas, sedangkan Wedding Organizer lebih cocok berlangganan berdasarkan jumlah project aktif. Hipotesis ini perlu diuji; bukan requirement internal V1.

# 13. Keputusan Tersisa dan Keputusan Final

## 13.1 Keputusan tersisa sebelum milestone terkait

- Nama produk dan identitas visual final.

- Anchor tanggal template timeline: tanggal akad atau event utama pilihan user. Keputusan ini wajib selesai sebelum M2.

## 13.2 Keputusan final untuk V1

- Email verification tidak menghalangi pembuatan wedding, tetapi wajib sebelum invitation dan upload file privat.

- Upload V1 menerima PDF, JPG/JPEG, PNG, dan WebP dengan batas 10 MB per file.

- Setiap wedding memiliki tepat satu owner. Ownership harus ditransfer sebelum owner dapat meninggalkan workspace.

- Export diprioritaskan Excel lebih dulu; PDF menyusul untuk kebutuhan print-oriented.

- Custom field dirilis untuk guest lebih dulu, kemudian task, checklist item, dan budget item. Vendor custom field berada di luar V1.

# 14. Definition of Done per Feature

- Requirement dan acceptance criteria sudah disepakati.

- Migration, model, policy, validation, controller/action, dan UI selesai.

- Happy path, validation failure, unauthorized access, empty state, dan destructive flow diuji.

- Query scoped ke wedding dan tidak menghasilkan N+1 yang jelas.

- Responsive desktop/mobile untuk operasi utama.

- Tidak ada secret yang dikirim ke browser.

- Dokumentasi perubahan schema dan environment tersedia.

- Lulus review manual pada data wedding dummy terpisah minimal dua akun.

# Lampiran A — Istilah

| **Istilah**       | **Definisi**                                                               |
|-------------------|----------------------------------------------------------------------------|
| Wedding workspace | Boundary data untuk satu project pernikahan.                               |
| Membership        | Relasi user dengan wedding dan role aksesnya.                              |
| Event             | Bagian acara seperti lamaran, akad, resepsi, atau event custom.            |
| Budget item       | Kebutuhan/tagihan yang mempunyai estimasi atau harga final.                |
| Payment term      | Satu jadwal atau realisasi pembayaran dari budget item.                    |
| Template          | Master data yang dicloning menjadi data independen milik wedding.          |
| Custom field      | Field tambahan buatan user pada module yang didukung.                      |
| Internal V1       | Rilis validasi tanpa billing, tetapi registrasi banyak akun tetap terbuka. |

*Dokumen ini menjadi sumber requirement produk. ERD, API/action contract, wireframe, dan implementation plan harus mengacu pada keputusan di dalam PRD ini.*
