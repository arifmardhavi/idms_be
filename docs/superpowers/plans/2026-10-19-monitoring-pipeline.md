# Monitoring Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modul API CRUD + import/export Excel untuk data inspeksi UT piping (30 field, FK unik ke `tag_numbers`), mengikuti pola MonitoringEquipment.

**Architecture:** Laravel 10 API-only (JWT). Tabel `monitoring_pipeline` 1:1 dengan `tag_numbers` (FK unique, cascade delete). Import dibaca langsung via PhpSpreadsheet (`readDataOnly` + `getOldCalculatedValue`) karena file sumber memakai formula Excel; upsert per `tag_number_id` dalam satu transaksi tanpa events. Export memakai `DynamicExport` generik dengan query yang sama dengan `index()`.

**Tech Stack:** Laravel 10, MySQL, maatwebsite/excel 3.1, phpspreadsheet (via maatwebsite), tymon/jwt-auth.

**Spec:** `docs/superpowers/specs/2026-10-19-monitoring-pipeline-design.md` — plan ini berargumen dari spec; eksekutor WAJIB membaca keduanya.

## Global Constraints

- Tanpa unit test (keputusan eksplisit user) — verifikasi via tinker/manual, bukan PHPUnit.
- Tanpa registrasi `features`, tanpa role-gate — semua endpoint cukup `auth:api`.
- Pencocokan tag saat import: **persis** tanpa normalisasi suffix; tidak ketemu → skip + error report.
- Tabel `protected $table` wajib eksplisit (`monitoring_pipeline`), tanpa itu Laravel mengpluralisasi.
- Literal routes (template/import/export) HARUS sebelum `Route::apiResource`.
- Kolom double: `double()` nullable. Kolom date: `date()` nullable.
- Import menormalisasi: `insulation` hanya `Yes`/`No`; error Excel (`#N/A`, `#VALUE!`, `N/A`, `rumus dasar`, teks `USE ASME B31.3` di kolom angka) → null; NPS pecahan (`1 1/2`) → float; serial tanggal → `Y-m-d`.
- Semua tulisan (message response, activity log) memakai bahasa Inggris mengikuti konvensi repo (`'Monitoring Pipeline created successfully.'`).
- Commit per task dengan prefix `feat:`.

---

### Task 1: Migration + Model + Activity Label

**Files:**
- Create: `database/migrations/2026_10_19_000001_create_monitoring_pipeline_table.php`
- Create: `app/Models/MonitoringPipeline.php`
- Modify: `app/Observers/GlobalActivityObserver.php:31-47` (array `$labelFields`)

**Interfaces:**
- Produces: model `App\Models\MonitoringPipeline` (table `monitoring_pipeline`, fillable 30 kolom, casts float/date, relasi `tagNumber(): BelongsTo`, accessor `record_label`), dipakai semua task berikutnya.

- [ ] **Step 1: Buat migration**

Buat file berikut (ikuti gaya `database/migrations/2026_06_11_150459_create_monitoring_equipment_table.php` — anonymous class):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_pipeline', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tag_number_id')
                ->constrained('tag_numbers')
                ->cascadeOnDelete();

            $table->string('tml_drawing_number', 150)->nullable();
            $table->integer('api_service_class')->nullable();
            $table->string('insulation', 10)->nullable();
            $table->string('grade_material', 150)->nullable();
            $table->double('nps')->nullable();
            $table->double('od')->nullable();
            $table->string('sch', 50)->nullable();
            $table->double('t_nom')->nullable();
            $table->double('operating_press')->nullable();
            $table->double('design_press')->nullable();
            $table->double('design_temp')->nullable();
            $table->double('s')->nullable();
            $table->double('e')->nullable();
            $table->double('w')->nullable();
            $table->double('y')->nullable();
            $table->double('min_req_thk')->nullable();
            $table->double('half_nom_thk')->nullable();
            $table->date('tanggal_pasang_line')->nullable();
            $table->double('actual_thk')->nullable();
            $table->date('tanggal_ut_terakhir')->nullable();
            $table->double('corrosion_rate')->nullable();
            $table->double('remaining_life')->nullable();
            $table->double('alert_structural_thickness')->nullable();
            $table->date('next_plan_inspection_api')->nullable();
            $table->date('used_plan_inspection')->nullable();
            $table->string('pipe_condition')->nullable();
            $table->date('estimated_end_of_life')->nullable();
            $table->string('maintenance_status')->nullable();
            $table->string('rencana_tindak_lanjut')->nullable();

            $table->timestamps();

            $table->unique('tag_number_id');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_pipeline');
    }
};
```

- [ ] **Step 2: Jalankan migration**

Run: `php artisan migrate`
Expected: `Creating table 'monitoring_pipeline' ... DONE`

- [ ] **Step 3: Buat model**

Buat `app/Models/MonitoringPipeline.php` (ikuti gaya `app/Models/MonitoringEquipment.php`):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringPipeline extends BaseModel
{
    use HasFactory;

    protected $table = 'monitoring_pipeline';

    protected $fillable = [

        'tag_number_id',

        'tml_drawing_number',

        'api_service_class',

        'insulation',

        'grade_material',

        'nps',

        'od',

        'sch',

        't_nom',

        'operating_press',

        'design_press',

        'design_temp',

        's',

        'e',

        'w',

        'y',

        'min_req_thk',

        'half_nom_thk',

        'tanggal_pasang_line',

        'actual_thk',

        'tanggal_ut_terakhir',

        'corrosion_rate',

        'remaining_life',

        'alert_structural_thickness',

        'next_plan_inspection_api',

        'used_plan_inspection',

        'pipe_condition',

        'estimated_end_of_life',

        'maintenance_status',

        'rencana_tindak_lanjut',

    ];

    protected $casts = [

        'nps' => 'float',
        'od' => 'float',
        't_nom' => 'float',
        'operating_press' => 'float',
        'design_press' => 'float',
        'design_temp' => 'float',
        's' => 'float',
        'e' => 'float',
        'w' => 'float',
        'y' => 'float',
        'min_req_thk' => 'float',
        'half_nom_thk' => 'float',
        'actual_thk' => 'float',
        'corrosion_rate' => 'float',
        'remaining_life' => 'float',
        'alert_structural_thickness' => 'float',

        'tanggal_pasang_line' => 'date:Y-m-d',
        'tanggal_ut_terakhir' => 'date:Y-m-d',
        'next_plan_inspection_api' => 'date:Y-m-d',
        'used_plan_inspection' => 'date:Y-m-d',
        'estimated_end_of_life' => 'date:Y-m-d',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tagNumber()
    {
        return $this->belongsTo(
            Tag_number::class,
            'tag_number_id'
        );
    }

    public function getRecordLabelAttribute()
    {
        return $this->tagNumber?->tag_number;
    }
}
```

