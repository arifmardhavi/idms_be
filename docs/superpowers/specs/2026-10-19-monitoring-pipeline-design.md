# Design: Tambah Modul Monitoring Pipeline

**Tanggal:** 2026-10-19
**Status:** Draft untuk review

## Latar Belakang

IDMS membutuhkan modul "Monitoring Pipeline" untuk menyimpan data inspeksi UT
(Ultrasonic Testing) piping: data teknis pipa (NPS, OD, SCH, tebal), data
desain (press, temp, material), hasil UT (actual thickness, corrosion rate,
remaining life), dan status perawatan. Sumber data utama adalah workbook
`Audit Data Asset Piping (Final 2) + Dashboard.xlsx`, sheet **Database UT
Piping** — berisi 516 baris piping (header di baris 2, data baris 3–524,
tanpa duplikat tag).

Fitur yang diminta: **CRUD, import Excel, export Excel, filter, search**.
Tanpa dashboard, tanpa riwayat/snapshot periode, tanpa registrasi `features`,
tanpa unit test (keputusan eksplisit saat brainstorming).

Modul memakai pola **Monitoring Equipment** karena paling dekat: tabel
`tag_number_id` FK unik, import/export Excel via Maatwebsite, filter + search +
sort whitelist. Yang tidak diambil: log periodik `BusinessPeriod`, dashboard,
dan role-gate tulis — semua endpoint cukup `auth:api`.

## Keputusan Saat Brainstorming

| Topik | Keputusan | Alasan |
|---|---|---|
| Pencocokan tag saat import | **Persis** (`tag_numbers.tag_number` = nilai file) | Pilihan user; tag tidak match → skip + laporan error |
| Tag tidak ditemukan | Lewat + catat di `summary.errors[]` | Konvensi MonitoringEquipment; aman untuk master data |
| Import ulang | **Upsert** per `tag_number_id` | `tag_number_id` unique; re-import = refresh data |
| Akses tulis | Semua user login (`auth:api` saja) | Pilihan user; MonitoringEquipment juga begitu |
| Template download | Ya | Pilihan user |
| Export ikut filter aktif | Ya | Pilihan user |
| Registrasi `features` / unit test | Tidak | Pilihan user |

## Analisis Sumber Data (workbook, sheet Database UT Piping)

- 38 kolom di file; hanya **30 field** yang diminta, kolom ekstra di-skip:
  `NO`, `STATUS LINE`, `Design Press Pa (N/m2)`, `S Pa (N/m2)`,
  `NEXT PLAN INSPECTION BASE ON RBI`, `Class Service` (2 kolom duplikat),
  `KENDALA LAPANGAN` (tidak diminta — kolom ini di file ada tapi tidak masuk
  daftar field), kolom catatan lepas di kanan.
- **Kolom formula**: TML drawing, S, E, W, Y, min req thk, half nom thk,
  actual thk, corrosion rate, remaining life, alert structural thk, next plan
  API, used plan, pipe condition, maintenance status. File menyimpan *cached
  value* per sel formula. `Excel::toArray` (Maatwebsite) mengembalikan
  **string formula**, bukan hasil — karenanya import service membaca file
  langsung dengan PhpSpreadsheet `readDataOnly=true` dan mengambil
  `getOldCalculatedValue()` untuk sel formula.
- Rumus mengandung `TODAY()` (maintenance status, used plan inspection)
  bernilai per terakhir file di-save di Excel — snapshot, bisa di-reimport.
- **Data kotor** yang dinormalisasi (lihat § Import — Normalisasi):
  insulation `0` (7 baris) & `33.4` (5 baris, data bergeser), API class `-`
  (14), `#N/A` (5 baris di beberapa kolom), `#VALUE!`/`rumus dasar` di min req
  thk, teks `USE ASME B31.3` di kolom alert structural thk (154 baris),
  `N/A` di operating press (3), NPS pecahan `1 1/2`/`3/4` (6).
