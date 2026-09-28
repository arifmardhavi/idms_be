# 20 - Monitoring PSV

Modul Monitoring PSV memantau **masa berlaku sertifikat COI** untuk peralatan
PSV (Pressure Safety Valve) dan TSV (Temperature Safety Valve). Berbeda dengan
Monitoring Equipment yang mengikuti kondisi fisik peralatan, modul ini berfokus
pada satu pertanyaan: *"sertifikat COI untuk tag PSV/TSV ini kapan jatuh tempo,
dan sudah redundant atau belum?"*

Perancangan mendalam (alasan pemilihan `:id` vs `:monitoring_psv`, trade-off
snapshot periode, dan temuan saat rollout) ada di
`docs/superpowers/specs/2026-09-27-monitoring-psv-design.md`.

---

## 1. Konsep

Empat kolom pertama berasal dari COI dan **tidak bisa diedit** lewat API:

| Kolom | Sumber |
|-------|--------|
| Tag Number | `tag_numbers.tag_number` (lewat COI) |
| Masa Berlaku | `cois.overdue_date` |
| Sisa Hari | `DATEDIFF(cois.overdue_date, CURDATE())` |
| Status Masa Berlaku | Turunan dari Sisa Hari |

Empat kolom terakhir adalah anotasi manual: `status_redundant`, `pid_no`,
`kategori`, `keterangan`.

Tabel `monitoring_psv` **tidak** menyimpan kolom read-only tersebut. Kuncinya
`coi_id`, dan sisanya dibaca live saat query. Konsekuensinya:

- Koreksi tanggal pada COI langsung tercermin, tanpa perlu sinkronisasi.
- Hapus record `cois` → baris monitoring ikut terhapus (cascade).
- Query list harus `JOIN cois` + `JOIN tag_numbers`, tidak bisa murni dari satu
  tabel.

### Penentuan PSV/TSV

Tidak ada kolom penanda. Satu-satunya sumber adalah pola pada teks tag number:
`tag_number LIKE '%PSV%' OR tag_number LIKE '%TSV%'` (case-insensitive).

Sengaja **tidak** memakai `type_id`, karena keduanya tidak ekuivalen di data ini:
`type_id = 116` (Pressure Safety Valve) juga dipakai 14 tag yang tercatat
"Breather Valve", dan `type_id = 133` dipakai 33 tag PSV yang tercatat
"Pressure Safety Valve".

---

## 2. Database Schema

### 2.1 Tabel `monitoring_psv`

| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| coi_id | bigint | FK → cois.id, CASCADE, UNIQUE | Anchor ke COI |
| status_redundant | varchar(50) | NULLABLE | Manual, hanya `Redundant` / `No` |
| pid_no | varchar(50) | NULLABLE | Manual |
| kategori | varchar(50) | NULLABLE | Manual, `CSO` / `CSC` |
| keterangan | text | NULLABLE | Manual |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Index:** `kategori`, `status_redundant` — keduanya untuk Needs Attention di
Dashboard.

Tidak ada tabel `monitoring_psv_logs`. Berbeda dengan Monitoring Equipment,
modul ini tidak menampilkan riwayat bulanan, jadi tidak ada snapshot periode.

---

## 3. Status Masa Berlaku

Bucket mengikuti konvensi 9-bulan modul COI, dengan batas bawah `<= 0` sehingga
tag yang jatuh tempo **hari ini** sudah `expired`:

| Sisa Hari | Bucket | Arti |
|-----------|--------|------|
| `> 270` | `safe` | lebih dari 9 bulan lagi |
| `1 … 270` | `warning` | ≤ 9 bulan lagi |
| `≤ 0` | `expired` | sudah lewat / jatuh tempo hari ini |

> Selisih dari modul COI: `CoiController` memakai `DATEDIFF(...) < 0` untuk
> expired, Monitoring PSV memakai `<= 0`. Bedanya 1 baris data. Sengaja dibedakan
> supaya "hari ini" termasuk perlu perhatian.

Seluruh aturan ini terpusat di `App\Support\MasaBerlakuBucket` dan dipakai oleh
lima pemanggil: query list, filter, resource, export, dan dashboard. Batasnya
tidak mungkin melenceng karena ditulis ulang di tempat lain.

Keseluruhan bucket `safe + warning + expired` selalu sama dengan total.

`overdue_date` NULL juga dihitung `expired`, bukan `warning`: COI tanpa tanggal
masa berlaku adalah gap kepatuhan, bukan kondisi aman. Aturan ini berlaku seragam
di resource, filter, dan dashboard. Sepanjang data tidak ada baris dengan
`overdue_date` NULL, jadi angka bucket tidak terpengaruh.

