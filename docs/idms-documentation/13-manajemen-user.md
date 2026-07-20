# Manajemen User

Modul manajemen user mengatur pengguna sistem, fitur-fitur yang tersedia, hak akses per fitur, serta pemetaan hak akses ke masing-masing user. Sistem ini menggunakan **JWT (JSON Web Token)** untuk autentikasi dan menerapkan mekanisme **role-based access control (RBAC)** berbasis level_user.

## 1. Arsitektur Hak Akses

```
Feature (Fitur)
 └── HakAkses (Hak Akses per Fitur)
      └── UserHakAkses (Pemetaan ke User)

User
 ├── hak_akses → UserHakAkses[] → HakAkses[] → Feature
 ├── contracts → Contract[] (untuk vendor)
 └── openFileActivities → OpenFileActivity[]
```

Empat model utama bekerja bersama untuk membentuk sistem manajemen user dan hak akses:

| Model | Tabel | Keterangan |
|-------|-------|------------|
| `User` | `users` | Data pengguna sistem |
| `Feature` | `features` | Daftar fitur modul |
| `HakAkses` | `hak_akses` | Hak akses spesifik per fitur |
| `UserHakAkses` | `user_hak_akses` | Pemetaan hak akses ke user |

## 2. User

**Model:** `app/Models/User.php`
**Controller:** `app/Http/Controllers/UserController.php`
**Resource:** `app/Http/Resources/UserResource.php`
**Tabel:** `users`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `fullname` | string, max:255 | - | Nama lengkap user (required) |
| `email` | string, max:255 | - | Email user (required, unique) |
| `username` | string, max:255 | - | Username untuk login (required, unique) |
| `password` | string | - | Password (required, auto-hash, hidden) |
| `level_user` | integer | - | Level user: 1=Admin, 2=User, 3=Vendor, 4=Viewer All, 5=Viewer (required) |
| `status` | integer | - | Status: `1` = Aktif, `0` = Nonaktif (required) |

### Level User Mapping

| Nilai | Label | Keterangan |
|-------|-------|------------|
| `1` | Admin | Akses penuh ke semua modul |
| `2` | User | User biasa dengan hak akses tertentu |
| `3` | Vendor | Vendor dengan kontrak terkait |
| `4` | Viewer All | Melihat semua data (read-only) |
| `5` | Viewer | Melihat data terbatas (read-only) |

### Computed Attributes (Appends)

| Attribute | Tipe | Keterangan |
|-----------|------|------------|
| `total_file_open` | integer | Total jumlah file yang pernah dibuka user |
| `file_open_per_feature` | object | Breakdown file open per fitur (key: nama fitur, value: jumlah) |
| `total_activities` | integer | Total jumlah aktivitas/log user |
| `activities_per_feature` | object | Breakdown aktivitas per modul (key: nama modul, value: jumlah) |
| `akses_list` | array | Array ID `hak_akses` yang dimiliki user |

### Hidden Fields

Field `password` dan `hak_akses` tidak ditampilkan di response JSON.

### Relasi

- `hak_akses()` → `hasMany(UserHakAkses)` — Hak akses user
- `openFileActivities()` → `hasMany(OpenFileActivity)` — Aktivitas buka file
- `logActivities()` → `hasMany(LogActivity)` — Log aktivitas user
- `contracts()` → `belongsToMany(Contract)` — Kontrak lama
- `contract_news()` → `belongsToMany(ContractNew)` — Kontrak baru

### Password Auto-Hash

```php
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = Hash::make($value);
}
```

Password otomatis di-hash menggunakan bcrypt saat disimpan. Tidak ada field `password` yang dikembalikan di response.

### Active Scope

```php
public function scopeActive($query)
{
    return $query->where('status', 1);
}
```

Digunakan untuk filter user aktif: `User::active()->get()`.

### JWT Integration

User mengimplementasi `JWTSubject`:

| Method | Keterangan |
|--------|------------|
| `getJWTIdentifier()` | Mengembalikan primary key (`id`) |
| `getJWTCustomClaims()` | Custom claims: `id`, `fullname`, `email`, `username`, `level_user`, `status`, `hak_akses_list` |

### Contoh Response UserResource

