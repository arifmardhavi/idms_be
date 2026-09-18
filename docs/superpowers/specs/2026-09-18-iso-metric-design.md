# Design: Tambah Modul ISO Metric (CRUD File Dokumen)

**Tanggal:** 2026-09-18
**Status:** Draft untuk review

## Latar Belakang

IDMS membutuhkan modul baru "ISO Metric" untuk menyimpan dokumen No Drawing,
Judul, Tanggal, dan file PDF. Modul ini mengikuti konvensi modul dokumen
standalone yang sudah ada (paling dekat: **GMS**), karena merupakan modul
standalone dengan upload file tunggal, filter + tanggal, download file, dan
bulk ZIP download.

## Kolom & Skema (Migration `create_iso_metrics_table`)

| Field | Tipe | Constraint |
|-------|------|------------|
| `id` | bigint unsigned | PK, auto-increment |
| `no_drawing` | string | NOT NULL, unique |
| `judul` | string | NOT NULL |
| `tanggal` | date | NOT NULL |
| `iso_metric_file` | text | NULLABLE |
| `created_at` / `updated_at` | timestamp | |

## Model — `app/Models/IsoMetric.php`

- `extends BaseModel`, `use HasFactory`
- `$fillable = ['no_drawing', 'judul', 'tanggal', 'iso_metric_file']`
- `boot()` hook `deleting` → hapus file fisik via unlink (pola GMS).

## Controller — `app/Http/Controllers/IsoMetricController.php`

Metode sekaligus pola (semua inline `Validator::make`, response shape
`{success, message, data}` mengikuti GMS):

- **index (`GET /api/iso_metric`)** — filter search (`no_drawing`/`judul`),
  `tanggal_start`/`tanggal_end` range, `sort_by`/`sort_order` (default
  `created_at`/`desc`), `per_page` opsional; paginate jika `per_page` diberikan.
- **store (`POST`)** — `no_drawing` `required|string|max:255|unique:iso_metrics,no_drawing`,
  `judul` `required|string|max:255`, `tanggal` `required|date`,
  `iso_metric_file` `required|file|mimes:pdf|max:102400` (100MB). Upload via
  `FileHelper::uploadWithVersion($file, 'iso_metric')` → 201.
- **show (`GET /{id}`)** — 404 jika tidak ada.
- **update (`PUT /{id}`)** — `sometimes|required`, unique ignore self; jika ada
  file baru: `FileHelper::deleteFile()` dulu lalu re-upload → 200.
- **destroy (`DELETE /{id}`)** — 404 cek, hapus record (file dihapus via boot hook).
- **downloadIsoMetricFile (`GET /iso_metric/download_file/{id}`)** — 404 cek,
  `activity()->log('download', 'IsoMetric', [...] )`, lalu
  `FileHelper::downloadFile('iso_metric', $isoMetric->iso_metric_file)`.
- **downloadIsoMetricFiles (`POST /iso_metric/download`)** — bulk ZIP dari
  array `ids` → `public/iso_metric_files.zip`, return JSON `{success, url}`.

## Routes — `routes/api.php` (di dalam `middleware(['auth:api'])`)

```php
Route::apiResource('iso_metric', IsoMetricController::class);
Route::get('/iso_metric/download_file/{id}', [IsoMetricController::class, 'downloadIsoMetricFile']);
Route::post('/iso_metric/download', [IsoMetricController::class, 'downloadIsoMetricFiles']);
```

## Registrasi Log Activity + Observer

- `config/log-activity.php`:
  - `features`: `'/iso-metric' => 'Iso Metric'`
  - `aliases`: `'IsoMetric' => 'Iso Metric'`
- `app/Observers/GlobalActivityObserver.php` labelFields: `'IsoMetric' => 'no_drawing'`
- `docs/log-activity-modules.md`: entri fitur utama + alias mapping.

> Catatan: Karena modul memakai BaseModel + observer global, create/update/
> delete otomatis tercatat. Download di-log manual di controller.

## Dokumentasi yang di-update

- `docs/idms-documentation/00-index.md` — tambah baris modul.
- `docs/idms-documentation/15-api-reference.md` — section ISO Metric + endpoint table.
- `docs/idms-documentation/16-database-schema.md` — tabel `iso_metrics`.
- `docs/idms-documentation/02-panduan-lokasi-file.md` — mapping controller/model/migration.
- `docs/api/iso_metric.postman_collection.json` — Postman collection lengkap.

## Verifikasi

- `php artisan migrate` → cek kolom via tinker.
- `php -l` semua file baru.
- Tinker test: CRUD (no_drawing unique), filter tanggal range, ZIP download count.
- `php artisan route:list` cek route terdaftar.
