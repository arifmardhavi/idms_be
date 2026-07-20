# 19. Catatan Penting dan Known Issues

> Dokumen ini memuat masalah-masalah yang diketahui, konvensi khusus, strategi penyimpanan file, dan catatan deployment pada proyek IDMS Backend.

---

## 1. Known Issues

### 1.1 RoleMiddleware Level Check Disabled

- **File:** `app/Http/Middleware/RoleMiddleware.php` baris 27–29
- **Status:** Level check di-**COMMENT OUT**
- **Dampak:** Semua authenticated user memiliki akses write (create, update, delete), tanpa memperhatikan level role.
- **Fix:** Uncomment level check dan pastikan mapping level sudah benar sesuai konfigurasi role.

### 1.2 Dual Auth Packages (JWT + Sanctum)

- **Status:** Menggunakan JWT sebagai auth primary, namun Sanctum juga ter-install.
- **Potensi Masalah:** Konflik middleware, token handling yang ambigu, dan overhead maintenance kedua package.
- **Rekomendasi:** Tentukan satu package sebagai standar. Jika hanya API (stateless), pertimbangkan hanya gunakan JWT dan hapus Sanctum.

### 1.3 Dual Activity Logging

- **Status:** Terdapat dua mekanisme logging yang berjalan bersamaan:
  1. **BaseModel boot method** — log activity di setiap model.
  2. **GlobalActivityObserver** — observer global yang juga mencatat activity.
- **Dampak:** Setiap operasi create/update/delete menghasilkan **2 entri log** yang sama atau mirip.
- **Rekomendasi:** Pertimbangkan menggunakan salah satu saja untuk menghindari duplikasi data log.

### 1.4 Legacy vs New Contract System

- **Status:** Terdapat **dua sistem kontrak** yang berjalan paralel:
  - Legacy contract system (lama)
  - New contract system (baru)
- **Dampak:** Duplikasi logic, potensi inkonsistensi data, dan kebingungan developer.
- **Rekomendasi:** Rencanakan deprecate legacy system dan migrasi sepenuhnya ke new contract system.

### 1.5 Triplicated Readiness Pipeline

- **Status:** Sekitar **~30 model** memiliki method/methods yang berulang secara triplicated (contoh: `readiness`, `readinessCheck`, dll).
- **Dampak:** Code duplication sangat tinggi (~60+ controller methods dengan pola serupa), meningkatkan risiko bug dan meningkatkan effort maintenance.
- **Rekomendasi:** Refactor menjadi satu pipeline/trait yang reusable untuk semua model.

### 1.6 POST /api/logout Outside auth:api

- **Status:** Route `POST /api/logout` **tidak dilindungi** middleware `auth:api`.
- **Dampak:** Request logout dapat dikirim tanpa autentikasi, yang bisa memicu error atau logging yang tidak valid.
- **Fix:** Pindahkan route ke dalam group `auth:api` atau tambahkan middleware auth pada route tersebut.

### 1.7 Redundant Throttle

- **Status:** Rate limiting diterapkan **dua kali** dengan middleware yang sama:
  1. `ThrottleRequests:api` (global middleware di Kernel)
  2. `throttle:api` (route-level middleware)
- **Dampak:** Double rate limit application, yang bisa menyebabkan rate limit lebih agresif dari yang diharapkan atau overhead performa.
- **Rekomendasi:** Gunakan salah satu saja — cukup throttle di level global **atau** level route.

### 1.8 Unused CustomCors Middleware

- **Status:** File `app/Http/Middleware/CustomCors.php` ada di codebase, tetapi **tidak terdaftar** di `Kernel.php`.
- **Dampak:** File unused code yang membingungkan developer baru.
- **Rekomendasi:** Hapus file atau integrasikan ke pipeline CORS jika diperlukan.

---

## 2. Status Convention (Inverted)

Pada proyek IDMS, **konvensi status berlawanan dengan konvensi umum**:

| Nilai | Arti | Keterangan |
|-------|------|------------|
| `0` | Aktif / Selesai | Status positif |
| `1` | Nonaktif / Pending | Status negatif |

> **Peringatan:** Konvensi ini **berlawanan** dengan convention umum di mana `1` = aktif/true dan `0` = nonaktif/false. Selalu perhatikan mapping status di setiap query dan API response.

---

## 3. File Storage Strategy

- **Lokasi:** Semua file disimpan di direktori `public/`
- **Naming Convention:** `{name}_{date}_{version}.{ext}`
  - Contoh: `dokumen_teknis_2026-07-15_v1.pdf`
- **Cascading Delete:** Ketika record induk dihapus, file terkait juga ikut dihapus (cascading delete).

---

## 4. Deployment Notes

| Item | Detail |
|------|--------|
| **Frontend URL** | `http://192.168.1.152:9999` |
| **Backend API Prefix** | `/api` |
| **CORS Configuration** | Pastikan origin frontend (`http://192.168.1.152:9999`) diizinkan di konfigurasi CORS backend |

> **Catatan:** Saat deployment, pastikan:
> 1. Environment variable sudah diset dengan benar (APP_URL, FRONTEND_URL, dsb).
> 2. CORS mengizinkan origin frontend yang sesuai.
> 3. File permission pada direktori `public/` sudah benar untuk upload file.

---

## Ringkasan

| No | Masalah | Prioritas | Status |
|----|---------|-----------|--------|
| 1 | RoleMiddleware level check disabled | 🔴 Tinggi | Belum diperbaiki |
| 2 | Dual auth packages | 🟡 Sedang | Perlu evaluasi |
| 3 | Dual activity logging | 🟡 Sedang | Perlu evaluasi |
| 4 | Legacy vs new contract system | 🟡 Sedang | Perlu migrasi |
| 5 | Triplicated readiness pipeline | 🟡 Sedang | Perlu refactor |
| 6 | Logout route unprotected | 🔴 Tinggi | Belum diperbaiki |
| 7 | Redundant throttle | 🟢 Rendah | Perlu cleanup |
| 8 | Unused CustomCors middleware | 🟢 Rendah | Perlu cleanup |

---

**Terakhir diperbarui:** Juli 2026
