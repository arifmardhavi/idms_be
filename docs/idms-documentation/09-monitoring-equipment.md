# 09 - Monitoring Equipment

## Overview

Modul Monitoring Equipment memantau kondisi fisik peralatan secara berkelanjutan berdasarkan Tag Number. Setiap peralatan dilacak perubahannya dari waktu ke waktu menggunakan mekanisme Business Period (snapshot log), sehingga memungkinkan analisis tren kondisi peralatan per periode.

---

## 1. Konsep Monitoring Equipment

Monitoring Equipment merupakan sistem pelacakan kondisi peralatan (equipment condition tracking) yang berfokus pada:

- **Satuan unit peralatan**: Setiap record terikat pada satu Tag Number (satu-satunya `tag_number_id` bersifat unik di tabel `monitoring_equipment`)
- **Status kondisi**: Kondisi peralatan diklasifikasikan menjadi beberapa level berdasarkan keparahan
- **Riwayat perubahan**: Setiap perubahan otomatis di-snapshot ke tabel log berdasarkan periode bisnis
- **Dashboard analitik**: Ringkasan kondisi peralatan per kategori criticality dan SECE

### Flow Kerja

```
User Input (Manual/Import)
    │
    ▼
monitoring_equipment (state terkini)
    │
    ├── updateOrCreate ──┐
    │                   │
    │              monitoring_equipment_logs (snapshot per periode)
    │                   │
    │              Cleanup periode > 3 bulan
    │
    └── Dashboard ──► Agregasi status × criticality
```

---

## 2. Database Schema

### 2.1 Tabel `monitoring_equipment`

Tabel utama yang menyimpan state terkini kondisi peralatan.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto increment |
| `tag_number_id` | FK → `tag_numbers` | Relasi ke Tag Number (**unique**) |
| `kondisi_peralatan` | string, nullable | Kondisi fisik peralatan (referensi master) |
| `status` | string, nullable | Status kondisi: High/Medium/Low/Breakdown |
| `jenis_kerusakan` | string, nullable | Jenis kerusakan |
| `penyebab` | string, nullable | Penyebab kerusakan |
| `penanganan_sementara` | string, nullable | Tindakan penanganan sementara |
| `perbaikan_permanen` | string, nullable | Rencana perbaikan permanen |
| `progress_perbaikan_permanen` | string, nullable | Progres perbaikan permanen |
| `kendala_perbaikan` | string, nullable | Kendala perbaikan |
| `estimasi_perbaikan` | bigint, nullable | Estimasi biaya perbaikan (angka) |
| `target` | string, nullable | Target perbaikan |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update terakhir |

**Constraints:**
- `UNIQUE(tag_number_id)` — satu Tag Number hanya boleh satu record
- `INDEX(status)` — untuk filter dashboard

### 2.2 Tabel `monitoring_equipment_logs`

Tabel log yang menyimpan snapshot kondisi peralatan per periode bisnis.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto increment |
| `tag_number_id` | FK → `tag_numbers` | Relasi ke Tag Number |
| `kondisi_peralatan` | string, nullable | Kondisi peralatan saat snapshot |
| `status` | string, nullable | Status saat snapshot |
| `jenis_kerusakan` | string, nullable | Jenis kerusakan saat snapshot |
| `penyebab` | string, nullable | Penyebab saat snapshot |
| `penanganan_sementara` | string, nullable | Penanganan sementara saat snapshot |
| `perbaikan_permanen` | string, nullable | Perbaikan permanen saat snapshot |
| `progress_perbaikan_permanen` | string, nullable | Progres saat snapshot |
| `kendala_perbaikan` | string, nullable | Kendala saat snapshot |
| `estimasi_perbaikan` | bigint, nullable | Estimasi saat snapshot |
| `target` | string, nullable | Target saat snapshot |
| `period_code` | string(7) | Kode periode (format: `YYYY-MM`) |
| `period_start` | date | Tanggal awal periode bisnis |
| `period_end` | date | Tanggal akhir periode bisnis |
| `created_at` | timestamp | Waktu pembuatan snapshot |
| `updated_at` | timestamp | Waktu update snapshot |

