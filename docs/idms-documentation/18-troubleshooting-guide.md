# 18. Troubleshooting Guide

Panduan penyelesaian masalah umum dalam pengembangan dan operasional IDMS Backend.

---

## 1. JWT Issues

### Token Expired

**Gejala:** Request gagal dengan error `Token has expired` atau status 401.

**Solusi:**
- Logout dari aplikasi
- Login ulang untuk mendapatkan token baru
- Pastikan client menyimpan token dengan benar dan melakukan refresh sebelum expired

### Invalid Token

**Gejala:** Request gagal dengan error `Token is invalid` atau `Malformed token`.

**Solusi:**
- Pastikan header `Authorization` menggunakan format: `Bearer {token}`
- Cek apakah token tidak terpotong atau mengandung karakter aneh
- Pastikan token diambil dari response login yang valid
- Periksa apakah signature token cocok dengan secret di `.env` (`JWT_SECRET`)

### CORS Errors

**Gejala:** Browser menampilkan error CORS di console, request OPTIONS gagal.

**Solusi:**
- Cek konfigurasi di `config/cors.php`
- Pastikan `allowed_origins` mencakup domain frontend
- Pastikan `allowed_methods` mencakup method yang digunakan (GET, POST, PUT, DELETE)
- Pastikan `allowed_headers` mencakup `Authorization`, `Content-Type`, `Accept`
- Jika menggunakan credentials, pastikan `supports_credentials` = `true`

---

## 2. File Upload Issues

### File Terlalu Besar

**Gejala:** Upload gagal dengan error 413 Request Entity Too Large atau pesan validasi ukuran file.

**Solusi:**
- Cek dan sesuaikan `upload_max_filesize` di `php.ini`
- Cek `post_max_size` di `php.ini` (harus >= `upload_max_filesize`)
- Cek validasi ukuran file di controller/model jika ada batasan custom
- Restart web server setelah mengubah `php.ini`

### Path Tidak Ditemukan

**Gejala:** Error `File not found` atau `No such file or directory` saat upload.

**Solusi:**
- Pastikan direktori `public/` dan subdirektori target writable
- Cek permission folder: `chmod -R 775 public/`
- Pastikan path penyimpanan sesuai konfigurasi di `.env` atau `config/filesystems.php`
- Buat direktori jika belum ada: `storage:link`

### Cascading Delete

**Gejala:** File ikut terhapus saat record dihapus (meskipun tidak diinginkan), atau file tetap ada meskipun record sudah dihapus.

**Penjelasan:**
- Model yang extend `BaseModel` memiliki behavior cascading delete untuk file
- Saat record dihapus, file terkait juga dihapus dari storage
- Pastikan logika bisnis mempertimbangkan hal ini
- Jika ingin mempertahankan file, pertimbangkan soft delete atau flag `is_deleted`

---

## 3. Database Issues

### Connection Refused

**Gejala:** Error `SQLSTATE[HY000] [2002] Connection refused` atau `Access denied`.

**Solusi:**
- Pastikan `.env` memiliki konfigurasi benar: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Pastikan database server berjalan
- Cek apakah port yang dikonfigurasi benar (default MySQL: 3306)
- Jika menggunakan MariaDB, pastikan koneksi kompatibel

### Table Not Found

**Gejala:** Error `Table 'xxx' doesn't exist`.

**Solusi:**
- Jalankan migrasi: `php artisan migrate`
- Jika ada error migration, cek log di `storage/logs/laravel.log`
- Pastikan database yang digunakan sesuai `.env`
- Untuk fresh migration (HATI-HATI, data hilang): `php artisan migrate:fresh`

### Duplicate Entry

**Gejala:** Error `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`.

**Solusi:**
- Cek unique constraints di migration file
- Pastikan data yang diinput tidak duplikat (berdasarkan field unique)
- Jika menggunakan import, pastikan data sumber tidak memiliki duplikat
- Gunakan `INSERT ... ON DUPLICATE KEY UPDATE` jika perlu upsert

---

## 4. Import/Export Issues

### Excel Parsing Error

**Gejala:** Error saat membaca file Excel, pesan parsing gagal.

**Solusi:**
- Pastikan format file `.xlsx` atau `.xls` (bukan `.csv` atau format lain)
- Pastikan library `maatwebsite/excel` terinstall dan terkonfigurasi
- Cek apakah file Excel tidak corrupt (buka manual di Excel)
- Pastikan sheet name sesuai yang diharapkan program

### Memory Limit

**Gejala:** Error `Allowed memory size of X bytes exhausted` saat import data besar.

**Solusi:**
- Gunakan batch import: bagi data menjadi chunk kecil
- Contoh: `$reader->chunk(100, function ($results) { ... })`
- Tingkatkan `memory_limit` di `php.ini` jika diperlukan
- Gunakan queue untuk import skala besar

### Tag Number Not Found

**Gejala:** Import gagal karena Tag Number tidak ditemukan di database.

**Solusi:**
- Pastikan Tag Number sudah ada di database sebelum import data terkait
- Periksa apakah Tag Number sudah diimport sebelumnya
- Cek format Tag Number sesuai dengan yang ada di database (case-sensitive)
- Gunakan relasi foreign key untuk validasi ketersediaan data

---

## 5. Performance Issues

### Slow Queries

**Gejala:** Request lambat, response time tinggi (> 2 detik).

**Solusi:**
- Gunakan eager loading untuk relasi: `Model::with(['relation1', 'relation2'])->get()`
- Tambahkan index pada kolom yang sering di-query
- Gunakan `select()` untuk mengambil hanya kolom yang diperlukan
- Pertimbangkan caching untuk data yang jarang berubah

### N+1 Queries

**Gejala:** Banyak query kecil dijalankan dalam satu request, terlihat di log.

**Solusi:**
- Cek query log: `DB::enableQueryLog()` lalu lihat `DB::getQueryLog()`
- Gunakan eager loading: `with()` saat fetch data dengan relasi
- Hindari akses relasi di dalam loop tanpa eager loading
- Gunakan Laravel Debugbar untuk analisis query

---

## 6. Common Code Patterns

### Status Convention

**PENTING:** Konvensi status di IDMS Backend:
- `0` = **Aktif** (enabled/active)
- `1` = **Nonaktif** (disabled/inactive)

**Inverted dari konvensi umum!** Jangan asumsikan 1=aktif. Selalu cek migration dan model untuk memastikan.

### BaseModel Logging

- Semua model harus extend `BaseModel`
- `BaseModel` otomatis melakukan logging untuk operasi CRUD
- Tidak perlu menambahkan logging manual di setiap model
- Log tersimpan di `system_logs` table

### Dual Logging

IDMS Backend menggunakan dual logging:
1. **BaseModel**: Log operasi CRUD (create, update, delete) otomatis
2. **GlobalActivityObserver**: Log aktivitas user secara global

Keduanya berjalan bersamaan. Jika ada duplikat log, pastikan logging tidak ditambahkan manual di controller.

---

## Tips Umum

- Selalu cek `storage/logs/laravel.log` untuk detail error
- Gunakan `php artisan tinker` untuk debugging query dan data
- Jalankan `php artisan config:clear` setelah mengubah `.env`
- Gunakan `php artisan route:list` untuk melihat semua route yang tersedia
- Pastikan `php artisan cache:clear` jika ada perubahan konfigurasi