- Tag di file: `12-5-P434-A1-H-ET`; di DB lokal: `12-5-P434-A1-H-ET/00`
  (suffix `/00`). Match persis = **0/516** di DB lokal saat ini. Ini konsekuensi
  keputusan match-persis; sebelum import produksi, pastikan format tag di
  `tag_numbers` sudah sama dengan file.

## Kolom & Skema (Migration `create_monitoring_pipeline_table`)

| Field | Tipe | Constraint | Catatan |
|---|---|---|---|
| `id` | bigint unsigned | PK auto-increment | |
| `tag_number_id` | foreignId | → `tag_numbers`, cascade delete, **unique** | 1:1 per tag |
| `tml_drawing_number` | string(150) | NULLABLE | |
| `api_service_class` | int | NULLABLE | |
| `insulation` | string(10) | NULLABLE | simpan `Yes` / `No` |
| `grade_material` | string(150) | NULLABLE | |
| `nps` | double | NULLABLE | |
| `od` | double | NULLABLE | |
| `sch` | string(50) | NULLABLE | |
| `t_nom` | double | NULLABLE | |
| `operating_press` | double | NULLABLE | |
| `design_press` | double | NULLABLE | |
| `design_temp` | double | NULLABLE | |
| `s` | double | NULLABLE | |
| `e` | double | NULLABLE | |
| `w` | double | NULLABLE | |
| `y` | double | NULLABLE | |
| `min_req_thk` | double | NULLABLE | |
| `half_nom_thk` | double | NULLABLE | |
| `tanggal_pasang_line` | date | NULLABLE | |
| `actual_thk` | double | NULLABLE | |
| `tanggal_ut_terakhir` | date | NULLABLE | |
| `corrosion_rate` | double | NULLABLE | |
| `remaining_life` | double | NULLABLE | |
| `alert_structural_thickness` | double | NULLABLE | teks `USE ASME B31.3` → null (info tetap ada di `pipe_condition`) |
| `next_plan_inspection_api` | date | NULLABLE | |
| `used_plan_inspection` | date | NULLABLE | |
| `pipe_condition` | string | NULLABLE | |
| `estimated_end_of_life` | date | NULLABLE | |
| `maintenance_status` | string | NULLABLE | |
| `rencana_tindak_lanjut` | string | NULLABLE | |
| `created_at` / `updated_at` | timestamp | | |

Index: `tag_number_id` (unique sudah menutup search by tag).

## Model — `app/Models/MonitoringPipeline.php`

- `extends BaseModel`, `use HasFactory`
- `protected $table = 'monitoring_pipeline'` — **wajib eksplisit** (Laravel akan
  mengpluralisasi jadi `monitoring_pipelines` kalau tidak ditulis)
- `$fillable`: 30 field di atas (termasuk `tag_number_id` — MonitoringEquipment
  juga memasukkannya; validasi FormRequest yang menjaga integritas)
- `$casts`: semua kolom double → `float`, kolom date → `date:Y-m-d`
- Relasi `tagNumber()` → `belongsTo(Tag_number::class, 'tag_number_id')`
- Accessor `getRecordLabelAttribute()` → tag number, dipakai
  `GlobalActivityObserver` sebagai label activity log

## Import — `app/Services/MonitoringPipelineImportService.php`

Pola service class seperti `MonitoringEquipmentImportService`, dengan satu
perbedaan mendasar: pembacaan file.

### Pembacaan file

1. Baca via `PhpOffice\PhpSpreadsheet\IOFactory::createForFile($file)` +
   `setReadDataOnly(true)` — `createForFile` memilih reader yang tepat untuk
   `.xlsx` maupun `.xls` (validasi request mengizinkan keduanya).
2. Sheet: `'Database UT Piping'` jika ada di workbook, kalau tidak → sheet
   pertama (supaya file template juga bisa di-import).
3. Cari baris header: baris pertama yang sel B-nya mengandung `TAG NUMBER`
   (file asli header di baris 2; template di baris 1). Data mulai di baris
   berikutnya sampai baris terakhir non-kosong.
