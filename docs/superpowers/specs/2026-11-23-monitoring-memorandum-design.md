# Design: Dashboard Monitoring Memorandum

**Tanggal:** 2026-11-23
**Status:** Draft untuk review

## Latar Belakang

IDMS membutuhkan modul baru "Dashboard Monitoring Memorandum" untuk memantau
progres penanganan issue operasional mulai dari nota dinas, notifikasi, work
order, purchase order, hingga status akhir (`open`/`close`).

Modul memakai pola **Monitoring Pipeline** karena paling dekat: tabel anak
dengan input manual, CRUD + export Excel via Maatwebsite, filter + search +
sort whitelist, dan activity log otomatis. Yang **tidak** diambil: import Excel,
snapshot `BusinessPeriod`, registrasi `features`, dan unit test.

Fitur utama:

1. CRUD penuh + export Excel yang mengikuti filter aktif.
2. Filter & global search.
3. Dashboard dengan dua pengolahan: **bobot** per tahun dan **backlog** per
   bucket 30 hari.

## Keputusan Saat Brainstorming

| Topik | Keputusan | Alasan |
|---|---|---|
| Nama tabel | `monitoring_memorandum` (singular) | Konsisten dengan `monitoring_pipeline`, `monitoring_psv` |
| Field wajib saat create | Hanya `tanggal_issue`, `issue_operasional` | Keputusan user (revisi terakhir) |
| Multiple tag number | Satu kolom `tag_number_ids` berisi daftar ID dipisah koma `43,44,93` | Keputusan user; tanpa tabel pivot |
| Penanda All Tag Number | Nilai literal `all` pada `tag_number_ids` | Keputusan user; tanpa kolom boolean baru |
| `search=all` | Tidak boleh menemukan baris bertanda `all` lewat sentinel | Keputusan user |
| Filter tag `all` | `?tag_number_ids=all` menemukan baris bertanda `all` | Keputusan user |
| Filter tag numerik | `?tag_number_ids=43` hanya mencocokkan ID di string, **tidak** mencocokkan baris `all` | Turunan dari keputusan di atas |
| Validasi `tanggal_issue` | `before_or_equal:today` | Keputusan user; `backlog_days` tidak bisa negatif |
| Bobot | Biner "terisi = bobot penuh", skor 0–100, dirata-rata per tahun | Keputusan user |
| Bucket backlog | `1–30`, `31–60`, `61–90`, `91+` | Keputusan user |
| Endpoint dashboard | Satu endpoint dengan dua blok, global (tanpa filter) | Keputusan user |
| Import / unit test | Tidak | Tidak diminta |

## Skema — Migration `2026_11_23_000001_create_monitoring_memorandum_table`

| Field | Tipe | Constraint | Catatan |
|---|---|---|---|
| `id` | bigint unsigned | PK auto-increment | |
| `tanggal_issue` | date | NOT NULL | wajib, ≤ hari ini |
| `issue_operasional` | string(255) | NOT NULL | wajib |
| `tag_number_ids` | string(255) | NULLABLE | `43,44,93` atau `all` |
| `nota_dinas` | foreignId | NULLABLE → `historical_memorandum.id`, `nullOnDelete` | FK, bukan teks |
| `no_notif` | unsignedBigInteger | NULLABLE | |
| `status_notif` | string(20) | NULLABLE, `in:OSNO,NOPR` | |
| `no_wo` | unsignedBigInteger | NULLABLE | |
| `status_wo` | string(2) | NULLABLE, `in:50,60` | |
| `no_po` | string(150) | NULLABLE | |
| `status` | string(10) | NULLABLE, `in:open,close` | |
| `keterangan` | text | NULLABLE | |
| `created_at` / `updated_at` | timestamp | | |

Index tambahan: `tanggal_issue`, `status`.

