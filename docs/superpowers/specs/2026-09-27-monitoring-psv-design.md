# Design: Tambah Modul Monitoring PSV

**Tanggal:** 2026-09-27
**Status:** Draft untuk review

## Latar Belakang

IDMS membutuhkan modul baru "Monitoring PSV" untuk memantau masa berlaku
sertifikat COI pada peralatan PSV (Pressure Safety Valve) dan TSV
(Temperature Safety Valve). Dua kolom pertama (Tag Number, Masa Berlaku) bersumber
dari data COI, sedangkan empat kolom sisanya adalah anotasi manual.

Modul ini memakai pola modul **Monitoring Equipment** (`monitoring_equipment`)
karena paling dekat secara bentuk: tabel anak dengan kolom input manual, filter +
search + sort whitelist, dashboard agregat, dan export Excel. Yang **tidak** diambil
adalah snapshot periode `BusinessPeriod`, karena Monitoring PSV tidak menampilkan
riwayat bulanan.

## Penentuan Cakupan PSV/TSV

Tidak ada kolom `psv`/`tsv` di database. Satu-satunya penanda adalah pola di teks
`tag_numbers.tag_number`, contoh `1-PSV-177/00`, `1-TSV-175S/00`, `5-TSV-501/00`.

Aturan tunggal: `tag_number LIKE '%PSV%' OR tag_number LIKE '%TSV%'`
(case-insensitive mengikuti collation kolom).

Sengaja **tidak** memakai `type_id` (`types` 116 = Pressure Safety Valve,
133 = Temperature Safety Valve) karena keduanya tidak ekuivalen:

| Cara pencocokan | Tag yang didapat | Masalah |
|---|---|---|
| `tag_number LIKE '%PSV%'/%TSV%` | 175 | — |
| `type_id IN (116, 133)` | 160 | 14 tag PSV tercatat "Breather Valve"; 33 tag TSV tercatat "Pressure Safety Valve" |

Angka aktual saat rollout (data 2026-09-27):

| Set | Jumlah |
|---|---|
| Tag PSV | 137 |
| Tag TSV | 38 |
| Tag PSV + TSV | 175 |
| …punya record COI (**ikut ditampilkan**) | **135** |
| …tidak punya record COI (diabaikan) | 40 |

Keputusan: hanya tag yang sudah punya COI yang ditampilkan (INNER JOIN).

## Kolom & Skema (Migration `create_monitoring_psv_table`)

Baris monitoring **tidak** menyimpan `tag_number` maupun masa berlaku. Kuncinya
`coi_id`, dan kedua kolom read-only itu dibaca live dari `cois` + `tag_numbers`.
Konsekuensinya, koreksi pada COI langsung tercermin tanpa sinkronisasi.

| Field | Tipe | Constraint | Sumber |
|-------|------|------------|--------|
| `id` | bigint unsigned | PK, auto-increment | — |
| `coi_id` | foreignId | → `cois`, cascade delete, **unique** | anchor ke COI |
| `status_redundant` | string(50) | NULLABLE | manual |
| `pid_no` | string(50) | NULLABLE | manual |
| `kategori` | string(50) | NULLABLE, validasi `in:CSO,CSC` | manual |
| `keterangan` | text | NULLABLE | manual |
| `created_at` / `updated_at` | timestamp | | — |

Index: `kategori`, `status_redundant`. `kategori` tidak memakai tabel lookup —
hanya ada dua nilai dan keduanya sudah pasti, konsisten dengan
`monitoring_equipment.status` yang juga `varchar`.

## Sumber Tunggal Aturan — `app/Support/`

Dua kelas ini adalah satu-satunya definisi aturan PSV/TSV dan bucket masa berlaku.
Kalau aturannya ditulis ulang di beberapa tempat, batasnya pasti melenceng di
salah satu.

### `PsvTagNumber.php`

| Member | Fungsi |
|--------|--------|
| `MARKERS = ['PSV', 'TSV']` | substring penanda |
| `matches(?string $tagNumber): bool` | sisi PHP (`stripos`) — dipakai listener |
| `query(?string $table)` | query PSV/TSV pada `tag_numbers` |
| `applyToQuery(Builder, string $column)` | tempel aturan ke query ber-alias — dipakai scope model |
| `conditionSql(string $column, string $marker)` | ekspresi `LIKE` untuk agregat dashboard |