4. Sel formula (`isFormula()`) dibaca dari cached value
   (`getOldCalculatedValue()`); sel biasa dari `getValue()`.
5. Kolom dipetakan berdasarkan **header file** (di-slug-kan), bukan posisi
   tetap — tahan terhadap perubahan urutan kolom. Hanya 30 kolom target yang
   diproses; header lain diabaikan.

### Mapping kolom file → field

| Field | Header file (slug setelah trim+newline→spasi) |
|---|---|
| `tag_number` | `tag_number` |
| `tml_drawing_number` | `tml_drawing_number` |
| `api_service_class` | `api_570_service_class` |
| `insulation` | `insulation_or_non_insulation` |
| `grade_material` | `grade_material` |
| `nps` / `od` / `sch` / `t_nom` | `nps` / `od_mm` / `sch` / `t_nom_mm` |
| `operating_press` | `operating_press_kg_cm2` |
| `design_press` | `design_press_kg_cm2` |
| `design_temp` | `design_temp_kg_cm2` |
| `s` / `e` / `w` / `y` | `s_mpa` / `e` / `w` / `y` |
| `min_req_thk` | `min_req_thk_mm` |
| `half_nom_thk` | `50_nom_thk_mm` → slug `50_nom_thk_mm` |
| `tanggal_pasang_line` | `tanggal_pasang_line` |
| `actual_thk` | `actual_thk_mm` |
| `tanggal_ut_terakhir` | `tanggal_ut_terakhir` |
| `corrosion_rate` | `corrosion_rate` |
| `remaining_life` | `remaining_life_asme_31_3` |
| `alert_structural_thickness` | `alert_structural_thickness_api_574` |
| `next_plan_inspection_api` | `next_plan_inspection_api_570` |
| `used_plan_inspection` | `used_plan_inspection` |
| `pipe_condition` | `pipe_condition_api_574` |
| `estimated_end_of_life` | `estimated_end_of_life_eol` |
| `maintenance_status` | `maintenance_status` |
| `rencana_tindak_lanjut` | `rencana_tindak_lanjut` |

Slugifikasi header memakai regex: trim → replace `\s+` jadi spasi → `str()->slug('_')`.

### Normalisasi per kolom

| Kolom | Aturan |
|---|---|
| `insulation` | `Yes`/`No` case-insensitive → `Yes`/`No`; selainnya (termasuk `0`, `33.4`) → **null + warning** |
| `api_service_class` | numerik → int; `-`/kosong → null |
| `nps` | numerik → float; pecahan (`1 1/2` → 1.5, `3/4` → 0.75) → float; tak terbaca → null + warning |
| `sch` | numerik → string tanpa `.0` (`40.0` → `"40"`); teks → string |
| semua double | numerik → float; error Excel (`#N/A`, `#VALUE!`, `N/A`, `rumus dasar`, `USE ASME B31.3`) → **null** |
| semua date | serial numerik (1..2958465) → `ExcelDate::excelToDateTimeObject()->format('Y-m-d')`; string ter-parse Carbon → `Y-m-d`; error Excel → null |
| string biasa | string apa adanya; error Excel (`#N/A`) → null; string kosong → null |

Setiap nilai yang di-null-kan karena tidak valid (bukan kosong) dicatat ke
`summary.warnings[]` berupa `{row, column, value, message}` — jumlah total +
maks 100 entri pertama agar response tidak meledak.

### Alur proses

1. Validasi awal: file kosong (≤1 baris) → `success=false`.
2. Kumpulkan semua baris ber-tag; baris kosong → `skipped++`.
3. Resolusi tag: `Tag_number::whereIn('tag_number', $tags)` → map by
   `tag_number` (**match persis**, tanpa normalisasi suffix). Tidak ketemu →
   `failed++` + `errors[] {row, tag_number, message: 'Tag Number tidak
   ditemukan.'}`.
4. Cek existing `monitoring_pipeline` by `tag_number_id` → split insert vs
   update.
