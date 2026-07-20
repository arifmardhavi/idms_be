# RKAP Anggaran

## 1. Deskripsi Umum

Modul **RKAP (Rencana Kerja Anggaran Perusahaan)** mengelola perencanaan dan realisasi anggaran. Terdapat **4 varian RKAP** yang mewakili kategori anggaran berbeda:

| Varian | Model Master | Model Detail | Controller | Service | Route Prefix |
|--------|-------------|-------------|------------|---------|-------------|
| **TA** (Turn Around) | `RkapTa` | `DetailRkapTa` | `RkapTaController` | `RkapTaService` | `/api/rkap_ta` |
| **OH** (Overhaul) | `RkapOh` | `DetailRkapOh` | `RkapOhController` | `RkapOhService` | `/api/rkap_oh` |
| **RT** (Routine) | `RkapRt` | `DetailRkapRt` | `RkapRtController` | `RkapRtService` | `/api/rkap_rt` |
| **NR** (Non-Routine) | `RkapNr` | `DetailRkapNr` | `RkapNrController` | `RkapNrService` | `/api/rkap_nr` |

Keempat varian memiliki struktur dan pola kode yang identik; perbedaannya hanya pada nama model dan foreign key. Oleh karena itu dokumentasi ini menggunakan varian TA sebagai representasi.

## 2. Struktur Data

### 2.1 Master: `rkap_tas`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigInt (PK) | Auto increment |
| `judul` | string(255) | Judul RKAP |
| `created_at` | timestamp | Waktu buat |
| `updated_at` | timestamp | Waktu update |

### 2.2 Detail: `detail_rkap_tas`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigInt (PK) | Auto increment |
| `rkap_ta_id` | foreignId | FK ke `rkap_tas`, cascade on delete |
| `periode` | tinyInt | Bulan (1-12) |
| `plan` | bigInt | Anggaran rencana (default 0) |
| `actual` | bigInt (nullable) | Realisasi anggaran |
| `created_at` | timestamp | Waktu buat |
| `updated_at` | timestamp | Waktu update |

### 2.3 Relasi

```
RkapTa (master)
 └── detailRkapTa: hasMany(DetailRkapTa, rkap_ta_id)

DetailRkapTa
 └── rkapTa: belongsTo(RkapTa, rkap_ta_id)
```

Model `RkapTa` memiliki:
- `$with = ['detailRkapTa']` — eager load otomatis
- `$appends = ['total']` — attribute total actual
- Accessor `getTotalActualAttribute()` — sum actual seluruh periode

### 2.4 Struktur Response (Resource)

`RkapTaResource` (`app/Http/Resources/RkapTaResource.php:15`):

```json
{
  "id": 1,
  "judul": "RKAP TA 2026",
  "data_periode": [
    { "periode": 1, "plan": 1000000, "actual": 800000, "selisih": 200000 },
    { "periode": 2, "plan": 1000000, "actual": 950000, "selisih": 50000 }
  ],
  "total_value": {
    "plan": 2000000,
    "actual": 1750000,
    "selisih": 250000
  }
}
```

## 3. Service Layer

Semua service (TA/OH/RT/NR) memiliki 4 method identik, dibedakan hanya oleh model yang digunakan.

### 3.1 `getSummary()` — Grand Total Semua RKAP

`app/Services/RkapTaService.php:11-47`

Menghitung aggregat seluruh data RKAP tanpa filter:

- **Grand total**: `SUM(plan)` dan `SUM(actual)` dari seluruh `DetailRkapTa`
- **Total per periode (1-12)**: Group by `periode`, pastikan array selalu 12 elemen menggunakan `collect(range(1, 12))`
- **Return**:
  ```json
  {
    "total_all_periode": { "total_plan": 50000000, "total_actual": 42000000 },
    "total_per_periode": [
      { "periode": 1, "total_plan": 5000000, "total_actual": 4000000 }
    ]
  }
  ```

### 3.2 `store(array $data)` — Create RKAP

`app/Services/RkapTaService.php:49-73`

Dalam `DB::transaction`:
1. Insert master `RkapTa` dengan field `judul`
2. Bulk insert detail dari array `data_periode` (12 record)
3. Load relasi `detailRkapTa` dan return

### 3.3 `getSummaryByRkap(int $rkapId)` — Summary per RKAP

`app/Services/RkapTaService.php:76-114`

Sama seperti `getSummary()` tetapi dengan filter `where('rkap_ta_id', $rkapId)`.

### 3.4 `update($rkap, array $data)` — Update RKAP

`app/Services/RkapTaService.php:116-147`

Dalam `DB::transaction`:
1. Update master `judul`
2. `delete()` semua detail lama
3. Re-insert 12 record detail (periode 1-12) — memastikan konsistensi

### 3.5 Store Pattern (Update Actual per Periode)

Method `updateActual` di Controller (`RkapTaController:190-240`) meng-update nilai `actual` untuk satu periode tertentu tanpa mengubah data lain:

