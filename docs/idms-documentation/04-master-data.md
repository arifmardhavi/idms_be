# Master Data

Master data merupakan fondasi dari seluruh modul di IDMS. Setiap peralatan di unit kilang harus terdaftar dalam hierarki master data sebelum dapat digunakan di modul lain (PLO, COI, Inspeksi, dsb).

## 1. Hierarki Master Data

```
Unit (area kilang)
 └── Category (kategori peralatan)
      └── Type (tipe peralatan)
           └── Tag Number (identitas unik peralatan)
```

Setiap level memiliki relasi parent-child:

| Level | Model | Tabel | Keterangan |
|-------|-------|-------|------------|
| 1 | Unit | `units` | Area kilang / unit pengolahan |
| 2 | Category | `categories` | Kategori umum peralatan |
| 3 | Type | `types` | Tipe spesifik peralatan |
| 4 | Tag_number | `tag_numbers` | Identitas unik peralatan |

## 2. Unit

**Model:** `app/Models/Unit.php`
**Controller:** `app/Http/Controllers/UnitController.php`
**Tabel:** `units`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `unit_name` | string, max:100 | - | Nama unit (required) |
| `unit_type` | integer | `0` | Tipe unit: `0` = Instalasi, `1` = Pipa Penyalur |
| `description` | string, nullable | `null` | Deskripsi unit |
| `status` | integer | `0` | `0` = aktif, `1` = nonaktif |

### Contoh Data

| unit_name | unit_type | status |
|-----------|-----------|--------|
| Unit 11 - Crude Distillation Unit | 0 | 1 |
| Unit 12 - Hydrocracking Unit | 0 | 1 |
| Pipa Transmisi - Zone A | 1 | 1 |

### Konvensi Status

- **0** = Aktif (terlihat di dropdown selection)
- **1** = Nonaktif / archived

> **Catatan:** Endpoint `GET /api/activeunits` mengembalikan data dengan `status = 1` (aktif). Endpoint `PUT /api/units/nonactive/{id}` mengatur `status = 0`.

### Validasi (Store & Update)

```php
'unit_name' => 'required|string|max:100',
'unit_type' => 'required|integer',
'description' => 'nullable|string',
'status' => 'required|in:0,1',
```

## 3. Category

**Model:** `app/Models/Category.php`
**Controller:** `app/Http/Controllers/CategoryController.php`
**Tabel:** `categories`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `category_name` | string, max:255 | - | Nama kategori (required) |
| `description` | string, nullable | `null` | Deskripsi kategori |
| `status` | integer | `0` | `0` = aktif, `1` = nonaktif |

### Relasi

- `hasMany(Type)` — Satu kategori memiliki banyak tipe

### Contoh Data

| category_name | description | status |
|---------------|-------------|--------|
| Static Equipment | Peralatan statik (fixed equipment) | 1 |
| Rotating Equipment | Peralatan berputar | 1 |
| Pressure Relief Device | Perangkat pelepas tekanan | 1 |
| Piping | Sistem perpipaan | 1 |
| Electrical | Peralatan kelistrikan | 1 |

### Validasi (Store & Update)

```php
'category_name' => 'required|string|max:255',
'description' => 'nullable|string',
'status' => 'required|in:0,1',
```

## 4. Type

**Model:** `app/Models/Type.php`
**Controller:** `app/Http/Controllers/TypeController.php`
**Tabel:** `types`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `type_name` | string, max:255 | - | Nama tipe (required) |
| `description` | string, nullable | `null` | Deskripsi tipe |
| `status` | integer | `0` | `0` = aktif, `1` = nonaktif |
| `category_id` | integer (FK) | - | Foreign key ke `categories.id` (required, cascade) |

### Relasi

- `belongsTo(Category)` — Tipe milik satu kategori

### Contoh Data

| type_name | category | status |
|-----------|----------|--------|
| Heat Exchanger | Static Equipment | 1 |
| Storage Tank | Static Equipment | 1 |
| Centrifugal Pump | Rotating Equipment | 1 |
| Compressor | Rotating Equipment | 1 |
| Pressure Relief Valve | Pressure Relief Device | 1 |

### Validasi (Store & Update)

```php
'type_name' => 'required|string|max:255',
'description' => 'nullable|string',
'status' => 'required|in:0,1',
'category_id' => 'required|exists:categories,id',
```

## 5. Tag Number

**Model:** `app/Models/Tag_number.php`
**Controller:** `app/Http/Controllers/Tag_numberController.php`
**Tabel:** `tag_numbers`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `unit_id` | integer (FK) | - | Foreign key ke `units.id` (required) |
| `type_id` | integer (FK) | - | Foreign key ke `types.id` (required) |
| `tag_number` | string, max:255 | - | Identitas unik peralatan (required, unique, auto-uppercase) |
| `criticality` | integer | `null` | Level kritikalitas (see mapping) |
| `sece` | integer | `null` | Safety Critical Equipment: `0` = Tidak, `1` = Ya |
| `description` | string, nullable | `null` | Deskripsi peralatan |
| `status` | integer | `0` | `0` = aktif, `1` = nonaktif |

