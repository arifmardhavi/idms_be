# Dashboard Monitoring Memorandum Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan modul `monitoring_memorandum` lengkap dengan CRUD, export Excel, filter/search, dan dashboard bobot + backlog di aplikasi Laravel IDMS.

**Architecture:** Modul mengikuti pola `monitoring_pipeline`: satu tabel `monitoring_memorandum` (singular), model `MonitoringMemorandum`, FormRequest, Resource, Export, Controller, dan route `auth:api`. Aturan lintas lapisan (format `tag_number_ids`, `backlog_days`, bobot) ditempatkan di `app/Support/` agar resource/export/dashboard tidak mendefinisikan ulang. Dashboard dihitung dua query agregat di `MonitoringMemorandumDashboardService`, tanpa kolom tersimpan.

**Tech Stack:** Laravel 10 / PHP 8.1, MySQL, `maatwebsite/excel` ^3.1, Carbon, JWT (`tymon/jwt-auth`), activity log internal (`activity()` helper).

**Spec:** `docs/superpowers/specs/2026-11-23-monitoring-memorandum-design.md`

## Global Constraints

- Spesifikasi sumber: `docs/superpowers/specs/2026-11-23-monitoring-memorandum-design.md`.
- **Tanpa unit test baru** (keputusan eksplisit spec). Test cycle tiap task memakai `php -l`, `php artisan tinker --execute="..."`, `php artisan route:list`, dan `curl` dengan expected output yang tertulis.
- Nama tabel **wajib** `monitoring_memorandum`; model wajib menulis `protected $table = 'monitoring_memorandum'` secara eksplisit.
- Field wajib saat create hanya `tanggal_issue`, `issue_operasional`; sisanya nullable.
- `tag_number_ids`: `all` atau `\d+(,\d+)*`, tanpa kolom boolean baru. `search` tidak boleh mencocokkan sentinel `all`; filter `tag_number_ids=all` harus mencocokkannya.
- `tanggal_issue` ≤ hari ini; `backlog_days` minimum `0`, tidak disimpan di DB.
- Bobot biner 0–100, dirata-rata per `YEAR(tanggal_issue)`. Bucket backlog tetap empat: `1–30`, `31–60`, `61–90`, `91+`.
- Semua route di dalam `middleware(['auth:api'])`. Route literal (`dashboard`, `export`) ditulis **sebelum** `apiResource`.
- Response CRUD: `['success' => bool, 'message' => string, 'data' => ...]`; `store` HTTP 201; error 500 memuat `error` hanya jika `config('app.debug')`.
- Activity log otomatis via `GlobalActivityObserver` dengan label `record_label`; `config/log-activity.php` **tidak** diubah.
- `constrained('historical_memorandum')` wajib eksplisit dan memakai `nullOnDelete()`.

---

### Task 1: Migration, model, dan label activity log

**Files:**
- Create: `database/migrations/2026_11_23_000001_create_monitoring_memorandum_table.php`
- Create: `app/Models/MonitoringMemorandum.php`
- Modify: `app/Observers/GlobalActivityObserver.php` (tambah satu entry `labelFields`)

**Interfaces:**
- Consumes: `App\Models\BaseModel`, helper `activity()`.
- Produces: `App\Models\MonitoringMemorandum` dengan konstanta `STATUS_NOTIF_OPTIONS`, `STATUS_WO_OPTIONS`, `STATUS_OPTIONS`, `TAG_ALL_MARKER`, relasi `historicalMemorandum()`, accessor `is_all_tag_number`, `backlog_days`, `record_label`. Task 2+ bergantung pada konstanta `TAG_ALL_MARKER` dan accessor.

- [ ] **Step 1: Buat migration**

`database/migrations/2026_11_23_000001_create_monitoring_memorandum_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_memorandum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_issue');
            $table->string('issue_operasional', 255);
            $table->string('tag_number_ids', 255)->nullable();
            $table->foreignId('nota_dinas')->nullable()
                ->constrained('historical_memorandum')
                ->nullOnDelete();
            $table->unsignedBigInteger('no_notif')->nullable();
            $table->string('status_notif', 20)->nullable();
            $table->unsignedBigInteger('no_wo')->nullable();
            $table->string('status_wo', 2)->nullable();
            $table->string('no_po', 150)->nullable();
            $table->string('status', 10)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('tanggal_issue');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_memorandum');
    }
};
```

- [ ] **Step 2: Buat model**

`app/Models/MonitoringMemorandum.php`:

```php
<?php

namespace App\Models;

use App\Support\BacklogDays;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringMemorandum extends BaseModel
{
    use HasFactory;

    /**
     * Wajib eksplisit: tanpa ini Laravel mempluralisasi menjadi
     * "monitoring_memorandums".
     */
    protected $table = 'monitoring_memorandum';

    protected $fillable = [
        'tanggal_issue',
        'issue_operasional',
        'tag_number_ids',
        'nota_dinas',
        'no_notif',
        'status_notif',
        'no_wo',
        'status_wo',
        'no_po',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_issue' => 'date:Y-m-d',
    ];

    public const STATUS_NOTIF_OPTIONS = ['OSNO', 'NOPR'];
    public const STATUS_WO_OPTIONS = ['50', '60'];
    public const STATUS_OPTIONS = ['open', 'close'];
    public const TAG_ALL_MARKER = 'all';

    public function historicalMemorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'nota_dinas');
    }

    public function getIsAllTagNumberAttribute(): bool
    {
        return trim((string) $this->tag_number_ids) === self::TAG_ALL_MARKER;
    }

    /**
     * Alias SQL `backlog_days` dari baseQuery()/dashboard service harus
     * menang atas perhitungan PHP. Tanpa ini, accessor menimpa alias saat
     * query tidak memilih kolom tanggal_issue (mis. agregat dashboard),
     * menghasilkan 0 untuk semua baris.
     */
    public function getBacklogDaysAttribute($value): int
    {
        return $value !== null
            ? (int) $value
            : BacklogDays::forDate($this->tanggal_issue);
    }

    public function getRecordLabelAttribute(): string
    {
        return (string) $this->issue_operasional;
    }
}
```