```json
{
    "id": 1,
    "fullname": "Ahmad Faza",
    "email": "faza.ahmad@example.com",
    "username": "faza.ahmad",
    "level_user": 1,
    "status": 1,
    "contract_news": [1, 2],
    "total_file_open": 45,
    "file_open_per_feature": {
        "coi": 20,
        "plo": 15,
        "skhp": 10
    },
    "total_activities": 120,
    "activities_per_feature": {
        "contract_new": 60,
        "coi": 40,
        "plo": 20
    },
    "akses_list": [1, 2, 3]
}
```

## 3. Feature

**Model:** `app/Models/Feature.php`
**Controller:** `app/Http/Controllers/FeatureController.php`
**Tabel:** `features`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `feature` | string, max:255 | - | Nama fitur (required) |
| `group` | string, max:255, nullable | `null` | Pengelompokan fitur |

### Contoh Data

| feature | group |
|---------|-------|
| Contract | Contract Management |
| COI | Certificate of Inspection |
| PLO | Pressure Loading Test |
| SKHP | Surat Keterangan Hasil Pemeriksaan |

## 4. HakAkses

**Model:** `app/Models/HakAkses.php`
**Controller:** `app/Http/Controllers/HakAksesController.php`
**Tabel:** `hak_akses`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `feature_id` | integer (FK) | - | Foreign key ke `features.id` (required) |
| `hak_akses` | string, max:255 | - | Nama hak akses (required) |

### Computed Attributes (Appends)

| Attribute | Tipe | Keterangan |
|-----------|------|------------|
| `feature_name` | string/null | Nama fitur dari relasi Feature |
| `group_name` | string/null | Nama group dari relasi Feature |

### Relasi

- `feature()` → `belongsTo(Feature)` — Fitur terkait

## 5. UserHakAkses

**Model:** `app/Models/UserHakAkses.php`
**Controller:** `app/Http/Controllers/UserHakAksesController.php`
**Tabel:** `user_hak_akses`

### Fields

| Field | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| `user_id` | integer (FK) | - | Foreign key ke `users.id` (required) |
| `hak_akses_id` | integer (FK) | - | Foreign key ke `hak_akses.id` (required) |

### Computed Attributes (Appends)

| Attribute | Tipe | Keterangan |
|-----------|------|------------|
| `hak_akses_name` | string/null | Nama hak akses dari relasi HakAkses |
| `feature_name` | string/null | Nama fitur dari relasi HakAkses → Feature |
| `group_name` | string/null | Nama group dari relasi HakAkses → Feature |

### Relasi

- `user()` → `belongsTo(User)` — User terkait
- `hak_akses()` → `belongsTo(HakAkses)` — Hak akses terkait

### Behavior Store

Saat `store`, controller menghapus semua UserHakAkses lama untuk user tersebut terlebih dahulu (`delete`), lalu membuat ulang berdasarkan array `hak_akses` yang dikirim. Ini memastikan data selalu sinkron.

## 6. Super Users

Beberapa user ditetapkan sebagai **super user** yang memiliki akses wildcard ke seluruh fitur. Super user tidak dibatasi oleh mekanisme `UserHakAkses` — mereka secara inheren memiliki akses penuh.

| Username | Keterangan |
|----------|------------|
| `admin` | Administrator utama sistem |
| `superadmin` | Super administrator |
| `faza.ahmad` | Developer / administrator |

Super user menggunakan wildcard `*` yang berarti mengakses semua fitur tanpa perlu pemetaan `UserHakAkses`.

## 7. API Endpoints

Semua endpoint berada di prefix `/api` dan memerlukan autentikasi JWT.