### `MasaBerlakuBucket.php`

Bucket mengikuti konvensi 9-bulan modul COI, dengan batas bawah `<= 0` sehingga
tag yang jatuh tempo **hari ini** sudah berstatus `expired`:

| `sisa_hari` | Bucket | Arti |
|-------------|--------|------|
| `> 270` | `safe` | lebih dari 9 bulan lagi |
| `1 … 270` | `warning` | ≤ 9 bulan lagi |
| `≤ 0` | `expired` | sudah lewat / jatuh tempo hari ini |

> Perbedaan dari modul COI: `CoiController` memakai `DATEDIFF(...) < 0` untuk
> expired, sedangkan Monitoring PSV memakai `<= 0`. Selisihnya 1 baris
> (tag yang jatuh tempo tepat hari ini). Sengaja dibedakan.

| Member | Fungsi |
|--------|--------|
| `forDays(?int $days): string` | bucket dari sisa hari |
| `daysSql(string $dateColumn)` | `DATEDIFF(col, CURDATE())` |
| `conditionSql(string $dateColumn, string $bucket)` | ekspresi boolean satu bucket |
| `sql(string $dateColumn)` | ekspresi `CASE` yang menghasilkan nilai bucket |
| `countSql(string $dateColumn, string $bucket)` | `SUM(CASE WHEN … THEN 1 ELSE 0 END)` |
| `filter(Builder, string $dateColumn, ?string $bucket)` | filter query; nilai di luar `ALL` diabaikan |

Batas bucket dipakai oleh lima pemanggil (query list, filter, resource, export,
dashboard) sehingga tidak mungkin melenceng.

### `overdue_date` NULL

`forDays(null)` mengembalikan `expired`. Supaya ketiga jalur konsisten,
`conditionSql` untuk `expired` juga memuat NULL:

```
(cois.overdue_date IS NULL OR DATEDIFF(cois.overdue_date, CURDATE()) <= 0)
```

Tanpa itu tiga tempat menjawab berbeda: resource menampilkan `expired`, filter
`expired` tidak memunculkan baris itu, dan `sql()` mengklasifikasinya `warning`.
COI tanpa tanggal masa berlaku adalah gap kepatuhan, bukan kondisi aman. Data
saat ini tidak punya baris seperti ini, jadi angka bucket tidak berubah.

## Model — `app/Models/MonitoringPsv.php`

- `extends BaseModel`, `use HasFactory`
- `protected $table = 'monitoring_psv'` — **wajib eksplisit**, tanpa ini Laravel
  akan mengpluralisasi "MonitoringPsv" menjadi `monitoring_psvs`.
- `$fillable = ['status_redundant', 'pid_no', 'kategori', 'keterangan']`.
  `coi_id` sengaja **tidak** ada di sini karena kolom sistem, bukan input user;
  nilainya di-set langsung oleh sync service sehingga tidak bisa ter-mass-assign.
- `$casts = ['sisa_hari' => 'integer', 'overdue_date' => 'date']` — keduanya
  berasal dari alias query, bukan kolom fisik tabel ini.
- Relasi `coi()` belongsTo `Coi`. Akses tag number: `$this->coi->tag_number->tag_number`
  (nama relasi pada `Coi` memang snake_case sejak model lama).
- Accessor `getRecordLabelAttribute()` mengembalikan tag number, dipakai
  `GlobalActivityObserver` sebagai label human-readable.

### Scope

| Scope | Isi | Dipakai oleh |
|-------|-----|--------------|
| `scopeJoinedToCoi` | join `cois` + `tag_numbers` saja | `show`, `update` |
| `scopePsvTsv` | join + filter PSV/TSV (tanpa `select`) | `index`, `export`, dashboard, `syncAll` |
| `scopeWithCoiData` | `select` `tag_numbers.tag_number` + `cois.overdue_date` + `selectRaw` alias `sisa_hari` | `index`, `export`, `show`, `update` |

