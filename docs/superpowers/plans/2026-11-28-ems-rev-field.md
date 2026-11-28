# EMS Rev Field Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan field `rev` (string, default `"0"`) ke fitur EMS melalui database, model, API, dan dokumentasi.

**Architecture:** Migration inkremental menambah kolom `rev` ke tabel `ems` yang sudah ada; model `Ems` menerima mass-assignment untuk kolom tersebut; `EmsController` memvalidasi input opsional. Jika request tidak mengirim `rev`, constraint default database yang mengisi `"0"`.

**Tech Stack:** Laravel 10 (PHP), MySQL, PHPUnit / Laravel Test (DatabaseTransactions + JWT).

## Global Constraints

- Tipe kolom: `string` (varchar), `NOT NULL`, `DEFAULT '0'`.
- Posisi kolom: setelah `no_ems` (`after('no_ems')`).
- Aturan validasi: `sometimes|string|max:255` pada `store()` dan `update()`.
- Nama migration: `2026_11_28_000000_add_rev_to_ems_table.php`.
- Jangan mengubah `database/migrations/2026_09_02_161301_create_ems_table.php`.
- Pengujian memakai MySQL `idms` sesuai `.env`; `phpunit.xml` tidak mengaktifkan sqlite in-memory.

---

### Task 1: Kolom database dan mass-assignment model

**Files:**
- Create: `database/migrations/2026_11_28_000000_add_rev_to_ems_table.php`
- Modify: `app/Models/Ems.php:12-17`
- Test: `tests/Feature/EmsRevFieldTest.php`

**Interfaces:**
- Consumes: `App\Models\Ems::create()` dengan atribut `tag_number_id`, `no_ems`, `tanggal`, opsional `rev`.
- Produces: kolom `ems.rev` (`varchar`, `NOT NULL`, default `'0'`) dan `rev` di `$fillable` model `Ems`.

- [x] **Step 1: Buat file test yang masih gagal**

Buat `tests/Feature/EmsRevFieldTest.php` dengan isi lengkap:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ems;
use App\Models\Tag_number;
use App\Models\Type;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmsRevFieldTest extends TestCase
{
    use DatabaseTransactions;

    private function tagNumber(): Tag_number
    {
        $suffix = bin2hex(random_bytes(5));

        $category = Category::create(['category_name' => 'ems rev test '.$suffix]);
        $type = Type::create([
            'type_name' => 'ems rev test '.$suffix,
            'category_id' => $category->id,
        ]);
        $unit = Unit::create(['unit_name' => 'ems rev test '.$suffix]);

        return Tag_number::create([
            'unit_id' => $unit->id,
            'type_id' => $type->id,
            'tag_number' => 'EMS-REV-TEST-'.$suffix,
        ]);
    }

    public function test_new_ems_row_gets_rev_default_zero(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-001',
            'tanggal' => '2026-11-28',
        ]);

        $this->assertSame('0', $ems->fresh()->rev);
    }

    public function test_rev_can_be_set_to_a_string(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-002',
            'tanggal' => '2026-11-28',
            'rev' => '3',
        ]);

        $this->assertSame('3', $ems->fresh()->rev);
    }
}
```

- [x] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=EmsRevFieldTest`
Expected: FAIL — error SQL `Unknown column 'rev' in 'field list'` (dan/atau `MassAssignmentException` untuk `rev` pada test kedua).

- [x] **Step 3: Buat migration baru**

Buat `database/migrations/2026_11_28_000000_add_rev_to_ems_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ems', function (Blueprint $table) {
            $table->string('rev')->default('0')->after('no_ems');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ems', function (Blueprint $table) {
            $table->dropColumn('rev');
        });
    }
};
```

- [x] **Step 4: Tambah `rev` ke `$fillable` model `Ems`**

Pada `app/Models/Ems.php`, ubah:

```php
    protected $fillable = [
        'tag_number_id',
        'no_ems',
        'tanggal',
        'ems_file',
    ];
```

menjadi:

```php
    protected $fillable = [
        'tag_number_id',
        'no_ems',
        'rev',
        'tanggal',
        'ems_file',
    ];
```