- [ ] **Step 4: Daftarkan label activity log**

Buka `app/Observers/GlobalActivityObserver.php`, tambahkan satu baris di array `$labelFields` (sekitar baris 47, dekat entri `MonitoringPsv`), pertahankan format kolom yang ada:

```php
        'MonitoringPipeline'     => 'record_label',
```

- [ ] **Step 5: Verifikasi via tinker**

Run:
```
php artisan tinker
>>> $t = App\Models\MonitoringPipeline::create(['tag_number_id' => App\Models\Tag_number::first()->id, 'nps' => 12.5, 'insulation' => 'No', 'tanggal_pasang_line' => '1995-09-17']);
>>> $t->nps; $t->tanggal_pasang_line->format('Y-m-d'); $t->record_label;
>>> $t->tagNumber->tag_number;
>>> $t->delete();
```
Expected: create sukses; `nps` float `12.5`; tanggal `1995-09-17`; `record_label` = tag number dari relasi; delete sukses.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_10_19_000001_create_monitoring_pipeline_table.php app/Models/MonitoringPipeline.php app/Observers/GlobalActivityObserver.php
git commit -m "feat: add monitoring pipeline migration, model, and activity label"
```

---

### Task 2: FormRequests + API Resource

**Files:**
- Create: `app/Http/Requests/StoreMonitoringPipelineRequest.php`
- Create: `app/Http/Requests/UpdateMonitoringPipelineRequest.php`
- Create: `app/Http/Requests/ImportMonitoringPipelineRequest.php`
- Create: `app/Http/Resources/MonitoringPipelineResource.php`

**Interfaces:**
- Consumes: model `MonitoringPipeline` (Task 1), `BaseRequest` (`app/Http/Requests/BaseRequest.php` — 422 JSON `{success:false,message,errors}`).
- Produces: request classes untuk controller (Task 4); resource `MonitoringPipelineResource` (kolom: `id`, `tag_number_id`, `tag_number`, 29 field data, `created_at`, `updated_at`).

- [ ] **Step 1: StoreMonitoringPipelineRequest**

Buat `app/Http/Requests/StoreMonitoringPipelineRequest.php` (ikuti gaya `StoreMonitoringEquipmentRequest.php`):

```php
<?php

namespace App\Http\Requests;

class StoreMonitoringPipelineRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'tag_number_id' => [
                'required',
                'integer',
                'exists:tag_numbers,id',
                'unique:monitoring_pipeline,tag_number_id',
            ],

            'tml_drawing_number' => 'nullable|string|max:150',
            'api_service_class' => 'nullable|integer',
            'insulation' => 'nullable|string|in:Yes,No',
            'grade_material' => 'nullable|string|max:150',

            'nps' => 'nullable|numeric',
            'od' => 'nullable|numeric',
            'sch' => 'nullable|string|max:50',
            't_nom' => 'nullable|numeric',
            'operating_press' => 'nullable|numeric',
            'design_press' => 'nullable|numeric',
            'design_temp' => 'nullable|numeric',
            's' => 'nullable|numeric',
            'e' => 'nullable|numeric',
            'w' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'min_req_thk' => 'nullable|numeric',
            'half_nom_thk' => 'nullable|numeric',
            'actual_thk' => 'nullable|numeric',
            'corrosion_rate' => 'nullable|numeric',
            'remaining_life' => 'nullable|numeric',
            'alert_structural_thickness' => 'nullable|numeric',

            'tanggal_pasang_line' => 'nullable|date',
            'tanggal_ut_terakhir' => 'nullable|date',
            'next_plan_inspection_api' => 'nullable|date',
            'used_plan_inspection' => 'nullable|date',
            'estimated_end_of_life' => 'nullable|date',

            'pipe_condition' => 'nullable|string',
            'maintenance_status' => 'nullable|string',
            'rencana_tindak_lanjut' => 'nullable|string',

        ];
    }
}
```

- [ ] **Step 2: UpdateMonitoringPipelineRequest**

Buat `app/Http/Requests/UpdateMonitoringPipelineRequest.php` — sama dengan Store, hanya `tag_number_id` berubah (ikuti gaya `UpdateMonitoringEquipmentRequest.php`):

```php
<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMonitoringPipelineRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'tag_number_id' => [
                'required',
                'integer',
                'exists:tag_numbers,id',
                Rule::unique('monitoring_pipeline')
                    ->ignore($this->route('monitoring_pipeline')),
            ],

            'tml_drawing_number' => 'nullable|string|max:150',
            'api_service_class' => 'nullable|integer',
            'insulation' => 'nullable|string|in:Yes,No',
            'grade_material' => 'nullable|string|max:150',

            'nps' => 'nullable|numeric',
            'od' => 'nullable|numeric',
            'sch' => 'nullable|string|max:50',
            't_nom' => 'nullable|numeric',
            'operating_press' => 'nullable|numeric',
            'design_press' => 'nullable|numeric',
            'design_temp' => 'nullable|numeric',
            's' => 'nullable|numeric',
            'e' => 'nullable|numeric',
            'w' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'min_req_thk' => 'nullable|numeric',
            'half_nom_thk' => 'nullable|numeric',
            'actual_thk' => 'nullable|numeric',
            'corrosion_rate' => 'nullable|numeric',
            'remaining_life' => 'nullable|numeric',
            'alert_structural_thickness' => 'nullable|numeric',

            'tanggal_pasang_line' => 'nullable|date',
            'tanggal_ut_terakhir' => 'nullable|date',
            'next_plan_inspection_api' => 'nullable|date',
            'used_plan_inspection' => 'nullable|date',
            'estimated_end_of_life' => 'nullable|date',

            'pipe_condition' => 'nullable|string',
            'maintenance_status' => 'nullable|string',
            'rencana_tindak_lanjut' => 'nullable|string',

        ];
    }
}
```

- [ ] **Step 3: ImportMonitoringPipelineRequest**

Buat `app/Http/Requests/ImportMonitoringPipelineRequest.php` (identik pola `ImportMonitoringEquipmentRequest.php`):

```php
<?php

namespace App\Http\Requests;

class ImportMonitoringPipelineRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'file' => [

                'required',

                'file',

                'mimes:xlsx,xls',

                'max:10240', // 10MB

            ]

        ];
    }
}
```

- [ ] **Step 4: MonitoringPipelineResource**

Buat `app/Http/Resources/MonitoringPipelineResource.php` (ikuti gaya `MonitoringEquipmentResource.php`; tanggal di-format eksplisit via `optional()->format()` — jangan andalkan `optional($string)` yang diam-diam mengembalikan null):

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringPipelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'tag_number_id' => $this->tag_number_id,

            'tag_number' => optional($this->tagNumber)->tag_number,

            'tml_drawing_number' => $this->tml_drawing_number,

            'api_service_class' => $this->api_service_class,

            'insulation' => $this->insulation,

            'grade_material' => $this->grade_material,

            'nps' => $this->nps,

            'od' => $this->od,

            'sch' => $this->sch,

            't_nom' => $this->t_nom,

            'operating_press' => $this->operating_press,

            'design_press' => $this->design_press,

            'design_temp' => $this->design_temp,

            's' => $this->s,

            'e' => $this->e,

            'w' => $this->w,

            'y' => $this->y,

            'min_req_thk' => $this->min_req_thk,

            'half_nom_thk' => $this->half_nom_thk,

            'tanggal_pasang_line' => optional($this->tanggal_pasang_line)
                ->format('Y-m-d'),

            'actual_thk' => $this->actual_thk,

            'tanggal_ut_terakhir' => optional($this->tanggal_ut_terakhir)
                ->format('Y-m-d'),

            'corrosion_rate' => $this->corrosion_rate,

            'remaining_life' => $this->remaining_life,

            'alert_structural_thickness' => $this->alert_structural_thickness,

            'next_plan_inspection_api' => optional($this->next_plan_inspection_api)
                ->format('Y-m-d'),

            'used_plan_inspection' => optional($this->used_plan_inspection)
                ->format('Y-m-d'),

            'pipe_condition' => $this->pipe_condition,

            'estimated_end_of_life' => optional($this->estimated_end_of_life)
                ->format('Y-m-d'),

            'maintenance_status' => $this->maintenance_status,

            'rencana_tindak_lanjut' => $this->rencana_tindak_lanjut,

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}
```

- [ ] **Step 5: Lint semua file baru**

Run:
```
php -l app/Http/Requests/StoreMonitoringPipelineRequest.php
php -l app/Http/Requests/UpdateMonitoringPipelineRequest.php
php -l app/Http/Requests/ImportMonitoringPipelineRequest.php
php -l app/Http/Resources/MonitoringPipelineResource.php
```
Expected: no syntax errors.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreMonitoringPipelineRequest.php app/Http/Requests/UpdateMonitoringPipelineRequest.php app/Http/Requests/ImportMonitoringPipelineRequest.php app/Http/Resources/MonitoringPipelineResource.php
git commit -m "feat: add monitoring pipeline form requests and API resource"
```

---

### Task 3: Import Service (PhpSpreadsheet cached-value reader + upsert)

**Files:**
- Create: `app/Services/MonitoringPipelineImportService.php`

**Interfaces:**
- Consumes: model `MonitoringPipeline`, `Tag_number`, `PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate`, `Illuminate\Support\Carbon`, `Model::withoutEvents`.
- Produces: `MonitoringPipelineImportService::import(UploadedFile $file): array` → `['success' => bool, 'message' => string, 'summary' => ['total','success','failed','skipped','errors'[],'warnings'[],'warnings_total']]` — dipakai controller (Task 4).

- [ ] **Step 1: Buat service**

Buat `app/Services/MonitoringPipelineImportService.php` dengan kode lengkap berikut:

```php
<?php

namespace App\Services;