`scopePsvTsv` sengaja **tidak** menambah `select` supaya bisa dipakai ulang oleh
query agregat dashboard yang butuh `selectRaw` sendiri. Inilah alasan scope dipecah
dua, bukan digabung.

`scopePsvTsv` juga yang membuat **kelengkapan list tidak bergantung pada listener**:
kalau `tag_number` di-rename sehingga tidak lagi PSV/TSV, barisnya otomatis hilang
dari list tanpa perlu penghapusan.

## Model — `app/Models/Coi.php` (diubah)

Ditambah `booted()` dengan listener `created`:

```php
protected static function booted(): void
{
    static::created(function (self $coi) {
        if (! PsvTagNumber::matches($coi->tag_number?->tag_number)) {
            return;
        }

        app(MonitoringPsvSyncService::class)->syncFromCoi($coi);
    });
}
```

## Service

### `MonitoringPsvSyncService.php`

| Method | Pemanggil | Sifat |
|--------|-----------|-------|
| `syncFromCoi(Coi): ?MonitoringPsv` | listener `Coi::created` | `SELECT` dulu, baru insert kalau belum ada |
| `syncAll(): array` | `POST /monitoring_psv/sync` | satu `DB::transaction`, `firstOrCreate` per COI |

`syncAll()` mengembalikan `['scanned', 'created', 'skipped']`. Both sides idempotent
dan **tidak pernah menghapus** baris — penghapusan terjadi otomatis lewat cascade
delete dari `cois`.

Implementasi memakai `pluck('cois.id')` + `array_flip` untuk lookup, **bukan**
`chunkById`, karena `chunkById` pada query ber-join menghasilkan `id` ambigu
(`SQLSTATE[23000] 1052 Column 'id' in field list is ambiguous`).

### `MonitoringPsvDashboardService.php`

Dua query, bukan satu `selectRaw` raksasa seperti
`MonitoringEquipmentDashboardService` (yang butuh 524 baris SQL untuk cross-tab
5 dimensi × 3 periode). Monitoring PSV hanya butuh dua agregat:

1. `summary()` — total, safe/warning/expired, PSV vs TSV (1 baris)
2. `byKategori()` — `groupBy(kategori)`, `ORDER BY kategori IS NULL, kategori`
   supaya kategori yang belum diisi selalu di akhir
3. `statusRedundant()` — redundant / not_redundant / unfilled (1 baris)

Dashboard bersifat **global**: tidak menerima filter, sama seperti
`MonitoringEquipmentDashboardService`.

## HTTP Layer

### `app/Http/Resources/MonitoringPsvResource.php`

Kolom (12, sama persis untuk `index` dan `show`): `id`, `coi_id`, `tag_number`,
`masa_berlaku`, `sisa_hari`, `status_masa_berlaku`, empat kolom manual,
`created_at`, `updated_at`, plus blok `coi` via `whenLoaded` (hanya pada
`show`) berisi `id`, `no_certificate`, `issue_date`, `overdue_date`,
`coi_certificate`.

> **Sengaja tidak diekspos:** `description`, `criticality`, `sece`
> (dari `tag_numbers`) dan `no_certificate` (dari `cois`). Semuanya tidak lagi
> dipakai response maupun export, jadi juga dibuang dari `SELECT` di
> `scopeWithCoiData()`. `no_certificate` dan `issue_date` tetap tersedia
> melalui blok `coi`.

> **Jebakan:** blok `coi` dibaca dari **relasi** `$this->coi`, bukan dari atribut
> hasil `SELECT`. Menulis `$this->issue_date` akan mengembalikan `null` tanpa
> error, karena Eloquent tidak tahu kolom tersebut tidak di-select.

> **Penting:** model `Coi` tidak punya date casts, sehingga `issue_date` /
> `overdue_date` tiba sebagai string mentah. `optional($string)->format()`
> mengembalikan `null` **diam-diam** karena `Optional::__call` hanya meneruskan ke
> object. Karena itu parsing tanggal dilakukan eksplisit via `Carbon::parse()`
> di private helper `formatDate()` / `formatDateTime()`.

### `app/Http/Requests/UpdateMonitoringPsvRequest.php`