**Constraints:**
- `UNIQUE(tag_number_id, period_code)` — satu Tag Number hanya satu snapshot per periode
- `INDEX(tag_number_id)` — untuk query by Tag Number
- `INDEX(period_code)` — untuk query by periode

---

## 3. Business Period (BusinessPeriod Helper)

### 3.1 Konsep Periode Bisnis

Sistem menggunakan periode bisnis 26–25 (tanggal 26 bulan ini sampai tanggal 25 bulan berikutnya), bukan kalender bulan biasa.

```
Contoh (hari ini: 8 Juli 2026):
  Start : 26 Juni 2026
  End   : 25 Juli 2026
  Code  : 2026-07

Contoh (hari ini: 28 Juli 2026):
  Start : 26 Juli 2026
  End   : 25 Agustus 2026
  Code  : 2026-08
```

### 3.2 Method Tersedia

| Method | Keterangan | Return |
|--------|-----------|--------|
| `BusinessPeriod::current()` | Periode aktif hari ini | `['code' => '2026-07', 'start' => Carbon, 'end' => Carbon]` |
| `BusinessPeriod::previous($n)` | Periode ke-n sebelumnya (`0` = current, `1` = bulan lalu, `2` = 2 bulan lalu) | `['code' => 'YYYY-MM', 'start' => Carbon, 'end' => Carbon]` |
| `BusinessPeriod::allowedPeriods()` | 3 periode yang boleh disimpan (current + 2 bulan sebelumnya) | `['2026-07', '2026-06', '2026-05']` |
| `BusinessPeriod::dashboardPeriods()` | Periode untuk dashboard (current, last_month, two_months_ago) | `['current' => '...', 'last_month' => '...', 'two_months_ago' => '...']` |

### 3.3 Logika Penentuan Periode

```php
// Jika tanggal >= 26 → periode dimulai bulan ini
// Jika tanggal < 26  → periode dimulai bulan lalu
// End = Start + 1 bulan - 1 hari
// Code = format(Y-m) dari end date
```

---

## 4. Status Classification

### 4.1 Status Peralatan

Status kondisi peralatan diklasifikasikan berdasarkan tingkat keparahan:

| Nilai | Label | Keterangan |
|-------|-------|------------|
| `0` | High | Kondisi kritis, perlu penanganan segera |
| `1` | Medium | Kondisi sedang, perlu perhatian |
| `2` | Low | Kondisi baik, tidak urgent |
| `3` | Breakdown | Peralatan dalam kondisi rusak total |

> **Catatan:** Pada migrasi terakhir, kolom `status` diubah dari `char(2)` menjadi `string` untuk mendukung nilai text juga (selain angka).

### 4.2 Mapping Import (Excel)

Pada saat import Excel, service akan melakukan mapping otomatis dari string ke angka:

| String Excel | Nilai Internal |
|-------------|---------------|
| `high` | `0` |
| `medium` | `1` |
| `low` | `2` |
| `breakdown` | `3` |

---

## 5. Fields Monitoring Equipment

### 5.1 Field Input

| Field | Tipe | Validasi | Keterangan |
|-------|------|----------|------------|
| `tag_number_id` | FK | required, exists, unique | ID Tag Number peralatan |
| `kondisi_peralatan` | string | nullable, max:255 | Kondisi fisik peralatan (referensi master) |
| `status` | string | nullable, max:255 | Status kondisi (High/Medium/Low/Breakdown) |
| `jenis_kerusakan` | string | nullable, max:255 | Jenis kerusakan |
| `penyebab` | string | nullable, max:255 | Penyebab kerusakan |
| `penanganan_sementara` | string | nullable, max:255 | Penanganan sementara |
| `perbaikan_permanen` | string | nullable, max:255 | Rencana perbaikan permanen |
| `progress_perbaikan_permanen` | string | nullable, max:255 | Progres perbaikan permanen |
| `kendala_perbaikan` | string | nullable, max:255 | Kendala perbaikan |
| `estimasi_perbaikan` | integer | nullable, min:0 | Estimasi biaya perbaikan |
| `target` | date | nullable | Target perbaikan |

