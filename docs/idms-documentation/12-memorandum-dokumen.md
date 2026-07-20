# 12. Memorandum & Dokumen

## Overview

Modul Memorandum & Dokumen mengelola berbagai jenis dokumen resmi perusahaan, meliputi Historical Memorandum, Lampiran Memo, Management of Change (MOC), Plant Inspection Report (PIR), NIB, P&ID, dan Project Specification. Modul ini menyediakan fitur upload, download, dan manajemen versi file.

## Entity Relationship

```
Unit ──< HistoricalMemorandum ──< LampiranMemo
  │           │
Category ─────┤
              │
TagNumber ────┘ (nullable, comma-separated)
              │
              ├──< Pir (nullable FK)
              │
Unit ──< Moc
  │
Category ──< Moc
TagNumber ──< Moc (nullable, comma-separated)

Nib (standalone)
P_id (standalone)
ProjectSpec (standalone)
```

---

## 1. Historical Memorandum

Dokumen memorandum historis yang terkait dengan unit, kategori, dan tag number tertentu. Mendukung multiple tag number (comma-separated) dan memiliki lampiran terkait.

### Tabel: `historical_memorandum`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `unit_id` | bigint (FK) | Relasi ke `units.id` (required) |
| `category_id` | bigint (FK) | Relasi ke `categories.id` (required) |
| `tag_number_id` | varchar | Tag number IDs (comma-separated, nullable) |
| `no_dokumen` | varchar(255) | Nomor dokumen (unique, required) |
| `perihal` | varchar(255) | Perihal/judul memorandum (required) |
| `tipe_memorandum` | varchar | Tipe memorandum (required) |
| `tanggal_terbit` | date | Tanggal terbit (required) |
| `memorandum_file` | varchar(255) | Nama file memorandum (PDF, required saat create) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

Contoh: `Memo-Inspeksi-001_20072026_0.pdf`

### Direktori Penyimpanan

```
public/historical_memorandum/
```

### Validasi Upload

- Format: `pdf`
- Ukuran maks: 30MB
- `no_dokumen` harus unik

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `unit` | belongsTo | Unit | `unit_id` |
| `category` | belongsTo | Category | `category_id` |
| `tag_number` | belongsTo | Tag_number | - (manual query via comma-separated IDs) |
| `lampiran_memo` | hasMany | LampiranMemo | `historical_memorandum_id` |

### Cascade Delete

Saat Historical Memorandum dihapus:
1. Semua Lampiran Memo terkait dihapus (termasuk file fisik di `public/historical_memorandum/lampiran/`)
2. File memorandum utama dihapus dari `public/historical_memorandum/`
3. Record LampiranMemo dihapus

### Custom Attributes

| Atribut | Deskripsi |
|---------|-----------|
| `tag_numbers` | Array tag_number dari parsing `tag_number_id` (comma-separated) |

---

## 2. Lampiran Memo

Lampiran/lampiran file yang terkait dengan Historical Memorandum. Mendukung multi-file upload (maks 10 file).

### Tabel: `lampiran_memos`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `historical_memorandum_id` | bigint (FK) | Relasi ke `historical_memorandum.id` (required) |
| `lampiran_memo` | varchar | Nama file lampiran (nullable) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

Contoh: `Lampiran-SOP-001_20072026_0.pdf`

### Direktori Penyimpanan

```
public/historical_memorandum/lampiran/
```

### Validasi Upload