- [ ] **Step 3: Daftarkan label activity log**

Di `app/Observers/GlobalActivityObserver.php`, pada array `$labelFields`, sisipkan setelah entri `'MonitoringPipeline'`:

```php
        'MonitoringMemorandum'  => 'record_label',
```

- [ ] **Step 4: Jalankan migration & verifikasi struktur**

Run:
```bash
php artisan migrate
php artisan tinker --execute="print_r(\Illuminate\Support\Facades\Schema::getColumnListing('monitoring_memorandum'));"
```
Expected: migration sukses; daftar kolom berisi `id`, `tanggal_issue`, `issue_operasional`, `tag_number_ids`, `nota_dinas`, `no_notif`, `status_notif`, `no_wo`, `status_wo`, `no_po`, `status`, `keterangan`, `created_at`, `updated_at`. Tidak ada kolom `backlog_days` dan tidak ada kolom boolean `all`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_11_23_000001_create_monitoring_memorandum_table.php app/Models/MonitoringMemorandum.php app/Observers/GlobalActivityObserver.php
git commit -m "feat(monitoring-memorandum): add table, model and activity label"
```

---

### Task 2: Support `TagNumberIds`

**Files:**
- Create: `app/Support/TagNumberIds.php`

**Interfaces:**
- Consumes: `App\Models\Tag_number`, `Illuminate\Database\Eloquent\Builder`.
- Produces: `TagNumberIds::MARKER`, `normalize(?string): ?string`, `isAll(?string): bool`, `parse(?string): array<int>`, `resolve(?string): array<int,array{id:int,tag_number:string}>`, `display(?string): string`, `applyFilter(Builder,string,?string): Builder`, `tagSearchSql(string): string`. Dipakai Task 4 (validasi), Task 5 (resource), Task 6 (export), Task 8 (controller).

- [ ] **Step 1: Buat support class**

`app/Support/TagNumberIds.php`:

```php
<?php

namespace App\Support;

use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu-satunya definisi aturan kolom tag_number_ids.
 *
 * Format tersimpan: "43,44,93" atau literal "all". Tidak ada kolom boolean
 * terpisah. $column pada method SQL selalu konstanta internal controller,
 * bukan input user.
 */
class TagNumberIds
{
    public const MARKER = 'all';

    /** Cache statis per request: id => tag_number. */
    private static array $cache = [];

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (strtolower($value) === self::MARKER) {
            return self::MARKER;
        }

