# Autentikasi & Autorisasi

## Daftar Isi

- [Alur Login JWT](#alur-login-jwt)
- [Konfigurasi JWT](#konfigurasi-jwt)
- [Sistem Hak Akses (RBAC Custom)](#sistem-hak-akses-rbac-custom)
- [Level User](#level-user)
- [RoleMiddleware](#rolemiddleware)
- [Endpoint Auth](#endpoint-auth)
- [Catatan Penting](#catatan-penting)

---

## Alur Login JWT

### Endpoint

```
POST /api/login
```

### Request Body

```json
{
  "username": "string (required)",
  "password": "string (required)"
}
```

> **Perbedaan dari Laravel bawaan:** Login menggunakan **username**, bukan email.

### Proses Autentikasi

Autentikasi dilakukan di `app/Http/Controllers/AuthController.php` (`login` method, baris 46-113):

1. **Cek keberadaan username** — query ke tabel `users` berdasarkan `username`. Jika tidak ditemukan, return `404` dengan pesan "Username tidak terdaftar."

2. **Cek status user** — field `status` harus bernilai `1` (aktif). Jika tidak, return `403` dengan pesan "Akun tidak aktif."

3. **Verifikasi password** — menggunakan `Hash::check()`. Jika salah, return `401` dengan pesan "Password salah."

4. **Ambil hak akses** — query ke pivot table `user_hak_akses` relasi ke `hak_akses` untuk mendapatkan list hak akses user:

```php
$data = UserHakAkses::with('hak_akses')
    ->where('user_id', $user->id)
    ->get();

$user->hak_akses_list = $data
    ->pluck('hak_akses.hak_akses')
    ->filter()
    ->values()
    ->toArray();
```

5. **Super user check** — username tertentu otomatis mendapat wildcard `*` (akses semua fitur):

```php
$superUsers = ['admin', 'superadmin', 'faza.ahmad'];

if (in_array($user->username, $superUsers)) {
    $hakAksesList[] = '*';
    $user->hak_akses_list = $hakAksesList;
}
```

6. **Generate token** — `JWTAuth::fromUser($user)` menghasilkan JWT token.

### Response Sukses (200)

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "fullname": "Admin IDMS",
      "email": "admin@idms.com",
      "username": "admin",
      "level_user": 99,
      "status": 1,
      "hak_akses_list": ["*"]
    }
  }
}
```

### Custom Claims JWT

Didefinisikan di `app/Models/User.php` method `getJWTCustomClaims()` (baris 56-67):

```php
public function getJWTCustomClaims()
{
    return [
        'id' => $this->id,
        'fullname' => $this->fullname,
        'email' => $this->email,
        'username' => $this->username,
        'level_user' => $this->level_user,
        'status' => $this->status,
        'hak_akses_list' => $this->hak_akses_list ?? [],
    ];
}
```

### Menggunakan Token

Untuk endpoint yang dilindungi, sertakan header:

```
Authorization: Bearer <token>
```

---

## Konfigurasi JWT

File: `config/jwt.php`

| Parameter | Nilai Default | Deskripsi |
|---|---|---|
| `secret` | `env('JWT_SECRET')` | Kunci rahasia untuk signing token (symmetric) |
| `ttl` | `360` menit (6 jam) | Masa berlaku token |
| `refresh_ttl` | `20160` menit (14 hari) | Batas waktu refresh token |
| `algo` | `HS256` | Algoritma hashing |
| `lock_subject` | `true` | Menambahkan claim `prv` untuk mencegah impersonation antar model |
| `blacklist_enabled` | `true` | Mengaktifkan blacklist untuk invalidasi token |
| `blacklist_grace_period` | `0` detik | Grace period untuk parallel request |
| `leeway` | `0` detik | Toleransi clock skew |
| `decrypt_cookies` | `false` | Decrypt cookies JWT |
| `required_claims` | `['iss', 'iat', 'exp', 'nbf', 'sub', 'jti']` | Claim yang wajib ada di setiap token |

### Auth Guard

File: `config/auth.php`

```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

Guard default menggunakan driver `jwt` dengan Eloquent provider (`App\Models\User`).

---

## Sistem Hak Akses (RBAC Custom)

IDMS menggunakan sistem RBAC custom berbasis 3 tabel relasi, bukan Laravel Gates/Policies.

### Struktur Tabel

```
features ──< hak_akses ──< user_hak_akses >── users
```

### Model Feature

File: `app/Models/Feature.php`

| Field | Tipe | Deskripsi |
|---|---|---|
| `feature` | string | Nama fitur (contoh: "Contract", "COI") |
| `group` | string | Kelompok fitur |

### Model HakAkses

File: `app/Models/HakAkses.php`

| Field | Tipe | Deskripsi |
|---|---|---|
| `feature_id` | FK → features | Relasi ke tabel features |
| `hak_akses` | string | Nama hak akses (contoh: "read_contract", "write_coi") |

**Accessor otomatis:**
- `feature_name` — nama fitur dari relasi Feature
- `group_name` — nama group dari relasi Feature

### Model UserHakAkses (Pivot)

File: `app/Models/UserHakAkses.php`

| Field | Tipe | Deskripsi |
|---|---|---|
| `user_id` | FK → users | Relasi ke tabel users |
| `hak_akses_id` | FK → hak_akses | Relasi ke tabel hak_akses |

**Accessor otomatis:**
- `hak_akses_name` — nama hak akses
- `feature_name` — nama fitur terkait
- `group_name` — nama group terkait

### Cara Kerja

```
User (id=1)
  └── UserHakAkses (user_id=1, hak_akses_id=5)
        └── HakAkses (id=5, feature_id=2, hak_akses="write_contract")
              └── Feature (id=2, feature="Contract", group="Master Data")
```

Pada saat login, semua `hak_akses` yang dimiliki user dikumpulkan ke array `hak_akses_list` dan di-embed sebagai custom claim JWT. Super user (`admin`, `superadmin`, `faza.ahmad`) mendapat wildcard `*`.

---

## Level User

Tabel `users`, field `level_user`:

| Kode | Nama Level | Deskripsi |
|------|-----------|-----------|
| `99` | SUPER_ADMIN | Akses penuh ke semua fitur dan konfigurasi sistem |
| `1` | ADMIN | Akses administrator, bisa mengelola data master |
| `2` | INPUTER | Bisa melakukan input dan edit data |
| `3` | VENDOR | Akses terbatas untuk vendor/pihak ketiga |
| `4` | VIEWER_All | Melihat semua data tanpa batasan |
| `5` | VIEWER | Melihat data dengan pembatasan hak akses |

> **Catatan:** Saat ini pengecekan `level_user` di middleware **di-comment out** (lihat bagian RoleMiddleware).

---

## RoleMiddleware

File: `app/Http/Middleware/RoleMiddleware.php`

### Logic

```php
public function handle(Request $request, Closure $next, ...$levels)
{
    // Izinkan semua akses GET tanpa cek level
    if ($request->isMethod('get')) {
        return $next($request);
    }

    // Autentikasi user
    $user = JWTAuth::parseToken()->authenticate();

    // Cek status aktif
    if ($user->status != 1) {
        return response()->json(['error' => 'Akun tidak aktif'], 403);
    }

    // Cek akses berdasarkan level langsung (kecuali GET)
    // if (!in_array($user->level_user, $levels)) {
    //     return response()->json(['error' => 'Tidak memiliki akses'], 403);
    // }

    return $next($request);
}
```

### Perilaku

| HTTP Method | Behavior |
|-------------|----------|
| **GET** | Langsung lolos, tanpa cek autentikasi atau level |
| **POST/PUT/DELETE** | Cek JWT valid + cek `status == 1` |

### PENTING: Level Check Di-Comment Out

Baris 27-29 di-comment out:

```php
// if (!in_array($user->level_user, $levels)) {
//     return response()->json(['error' => 'Tidak memiliki akses'], 403);
// }
```

**Dampak:** Middleware `role:1,99` saat ini **tidak memblokir** user berdasarkan `level_user`. Semua user yang terautentikasi dan aktif (`status == 1`) dapat mengakses route yang dilindungi middleware ini, terlepas dari level-nya.

### Contoh Penggunaan di Route

```php
Route::middleware(['role:1,99'])->group(function () {
    Route::apiResource('units', UnitController::class)->only(['store', 'update', 'destroy']);
    // ...
});
```

Parameter `1,99` dimaksudkan untuk ADMIN dan SUPER_ADMIN, tetapi saat ini tidak diperiksa karena level check di-comment out.

---

## Endpoint Auth

### POST /api/login

Deskripsi di bagian [Alur Login JWT](#alur-login-jwt).

### POST /api/logout

```
POST /api/logout
Authorization: Bearer <token>
```

**Response (200):**

```json
{
  "success": true,
  "message": "Logout berhasil.",
  "data": null
}
```

**PENTING:** Route logout didefinisikan **di luar** group `auth:api` (`routes/api.php` baris 141):

```php
// Public Routes (No Auth)
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected Routes (Requires Auth)
Route::post('/logout', [AuthController::class, 'logout']);  // ← di luar middleware auth:api
Route::middleware(['auth:api'])->group(function () {
    Route::post('/me', [AuthController::class, 'me']);
    // ...
});
```

Meskipun route logout berada di luar middleware `auth:api`, method `logout()` tetap memanggil `JWTAuth::invalidate(JWTAuth::getToken())` yang membutuhkan token valid. Client diharapkan tetap mengirim header `Authorization: Bearer <token>`.

### POST /api/me

```
POST /api/me
Authorization: Bearer <token>
```

**Response (200):**

```json
{
  "success": true,
  "message": "User data retrieved successfully.",
  "data": {
    "id": 1,
    "fullname": "Admin IDMS",
    "email": "admin@idms.com",
    "username": "admin",
    "level_user": 99,
    "status": 1,
    "hak_akses_list": ["*"]
  }
}
```

---

## Catatan Penting

### 1. Dual Auth System

- **JWT (Primary):** Menggunakan `tymon/jwt-auth`. Guard driver diatur ke `jwt` di `config/auth.php`.
- **Sanctum (Installed):** Package `laravel/sanctum` terinstall dan trait `HasApiTokens` di-*use* di model `User`, tetapi tidak digunakan untuk autentikasi utama.

### 2. Logout di Luar Middleware Group

Route `POST /api/logout` didefinisikan di luar group `middleware(['auth:api'])`. Ini berarti route ini tidak melewati middleware `auth:api`, tetapi tetap membutuhkan token karena memanggil `JWTAuth::invalidate()`.

### 3. Tidak Ada Laravel Gates/Policies

Sistem autorisasi IDMS **tidak menggunakan** Laravel Gates atau Policies. Seluruh kontrol akses dilakukan melalui:
- `RoleMiddleware` (cek status aktif)
- Custom `hak_akses_list` pada JWT claims
- Implementasi manual di controller

### 4. Password Auto-Hash

Model `User` menggunakan mutator untuk auto-hash password:

```php
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = Hash::make($value);
}
```

### 5. Scope Active

Model `User` menyediakan scope untuk filter user aktif:

```php
public function scopeActive($query)
{
    return $query->where('status', 1);
}
```

### 6. Relasi Hak Akses di User Model

```php
// HasMany ke UserHakAkses
public function hak_akses()
{
    return $this->hasMany(UserHakAkses::class);
}

// Accessor: list ID hak akses
public function getAksesListAttribute()
{
    return $this->hak_akses
        ->pluck('hak_akses.id')
        ->filter()
        ->values()
        ->toArray();
}
```