### Relasi

- `belongsTo(Unit)` — Tag number milik satu unit
- `belongsTo(Type)` — Tag number bertipe satu tipe

### Casts

```php
protected $casts = [
    'status' => 'integer',
    'criticality' => 'integer',
    'sece' => 'integer',
];
```

### Criticality Mapping

| Nilai | Label | Keterangan |
|-------|-------|------------|
| `0` | High | Kritikalitas tinggi |
| `1` | Medium High | Kritikalitas sedang-tinggi |
| `2` | Medium | Kritikalitas sedang |
| `3` | Negligible | Tidak signifikan |
| `4` | Low | Rendah |

### SECE Mapping

| Nilai | Label | Keterangan |
|-------|-------|------------|
| `0` | Tidak | Bukan Safety Critical Equipment |
| `1` | Ya | Safety Critical Equipment |

### Contoh Data

| tag_number | unit | type | criticality | sece | status |
|------------|------|------|-------------|------|--------|
| 11-E-101 | Unit 11 | Heat Exchanger | 0 | 1 | 1 |
| 11-P-201A | Unit 11 | Centrifugal Pump | 1 | 0 | 1 |
| 12-TK-301 | Unit 12 | Storage Tank | 2 | 0 | 1 |

### Normalisasi Otomatis

Saat store/update, tag number akan di-normalisasi:

```php
$request->merge([
    'tag_number' => strtoupper(preg_replace('/\s+/', '', $request->tag_number)),
]);
```

- Spasi dihapus
- Huruf kapital otomatis (contoh: `11-e-101` → `11-E-101`)

### Validasi (Store & Update)

```php
'tag_number' => 'required|string|max:255|unique:tag_numbers,tag_number', // unique kecuali diri sendiri saat update
'description' => 'nullable|string',
'type_id' => 'required|exists:types,id',
'unit_id' => 'required|exists:units,id',
'sece' => 'nullable|in:0,1',
'criticality' => 'nullable|in:0,1,2,3,4',
'status' => 'required|in:0,1',
```

## 6. Import Excel (Tag Number)

Import Excel menggunakan library **Maatwebsite Excel** untuk bulk create dan update tag number.

### 6.1 TagNumberImport (Bulk Create)

**File:** `app/Imports/TagNumberImport.php`
**Endpoint:** `POST /api/tagnumbers/import`

Membuat data tag number baru dari file Excel (.xlsx, .xls, .csv).

**Kolom Excel yang diharapkan:**

| Kolom Excel | Mapping ke Field | Keterangan |
|-------------|------------------|------------|
| `unit` | `unit_id` | Nama unit (di-lookup ke tabel units) |
| `tipe` | `type_id` | Nama tipe (di-lookup ke tabel types) |
| `tag_number` | `tag_number` | Auto-uppercase |
| `criticality` | `criticality` | String → integer (see mapping) |
| `sece` | `sece` | String → integer (see mapping) |
| `status` | `status` | String → integer (see mapping) |
| `deskripsi` | `description` | Deskripsi peralatan |

**Mapping fungsi internal:**

```php
// Criticality string → integer
'high'        => 0,
'medium high' => 1,
'medium'      => 2,
'negligible'  => 3,
'low'         => 4,

// SECE string → integer
'ya'   => 1,
'yes'  => 1,
'tidak' => 0,
'no'   => 0,

// Status string → integer
'aktif'     => 1,
'active'    => 1,
'nonaktif'  => 0,
'nonactive' => 0,
```

**Aturan error handling:**

- Jika unit/tipe tidak ditemukan → baris dilewati, error dicatat ke `$this->errors`
- Jika tag_number sudah ada di database → baris dilewati, error dicatat
- Error dikembalikan sebagai response `422` dengan detail per baris

### 6.2 TagNumberImportUpdate (Bulk Update)

**File:** `app/Imports/TagNumberImportUpdate.php`
**Endpoint:** `POST /api/tagnumbers/import_update`

Memperbarui data tag number yang sudah ada dari file Excel.

**Perbedaan dengan TagNumberImport:**

| Aspek | TagNumberImport | TagNumberImportUpdate |
|-------|----------------|----------------------|
| Operasi | `create` (INSERT) | `update` (UPDATE) |
| Jika tag_number belum ada | Skip + error | Skip + error ("belum terdaftar") |
| Jika tag_number sudah ada | Skip + error ("sudah ada") | Update field |
| Kolom `tag_number` | Wajib (sebagai identifier) | Wajib (sebagai identifier, tidak diubah) |
| Field yang diupdate | Semua | Semua kecuali tag_number |