        $parts = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $value)),
            fn ($part) => $part !== ''
        )));

        return $parts === [] ? null : implode(',', $parts);
    }

    public static function isAll(?string $value): bool
    {
        return strtolower(trim((string) $value)) === self::MARKER;
    }

    /** @return array<int> */
    public static function parse(?string $value): array
    {
        if (self::isAll($value) || trim((string) $value) === '') {
            return [];
        }

        return array_map(
            'intval',
            explode(',', self::normalize($value) ?? '')
        );
    }

    /**
     * @return array<int, array{id:int, tag_number:string}>
     */
    public static function resolve(?string $value): array
    {
        $ids = self::parse($value);

        if ($ids === []) {
            return [];
        }

        $missing = array_filter(
            $ids,
            fn ($id) => ! array_key_exists($id, self::$cache)
        );

        if ($missing !== []) {
            foreach (Tag_number::whereIn('id', $missing)->get(['id', 'tag_number']) as $tag) {
                self::$cache[(int) $tag->id] = (string) $tag->tag_number;
            }
        }

        return array_map(fn ($id) => [
            'id' => $id,
            'tag_number' => self::$cache[$id] ?? (string) $id,
        ], $ids);
    }

    public static function display(?string $value): string
    {
        if (self::isAll($value)) {
            return 'All Tag Number';
        }

        return implode(', ', array_column(self::resolve($value), 'tag_number'));
    }

    public static function applyFilter(Builder $query, string $column, ?string $value): Builder
    {
        if ($value === null || trim($value) === '') {
            return $query;
        }

        if (self::isAll($value)) {
            return $query->where($column, self::MARKER);
        }

        $ids = self::parse($value);

        if ($ids === []) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($column, $ids) {
            foreach ($ids as $id) {
                $query->orWhereRaw('FIND_IN_SET(?, ' . $column . ') > 0', [$id]);
            }
        });
    }

    /**
     * Baris bertanda 'all' otomatis tidak cocok karena
     * FIND_IN_SET(id, 'all') selalu 0.
     */
    public static function tagSearchSql(string $column): string
    {
        return 'EXISTS (SELECT 1 FROM tag_numbers'
            . ' WHERE FIND_IN_SET(tag_numbers.id, ' . $column . ') > 0'
            . ' AND tag_numbers.tag_number LIKE ?)';
    }
}
```

- [ ] **Step 2: Verifikasi normalisasi & parsing**

Run:
```bash
php artisan tinker --execute="use App\Support\TagNumberIds; var_export([TagNumberIds::normalize(' 43, 44,43 '), TagNumberIds::normalize(''), TagNumberIds::normalize('ALL'), TagNumberIds::parse('43,44'), TagNumberIds::parse('all'), TagNumberIds::isAll('all'), TagNumberIds::display('all')]);"
```
Expected: `['43,44', null, 'all', [43, 44], [], true, 'All Tag Number']`.

- [ ] **Step 3: Verifikasi filter pada query nyata**

Run:
```bash
php artisan tinker --execute="use App\Support\TagNumberIds; use App\Models\MonitoringMemorandum; \$q = MonitoringMemorandum::query(); TagNumberIds::applyFilter(\$q, 'monitoring_memorandum.tag_number_ids', '43,44'); echo \$q->toSql(); echo PHP_EOL; \$a = MonitoringMemorandum::query(); TagNumberIds::applyFilter(\$a, 'monitoring_memorandum.tag_number_ids', 'all'); echo \$a->toSql();"
```
Expected: query pertama memuat `FIND_IN_SET(?, monitoring_memorandum.tag_number_ids) > 0` dua kali; query kedua memuat `monitoring_memorandum.tag_number_ids = ?` (bukan `FIND_IN_SET`).

- [ ] **Step 4: Commit**

```bash
git add app/Support/TagNumberIds.php
git commit -m "feat(monitoring-memorandum): add tag_number_ids support"
```

---

### Task 3: Support `BacklogDays` dan bobot

**Files:**
- Create: `app/Support/BacklogDays.php`
- Create: `app/Support/MonitoringMemorandumBobot.php`

**Interfaces:**
- Consumes: Carbon.
- Produces: `BacklogDays::daysSql(string): string`, `forDate(CarbonInterface|string|null): int`, `bucket(int): int`, `label(int): string`, `meta(int): array{bucket:string,hari_min:int,hari_max:?int}`; `MonitoringMemorandumBobot::scoreSql(string): string`, `score(array): int`. Dipakai Task 1 (accessor), Task 7 (dashboard), Task 8 (controller).

- [ ] **Step 1: Buat `BacklogDays`**

`app/Support/BacklogDays.php`:

```php
<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class BacklogDays
{
    public static function daysSql(string $dateColumn): string
    {
        return "DATEDIFF(CURDATE(), {$dateColumn})";
    }

    public static function forDate(CarbonInterface|string|null $date): int
    {
        if ($date === null) {
            return 0;
        }

        // Positif untuk tanggal lampau; tanggal_issue divalidasi <= hari ini.
        return Carbon::parse($date)->diffInDays(Carbon::today(), false);
    }

    public static function bucket(int $days): int
    {
        return min(intdiv(max($days - 1, 0), 30) + 1, 4);
    }

    public static function label(int $bucket): string
    {
        return match ($bucket) {
            1 => '1–30',
            2 => '31–60',
            3 => '61–90',
            default => '91+',
        };
    }

    /** @return array{bucket:string, hari_min:int, hari_max:?int} */
    public static function meta(int $bucket): array
    {
        return match ($bucket) {
            1 => ['bucket' => self::label(1), 'hari_min' => 1, 'hari_max' => 30],
            2 => ['bucket' => self::label(2), 'hari_min' => 31, 'hari_max' => 60],
            3 => ['bucket' => self::label(3), 'hari_min' => 61, 'hari_max' => 90],
            default => ['bucket' => self::label(4), 'hari_min' => 91, 'hari_max' => null],
        };
    }
}
```

- [ ] **Step 2: Buat `MonitoringMemorandumBobot`**

`app/Support/MonitoringMemorandumBobot.php`:

```php
<?php

namespace App\Support;

/**
 * Aturan bobot Monitoring Memorandum. Sumber tunggal untuk query dashboard
 * maupun verifikasi manual. Total bobot maksimal 100.
 */
class MonitoringMemorandumBobot
{
    public static function scoreSql(string $table = 'monitoring_memorandum'): string
    {
        return '(CASE WHEN ' . $table . '.nota_dinas IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.no_notif IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.status_notif IS NOT NULL THEN 20 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.no_wo IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.status_wo IS NOT NULL THEN 20 ELSE 0 END'
            . " + CASE WHEN (" . $table . ".no_po IS NOT NULL AND TRIM(" . $table . ".no_po) <> '') THEN 10 ELSE 0 END"
            . " + CASE WHEN " . $table . ".status = 'close' THEN 20 ELSE 0 END)";
    }

    /** @param array<string, mixed> $row */
    public static function score(array $row): int
    {
        $score = 0;
        $score += self::filled($row, 'nota_dinas') ? 10 : 0;
        $score += self::filled($row, 'no_notif') ? 10 : 0;
        $score += self::filled($row, 'status_notif') ? 20 : 0;
        $score += self::filled($row, 'no_wo') ? 10 : 0;
        $score += self::filled($row, 'status_wo') ? 20 : 0;
        $score += self::filled($row, 'no_po') ? 10 : 0;
        $score += (($row['status'] ?? null) === 'close') ? 20 : 0;

        return $score;
    }

