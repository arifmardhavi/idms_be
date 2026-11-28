# Design: Tambah Field `rev` pada Fitur EMS

## Ringkasan

Menambahkan satu field baru `rev` pada fitur EMS (tabel `ems`, model `Ems`, API `EmsController`). Tipe data string dengan nilai default `"0"`. Field bersifat opsional saat input: bila tidak dikirim, database yang mengisi `"0"`; bila dikirim, harus berupa string.

## Keputusan

- **Pendekatan yang dipilih:** migration baru untuk menambah kolom pada tabel yang sudah ada. Migration lama `create_ems_table` tidak diubah agar perubahan tetap diterapkan pada database yang sudah berjalan.
- **Scope:** database + model + API + dokumentasi.
- **Kolom:** `string`, `NOT NULL`, `DEFAULT '0'`, ditempatkan setelah `no_ems` (`after('no_ems')`).
- **Validasi API:** `sometimes|string|max:255` pada `store()` dan `update()`. Tidak memakai `nullable` agar `null` eksplisit ditolak dan tidak melanggar constraint `NOT NULL`.

## Komponen yang Diubah

### 1. Migration baru

File: `database/migrations/2026_11_28_000000_add_rev_to_ems_table.php`

- `up()`: `Schema::table('ems', fn (Blueprint $table) => $table->string('rev')->default('0')->after('no_ems'))`
- `down()`: `Schema::table('ems', fn (Blueprint $table) => $table->dropColumn('rev'))`

Mengikuti pola migration inkremental yang sudah ada, misalnya `2026_10_13_000000_add_set_pressure_to_monitoring_psv_table.php`.

### 2. Model

File: `app/Models/Ems.php`

- Tambahkan `'rev'` ke array `$fillable` setelah `'no_ems'`.

### 3. Controller

File: `app\Http\Controllers\EmsController.php`

- `store()`: tambah aturan `'rev' => 'sometimes|string|max:255'`.
- `update()`: tambah aturan `'rev' => 'sometimes|string|max:255'`.

Perilaku:

- `store` tanpa `rev` → kolom terisi `"0"` oleh default database.
- `store` dengan `rev: "1"` → tersimpan `"1"`.
- `update` tanpa `rev` → nilai lama tidak berubah.
- `rev: null` atau tipe non-string → response 422.

Response API sudah mengembalikan seluruh atribut model (`data` => `$ems` / `$ems->load('tag_number')`), sehingga `rev` otomatis tampil tanpa perubahan resource.

### 4. Dokumentasi

- `docs/idms-documentation/16-database-schema.md`: tambah baris `rev | varchar | NOT NULL, DEFAULT '0' | Revisi EMS` pada tabel `ems`.
- `docs/api/ems.postman_collection.json`: tambah field `rev` (text, opsional, contoh `"0"`) pada formdata request Store dan Update.

## Testing & Verifikasi

1. `php artisan migrate` — migration berhasil dijalankan.
2. Query read-only untuk struktur kolom: kolom `rev` bertipe `varchar`, `NOT NULL`, default `'0'`.
3. Uji API:
   - POST `/api/ems` tanpa `rev` → `data.rev === "0"`.
   - POST `/api/ems` dengan `rev: "2"` → `data.rev === "2"`.
   - PUT `/api/ems/{id}` tanpa `rev` → nilai `rev` tidak berubah.
   - POST/PUT dengan `rev: null` → 422.
4. `php artisan migrate:rollback` pada migration ini → kolom `rev` terhapus (verifikasi `down()`).

## Non-Goals

- Tidak mengubah logika pencarian/filter EMS.
- Tidak mengubah migration `create_ems_table` atau dump SQL lama.
- Tidak menambah kolom `rev` ke tabel lain.