### 5.2 Field Response (MonitoringEquipmentResource)

Response API mengembalikan field-field di atas ditambah:
- `criticality` — dari relasi Tag Number (0=High, 1=Medium High, 2=Medium, 3=Negligible, 4=Low)
- `sece` — Safety Critical Equipment dari Tag Number (0=Tidak, 1=Ya)
- `tag_number` — nama Tag Number dari relasi
- `logs` — array riwayat snapshot (log) peralatan

---

## 6. MonitoringEquipmentLog — Snapshot Periode

### 6.1 Mekanisme Snapshot

Setiap kali `monitoring_equipment` dibuat atau diupdate, sistem otomatis membuat/mengupdate snapshot di `monitoring_equipment_logs`:

```
1. Ambil data terbaru dari monitoring_equipment (fresh)
2. Copy field yang sama ke monitoring_equipment_logs
3. Tambahkan period_code, period_start, period_end dari BusinessPeriod::current()
4. updateOrCreate berdasarkan (tag_number_id + period_code)
5. Cleanup: hapus log yang period_code-nya tidak di allowedPeriods()
```

### 6.2 Retensi Data

- **Retensi**: 3 periode terakhir saja (current + 2 bulan sebelumnya)
- **Mekanisme cleanup**: Setiap insert/update, log lama yang di luar `allowedPeriods()` otomatis dihapus
- **Log lama**: Tidak dapat dikembalikan (hard delete)

### 6.3 Query Log di API

Pada `index` dan `show`, log yang ditampilkan adalah log yang **bukan** periode saat ini (karena data saat ini sudah ada di record utama `monitoring_equipment`):

```php
->with('logs', function ($query) {
    $query->where('period_code', '!=', $currentPeriod)
          ->latest('period_code');
})
```

---

## 7. Import Workflow (MonitoringEquipmentImportService)

Proses import Excel dilakukan dalam 6 langkah:

### 7.1 Alur Import

```
Langkah 1: Parse Excel → konversi header ke snake_case
Langkah 2: Validasi file tidak kosong
Langkah 3: Loop per baris, convert row ke associative array
Langkah 4: Lookup Tag Number berdasarkan tag_number string
Langkah 5: createOrUpdate monitoring_equipment + snapshot log
Langkah 6: Cleanup log lama (> 3 periode)
```

### 7.2 Detail Per Langkah

| Langkah | Deskripsi | Error Handling |
|---------|-----------|----------------|
| 1 | Baca Excel, ambil sheet pertama, convert header ke `snake_case` | Return error jika file kosong |
| 2 | Cek apakah ada data selain header | Return `success: false` jika kosong |
| 3 | Skip baris kosong, convert `array_combine(headers, row)` | — |
| 4 | Lookup `Tag_number::where('tag_number', ...)` | Tag tidak ditemukan → failed + error message |
| 5 | `MonitoringEquipment::updateOrCreate` berdasarkan `tag_number_id` | — |
| 6 | `MonitoringEquipmentLog::updateOrCreate` (snapshot) + cleanup | — |

### 7.3 Format Template Excel

**Sheet 1: Monitoring Equipment** (input area)

| Kolom | Header |
|-------|--------|
| A | Tag Number |
| B | Status |
| C | Jenis Kerusakan |
| D | Penyebab |
| E | Penanganan Sementara |
| F | Perbaikan Permanen |
| G | Progress Perbaikan Permanen |
| H | Kendala Perbaikan |
| I | Estimasi Perbaikan |
| J | Target |

**Sheet 2: Reference** (referensi dropdown, proteksi password)

