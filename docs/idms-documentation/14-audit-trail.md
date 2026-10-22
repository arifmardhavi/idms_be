# 14. Audit Trail

## Ringkasan

Sistem audit trail pada IDMS Backend mencatat setiap aktivitas pengguna terhadap data di sistem. Seluruh catatan disimpan pada satu tabel, yaitu `log_activities`.

Mekanisme pencatatan terdiri dari:

- **ActivityLogger** (`app/Services/ActivityLogger.php`) — helper `activity()` untuk mencatat aktivitas secara manual (login, logout, visit, view, download, export, import).
- **GlobalActivityObserver** (`app/Observers/GlobalActivityObserver.php`) — merekam otomatis setiap operasi CRUD pada seluruh model.

> **Catatan:** mekanisme `OpenFileActivity` (tabel `open_file_activities`) telah
> **dihapus**. Aktivitas membuka file kini dicatat pada `log_activities` dengan
> `action = 'view'` dan `module` = nama fitur. Lihat
> [13-manajemen-user.md](13-manajemen-user.md) untuk atribut turunan
> `total_activities` dan `activities_per_feature` pada model `User`.

---

## 1. LogActivity

**Lokasi:** `app/Models/LogActivity.php`

Tabel `log_activities` menyimpan seluruh catatan aktivitas pengguna.

### Struktur Kolom

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `user_id` | bigint, nullable | ID pengguna yang melakukan aktivitas (null = sistem / guest), FK ke `users` dengan `ON DELETE SET NULL` |
| `action` | enum | Jenis aktivitas (lihat tabel di bawah) |
| `module` | varchar(100) | Nama modul/fitur kanonikal (misal: `Contract`, `Monitoring PSV`) |
| `record_id` | bigint, nullable | ID record yang berkaitan |
| `record_label` | varchar(255), nullable | Label human-readable record (misal: `COI-2025-0012`) |
| `description` | text, nullable | Kalimat ringkasan aktivitas, digenerate otomatis bila kosong |
| `metadata` | json, nullable | Detail mentah dalam bentuk JSON |
| `ip_address` | varchar(45), nullable | Alamat IP pengguna |
| `user_agent` | text, nullable | User agent browser |
| `created_at` | timestamp | Waktu aktivitas tercatat |
| `updated_at` | timestamp | Waktu record diperbarui |

### Nilai `action`

| Nilai | Label di UI | Keterangan |
|---|---|---|
| `login` | Login | Pengguna berhasil login |
| `logout` | Logout | Pengguna logout |
| `visit` | Mengunjungi Fitur | Pengguna membuka fitur, dicatat bila durasi ≥ 5 detik |
| `view` | Melihat File | Pengguna melihat file |
| `download` | Mengunduh File | Pengguna mengunduh file |
| `export` | Export Data | Pengguna melakukan export |
| `create` | Menambah Data | Record baru dibuat |
| `update` | Mengubah Data | Record diperbarui |
| `delete` | Mengapus Data | Record dihapus |
| `import` | Import Data | Pengguna melakukan import |

### Contoh Format `metadata`

Untuk `action = 'update'`, `metadata` berisi daftar field yang berubah:

```json
{
  "name": {
    "old": "Joko",
    "new": "Joko Santoso"
  },
  "email": {
    "old": "joko@old.com",
    "new": "joko@new.com"
  }
}
```

Untuk `action = 'create'` / `delete`, `metadata` berisi seluruh atribut record
(tanpa field sensitif).

### Relasi

- `user()` → belongsTo `User`

---

## 2. ActivityLogger

**Lokasi:** `app/Services/ActivityLogger.php`
**Helper:** `app/Helpers/activity.php` → fungsi global `activity()`

Helper `activity()` membuat satu record `log_activities` dengan penulisan ringkas:

```php
activity()->log('view', 'Coi', [
    'recordId'    => $coi->id,
    'recordLabel' => $coi->no_certificate,
    'object'      => $fileName,
    'metadata'    => ['size' => $size],
]);
```

### Opsi yang Didukung

| Key | Tipe | Keterangan |
|---|---|---|
| `recordId` | int/string/null | ID record, disimpan ke `record_id` |
| `recordLabel` | string/null | Label record, disimpan ke `record_label` |
| `description` | string/null | Kalimat lengkap; bila kosong, digenerate otomatis |
| `metadata` | array/null | Detail mentah, disimpan ke `metadata` (JSON) |
| `object` | string/null | Objek yang dirujuk (nama file, jumlah baris) untuk penyusunan `description` |
| `userId` | int/null | Default `Auth::id()` |
| `allowGuest` | bool | Default `true` |

### Generasi `description`

Bila `description` tidak diisi, kalimat disusun dari `verbMap`:

| Action | Kalimat |
|---|---|
| `create` / `update` / `delete` | `{nama} {kata kerja} {record} di fitur {label}` |
| `import` | `{nama} import {objek} di fitur {label}` |
| `export` | `{nama} export data di fitur {label}` |
| `visit` | `{nama} mengunjungi fitur {label}` |
| `view` / `download` | `{nama} melihat file {objek} di fitur {label}` |

---

## 3. GlobalActivityObserver

**Lokasi:** `app/Observers/GlobalActivityObserver.php`
**Registrasi:** `app/Providers/AppServiceProvider.php` → method `boot()`

Observer didaftarkan secara global memakai wildcard listener sehingga menangkap
event untuk **semua** model konkret:

```php
Event::listen('eloquent.created: *', fn($eventName, $payload) => $observer->created($payload[0] ?? null));
Event::listen('eloquent.updated: *', fn($eventName, $payload) => $observer->updated($payload[0] ?? null));
Event::listen('eloquent.deleted: *', fn($eventName, $payload) => $observer->deleted($payload[0] ?? null));
```

