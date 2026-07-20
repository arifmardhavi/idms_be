# 11. Data Rekayasa

## Overview

Modul Data Rekayasa mengelola dokumen teknis terkait equipment/pipa, meliputi GA Drawing, Datasheet, dan Master Document Register (MDR). Setiap data rekayasa terhubung dengan tag number melalui `EngineeringData`.

## Entity Relationship

```
TagNumber ──< EngineeringData ──< GaDrawing
                      │
                      ├──< Datasheet
                      │
                      └──< MdrFolder ──< MdrItem
```

---

## 1. EngineeringData

Tabel penghubung antara tag number dan seluruh dokumen rekayasa.

### Tabel: `engineering_data`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `tag_number_id` | bigint (FK) | Relasi ke `tag_numbers.id` (unique) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Computed Attributes

| Atribut | Deskripsi | Sumber |
|---------|-----------|--------|
| `unit` | Nama unit dari tag number | `tagNumber.unit.unit_name` |
| `ga_drawings_count` | Jumlah GA Drawing | `gaDrawings().count()` |
| `datasheets_count` | Jumlah Datasheet | `datasheets().count()` |
| `mdr_count` | Jumlah MDR Folder | `mdrFolders().count()` |

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `tagNumber` | belongsTo | Tag_number | `tag_number_id` |
| `gaDrawings` | hasMany | GaDrawing | `engineering_data_id` |
| `datasheets` | hasMany | Datasheet | `engineering_data_id` |
| `mdrFolders` | hasMany | MdrFolder | `engineering_data_id` |

### Cascade Delete

Saat EngineeringData dihapus:
- Semua GaDrawing terkait dihapus (termasuk file fisik di `public/engineering_data/ga_drawing/`)
- Semua Datasheet terkait dihapus (termasuk file fisik di `public/engineering_data/datasheet/`)

---

## 2. GA Drawing

Dokumen gambar General Arrangement (GA) untuk equipment/pipa.

### Tabel: `ga_drawings`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `engineering_data_id` | bigint (FK) | Relasi ke `engineering_data.id` |
| `nama_dokumen` | varchar(255) | Nama dokumen (nullable) |
| `no_dokumen` | varchar(255) | Nomor dokumen (nullable) |
| `drawing_file` | varchar(255) | Nama file yang disimpan |
| `date_drawing` | date | Tanggal drawing (nullable) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_{ddmmyyyy}_{version}.{extension}
```

Contoh: `GA-Pipa-001_20072026_0.pdf`

### Direktori Penyimpanan

```
public/engineering_data/ga_drawing/
```

### Validasi Upload

- Format: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, zip, rar`
- Ukuran maks: 200MB per file
- Maksimal 10 file per upload

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `engineeringData` | belongsTo | EngineeringData | `engineering_data_id` |

---

## 3. Datasheet

Dokumen datasheet teknis untuk equipment/pipa.

### Tabel: `datasheets`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `engineering_data_id` | bigint (FK) | Relasi ke `engineering_data.id` |
| `nama_dokumen` | varchar(255) | Nama dokumen (nullable) |
| `no_dokumen` | varchar(255) | Nomor dokumen (unique, nullable) |
| `datasheet_file` | varchar(255) | Nama file yang disimpan |
| `date_datasheet` | date | Tanggal datasheet (nullable) |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

### Format Penamaan File

```
{original_name}_datasheet_{tag_number_clean}_{ddmmyyyy}_{version}.{extension}
```

`tag_number_clean` = tag number tanpa suffix `/00` (contoh: `1-C-25`)

Contoh: `Spec-Valve-001_datasheet_1-C-25_20072026_0.pdf`

### Direktori Penyimpanan

```
public/engineering_data/datasheet/
```

### Validasi Upload

- Format: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, zip, rar`
- Ukuran maks: 200MB per file
- Maksimal 10 file per upload
- `no_dokumen` harus unik

### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `engineeringData` | belongsTo | EngineeringData | `engineering_data_id` |

---

## 4. MDR (Master Document Register)

Hierarki folder untuk mengelola dokumen MDR secara terstruktur.

```
EngineeringData → MdrFolder (folder_name) → MdrItem (file_name)
```

### 4.1 MdrFolder

#### Tabel: `mdr_folders`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `engineering_data_id` | bigint (FK) | Relasi ke `engineering_data.id` |
| `folder_name` | varchar(100) | Nama folder |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

#### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `engineeringData` | belongsTo | EngineeringData | `engineering_data_id` |
| `mdrItems` | hasMany | MdrItem | `mdr_folder_id` |

#### Resource Transform (MdrResource)

```json
{
    "id": 1,
    "engineering_data_id": 1,
    "folder_name": "Dokumen Piping",
    "files": [...]
}
```

### 4.2 MdrItem

#### Tabel: `mdr_items`

| Field | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | Auto-increment |
| `mdr_folder_id` | bigint (FK) | Relasi ke `mdr_folders.id` |
| `file_name` | varchar(255) | Nama file yang disimpan |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu pembaruan |

#### Format Penamaan File

```
{original_name}_MDR_{ddmmyyyy}_{version}.{extension}
```

Contoh: `Piping-Spec-001_MDR_20072026_0.pdf`

#### Direktori Penyimpanan

```
public/engineering_data/mdr/
```

#### Validasi Upload

- Format: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png`
- Ukuran maks: 200MB per file
- Maksimal 10 file per upload

#### Relasi

| Relasi | Tipe | Model | Foreign Key |
|--------|------|-------|-------------|
| `mdrFolder` | belongsTo | MdrFolder | `mdr_folder_id` |

---

## 5. API Endpoints

Base path: `/api`