`extends BaseRequest`. Hanya empat kolom manual; `tag_number`, `masa_berlaku`, dan
`coi_id` **tidak** punya aturan sama sekali sehingga otomatis tertuang oleh
`validated()` dan tidak bisa di-overwrite dari HTTP.

| Field | Rule |
|-------|------|
| `status_redundant` | `nullable|string|max:50` |
| `pid_no` | `nullable|string|max:50` |
| `kategori` | `nullable|string|in:CSO,CSC` |
| `keterangan` | `nullable|string|max:1000` |

### `app/Exports/MonitoringPsvExport.php`

`FromQuery` + `WithHeadings` + `WithMapping` + `ShouldAutoSize` + `WithTitle`.
Query-nya **dibangun controller** dan dipakai bersama dengan `index`, jadi isi
export dijamin identik dengan hasil filter di layar. 9 kolom: `No`, `Tag Number`,
`Masa Berlaku COI`, `Sisa Hari`, `Status Masa Berlaku`, `Status Redundant`,
`PID No`, `Kategori`, `Keterangan` — konsisten dengan response, tanpa
`Description` dan `No Certificate`.

### `app/Http/Controllers/MonitoringPsvController.php`

| Method | Endpoint | Isi |
|--------|----------|-----|
| `index` | `GET /monitoring_psv` | list + filter + search + sort + paginate |
| `show` | `GET /monitoring_psv/{monitoring_psv}` | 1 baris + detail COI |
| `update` | `PUT /monitoring_psv/{monitoring_psv}` | 4 kolom manual saja |
| `sync` | `POST /monitoring_psv/sync` | backfill/repair, return counts |
| `dashboard` | `GET /monitoring_psv/dashboard` | ringkasan |
| `export` | `GET /monitoring_psv/export` | Excel, `activity()->log('export', 'MonitoringPsv')` |

`baseQuery()` adalah method privat yang dipakai bersama `index` dan `export`.

Query param:

| Param | Contoh | Keterangan |
|-------|--------|------------|
| `search` | `PSV-177` | `tag_numbers.tag_number`, `pid_no`, `keterangan` |
| `kategori` | `CSO` | exact match |
| `status_redundant` | `Redundant` | exact match |
| `status_masa_berlaku` | `expired` | `safe` / `warning` / `expired` |
| `sort_by` | `sisa_hari` | whitelist, default `sisa_hari` |
| `sort_order` | `asc` | default `asc` (paling mendesak di atas) |
| `per_page` | `25` | default 10, dikunci 1..100 |

`$allowedSort`: `id`, `tag_number`, `masa_berlaku`, `sisa_hari`, `kategori`,
`status_redundant`, `pid_no`, `created_at`, `updated_at`. Kolom di luar whitelist
diabaikan (fallback ke default), tidak pernah di-interpolasi ke query.

`sort_by=status_masa_berlaku` memakai `FIELD(…)` agar urutannya
`expired → warning → safe`, mengikuti pola `MonitoringEquipmentController`.

`sort_order` hanya menerima `desc`; semua nilai lain (termasuk kosong dan spasi)
berhasil menjadi `asc`. Penulisan awal memakai `== 'asc' ? 'asc' : 'desc'` yang
menyusun `sort_order=` (kosong dari FE) menjadi `desc` — berlawanan dengan default
yang didokumentasikan. Perbandingan sekarang `=== 'desc' ? 'desc' : 'asc'`.

`per_page` dikunci `min(max($n, 1), 100)` mengikuti `ActivityController`. Mayoritas
controller lain di repo ini meneruskan `per_page` mentah ke `paginate()`, sehingga
`per_page=999999` menarik seluruh tabel sekaligus.

`show` dan `update` memuat ulang baris lewat `reloadWithCoiData()` (scope
`joinedToCoi()` + `withCoiData()`) — model hasil route-model binding belum punya
`tag_number` / `overdue_date` / `sisa_hari`, sehingga tanpa itu bucket masa berlaku
akan selalu salah terbaca `expired`. Sengaja **tanpa** filter PSV/TSV supaya
`show` tetap bisa menjangkau baris yang tag number-nya sudah berubah.