### Model yang Dikecualikan

Observer tidak merekam model berikut:

```
LogActivity, UserHakAkses, MonitoringEquipmentLog, HakAkses, Feature
```

`LogActivity` dikecualikan untuk mencegah recursive logging (log menulis log).

### Ignored Fields

Field berikut tidak pernah masuk ke `metadata`:

```
password, remember_token, api_token
```

### `record_label` Otomatis

`record_label` diambil dari field "label" yang relevan per model
(misal `Coi` → `no_certificate`, `Contract` → `no_contract`,
`MonitoringPsv` → `record_label`). Bila tidak ada mapping atau nilainya kosong,
diakai fallback `#<id>`.

### Perilaku per Event

- **created()**: mencatat seluruh atribut model (kecuali ignored fields)
- **updated()**: mencatat hanya field yang berubah (`old` / `new`), `updated_at` diabaikan
- **deleted()**: mencatat data sebelum dihapus dari `getOriginal()`

---

## 4. ActivityModule (Normalisasi Label Modul)

**Lokasi:** `app/Support/ActivityModule.php`
**Config:** `config/log-activity.php`

`ActivityModule::label()` mengubah nilai `module` mentah menjadi label kanonikal
frontend. Urutan resolution:

1. Cocok dengan `config('log-activity.aliases')` (identifier BE → label FE)
2. Cocok dengan key `config('log-activity.features')` (path FE → label FE)
3. Sudah berupa label FE (idempotent)
4. Fallback prettify (`camelCase`/`snake_case` → Title Case)

Contoh: `SpkNew` → `Contract`, `BapkCoi` → `Coi`, `Ems` → `Fitur Ems`,
`Spk_progress` → `Contract`.

Config `features` juga menjadi **satu-satunya sumber** label fitur utama yang
sah, dipakai oleh `GET /activity/features` dan validasi `POST /activity/visit`.

---

## 5. API Endpoints

Seluruh endpoint berada di bawah prefix `/api` dan memerlukan autentikasi.

| Method | Endpoint | Handler | Keterangan |
|---|---|---|---|
| GET | `/api/activity` | `ActivityController@index` | Daftar log aktivitas (search + sort + filter tanggal + pagination) |
| POST | `/api/activity/visit` | `ActivityController@visit` | Catat kunjungan fitur (hanya bila durasi ≥ 5 detik) |
| GET | `/api/activity/features` | `ActivityController@features` | Daftar label fitur utama kanonikal |
| GET | `/api/activity/ranking/active` | `ActivityController@rankingActive` | Top-10 user paling aktif (view + download + visit + export + login) |
| GET | `/api/activity/ranking/contributor` | `ActivityController@rankingContributor` | Top-10 user kontributor (create + update + delete + import) |
| GET | `/api/activity/stats/overview` | `ActivityController@statsOverview` | Ringkasan KPI aktivitas |
| GET | `/api/activity/stats/trend` | `ActivityController@statsTrend` | Tren aktivitas per hari/bulan (`?group=day\|month`) |
| GET | `/api/activity/stats/by-action` | `ActivityController@statsByAction` | Distribusi per aksi |
| GET | `/api/activity/stats/by-module` | `ActivityController@statsByModule` | Distribusi per modul |
| GET | `/api/activity/stats/by-user` | `ActivityController@statsByUser` | Aktivitas per user (total + breakdown per aksi) |

### Query Parameter Umum

| Parameter | Tipe | Berlaku di | Keterangan |
|---|---|---|---|
| `from` | date | semua endpoint stats | Filter `created_at >=` tanggal |
| `to` | date | semua endpoint stats | Filter `created_at <=` tanggal |
| `user_id` | int | stats | Filter per pengguna |
| `module` | string | stats | Filter per modul |
| `search` | string | `/api/activity` | Mencari di `action`, `module`, `description`, dan label aksi |
| `sort_by` | string | `/api/activity` | `action`, `action_label`, `module`, `module_label`, `description` |
| `sort_order` | string | `/api/activity` | `asc` / `desc` (default `desc`) |
| `per_page` | int | `/api/activity` | 1–100, default 20 |

### Response Format: `/api/activity`

```json
{
  "success": true,
  "message": "Log aktivitas ditemukan.",
  "data": {
    "current_page": 1,
    "per_page": 20,
    "total": 505,
    "last_page": 26,
    "items": [
      {
        "id": 1,
        "user": { "id": 71, "fullname": "Nama Pengguna" },
        "action": "update",
        "action_label": "Mengubah Data",
        "module": "TermBilling",
        "module_label": "Tagihan Termin",
        "record_id": 12,
        "record_label": "COI-2025-0012",
        "description": "Ahmad Faza mengubah data COI-2025-0012 di fitur Tagihan Termin",
        "metadata": { "name": { "old": "A", "new": "B" } },
        "ip_address": "127.0.0.1",
        "time": "2026-09-01 10:12:00",
        "time_ago": "2 hours ago"
      }
    ]
  }
}
```

### Response Format: `/api/activity/stats/by-user`

```json
{
  "success": true,
  "data": [
    {
      "user_id": 1,
      "name": "Ahmad Faza",
      "total": 271,
      "breakdown": {
        "view": 120,
        "download": 40,
        "login": 15
      }
    }
  ]
}
```

---

## 6. Catatan Migration

Migrasi `2026_09_01_000000_rebuild_log_activities_table.php` melakukan rebuild
tabel `log_activities` (kolom `changes` diganti `record_id`, `record_label`,
`description`, `metadata`, serta `action` menjadi enum) dan sekaligus menghapus
tabel `open_file_activities`.