| Status | Description |
|--------|------------|
| 0 | High |
| 1 | Medium |
| 2 | Low |
| 3 | Breakdown |

**Validasi import:**
- Format file: `.xlsx` atau `.xls`
- Ukuran maksimum: 10MB

### 7.4 Response Import

```json
{
  "success": true,
  "message": "Import selesai.",
  "summary": {
    "total": 50,
    "success": 48,
    "failed": 2,
    "skipped": 0,
    "errors": [
      {
        "row": 5,
        "tag_number": "INVALID-001",
        "message": "Tag Number tidak ditemukan."
      }
    ]
  }
}
```

---

## 8. Dashboard Analytics (MonitoringEquipmentDashboardService)

### 8.1 Struktur Dashboard

Dashboard menampilkan data dari 3 periode:

| Periode | Sumber Data | Keterangan |
|---------|-------------|------------|
| `current` | `monitoring_equipment` | Data terkini (live) |
| `last_month` | `monitoring_equipment_logs` | Snapshot periode lalu |
| `two_months_ago` | `monitoring_equipment_logs` | Snapshot 2 bulan lalu |

### 8.2 Cross-Tabulation: Status × Criticality

Setiap periode menghasilkan agregasi silang antara Status peralatan dan Kategori Criticality:

#### Kategori Kritisitas

| Kategori | Kondisi | Keterangan |
|----------|---------|------------|
| `sece_yes` | `tag_numbers.sece = 1` | Safety Critical Equipment |
| `criticality_high` | `sece = 0 AND criticality = 0` | Criticality High (non-SECE) |
| `criticality_medium_high` | `sece = 0 AND criticality = 1` | Criticality Medium High (non-SECE) |
| `criticality_other` | `sece = 0 AND criticality IN (2,3,4)` | Criticality Medium/Negligible/Low |
| `uncategorized` | `sece IS NULL` atau (`sece = 0 AND criticality IS NULL`) | Belum terklasifikasi |

#### Contoh Struktur Response per Periode

```json
{
  "current": {
    "all": {
      "high": 10, "medium": 5, "low": 3, "breakdown": 2, "total": 20
    },
    "high": {
      "sece_yes": 3, "criticality_high": 2, "criticality_medium_high": 1,
      "criticality_other": 3, "uncategorized": 1, "total": 10
    },
    "medium": {
      "sece_yes": 1, "criticality_high": 1, "criticality_medium_high": 1,
      "criticality_other": 1, "uncategorized": 1, "total": 5
    },
    "low": {
      "sece_yes": 0, "criticality_high": 1, "criticality_medium_high": 1,
      "criticality_other": 1, "uncategorized": 0, "total": 3
    },
    "breakdown": {
      "sece_yes": 0, "criticality_high": 1, "criticality_medium_high": 0,
      "criticality_other": 1, "uncategorized": 0, "total": 2
    },
    "summary": {
      "sece_yes": 4, "criticality_high": 5, "criticality_medium_high": 3,
      "criticality_other": 6, "uncategorized": 2, "grand_total": 20
    }
  },
  "last_month": { "...struktur sama..." },
  "two_months_ago": { "...struktur sama..." }
}
```

---

## 9. Master Data

### 9.1 KondisiPeralatan

Model untuk data referensi kondisi peralatan.

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto increment |
| `kondisi_peralatan` | string | Nama kondisi peralatan |
| `status` | string | Status terkait |
| `is_active` | boolean | Aktif/tidak aktif |

**Endpoint:**
- `GET /api/kondisi_peralatan` — list semua
- `GET /api/kondisi_peralatan/active` — list yang aktif saja
- `PUT /api/kondisi_peralatan/update_active/{id}` — toggle status aktif
- `POST /api/kondisi_peralatan` — tambah baru
- `PUT /api/kondisi_peralatan/{id}` — update
- `DELETE /api/kondisi_peralatan/{id}` — hapus

### 9.2 StatusPeralatan