    /** @param array<string, mixed> $row */
    private static function filled(array $row, string $key): bool
    {
        return array_key_exists($key, $row)
            && $row[$key] !== null
            && $row[$key] !== '';
    }
}
```

- [ ] **Step 3: Verifikasi bucket dan bobot**

Run:
```bash
php artisan tinker --execute="use App\Support\BacklogDays; use App\Support\MonitoringMemorandumBobot; echo implode(',', array_map([BacklogDays::class,'bucket'], [0,30,31,60,61,90,91,511])); echo PHP_EOL; echo BacklogDays::meta(4)['bucket']; echo PHP_EOL; echo MonitoringMemorandumBobot::score(['nota_dinas'=>18,'no_notif'=>4501,'status_notif'=>'OSNO','no_wo'=>8101,'status_wo'=>'50','no_po'=>'PO-1','status'=>'close']); echo PHP_EOL; echo MonitoringMemorandumBobot::score(['no_notif'=>4501,'status_notif'=>'OSNO']); echo PHP_EOL; echo MonitoringMemorandumBobot::score([]);"
```
Expected baris: `1,1,2,2,3,3,4,4`; `91+`; `100`; `30`; `0`.

- [ ] **Step 4: Commit**

```bash
git add app/Support/BacklogDays.php app/Support/MonitoringMemorandumBobot.php
git commit -m "feat(monitoring-memorandum): add backlog and bobot rules"
```

---

### Task 4: FormRequest (validasi + normalisasi payload)

**Files:**
- Create: `app/Support/MonitoringMemorandumRules.php`
- Create: `app/Http/Requests/StoreMonitoringMemorandumRequest.php`
- Create: `app/Http/Requests/UpdateMonitoringMemorandumRequest.php`

**Interfaces:**
- Consumes: `App\Support\TagNumberIds`, `App\Models\Tag_number`, `App\Http\Requests\BaseRequest`.
- Produces: `MonitoringMemorandumRules::rules(bool $update = false): array<string,string|array>`, `normalize(array): array`, `validateTagIds(\Illuminate\Validation\Validator): void`; serta dua FormRequest yang dipakai Task 8.

- [ ] **Step 1: Buat kumpulan rule**

`app/Support/MonitoringMemorandumRules.php`:

```php
<?php

namespace App\Support;

use App\Models\Tag_number;
use Illuminate\Validation\Validator;

class MonitoringMemorandumRules
{
    /** @return array<string, string|array<int, string>> */
    public static function rules(bool $update = false): array
    {
        $p = fn (string $rule) => $update ? 'sometimes|' . $rule : $rule;

        return [
            'tanggal_issue'     => $p('required|date|before_or_equal:today'),
            'issue_operasional' => $p('required|string|max:255'),
            // WAJIB array: Laravel memecah rule string pada "|", sehingga
            // karakter "|" di dalam regex akan merusak rule bila ditulis string.
            'tag_number_ids'    => array_merge(
                $update ? ['sometimes'] : [],
                ['nullable', 'string', 'max:255', 'regex:/^(all|\d+(,\d+)*)$/i']
            ),
            'nota_dinas'        => $p('nullable|integer|exists:historical_memorandum,id'),
            'no_notif'          => $p('nullable|integer|min:0'),
            'status_notif'      => $p('nullable|string|in:OSNO,NOPR'),
            'no_wo'             => $p('nullable|integer|min:0'),
            'status_wo'         => $p('nullable|string|in:50,60'),
            'no_po'             => $p('nullable|string|max:150'),
            'status'            => $p('nullable|string|in:open,close'),
            'keterangan'        => $p('nullable|string|max:5000'),
        ];
    }

    /** String kosong -> null, hanya untuk key yang memang dikirim. */
    public static function normalize(array $data): array
    {
        $nullable = [
            'tag_number_ids', 'no_po', 'status_notif', 'status_wo',
            'status', 'keterangan',
        ];

        foreach ($nullable as $key) {
            if (array_key_exists($key, $data)
                && is_string($data[$key])
                && trim($data[$key]) === ''
            ) {
                $data[$key] = null;
            }
        }

        if (array_key_exists('tag_number_ids', $data)) {
            $data['tag_number_ids'] = is_string($data['tag_number_ids'])
                ? TagNumberIds::normalize($data['tag_number_ids'])
                : $data['tag_number_ids'];
        }

        return $data;
    }

    public static function validateTagIds(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('tag_number_ids')) {
                return;
            }

            $value = $validator->getData()['tag_number_ids'] ?? null;

            if ($value === null || TagNumberIds::isAll($value)) {
                return;
            }

            $ids = TagNumberIds::parse($value);

            if ($ids === []) {
                return;
            }

            $found = Tag_number::whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = array_diff($ids, $found);

            if ($missing !== []) {
                $validator->errors()->add(
                    'tag_number_ids',
                    'Tag Number tidak ditemukan: ' . implode(', ', $missing)
                );
            }
        });
    }
}
```

- [ ] **Step 2: Buat StoreRequest**

`app/Http/Requests/StoreMonitoringMemorandumRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Support\MonitoringMemorandumRules;
use Illuminate\Validation\Validator;

