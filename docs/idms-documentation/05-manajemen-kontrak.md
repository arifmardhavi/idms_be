# 05 - Manajemen Kontrak

Dokumentasi lengkap sistem manajemen kontrak pada IDMS Backend, mencakup dua sistem (Legacy & New), tipe kontrak, relasi data, progress tracking, termin & billing, amandemen, monitoring, dan API endpoints.

---

## Daftar Isi

1. [Dua Sistem Kontrak](#1-dua-sistem-kontrak)
2. [Tipe Kontrak](#2-tipe-kontrak)
3. [Metadata Kontrak (ContractNew)](#3-metadata-kontrak-contractnew)
4. [Relasi Kontrak](#4-relasi-kontrak)
5. [Progress Tracking](#5-progress-tracking)
6. [SPK (Surat Perintah Kerja)](#6-spk-surat-perintah-kerja)
7. [Termin & Billing](#7-termin--billing)
8. [Amandemen](#8-amandemen)
9. [Monitoring Contract](#9-monitoring-contract)
10. [Cascading Delete & File Cleanup](#10-cascading-delete--file-cleanup)
11. [API Endpoints](#11-api-endpoints)

---

## 1. Dua Sistem Kontrak

IDMS memiliki dua sistem kontrak yang berjalan berdampingan:

### Legacy System (`contracts`)

| Aspek | Detail |
|---|---|
| **Model** | `App\Models\Contract` |
| **Tabel** | `contracts` |
| **Fitur** | Kontrak dasar tanpa TKDN, progress dalam format yang berbeda |

**Karakteristik:**
- Field `contract_penalty` tersedia langsung di model
- Field `kom` (Koefisien) dihitung dari `contract_start_date` dan `contract_end_date`
- Progress tracking menggunakan model `Lumpsum_progress` dan `Spk` (tanpa suffix "New")
- Billing melalui relasi `Termin → TermBilling` (hasManyThrough)
- Cascading delete dilakukan manual di boot method, menghapus semua file terkait secara rekursif

### New System (`contract_news`)

| Aspek | Detail |
|---|---|
| **Model** | `App\Models\ContractNew` |
| **Tabel** | `contract_news` |
| **Fitur** | Kontrak dengan TKDN, decimal progress, receipt-based billing |

**Karakteristik:**
- Field `tkdn` (Tingkat Komponen Dalam Negeri) tersedia
- Field `current_status` untuk status konten dinamis
- Progress tracking menggunakan model `LumpsumProgressNew` dan `SpkNew` (dengan suffix "New")
- Billing melalui relasi `TerminNew → TerminReceiptNew` (receipt-based)
- Cascading delete hanya menghapus file `contract_file` dan `meeting_notes` di boot method (relasi anak dihapus oleh DB migration/cascade)

### Perbedaan Utama

| Fitur | Legacy (`Contract`) | New (`ContractNew`) |
|---|---|---|
| **TKDN** | Tidak ada | Field `tkdn` (nullable integer) |
| **Decimal Progress** | `plan_progress` / `actual_progress` (appended) | `plan` / `actual` (decimal di model child) |
| **Billing** | `Termin → TermBilling` | `TerminNew → TerminReceiptNew` |
| **File Storage** | `public/contract/` | `public/contract_new/` |
| **Atribut Monitor** | `monitoring_progress` (computed) | `deviation_progress` (computed) |
| **Sisa Nilai** | `sisa_nilai` (appended) | `sisa_nilai` (appended, optimasi relasi loaded) |
| **contract_type** | 1=Lumpsum, 2=Unit Price | 1=Lumpsum, 2=Unit Price, 3=PO Material, 4=PO Jasa |
| **Unique Validation** | `no_contract` unique | `no_contract` unique:contract_news |

---

## 2. Tipe Kontrak

Sistem mendukung 4 tipe kontrak yang ditandai dengan `contract_type`:

| ID | Tipe | Keterangan | Validasi Khusus |
|---|---|---|---|
| **1** | Lumpsum | Kontrak harga satuan total (borongan) | `contract_date` wajib, `meeting_notes` opsional |
| **2** | Unit Rate | Kontrak harga satuan per unit | `contract_date` wajib, `meeting_notes` opsional |
| **3** | PO Material | Purchase Order Material | `contract_date` di-set null, `contract_start_date` & `contract_end_date` wajib |
| **4** | PO Jasa | Purchase Order Jasa | `contract_start_date` & `contract_end_date` wajib |

### Perilaku Per Tipe

- **Tipe 1 & 2 (Lumpsum/Unit Rate):**
  - Menggunakan `LumpsumProgressNew` atau `SpkNew` untuk progress
  - Jika `contract_type == 2`, progress diambil dari SPK terbaru via `allSpkProgress()`
  - Jika `contract_type == 1`, progress diambil dari `lumpsum_progress()` terbaru

- **Tipe 3 & 4 (PO Material/PO Jasa):**
  - `kom` attribute selalu mengembalikan `0` (tidak ada durasi MPP)
  - Tidak memiliki `contract_date`
  - `contract_start_date` dan `contract_end_date` wajib diisi

---

## 3. Metadata Kontrak (ContractNew)

### Field Database

| Field | Type | Keterangan |
|---|---|---|
| `id` | bigint (PK) | Auto-increment ID |
| `no_vendor` | string | Nomor vendor |
| `vendor_name` | string | Nama vendor (disimpan uppercase) |
| `no_contract` | string | Nomor kontrak (unique) |
| `contract_name` | string | Nama kontrak (disimpan uppercase) |
| `contract_type` | integer | Tipe: 1=Lumpsum, 2=Unit Rate, 3=PO Material, 4=PO Jasa |
| `contract_date` | date | Tanggal kontrak (nullable untuk PO) |
| `contract_price` | bigint (integer cast) | Nilai kontrak dalam Rupiah |
| `contract_file` | string (nullable) | Filename PDF kontrak (maks 30MB/50MB) |
| `current_status` | string (nullable) | Status konten dinamis |
| `tkdn` | integer (nullable) | Tingkat Komponen Dalam Negeri (persentase) |
| `contract_start_date` | date (nullable) | Tanggal mulai kontrak |
| `contract_end_date` | date (nullable) | Tanggal berakhir kontrak |
| `meeting_notes` | string (nullable) | Filename PDF meeting notes (maks 5MB) |
| `pengawas` | integer (required) | ID Pengawas |
| `contract_status` | integer | 0 = Selesai, 1 = Aktif |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update terakhir |

### Appended Attributes (Computed)

| Attribute | Tipe | Keterangan |
|---|---|---|
| `durasi_mpp` | `{sisa: int, color: string}` | Sisa hari kontrak + warna indikator |
| `kom` | int | 0 atau 1, apakah kontrak memiliki durasi |
| `sisa_nilai` | `{sisa, nilai, denda, totalPenagihan, color}` | Sisa nilai kontrak + breakdown |
| `plan_progress` | float | Progress rencana terbaru (0-100) |
| `actual_progress` | float | Progress aktual terbaru (0-100) |
| `deviation_progress` | `{deviation: float, color: string}` | Selisih actual - plan + warna |
| `has_amandemen_price` | int | 1 jika ada amandemen harga, 0 jika tidak |
| `has_amandemen_date` | int | 1 jika ada amandemen tanggal, 0 jika tidak |

### Casts

```php
'contract_date' => 'date',
'contract_start_date' => 'date',
'contract_end_date' => 'date',
'contract_price' => 'integer',
```

---

## 4. Relasi Kontrak

### Diagram Relasi

```
ContractNew
├── belongsToMany(User)              via contract_new_user (pivot)
├── hasMany(TerminNew)
│   └── hasMany(TerminReceiptNew)
├── hasMany(LumpsumProgressNew)
├── hasMany(SpkNew)
│   └── hasMany(SpkProgressNew)
├── hasMany(AmandemenNew)
├── hasManyThrough(TerminReceiptNew) via TerminNew
└── hasManyThrough(SpkProgressNew)   via SpkNew
```

### Detail Relasi

| Relasi | Tipe | Model | Foreign Key | Local Key |
|---|---|---|---|---|
| `users` | belongsToMany | User | pivot: `contract_new_user` | - |
| `termin` | hasMany | TerminNew | `contract_new_id` | `id` |
| `lumpsum_progress` | hasMany | LumpsumProgressNew | `contract_new_id` | `id` |
| `spk` | hasMany | SpkNew | `contract_new_id` | `id` |
| `amandemen` | hasMany | AmandemenNew | `contract_new_id` | `id` |
| `terminReceipt` | hasManyThrough | TerminReceiptNew | via TerminNew | - |
| `allSpkProgress` | hasManyThrough | SpkProgressNew | via SpkNew | - |
| `lastPriceAmandemen` | hasOne | AmandemenNew | `contract_new_id` | latest where price > 0 |
| `lastDateAmandemen` | hasOne | AmandemenNew | `contract_new_id` | latest where end_date not null |

### Relasi Legacy (Contract)

| Relasi | Tipe | Model |
|---|---|---|
| `users` | belongsToMany | User |
| `termin` | hasMany | Termin |
| `lumpsum_progress` | hasMany | Lumpsum_progress |
| `spk` | hasMany | Spk |
| `amandemen` | hasMany | Amandemen |
| `allSpkProgress` | hasManyThrough | Spk_progress via Spk |
| `termBillings` | hasManyThrough | TermBilling via Termin |

---

## 5. Progress Tracking

### Concept

Progress tracking mengukur perkembangan pekerjaan kontrak dengan membandingkan **plan** (rencana) vs **actual** (aktual), lalu menghitung **deviation** (selisih).

### Sumber Data Progress

| Tipe Kontrak | Sumber Plan | Sumber Actual |
|---|---|---|
| Tipe 1 (Lumpsum) | `LumpsumProgressNew.plan` (latest) | `LumpsumProgressNew.actual` (latest) |
| Tipe 2 (Unit Rate) | `SpkProgressNew.plan` (latest via allSpkProgress) | `SpkProgressNew.actual` (latest via allSpkProgress) |

### Atribut di ContractNew

#### `plan_progress`
```
contract_type == 2 → SpkProgressNew terbaru.value('plan') ?? 0
contract_type == 1 → LumpsumProgressNew terbaru.value('plan') ?? 0
```

#### `actual_progress`
```
contract_type == 2 → SpkProgressNew terbaru.value('actual') ?? 0
contract_type == 1 → LumpsumProgressNew terbaru.value('actual') ?? 0
```

#### `deviation_progress`
```
deviation = actual - plan (rounded 2 decimal)
```

### Color Coding (Deviation)

| Kondisi | Warna | Keterangan |
|---|---|---|
| `contract_status == 0` | **blue** | Kontrak selesai |
| Ada amandemen belum upload file | **black** | Perlu upload BA/result amandemen |
| `deviation >= 0` | **green** | Actual ≥ Plan (sesuai/di atas rencana) |
| `-20 < deviation < 0` | **yellow** | Sedikit tertinggal (≤ 20%) |
| `deviation <= -20` | **red** | Tertinggal jauh (> 20%) |

### Color Coding (Durasi MPP)

| Kondisi | Warna |
|---|---|
| `contract_status == 0` | **blue** (selesai) |
| `sisa_hari <= 0` dan tidak ada amandemen | **red** (terlambat) |
| `sisa_hari <= 28` dan tidak ada penagihan | **yellow** (perlu tindakan) |
| Lainnya | **green** (aman) |

### Color Coding (Sisa Nilai)

| Kondisi | Warna |
|---|---|
| `contract_status == 0` | **blue** (selesai) |
| `sisa_nilai <= 0` | **red** (habis/minus) |
| `sisa_nilai <= 20% * contract_price` | **yellow** (menipis) |
| Lainnya | **green** (aman) |

---

## 6. SPK (Surat Perintah Kerja)

### Model: `SpkNew`

**Tabel:** `spk_news`

| Field | Type | Keterangan |
|---|---|---|
| `id` | bigint (PK) | Auto-increment |
| `contract_new_id` | bigint (FK) | Relasi ke ContractNew |
| `no_spk` | string | Nomor SPK |
| `spk_name` | string | Nama/judul SPK |
| `spk_start_date` | date | Tanggal mulai SPK |
| `spk_end_date` | date | Tanggal berakhir SPK |
| `spk_price` | numeric | Nilai SPK |
| `spk_file` | string (nullable) | File PDF SPK |
| `spk_status` | integer | Status SPK |
| `receipt_nominal` | numeric (nullable) | Nominal penagihan/receipt |
| `receipt_file` | string (nullable) | File bukti receipt |

### Appended Attributes

| Attribute | Tipe | Keterangan |
|---|---|---|
| `total_weeks` | int | Jumlah minggu SPK (dari Jumat pertama) |
| `penagihan_status` | int | 1 jika sudah ada receipt, 0 jika belum |

### Progress SPK: `SpkProgressNew`

**Tabel:** `spk_progress_news`

| Field | Type | Keterangan |
|---|---|---|
| `id` | bigint (PK) | Auto-increment |
| `spk_new_id` | bigint (FK) | Relasi ke SpkNew |
| `week` | int | Nomor minggu |
| `plan` | decimal | Persentase plan minggu ini |
| `actual` | decimal | Persentase aktual minggu ini |
| `progress_file` | string (nullable) | File bukti progress |

**Relasi:** `SpkProgressNew` belongsTo `SpkNew`

### Perhitungan Minggu SPK

Minggu dihitung mulai dari hari **Jumat** pertama setelah `spk_start_date`:
1. Geser ke Jumat terdekat jika belum Jumat
2. Hitung jumlah minggu hingga `spk_end_date` dengan interval 7 hari

---

## 7. Termin & Billing

### Legacy System

```
Contract → hasMany(Termin) → hasMany(TermBilling)
```

- **Termin**: Level penagihan (termin 1, 2, 3, dst.)
- **TermBilling**: Dokumen billing per termin (payment_document)

### New System (Receipt-Based)

```
ContractNew → hasMany(TerminNew) → hasMany(TerminReceiptNew)
```

#### TerminNew

| Field | Type | Keterangan |
|---|---|---|
| `contract_new_id` | bigint (FK) | Relasi ke ContractNew |
| `termin` | int/string | Nomor termin |
| `description` | string | Deskripsi termin |
| `receipt_nominal` | numeric | Nominal receipt |
| `receipt_file` | string (nullable) | File receipt |

#### TerminReceiptNew

| Field | Type | Keterangan |
|---|---|---|
| `termin_new_id` | bigint (FK) | Relasi ke TerminNew |
| `receipt_nominal` | numeric | Nominal receipt/billing |
| `receipt_file` | string (nullable) | File bukti receipt |

**Appended Attribute:** `termin` - mengembalikan nomor termin dari parent `TerminNew`

### Perhitungan Total Penagihan

```
contract_type == 2 (Unit Rate):
  totalPenagihan = SpkNew.sum('receipt_nominal')

contract_type != 2 (Lumpsum/PO):
  totalPenagihan = TerminReceiptNew.sum('receipt_nominal') via terminReceipt() hasManyThrough
```

---

## 8. Amandemen

### Model: `AmandemenNew`

| Field | Type | Keterangan |
|---|---|---|
| `contract_new_id` | bigint (FK) | Relasi ke ContractNew |
| `contract_price_before_amandemen` | numeric (nullable) | Nilai kontrak sebelum amandemen |
| `contract_end_date_before_amandemen` | date (nullable) | Tanggal berakhir sebelum amandemen |
| `ba_agreement_file` | string (nullable) | File BA kesepakatan |
| `result_amandemen_file` | string (nullable) | File hasil amandemen |
| `principle_permit_file` | string (nullable) | File izin prinsip |
| `amandemen_price` | numeric (nullable) | Nilai baru setelah amandemen |
| `amandemen_end_date` | date (nullable) | Tanggal berakhir baru |
| `amandemen_penalty` | numeric (nullable) | Denda dari amandemen |
| `amandemen_termin` | string (nullable) | Termin tambahan dari amandemen |

### Deteksi Amandemen di ContractNew

| Attribute | Logika |
|---|---|
| `has_amandemen_price` | `1` jika ada amandemen dengan `amandemen_price` tidak null |
| `has_amandemen_date` | `1` jika ada amandemen dengan `amandemen_end_date` tidak null |
| `hasAmandemenUnuploaded()` | `true` jika ada amandemen dengan `ba_agreement_file` atau `result_amandemen_file` kosong |

### Relasi Tambahan

- `lastPriceAmandemen()`:hasOne → AmandemenNew terbaru yang memiliki `contract_price_before_amandemen > 0`
- `lastDateAmandemen()`:hasOne → AmandemenNew terbaru yang memiliki `contract_end_date_before_amandemen` tidak null

---

## 9. Monitoring Contract

### Endpoint: `GET /monitoring_contract_new`

Mengembalikan dashboard monitoring dengan data berikut:

### Struktur Response

```json
{
  "data": {
    "total_contract": 100,
    "total_active_contract": 75,
    "total_selesai_contract": 25,
    "total_lumpsum_contract": 40,
    "total_unit_price_contract": 35,
    "total_po_material_contract": 25,
    "active_lumpsum_contract": 30,
    "active_unit_price_contract": 25,
    "active_po_material_contract": 20,
    "monitoring_durasi_mpp": {
      "blue": 25,
      "green": 50,
      "yellow": 15,
      "red": 10
    },
    "monitoring_progress_pekerjaan": {
      "blue": 25,
      "green": 40,
      "yellow": 15,
      "red": 10,
      "black": 10
    },
    "monitoring_durasi_mpp_lumpsum_unit": { "green": 0, "yellow": 0, "red": 0 },
    "monitoring_durasi_mpp_po_material": { "green": 0, "yellow": 0, "red": 0 },
    "monitoring_sisa_nilai_lumpsum_unit": { "green": 0, "yellow": 0, "red": 0 }
  }
}
```

### Kategori Monitoring

| Field | Keterangan |
|---|---|
| `monitoring_durasi_mpp` | Distribusi warna durasi MPP semua kontrak |
| `monitoring_progress_pekerjaan` | Distribusi warna deviation progress |
| `monitoring_durasi_mpp_lumpsum_unit` | Durasi MPP khusus kontrak tipe 1 & 2 |
| `monitoring_durasi_mpp_po_material` | Durasi MPP khusus kontrak tipe 3 |
| `monitoring_sisa_nilai_lumpsum_unit` | Sisa nilai khusus kontrak tipe 1 & 2 |

### Update Status & TKDN

- **`PUT /contract_new/current_status/{id}`** - Update field `current_status` (string nullable)
- **`PUT /contract_new/tkdn/{id}`** - Update field `tkdn` (integer nullable)

---

## 10. Cascading Delete & File Cleanup

### Legacy System (Contract)

Saat kontrak dihapus, boot method `deleting` melakukan pembersihan file secara **rekursif manual**:

```
Contract deleted
├── Hapus contract_file → public/contract/{file}
├── Hapus meeting_notes → public/contract/meeting_notes/{file}
├── Untuk setiap LumpsumProgress:
│   └── Hapus progress_file → public/contract/lumpsum/progress/{file}
├── Untuk setiap SPK:
│   ├── Hapus spk_file → public/contract/spk/{file}
│   ├── Hapus invoice_file → public/contract/spk/invoice/{file}
│   ├── Untuk setiap SpkProgress:
│   │   └── Hapus progress_file → public/contract/spk/progress/{file}
│   └── Hapus SPK record
├── Untuk setiap Termin:
│   ├── Untuk setiap TermBilling:
│   │   └── Hapus payment_document → public/contract/payment/{file}
│   └── Hapus Termin record
└── Untuk setiap Amandemen:
    ├── Hapus ba_agreement_file → public/contract/amandemen/ba_agreement/{file}
    ├── Hapus result_amandemen_file → public/contract/amandemen/result_amandemen/{file}
    ├── Hapus principle_permit_file → public/contract/amandemen/principle_permit/{file}
    └── Hapus Amandemen record
```

### New System (ContractNew)

Saat kontrak dihapus, boot method hanya membersihkan file **langsung** pada record:

```
ContractNew deleted
├── Hapus contract_file → public/contract_new/{file}
└── Hapus meeting_notes → public/contract_new/meeting_notes/{file}
```

> **Catatan:** File pada relasi anak (SPK, Termin, Amandemen, dll.) diasumsikan ditangani oleh database cascade constraint atau dihapus secara terpisah oleh controller masing-masing.

### Pembersihan Saat Update

Saat update dengan file baru:
1. Upload file baru dengan versi (`FileHelper::uploadWithVersion`)
2. Hapus file lama jika ada (`FileHelper::deleteFile`)

---

## 11. API Endpoints

### Contract New (CRUD)

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/contract_new` | `index` | List semua kontrak |
| `POST` | `/api/contract_new` | `store` | Buat kontrak baru |
| `GET` | `/api/contract_new/{id}` | `show` | Detail kontrak |
| `PUT` | `/api/contract_new/{id}` | `update` | Update kontrak |
| `DELETE` | `/api/contract_new/{id}` | `destroy` | Hapus kontrak |

### Contract New (Custom Routes)

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/contract_new_user` | `contractsByUser` | Kontrak milik user vendor yang login |
| `GET` | `/api/contract_new/download/{id}` | `downloadContractFile` | Download file kontrak PDF |
| `PUT` | `/api/contract_new/current_status/{id}` | `updateCurrentStatus` | Update status konten |
| `PUT` | `/api/contract_new/tkdn/{id}` | `updateTkdn` | Update TKDN |
| `GET` | `/api/contract_new/lumpsum_progress/{id}` | `contractLumpsumProgress` | Data durasi minggu lumpsum |
| `GET` | `/api/contract_new_po_material_type` | `showByPoMaterialType` | Filter kontrak PO Material (type=3) |
| `GET` | `/api/contract_new_un_po_material_type` | `showByUnPoMaterialType` | Filter kontrak selain PO Material |
| `GET` | `/api/monitoring_contract_new` | `monitoringContract` | Dashboard monitoring |

### Termin New

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/termin_new` | `index` | List semua termin |
| `POST` | `/api/termin_new` | `store` | Buat termin baru |
| `GET` | `/api/termin_new/{id}` | `show` | Detail termin |
| `PUT` | `/api/termin_new/{id}` | `update` | Update termin |
| `DELETE` | `/api/termin_new/{id}` | `destroy` | Hapus termin |
| `GET` | `/api/termin_new/contract/{id}` | `showByContract` | Termin berdasarkan kontrak |

### Termin Receipt

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/termin_receipt` | `index` | List semua receipt |
| `POST` | `/api/termin_receipt` | `store` | Buat receipt baru |
| `GET` | `/api/termin_receipt/{id}` | `show` | Detail receipt |
| `PUT` | `/api/termin_receipt/{id}` | `update` | Update receipt |
| `DELETE` | `/api/termin_receipt/{id}` | `destroy` | Hapus receipt |
| `GET` | `/api/termin_receipt/contract/{id}` | `showByContract` | Receipt berdasarkan kontrak |

### SPK New

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/spk_new` | `index` | List semua SPK |
| `POST` | `/api/spk_new` | `store` | Buat SPK baru |
| `GET` | `/api/spk_new/{id}` | `show` | Detail SPK |
| `PUT` | `/api/spk_new/{id}` | `update` | Update SPK |
| `DELETE` | `/api/spk_new/{id}` | `destroy` | Hapus SPK |
| `GET` | `/api/spk_new/contract/{id}` | `showByContract` | SPK berdasarkan kontrak |

### SPK Progress New

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/spk_progress_new` | `index` | List semua progress SPK |
| `POST` | `/api/spk_progress_new` | `store` | Buat progress baru |
| `GET` | `/api/spk_progress_new/{id}` | `show` | Detail progress |
| `PUT` | `/api/spk_progress_new/{id}` | `update` | Update progress |
| `DELETE` | `/api/spk_progress_new/{id}` | `destroy` | Hapus progress |
| `GET` | `/api/spk_progress_new/contract/{id}` | `showByContract` | Progress berdasarkan kontrak |

### Lumpsum Progress New

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/lumpsum_progress_new` | `index` | List semua lumpsum progress |
| `POST` | `/api/lumpsum_progress_new` | `store` | Buat progress baru |
| `GET` | `/api/lumpsum_progress_new/{id}` | `show` | Detail progress |
| `PUT` | `/api/lumpsum_progress_new/{id}` | `update` | Update progress |
| `DELETE` | `/api/lumpsum_progress_new/{id}` | `destroy` | Hapus progress |
| `GET` | `/api/lumpsum_progress_new/contract/{id}` | `showByContract` | Progress berdasarkan kontrak |

### Amandemen New

| Metode | Endpoint | Controller Method | Keterangan |
|---|---|---|---|
| `GET` | `/api/amandemen_new` | `index` | List semua amandemen |
| `POST` | `/api/amandemen_new` | `store` | Buat amandemen baru |
| `GET` | `/api/amandemen_new/{id}` | `show` | Detail amandemen |
| `PUT` | `/api/amandemen_new/{id}` | `update` | Update amandemen |
| `DELETE` | `/api/amandemen_new/{id}` | `destroy` | Hapus amandemen |
| `GET` | `/api/amandemen_new/contract/{id}` | `showByContract` | Amandemen berdasarkan kontrak |

### Contract Legacy (Referensi)

| Metode | Endpoint | Controller Method |
|---|---|---|
| `GET` | `/api/contract` | `index` |
| `POST` | `/api/contract` | `store` |
| `GET` | `/api/contract/{id}` | `show` |
| `PUT` | `/api/contract/{id}` | `update` |
| `DELETE` | `/api/contract/{id}` | `destroy` |
| `GET` | `/api/monitoring_contract` | `monitoring` |
| `PUT` | `/api/contract/current_status/{id}` | `updateCurrentStatus` |

---

## Lokasi File Terkait

| File | Path |
|---|---|
| Model ContractNew | `app/Models/ContractNew.php` |
| Model Contract (Legacy) | `app/Models/Contract.php` |
| Model TerminNew | `app/Models/TerminNew.php` |
| Model TerminReceiptNew | `app/Models/TerminReceiptNew.php` |
| Model SpkNew | `app/Models/SpkNew.php` |
| Model SpkProgressNew | `app/Models/SpkProgressNew.php` |
| Model LumpsumProgressNew | `app/Models/LumpsumProgressNew.php` |
| Model AmandemenNew | `app/Models/AmandemenNew.php` |
| Controller ContractNew | `app/Http/Controllers/ContractNewController.php` |
| Routes | `routes/api.php` (line 290, 350, 456-462) |
| File Storage (New) | `public/contract_new/` |
| File Storage (Legacy) | `public/contract/` |