- Format: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, zip, rar`
- Ukuran maks: 200MB per file
- Maksimal 10 file per upload

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `historicalMemorandum` | belongsTo | HistoricalMemorandum | `historical_memorandum_id` |

---

## 3. MOC (Management of Change)

Dokumen perubahan manajemen yang terkait dengan unit, kategori, dan tag number.

### Tabel: `mocs`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `unit_id` | bigint (FK) | Relasi ke `units.id` (required) |
| `category_id` | bigint (FK) | Relasi ke `categories.id` (required) |
| `tag_number_id` | varchar | Tag number IDs (comma-separated, nullable) |
| `no_dokumen` | varchar(255) | Nomor dokumen (unique, required) |
| `perihal` | varchar(255) | Perihal/judul MOC (required) |
| `tipe_moc` | varchar | Tipe MOC (required) |
| `tanggal_terbit` | date | Tanggal terbit (required) |
| `moc_file` | varchar(255) | Nama file MOC (PDF, required saat create) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

Contoh: `MOC-Perubahan-001_20072026_0.pdf`

### Direktori Penyimpanan

```
public/moc/
```

### Validasi Upload

- Format: `pdf`
- Ukuran maks: 30MB
- `no_dokumen` harus unik

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `unit` | belongsTo | Unit | `unit_id` |
| `category` | belongsTo | Category | `category_id` |
| `tag_number` | belongsTo | Tag_number | - (manual query via comma-separated IDs) |

### Cascade Delete

Saat MOC dihapus:
1. Semua Lampiran MOC terkait dihapus (termasuk file fisik di `public/moc/lampiran/`)
2. File MOC utama dihapus dari `public/moc/`

---

## 4. PIR (Plant Inspection Report)

Dokumen laporan inspeksi tanaman yang dapat terkait dengan Historical Memorandum.

### Tabel: `pirs`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `judul` | varchar(255) | Judul PIR (required) |
| `tanggal_pir` | date | Tanggal PIR (required) |
| `historical_memorandum_id` | bigint (FK) | Relasi ke `historical_memorandum.id` (nullable) |
| `pir_file` | varchar(255) | Nama file PIR (nullable) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Computed Attributes

| Atribut | Deskripsi | Sumber |
|---------|-----------|--------|
| `memorandum_file` | File memorandum terkait | `historical_memorandum.memorandum_file` |

### Direktori Penyimpanan

```
public/pir/
```

### Validasi Upload

- Format: `pdf`
- `historical_memorandum_id` harus exists (jika diisi)

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `historical_memorandum` | belongsTo | HistoricalMemorandum | `historical_memorandum_id` |

### Catatan Update

Saat `historical_memorandum_id` diisi:
- File PIR yang ada akan dihapus dan `pir_file` di-set null

Saat `pir_file` diunggah:
- File lama dihapus jika ada
- `historical_memorandum_id` di-set null (menggunakan file baru)

---

## 5. NIB (Nomor Induk Berusaha)

Dokumen NIB perusahaan.

### Tabel: `nibs`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `no_nib` | varchar(200) | Nomor NIB (unique, required) |
| `judul` | varchar(255) | Judul NIB (required) |
| `tanggal_nib` | date | Tanggal NIB (required) |
| `nib_file` | varchar(255) | Nama file NIB (PDF, required) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Direktori Penyimpanan

```
public/nib/
```

### Validasi Upload

- Format: `pdf`
- `no_nib` harus unik

---

## 6. P&ID (Piping and Instrumentation Diagram)

Dokumen diagram piping dan instrumentasi.

### Tabel: `p_ids`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `file_name` | varchar | Nama file (nullable) |
| `p_id_file` | varchar | Nama file P&ID (required) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

Contoh: `Piping-Diagram-001_20072026_0.pdf`

### Direktori Penyimpanan

```
public/p_id/
```

### Validasi Upload

- Format: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png`
- Maksimal 10 file per upload

---

## 7. Project Specification

Dokumen spesifikasi proyek.

### Tabel: `project_specs`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `no_project_spec` | varchar(200) | Nomor Project Spec (unique, required) |
| `judul` | varchar(255) | Judul (required) |
| `tanggal_project_spec` | date | Tanggal (required) |
| `project_spec_file` | varchar(255) | Nama file (PDF, required) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Direktori Penyimpanan

```
public/project_specs/
```

### Validasi Upload

- Format: `pdf`
- `no_project_spec` harus unik

---

## 8. API Endpoints

Base path: `/api`