1. Validasi `periode` (1-12) dan `actual`
2. Cari `DetailRkapTa` berdasarkan `rkap_ta_id` + `periode`
3. Jika belum ada → create baru dengan `plan: 0`
4. Jika sudah ada → update `actual`
5. Return response

Endpoint: `PUT /api/rkap_ta/update_actual/{id}`

### 3.6 Casting & Konversi

```php
// DetailRkapTa
protected $casts = [
    'periode' => 'integer',
    'plan'    => 'integer',
    'actual'  => 'integer',
];
```

Nilai integer dipilih untuk plan/actual karena nominal Rupiah (IDR) dalam satuan penuh (tidak pakai koma/desimal).

## 4. Controller Layer

### 4.1 `RkapTaController` (`app/Http/Controllers/RkapTaController.php`)

Inherits `Controller` dan menyediakan 6 method:

| Method | Route | Fungsi |
|--------|-------|--------|
| `index()` | `GET /api/rkap_ta` | List pagination + search + sort + grand total summary |
| `store()` | `POST /api/rkap_ta` | Create RKAP |
| `show()` | `GET /api/rkap_ta/{id}` | Detail + summary per RKAP |
| `update()` | `PUT /api/rkap_ta/{id}` | Update RKAP |
| `destroy()` | `DELETE /api/rkap_ta/{id}` | Hapus RKAP (cascade ke detail) |
| `updateActual()` | `PUT /api/rkap_ta/update_actual/{id}` | Update actual per periode |

### 4.2 Fitur Index

- **Pagination**: Default 10 per page (`?per_page=`)
- **Search**: `?search=` filter by `judul LIKE %search%`
- **Sort**: `?sort_by=id|judul|created_at` + `?sort_order=asc|desc` (default `id desc`)
- **Summary**: Menyertakan grand total dari `RkapTaService::getSummary()` sebagai `total` key di response

### 4.3 `DashboardRkapController` (`app/Http/Controllers/DashboardRkapController.php`)

Endpoint: `GET /api/dashboard_rkap`

| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `usd` | number (optional) | Kurs USD untuk konversi mata uang |

## 5. Dashboard RKAP Service

### 5.1 `DashboardRkapService` (`app/Services/DashboardRkapService.php`)

Menggabungkan data dari keempat varian RKAP (TA, OH, RT, NR) ke dalam satu dashboard.

### 5.2 Method `getData($usd = null)`

**Alur:**

1. Ambil data per varian via `groupByPeriode()` (TA, OH, RT, NR)
2. Hitung total per varian via `getTotal()`
3. Gabungkan ke `getTotalPerPeriode()` — sum plan + actual dari 4 varian per periode
4. Return struktur lengkap

### 5.3 `groupByPeriode($model, $usd)`

```sql
SELECT periode, SUM(plan) as plan, SUM(actual) as actual
FROM detail_rkap_tas
GROUP BY periode
```

- Output selalu 12 periode (1-12) meskipun ada periode yang kosong (nilai 0)
- Dukungan konversi USD: plan / usd, actual / usd (jika parameter `usd` diberikan)
- Setiap item memiliki field `selisih`

### 5.4 Perhitungan Selisih (Deviasi)

Rumus deviasi di `calculatePercent()`:
```
selisih = (plan - actual) / plan * 100
```

Jika `plan = 0`, selisih = 0 (menghindari division by zero).

**Catatan**: Terdapat perbedaan rumus antara `DashboardRkapService`:
- `getTotal()`: `(plan - actual) / plan * 100`
- `getTotalPerPeriode()`: `(actual - plan) / plan * 100` (menggunakan parameter `$actual` pada posisi pertama)

### 5.5 Response Dashboard

```json
{
  "success": true,
  "message": "Dashboard RKAP retrieved successfully.",
  "data": {
    "rkap_ta": [
      { "periode": 1, "plan": 5000000, "actual": 4200000, "selisih": 16 }
    ],
    "rkap_oh": [],
    "rkap_rt": [],
    "rkap_nr": [],
    "total_per_periode": [
      { "periode": 1, "plan": 12000000, "actual": 10000000, "selisih": 20 }
    ],
    "all_rkap": {
      "rkap_ta": { "plan": 50000000, "actual": 40000000, "selisih": 20 },
      "rkap_oh": {},
      "rkap_rt": {},
      "rkap_nr": {}
    }
  },
  "meta": {
    "currency": "IDR",
    "rate": null
  }
}
```

## 6. API Endpoints

### 6.1 CRUD per Varian

| Method | Endpoint | Controller Method |
|--------|----------|------------------|
| GET | `/api/rkap_ta` | `index` |
| POST | `/api/rkap_ta` | `store` |
| GET | `/api/rkap_ta/{id}` | `show` |
| PUT | `/api/rkap_ta/{id}` | `update` |
| DELETE | `/api/rkap_ta/{id}` | `destroy` |
| PUT | `/api/rkap_ta/update_actual/{id}` | `updateActual` |