5. Satu `DB::transaction`: bulk `insert()` untuk yang baru, `update()` per id
   untuk yang existing. **Tanpa events** (`Model::withoutEvents`) — observer
   tidak mencatat ratusan baris import sebagai activity create/update;
   import dicatat sekali via `activity()->log('import', 'MonitoringPipeline', ...)`.
6. Return `{success, message, summary: {total, success, failed, skipped,
   errors[], warnings[]}}`.

## Export & Template

### `app/Exports/MonitoringPipelineExport.php`

`FromArray` + `WithHeadings` + `WithMapping` + `ShouldAutoSize` +
` WithEvents` (freeze baris header + bold header + auto column width).

- Data dari **query builder yang sama dengan `index()`** (method privat
  `baseQuery()` di controller dipakai bersama) → export selalu identik dengan
  hasil filter/search di layar.
- 31 kolom: `No` + 30 field. Nilai null tampil kosong; tanggal format
  `Y-m-d`.
- Nama file: `Monitoring_Pipeline_YYYYMMDD_HHIS.xlsx`.

### `app/Exports/MonitoringPipelineTemplateExport.php`

`FromArray` + `WithHeadings`: header 30 kolom (label human-readable, urutan
sama dengan import) + 1 baris contoh data. Nama file:
`Template_Monitoring_Pipeline.xlsx`.

## HTTP Layer

### `app/Http/Resources/MonitoringPipelineResource.php`

Kolom: `id`, `tag_number_id`, blok tag number (`tag_number`, `description`,
`unit_id`, `type_id` via `whenLoaded('tagNumber')` — untuk show), 29 field
data, `created_at`, `updated_at`.

### FormRequests (semua `extends BaseRequest`, 422 JSON seragam)

`StoreMonitoringPipelineRequest`:

| Field | Rule |
|---|---|
| `tag_number_id` | `required\|integer\|exists:tag_numbers,id\|unique:monitoring_pipeline,tag_number_id` |
| `tml_drawing_number`, `grade_material` | `nullable\|string\|max:150` |
| `api_service_class` | `nullable\|integer` |
| `insulation` | `nullable\|string\|in:Yes,No` |
| `nps`, `od`, `t_nom`, `operating_press`, `design_press`, `design_temp`, `s`, `e`, `w`, `y`, `min_req_thk`, `half_nom_thk`, `actual_thk`, `corrosion_rate`, `remaining_life`, `alert_structural_thickness` | `nullable\|numeric` |
| `sch` | `nullable\|string\|max:50` |
| `tanggal_pasang_line`, `tanggal_ut_terakhir`, `next_plan_inspection_api`, `used_plan_inspection`, `estimated_end_of_life` | `nullable\|date` |
| `pipe_condition`, `maintenance_status`, `rencana_tindak_lanjut` | `nullable\|string` |

`UpdateMonitoringPipelineRequest`: sama, tapi `tag_number_id` =
`sometimes|required|integer|exists:tag_numbers,id|unique:monitoring_pipeline,tag_number_id,{id}`.

`ImportMonitoringPipelineRequest`: `file => required|file|mimes:xlsx,xls|max:10240`.

### `app/Http/Controllers/MonitoringPipelineController.php`

| Method | Endpoint | Isi |
|---|---|---|
| `index` | `GET /monitoring_pipeline` | list + search + filter + sort + paginate |
| `store` | `POST /monitoring_pipeline` | create, `DB::transaction` |
| `show` | `GET /monitoring_pipeline/{monitoring_pipeline}` | 1 baris + `with('tagNumber')` |
| `update` | `PUT /monitoring_pipeline/{monitoring_pipeline}` | update, `DB::transaction` |
| `destroy` | `DELETE /monitoring_pipeline/{monitoring_pipeline}` | delete |
| `downloadTemplate` | `GET /monitoring_pipeline/template` | download template |
| `import` | `POST /monitoring_pipeline/import` | import + activity log |
| `export` | `GET /monitoring_pipeline/export` | export (ikut filter) + activity log |