- [x] **Step 5: Jalankan migration**

Run: `php artisan migrate`
Expected: batch baru berisi `2026_11_28_000000_add_rev_to_ems_table` dengan status `DONE`, tanpa error.

- [x] **Step 6: Jalankan test kembali**

Run: `php artisan test --filter=EmsRevFieldTest`
Expected: PASS (2 tests).

- [x] **Step 7: Commit**

```bash
git add database/migrations/2026_11_28_000000_add_rev_to_ems_table.php app/Models/Ems.php tests/Feature/EmsRevFieldTest.php
git commit -m "feat(ems): add rev column with default 0"
```

---

### Task 2: Validasi API dan dokumentasi

**Files:**
- Modify: `app/Http/Controllers/EmsController.php:72-77`, `app/Http/Controllers/EmsController.php:145-150`
- Modify: `tests/Feature/EmsRevFieldTest.php`
- Modify: `docs/idms-documentation/16-database-schema.md:654-663`
- Modify: `docs/api/ems.postman_collection.json:129-134`, `docs/api/ems.postman_collection.json:161-166`

**Interfaces:**
- Consumes: kolom `ems.rev` dan `$fillable` dari Task 1.
- Produces: `data.rev` pada response JSON endpoint `POST /api/ems` dan `PUT /api/ems/{id}`; field `rev` terdokumentasi di skema DB dan Postman.

- [x] **Step 1: Tambah test validasi API ke file test yang sudah ada**

Tambahkan `use` statements berikut di `tests/Feature/EmsRevFieldTest.php` setelah `use Tests\TestCase;`:

```php
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
```

Tambahkan method berikut di dalam class `EmsRevFieldTest` (setelah `test_rev_can_be_set_to_a_string`):

```php
    /** @return array<string, string> */
    private function authHeaders(): array
    {
        $suffix = bin2hex(random_bytes(5));

        $user = User::create([
            'fullname' => 'EMS rev test',
            'email' => 'ems_rev_'.$suffix.'@example.test',
            'username' => 'ems_rev_'.$suffix,
            'password' => 'secret123',
            'level_user' => 1,
            'status' => 1,
        ]);

        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
    }

    public function test_store_endpoint_defaults_rev_to_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-003',
            'tanggal' => '2026-11-28',
        ]);

        $response->assertCreated()->assertJsonPath('data.rev', '0');
    }

    public function test_store_endpoint_accepts_rev_string(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-004',
            'tanggal' => '2026-11-28',
            'rev' => '7',
        ]);

        $response->assertCreated()->assertJsonPath('data.rev', '7');
    }

    public function test_update_endpoint_keeps_rev_when_not_provided(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-005',
            'tanggal' => '2026-11-28',
            'rev' => '9',
        ]);

        $response = $this->withHeaders($this->authHeaders())->putJson('/api/ems/'.$ems->id, [
            'no_ems' => 'EMS-REV-005-UPDATED',
        ]);

        $response->assertOk()->assertJsonPath('data.rev', '9');
    }

    public function test_store_endpoint_rejects_null_rev(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-006',
            'tanggal' => '2026-11-28',
            'rev' => null,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('rev');
    }
```

- [x] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=EmsRevFieldTest`
Expected: 4 test baru FAIL — `rev: "7"` diabaikan karena tidak ada di rules (hasil `"0"`), dan `rev: null` diterima (201, bukan 422).

- [x] **Step 3: Tambah aturan validasi `rev` di `store()`**

Pada `app/Http\Controllers\EmsController.php`, ubah blok validator `store()`:

```php
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_ems' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'ems_file' => 'nullable|file',
        ]);
```

menjadi:

```php
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_ems' => 'required|string|max:255',
            'rev' => 'sometimes|string|max:255',
            'tanggal' => 'required|date',
            'ems_file' => 'nullable|file',
        ]);
```

- [x] **Step 4: Tambah aturan validasi `rev` di `update()`**

Pada `app/Http\Controllers\EmsController.php`, ubah blok validator `update()`:

```php
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'sometimes|required|exists:tag_numbers,id',
            'no_ems' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'ems_file' => 'sometimes|nullable|file',
        ]);