**Aturan update:**

- Field yang kosong di Excel → mempertahankan nilai lama dari database
- Field `tag_number` tidak diubah (hanya sebagai kunci pencarian)
- Relasi `unit_id` dan `type_id` di-update berdasarkan nama di Excel

## 7. API Endpoints

Semua endpoint berada di prefix `/api` dan memerlukan autentikasi JWT.

### 7.1 Units

| Metode | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| `GET` | `/api/units` | Semua role | Ambil semua unit |
| `GET` | `/api/units/{id}` | Semua role | Ambil satu unit |
| `POST` | `/api/units` | Admin (1,99) | Buat unit baru |
| `PUT` | `/api/units/{id}` | Admin (1,99) | Update unit |
| `DELETE` | `/api/units/{id}` | Admin (1,99) | Hapus unit |
| `GET` | `/api/activeunits` | Semua role | Ambil unit aktif (status=1) |
| `PUT` | `/api/units/nonactive/{id}` | Semua role | Nonaktifkan unit (status→0) |
| `GET` | `/api/exportunits` | Admin (1,99) | Export unit ke Excel |

### 7.2 Categories

| Metode | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| `GET` | `/api/categories` | Semua role | Ambil semua kategori |
| `GET` | `/api/categories/{id}` | Semua role | Ambil satu kategori |
| `POST` | `/api/categories` | Admin (1,99) | Buat kategori baru |
| `PUT` | `/api/categories/{id}` | Admin (1,99) | Update kategori |
| `DELETE` | `/api/categories/{id}` | Admin (1,99) | Hapus kategori |
| `GET` | `/api/activecategories` | Semua role | Ambil kategori aktif (status=1) |
| `PUT` | `/api/categories/nonactive/{id}` | Semua role | Nonaktifkan kategori (status→0) |

> **Catatan:** Route `GET /api/categories/unit/{unitId}` terdaftar di `api.php` namun method `showByUnit` belum terimplementasi di `CategoryController`.

### 7.3 Types

| Metode | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| `GET` | `/api/types` | Semua role | Ambil semua tipe (dengan eager load category) |
| `GET` | `/api/types/{id}` | Semua role | Ambil satu tipe |
| `POST` | `/api/types` | Admin (1,99) | Buat tipe baru |
| `PUT` | `/api/types/{id}` | Admin (1,99) | Update tipe |
| `DELETE` | `/api/types/{id}` | Admin (1,99) | Hapus tipe |
| `GET` | `/api/activetypes` | Semua role | Ambil tipe aktif (status=1) |
| `GET` | `/api/types/category/{categoryId}` | Semua role | Ambil tipe berdasarkan kategori (hanya status=1) |
| `PUT` | `/api/types/nonactive/{id}` | Semua role | Nonaktifkan tipe (status→0) |

### 7.4 Tag Numbers

| Metode | Endpoint | Akses | Keterangan |
|--------|----------|-------|------------|
| `GET` | `/api/tagnumbers` | Semua role | Ambil semua tag number (dengan relasi type, unit) |
| `GET` | `/api/tagnumbers/{id}` | Semua role | Ambil satu tag number |
| `POST` | `/api/tagnumbers` | Admin (1,99) | Buat tag number baru |
| `PUT` | `/api/tagnumbers/{id}` | Admin (1,99) | Update tag number |
| `DELETE` | `/api/tagnumbers/{id}` | Admin (1,99) | Hapus tag number |
| `GET` | `/api/tagnumbers/type/{typeId}` | Semua role | Tag number berdasarkan tipe (status=1) |
| `GET` | `/api/tagnumbers/typeunit/{typeId}/{unitId}` | Semua role | Tag number berdasarkan tipe + unit |
| `GET` | `/api/tagnumbers/unit/{unitId}` | Semua role | Tag number berdasarkan unit |
| `GET` | `/api/tagnumbers/tag_number/{id}` | Semua role | Tag number by ID (dengan join tabel) |
| `GET` | `/api/tagname?tag_number={value}` | Semua role | Cari tag number by nama (query string) |
| `PUT` | `/api/tagnumbers/nonactive/{id}` | Semua role | Nonaktifkan tag number (status→0) |
| `POST` | `/api/tagnumbers/import` | Admin (1,99) | Import tag number dari Excel (bulk create) |
| `POST` | `/api/tagnumbers/import_update` | Admin (1,99) | Update tag number dari Excel (bulk update) |

## 8. Flow Diagrams

Untuk visualisasi flow master data, referensi file drawio tersedia di folder:

```
docs/flow/
```

Flow yang tersedia meliputi:

- **Flow Pembuatan Master Data** — Urutan pembuatan Unit → Category → Type → Tag Number
- **Flow Import Tag Number** — Proses import Excel dengan validasi
- **Flow Nonaktifasi Master Data** — Cascade effect saat nonaktifasi entitas