`constrained('historical_memorandum')` **wajib ditulis eksplisit** karena nama
kolomnya `nota_dinas` tidak sama dengan nama tabel; Laravel jika dibiarkan akan
mencari tabel `nota_dinas`. `nullOnDelete()` dipilih (bukan `cascade`) supaya
menghapus memorandum tidak ikut menghapus baris monitoring — kolomnya cukup
menjadi `null`.

## Aturan `tag_number_ids`

Dikelola di `App\Support\TagNumberIds` sebagai sumber tunggal aturan:

| Member | Fungsi |
|---|---|
| `MARKER = 'all'` | Penanda All Tag Number |
| `normalize(?string): ?string` | trim, buang spasi di sekitar koma, hapus duplikat, pertahankan urutan; `''` → `null` |
| `isAll(?string): bool` | `trim($value) === 'all'` |
| `parse(?string): array` | `[]` untuk null/`all`; Otherwise array<int> sesuai urutan |
| `resolve(?string): array` | `[{id, tag_number}, ...]` dari `tag_numbers`, urutan mengikuti string |
| `display(?string): string` | `'All Tag Number'` atau tag number tergabung `, ` |
| `applyFilter(Builder, string $column, ?string $value)` | filter query (lihat bawah) |
| `tagSearchSql(string $column): string` | ekspresi `EXISTS` untuk cari teks tag number |

Normalisasi & validasi:

- Format sah: `all` atau `\d+(,\d+)*`. `43,all` ditolak 422.
- Setiap ID harus ada di `tag_numbers`; ID tidak ditemukan → 422 (bukan
  dibuang diam-diam).
- String kosong atau hanya spasi → `null`.

Perilaku filter:

| Query | Hasil |
|---|---|
| `?tag_number_ids=43` | baris yang `FIND_IN_SET(43, tag_number_ids) > 0`; baris `all` **tidak** ikut |
| `?tag_number_ids=43,44` | baris yang memuat **salah satu** dari 43 atau 44 (ANY, bukan ALL) |
| `?tag_number_ids=all` | hanya baris dengan `tag_number_ids = 'all'` |
| `?search=all` | tidak mencocokkan sentinel; kolom `tag_number_ids` tidak termasuk global search |
| `?search=P434` | tetap bisa mencocokkan tag number lewat subquery `EXISTS ... FIND_IN_SET ... LIKE` |

Subquery pencarian tag tidak perlu syarat `tag_number_ids <> 'all'` karena
`FIND_IN_SET(id, 'all')` selalu `0`, sehingga baris `all` otomatis tidak cocok.

`resolve()` memakai cache statis per request (`id => tag_number`) agar list
berisi banyak baris tidak menimbulkan N+1 query.

## Field & Validasi

`StoreMonitoringMemorandumRequest` dan `UpdateMonitoringMemorandumRequest`
masing-masing `extends BaseRequest` (422 JSON seragam).

`prepareForValidation()` hanya menyentuh key yang benar-benar ada di payload
(`array_key_exists`) supaya field yang tidak dikirim tidak berubah menjadi
`null` dan ikut ter-update. String kosong pada field nullable dinormalisasi
jadi `null`.

| Field | Rule create | Rule update |
|---|---|---|
| `tanggal_issue` | `required\|date\|before_or_equal:today` | `sometimes\|required\|date\|before_or_equal:today` |
| `issue_operasional` | `required\|string\|max:255` | `sometimes\|required\|string\|max:255` |
| `tag_number_ids` | `nullable\|string\|max:255\|regex` + cek exists per ID | `sometimes\|nullable\|…` sama |
| `nota_dinas` | `nullable\|integer\|exists:historical_memorandum,id` | `sometimes\|nullable\|…` sama |
| `no_notif` | `nullable\|integer\|min:0` | `sometimes\|nullable\|…` sama |
| `status_notif` | `nullable\|string\|in:OSNO,NOPR` | `sometimes\|nullable\|…` sama |
| `no_wo` | `nullable\|integer\|min:0` | `sometimes\|nullable\|…` sama |
| `status_wo` | `nullable\|string\|in:50,60` | `sometimes\|nullable\|…` sama |
| `no_po` | `nullable\|string\|max:150` | `sometimes\|nullable\|…` sama |
| `status` | `nullable\|string\|in:open,close` | `sometimes\|nullable\|…` sama |
| `keterangan` | `nullable\|string\|max:5000` | `sometimes\|nullable\|…` sama |