`baseQuery()`: `leftJoin('tag_numbers')` + `select('monitoring_pipeline.*')` +
`with('tagNumber')` — dipakai `index` dan `export`.

Query param `index`:

| Param | Contoh | Keterangan |
|---|---|---|
| `search` | `P434` | LIKE: `tag_numbers.tag_number`, `tml_drawing_number`, `grade_material`, `sch`, `pipe_condition`, `maintenance_status`, `rencana_tindak_lanjut` |
| `insulation` | `No` | LIKE `%value%` (case-insensitive) |
| `api_service_class` | `3` | exact integer |
| `grade_material` | `SA10` | LIKE `%value%` — `SA10` juga menemukan `SA106 GR B` |
| `unit_id` | `5` | exact via `tag_numbers.unit_id` |
| `pipe_condition` | `GOOD` | LIKE `%value%` |
| `maintenance_status` | `MONI` | LIKE `%value%` |
| `tanggal_pasang_line_from/to` | `2020-01-01` | rentang (inclusive) |
| `tanggal_ut_terakhir_from/to` | | rentang |
| `next_plan_inspection_api_from/to` | | rentang |
| `sort_by` | `actual_thk` | whitelist, default `created_at` |
| `sort_order` | `asc` | default `desc` |
| `per_page` | `25` | default 10 |

`$allowedSort`: `id`, `tag_number` (kolom join), semua kolom data,
`created_at`, `updated_at`. Nilai di luar whitelist → fallback default,
tidak pernah di-interpolasi.

Response suka CRUD: `response()->json(['success' => true, 'message' => ...,
'data' => new MonitoringPipelineResource(...)])` (201 untuk store). Error 500:
`'error' => config('app.debug') ? $e->getMessage() : null`.

## Routes — `routes/api.php`

Di dalam `middleware(['auth:api'])`, setelah blok Monitoring Equipment /
PSV. **Literal routes sebelum `apiResource`** agar `template`/`import`/
`export` tidak tertangkap `{monitoring_pipeline}`:

```php
// MONITORING PIPELINE
Route::get('/monitoring_pipeline/template', [MonitoringPipelineController::class, 'downloadTemplate']);
Route::post('/monitoring_pipeline/import', [MonitoringPipelineController::class, 'import']);
Route::get('/monitoring_pipeline/export', [MonitoringPipelineController::class, 'export']);
Route::apiResource('monitoring_pipeline', MonitoringPipelineController::class);
```

## Activity Log

- CRUD otomatis oleh `GlobalActivityObserver` (listener global di
  `AppServiceProvider`) — label dari `record_label` accessor (tag number).
- Import: manual `activity()->log('import', 'MonitoringPipeline', ['object' =>
  $n . ' data'])` seperti MonitoringEquipment.
- `config/log-activity.php` features/aliases: **tidak** ditambah (keputusan
  brainstorming: tanpa registrasi fitur).

## Di Luar Scope v1

- Dashboard / agregat.
- Riwayat perubahan / snapshot `BusinessPeriod`.
- Sinkronisasi otomatis dari workbook (import manual saja).
- Auto-create `tag_numbers` untuk tag yang tidak match.
- Registrasi `features`, unit test, Postman collection.

## Verifikasi

- `php artisan migrate` → tabel `monitoring_pipeline` terbentuk.
- `php artisan route:list --path=monitoring_pipeline` → 8 route.
- Import file asli via tinker/curl: tag tidak match di DB lokal → semua
  `failed` dengan pesan jelas (perilaku match-persis); setelah insert tag
  manual yang match, baris masuk; re-import → update (idempotent upsert).
- Normalisasi: `insulation=0` → null+warning; `nps='1 1/2'` → 1.5;
  serial tanggal → `Y-m-d`; `#N/A` → null.
- `GET /monitoring_pipeline?search=...&insulation=Yes` → terfilter;
  `export` dengan filter sama → isi Excel identik.
- `GET /monitoring_pipeline/template` → xlsx valid dengan 30 header.