### 5.1 EngineeringData

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/engineering_data` | Daftar semua engineering data | Bearer |
| POST | `/engineering_data` | Buat engineering data baru (+ optional GA Drawing) | Bearer |
| GET | `/engineering_data/{id}` | Detail engineering data | Bearer |
| PUT | `/engineering_data/{id}` | Update engineering data | Bearer |
| DELETE | `/engineering_data/{id}` | Hapus engineering data + cascading | Bearer |

### 5.2 GA Drawing

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/ga_drawing` | Daftar semua GA Drawing | Bearer |
| POST | `/ga_drawing` | Upload GA Drawing (multi-file) | Bearer |
| GET | `/ga_drawing/{id}` | Detail GA Drawing | Bearer |
| PUT | `/ga_drawing/{id}` | Update GA Drawing | Bearer |
| DELETE | `/ga_drawing/{id}` | Hapus GA Drawing + file fisik | Bearer |
| GET | `/ga_drawing/engineering/{id}` | GA Drawing berdasarkan engineering_data_id | Bearer |

### 5.3 Datasheet

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/datasheet` | Daftar semua Datasheet | Bearer |
| POST | `/datasheet` | Upload Datasheet (multi-file) | Bearer |
| GET | `/datasheet/{id}` | Detail Datasheet | Bearer |
| PUT | `/datasheet/{id}` | Update Datasheet | Bearer |
| DELETE | `/datasheet/{id}` | Hapus Datasheet + file fisik | Bearer |
| GET | `/datasheet/engineering/{id}` | Datasheet berdasarkan engineering_data_id | Bearer |

### 5.4 MDR Folder

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/mdr_folder` | Daftar semua MDR Folder (+ items) | Bearer |
| POST | `/mdr_folder` | Buat MDR Folder baru | Bearer |
| GET | `/mdr_folder/{id}` | Detail MDR Folder | Bearer |
| PUT | `/mdr_folder/{id}` | Update MDR Folder | Bearer |
| DELETE | `/mdr_folder/{id}` | Hapus MDR Folder | Bearer |
| GET | `/mdr_folder/engineering/{id}` | MDR Folder berdasarkan engineering_data_id | Bearer |

### 5.5 MDR Item

| Metode | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| GET | `/mdr_item` | Daftar semua MDR Item | Bearer |
| POST | `/mdr_item` | Upload MDR Item (multi-file) | Bearer |
| GET | `/mdr_item/{id}` | Detail MDR Item | Bearer |
| DELETE | `/mdr_item/{id}` | Hapus MDR Item + file fisik | Bearer |
| GET | `/mdr_item/folder/{id}` | MDR Item berdasarkan mdr_folder_id | Bearer |

---

## 6. Flow Operasional

### Upload Dokumen Rekayasa

```
1. Buat EngineeringData (POST /engineering_data)
   └─ Input: tag_number_id

2. Upload GA Drawing (POST /ga_drawing)
   └─ Input: engineering_data_id + file(s)

3. Upload Datasheet (POST /datasheet)
   └─ Input: engineering_data_id + file(s)

4. Buat MDR Folder (POST /mdr_folder)
   └─ Input: engineering_data_id + folder_name

5. Upload MDR Item (POST /mdr_item)
   └─ Input: mdr_folder_id + file(s)
```

### Pengambilan Data

```
GET /engineering_data/{id}
├─ Menampilkan data engineering + counts

GET /ga_drawing/engineering/{id}
├─ Menampilkan semua GA Drawing untuk engineering data

GET /datasheet/engineering/{id}
├─ Menampilkan semua Datasheet untuk engineering data

GET /mdr_folder/engineering/{id}
├─ Menampilkan semua MDR Folder + items untuk engineering data

GET /mdr_item/folder/{id}
├─ Menampilkan semua MDR Item dalam folder
```

### Cascade Delete

```
DELETE /engineering_data/{id}
├─ Menghapus semua GA Drawing (termasuk file fisik)
├─ Menghapus semua Datasheet (termasuk file fisik)
└─ Menghapus record EngineeringData

DELETE /ga_drawing/{id}
├─ Menghapus file fisik
└─ Menghapus record

DELETE /datasheet/{id}
├─ Menghapus file fisik
└─ Menghapus record

DELETE /mdr_item/{id}
├─ Menghapus file fisik
└─ Menghapus record

DELETE /mdr_folder/{id}
└─ Menghapus record (items harus dihapus manual)
```

---

## 7. Contoh Request

### Buat Engineering Data

```json
POST /api/engineering_data
{
    "tag_number_id": 1,
    "drawing_file": "(file upload - optional)"
}
```

### Upload GA Drawing (Multi-File)

```
POST /api/ga_drawing
Content-Type: multipart/form-data

engineering_data_id: 1
nama_dokumen: GA Drawing Piping
no_dokumen: GA-001/2026
date_drawing: 2026-07-20
drawing_file[]: (file1.pdf)
drawing_file[]: (file2.pdf)
```

### Upload Datasheet (Multi-File)

```
POST /api/datasheet
Content-Type: multipart/form-data

engineering_data_id: 1
nama_dokumen: Datasheet Valve
no_dokumen: DS-001/2026
date_datasheet: 2026-07-20
datasheet_file[]: (file1.pdf)
```

### Buat MDR Folder

```json
POST /api/mdr_folder
{
    "engineering_data_id": 1,
    "folder_name": "Dokumen Piping"
}
```

### Upload MDR Item (Multi-File)

```
POST /api/mdr_item
Content-Type: multipart/form-data

mdr_folder_id: 1
file_name[]: (file1.pdf)
file_name[]: (file2.pdf)
```