Konsekuensi `sometimes`: `PUT /monitoring_memorandum/1` dengan body
`{"nota_dinas": 18}` hanya mengubah kolom `nota_dinas`. Untuk mengosongkan,
kirim `{"nota_dinas": null}`. Response tetap berisi representasi lengkap.

## `backlog_days` (computed, tidak disimpan)

Satu helper `App\Support\BacklogDays`:

- `daysSql(string $column)` → `DATEDIFF(CURDATE(), {$column})`.
- `forDate($date)` → `Carbon::parse($tanggalIssue)->diffInDays(today(), false)`
  (positif untuk tanggal lampau). Karena `tanggal_issue` divalidasi ≤ hari ini,
  nilai minimum `0`.
- `bucket(int $days)` → `min(intdiv(max($days - 1, 0), 30) + 1, 4)`.
  `0..30 → 1`, `31..60 → 2`, `61..90 → 3`, `>= 91 → 4`. Empat bucket tetap,
  sesuai demo yang disetujui; tidak ada bucket dinamis yang bertumpang tindih.
- `label(int $index)` → `1` = `1–30`, `2` = `31–60`, `3` = `61–90`,
  `4` = `91+` (open-ended). `hari_min` berturut-turut `1/31/61/91`,
  `hari_max` `30/60/90/null`.

Accessor `getBacklogDaysAttribute()` memakai nilai alias SQL `backlog_days`
apabila query menyertakannya, dan fallback ke `forDate()` bila tidak ada. Ini
wajib: accessor yang selalu menghitung dari `tanggal_issue` akan menimpa alias
SQL dan menghasilkan `0` pada query dashboard yang tidak memilih kolom
`tanggal_issue`.

## Bobot

Sumber tunggal: `App\Support\MonitoringMemorandumBobot`.

Bobot per baris (biner, 0–100):

| Komponen | Bobot | Syarat |
|---|---|---|
| `nota_dinas` | 10 | tidak null |
| `no_notif` | 10 | tidak null |
| `status_notif` | 20 | tidak null |
| `no_wo` | 10 | tidak null |
| `status_wo` | 20 | tidak null |
| `no_po` | 10 | tidak null dan `TRIM(...) <> ''` |
| `status` | 20 | `= 'close'` |

Jumlah bobot = 100. `scoreSql()` menghasilkan ekspresi `CASE` untuk dipakai
di dalam `AVG(...)` pada query dashboard; `score(array $row)` tersedia untuk
verifikasi manual via tinker. Tidak ada kolom `bobot` di tabel maupun di
resource list — bobot hanya hasil olahan dashboard.

## Dashboard — `GET /api/monitoring_memorandum/dashboard`

`App\Services\MonitoringMemorandumDashboardService::getDashboard()` mengembalikan:

```
{
  "success": true,
  "message": "Dashboard Monitoring Memorandum.",
  "data": {
    "bobot": [
      { "tahun": 2025, "rata_rata_bobot": 90, "total_data": 2 },
      { "tahun": 2026, "rata_rata_bobot": 37.5, "total_data": 4 }
    ],
    "backlog": [
      { "bucket": "1–30", "hari_min": 1, "hari_max": 30, "total": 3 },
      { "bucket": "31–60", "hari_min": 31, "hari_max": 60, "total": 1 },
      { "bucket": "61–90", "hari_min": 61, "hari_max": 90, "total": 0 },
      { "bucket": "91+", "hari_min": 91, "hari_max": null, "total": 2 }
    ]
  }
}
```