use App\Models\MonitoringPipeline;
use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringPipelineImportService
{
    private const SHEET_NAME = 'Database UT Piping';

    /**
     * Slug header file (setelah normalisasi whitespace + slug '_') => field model.
     */
    private const HEADER_MAP = [
        'tag_number' => 'tag_number',
        'tml_drawing_number' => 'tml_drawing_number',
        'api_570_service_class' => 'api_service_class',
        'insulation_or_non_insulation' => 'insulation',
        'grade_material' => 'grade_material',
        'nps' => 'nps',
        'od_mm' => 'od',
        'sch' => 'sch',
        't_nom_mm' => 't_nom',
        'operating_press_kg_cm2' => 'operating_press',
        'design_press_kg_cm2' => 'design_press',
        'design_temp_kg_cm2' => 'design_temp',
        's_mpa' => 's',
        'e' => 'e',
        'w' => 'w',
        'y' => 'y',
        'min_req_thk_mm' => 'min_req_thk',
        '50_nom_thk_mm' => 'half_nom_thk',
        'tanggal_pasang_line' => 'tanggal_pasang_line',
        'actual_thk_mm' => 'actual_thk',
        'tanggal_ut_terakhir' => 'tanggal_ut_terakhir',
        'corrosion_rate' => 'corrosion_rate',
        'remaining_life_asme_31_3' => 'remaining_life',
        'alert_structural_thickness_api_574' => 'alert_structural_thickness',
        'next_plan_inspection_api_570' => 'next_plan_inspection_api',
        'used_plan_inspection' => 'used_plan_inspection',
        'pipe_condition_api_574' => 'pipe_condition',
        'estimated_end_of_life_eol' => 'estimated_end_of_life',
        'maintenance_status' => 'maintenance_status',
        'rencana_tindak_lanjut' => 'rencana_tindak_lanjut',
    ];

    private const DOUBLE_FIELDS = [
        'nps', 'od', 't_nom', 'operating_press', 'design_press', 'design_temp',
        's', 'e', 'w', 'y', 'min_req_thk', 'half_nom_thk', 'actual_thk',
        'corrosion_rate', 'remaining_life', 'alert_structural_thickness',
    ];

    private const DATE_FIELDS = [
        'tanggal_pasang_line', 'tanggal_ut_terakhir', 'next_plan_inspection_api',
        'used_plan_inspection', 'estimated_end_of_life',
    ];

    private const EXCEL_ERRORS = [
        '#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!', 'N/A',
    ];

    private const MAX_WARNINGS = 100;

    public function import(UploadedFile $file): array
    {
        $result = null;

        Model::withoutEvents(function () use ($file, &$result) {
            $result = $this->processImport($file);
        });

        return $result;
    }

    private function processImport(UploadedFile $file): array
    {
        $reader = IOFactory::createForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());

        $sheetNames = $spreadsheet->getSheetNames();

        $sheet = in_array(self::SHEET_NAME, $sheetNames, true)
            ? $spreadsheet->getSheetByName(self::SHEET_NAME)
            : $spreadsheet->getSheetIndex(0);

        $headerRow = $this->findHeaderRow($sheet);

        if ($headerRow === null) {
            $spreadsheet->disconnectWorksheets();

            return [
                'success' => false,
                'message' => 'Baris header dengan kolom TAG NUMBER tidak ditemukan.',
            ];
        }

        $columnMap = $this->mapColumns($sheet, $headerRow);

        if (! isset($columnMap['tag_number'])) {
            $spreadsheet->disconnectWorksheets();

            return [
                'success' => false,
                'message' => 'Kolom TAG NUMBER tidak ditemukan.',
            ];
        }

        $summary = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
            'warnings_total' => 0,
        ];

        $rows = [];

        $highestRow = $sheet->getHighestRow();

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {

            $row = [];
            $isEmpty = true;

            foreach ($columnMap as $field => $letter) {
                $cell = $sheet->getCell($letter.$r);
                $value = $cell->isFormula()
                    ? $cell->getOldCalculatedValue()
                    : $cell->getValue();

                $row[$field] = $value;

                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
            }

            if ($isEmpty) {
                $summary['skipped']++;

                continue;
            }

            $tagNumber = is_string($row['tag_number'])
                ? trim($row['tag_number'])
                : trim((string) ($row['tag_number'] ?? ''));

            if ($tagNumber === '') {
                $summary['skipped']++;

                continue;
            }

            $summary['total']++;

            $rows[] = [
                'row_number' => $r,
                'tag_number' => $tagNumber,
                'row' => $row,
            ];
        }

        $tagNumbers = collect($rows)->pluck('tag_number')->unique()->values()->all();

        $tags = Tag_number::whereIn('tag_number', $tagNumbers)
            ->get()
            ->keyBy('tag_number');

        $foundTagIds = $tags->pluck('id')->values()->all();

        $existingPipelines = MonitoringPipeline::whereIn('tag_number_id', $foundTagIds)
            ->get()
            ->keyBy('tag_number_id');

        $inserts = [];
        $updates = [];

        foreach ($rows as $item) {

            $tag = $tags->get($item['tag_number']);

            if (! $tag) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $item['row_number'],
                    'tag_number' => $item['tag_number'],
                    'message' => 'Tag Number tidak ditemukan.',
                ];

                continue;
            }

            $data = $this->normalizeRow($item['row'], $item['row_number'], $summary);

            $data['tag_number_id'] = $tag->id;

            $existing = $existingPipelines->get($tag->id);

            if ($existing) {
                $updates[] = ['id' => $existing->id, 'data' => $data];
            } else {
                $inserts[] = $data;
            }

            $summary['success']++;
        }

        DB::transaction(function () use ($inserts, $updates) {

            if ($inserts) {
                MonitoringPipeline::insert($inserts);
            }

            foreach ($updates as $item) {
                MonitoringPipeline::where('id', $item['id'])
                    ->update($item['data']);
            }

        });

        $spreadsheet->disconnectWorksheets();

        return [
            'success' => true,
            'message' => 'Import selesai.',
            'summary' => $summary,
        ];
    }

    /**
     * Cari baris header: baris (maks 15 baris pertama) yang sel kolom B-nya
     * slug-nya 'tag_number'.
     */
    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $limit = min(15, $sheet->getHighestRow());

        for ($r = 1; $r <= $limit; $r++) {
            $value = $sheet->getCell('B'.$r)->getValue();

            if ($value === null) {
                continue;
            }

            if ($this->slugHeader($value) === 'tag_number') {
                return $r;
            }
        }

        return null;
    }

    /**
     * Petakan field model => letter kolom berdasarkan slug header.
     * Batas 100 kolom (file asli punya phantom columns di kanan).
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        $highestColumn = Coordinate::columnIndexFromString(
            $sheet->getHighestDataColumn()
        );

        $limit = min($highestColumn, 100);

        $columnMap = [];

        for ($c = 1; $c <= $limit; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);

            $value = $sheet->getCell($letter.$headerRow)->getValue();

            if ($value === null || $value === '') {
                continue;
            }

            $slug = $this->slugHeader($value);

            if (isset(self::HEADER_MAP[$slug])) {
                $columnMap[self::HEADER_MAP[$slug]] = $letter;
            }
        }

        return $columnMap;
    }

    private function slugHeader($value): string
    {
        return str(preg_replace('/[\r\n]+/', ' ', trim((string) $value)))
            ->slug('_')
            ->toString();
    }

    private function normalizeRow(array $row, int $rowNumber, array &$summary): array
    {
        $data = [];

        foreach ($row as $field => $value) {

            if ($field === 'tag_number') {
                continue;
            }

            if ($field === 'insulation') {
                $data[$field] = $this->normalizeInsulation(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if ($field === 'nps') {
                $data[$field] = $this->normalizeNps(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if ($field === 'sch') {
                $data[$field] = $this->normalizeSch($value);

                continue;
            }

            if (in_array($field, self::DOUBLE_FIELDS, true)) {
                $data[$field] = $this->normalizeDouble(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if (in_array($field, self::DATE_FIELDS, true)) {
                $data[$field] = $this->normalizeDate($value);

                continue;
            }

            $data[$field] = $this->normalizeString($value);
        }

        return $data;
    }

    private function normalizeInsulation(
        $value, string $field, int $rowNumber, array &$summary
    ): ?string {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        if (strcasecmp($string, 'Yes') === 0) {
            return 'Yes';
        }

        if (strcasecmp($string, 'No') === 0) {
            return 'No';
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai insulation bukan Yes/No, disimpan kosong.'
        );

        return null;
    }

    private function normalizeNps(
        $value, string $field, int $rowNumber, array &$summary
    ): ?float {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);

        // Pecahan campuran: "1 1/2" => 1.5
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $string, $m) && (int) $m[3] !== 0) {
            return (float) $m[1] + ((float) $m[2] / (float) $m[3]);
        }

        // Pecahan: "3/4" => 0.75
        if (preg_match('/^(\d+)\/(\d+)$/', $string, $m) && (int) $m[2] !== 0) {
            return (float) $m[1] / (float) $m[2];
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai NPS tidak terbaca sebagai angka atau pecahan.'
        );

        return null;
    }

    private function normalizeSch($value): ?string
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $float = (float) $value;

            return $float == (int) $float
                ? (string) (int) $float
                : (string) $float;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function normalizeDouble(
        $value, string $field, int $rowNumber, array &$summary
    ): ?float {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai bukan angka (termasuk error Excel), disimpan kosong.'
        );

        return null;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial >= 1 && $serial <= 2958465) {
                return ExcelDate::excelToDateTimeObject($serial)
                    ->format('Y-m-d');
            }

            return null;
        }

        $string = trim((string) $value);

        if ($string === '' || in_array($string, self::EXCEL_ERRORS, true)) {
            return null;
        }

        try {
            return Carbon::parse($string)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeString($value): ?string
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '' || in_array($string, self::EXCEL_ERRORS, true)) {
            return null;
        }

        return $string;
    }

    private function pushWarning(
        array &$summary, int $rowNumber, string $column, $value, string $message
    ): void {
        $summary['warnings_total']++;

        if (count($summary['warnings']) >= self::MAX_WARNINGS) {
            return;
        }

        $summary['warnings'][] = [
            'row' => $rowNumber,
            'column' => $column,
            'value' => is_scalar($value) ? $value : null,
            'message' => $message,
        ];
    }
}
```

- [ ] **Step 2: Lint + pastikan service ter-resolve**

Run:
```
php -l app/Services/MonitoringPipelineImportService.php
php artisan tinker
>>> app(App\Services\MonitoringPipelineImportService::class)::class
```
Expected: no syntax error; class name ter-cetak.

- [ ] **Step 3: Verifikasi import dengan tag buatan**

File asli berisi 516 tag; DB lokal tidak punya tag tersebut (match persis = 0). Untuk menguji jalur sukses + upsert, buat satu tag yang sama persis dengan baris pertama file (`12-5-P434-A1-H-ET`):

```
php artisan tinker
>>> $src = App\Models\Tag_number::first();
>>> $tag = App\Models\Tag_number::create(['unit_id' => $src->unit_id, 'type_id' => $src->type_id, 'tag_number' => '12-5-P434-A1-H-ET', 'status' => 1]);
>>> $file = new Illuminate\Http\File('C:/Users/Inspeksi RU 7/Downloads/Audit Data Asset Piping (Final 2) + Dashboard.xlsx');
>>> $r1 = app(App\Services\MonitoringPipelineImportService::class)->import($file);
>>> $r1['summary']['total']; $r1['summary']['success']; $r1['summary']['failed']; $r1['summary']['skipped']; $r1['summary']['warnings_total'];
>>> $p = App\Models\MonitoringPipeline::first();
>>> $p->tag_number_id === $tag->id; $p->insulation; $p->nps; $p->tml_drawing_number; $p->tanggal_pasang_line; $p->actual_thk; $p->corrosion_rate;
>>> $p->id;
```
Expected:
- `total` = 516, `success` = 1, `failed` = 515, `skipped` = 6.
- `warnings_total` > 0 (baris insulation `0`/`33.4`, `#N/A`, `USE ASME B31.3` di kolom angka).
- Baris pipeline: `insulation` = `'No'`, `nps` = `12`, `tml_drawing_number` = `'J-05-1676-089'`, `tanggal_pasang_line` tanggal valid tahun ~1995 (serial 35065), `actual_thk` ≈ 9.2, `corrosion_rate` ≈ 0.0112.

- [ ] **Step 4: Verifikasi upsert (import kedua)**

```
>>> $id1 = $p->id;
>>> $r2 = app(App\Services\MonitoringPipelineImportService::class)->import($file);
>>> $r2['summary']['success'];
>>> App\Models\MonitoringPipeline::count(); App\Models\MonitoringPipeline::first()->id === $id1;
```
Expected: `success` = 1 lagi, `count()` tetap **1**, id sama (update, bukan insert ganda).

- [ ] **Step 5: Bersihkan data uji**

```
>>> $tag->delete();
>>> App\Models\MonitoringPipeline::count();
```
Expected: cascade delete → `0`.

- [ ] **Step 6: Commit**

```bash
git add app/Services/MonitoringPipelineImportService.php
git commit -m "feat: add monitoring pipeline excel import service with cached-value formula reader"
```

---

### Task 4: Controller CRUD + Routes

**Files:**
- Create: `app/Http/Controllers/MonitoringPipelineController.php`
- Modify: `routes/api.php` (grouped `use` statement baris 4; sisip blok route setelah blok Monitoring PSV, sekitar baris 360)

**Interfaces:**
- Consumes: `MonitoringPipeline`, `MonitoringPipelineResource`, `ApiResource::pagination()`, `Store/Update/ImportMonitoringPipelineRequest`, `MonitoringPipelineImportService::import()` (Task 3), `MonitoringPipelineExport`/`MonitoringPipelineTemplateExport` (Task 5 — dipanggil tapi dibuat di task berikutnya; commit controller bersama Task 5 jika memilih eksekusi berurutan, atau buat stub dulu).
- Produces: 8 endpoint route `monitoring_pipeline`; method privat `baseQuery()`, `applyFilters($query, $request)`, `applySort($query, $request)` dipakai ulang oleh `export()`.

- [ ] **Step 1: Buat controller**

Buat `app/Http/Controllers/MonitoringPipelineController.php` dengan kode lengkap berikut:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\MonitoringPipelineExport;
use App\Exports\MonitoringPipelineTemplateExport;
use App\Http\Requests\ImportMonitoringPipelineRequest;
use App\Http\Requests\StoreMonitoringPipelineRequest;
use App\Http\Requests\UpdateMonitoringPipelineRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\MonitoringPipelineResource;
use App\Models\MonitoringPipeline;
use App\Services\MonitoringPipelineImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringPipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = $this->baseQuery();

        $this->applyFilters($query, $request);

        $this->applySort($query, $request);

        $data = $query->paginate($perPage);

        return ApiResource::pagination(
            $data,
            MonitoringPipelineResource::class
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitoringPipelineRequest $request)
    {
        try {

            $validated = $request->validated();

            $monitoringPipeline = DB::transaction(function () use ($validated) {

                return MonitoringPipeline::create($validated);
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline created successfully.',
                'data' => new MonitoringPipelineResource(
                    $monitoringPipeline->load('tagNumber')
                ),
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MonitoringPipeline $monitoringPipeline)
    {
        $monitoringPipeline->load('tagNumber');

        return new MonitoringPipelineResource($monitoringPipeline);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateMonitoringPipelineRequest $request,
        MonitoringPipeline $monitoringPipeline
    ) {
        try {

            $validated = $request->validated();

            DB::transaction(function () use (&$monitoringPipeline, $validated) {

                $monitoringPipeline->update($validated);

                $monitoringPipeline->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline updated successfully.',
                'data' => new MonitoringPipelineResource(
                    $monitoringPipeline->load('tagNumber')
                ),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MonitoringPipeline $monitoringPipeline)
    {
        try {

            DB::transaction(function () use ($monitoringPipeline) {

                $monitoringPipeline->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline deleted successfully.',
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    public function import(
        ImportMonitoringPipelineRequest $request,
        MonitoringPipelineImportService $service
    ) {
        $result = $service->import(
            $request->file('file')
        );

        activity()->log('import', 'MonitoringPipeline', [
            'object' => ($result['summary']['success'] ?? 0).' data',
        ]);

        return $result;
    }

    /**
     * Download Excel Template
     */
    public function downloadTemplate()
    {
        return Excel::download(

            new MonitoringPipelineTemplateExport,

            'Template_Monitoring_Pipeline.xlsx'

        );
    }

    /**
     * Export Monitoring Pipeline Data
     */
    public function export(Request $request)
    {
        activity()->log('export', 'MonitoringPipeline');

        $query = $this->baseQuery();

        $this->applyFilters($query, $request);

        $this->applySort($query, $request);

        $rows = $query->get()
            ->map(fn ($item, $index) => array_merge(
                ['no' => $index + 1],
                $item->only(array_keys($this->exportColumns())),
                ['tag_number' => optional($item->tagNumber)->tag_number]
            ))
            ->map(fn ($row) => $this->formatExportRow($row))
            ->toArray();

        return Excel::download(

            new MonitoringPipelineExport($rows, $this->exportColumns()),

            'Monitoring_Pipeline_'.now()->format('Ymd_His').'.xlsx'

        );
    }

    /**
     * Query dasar: join tag_numbers + relasi tagNumber.
     * Dipakai bersama index() dan export().
     */
    private function baseQuery(): Builder
    {
        return MonitoringPipeline::query()

            ->leftJoin(
                'tag_numbers',
                'tag_numbers.id',
                '=',
                'monitoring_pipeline.tag_number_id'
            )

            ->select('monitoring_pipeline.*')

            ->with('tagNumber');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $search = $request->get('search');

        /**
         * =====================================================
         * GLOBAL SEARCH
         * =====================================================
         */
        $query->when($search, function ($q) use ($search) {

            $q->where(function ($query) use ($search) {

                $query

                    ->where(
                        'tag_numbers.tag_number',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.tml_drawing_number',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.grade_material',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.sch',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.pipe_condition',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.maintenance_status',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.rencana_tindak_lanjut',
                        'like',
                        "%{$search}%"
                    );

            });

        });

        /**
         * =====================================================
         * FILTER (equality)
         * =====================================================
         */

        $equalityFilters = [
            'insulation' => 'monitoring_pipeline.insulation',
            'api_service_class' => 'monitoring_pipeline.api_service_class',
            'grade_material' => 'monitoring_pipeline.grade_material',
            'unit_id' => 'tag_numbers.unit_id',
            'pipe_condition' => 'monitoring_pipeline.pipe_condition',
            'maintenance_status' => 'monitoring_pipeline.maintenance_status',
        ];

        foreach ($equalityFilters as $param => $column) {
            $query->when(
                $request->filled($param),
                fn ($q) => $q->where($column, $request->get($param))
            );
        }

        /**
         * =====================================================
         * FILTER (rentang tanggal, inclusive)
         * =====================================================
         */

        $dateRanges = [
            'tanggal_pasang_line' => 'monitoring_pipeline.tanggal_pasang_line',
            'tanggal_ut_terakhir' => 'monitoring_pipeline.tanggal_ut_terakhir',
            'next_plan_inspection_api' => 'monitoring_pipeline.next_plan_inspection_api',
        ];

        foreach ($dateRanges as $param => $column) {
            $query->when(
                $request->filled($param.'_from'),
                fn ($q) => $q->where(
                    $column,
                    '>=',
                    $request->get($param.'_from')
                )
            );

            $query->when(
                $request->filled($param.'_to'),
                fn ($q) => $q->where(
                    $column,
                    '<=',
                    $request->get($param.'_to')
                )
            );
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');

        $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $allowedSort = [

            'id' => 'monitoring_pipeline.id',

            'tag_number' => 'tag_numbers.tag_number',

            'tml_drawing_number' => 'monitoring_pipeline.tml_drawing_number',

            'api_service_class' => 'monitoring_pipeline.api_service_class',

            'insulation' => 'monitoring_pipeline.insulation',

            'grade_material' => 'monitoring_pipeline.grade_material',

            'nps' => 'monitoring_pipeline.nps',

            'od' => 'monitoring_pipeline.od',

            'sch' => 'monitoring_pipeline.sch',

            't_nom' => 'monitoring_pipeline.t_nom',

            'operating_press' => 'monitoring_pipeline.operating_press',

            'design_press' => 'monitoring_pipeline.design_press',

            'design_temp' => 'monitoring_pipeline.design_temp',

            's' => 'monitoring_pipeline.s',

            'e' => 'monitoring_pipeline.e',

            'w' => 'monitoring_pipeline.w',

            'y' => 'monitoring_pipeline.y',

            'min_req_thk' => 'monitoring_pipeline.min_req_thk',

            'half_nom_thk' => 'monitoring_pipeline.half_nom_thk',

            'tanggal_pasang_line' => 'monitoring_pipeline.tanggal_pasang_line',

            'actual_thk' => 'monitoring_pipeline.actual_thk',

            'tanggal_ut_terakhir' => 'monitoring_pipeline.tanggal_ut_terakhir',

            'corrosion_rate' => 'monitoring_pipeline.corrosion_rate',

            'remaining_life' => 'monitoring_pipeline.remaining_life',

            'alert_structural_thickness' => 'monitoring_pipeline.alert_structural_thickness',

            'next_plan_inspection_api' => 'monitoring_pipeline.next_plan_inspection_api',

            'used_plan_inspection' => 'monitoring_pipeline.used_plan_inspection',

            'pipe_condition' => 'monitoring_pipeline.pipe_condition',

            'estimated_end_of_life' => 'monitoring_pipeline.estimated_end_of_life',

            'maintenance_status' => 'monitoring_pipeline.maintenance_status',

            'rencana_tindak_lanjut' => 'monitoring_pipeline.rencana_tindak_lanjut',

            'created_at' => 'monitoring_pipeline.created_at',

            'updated_at' => 'monitoring_pipeline.updated_at',

        ];

        $query->orderBy(
            $allowedSort[$sortBy] ?? 'monitoring_pipeline.created_at',
            $sortOrder
        );
    }

    /**
     * Kolom export: key => label heading. 'no' direset manual.
     */
    private function exportColumns(): array
    {
        return [

            'no' => 'No',

            'tag_number' => 'Tag Number',

            'tml_drawing_number' => 'TML Drawing Number',

            'api_service_class' => 'API 570 Service Class',

            'insulation' => 'Insulation OR Non-Insulation',

            'grade_material' => 'Grade Material',

            'nps' => 'NPS',

            'od' => 'OD (mm)',

            'sch' => 'SCH',

            't_nom' => 'T. NOM (mm)',

            'operating_press' => 'Operating Press (kg/cm2)',

            'design_press' => 'Design Press (kg/cm2)',

            'design_temp' => 'Design Temp (kg/cm2)',

            's' => 'S (MPa)',

            'e' => 'E',

            'w' => 'W',

            'y' => 'Y',

            'min_req_thk' => 'Min Req Thk (mm)',

            'half_nom_thk' => '50% Nom. Thk (mm)',

            'tanggal_pasang_line' => 'Tanggal Pasang Line',

            'actual_thk' => 'Actual THK (mm)',

            'tanggal_ut_terakhir' => 'Tanggal UT Terakhir',

            'corrosion_rate' => 'Corrosion Rate',

            'remaining_life' => 'Remaining Life',

            'alert_structural_thickness' => 'Alert Structural Thickness (API 574)',

            'next_plan_inspection_api' => 'Next Plan Inspection API 570',

            'used_plan_inspection' => 'Used Plan Inspection',

            'pipe_condition' => 'Pipe Condition (API 574)',

            'estimated_end_of_life' => 'Estimated End Of Life (EOL)',

            'maintenance_status' => 'Maintenance Status',

            'rencana_tindak_lanjut' => 'Rencana Tindak Lanjut',

        ];
    }

    /**
     * Format nilai row untuk export: tanggal => Y-m-d, float berlebih dibulatkan.
     */
    private function formatExportRow(array $row): array
    {
        $dateKeys = [
            'tanggal_pasang_line',
            'tanggal_ut_terakhir',
            'next_plan_inspection_api',
            'used_plan_inspection',
            'estimated_end_of_life',
        ];

        foreach ($dateKeys as $key) {
            $value = $row[$key] ?? null;

            $row[$key] = $value === null
                ? null
                : \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        }

        $floatKeys = [
            'nps', 'od', 't_nom', 'operating_press', 'design_press',
            'design_temp', 's', 'e', 'w', 'y', 'min_req_thk',
            'half_nom_thk', 'actual_thk', 'corrosion_rate', 'remaining_life',
            'alert_structural_thickness',
        ];

        foreach ($floatKeys as $key) {
            $value = $row[$key] ?? null;

            if ($value !== null) {
                $row[$key] = round((float) $value, 4);
            }
        }

        return $row;
    }
}
```

- [ ] **Step 2: Daftarkan controller dan routes di `routes/api.php`**

(a) Tambahkan `MonitoringPipelineController` ke grouped use statement `use App\Http\Controllers\{` (baris 4), posisi setelah `MonitoringPsvController` (jaga urutan alfabetis longgar mengikuti file):

```php
    MonitoringPipelineController,
```

(b) Sisipkan blok route **setelah** baris penutup blok Monitoring PSV (`Route::put('/monitoring_psv/{monitoring_psv}', ...)` sekitar baris 359) dan **sebelum** blok berikutnya:

```php
    // MONITORING PIPELINE
    // Catatan: route literal HARUS ditulis sebelum apiResource {monitoring_pipeline}

    Route::get('/monitoring_pipeline/template', [MonitoringPipelineController::class, 'downloadTemplate']);

    Route::post('/monitoring_pipeline/import', [MonitoringPipelineController::class, 'import']);

    Route::get('/monitoring_pipeline/export', [MonitoringPipelineController::class, 'export']);

    Route::apiResource('monitoring_pipeline', MonitoringPipelineController::class);
```

- [ ] **Step 3: Verifikasi route**

Run: `php artisan route:list --path=monitoring_pipeline`
Expected: **8 route** — GET `monitoring_pipeline`, POST `monitoring_pipeline`, GET/PUT/DELETE `monitoring_pipeline/{monitoring_pipeline}`, GET `monitoring_pipeline/template`, POST `monitoring_pipeline/import`, GET `monitoring_pipeline/export`. Tidak ada error class not found (Task 5 masih menyediakan class Export — kalau route:list error karena class export belum ada, lanjutkan ke Task 5 lebih dulu lalu ulangi step ini).

- [ ] **Step 4: Verifikasi CRUD via tinker (tanpa HTTP server)**

```
php artisan tinker
>>> $ctrl = app(App\Http\Controllers\MonitoringPipelineController::class);
>>> $req = new Illuminate\Http\Request(['per_page' => 5, 'sort_by' => 'tag_number', 'sort_order' => 'asc']);
>>> $resp = $ctrl->index($req);
>>> json_decode($resp->getContent(), true)['success']; json_decode($resp->getContent(), true)['meta'];
```
Expected: `success` true, `meta` lengkap (data kosong, total 0).

Lanjutkan uji store → show → update → destroy lewat HTTP dengan JWT (butuh server):

(a) Ambil token:
```
php artisan tinker
>>> auth()->login(App\Models\User::first()); echo auth()->token();
```
(b) Jalankan server bila belum berjalan: `php artisan serve` (http://127.0.0.1:8000).
(c) Gunakan `$TOKEN` dari (a):

```bash
curl -s -X POST http://127.0.0.1:8000/api/monitoring_pipeline -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "{\"tag_number_id\": 1, \"insulation\": \"Yes\", \"nps\": 12, \"sch\": \"STD\"}"
```
Expected: 201, `data.tag_number` terisi (via relasi).

```bash
curl -s http://127.0.0.1:8000/api/monitoring_pipeline/1 -H "Authorization: Bearer $TOKEN"
curl -s -X PUT http://127.0.0.1:8000/api/monitoring_pipeline/1 -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "{\"insulation\": \"No\"}"
curl -s -X DELETE http://127.0.0.1:8000/api/monitoring_pipeline/1 -H "Authorization: Bearer $TOKEN"
```
Expected: 200; update mengubah `insulation`; delete `success: true`.

Validasi 422: kirim store tanpa `tag_number_id` → `{success:false, message:"Validation failed.", errors:{...}}` status 422.

- [ ] **Step 5: Verifikasi filter + search + sort**

Dengan minimal 1–2 baris data (buat via store curl atau tinker `MonitoringPipeline::create`):

```bash
curl -s "http://127.0.0.1:8000/api/monitoring_pipeline?search=STD&insulation=No&sort_by=nps&sort_order=asc&per_page=5" -H "Authorization: Bearer $TOKEN"
```
Expected: `data` hanya berisi baris yang cocok; kolom `nps` terurut asc; `?sort_by=tidak_ada&sort_order=asdsad` jatuh ke default `created_at desc` tanpa error SQL.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/MonitoringPipelineController.php routes/api.php
git commit -m "feat: add monitoring pipeline controller with crud, import, export routes"
```

---

### Task 5: Export Classes (data + template)

**Files:**
- Create: `app/Exports/MonitoringPipelineExport.php`
- Create: `app/Exports/MonitoringPipelineTemplateExport.php`

**Interfaces:**
- Consumes: `App\Exports\DynamicExport` (generik: constructor `(array $data, array $columns, array $options)` — `columns` key=>label, data array of assoc arrays) di `app/Exports/DynamicExport.php`.
- Produces: `new MonitoringPipelineExport(array $rows, array $columns)` (rows sudah diformat controller, key persis sama dengan `columns`) dan `new MonitoringPipelineTemplateExport` tanpa argumen — dipakai `MonitoringPipelineController::export()`/`downloadTemplate()` (Task 4).

- [ ] **Step 1: MonitoringPipelineExport**

Buat `app/Exports/MonitoringPipelineExport.php`:

```php
<?php

namespace App\Exports;

class MonitoringPipelineExport extends DynamicExport
{
    public function __construct(array $data, array $columns)
    {
        parent::__construct($data, $columns, [

            'freezeHeader' => true,

            'filter' => true,

            'headerStyle' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9E1F2'],
                ],
            ],

            'dateFormat' => [
                'tanggal_pasang_line' => 'yyyy-mm-dd',
                'tanggal_ut_terakhir' => 'yyyy-mm-dd',
                'next_plan_inspection_api' => 'yyyy-mm-dd',
                'used_plan_inspection' => 'yyyy-mm-dd',
                'estimated_end_of_life' => 'yyyy-mm-dd',
            ],

            'autoWidth' => true,

            'border' => true,

        ]);
    }
}
```

- [ ] **Step 2: MonitoringPipelineTemplateExport**

Buat `app/Exports/MonitoringPipelineTemplateExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class MonitoringPipelineTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStrictNullComparison
{
    /**
     * 30 label kolom — urutan SAMA dengan format import.
     */
    public function headings(): array
    {
        return [

            'Tag Number',

            'TML Drawing Number',

            'API 570 Service Class',

            'Insulation OR Non-Insulation',

            'Grade Material',

            'NPS',

            'OD (mm)',

            'SCH',

            'T. NOM (mm)',

            'Operating Press (kg/cm2)',

            'Design Press (kg/cm2)',

            'Design Temp (kg/cm2)',

            'S (MPa)',

            'E',

            'W',

            'Y',

            'Min Req Thk (mm)',

            '50% Nom. Thk (mm)',

            'Tanggal Pasang Line',

            'Actual THK (mm)',

            'Tanggal UT Terakhir',

            'Corrosion Rate',

            'Remaining Life',

            'Alert Structural Thickness (API 574)',

            'Next Plan Inspection API 570',

            'Used Plan Inspection',

            'Pipe Condition (API 574)',

            'Estimated End Of Life (EOL)',

            'Maintenance Status',

            'Rencana Tindak Lanjut',

        ];
    }

    /**
     * Satu baris contoh data.
     */
    public function array(): array
    {
        return [

            [

                '12-5-P434-A1-H-ET',
                'J-05-1676-089',
                3,
                'No',
                'SA53 GR B',
                12,
                323.8,
                'STD',
                9.53,
                4.3,
                6.1,
                100,
                17.24,
                1.0,
                1.0,
                1.0,
                0.7,
                4.76,
                '2020-01-15',
                9.2,
                '2024-06-01',
                0.0112,
                20.5,
                2.5,
                '2029-06-01',
                '2026-06-01',
                'GOOD CONDITION',
                '2035-02-08',
                'GOOD CONDITION',
                'Periksa kembali line segera',

            ]

        ];
    }
}
```

- [ ] **Step 3: Lint**

Run:
```
php -l app/Exports/MonitoringPipelineExport.php
php -l app/Exports/MonitoringPipelineTemplateExport.php
```
Expected: no syntax errors.

- [ ] **Step 4: Verifikasi template + export via tinker**

```
php artisan tinker
>>> $t = new App\Exports\MonitoringPipelineTemplateExport;
>>> count($t->headings()); count($t->array()[0]);
```
Expected: `30` dan `30`.

```
>>> $src = App\Models\Tag_number::first();
>>> $p = App\Models\MonitoringPipeline::create(['tag_number_id' => $src->id, 'insulation' => 'No', 'nps' => 12, 'tanggal_pasang_line' => '2020-01-15']);
>>> $ctrl = app(App\Http\Controllers\MonitoringPipelineController::class);
>>> $resp = $ctrl->downloadTemplate();
>>> $resp->headers->get('content-disposition');
>>> $resp2 = $ctrl->export(new Illuminate\Http\Request);
>>> $resp2->headers->get('content-disposition');
>>> $p->delete();
```
Expected: kedua response punya `content-disposition` berisi filename (`Template_Monitoring_Pipeline.xlsx` dan `Monitoring_Pipeline_...xlsx`). Buka file hasil download bila memungkinkan — heading export = label `exportColumns()` + freeze header.

- [ ] **Step 5: Verifikasi round-trip import template**

Template berheader saja (tanpa data) harus bisa di-import tanpa error:

```
>>> $path = storage_path('app/template_test.xlsx');
>>> \Maatwebsite\Excel\Facades\Excel::store(new App\Exports\MonitoringPipelineTemplateExport, 'template_test.xlsx');
>>> $file = new Illuminate\Http\File(storage_path('app/template_test.xlsx'));
>>> $r = app(App\Services\MonitoringPipelineImportService::class)->import($file);
>>> $r['summary'];
>>> \Illuminate\Support\Facades\Storage::delete('template_test.xlsx');
```
Expected: `success: true`; `total` = 1 (baris contoh — tag tidak ada di DB sehingga `failed` = 1, `message` error "Tag Number tidak ditemukan"), `skipped` = 0. Intinya: template terbaca, header ter-mapping, tidak ada exception.

- [ ] **Step 6: Commit**

```bash
git add app/Exports/MonitoringPipelineExport.php app/Exports/MonitoringPipelineTemplateExport.php
git commit -m "feat: add monitoring pipeline data export and import template"
```

---

## Akhir Plan — Verifikasi Akhir Gabungan

Setelah kelima task selesai:

1. `php artisan route:list --path=monitoring_pipeline` → 8 route.
2. Import file asli (`C:\Users\Inspeksi RU 7\Downloads\Audit Data Asset Piping (Final 2) + Dashboard.xlsx`): tanpa tag match di DB lokal → semua `failed` dengan pesan jelas (perilaku match-persis yang dikehendaki); dengan 1 tag buatan → `success >= 1`, re-import idempotent.
3. `GET /monitoring_pipeline?...filter` dan `GET /monitoring_pipeline/export?...filter` menghasilkan data identik.
4. `GET /monitoring_pipeline/template` → xlsx 30 kolom.
5. Bersihkan semua data uji (tag buatan + pipeline count = 0).