---

## 4. Fields & Response

### 4.1 Kolom API

Response list dan show memuat **12 kolom** yang sama persis:

| Kolom | Tipe | Sumber |
|-------|------|--------|
| `id` | int | PK |
| `coi_id` | int | FK COI |
| `tag_number` | string | `tag_numbers.tag_number` |
| `masa_berlaku` | date | `cois.overdue_date` |
| `sisa_hari` | int | `DATEDIFF(overdue_date, CURDATE())` |
| `status_masa_berlaku` | enum | `safe` / `warning` / `expired` |
| `status_redundant` | enum | manual — `Redundant` / `No` / kosong |
| `pid_no` | string | manual |
| `kategori` | string | manual |
| `keterangan` | text | manual |
| `created_at` | datetime | |
| `updated_at` | datetime | |

> **Kolom yang sengaja tidak diekspos:** `description`, `criticality`, `sece`
> (dari `tag_numbers`) dan `no_certificate` (dari `cois`). Semuanya tidak lagi
> dipakai response maupun export, sehingga juga dibuang dari `SELECT` pada
> `scopeWithCoiData()`. `no_certificate` dan `issue_date` tetap bisa diakses
> lewat blok `coi` di halaman detail.

### 4.2 Blok `coi` (hanya pada `GET /{id}`)

`id`, `no_certificate`, `issue_date`, `overdue_date`, `coi_certificate`.
Ditambahkan lewat `whenLoaded('coi')`, jadi tidak muncul di response list.

> Blok ini dibaca dari **relasi** `$this->coi`, bukan dari atribut hasil
> `SELECT`. Karena `scopeWithCoiData()` tidak lagi memilih
> `no_certificate` / `issue_date` / `coi_certificate`, menulis `$this->issue_date`
> di sini akan menghasilkan `null` **tanpa error** — Eloquent tidak tahu
> sebuah kolom tidak ada di hasil query.

> **Catatan implementasi:** model `Coi` tidak punya date casts, sehingga
> `issue_date` / `overdue_date` tiba sebagai string mentah. Pola
> `optional($string)->format('Y-m-d')` mengembalikan `null` **diam-diam** karena
> `Optional::__call` hanya meneruskan pemanggilan ke object. Karena itu
> `MonitoringPsvResource` mem-parse tanggal secara eksplisit dengan
> `Carbon::parse()`.

---

## 5. Scope Model

| Scope | Isi | Dipakai oleh |
|-------|-----|--------------|
| `scopeJoinedToCoi` | join `cois` + `tag_numbers` saja | `show`, `update` |
| `scopePsvTsv` | join + filter PSV/TSV (tanpa `select`) | `index`, `export`, dashboard, `syncAll` |
| `scopeWithCoiData` | `select` `tag_numbers.tag_number` + `cois.overdue_date` + `selectRaw` alias `sisa_hari` | `index`, `export`, `show`, `update` |

`scopePsvTsv` sengaja tidak menambah `select` supaya bisa dipakai ulang oleh query
agregat dashboard yang membutuhkan `selectRaw` sendiri.

`scopePsvTsv` juga yang membuat kelengkapan list tidak bergantung pada listener:
kalau `tag_number` di-rename sehingga tidak lagi PSV/TSV, barisnya otomatis hilang
dari list tanpa perlu dihapus.

Model meng-override `$table = 'monitoring_psv'` karena tanpa itu Laravel akan
mengpluralisasi `MonitoringPsv` menjadi `monitoring_psvs`.

---

## 6. Auto-Create & Sync

### 6.1 Listener COI

`Coi::booted()` memasang listener `created` yang memanggil
`MonitoringPsvSyncService::syncFromCoi()`. Baris baru otomatis masuk begitu COI
PSV/TSV dibuat — tanpa perlu FE melakukan POST.

### 6.2 Sync Manual

`POST /api/monitoring_psv/sync` untuk backfill data lama atau perbaikan.
Sifatnya idempotent — `firstOrCreate` per COI di dalam satu `DB::transaction`.
Tidak pernah menghapus baris.

```json
{ "scanned": 135, "created": 0, "skipped": 135 }
```

Kondisi aktual saat rollout: 175 tag PSV/TSV, **135 punya COI** (diisi), 40
tanpa COI (diabaikan).

> Sync pertama menghasilkan 135 entri `create` di `log_activities`. Ini disengaja
> — untuk sistem kepatuhan, jejak audit lebih penting daripada kepadatan log.