- `bobot`: satu query `YEAR(tanggal_issue) as tahun`,
  `AVG(scoreSql()) as rata_rata_bobot`, `COUNT(*) as total_data`,
  `GROUP BY tahun ORDER BY tahun ASC`. Hasil dibulatkan 2 desimal.
- `backlog`: satu query mengambil `backlog_days` per baris, dikelompokkan di PHP
  per index bucket. Keempat bucket (`1–30`, `31–60`, `61–90`, `91+`) selalu
  ditampilkan, termasuk yang `total: 0`, supaya grafik konsisten antar periode.
- Dashboard **global**, tidak menerima filter/search, mengikuti konvensi
  dashboard PSV/Equipment.

## Model — `app/Models/MonitoringMemorandum.php`

- `extends BaseModel`, `use HasFactory`.
- `$table = 'monitoring_memorandum'` **wajib eksplisit** (Laravel akan
  mempluralisasi menjadi `monitoring_memorandums`).
- `$fillable`: 11 field di atas.
- `$casts`: `tanggal_issue => 'date:Y-m-d'`.
- Konstanta: `STATUS_NOTIF_OPTIONS`, `STATUS_WO_OPTIONS`, `STATUS_OPTIONS`,
  `TAG_ALL_MARKER` — dipakai bersama request, resource, dan dashboard.
- Relasi `historicalMemorandum()` → `belongsTo(HistoricalMemorandum::class, 'nota_dinas')`.
- Accessor `getRecordLabelAttribute()` → `issue_operasional` (label activity log).
- Accessor `getIsAllTagNumberAttribute()` dan `getBacklogDaysAttribute()`.

## HTTP Layer

### `MonitoringMemorandumResource`

`id`, `tanggal_issue`, `issue_operasional`, `tag_number_ids`,
`is_all_tag_number`, `tag_numbers` (array `{id, tag_number}`),
`nota_dinas`, `no_dokumen` (via `whenLoaded('historicalMemorandum')`),
`no_notif`, `status_notif`, `no_wo`, `status_wo`, `no_po`, `status`,
`backlog_days`, `keterangan`, `created_at`, `updated_at`.

Sama persis untuk `index`, `show`, `store`, `update`.

### `MonitoringMemorandumExport`

`FromQuery` + `WithHeadings` + `WithMapping` + `ShouldAutoSize` + `WithTitle`.
Query dibangun controller (`baseQuery()`) yang **sama persis** dengan `index()`,
sehingga export selalu mengikuti filter/search aktif.

13 kolom: `No`, `Tanggal Issue`, `Issue Operasional`, `Tag Number`, `Nota Dinas`,
`No Notif`, `Status Notif`, `No WO`, `Status WO`, `No PO`, `Status`,
`Backlog Days`, `Keterangan`. Kolom Tag Number memakai
`TagNumberIds::display()` → `All Tag Number` untuk sentinel. Null ditampilkan
kosong; tanggal `Y-m-d`. Nama file `Monitoring_Memorandum_YYYYMMDD_HHIS.xlsx`.

### `MonitoringMemorandumController`

| Method | Endpoint | Isi |
|---|---|---|
| `index` | `GET /monitoring_memorandum` | list + search + filter + sort + paginate |
| `store` | `POST /monitoring_memorandum` | create, `DB::transaction` |
| `show` | `GET /monitoring_memorandum/{monitoring_memorandum}` | 1 baris + relasi |
| `update` | `PUT /monitoring_memorandum/{monitoring_memorandum}` | partial update |
| `destroy` | `DELETE /monitoring_memorandum/{monitoring_memorandum}` | delete |
| `export` | `GET /monitoring_memorandum/export` | Excel + activity log |
| `dashboard` | `GET /monitoring_memorandum/dashboard` | bobot + backlog |

`baseQuery(Request $request): Builder` dipakai bersama `index()` dan `export()`:

