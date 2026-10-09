# Kontrak API — M01: Events Modal

Perubahan backend modul M01 (modal forms). Lihat `docs/analysis/m01-modal-forms-events.md` (AC-M01-015/016) dan `docs/decision-log.md` (D-031, D-032).

## Endpoint yang tidak berubah

| Method | Path | Perilaku |
|---|---|---|
| `GET` | `/weddings/{wedding}/events` | Tidak berubah. |
| `GET` | `/weddings/{wedding}/events/{event}` | Tidak berubah. |
| `POST` | `/weddings/{wedding}/events` | Endpoint, validasi (`StoreWeddingEventRequest`), `ChangePrimaryWeddingEventAction`, Policy, dan redirect ke `weddings.events.index` tidak berubah. |
| `PUT/PATCH` | `/weddings/{wedding}/events/{event}` | Sama seperti di atas (`UpdateWeddingEventRequest`). |
| `DELETE` | `/weddings/{wedding}/events/{event}` | Endpoint, Policy, penolakan hapus acara utama, dan redirect tidak berubah. |

## Perubahan perilaku

### 1. GET create/edit menjadi redirect 302

| Method | Path Lama | Perilaku Baru |
|---|---|---|
| `GET` | `/weddings/{wedding}/events/create` | 302 redirect ke `weddings.events.index` (bukan 404) — URL lama tetap hidup. Otorisasi tetap dicegah (403 untuk non-member). |
| `GET` | `/weddings/{wedding}/events/{event}/edit` | 302 redirect ke `weddings.events.index`. Otorisasi `update` tetap dicegah (403 untuk viewer/non-member). |

Route dan verb tidak berubah; hanya isi aksi controller yang berubah.

### 2. Flash sukses: konvensi toast

`store`, `update`, dan `destroy` tidak lagi memakai `->with('success', ...)`. Ketiganya sekarang memakai konvensi aplikasi:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => '<pesan>']);
```

| Aksi | Pesan |
|---|---|
| `store` | `Acara berhasil ditambahkan.` |
| `update` | `Acara berhasil diperbarui.` |
| `destroy` | `Acara berhasil dihapus.` |

Frontend menampilkan toast via `useFlashToast` (sonner). Error validasi dan penolakan hapus acara utama tidak berubah (session errors, bukan toast).

## Kode error

| Kode | Kondisi |
|---|---|
| 302 | GET create/edit (redirect ke index); store/update/destroy sukses. |
| 403 | Non-member pada semua aksi; viewer pada store/update/destroy; viewer pada GET create/edit. |
| 404 | Event dari wedding lain (scoped binding). |
| 422 | Validasi Form Request gagal (session errors via Inertia). |
