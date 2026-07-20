# Arsitektur Sistem IDMS Backend

## 1. Tech Stack

| Komponen       | Versi           | Keterangan                                      |
|----------------|-----------------|--------------------------------------------------|
| PHP            | ^8.1            | Minimum PHP version                              |
| Laravel        | ^10.10          | Framework utama                                  |
| MySQL          | 8.0             | Database dengan charset `utf8mb4_unicode_ci`, strict mode |
| JWT Auth       | tymon/jwt-auth ^2.2 | Autentikasi utama via JSON Web Token         |
| Sanctum        | laravel/sanctum ^3.3 | Terinstall tapi bukan primary auth          |
| Excel          | maatwebsite/excel ^3.1 | Import/export data Excel                   |
| HTTP Client    | guzzlehttp/guzzle ^7.2 | HTTP client untuk integrasi eksternal     |

## 2. Arsitektur Aplikasi

Sistem ini dirancang sebagai **RESTful API** yang melayani **Single Page Application (SPA)** berbasis **Next.js**.

- **Backend**: Laravel 10.x (PHP 8.1+) — RESTful API
- **Frontend**: Next.js SPA — URL: `http://192.168.1.152:9999`
- **Semua API routes** menggunakan prefix `/api`

Autentikasi menggunakan **JWT (JSON Web Token)** via guard `api` dengan driver `jwt`. User provider menggunakan Eloquent dengan model `App\Models\User`.

## 3. Dependensi Lengkap

### Production Dependencies

| Package                      | Versi     | Keterangan                                      |
|------------------------------|-----------|--------------------------------------------------|
| `php`                        | ^8.1      | Runtime PHP minimum                              |
| `laravel/framework`          | ^10.10    | Core Laravel framework                           |
| `laravel/sanctum`            | ^3.3      | API token authentication (bukan primary auth)    |
| `laravel/tinker`             | ^2.8      | REPL interaktif untuk debugging                  |
| `tymon/jwt-auth`             | ^2.2      | JWT authentication provider                      |
| `maatwebsite/excel`          | ^3.1      | Import/export file Excel (.xlsx, .xls, .csv)     |
| `guzzlehttp/guzzle`          | ^7.2      | HTTP client untuk request ke API eksternal       |

### Development Dependencies

| Package                      | Versi     | Keterangan                                      |
|------------------------------|-----------|--------------------------------------------------|
| `fakerphp/faker`             | ^1.9.1    | Generate data dummy untuk testing                |
| `laravel/pint`               | ^1.0      | Code style fixer (PHP-CS Fixer wrapper)          |
| `laravel/sail`               | ^1.18     | Docker development environment                   |
| `mockery/mockery`            | ^1.4.4    | Mock object framework untuk unit testing         |
| `nunomaduro/collision`       | ^7.0      | Error reporting yang lebih baik di CLI           |
| `phpunit/phpunit`            | ^10.1     | Testing framework                                |
| `spatie/laravel-ignition`    | ^2.0      | Error page yang lebih informatif (dev only)      |

## 4. Konfigurasi Environment

### Database

| Variable          | Nilai Default | Keterangan                    |
|-------------------|---------------|-------------------------------|
| `DB_CONNECTION`   | `mysql`       | Driver database               |
| `DB_HOST`         | `127.0.0.1`  | Host database                 |
| `DB_PORT`         | `3306`        | Port database                 |
| `DB_DATABASE`     | `laravel`     | Nama database                 |
| `DB_USERNAME`     | `root`        | Username database             |
| `DB_PASSWORD`     | (kosong)      | Password database             |

MySQL dikonfigurasi dengan:
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Strict mode**: `true`

### JWT

| Variable          | Nilai Default | Keterangan                    |
|-------------------|---------------|-------------------------------|
| `JWT_SECRET`      | —             | Secret key untuk sign token (wajib diisi) |
| `JWT_TTL`         | `360`         | Token expiry (menit)          |
| `JWT_REFRESH_TTL` | `20160`       | Refresh window (menit)        |
| `JWT_ALGO`        | `HS256`       | Hashing algorithm             |