```

menjadi:

```php
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'sometimes|required|exists:tag_numbers,id',
            'no_ems' => 'sometimes|required|string|max:255',
            'rev' => 'sometimes|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'ems_file' => 'sometimes|nullable|file',
        ]);
```

- [x] **Step 5: Jalankan test sampai lulus**

Run: `php artisan test --filter=EmsRevFieldTest`
Expected: PASS (6 tests).

- [x] **Step 6: Update dokumentasi skema database**

Pada `docs/idms-documentation/16-database-schema.md`, di dalam tabel `ems`, sisipkan baris setelah `no_ems`:

```markdown
| rev | varchar | NOT NULL, DEFAULT '0' | Revisi EMS |
```

Hasil bagian tersebut:

```markdown
| no_ems | varchar | NOT NULL | Nomor EMS |
| rev | varchar | NOT NULL, DEFAULT '0' | Revisi EMS |
| tanggal | date | NULLABLE | Tanggal EMS |
```

- [x] **Step 7: Update Postman collection**

Pada `docs/api/ems.postman_collection.json`, di formdata request **Store (tambah)**, sisipkan setelah entri `no_ems`:

```json
                { "key": "rev", "value": "0", "type": "text", "description": "revisi EMS (opsional, default \"0\")" },
```

Pada formdata request **Update**, sisipkan setelah entri `no_ems`:

```json
                { "key": "rev", "value": "1", "type": "text", "description": "revisi EMS (opsional)" },
```

- [x] **Step 8: Commit**

```bash
git add app/Http/Controllers/EmsController.php tests/Feature/EmsRevFieldTest.php docs/idms-documentation/16-database-schema.md docs/api/ems.postman_collection.json
git commit -m "feat(ems): expose rev via API and docs"
```

---

### Task 3: Verifikasi akhir

**Files:** tidak ada perubahan file.

**Interfaces:**
- Consumes: kapabilitas dari Task 1 dan Task 2.
- Produces: bukti bahwa kolom, default, validasi, dan `down()` migration berfungsi.

- [x] **Step 1: Jalankan seluruh test suite**

Run: `php artisan test`
Expected: PASS (termasuk 6 test di `EmsRevFieldTest`).

- [x] **Step 2: Verifikasi struktur kolom di MySQL**

Gunakan tool MySQL read-only:

```sql
SHOW COLUMNS FROM ems LIKE 'rev';
```

Expected: satu baris dengan `Field = rev`, `Type = varchar(255)`, `Null = NO`, `Default = 0`.

- [x] **Step 3: Verifikasi `down()` migration**

Run (berurutan):

```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

Expected: setelah rollback, `SHOW COLUMNS FROM ems LIKE 'rev'` mengembalikan 0 baris; setelah migrate ulang, kolom kembali dengan default `'0'`.

- [x] **Step 4: Pastikan working tree bersih**

Run: `git status --short`
Expected: tidak ada output (semua perubahan sudah di-commit).

## Catatan Eksekusi

- `php artisan migrate` penuh gagal karena migration lama `monitoring_psv` belum tercatat di tabel `migrations` meski tabelnya sudah ada (masalah environment yang sudah ada sebelumnya). Migration `rev` diterapkan dengan `php artisan migrate --path=database/migrations/2026_11_28_000000_add_rev_to_ems_table.php`, dan cara yang sama dipakai setelah rollback verifikasi.
- `store()` memanggil `$ems->refresh()` sebelum response agar default `'0'` dari database ikut terkirim (Eloquent tidak mengisi atribut dari default DB setelah `create()`).
- Saat verifikasi akhir, `git status --short` juga menampilkan perubahan di luar task ini (`app/Support/BacklogDays.php`, `docs/api/monitoring_memorandum.postman_collection.json`, `docs/superpowers/specs/2026-11-23-monitoring-memorandum-design.md`, `tests/Unit/BacklogDaysBucketTest.php`). Perubahan tersebut tidak disentuh dan tidak di-commit.