class StoreMonitoringMemorandumRequest extends BaseRequest
{
    /** WAJIB: FormRequest::authorize() default-nya false -> 403 tanpa ini. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return MonitoringMemorandumRules::rules(false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(MonitoringMemorandumRules::normalize($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        MonitoringMemorandumRules::validateTagIds($validator);
    }
}
```

- [ ] **Step 3: Buat UpdateRequest**

`app/Http/Requests/UpdateMonitoringMemorandumRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Support\MonitoringMemorandumRules;
use Illuminate\Validation\Validator;

class UpdateMonitoringMemorandumRequest extends BaseRequest
{
    /** WAJIB: FormRequest::authorize() default-nya false -> 403 tanpa ini. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return MonitoringMemorandumRules::rules(true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(MonitoringMemorandumRules::normalize($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        MonitoringMemorandumRules::validateTagIds($validator);
    }
}
```

- [ ] **Step 4: Verifikasi aturan & normalisasi**

Run:
```bash
php artisan tinker --execute="use App\Support\MonitoringMemorandumRules; use App\Support\TagNumberIds; var_export([MonitoringMemorandumRules::rules()['tanggal_issue'], MonitoringMemorandumRules::rules(true)['nota_dinas'], MonitoringMemorandumRules::rules()['tag_number_ids'], MonitoringMemorandumRules::normalize(['no_po'=>'   ','keterangan'=>'','tag_number_ids'=>' 43, 44,43 '])]);"
```
Expected: `'required|date|before_or_equal:today'`; `'sometimes|nullable|integer|exists:historical_memorandum,id'`; `['nullable', 'string', 'max:255', 'regex:/^(all|\d+(,\d+)*)$/i']` (array, bukan string); `['no_po' => null, 'keterangan' => null, 'tag_number_ids' => '43,44']`.

- [ ] **Step 5: Commit**

```bash
git add app/Support/MonitoringMemorandumRules.php app/Http/Requests/StoreMonitoringMemorandumRequest.php app/Http/Requests/UpdateMonitoringMemorandumRequest.php
git commit -m "feat(monitoring-memorandum): add store and update requests"
```

---

### Task 5: Resource

**Files:**
- Create: `app/Http/Resources/MonitoringMemorandumResource.php`

**Interfaces:**
- Consumes: `App\Support\TagNumberIds`, accessor `is_all_tag_number` & `backlog_days` dari Task 1, relasi `historicalMemorandum`.
- Produces: `MonitoringMemorandumResource::toArray(Request): array` dengan 18 key seperti demo. Dipakai Task 8.

- [ ] **Step 1: Buat resource**

`app/Http/Resources/MonitoringMemorandumResource.php`:

```php
<?php

namespace App\Http\Resources;

use App\Support\TagNumberIds;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringMemorandumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tanggal_issue'     => $this->tanggal_issue?->format('Y-m-d'),
            'issue_operasional' => $this->issue_operasional,
            'tag_number_ids'    => $this->tag_number_ids,
            'is_all_tag_number' => $this->is_all_tag_number,
            'tag_numbers'       => TagNumberIds::resolve($this->tag_number_ids),
            'nota_dinas'        => $this->nota_dinas,
            'no_dokumen'        => $this->whenLoaded(
                'historicalMemorandum',
                fn () => $this->historicalMemorandum?->no_dokumen
            ),
            'no_notif'          => $this->no_notif,
            'status_notif'      => $this->status_notif,
            'no_wo'             => $this->no_wo,
            'status_wo'         => $this->status_wo,
            'no_po'             => $this->no_po,
            'status'            => $this->status,
            'backlog_days'      => $this->backlog_days,
            'keterangan'        => $this->keterangan,
            'created_at'        => $this->created_at?->toJSON(),
            'updated_at'        => $this->updated_at?->toJSON(),
        ];
    }
}
```

- [ ] **Step 2: Verifikasi shape resource**

Run:
```bash
php artisan tinker --execute="use App\Models\MonitoringMemorandum; use App\Http\Resources\MonitoringMemorandumResource; \$m = new MonitoringMemorandum(['tanggal_issue'=>'2026-11-18','issue_operasional'=>'Uji resource','tag_number_ids'=>'all']); var_export((new MonitoringMemorandumResource(\$m))->toArray(\Illuminate\Http\Request::create('/')));"
```
Expected: `is_all_tag_number` = `true`, `tag_numbers` = `[]`, `backlog_days` = `5` (saat dijalankan 2026-11-23), `no_dokumen` tidak muncul karena relasi belum di-load.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Resources/MonitoringMemorandumResource.php
git commit -m "feat(monitoring-memorandum): add API resource"
```

---

### Task 6: Export Excel

**Files:**
- Create: `app/Exports/MonitoringMemorandumExport.php`

**Interfaces:**
- Consumes: `App\Support\TagNumberIds::display()`, `Illuminate\Database\Eloquent\Builder` dari Task 8 `baseQuery()`.
- Produces: `MonitoringMemorandumExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle`; constructor `(Builder $builder)`. Dipakai Task 8.

- [ ] **Step 1: Buat export class**

`app/Exports/MonitoringMemorandumExport.php`:

```php
<?php

namespace App\Exports;

use App\Support\TagNumberIds;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonitoringMemorandumExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private int $no = 0;

    public function __construct(protected Builder $builder)
    {
    }

    public function title(): string
    {
        return 'Monitoring Memorandum';
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Issue',
            'Issue Operasional',
            'Tag Number',
            'Nota Dinas',
            'No Notif',
            'Status Notif',
            'No WO',
            'Status WO',
            'No PO',
            'Status',
            'Backlog Days',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->no,
            $row->tanggal_issue?->format('Y-m-d') ?? '',
            $row->issue_operasional,
            TagNumberIds::display($row->tag_number_ids),
            $row->historicalMemorandum?->no_dokumen ?? '',
            $row->no_notif ?? '',
            $row->status_notif ?? '',
            $row->no_wo ?? '',
            $row->status_wo ?? '',
            $row->no_po ?? '',
            $row->status ?? '',
            $row->backlog_days,
            $row->keterangan ?? '',
        ];
    }
}
```

- [ ] **Step 2: Verifikasi kontrak class**

Run:
```bash
php artisan tinker --execute="\$i = class_implements(\App\Exports\MonitoringMemorandumExport::class); var_export([isset(\$i['Maatwebsite\Excel\Concerns\FromQuery']), isset(\$i['Maatwebsite\Excel\Concerns\WithMapping']), count((new \App\Exports\MonitoringMemorandumExport(\App\Models\MonitoringMemorandum::query()))->headings())]);"
```
Expected: `[true, true, 13]`.

- [ ] **Step 3: Commit**

```bash
git add app/Exports/MonitoringMemorandumExport.php
git commit -m "feat(monitoring-memorandum): add Excel export"
```

---

### Task 7: Dashboard service

**Files:**
- Create: `app/Services/MonitoringMemorandumDashboardService.php`

**Interfaces:**
- Consumes: `App\Models\MonitoringMemorandum`, `App\Support\BacklogDays`, `App\Support\MonitoringMemorandumBobot::scoreSql()`.
- Produces: `MonitoringMemorandumDashboardService::getDashboard(): array` berisi key `bobot` dan `backlog`. Dipakai Task 8.

- [ ] **Step 1: Buat service**

`app/Services/MonitoringMemorandumDashboardService.php`:

```php
<?php

namespace App\Services;

use App\Models\MonitoringMemorandum;
use App\Support\BacklogDays;
use App\Support\MonitoringMemorandumBobot;

class MonitoringMemorandumDashboardService
{
    public function getDashboard(): array
    {
        return [
            'bobot' => $this->bobot(),
            'backlog' => $this->backlog(),
        ];
    }

    private function bobot(): array
    {
        return MonitoringMemorandum::query()
            ->selectRaw('YEAR(tanggal_issue) as tahun')
            ->selectRaw(
                'AVG(' . MonitoringMemorandumBobot::scoreSql() . ') as rata_rata_bobot'
            )
            ->selectRaw('COUNT(*) as total_data')
            ->groupBy('tahun')
            ->orderBy('tahun')
            ->get()
            ->map(fn ($row) => [
                'tahun' => (int) $row->tahun,
                'rata_rata_bobot' => round((float) $row->rata_rata_bobot, 2),
                'total_data' => (int) $row->total_data,
            ])
            ->all();
    }

    private function backlog(): array
    {
        $counts = array_fill(1, 4, 0);

        MonitoringMemorandum::query()
            ->selectRaw(BacklogDays::daysSql('tanggal_issue') . ' as backlog_days')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[BacklogDays::bucket((int) $row->backlog_days)]++;
            });

        $result = [];

        for ($bucket = 1; $bucket <= 4; $bucket++) {
            $result[] = BacklogDays::meta($bucket) + ['total' => $counts[$bucket]];
        }

        return $result;
    }
}
```

- [ ] **Step 2: Verifikasi agregat dengan data uji dalam transaksi**

Run:
```bash
php artisan tinker --execute="DB::beginTransaction(); \$now = \Carbon\Carbon::today(); \$rows = [['2026-11-18',30],['2026-10-29',20],['2026-10-01',100],['2026-11-05',0],['2025-12-15',80],['2025-06-30',100]]; foreach (\$rows as \$r) { \$m = new \App\Models\MonitoringMemorandum(); \$m->tanggal_issue = \$r[0]; \$m->issue_operasional = 'uji'; \$m->no_po = 'X'; if (\$r[1] === 100) \$m->status = 'close'; \$m->save(); } print_r(app(\App\Services\MonitoringMemorandumDashboardService::class)->getDashboard()); DB::rollBack();"
```
Expected: `bobot` dua entri tahun 2025 & 2026; `backlog` empat entri dengan bucket `1–30`, `31–60`, `61–90`, `91+`; transaksi di-rollback sehingga tidak ada data tertinggal.

- [ ] **Step 3: Commit**

```bash
git add app/Services/MonitoringMemorandumDashboardService.php
git commit -m "feat(monitoring-memorandum): add bobot and backlog dashboard"
```

---

### Task 8: Controller, routes, dan verifikasi end-to-end

**Files:**
- Create: `app/Http/Controllers/MonitoringMemorandumController.php`
- Modify: `routes/api.php` (import controller + 3 route)
- Modify: `app/Observers/GlobalActivityObserver.php` — sudah diubah Task 1, tidak diubah lagi di sini.

**Interfaces:**
- Consumes: semua task sebelumnya.
- Produces: `MonitoringMemorandumController::{index,store,show,update,destroy,export,dashboard}` dan route `GET/POST /api/monitoring_memorandum`, `GET/PUT/DELETE /api/monitoring_memorandum/{monitoring_memorandum}`, `GET /api/monitoring_memorandum/export`, `GET /api/monitoring_memorandum/dashboard`.

- [ ] **Step 1: Buat controller**

`app/Http/Controllers/MonitoringMemorandumController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\MonitoringMemorandumExport;
use App\Http\Requests\StoreMonitoringMemorandumRequest;
use App\Http\Requests\UpdateMonitoringMemorandumRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\MonitoringMemorandumResource;
use App\Models\MonitoringMemorandum;
use App\Services\MonitoringMemorandumDashboardService;
use App\Support\BacklogDays;
use App\Support\TagNumberIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringMemorandumController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        return ApiResource::pagination(
            $this->baseQuery($request)->paginate($perPage),
            MonitoringMemorandumResource::class
        );
    }

    public function store(StoreMonitoringMemorandumRequest $request)
    {
        try {
            $row = DB::transaction(
                fn () => MonitoringMemorandum::create($request->validated())
            );

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum created successfully.',
                'data' => new MonitoringMemorandumResource(
                    $row->load('historicalMemorandum')
                ),
            ], 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to create Monitoring Memorandum.', $e);
        }
    }

    public function show(MonitoringMemorandum $monitoringMemorandum)
    {
        return new MonitoringMemorandumResource(
            $monitoringMemorandum->load('historicalMemorandum')
        );
    }

    public function update(
        UpdateMonitoringMemorandumRequest $request,
        MonitoringMemorandum $monitoringMemorandum
    ) {
        try {
            DB::transaction(function () use ($request, $monitoringMemorandum) {
                $monitoringMemorandum->update($request->validated());
                $monitoringMemorandum->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum updated successfully.',
                'data' => new MonitoringMemorandumResource(
                    $monitoringMemorandum->load('historicalMemorandum')
                ),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update Monitoring Memorandum.', $e);
        }
    }

    public function destroy(MonitoringMemorandum $monitoringMemorandum)
    {
        try {
            DB::transaction(fn () => $monitoringMemorandum->delete());

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete Monitoring Memorandum.', $e);
        }
    }

    public function export(Request $request)
    {
        activity()->log('export', 'MonitoringMemorandum');

        return Excel::download(
            new MonitoringMemorandumExport($this->baseQuery($request)),
            'Monitoring_Memorandum_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function dashboard(MonitoringMemorandumDashboardService $service)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Dashboard Monitoring Memorandum.',
                'data' => $service->getDashboard(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed load dashboard.', $e);
        }
    }

    private function error(string $message, \Throwable $e)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }

    /**
     * Query dasar dipakai bersama index() dan export() agar isi export
     * selalu identik dengan hasil filter di layar.
     */
    private function baseQuery(Request $request): Builder
    {
        $query = MonitoringMemorandum::query()
            ->leftJoin(
                'historical_memorandum',
                'historical_memorandum.id',
                '=',
                'monitoring_memorandum.nota_dinas'
            )
            ->select('monitoring_memorandum.*')
            ->selectRaw(
                BacklogDays::daysSql('monitoring_memorandum.tanggal_issue')
                . ' as backlog_days'
            )
            ->with('historicalMemorandum');

        $search = $request->get('search');

        $query->when($search, function (Builder $query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function (Builder $query) use ($like) {
                $query
                    ->where('monitoring_memorandum.issue_operasional', 'like', $like)
                    ->orWhere('historical_memorandum.no_dokumen', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_notif', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_wo', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_po', 'like', $like)
                    ->orWhere('monitoring_memorandum.keterangan', 'like', $like)
                    ->orWhereRaw(
                        TagNumberIds::tagSearchSql('monitoring_memorandum.tag_number_ids'),
                        [$like]
                    );
            });
        });

        foreach (['status', 'status_notif', 'status_wo', 'nota_dinas'] as $field) {
            $query->when(
                $request->filled($field),
                fn (Builder $q) => $q->where('monitoring_memorandum.' . $field, $request->get($field))
            );
        }

        TagNumberIds::applyFilter(
            $query,
            'monitoring_memorandum.tag_number_ids',
            $request->get('tag_number_ids')
        );

        $query->when(
            $request->filled('tanggal_issue_from'),
            fn (Builder $q) => $q->where(
                'monitoring_memorandum.tanggal_issue',
                '>=',
                $request->get('tanggal_issue_from')
            )
        );

        $query->when(
            $request->filled('tanggal_issue_to'),
            fn (Builder $q) => $q->where(
                'monitoring_memorandum.tanggal_issue',
                '<=',
                $request->get('tanggal_issue_to')
            )
        );

        $backlogColumn = BacklogDays::daysSql('monitoring_memorandum.tanggal_issue');

        $query->when(
            $request->filled('backlog_days_min'),
            fn (Builder $q) => $q->whereRaw($backlogColumn . ' >= ?', [(int) $request->get('backlog_days_min')])
        );

        $query->when(
            $request->filled('backlog_days_max'),
            fn (Builder $q) => $q->whereRaw($backlogColumn . ' <= ?', [(int) $request->get('backlog_days_max')])
        );

        $allowedSort = [
            'id' => 'monitoring_memorandum.id',
            'tanggal_issue' => 'monitoring_memorandum.tanggal_issue',
            'issue_operasional' => 'monitoring_memorandum.issue_operasional',
            'tag_number_ids' => 'monitoring_memorandum.tag_number_ids',
            'nota_dinas' => 'monitoring_memorandum.nota_dinas',
            'no_notif' => 'monitoring_memorandum.no_notif',
            'status_notif' => 'monitoring_memorandum.status_notif',
            'no_wo' => 'monitoring_memorandum.no_wo',
            'status_wo' => 'monitoring_memorandum.status_wo',
            'no_po' => 'monitoring_memorandum.no_po',
            'status' => 'monitoring_memorandum.status',
            'backlog_days' => 'backlog_days',
            'created_at' => 'monitoring_memorandum.created_at',
            'updated_at' => 'monitoring_memorandum.updated_at',
        ];

        $sortBy = $request->get('sort_by', 'tanggal_issue');
        $sortOrder = strtolower(trim((string) $request->get('sort_order', 'desc'))) === 'asc'
            ? 'asc'
            : 'desc';

        $query->orderBy(
            $allowedSort[$sortBy] ?? 'monitoring_memorandum.tanggal_issue',
            $sortOrder
        );

        return $query;
    }
}
```

- [ ] **Step 2: Daftarkan import controller**

Di `routes/api.php`, pada blok `use App\Http\Controllers\{ ... }`, tambahkan setelah `MonitoringPipelineController,`:

```php
    MonitoringMemorandumController,