Model untuk data referensi status peralatan.

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto increment |
| `status_peralatan` | string | Nama status peralatan |
| `is_active` | boolean | Aktif/tidak aktif |

**Endpoint:**
- `GET /api/status_peralatan` — list semua
- `GET /api/status_peralatan/active` — list yang aktif saja
- `PUT /api/status_peralatan/update_active/{id}` — toggle status aktif
- `POST /api/status_peralatan` — tambah baru
- `PUT /api/status_peralatan/{id}` — update
- `DELETE /api/status_peralatan/{id}` — hapus

---

## 10. Import & Export

### 10.1 Import Monitoring Equipment

| Aspek | Detail |
|-------|--------|
| Method | `POST` |
| Endpoint | `/api/monitoring_equipment/import` |
| Content-Type | `multipart/form-data` |
| Parameter | `file` (xlsx/xls, max 10MB) |
| Service | `MonitoringEquipmentImportService::import()` |

### 10.2 Export Monitoring Equipment

| Aspek | Detail |
|-------|--------|
| Method | `GET` |
| Endpoint | `/api/monitoring_equipment/export` |
| Filter (query string) | `search`, `criticality`, `status`, `sece` |
| Output | File `.xlsx` download |
| Export Class | `MonitoringEquipmentExport` |

**Kolom Export:**
No, Tag Number, Criticality, SECE, Status, Jenis Kerusakan, Penyebab, Penanganan Sementara, Perbaikan Permanen, Progress, Kendala, Estimasi, Target, Updated At

### 10.3 Export Monitoring Equipment Logs

| Aspek | Detail |
|-------|--------|
| Method | `GET` |
| Endpoint | `/api/monitoring_equipment/export/logs` |
| Filter (query string) | `search`, `period_code`, `criticality`, `status` |
| Output | File `.xlsx` download |
| Export Class | `MonitoringEquipmentLogExport` |

**Kolom Export:**
No, Periode, Tag Number, Criticality, SECE, Status, Jenis Kerusakan, Penyebab, Penanganan Sementara, Perbaikan Permanen, Progress, Kendala, Estimasi, Target, Period Start, Period End, Snapshot

### 10.4 Template Download

| Aspek | Detail |
|-------|--------|
| Method | `GET` |
| Endpoint | `/api/monitoring_equipment/template` |
| Output | File `Monitoring_Equipment_Template.xlsx` |
| Export Class | `MonitoringEquipmentTemplateExport` |

**Isi Template:**
- Sheet 1 (`Monitoring Equipment`): Form input dengan header yang sudah ditentukan + dropdown Status
- Sheet 2 (`Reference`): Referensi dropdown Status (0–3), dilindungi password

---

## 11. API Endpoints

### 11.1 Monitoring Equipment

| Method | Endpoint | Keterangan | Auth |
|--------|----------|------------|------|
| `GET` | `/api/monitoring_equipment` | List semua (paginated) | JWT |
| `POST` | `/api/monitoring_equipment` | Buat baru + auto snapshot | JWT |
| `GET` | `/api/monitoring_equipment/{id}` | Detail + riwayat log | JWT |
| `PUT` | `/api/monitoring_equipment/{id}` | Update + auto snapshot + cleanup | JWT |
| `DELETE` | `/api/monitoring_equipment/{id}` | Hapus + cascade hapus log | JWT |

### 11.2 Custom Routes

| Method | Endpoint | Keterangan | Auth |
|--------|----------|------------|------|
| `GET` | `/api/monitoring_equipment/template` | Download template Excel | JWT |
| `POST` | `/api/monitoring_equipment/import` | Import dari Excel | JWT |
| `GET` | `/api/monitoring_equipment/export` | Export ke Excel | JWT |
| `GET` | `/api/monitoring_equipment/export/logs` | Export log ke Excel | JWT |
| `GET` | `/api/monitoring_equipment/dashboard` | Dashboard analytics | JWT |
| `PUT` | `/api/monitoring_equipment/update_log/{id}` | Update log spesifik | JWT |