### 7.1 Users (apiResource)

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/users` | Ambil semua user (dengan contract_news, openFileActivities) |
| `GET` | `/api/users/{id}` | Ambil satu user (dengan contract_news) |
| `POST` | `/api/users` | Buat user baru |
| `PUT` | `/api/users/{id}` | Update user |
| `DELETE` | `/api/users/{id}` | Hapus user |

### Custom Routes — Users

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `PUT` | `/api/users/nonactive/{id}` | Nonaktifkan user (set `status = 0`) |

### Validasi Store User

```php
'fullname' => 'required|string|max:255',
'email' => 'required|string|email|unique:users,email',
'username' => 'required|string|unique:users,username',
'password' => 'required|string|min:6',
'level_user' => 'required|numeric|in:1,2,3,4,5',
'status' => 'required|in:0,1',
'contract_new_id' => 'nullable|array|required_if:level_user,3',
'contract_new_id.*' => 'exists:contract_news,id',
```

### Validasi Update User

```php
'fullname' => 'sometimes|string|max:255',
'email' => 'sometimes|string|email|unique:users,email,{id}',
'username' => 'sometimes|string|unique:users,username,{id}',
'password' => 'nullable|string|min:6',
'level_user' => 'sometimes|in:1,2,3,4,5',
'status' => 'sometimes|in:0,1',
'contract_new_id' => 'nullable|string|required_if:level_user,3',
```

> **Catatan:** Password kosong (`""` atau `null`) tidak akan di-hash — field diabaikan. Jika `level_user` berubah dari 3 (vendor) ke non-3, relasi kontrak otomatis di-cleanup.

### 7.2 Features (apiResource)

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/features` | Ambil semua fitur |
| `GET` | `/api/features/{id}` | Ambil satu fitur |
| `POST` | `/api/features` | Buat fitur baru |
| `PUT` | `/api/features/{id}` | Update fitur |
| `DELETE` | `/api/features/{id}` | Hapus fitur |

### Custom Routes — Features

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/feature/group` | Ambil fitur dikelompokkan berdasarkan `group` |

### 7.3 Hak Akses (apiResource)

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/hak_akses` | Ambil semua hak akses (dengan relasi feature) |
| `GET` | `/api/hak_akses/{id}` | Ambil satu hak akses |
| `POST` | `/api/hak_akses` | Buat hak akses baru |
| `PUT` | `/api/hak_akses/{id}` | Update hak akses |
| `DELETE` | `/api/hak_akses/{id}` | Hapus hak akses |

### 7.4 User Hak Akses (apiResource)

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/user_hak_akses` | Ambil semua pemetaan user-hak akses |
| `GET` | `/api/user_hak_akses/{id}` | Ambil satu pemetaan |
| `POST` | `/api/user_hak_akses` | Buat/update pemetaan (replace all untuk user) |
| `PUT` | `/api/user_hak_akses/{id}` | Update satu pemetaan |
| `DELETE` | `/api/user_hak_akses/{id}` | Hapus satu pemetaan |

### Custom Routes — User Hak Akses

| Metode | Endpoint | Keterangan |
|--------|----------|------------|
| `GET` | `/api/user_hak_akses/user/{id}` | Ambil semua hak akses milik user tertentu |

### Store UserHakAkses — Request Body

```json
{
    "user_id": 1,
    "hak_akses": [1, 2, 3]
}
```

> **Perilaku:** Semua UserHakAkses lama untuk `user_id` tersebut akan dihapus terlebih dahulu, lalu dibuat ulang berdasarkan array `hak_akses`. Ini berfungsi sebagai "replace all" — array berisi ID hak akses baru yang diinginkan.

## 8. Flow Diagrams

```
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│   Feature    │────<│   HakAkses   │────<│ UserHakAkses│
│ (Modul/Menu) │     │ (Izin Akses) │     │ (Pemetaan)  │
└─────────────┘     └──────────────┘     └─────┬──────┘
                                                │
                                         ┌──────▼──────┐
                                         │    User     │
                                         │ (Pengguna)  │
                                         └─────────────┘
```

### Flow Pembuatan User

1. Buat User (`POST /api/users`)
2. Jika level_user = 3 (Vendor), sertakan `contract_new_id`
3. Buat Feature (`POST /api/features`)
4. Buat HakAkses untuk Feature (`POST /api/hak_akses`)
5. Pemetaan UserHakAkses (`POST /api/user_hak_akses`)

### Flow Nonaktifasi User

1. `PUT /api/users/nonactive/{id}` → `status = 0`
2. User tidak bisa login (dicek di middleware auth)
3. Data tetap ada di database untuk keperluan historis

## 9. Arsitektur Autentikasi

### JWT Token Flow

```
Login (POST /api/login)
  → AuthController::login()
  → JWT token dikembalikan
  → Client menyimpan token
  → Setiap request: Authorization: Bearer {token}

Protected Routes:
  → Middleware auth:api memverifikasi token
  → User teridentifikasi dari token claims
  → level_user dan hak_akses_list tersedia dari custom claims
```

### Custom Claims dalam Token

```php
[
    'id' => $user->id,
    'fullname' => $user->fullname,
    'email' => $user->email,
    'username' => $user->username,
    'level_user' => $user->level_user,
    'status' => $user->status,
    'hak_akses_list' => $user->hak_akses_list ?? [],
]
```