```

- [ ] **Step 3: Daftarkan route**

Di `routes/api.php`, tepat setelah baris `Route::apiResource('monitoring_pipeline', MonitoringPipelineController::class);`, tambahkan:

```php

    // MONITORING MEMORANDUM
    // Route literal HARUS sebelum apiResource agar tidak tertangkap {monitoring_memorandum}.
    Route::get('/monitoring_memorandum/dashboard', [MonitoringMemorandumController::class, 'dashboard']);
    Route::get('/monitoring_memorandum/export', [MonitoringMemorandumController::class, 'export']);
    Route::apiResource('monitoring_memorandum', MonitoringMemorandumController::class);
```

- [ ] **Step 4: Verifikasi route & sintaks**

Run:
```bash
php artisan route:list --path=monitoring_memorandum
```
Expected: 7 baris — `GET|HEAD dashboard`, `GET|HEAD export`, `GET|HEAD monitoring_memorandum`, `POST monitoring_memorandum`, `GET|HEAD monitoring_memorandum/{monitoring_memorandum}`, `PUT|PATCH monitoring_memorandum/{monitoring_memorandum}`, `DELETE monitoring_memorandum/{monitoring_memorandum}`; semuanya middleware `auth:api`.

- [ ] **Step 5: Verifikasi CRUD & filter lewat HTTP**

Jalankan `php artisan serve` di satu terminal. Siapkan `TOKEN` berisi JWT user yang sudah login (`POST /api/auth/login`). Kemudian:

```bash
curl -s -X POST http://127.0.0.1:8000/api/monitoring_memorandum -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"tanggal_issue":"2026-11-18","issue_operasional":"Kebocoran flange line A","tag_number_ids":"43,44","no_notif":4501,"status_notif":"OSNO"}'
```
Expected: HTTP 201, `success:true`, `tag_number_ids` = `43,44`, `backlog_days` = `5`, `nota_dinas` = `null`.

```bash
curl -s -X POST http://127.0.0.1:8000/api/monitoring_memorandum -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"tanggal_issue":"2026-11-05","issue_operasional":"Insulasi thermal rusak","tag_number_ids":"all"}'
```
Expected: HTTP 201, `is_all_tag_number:true`, `tag_numbers: []`.

```bash
curl -s 'http://127.0.0.1:8000/api/monitoring_memorandum?tag_number_ids=all' -H "Authorization: Bearer $TOKEN"
curl -s 'http://127.0.0.1:8000/api/monitoring_memorandum?search=all' -H "Authorization: Bearer $TOKEN"
```
Expected: permintaan pertama hanya mengembalikan baris `all`; permintaan kedua `meta.total` = `0` (sentinel tidak ikut ter-search).

```bash
curl -s -X PUT http://127.0.0.1:8000/api/monitoring_memorandum/1 -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"nota_dinas":18}'
```
Expected: HTTP 200, `nota_dinas` = `18`, `tag_number_ids` masih `43,44`, `no_notif` masih `4501` (partial update).

```bash
curl -s -X PUT http://127.0.0.1:8000/api/monitoring_memorandum/1 -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"tanggal_issue":"2026-12-01"}'
```
Expected: HTTP 422 dengan `errors.tanggal_issue` berisi `may not be a date in the future`.

```bash
curl -s http://127.0.0.1:8000/api/monitoring_memorandum/dashboard -H "Authorization: Bearer $TOKEN"
```
Expected: `data.bobot` berisi tahun 2026, `data.backlog` empat entri `1–30`, `31–60`, `61–90`, `91+`.

```bash
curl -s -D mm-export-headers.txt -o mm-export.xlsx 'http://127.0.0.1:8000/api/monitoring_memorandum/export?tag_number_ids=all' -H "Authorization: Bearer $TOKEN"
```
Expected: HTTP 200, `Content-Type` `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, file `.xlsx` valid dengan 13 heading; hanya baris `all` yang terekspor.

