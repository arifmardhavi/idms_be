# 10 - Laporan Inspeksi

Dokumentasi lengkap sistem laporan inspeksi pada IDMS Backend, mencakup struktur data, 7 jenis inspeksi, relasi ke Historical Memorandum, file upload, dan API endpoints.

---

## Daftar Isi

1. [Struktur Inspeksi](#1-struktur-inspeksi)
2. [LaporanInspection (Parent)](#2-laporaninspection-parent)
3. [7 Jenis Inspeksi](#3-7-jenis-inspeksi)
4. [Relasi ke Historical Memorandum](#4-relasi-ke-historical-memorandum)
5. [File Upload & Penamaan](#5-file-upload--penamaan)
6. [API Endpoints](#6-api-endpoints)

---

## 1. Struktur Inspeksi

Sistem inspeksi menggunakan pola **parent-child** di mana `LaporanInspection` berfungsi sebagai record induk yang terikat pada satu `tag_number`, dan 7 jenis inspeksi merupakan child records:

```
LaporanInspection (parent, per tag_number)
├── InternalInspection
├── ExternalInspection
├── OnstreamInspection
├── Surveillance
├── BreakdownReport
├── Preventive
└── Overhaul
```

Setiap jenis inspeksi memiliki controller, model, dan route CRUD sendiri-sendiri, namun semuanya terhubung ke parent `LaporanInspection` melalui foreign key `laporan_inspection_id`.

---

## 2. LaporanInspection (Parent)

### Model: `App\Models\LaporanInspection`

**Tabel:** `laporan_inspections`

| Field | Type | Keterangan |
|---|---|---|
| `id` | bigint (PK) | Auto-increment ID |
| `tag_number_id` | bigint (FK) | Relasi ke `tag_numbers` (unique) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update terakhir |

### Appended Attributes (Computed)

| Attribute | Tipe | Keterangan |
|---|---|---|
| `unit` | string/null | Nama unit dari relasi `tagNumber.unit` |
| `type` | string/null | Tipe dari relasi `tagNumber.type` |
| `category` | string/null | Kategori dari relasi `tagNumber.type.category` |

### Relasi

| Relasi | Tipe | Model | Keterangan |
|---|---|---|---|
| `tagNumber` | belongsTo | Tag_number | Unit equipment terkait |
| `internalInspection` | hasMany | InternalInspection | Inspeksi internal |
| `externalInspection` | hasMany | ExternalInspection | Inspeksi eksternal |
| `onstream` | hasMany | OnstreamInspection | Inspeksi onstream |
| `surveillance` | hasMany | Surveillance | Pengawasan |
| `breakdownReport` | hasMany | BreakdownReport | Laporan breakdown |
| `preventive` | hasMany | Preventive | Pemeliharaan preventif |
| `overhaul` | hasMany | Overhaul | Pengecekan overhaul |

### Validasi Store

- `tag_number_id` : **required**, harus ada di tabel `tag_numbers`, **unique** di `laporan_inspections`

> **Penting:** Satu `tag_number` hanya boleh memiliki satu record `LaporanInspection`. Semua jenis inspeksi untuk equipment tersebut dikumpulkan di bawah parent yang sama.

---

## 3. 7 Jenis Inspeksi

### Tabel Perbandingan

| Jenis | Model | Tabel DB | Date Field | Upload Path |
|---|---|---|---|---|
| **Internal Inspection** | `InternalInspection` | `internal_inspections` | `inspection_date` | `public/laporan_inspection/internal_inspection/` |
| **External Inspection** | `ExternalInspection` | `external_inspections` | `inspection_date` | `public/laporan_inspection/external_inspection/` |
| **Onstream Inspection** | `OnstreamInspection` | `onstream_inspections` | `inspection_date` | `public/laporan_inspection/onstream_inspection/` |
| **Surveillance** | `Surveillance` | `surveillances` | `surveillance_date` | `public/laporan_inspection/surveillance/` |
| **Breakdown Report** | `BreakdownReport` | `breakdown_reports` | `breakdown_report_date` | `public/laporan_inspection/breakdown_report/` |
| **Preventive** | `Preventive` | `preventives` | `preventive_date` | `public/laporan_inspection/preventive/` |
| **Overhaul** | `Overhaul` | `overhauls` | `overhaul_date` | `public/laporan_inspection/overhaul/` |

### Field Database Identik

Ke-7 jenis inspeksi memiliki struktur field yang sama:

| Field | Type | Keterangan |
|---|---|---|
| `id` | bigint (PK) | Auto-increment ID |
| `laporan_inspection_id` | bigint (FK) | Relasi ke `laporan_inspections` |
| `judul` | string (max:255) | Judul/jenis laporan |
| `{date_field}` | date | Tanggal inspeksi (nama field berbeda per jenis) |
| `historical_memorandum_id` | bigint (FK, nullable) | Relasi ke `historical_memorandum` |
| `laporan_file` | string (nullable) | Filename file laporan |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update terakhir |

### Validasi Store (Identik untuk semua)

| Field | Validasi |
|---|---|
| `laporan_inspection_id` | required, exists:laporan_inspections,id |
| `judul` | required, string, max:255 |
| `{date_field}` | required, date |
| `historical_memorandum_id` | nullable, exists:historical_memorandum,id |
| `laporan_file` | nullable, file, mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar, max:204800 (200MB) |

> **Catatan:** `Overhaul` dan `Preventive` tidak menerima format `zip` dan `rar` pada validasi store.

### Relasi (Identik untuk semua)

| Relasi | Tipe | Model |
|---|---|---|
| `laporan_inspection` | belongsTo | LaporanInspection |
| `historical_memorandum` | belongsTo | HistoricalMemorandum |

---

## 4. Relasi ke Historical Memorandum

Setiap jenis inspeksi memiliki field **nullable** `historical_memorandum_id` yang berfungsi sebagai mekanisme **sumber dokumen alternatif**.

### Prinsip Mutual Exclusion

Laporan inspeksi mendukung **dua sumber dokumen** yang **saling eksklusif**:

| Sumber | Field | Keterangan |
|---|---|---|
| **File Upload** | `laporan_file` | File langsung yang di-upload |
| **Historical Memorandum** | `historical_memorandum_id` | Referensi ke dokumen memorandum yang sudah ada |

### Perilaku saat Update

Ketika user mengisi `historical_memorandum_id`:
1. File `laporan_file` yang lama **dihapus** dari disk
2. Field `laporan_file` di-set ke `null`
3. `historical_memorandum_id` disimpan

Ketika user meng-upload file baru (`laporan_file`):
1. File baru di-upload dengan versi
2. File lama (jika ada) dihapus dari disk
3. Field `historical_memorandum_id` di-set ke `null`

> **Catatan:** Saat create, user bisa mengisi keduanya (file dan memorandum). Mutual exclusion hanya diterapkan saat **update**.

---

## 5. File Upload & Penamaan

### Format Penamaan

```
{originalName}_{ddmmyyyy}_{version}.{extension}
```

**Contoh:** `laporan-inspeksi-internal_20072026_0.pdf`

### Penamaan Versi

Sistem menggunakan **auto-increment version** jika file dengan nama yang sama sudah ada:

1. Mulai dari version `0`
2. Cek apakah file sudah ada di folder tujuan
3. Jika ada, increment version sampai nama unik ditemukan

### Folder Penyimpanan per Jenis

| Jenis | Path |
|---|---|
| InternalInspection | `public/laporan_inspection/internal_inspection/` |
| ExternalInspection | `public/laporan_inspection/external_inspection/` |
| OnstreamInspection | `public/laporan_inspection/onstream_inspection/` |
| Surveillance | `public/laporan_inspection/surveillance/` |
| BreakdownReport | `public/laporan_inspection/breakdown_report/` |
| Preventive | `public/laporan_inspection/preventive/` |
| Overhaul | `public/laporan_inspection/overhaul/` |

### Ekstensi yang Diizinkan

`pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `jpg`, `jpeg`, `png`, `zip`, `rar`

> **Overhaul & Preventive:** Tidak menerima `zip` dan `rar`.

### Pembersihan Saat Hapus

Saat record inspeksi dihapus, file terkait (`laporan_file`) juga dihapus dari disk secara manual di controller.

---

## 6. API Endpoints

### LaporanInspection (CRUD)

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/laporan_inspection` | `index` | List semua laporan inspeksi |
| `POST` | `/api/laporan_inspection` | `store` | Buat laporan inspeksi baru |
| `GET` | `/api/laporan_inspection/{id}` | `show` | Detail laporan inspeksi (dengan tagNumber.unit, tagNumber.type.category) |
| `PUT` | `/api/laporan_inspection/{id}` | `update` | Update laporan inspeksi |
| `DELETE` | `/api/laporan_inspection/{id}` | `destroy` | Hapus laporan inspeksi |

### Internal Inspection

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/internal_inspection` | `index` | List semua internal inspection |
| `POST` | `/api/internal_inspection` | `store` | Buat internal inspection baru |
| `GET` | `/api/internal_inspection/{id}` | `show` | Detail internal inspection |
| `PUT` | `/api/internal_inspection/{id}` | `update` | Update internal inspection |
| `DELETE` | `/api/internal_inspection/{id}` | `destroy` | Hapus internal inspection |
| `GET` | `/api/internal_inspection/laporan_inspection/{id}` | `showByLaporanInspection` | Internal inspection berdasarkan parent |

### External Inspection

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/external_inspection` | `index` | List semua external inspection |
| `POST` | `/api/external_inspection` | `store` | Buat external inspection baru |
| `GET` | `/api/external_inspection/{id}` | `show` | Detail external inspection |
| `PUT` | `/api/external_inspection/{id}` | `update` | Update external inspection |
| `DELETE` | `/api/external_inspection/{id}` | `destroy` | Hapus external inspection |
| `GET` | `/api/external_inspection/laporan_inspection/{id}` | `showByLaporanInspection` | External inspection berdasarkan parent |

### Onstream Inspection

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/onstream_inspection` | `index` | List semua onstream inspection |
| `POST` | `/api/onstream_inspection` | `store` | Buat onstream inspection baru |
| `GET` | `/api/onstream_inspection/{id}` | `show` | Detail onstream inspection |
| `PUT` | `/api/onstream_inspection/{id}` | `update` | Update onstream inspection |
| `DELETE` | `/api/onstream_inspection/{id}` | `destroy` | Hapus onstream inspection |
| `GET` | `/api/onstream_inspection/laporan_inspection/{id}` | `showByLaporanInspection` | Onstream inspection berdasarkan parent |

### Surveillance

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/surveillance` | `index` | List semua surveillance |
| `POST` | `/api/surveillance` | `store` | Buat surveillance baru |
| `GET` | `/api/surveillance/{id}` | `show` | Detail surveillance |
| `PUT` | `/api/surveillance/{id}` | `update` | Update surveillance |
| `DELETE` | `/api/surveillance/{id}` | `destroy` | Hapus surveillance |
| `GET` | `/api/surveillance/laporan_inspection/{id}` | `showByLaporanInspection` | Surveillance berdasarkan parent |

### Breakdown Report

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/breakdown_report` | `index` | List semua breakdown report |
| `POST` | `/api/breakdown_report` | `store` | Buat breakdown report baru |
| `GET` | `/api/breakdown_report/{id}` | `show` | Detail breakdown report |
| `PUT` | `/api/breakdown_report/{id}` | `update` | Update breakdown report |
| `DELETE` | `/api/breakdown_report/{id}` | `destroy` | Hapus breakdown report |
| `GET` | `/api/breakdown_report/laporan_inspection/{id}` | `showByLaporanInspection` | Breakdown report berdasarkan parent |

### Preventive

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/preventive` | `index` | List semua preventive |
| `POST` | `/api/preventive` | `store` | Buat preventive baru |
| `GET` | `/api/preventive/{id}` | `show` | Detail preventive |
| `PUT` | `/api/preventive/{id}` | `update` | Update preventive |
| `DELETE` | `/api/preventive/{id}` | `destroy` | Hapus preventive |
| `GET` | `/api/preventive/laporan_inspection/{id}` | `showByLaporanInspection` | Preventive berdasarkan parent |

### Overhaul

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/overhaul` | `index` | List semua overhaul |
| `POST` | `/api/overhaul` | `store` | Buat overhaul baru |
| `GET` | `/api/overhaul/{id}` | `show` | Detail overhaul |
| `PUT` | `/api/overhaul/{id}` | `update` | Update overhaul |
| `DELETE` | `/api/overhaul/{id}` | `destroy` | Hapus overhaul |
| `GET` | `/api/overhaul/laporan_inspection/{id}` | `showByLaporanInspection` | Overhaul berdasarkan parent |

---

## Lokasi File Terkait

| File | Path |
|---|---|
| Model LaporanInspection | `app/Models/LaporanInspection.php` |
| Model InternalInspection | `app/Models/InternalInspection.php` |
| Model ExternalInspection | `app/Models/ExternalInspection.php` |
| Model OnstreamInspection | `app/Models/OnstreamInspection.php` |
| Model Surveillance | `app/Models/Surveillance.php` |
| Model BreakdownReport | `app/Models/BreakdownReport.php` |
| Model Preventive | `app/Models/Preventive.php` |
| Model Overhaul | `app/Models/Overhaul.php` |
| Controller LaporanInspection | `app/Http/Controllers/LaporanInspectionController.php` |
| Controller InternalInspection | `app/Http/Controllers/InternalInspectionController.php` |
| Controller ExternalInspection | `app/Http/Controllers/ExternalInspectionController.php` |
| Controller OnstreamInspection | `app/Http/Controllers/OnstreamInspectionController.php` |
| Controller Surveillance | `app/Http/Controllers/SurveillanceController.php` |
| Controller BreakdownReport | `app/Http/Controllers/BreakdownReportController.php` |
| Controller Preventive | `app/Http/Controllers/PreventiveController.php` |
| Controller Overhaul | `app/Http/Controllers/OverhaulController.php` |
| Routes | `routes/api.php` (line 220-227, 510-522) |
| File Storage | `public/laporan_inspection/` |