### 8.1 Historical Memorandum

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/historical_memorandum` | Daftar semua historical memorandum | Bearer |
| POST | `/historical_memorandum` | Buat historical memorandum baru | Bearer |
| GET | `/historical_memorandum/{id}` | Detail historical memorandum | Bearer |
| PUT | `/historical_memorandum/{id}` | Update historical memorandum | Bearer |
| DELETE | `/historical_memorandum/{id}` | Hapus historical memorandum + cascading | Bearer |
| GET | `/historical_memorandum/download_file/{id}` | Download file memorandum | Bearer |
| POST | `/historical_memorandum/download` | Download multiple memorandum sebagai ZIP | Bearer |
| GET | `/historical_memorandum/lampiran/{id}` | Lampiran berdasarkan historical_memorandum_id | Bearer |

### 8.2 Lampiran Memo

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/lampiran_memo` | Daftar semua lampiran memo | Bearer |
| POST | `/lampiran_memo` | Upload lampiran memo (multi-file) | Bearer |
| GET | `/lampiran_memo/{id}` | Detail lampiran memo | Bearer |
| DELETE | `/lampiran_memo/{id}` | Hapus lampiran memo + file fisik | Bearer |
| GET | `/lampiran_memo/download_file/{id}` | Download file lampiran memo | Bearer |
| POST | `/lampiran_memo/download` | Download multiple lampiran sebagai ZIP | Bearer |

### 8.3 MOC (Management of Change)

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/moc` | Daftar semua MOC | Bearer |
| POST | `/moc` | Buat MOC baru | Bearer |
| GET | `/moc/{id}` | Detail MOC | Bearer |
| PUT | `/moc/{id}` | Update MOC | Bearer |
| DELETE | `/moc/{id}` | Hapus MOC + cascading | Bearer |

### 8.4 PIR (Plant Inspection Report)

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/pir` | Daftar semua PIR | Bearer |
| POST | `/pir` | Buat PIR baru | Bearer |
| GET | `/pir/{id}` | Detail PIR | Bearer |
| PUT | `/pir/{id}` | Update PIR | Bearer |
| DELETE | `/pir/{id}` | Hapus PIR + file fisik | Bearer |

### 8.5 NIB

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/nib` | Daftar semua NIB | Bearer |
| POST | `/nib` | Buat NIB baru | Bearer |
| GET | `/nib/{id}` | Detail NIB | Bearer |
| PUT | `/nib/{id}` | Update NIB | Bearer |
| DELETE | `/nib/{id}` | Hapus NIB + file fisik | Bearer |
| GET | `/nib/download_file/{id}` | Download file NIB | Bearer |

### 8.6 P&ID

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/p_id` | Daftar semua P&ID | Bearer |
| POST | `/p_id` | Upload P&ID (multi-file) | Bearer |
| GET | `/p_id/{id}` | Detail P&ID | Bearer |
| PUT | `/p_id/{id}` | Update P&ID | Bearer |
| DELETE | `/p_id/{id}` | Hapus P&ID + file fisik | Bearer |
| GET | `/p_id/download_file/{id}` | Download file P&ID | Bearer |

### 8.7 Project Specification

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/project_spec` | Daftar semua Project Spec | Bearer |
| POST | `/project_spec` | Buat Project Spec baru | Bearer |
| GET | `/project_spec/{id}` | Detail Project Spec | Bearer |
| PUT | `/project_spec/{id}` | Update Project Spec | Bearer |
| DELETE | `/project_spec/{id}` | Hapus Project Spec + file fisik | Bearer |
| GET | `/project_spec/download_file/{id}` | Download file Project Spec | Bearer |

---

## 9. Flow Operasional

### Upload Historical Memorandum dengan Lampiran

```
1. Buat Historical Memorandum (POST /historical_memorandum)
   └─ Input: unit_id, category_id, no_dokumen, perihal, tipe_memorandum, tanggal_terbit, memorandum_file

2. Upload Lampiran Memo (POST /lampiran_memo)
   └─ Input: historical_memorandum_id + file(s)
   └─ Maksimal 10 file per upload