### 11.3 Query Parameters (Index)

| Parameter | Tipe | Default | Keterangan |
|-----------|------|---------|------------|
| `per_page` | integer | 10 | Jumlah data per halaman |
| `search` | string | — | Pencarian global (tag_number, jenis_kerusakan, penyebab, kondisi, dll.) |
| `sort_by` | string | `id` | Field untuk sorting |
| `sort_order` | string | `desc` | `asc` atau `desc` |
| `status` | string | — | Filter status (0/1/2/3) |
| `criticality` | integer | — | Filter criticality dari Tag Number |
| `sece` | integer | — | Filter SECE dari Tag Number (0/1) |

### 11.4 Sortable Fields

| Parameter | Kolom Database |
|-----------|---------------|
| `id` | `monitoring_equipment.id` |
| `tag_number` | `tag_numbers.tag_number` |
| `criticality` | `tag_numbers.criticality` |
| `sece` | `tag_numbers.sece` |
| `kondisi_peralatan` | `monitoring_equipment.kondisi_peralatan` |
| `status` | `monitoring_equipment.status` |
| `jenis_kerusakan` | `monitoring_equipment.jenis_kerusakan` |
| `penyebab` | `monitoring_equipment.penyebab` |
| `penanganan_sementara` | `monitoring_equipment.penanganan_sementara` |
| `perbaikan_permanen` | `monitoring_equipment.perbaikan_permanen` |
| `progress_perbaikan_permanen` | `monitoring_equipment.progress_perbaikan_permanen` |
| `kendala_perbaikan` | `monitoring_equipment.kendala_perbaikan` |
| `estimasi_perbaikan` | `monitoring_equipment.estimasi_perbaikan` |
| `target` | `monitoring_equipment.target` |
| `created_at` | `monitoring_equipment.created_at` |
| `updated_at` | `monitoring_equipment.updated_at` |

---

## 12. File Reference

| File | Keterangan |
|------|------------|
| `app/Models/MonitoringEquipment.php` | Model utama monitoring equipment |
| `app/Models/MonitoringEquipmentLog.php` | Model log/snapshot per periode |
| `app/Models/KondisiPeralatan.php` | Master data kondisi peralatan |
| `app/Models/StatusPeralatan.php` | Master data status peralatan |
| `app/Helpers/BusinessPeriod.php` | Helper periode bisnis (26–25) |
| `app/Services/MonitoringEquipmentImportService.php` | Service import Excel |
| `app/Services/MonitoringEquipmentDashboardService.php` | Service dashboard analytics |
| `app/Http/Controllers/MonitoringEquipmentController.php` | Controller utama |
| `app/Http/Requests/StoreMonitoringEquipmentRequest.php` | Validasi create |
| `app/Http/Requests/UpdateMonitoringEquipmentRequest.php` | Validasi update |
| `app/Http/Requests/ImportMonitoringEquipmentRequest.php` | Validasi import |
| `app/Http/Resources/MonitoringEquipmentResource.php` | JSON Resource response |
| `app/Exports/MonitoringEquipmentExport.php` | Export data ke Excel |
| `app/Exports/MonitoringEquipmentLogExport.php` | Export log ke Excel |
| `app/Exports/MonitoringEquipmentTemplateExport.php` | Template Excel (multi-sheet) |
| `app/Exports/MonitoringEquipmentTemplateSheet.php` | Sheet 1 template (input) |
| `app/Exports/MonitoringEquipmentReferenceSheet.php` | Sheet 2 template (referensi) |
| `database/migrations/*_create_monitoring_equipment_table.php` | Migration tabel utama |
| `database/migrations/*_create_monitoring_equipment_logs_table.php` | Migration tabel log |
| `database/migrations/*_alter_monitoring_equipment_add_kondisi_peralatan*.php` | Migration tambah kolom |
| `routes/api.php` (baris 319–326) | Route definition |