- `select('monitoring_memorandum.*')` +
  `selectRaw('DATEDIFF(CURDATE(), monitoring_memorandum.tanggal_issue) as backlog_days')`
  + `with('historicalMemorandum')` + `leftJoin('historical_memorandum', ...)`.
- Search: `issue_operasional`, `historical_memorandum.no_dokumen`, `no_notif`,
  `no_wo`, `no_po`, `keterangan`, dan subquery tag number. `tag_number_ids`
  **sengaja tidak** ikut di-search.
- Filter exact: `status`, `status_notif`, `status_wo`, `nota_dinas`.
- Filter tag: `TagNumberIds::applyFilter()`.
- Filter rentang: `tanggal_issue_from/_to`, `backlog_days_min/_max`
  (memakai `DATEDIFF` langsung, bukan alias `HAVING`).
- Sort whitelist: `id`, `tanggal_issue`, `issue_operasional`, `tag_number_ids`,
  `nota_dinas`, `no_notif`, `status_notif`, `no_wo`, `status_wo`, `no_po`,
  `status`, `backlog_days`, `created_at`, `updated_at`. Default `tanggal_issue`
  desc. Nilai di luar whitelist jatuh ke default, tidak pernah di-interpolasi.
- `per_page` dikunci `min(max($n, 1), 100)`, default 10.

Response CRUD mengikuti konvensi pipeline:
`['success', 'message', 'data']` (201 untuk store), error 500 memuat `error`
hanya saat `config('app.debug')`.

## Routes — `routes/api.php`

Di dalam `middleware(['auth:api'])`. Route literal ditulis **sebelum**
`apiResource` agar `dashboard`/`export` tidak tertangkap sebagai `{id}`:

```php
Route::get('/monitoring_memorandum/dashboard', [MonitoringMemorandumController::class, 'dashboard']);
Route::get('/monitoring_memorandum/export', [MonitoringMemorandumController::class, 'export']);
Route::apiResource('monitoring_memorandum', MonitoringMemorandumController::class);
```

## Activity Log

- CRUD otomatis oleh `GlobalActivityObserver`; tambahkan
  `labelFields['MonitoringMemorandum'] = 'record_label'`.
- Export: `activity()->log('export', 'MonitoringMemorandum')`.
- `config/log-activity.php` **tidak** diubah (keputusan: mengikuti modul pipeline).

## Di Luar Scope v1

- Import Excel / template.
- Riwayat perubahan, snapshot `BusinessPeriod`, tabel log.
- Upload lampiran.
- Registrasi `features`, unit test, Postman collection.
- Penyimpanan bobot per baris sebagai kolom.

## Verifikasi

- `php artisan migrate` → tabel `monitoring_memorandum` terbentuk dengan FK ke
  `historical_memorandum`.
- `php artisan route:list --path=monitoring_memorandum` → 7 route (5 dari
  `apiResource` + `dashboard` + `export`).
- `TagNumberIds::normalize(' 43, 44,43 ')` → `43,44`; `parse('all')` → `[]`;
  `isAll('all')` → `true`.
- `?tag_number_ids=43` menampilkan baris `43,44` dan `412,43,93`, tidak
  menampilkan baris `all`; `?tag_number_ids=all` hanya menampilkan baris `all`;
  `?search=all` mengembalikan 0 baris (selama tidak ada teks "all" di field lain).
- `PUT /monitoring_memorandum/1` dengan `{"nota_dinas": 18}` hanya mengubah
  `nota_dinas`; `{"nota_dinas": null}` mengosongkannya.
- `MonitoringMemorandumBobot::score()`:
  semua terisi → 100; hanya `no_notif` + `status_notif` → 30; tidak ada → 0.
  Dashboard contoh: 2025 → 90, 2026 → 37.5.
- `BacklogDays::bucket(0/30/31/60/61/90/91/511)` → `1/1/2/2/3/3/4/4`;
  bucket tampil `1–30`, `31–60`, `61–90`, `91+`.
- Export `.xlsx` valid dengan 13 heading; isi identik dengan filter aktif.