### Frontend / CORS

| Variable          | Nilai Default              | Keterangan                    |
|-------------------|---------------------------|-------------------------------|
| `FRONTEND_URL`    | `http://192.168.1.152:9999` | URL frontend SPA           |

## 5. CORS Configuration

CORS dikonfigurasi di `config/cors.php` untuk mengizinkan akses dari SPA Frontend.

| Setting                | Nilai                                       |
|------------------------|---------------------------------------------|
| `paths`                | `api/*`, `coi/*`, `sanctum/csrf-cookie`     |
| `allowed_methods`      | `*`                                         |
| `allowed_origins`      | `*`, `http://192.168.1.152:9999`            |
| `allowed_headers`      | `*`                                         |
| `exposed_headers`      | `Content-Disposition`                       |
| `max_age`              | `0`                                         |
| `supports_credentials` | `true`                                      |

**Catatan**: Header `Content-Disposition` di-expose agar frontend dapat mengakses nama file saat download.

## 6. File Storage

File disimpan di direktori `public/` menggunakan `FileHelper` helper.

### Lokasi Penyimpanan

File disimpan langsung ke folder di dalam `public/` menggunakan `move()` method, bukan Laravel Storage facade.

### Penamaan File

Format: `{nama_original}_{tanggal}_{versi}.{ext}`

Contoh: `kontrak_20072026_0.pdf`

- **nama_original**: Nama file tanpa ekstensi
- **tanggal**: Format `ddMMyyyy`
- **versi**: Dimulai dari 0, auto-increment jika file sudah ada
- **ext**: Ekstensi file original

### FileHelper

Lokasi: `app/Helpers/FileHelper.php`

| Method                    | Fungsi                                              |
|---------------------------|-----------------------------------------------------|
| `uploadWithVersion()`     | Upload file dengan auto-versioning                  |
| `deleteFile()`            | Hapus file dari storage                             |
| `folderSize()`            | Hitung total ukuran folder                           |
| `downloadFile()`          | Download file dengan Content-Disposition header      |

## 7. Queue & Scheduler

### Queue

- **Driver**: `sync` (synchronous)
- Tidak ada async queue processing
- Semua job dieksekusi secara langsung tanpa delay

### Scheduler

- Tidak ada scheduled tasks yang dikonfigurasi
- Tidak ada cron job aktif

## 8. JWT Configuration

### Token Lifecycle

| Parameter             | Nilai     | Keterangan                                     |
|-----------------------|-----------|-------------------------------------------------|
| TTL (Time To Live)    | 360 menit | Token valid selama 6 jam                        |
| Refresh TTL           | 20160 menit | Token dapat di-refresh dalam 2 minggu         |
| Algorithm             | HS256     | HMAC-SHA256 (symmetric key)                    |
| Blacklist             | Enabled   | Token yang logout dimasukkan ke blacklist       |
| Lock Subject          | Enabled   | Mencegah impersonation antar model user        |
| Leeway                | 0 detik   | Toleransi clock skew                            |

### Required Claims

Token harus memiliki claim berikut:
- `iss` (Issuer)
- `iat` (Issued At)
- `exp` (Expiration Time)
- `nbf` (Not Before)
- `sub` (Subject)
- `jti` (JWT ID)

### Blacklist

- **Blacklist enabled**: `true`
- **Grace period**: `0` detik
- **Decrypt cookies**: `false`

### Provider

| Komponen            | Class                                                |
|----------------------|------------------------------------------------------|
| JWT Provider         | `Tymon\JWTAuth\Providers\JWT\Lcobucci::class`       |
| Auth Provider        | `Tymon\JWTAuth\Providers\Auth\Illuminate::class`    |
| Storage Provider     | `Tymon\JWTAuth\Providers\Storage\Illuminate::class` |

## 9. Autentikasi Guard

Guard default dikonfigurasi sebagai `api` dengan driver `jwt`:

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

User provider menggunakan Eloquent dengan model `App\Models\User`.

Password reset menggunakan tabel `password_reset_tokens` dengan expiry 60 menit dan throttle 60 detik.
