# 14. Audit Trail

## Ringkasan

Sistem audit trail pada IDMS Backend mencatat setiap aktivitas CRUD (Create, Update, Delete) yang dilakukan pengguna terhadap data di sistem. Terdapat dua mekanisme logging yang bekerja secara simultan: **BaseModel boot events** dan **GlobalActivityObserver**. Selain itu, terdapat fitur **OpenFileActivity** untuk mencatat aktivitas pembukaan file oleh pengguna.

---

## 1. LogActivity

Tabel `log_activities` menyimpan seluruh catatan aktivitas pengguna.

### Struktur Kolom

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `user_id` | bigint, nullable | ID pengguna yang melakukan aktivitas (null jika sistem) |
| `module` | string | Nama model/modul yang dimanipulasi (misal: `User`, `TermBilling`) |
| `action` | string | Jenis aktivitas: `create`, `update`, `delete` |
| `changes` | json | Data perubahan dalam format `{field: {before, after}}` |
| `ip_address` | string, nullable | IP address pengguna saat melakukan aktivitas |
| `user_agent` | string, nullable | User agent browser pengguna |
| `created_at` | timestamp | Waktu aktivitas tercatat |

### Contoh Format `changes`

```json
{
  "name": {
    "before": null,
    "after": "Joko"
  },
  "email": {
    "before": "joko@old.com",
    "after": "joko@new.com"
  }
}
```

### Relasi

- `user()` → belongsTo `User`

---

## 2. Dua Mekanisme Logging

### Mekanisme 1: BaseModel Boot Events

**Lokasi:** `app/Models/BaseModel.php`

Model abstrak `BaseModel` mendefinisikan boot events yang otomatis terpicu pada setiap model yang mewarisinya:

```php
static::created(function ($model) { ... });   // → action: create
static::updating(function ($model) { ... });   // → action: update
static::deleted(function ($model) { ... });    // → action: delete
```

Setiap event akan memanggil `self::logActivity()` yang membuat record ke `log_activities`.

### Mekanisme 2: GlobalActivityObserver

**Lokasi:** `app/Observers/GlobalActivityObserver.php`
**Registrasi:** `app/Providers/AppServiceProvider.php` → method `boot()`

Observer ini didaftarkan secara global melalui event `Model`:

```php
Model::created(fn($model) => (new GlobalActivityObserver)->created($model));
Model::updated(fn($model) => (new GlobalActivityObserver)->updated($model));
Model::deleted(fn($model) => (new GlobalActivityObserver)->deleted($model));
```

Artinya, **setiap** model di aplikasi akan ter-observe, bukan hanya model yang extends `BaseModel`.

### Dampak: Dual Logging

Model yang **extends BaseModel** akan menghasilkan **2 record log** untuk setiap operasi CRUD — satu dari `BaseModel::boot()` dan satu lagi dari `GlobalActivityObserver`. Ini karena:

1. BaseModel boot event trigger → log ke `log_activities`
2. Eloquent event `Model` global event → `GlobalActivityObserver` juga log ke `log_activities`

Model yang **tidak** extends BaseModel hanya akan ter-log oleh `GlobalActivityObserver`.

---

## 3. BaseModel: Field Filtering & Guard

**Lokasi:** `app/Models/BaseModel.php`

### Ignored Fields (update/create)

Field berikut diabaikan dan tidak dicatat dalam perubahan:

```
created_at, updated_at, id, status
```

### Sensitive Fields (delete)

Pada saat delete, field sensitif berikut juga diabaikan:

```
password, remember_token, secret_key, id, status, created_at, updated_at
```

### Guard Against Self-Logging

`BaseModel` secara eksplisit mencegah infinite loop dengan memeriksa apakah model adalah instance `LogActivity`:

```php
if ($model instanceof LogActivity) {
    return; // skip, hindari recursive logging
```

`GlobalActivityObserver` juga memiliki guard yang sama:

```php
private function shouldLog($model)
{
    return !($model instanceof LogActivity);
}
```

---

## 4. GlobalActivityObserver: Module Alias & Field Filtering

**Lokasi:** `app/Observers/GlobalActivityObserver.php`

### Module Alias

Observer memiliki pemetaan nama model ke nama alias yang lebih mudah dibaca:

| Model | Alias |
|---|---|
| `TermBilling` | `Tagihan Termin` |

Jika tidak ada alias, nama class model asli digunakan.

### Ignored Fields

```
password, remember_token, api_token
```

### Fitur

- **created()**: Mencatat semua atribut model (kecuali ignored fields) dengan `before: null`
- **updated()**: Mencatat perubahan field (old/new), otomatis mengabaikan `updated_at`
- **deleted()**: Mencatat data sebelum dihapus dari `getOriginal()` (kecuali ignored fields)

---

## 5. OpenFileActivity

Tabel `open_file_activities` mencatat aktivitas pengguna saat membuka/mengakses file tertentu di sistem.

### Struktur Kolom

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `user_id` | bigint | ID pengguna (wajib, foreign key ke users) |
| `file_name` | string | Nama file yang dibuka |
| `features` | string | Fitur/fitur terkait akses file |
| `created_at` | timestamp | Waktu file dibuka |
| `updated_at` | timestamp | Waktu record diperbarui |

### Virtual Field

- `timestamp` (appended): Format tanggal `d F Y H:i:s` dari `created_at`

### Relasi

- `user()` → belongsTo `User`

---

## 6. API Endpoints

Seluruh endpoint berada di bawah prefix `/api` dan memerlukan autentikasi.

### Log Activity

| Method | Endpoint | Handler | Keterangan |
|---|---|---|---|
| GET | `/api/log_activities` | `LogActivityController@index` | Semua log aktivitas (terbaru ke lama) |
| GET | `/api/log_activities/user` | `LogActivityController@showByAllUsers` | Log aktivitas dikelompokkan per pengguna & modul |
| GET | `/api/log_activities/user/{user_id}` | `LogActivityController@showByUser` | Log aktivitas untuk pengguna tertentu + count per module |

### Open File Activity

| Method | Endpoint | Handler | Keterangan |
|---|---|---|---|
| GET | `/api/open_file_activity` | `OpenFileActivityController@index` | Semua aktivitas buka file |
| POST | `/api/open_file_activity` | `OpenFileActivityController@store` | Catat aktivitas buka file baru |
| GET | `/api/open_file_activity/{id}` | `OpenFileActivityController@show` | Detail aktivitas buka file |
| PUT | `/api/open_file_activity/{id}` | `OpenFileActivityController@update` | Perbarui aktivitas buka file |
| DELETE | `/api/open_file_activity/{id}` | `OpenFileActivityController@destroy` | Hapus aktivitas buka file |
| GET | `/api/open_file_activity/user/{id}` | `OpenFileActivityController@showByUserId` | Aktivitas buka file untuk pengguna tertentu |

### Response Format: showByAllUsers

```json
{
  "data": {
    "user_id_1": {
      "user": "Nama Pengguna",
      "modules": {
        "ModuleName": {
          "total_in_module": 5,
          "actions": {
            "create": { "count": 2, "logs": [...] },
            "update": { "count": 3, "logs": [...] }
          }
        }
      }
    }
  }
}
```

### Response Format: showByUser

```json
{
  "data": [
    {
      "id": 1,
      "user": "Nama Pengguna",
      "module": "TermBilling",
      "action": "update",
      "changes": { "name": { "old": "A", "new": "B" } },
      "ip_address": "127.0.0.1",
      "user_agent": "...",
      "time": "2 hours ago",
      "count_in_module": 5
    }
  ]
}
```