## Routes — `routes/api.php`

Di dalam `middleware(['auth:api'])`, tepat setelah blok Monitoring Equipment.
Tanpa `role:1,99` — `POST /sync` cukup login, sesuai keputusan.

```php
// Catatan: route literal HARUS ditulis sebelum route berparameter {monitoring_psv}
// agar 'dashboard'/'export' tidak tertangkap sebagai id.
Route::get('/monitoring_psv/dashboard', [MonitoringPsvController::class, 'dashboard']);
Route::get('/monitoring_psv/export', [MonitoringPsvController::class, 'export']);
Route::post('/monitoring_psv/sync', [MonitoringPsvController::class, 'sync']);
Route::get('/monitoring_psv', [MonitoringPsvController::class, 'index']);
Route::get('/monitoring_psv/{monitoring_psv}', [MonitoringPsvController::class, 'show']);
Route::put('/monitoring_psv/{monitoring_psv}', [MonitoringPsvController::class, 'update']);
```

Route eksplisit, **bukan** `apiResource`:

- Tidak ada `store` — baris hanya lahir dari sync.
- Tidak ada `destroy` — menghapus baris hanya menghapus anotasi, yang sudah bisa
  dilakukan dengan mengirim `null`; barisnya akan muncul lagi saat sync.

Nama parameter harus `{monitoring_psv}` (bukan `{id}`) agar route-model binding
Laravel bekerja: `ImplicitRouteBinding` mencocokkan nama variabel dengan
`Str::snake()` dari nama parameter tersebut.

## Registrasi Log Activity + Observer

- `config/log-activity.php`:
  - `features`: `'/monitoring-psv' => 'Monitoring PSV'`
  - `aliases`: `'MonitoringPsv' => 'Monitoring PSV'`
- `app/Observers/GlobalActivityObserver.php`:
  `labelFields['MonitoringPsv'] = 'record_label'` — tabel tidak punya kolom
  `tag_number`, jadi memakai accessor.

> Sync pertama akan menghasilkan **135 entri** `create` di `log_activities`
> dengan `record_label` berupa tag number. Ini memangundesired secara volume,
> tapi disengaja: untuk sistem kepatuhan, jejak audit lebih penting daripada
> kepadatan log, dan cara yang benar untuk memangkas adalah 조치 global pada
> observer — bukan penambahan pengecualian per-modul.

## Di Luar Scope v1

- Dashboard: toplist tag paling kritis, breakdown per unit.
- Riwayat bulanan / snapshot `BusinessPeriod` (butuh `monitoring_psv_logs`).
- Bulk update kolom manual & import Excel.
- `DELETE` baris monitoring.
- Tag PSV/TSV tanpa COI (40 baris).

## Dokumentasi yang di-update

- `docs/idms-documentation/00-index.md` — tambah baris modul.
- `docs/idms-documentation/15-api-reference.md` — section + endpoint table.
- `docs/idms-documentation/16-database-schema.md` — tabel `monitoring_psv`.
- `docs/idms-documentation/02-panduan-lokasi-file.md` — mapping file & migration.
- `docs/log-activity-modules.md` — entri fitur + alias.
- `docs/api/monitoring_psv.postman_collection.json` — Postman collection.

## Verifikasi

- `php artisan migrate` → tabel `monitoring_psv` terbentuk.
- `php artisan route:list --path=monitoring_psv` → 6 route.
- Tinker:
  - `syncAll()` → `scanned=135, created=135, skipped=0`; panggil ulang →
    `created=0` (idempotent).
  - `index` → `total=135`.
  - `dashboard` → `safe+warning+expired = total`, `psv+tsv = total`,
    `redundant+not+unfilled = total`, `sum(by_kategori.total) = total`.
  - `PsvTagNumber::matches()` — `1-PSV-177/00` true, `1-C-01/00` false, `null` false.
  - `MasaBerlakuBucket::forDays()` — `null/-5/0 → expired`, `1/270 → warning`,
    `271/400 → safe`.
  - `validated()` atas payload `tag_number` + `coi_id` → keduanya tertuang.
  - Export menghasilkan `.xlsx` yang valid.