---

## 7. Dashboard

`GET /api/monitoring_psv/dashboard` — bersifat global, tidak menerima filter
(mirip `MonitoringEquipmentDashboardService`).

| Bagian | Isi |
|--------|-----|
| Ringkasan | total, safe / warning / expired, PSV vs TSV |
| Cross-tab kategori | jumlah per `kategori` |
| Status Redundant | redundant / not redundant / belum diisi |

Kategori yang belum diisi selalu tampil di akhir (`ORDER BY kategori IS NULL,
kategori`) supaya tidak mengacaukan tabel utama.

Rekap Status Redundant membandingkan string **persis** terhadap `Redundant` dan
`No`, jadi penjumlahannya `redundant + not_redundant + unfilled` selalu sama
dengan `total`. Ini yang dijaga oleh normalisasi input di
`UpdateMonitoringPsvRequest`: daftar nilai sah disimpan sekali di
`MonitoringPsv::STATUS_REDUNDANT_OPTIONS` dan dipakai bersama oleh request dan
service. Nilai baru tidak boleh ditambah di konstanta itu tanpa menambahkan
perbandingan yang sama di `MonitoringPsvDashboardService::statusRedundant()`.

---

## 8. Import & Export

| Aspect | Detail |
|--------|--------|
| Export Class | `MonitoringPsvExport` |
| Trigger | `GET /api/monitoring_psv/export` |
| Sheets | 1 sheet, 9 kolom |
| Activity Log | tercatat sebagai `export` pada modul `Monitoring PSV` |

Query export dibangun controller dan **dipakai bersama** dengan `index`, jadi
isi export dijamin identik dengan hasil filter di layar.

Urutan kolom export: `No`, `Tag Number`, `Masa Berlaku COI`, `Sisa Hari`,
`Status Masa Berlaku`, `Status Redundant`, `PID No`, `Kategori`, `Keterangan`.

Jadi sama dengan 12 kolom response, dikurangi `coi_id`, `created_at`, dan
`updated_at` yang tidak diekspos ke file; `id` sendiri sudah menjadi kolom `No`.

Tidak ada import. Data master (Tag Number, COI) berasal dari modul masing-masing.

---

## 9. API Endpoints

Semua endpoint di dalam `auth:api`. Route literal ditulis **sebelum** route
berparameter agar `dashboard` / `export` / `sync` tidak tertangkap sebagai `id`.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/monitoring_psv` | Daftar + filter + search + sort + paginate |
| `GET` | `/api/monitoring_psv/dashboard` | Dashboard |
| `GET` | `/api/monitoring_psv/export` | Export Excel |
| `POST` | `/api/monitoring_psv/sync` | Backfill / repair |
| `GET` | `/api/monitoring_psv/{id}` | Detail + blok COI |
| `PUT` | `/api/monitoring_psv/{id}` | Update 4 kolom manual |

Tidak ada `POST` (baris lahir dari sync) dan tidak ada `DELETE` (menghapus baris
hanya menghapus anotasi; kirim `null` untuk mengosongkan kolom).

Rincian query parameter, whitelist sort, dan body PUT ada di
[15-api-reference.md](15-api-reference.md#monitoring-psv).

`sort_order` hanya menerima `desc`; nilai lain (termasuk kosong dari select FE)
berhasil menjadi `asc`. `per_page` dikunci 1..100.

---

## 10. File Reference

| File | Keterangan |
|------|------------|
| `app/Models/MonitoringPsv.php` | Model utama, scope, accessor activity log |
| `app/Support/PsvTagNumber.php` | Sumber tunggal aturan PSV/TSV |
| `app/Support/MasaBerlakuBucket.php` | Sumber tunggal aturan bucket masa berlaku |
| `app/Services/MonitoringPsvSyncService.php` | Auto-create + backfill idempotent |
| `app/Services/MonitoringPsvDashboardService.php` | Agregasi dashboard |
| `app/Http/Controllers/MonitoringPsvController.php` | Controller utama |
| `app/Http/Requests/UpdateMonitoringPsvRequest.php` | Validasi 4 kolom manual |
| `app/Http/Resources/MonitoringPsvResource.php` | Response + blok detail COI |
| `app/Exports/MonitoringPsvExport.php` | Export Excel 9 kolom |
| `database/migrations/2026_09_27_080000_create_monitoring_psv_table.php` | Tabel `monitoring_psv` |
| `config/log-activity.php` | Registrasi fitur + alias `MonitoringPsv` |