- [ ] **Step 6: Bersihkan data uji**

Run:
```bash
php artisan tinker --execute="\App\Models\MonitoringMemorandum::query()->whereIn('issue_operasional',['Kebocoran flange line A','Insulasi thermal rusak'])->delete();"
```
Expected: `2`. Hapus juga file uji `mm-export-headers.txt` dan `mm-export.xlsx` dari root project.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/MonitoringMemorandumController.php routes/api.php
git commit -m "feat(monitoring-memorandum): add controller, routes and verify CRUD"
```

---

## Self-Review Checklist (sudah dijalankan penulis plan)

- **Spec coverage:** migration & model (Task 1), `tag_number_ids` aturan + sentinel + search/filter (Task 2, 4, 8), `backlog_days` (Task 1, 3, 7, 8), bobot & dashboard (Task 3, 7, 8), validasi & partial update (Task 4, 8), resource (Task 5), export (Task 6, 8), routes & activity log (Task 1, 8). Tidak ada requirement spec tanpa task.
- **Placeholder scan:** tidak ada `TBD`, `TODO`, "implement later", "add appropriate error handling", atau "serupa dengan Task N". Semua langkah kode memuat kode lengkap.
- **Type consistency:** `BacklogDays::bucket(int): int`, `label(int): string`, `meta(int): array`; `TagNumberIds::applyFilter(Builder,string,?string): Builder`; `MonitoringMemorandumBobot::scoreSql(string): string`; `MonitoringMemorandumDashboardService::getDashboard(): array` dengan key `bobot`/`backlog`; `baseQuery(Request): Builder` dipakai `index()` dan `export()`. Nama konsisten di semua task.
- **Koreksi saat eksekusi Task 7:** accessor `getBacklogDaysAttribute()` awalnya selalu memanggil `forDate()`, ternyata menimpa alias SQL `backlog_days` pada query dashboard yang tidak memilih `tanggal_issue` (semua baris jadi bucket `1–30`). Sudah diperbaiki di model, spec, dan plan: accessor menerima `$value` dari alias dan hanya fallback ke `forDate()` bila alias tidak ada. Verifikasi ulang menghasilkan bucket `3/1/0/2`.
- **Verifikasi terhadap codebase nyata (sudah dicek):** `ApiResource::pagination($paginator, $resourceClass)` sesuai pemakaian; `BaseRequest` tidak meng-override `authorize()`, jadi kedua FormRequest wajib menambahkan `authorize(): bool` (sudah ada di Task 4); `ValidationRuleParser::explodeRules()` memecah rule string pada `|`, jadi rule `regex` dengan alternasi `all|\d+` ditulis array-form (sudah ada di Task 4); tabel `tag_numbers` dan FK `historical_memorandum` sesuai migration existing; `AppServiceProvider` memakai wildcard listener `eloquent.*: *`, sehingga tidak perlu registrasi observer per model; `ActivityModule::label('MonitoringMemorandum')` otomatis menjadi "Monitoring Memorandum" tanpa ubah config.