```

### Pengambilan Data Lampiran

```
GET /historical_memorandum/lampiran/{historical_memorandum_id}
└─ Mengembalikan semua lampiran untuk memorandum tertentu
```

### Download Dokumen

```
# Download single file
GET /historical_memorandum/download_file/{id}

# Download multiple sebagai ZIP
POST /historical_memorandum/download
Body: { "ids": [1, 2, 3] }

# Lampiran
GET /lampiran_memo/download_file/{id}
POST /lampiran_memo/download
Body: { "ids": [1, 2, 3] }
```

### Cascade Delete Historical Memorandum

```
DELETE /historical_memorandum/{id}
├─ Menghapus semua Lampiran Memo terkait (termasuk file fisik)
│  └─ public/historical_memorandum/lampiran/{filename}
├─ Menghapus file memorandum utama
│  └─ public/historical_memorandum/{filename}
└─ Menghapus record historical memorandum
```

---

## 10. Contoh Request

### Buat Historical Memorandum

```
POST /api/historical_memorandum
Content-Type: multipart/form-data

unit_id: 1
category_id: 2
tag_number_id: "5,12,18"
no_dokumen: MEM-001/2026
perihal: Inspeksi Berkala Unit A
tipe_memorandum: Inspeksi
tanggal_terbit: 2026-07-20
memorandum_file: (file.pdf)
```

### Upload Lampiran Memo

```
POST /api/lampiran_memo
Content-Type: multipart/form-data

historical_memorandum_id: 1
lampiran_memo[]: (file1.pdf)
lampiran_memo[]: (file2.pdf)
```

### Buat MOC

```
POST /api/moc
Content-Type: multipart/form-data

unit_id: 1
category_id: 2
tag_number_id: "5,12"
no_dokumen: MOC-001/2026
perihal: Perubahan Spesifikasi Valve
tipe_moc: Perubahan Teknis
tanggal_terbit: 2026-07-20
moc_file: (file.pdf)
```

### Buat PIR

```
POST /api/pir
Content-Type: multipart/form-data

judul: Laporan Inspeksi Bulanan Juli 2026
tanggal_pir: 2026-07-20
historical_memorandum_id: 1 (optional)
pir_file: (file.pdf) (optional)
```

### Buat NIB

```
POST /api/nib
Content-Type: multipart/form-data

no_nib: 1234567890
judul: NIB Perusahaan
tanggal_nib: 2026-07-20
nib_file: (file.pdf)
```

### Upload P&ID

```
POST /api/p_id
Content-Type: multipart/form-data

file_name: Diagram Piping Unit A
p_id_file[]: (file1.pdf)
p_id_file[]: (file2.png)
```

### Buat Project Specification

```
POST /api/project_spec
Content-Type: multipart/form-data

no_project_spec: PS-001/2026
judul: Spesifikasi Proyek Unit A
tanggal_project_spec: 2026-07-20
project_spec_file: (file.pdf)
```

### Download Multiple Files sebagai ZIP

```
POST /api/historical_memorandum/download
Content-Type: application/json

{ "ids": [1, 2, 3] }

Response: { "success": true, "url": "http://host/file_historical_memorandum.zip" }
```

---

## 11. Penyimpanan File

### Ringkasan Direktori

| Entity | Direktori |
|--------|-----------|
| Historical Memorandum | `public/historical_memorandum/` |
| Lampiran Memo | `public/historical_memorandum/lampiran/` |
| MOC | `public/moc/` |
| PIR | `public/pir/` |
| NIB | `public/nib/` |
| P&ID | `public/p_id/` |
| Project Spec | `public/project_specs/` |

### Format Penamaan File (Umum)

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

- Versi dimulai dari `0` dan increment jika file sudah ada
- Format tanggal: `ddmmyyyy` (contoh: `20072026`)
- Nama file original diambil tanpa ekstensi, lalu disambung dengan tanggal dan versi