Pola yang sama berlaku untuk OH, RT, NR dengan mengganti `ta` → `oh`, `rt`, `nr`.

### 6.2 Dashboard

| Method | Endpoint | Controller Method |
|--------|----------|------------------|
| GET | `/api/dashboard_rkap` | `DashboardRkapController@index` |

Query parameter: `?usd=16000` untuk konversi ke USD.

### 6.3 Route Definisi

Semua route berada di `routes/api.php:303-307,367-374` dalam middleware `auth:api`:

```php
// Route API Resource
Route::apiResource('rkap_ta', RkapTaController::class);
Route::apiResource('rkap_oh', RkapOhController::class);
Route::apiResource('rkap_rt', RkapRtController::class);
Route::apiResource('rkap_nr', RkapNrController::class);

// Dashboard
Route::get('/dashboard_rkap', [DashboardRkapController::class, 'index']);

// Update Actual
Route::put('/rkap_ta/update_actual/{id}', [RkapTaController::class, 'updateActual']);
Route::put('/rkap_oh/update_actual/{id}', [RkapOhController::class, 'updateActual']);
Route::put('/rkap_rt/update_actual/{id}', [RkapRtController::class, 'updateActual']);
Route::put('/rkap_nr/update_actual/{id}', [RkapNrController::class, 'updateActual']);
```

## 7. Alur Data Lengkap

### 7.1 Create RKAP

```
Client                    Controller                    Service                  Database
  │                          │                            │                        │
  │─ POST /api/rkap_ta ─────→│                            │                        │
  │  {judul, data_periode}   │── $service->store() ─────→│                        │
  │                          │                            │── DB::transaction ───→│
  │                          │                            │── RkapTa::create() ───→│ rkap_tas
  │                          │                            │── DetailRkapTa::insert─→│ detail_rkap_tas
  │                          │                            │── load('detailRkapTa') │
  │                          │←──── RkapTaResource ──────│                        │
  │←── 201 + resource ──────│                            │                        │
```

### 7.2 Dashboard Aggregation

```
Client                    DashboardRkapController        DashboardRkapService      Database
  │                          │                              │                        │
  │─ GET /dashboard_rkap ───→│                              │                        │
  │         ?usd=16000       │── $service->getData($usd) ──→│                        │
  │                          │                              │── groupByPeriode(TA) ─→│ detail_rkap_tas
  │                          │                              │── groupByPeriode(OH) ─→│ detail_rkap_ohs
  │                          │                              │── groupByPeriode(RT) ─→│ detail_rkap_rts
  │                          │                              │── groupByPeriode(NR) ─→│ detail_rkap_nrs
  │                          │                              │── getTotal() x4        │
  │                          │                              │── getTotalPerPeriode()  │
  │                          │←──── response data ────────│                        │
  │←── 200 + dashboard ─────│                              │                        │
```

## 8. Migration

Setiap varian memiliki 2 migration: master table dan detail table.

**Master** — `rkap_tas`, `rkap_ohs`, `rkap_rts`, `rkap_nrs`:
- `id` (primary)
- `judul` (string)
- `created_at`, `updated_at` (timestamps)

**Detail** — `detail_rkap_tas`, `detail_rkap_ohs`, `detail_rkap_rts`, `detail_rkap_nrs`:
- `id` (primary)
- `rkap_ta_id` / `rkap_oh_id` / `rkap_rt_id` / `rkap_nr_id` (foreign, cascade delete)
- `periode` (tinyInt, 1-12)
- `plan` (bigInt, default 0)
- `actual` (bigInt, nullable)
- `created_at`, `updated_at` (timestamps)

## 9. Daftar File

| File | Lokasi |
|------|--------|
| Model Master | `app/Models/RkapTa.php`, `RkapOh.php`, `RkapRt.php`, `RkapNr.php` |
| Model Detail | `app/Models/DetailRkapTa.php`, `DetailRkapOh.php`, `DetailRkapRt.php`, `DetailRkapNr.php` |
| Controller | `app/Http/Controllers/RkapTaController.php`, `RkapOhController.php`, `RkapRtController.php`, `RkapNrController.php`, `DashboardRkapController.php` |
| Service | `app/Services/RkapTaService.php`, `RkapOhService.php`, `RkapRtService.php`, `RkapNrService.php`, `DashboardRkapService.php` |
| Resource | `app/Http/Resources/RkapTaResource.php`, `RkapOhResource.php`, `RkapRtResource.php`, `RkapNrResource.php` |
| Collection | `app/Http/Resources/RkapTaCollection.php`, `RkapOhCollection.php`, `RkapRtCollection.php`, `RkapNrCollection.php` |
| Migration Master | `database/migrations/*_create_rkap_*s_table.php` |
| Migration Detail | `database/migrations/*_create_detail_rkap_*s_table.php` |
| Route | `routes/api.php:303-307,367-374` |
