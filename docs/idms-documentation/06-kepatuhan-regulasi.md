# 06 - Dokumentasi Kepatuhan Regulasi

Dokumentasi ini menjelaskan模模块 modul kepatuhan regulasi dalam sistem IDMS Backend yang mengelola berbagai jenis sertifikat, izin, dan dokumen pendukung terkait kepatuhan regulasi.

---

## Daftar Isi

1. [Jenis Sertifikat/Izin](#1-jenis-sertifikatizin)
2. [Due Days Tracking](#2-due-days-tracking)
3. [RLA (Remaining Life Assessment)](#3-rla-remaining-life-assessment)
4. [BAPK (Berita Acara Pemeriksaan Keandalan)](#4-bapk-berita-acara-pemeriksaan-keandalan)
5. [Report Tables](#5-report-tables)
6. [File Management](#6-file-management)
7. [API Endpoints](#7-api-endpoints)

---

## 1. Jenis Sertifikat/Izin

Sistem mengelola **8 jenis** sertifikat/izin yang terbagi menjadi dua kategori berdasarkan cakupan:

### Kategori berdasarkan Unit
| No | Jenis | Model | Scope |
|----|-------|-------|-------|
| 1 | PLO (Persetujuan Layak Operasi) | `Plo` | Per Unit |
| 2 | Izin Operasi | `IzinOperasi` | Per Unit |
| 3 | Izin Usaha | `IzinUsaha` | Legalitas Entitas |
| 4 | NIB (Nomor Induk Berusaha) | `Nib` | Legalitas Entitas |

### Kategori berdasarkan Tag Number
| No | Jenis | Model | Scope |
|----|-------|-------|-------|
| 5 | COI (Certificate of Inspection) | `Coi` | Per Tag Number |
| 6 | SKHP | `Skhp` | Per Tag Number |
| 7 | Sertifikat Kalibrasi | `SertifikatKalibrasi` | Per Tag Number |
| 8 | Izin Disnaker | `IzinDisnaker` | Per Tag Number |

---

### 1.1 PLO (Persetujuan Layak Operasi)

**Model:** `app/Models/Plo.php`
**Tabel:** `plos`
**Cakupan:** Per Unit

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `unit_id` | foreign key | Relasi ke tabel `units` |
| `no_certificate` | string | Nomor sertifikat PLO |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `plo_certificate` | string | Nama file sertifikat PLO (current) |
| `plo_old_certificate` | string | Nama file sertifikat PLO lama |
| `rla` | boolean (0/1) | Apakah memiliki RLA |
| `rla_issue` | date | Tanggal terbit RLA |
| `rla_overdue` | date | Tanggal kadaluarsa RLA |
| `rla_certificate` | string | Nama file sertifikat RLA |
| `rla_old_certificate` | string | Nama file sertifikat RLA lama |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `rla_due_days` - Selisih hari hingga RLA overdue
- `count_report_plo` - Jumlah laporan terkait
- `count_bapk_plo` - Jumlah BAPK terkait

**Relationships:**
- `unit()` → belongsTo `Unit`
- `reportPlo()` → hasMany `ReportPlo`
- `bapkPlo()` → hasMany `BapkPlo`

---

### 1.2 COI (Certificate of Inspection)

**Model:** `app/Models/Coi.php`
**Tabel:** `cois`
**Cakupan:** Per Tag Number

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `plo_id` | foreign key | Relasi ke tabel `plos` |
| `tag_number_id` | foreign key | Relasi ke tabel `tag_numbers` |
| `no_certificate` | string | Nomor sertifikat COI |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `coi_certificate` | string | Nama file sertifikat COI (current) |
| `coi_old_certificate` | string | Nama file sertifikat COI lama |
| `rla` | boolean (0/1) | Apakah memiliki RLA |
| `rla_issue` | date | Tanggal terbit RLA |
| `rla_overdue` | date | Tanggal kadaluarsa RLA |
| `rla_certificate` | string | Nama file sertifikat RLA |
| `rla_old_certificate` | string | Nama file sertifikat RLA lama |
| `re_engineer` | boolean (0/1) | Apakah memiliki re-engineering |
| `re_engineer_certificate` | string | Nama file re-engineering |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `rla_due_days` - Selisih hari hingga RLA overdue
- `count_report_coi` - Jumlah laporan terkait
- `count_bapk_coi` - Jumlah BAPK terkait

**Relationships:**
- `tag_number()` → belongsTo `Tag_number`
- `plo()` → belongsTo `Plo`
- `reportCoi()` → hasMany `ReportCoi`
- `bapkCoi()` → hasMany `BapkCoi`

---

### 1.3 SKHP (Sertifikat Kelayakan Hardware Peralatan)

**Model:** `app/Models/Skhp.php`
**Tabel:** `skhps`
**Cakupan:** Per Tag Number

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tag_number_id` | foreign key | Relasi ke tabel `tag_numbers` |
| `no_skhp` | string | Nomor SKHP |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `file_skhp` | string | Nama file SKHP (current) |
| `file_old_skhp` | string | Nama file SKHP lama |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `type_id` - ID type dari tag_number
- `category_id` - ID kategori dari tag_number
- `unit_id` - ID unit dari tag_number

**Relationships:**
- `tag_number()` → belongsTo `Tag_number`

---

### 1.4 Sertifikat Kalibrasi

**Model:** `app/Models/SertifikatKalibrasi.php`
**Tabel:** `sertifikat_kalibrasis`
**Cakupan:** Per Tag Number

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tag_number_id` | foreign key | Relasi ke tabel `tag_numbers` |
| `no_sertifikat_kalibrasi` | string | Nomor sertifikat kalibrasi |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `file_sertifikat_kalibrasi` | string | Nama file sertifikat kalibrasi (current) |
| `file_old_sertifikat_kalibrasi` | string | Nama file sertifikat kalibrasi lama |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `type_id` - ID type dari tag_number
- `category_id` - ID kategori dari tag_number
- `unit_id` - ID unit dari tag_number

**Relationships:**
- `tag_number()` → belongsTo `Tag_number`

---

### 1.5 Izin Usaha & NIB (Legalitas Entitas)

#### Izin Usaha
**Model:** `app/Models/IzinUsaha.php`
**Tabel:** `izin_usahas`

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `no_izin_usaha` | string | Nomor izin usaha |
| `judul` | string | Judul/jenis izin usaha |
| `tanggal_izin_usaha` | date | Tanggal terbit |
| `izin_usaha_file` | string | Nama file dokumen |

#### NIB (Nomor Induk Berusaha)
**Model:** `app/Models/Nib.php`
**Tabel:** `nibs`

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `no_nib` | string | Nomor NIB |
| `judul` | string | Judul/deskripsi NIB |
| `tanggal_nib` | date | Tanggal terbit |
| `nib_file` | string | Nama file dokumen |

> **Catatan:** Izin Usaha dan NIB bersifat dokumen legalitas entitas (perusahaan), bukan per unit atau tag number.

---

### 1.6 Izin Disnaker

**Model:** `app/Models/IzinDisnaker.php`
**Tabel:** `izin_disnakers`
**Cakupan:** Per Tag Number

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `plo_id` | foreign key | Relasi ke tabel `plos` |
| `tag_number_id` | foreign key | Relasi ke tabel `tag_numbers` |
| `no_certificate` | string | Nomor sertifikat Izin Disnaker |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `izin_disnaker_certificate` | string | Nama file sertifikat Izin Disnaker (current) |
| `izin_disnaker_old_certificate` | string | Nama file sertifikat Izin Disnaker lama |
| `rla` | boolean (0/1) | Apakah memiliki RLA |
| `rla_issue` | date | Tanggal terbit RLA |
| `rla_overdue` | date | Tanggal kadaluarsa RLA |
| `rla_certificate` | string | Nama file sertifikat RLA |
| `rla_old_certificate` | string | Nama file sertifikat RLA lama |
| `re_engineer` | boolean (0/1) | Apakah memiliki re-engineering |
| `re_engineer_certificate` | string | Nama file re-engineering |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `rla_due_days` - Selisih hari hingga RLA overdue

**Relationships:**
- `tag_number()` → belongsTo `Tag_number`
- `plo()` → belongsTo `Plo`

---

### 1.7 Izin Operasi

**Model:** `app/Models/IzinOperasi.php`
**Tabel:** `izin_operasis`
**Cakupan:** Per Unit

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `unit_id` | foreign key | Relasi ke tabel `units` |
| `no_certificate` | string | Nomor sertifikat Izin Operasi |
| `issue_date` | date | Tanggal terbit |
| `overdue_date` | date | Tanggal kadaluarsa |
| `izin_operasi_certificate` | string | Nama file sertifikat Izin Operasi (current) |
| `izin_operasi_old_certificate` | string | Nama file sertifikat Izin Operasi lama |
| `rla` | boolean (0/1) | Apakah memiliki RLA |
| `rla_issue` | date | Tanggal terbit RLA |
| `rla_overdue` | date | Tanggal kadaluarsa RLA |
| `rla_certificate` | string | Nama file sertifikat RLA |
| `rla_old_certificate` | string | Nama file sertifikat RLA lama |

**Computed Attributes (appends):**
- `due_days` - Selisih hari hingga overdue
- `rla_due_days` - Selisih hari hingga RLA overdue

**Relationships:**
- `unit()` → belongsTo `Unit`
- `reportIzinOperasi()` → hasMany `ReportIzinOperasi`

---

## 2. Due Days Tracking

Sistem ini menggunakan perhitungan **due_days** untuk melacak status keterbatasan waktu berlakunya sertifikat/izin.

### Perhitungan

Due days dihitung menggunakan formula:

```
due_days = (overdue_date - tanggal_sekarang) dalam satuan hari
```

Implementasi dalam kode (dari `PloController.php` / model):

```php
public function getDueDaysAttribute()
{
    return $this->calculateDaysDifference($this->overdue_date);
}

private function calculateDaysDifference($date)
{
    if (!$date) {
        return null;
    }

    $targetTimestamp = strtotime($date);
    $todayTimestamp = strtotime(now()->toDateString());

    return ($targetTimestamp - $todayTimestamp) / 86400;
}
```

### Status Warna (Kode Frontend)

| Kondisi | Warna | Keterangan |
|---------|-------|------------|
| `due_days > 90` | **Hijau** | Aman, masih lebih dari 90 hari |
| `due_days 30-90` | **Kuning** | Waspada, perlu perhatian |
| `due_days < 30` | **Merah** | Kritis, segera perlu perpanjangan |

### Filter Due Days (dari COI Controller)

Untuk COI dan Izin Disnaker, terdapat filter berbasis **9 bulan (270 hari)**:

| Filter | Kondisi | Keterangan |
|--------|---------|------------|
| `*_more_than_nine_months` | `DATEDIFF(overdue_date, CURDATE()) > 270` | Lebih dari 9 bulan |
| `*_less_than_nine_months` | `BETWEEN 0 AND 270` | Kurang dari 9 bulan |
| `*_expired` | `DATEDIFF(overdue_date, CURDATE()) < 0` | Sudah kadaluarsa |

---

## 3. RLA (Remaining Life Assessment)

RLA merupakan aspek tambahan dari beberapa jenis sertifikat yang melacak sisa umur peralatan.

### Jenis Sertifikat dengan RLA

| Model | Field RLA |
|-------|-----------|
| PLO | `rla`, `rla_issue`, `rla_overdue`, `rla_certificate`, `rla_old_certificate` |
| COI | `rla`, `rla_issue`, `rla_overdue`, `rla_certificate`, `rla_old_certificate` |
| Izin Disnaker | `rla`, `rla_issue`, `rla_overdue`, `rla_certificate`, `rla_old_certificate` |
| Izin Operasi | `rla`, `rla_issue`, `rla_overdue`, `rla_certificate`, `rla_old_certificate` |

### Aturan RLA

- Field `rla` bersifat boolean (0/1)
- Jika `rla = 1`, maka field `rla_issue`, `rla_overdue`, dan `rla_certificate` bersifat **required**
- `rla_overdue` harus `>= rla_issue` (after_or_equal)
- RLA memiliki computed attribute `rla_due_days` yang dihitung dengan cara yang sama seperti `due_days`

### Validasi Store

```php
'rla' => 'required|in:0,1',
'rla_issue' => 'nullable|date|required_if:rla,1',
'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue',
'rla_certificate' => 'nullable|file|mimes:pdf|max:25600|required_if:rla,1',
```

---

## 4. BAPK (Berita Acara Pemeriksaan Keandalan)

BAPK adalah dokumen pendukung terkait hasil pemeriksaan keandalan peralatan.

### BAPK PLO

**Model:** `app/Models/BapkPlo.php`
**Tabel:** `bapk_plos`

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `plo_id` | foreign key | Relasi ke tabel `plos` |
| `bapk_plo` | string | Nama file BAPK PLO |

**Computed Attributes:**
- `unit_name` - Nama unit dari relasi PLO

**Relationships:**
- `plo()` → belongsTo `Plo`

### BAPK COI

**Model:** `app/Models/BapkCoi.php`
**Tabel:** `bapk_cois`

**Fields:**
| Field | Tipe | Keterangan |
|-------|------|------------|
| `coi_id` | foreign key | Relasi ke tabel `cois` |
| `bapk_coi` | string | Nama file BAPK COI |

**Computed Attributes:**
- `unit_name` - Nama unit dari relasi COI → PLO → Unit

**Relationships:**
- `coi()` → belongsTo `Coi`

---

## 5. Report Tables

Report tables menyimpan file laporan terkait masing-masing jenis sertifikat.

### Report PLO
**Model:** `app/Models/ReportPlo.php`
**Tabel:** `report_plos`

| Field | Keterangan |
|-------|------------|
| `plo_id` | Relasi ke Plo |
| `report_plo` | Nama file laporan |

### Report COI
**Model:** `app/Models/ReportCoi.php`
**Tabel:** `report_cois`

| Field | Keterangan |
|-------|------------|
| `coi_id` | Relasi ke Coi |
| `report_coi` | Nama file laporan |

### Report Izin Disnaker
**Model:** `app/Models/ReportIzinDisnaker.php`
**Tabel:** `report_izin_disnakers`

| Field | Keterangan |
|-------|------------|
| `izin_disnaker_id` | Relasi ke IzinDisnaker |
| `report_izin_disnaker` | Nama file laporan |

### Report Izin Operasi
**Model:** `app/Models/ReportIzinOperasi.php`
**Tabel:** `report_izin_operasis`

| Field | Keterangan |
|-------|------------|
| `izin_operasi_id` | Relasi ke IzinOperasi |
| `report_izin_operasi` | Nama file laporan |

---

## 6. File Management

### Pola Penamaan File

Sistem menggunakan pola penamaan versi untuk file sertifikat:

```
{original_name}_{ddmmyyyy}_{version}.{ext}
```

Contoh: `sertifikat_PLO_20012026_0.pdf`

### Alur Upload

1. File diupload dengan nama original
2. Sistem mengambil nama file tanpa ekstensi
3. Menambahkan tanggal upload (`ddmmyyyy`) dan versi (`0`)
4. Jika file sudah ada, versi dinaikkan (`1`, `2`, dst.)

### Penyimpanan File

| Jenis | Path Current | Path Old |
|-------|-------------|----------|
| PLO Certificate | `public/plo/certificates/` | `public/plo/certificates/` |
| PLO RLA | `public/plo/rla/` | `public/plo/rla/` |
| COI Certificate | `public/coi/certificates/` | `public/coi/certificates/` |
| SKHP | `public/skhp/` | `public/skhp/` |
| Sertifikat Kalibrasi | `public/sertifikat_kalibrasi/` | `public/sertifikat_kalibrasi/` |
| Izin Disnaker Certificate | `public/izin_disnaker/certificates/` | `public/izin_disnaker/certificates/` |
| Izin Operasi Certificate | `public/izin_operasi/certificates/` | `public/izin_operasi/certificates/` |
| Izin Usaha | `public/izin_usaha/` | - |
| NIB | `public/nib/` | - |

### File Old Certificate

Ketika sertifikat diperbarui, file lama disimpan sebagai `*_old_certificate` untuk keperluan audit trail.

### Hapus File (on Delete)

Saat record dihapus, semua file terkait otomatis dihapus melalui boot method `static::deleting()`:

```php
static::deleting(function ($model) {
    // Hapus file sertifikat current
    // Hapus file sertifikat lama
    // Hapus file RLA (jika ada)
    // Hapus file RLA lama (jika ada)
    // Hapus semua report terkait (termasuk filenya)
});
```

---

## 7. API Endpoints

Semua endpoint berada di bawah prefix `/api/v1/` dan memerlukan autentikasi (`auth:api`).

### 7.1 PLO

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/plo` | `PloController@index` | List semua PLO |
| `POST` | `/plo` | `PloController@store` | Tambah PLO baru |
| `GET` | `/plo/{id}` | `PloController@show` | Detail PLO |
| `PUT/PATCH` | `/plo/{id}` | `PloController@update` | Update PLO |
| `DELETE` | `/plo/{id}` | `PloController@destroy` | Hapus PLO |
| `GET` | `/plo/download_file/{id}` | `PloController@downloadPloFile` | Download file PLO |
| `PUT` | `/plo/deletefile/{id}` | `PloController@deleteFilePlo` | Hapus file PLO |
| `POST` | `/plo/download` | `PloController@downloadPloCertificates` | Download sertifikat PLO |
| `GET` | `/plo_countduedays` | `PloController@countPloDueDays` | Hitung due days PLO |

### 7.2 COI

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/coi` | `CoiController@index` | List semua COI (support filter & search) |
| `POST` | `/coi` | `CoiController@store` | Tambah COI baru |
| `GET` | `/coi/{id}` | `CoiController@show` | Detail COI |
| `PUT/PATCH` | `/coi/{id}` | `CoiController@update` | Update COI |
| `DELETE` | `/coi/{id}` | `CoiController@destroy` | Hapus COI |
| `GET` | `/coi/download_file/{id}` | `CoiController@downloadCoiFile` | Download file COI |
| `PUT` | `/coi/deletefile/{id}` | `CoiController@deleteFileCoi` | Hapus file COI |
| `POST` | `/coi/download` | `CoiController@downloadCoiCertificates` | Download sertifikat COI |
| `GET` | `/coi_countduedays` | `CoiController@countCoiDueDays` | Hitung due days COI |
| `GET` | `/coi_filter` | `CoiController@filteringCoi` | Filter COI |
| `GET` | `/coi/tag_number/{id}` | `CoiController@showByTagNumber` | COI by Tag Number |

### 7.3 SKHP

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/skhp` | `SkhpController@index` | List semua SKHP |
| `POST` | `/skhp` | `SkhpController@store` | Tambah SKHP baru |
| `GET` | `/skhp/{id}` | `SkhpController@show` | Detail SKHP |
| `PUT/PATCH` | `/skhp/{id}` | `SkhpController@update` | Update SKHP |
| `DELETE` | `/skhp/{id}` | `SkhpController@destroy` | Hapus SKHP |
| `GET` | `/skhp/download_file/{id}` | `SkhpController@downloadSkhpFile` | Download file SKHP |
| `PUT` | `/skhp/deletefile/{id}` | `SkhpController@deleteFileskhp` | Hapus file SKHP |
| `POST` | `/skhp/download` | `SkhpController@downloadskhpCertificates` | Download sertifikat SKHP |
| `GET` | `/skhp_countduedays` | `SkhpController@countskhpDueDays` | Hitung due days SKHP |

### 7.4 Sertifikat Kalibrasi

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/sertifikat_kalibrasi` | `SertifikatKalibrasiController@index` | List semua |
| `POST` | `/sertifikat_kalibrasi` | `SertifikatKalibrasiController@store` | Tambah baru |
| `GET` | `/sertifikat_kalibrasi/{id}` | `SertifikatKalibrasiController@show` | Detail |
| `PUT/PATCH` | `/sertifikat_kalibrasi/{id}` | `SertifikatKalibrasiController@update` | Update |
| `DELETE` | `/sertifikat_kalibrasi/{id}` | `SertifikatKalibrasiController@destroy` | Hapus |
| `GET` | `/sertifikat_kalibrasi/download_file/{id}` | `SertifikatKalibrasiController@downloadSertifikatKalibrasiFile` | Download file |
| `PUT` | `/sertifikat_kalibrasi/deletefile/{id}` | `SertifikatKalibrasiController@deleteFileSertifikatKalibrasi` | Hapus file |
| `POST` | `/sertifikat_kalibrasi/download` | `SertifikatKalibrasiController@downloadSertifikatKalibrasiCertificates` | Download sertifikat |
| `GET` | `/sertifikat_kalibrasi_countduedays` | `SertifikatKalibrasiController@countSertifikatKalibrasiDueDays` | Hitung due days |

### 7.5 Izin Usaha

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/izin_usaha` | `IzinUsahaController@index` | List semua |
| `POST` | `/izin_usaha` | `IzinUsahaController@store` | Tambah baru |
| `GET` | `/izin_usaha/{id}` | `IzinUsahaController@show` | Detail |
| `PUT/PATCH` | `/izin_usaha/{id}` | `IzinUsahaController@update` | Update |
| `DELETE` | `/izin_usaha/{id}` | `IzinUsahaController@destroy` | Hapus |
| `GET` | `/izin_usaha/download_file/{id}` | `IzinUsahaController@downloadIzinUsahaFile` | Download file |

### 7.6 NIB

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/nib` | `NibController@index` | List semua |
| `POST` | `/nib` | `NibController@store` | Tambah baru |
| `GET` | `/nib/{id}` | `NibController@show` | Detail |
| `PUT/PATCH` | `/nib/{id}` | `NibController@update` | Update |
| `DELETE` | `/nib/{id}` | `NibController@destroy` | Hapus |
| `GET` | `/nib/download_file/{id}` | `NibController@downloadNibFile` | Download file |

### 7.7 Izin Disnaker

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/izin_disnaker` | `IzinDisnakerController@index` | List semua |
| `POST` | `/izin_disnaker` | `IzinDisnakerController@store` | Tambah baru |
| `GET` | `/izin_disnaker/{id}` | `IzinDisnakerController@show` | Detail |
| `PUT/PATCH` | `/izin_disnaker/{id}` | `IzinDisnakerController@update` | Update |
| `DELETE` | `/izin_disnaker/{id}` | `IzinDisnakerController@destroy` | Hapus |
| `GET` | `/izin_disnaker/download_file/{id}` | `IzinDisnakerController@downloadIzinDisnakerFile` | Download file |
| `PUT` | `/izin_disnaker/deletefile/{id}` | `IzinDisnakerController@deleteFileIzinDisnaker` | Hapus file |
| `POST` | `/izin_disnaker/download` | `IzinDisnakerController@downloadIzinDisnakerCertificates` | Download sertifikat |
| `GET` | `/izin_disnaker_countduedays` | `IzinDisnakerController@countIzinDisnakerDueDays` | Hitung due days |
| `GET` | `/izin_disnaker/tag_number/{id}` | `IzinDisnakerController@showByTagNumber` | By Tag Number |

### 7.8 Izin Operasi

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/izin_operasi` | `IzinOperasiController@index` | List semua |
| `POST` | `/izin_operasi` | `IzinOperasiController@store` | Tambah baru |
| `GET` | `/izin_operasi/{id}` | `IzinOperasiController@show` | Detail |
| `PUT/PATCH` | `/izin_operasi/{id}` | `IzinOperasiController@update` | Update |
| `DELETE` | `/izin_operasi/{id}` | `IzinOperasiController@destroy` | Hapus |
| `GET` | `/izin_operasi/download_file/{id}` | `IzinOperasiController@downloadIzinOperasiFile` | Download file |
| `PUT` | `/izin_operasi/deletefile/{id}` | `IzinOperasiController@deleteFileIzinOperasi` | Hapus file |
| `POST` | `/izin_operasi/download` | `IzinOperasiController@downloadIzinOperasiCertificates` | Download sertifikat |
| `GET` | `/izin_operasi_countduedays` | `IzinOperasiController@countIzinOperasiDueDays` | Hitung due days |

### 7.9 Report Endpoints

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/report_plo` | `ReportPloController@index` | List Report PLO |
| `POST` | `/report_plo` | `ReportPloController@store` | Tambah Report PLO |
| `GET` | `/report_plo/{id}` | `ReportPloController@show` | Detail Report PLO |
| `PUT/PATCH` | `/report_plo/{id}` | `ReportPloController@update` | Update Report PLO |
| `DELETE` | `/report_plo/{id}` | `ReportPloController@destroy` | Hapus Report PLO |
| `GET` | `/report_plo/download_file/{id}` | `ReportPloController@downloadReportPloFile` | Download file Report PLO |
| `GET` | `/report_plos/{id}` | `ReportPloController@showWithPloId` | Report by PLO ID |
| `GET` | `/report_coi` | `ReportCoiController@index` | List Report COI |
| `POST` | `/report_coi` | `ReportCoiController@store` | Tambah Report COI |
| `GET` | `/report_coi/{id}` | `ReportCoiController@show` | Detail Report COI |
| `PUT/PATCH` | `/report_coi/{id}` | `ReportCoiController@update` | Update Report COI |
| `DELETE` | `/report_coi/{id}` | `ReportCoiController@destroy` | Hapus Report COI |
| `GET` | `/report_coi/download_file/{id}` | `ReportCoiController@downloadReportCoiFile` | Download file Report COI |
| `GET` | `/report_cois/{id}` | `ReportCoiController@showWithCoiId` | Report by COI ID |
| `GET` | `/report_izin_disnaker` | `ReportIzinDisnakerController@index` | List Report Izin Disnaker |
| `POST` | `/report_izin_disnaker` | `ReportIzinDisnakerController@store` | Tambah Report Izin Disnaker |
| `GET` | `/report_izin_disnaker/{id}` | `ReportIzinDisnakerController@show` | Detail Report Izin Disnaker |
| `PUT/PATCH` | `/report_izin_disnaker/{id}` | `ReportIzinDisnakerController@update` | Update Report Izin Disnaker |
| `DELETE` | `/report_izin_disnaker/{id}` | `ReportIzinDisnakerController@destroy` | Hapus Report Izin Disnaker |
| `GET` | `/report_izin_disnaker/download_file/{id}` | `ReportIzinDisnakerController@downloadReportIzinDisnakerFile` | Download file |
| `GET` | `/report_izin_disnakers/{id}` | `ReportIzinDisnakerController@showWithIzinDisnakerId` | Report by Izin Disnaker ID |
| `GET` | `/report_izin_operasi` | `ReportIzinOperasiController@index` | List Report Izin Operasi |
| `POST` | `/report_izin_operasi` | `ReportIzinOperasiController@store` | Tambah Report Izin Operasi |
| `GET` | `/report_izin_operasi/{id}` | `ReportIzinOperasiController@show` | Detail Report Izin Operasi |
| `PUT/PATCH` | `/report_izin_operasi/{id}` | `ReportIzinOperasiController@update` | Update Report Izin Operasi |
| `DELETE` | `/report_izin_operasi/{id}` | `ReportIzinOperasiController@destroy` | Hapus Report Izin Operasi |
| `GET` | `/report_izin_operasi/download_file/{id}` | `ReportIzinOperasiController@downloadReportIzinOperasiFile` | Download file |
| `GET` | `/report_izin_operasis/{id}` | `ReportIzinOperasiController@showWithIzinOperasiId` | Report by Izin Operasi ID |

### 7.10 BAPK Endpoints

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| `GET` | `/bapk_plo` | `BapkPloController@index` | List BAPK PLO |
| `POST` | `/bapk_plo` | `BapkPloController@store` | Tambah BAPK PLO |
| `GET` | `/bapk_plo/{id}` | `BapkPloController@show` | Detail BAPK PLO |
| `PUT/PATCH` | `/bapk_plo/{id}` | `BapkPloController@update` | Update BAPK PLO |
| `DELETE` | `/bapk_plo/{id}` | `BapkPloController@destroy` | Hapus BAPK PLO |
| `GET` | `/bapk_plo/download_file/{id}` | `BapkPloController@downloadBapkPloFile` | Download file BAPK PLO |
| `GET` | `/bapk_plos/{id}` | `BapkPloController@showByPlo` | BAPK by PLO ID |
| `GET` | `/bapk_coi` | `BapkCoiController@index` | List BAPK COI |
| `POST` | `/bapk_coi` | `BapkCoiController@store` | Tambah BAPK COI |
| `GET` | `/bapk_coi/{id}` | `BapkCoiController@show` | Detail BAPK COI |
| `PUT/PATCH` | `/bapk_coi/{id}` | `BapkCoiController@update` | Update BAPK COI |
| `DELETE` | `/bapk_coi/{id}` | `BapkCoiController@destroy` | Hapus BAPK COI |
| `GET` | `/bapk_coi/download_file/{id}` | `BapkCoiController@downloadBapkCoiFile` | Download file BAPK COI |
| `GET` | `/bapk_cois/{id}` | `BapkCoiController@showByCoi` | BAPK by COI ID |

---

## Catatan Penting

### Base Model

Semua model kepatuhan regulasi mewarisi `BaseModel` yang memberikan fitur:
- **Auto Log Activity**: Setiap create/update/delete otomatis tercatat di `log_activities`
- **Snapshot Before-After**: Perubahan data disimpan dalam format `field: {before: ..., after: ...}`

### Validasi Umum

- File upload: `mimes:pdf|max:25600` (maksimal 25MB)
- Semua tanggal harus valid format `date`
- Relasi foreign key harus `exists` di tabel terkait
- `tag_number_id` di COI dan Izin Disnaker harus `unique` (satu tag number hanya boleh punya satu record)

### Role-based Access

Semua endpoint sertifikat/izin berada di dalam middleware `auth:api`. Beberapa operasi tertentu (create/update/delete master data) memerlukan role `1` atau `99` (admin).
