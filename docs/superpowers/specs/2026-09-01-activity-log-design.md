# Design: Sistem Activity Log & Ranking IDMS

Tanggal: 2026-09-01
Status: Disetujui

## Tujuan
Membangun ulang sistem log aktivitas IDMS menjadi satu sistem terpusat yang mencatat **semua aktivitas pengguna** (login, logout, kunjungan fitur >=5 detik, melihat/mengunduh file, export, create, update, delete, import) dan menyediakan **ranking** Most Active User & Most Contributor serta **statistik** untuk ditampilkan sebagai grafik.

## Cakupan & Keputusan
- **Objektif**: log proper yang seragam + ranking + statistik.
- **Satu sumber kebenaran**: tabel `log_activities` diperbesar. `open_file_activities` **dihapus**.
- **Melacak CRUD**: observer Eloquent global + daftar pengecualian (pivot/utility).
- **Pemicu non-CRUD**: helper `activity()` manual di controller/servis.
- **Kunjungan fitur**: frontend kirim `POST /activity/visit` `{feature, duration_seconds}`; backend hanya rekam jika `duration_seconds >= 5`.
- **Ranking**: raw count (tanpa bobot).
- **Statistik**: disediakan backend sebagai data agregat; ditampilkan frontend.
- **Retensi**: **manual oleh manusia** (delete langsung by DB). Tidak ada sistem/event/scheduler.
- **Backfill**: mulai bersih (data log lama dibuang, skema berubah total).

## Perubahan Database
### Tabel diubah: `log_activities` (drop & buat ulang)
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | bigint PK | |
| `user_id` | FK users nullable, `onDelete set null` | |
| `action` | enum | `login, logout, visit, view, download, export, create, update, delete, import` |
| `module` | varchar(100) | nama fitur/modul |
| `record_id` | bigint nullable | id record yang diubah |
| `record_label` | varchar(255) nullable | deskripsi manusiawi record |
| `description` | text nullable | kalimat lengkap dengan nama user, mis. "Faza Ahmad menghapus data X di fitur Y" |
| `metadata` | json nullable | detail mentah (muncul saat expand) |
| `ip_address` | varchar(45) nullable | |
| `user_agent` | text nullable | |
| `created_at` | timestamp | |
| Index | `(user_id, created_at)`, `(action)`, `(module)` | |

Kolom lama `changes` diganti menjadi `metadata`. Data lama di-truncate.

### Tabel dihapus: `open_file_activities`
Seluruh fungsionalitas dipindah ke `log_activities` (aksi `view`/`visit`). Data tidak dimigrasi.

### Tabel ditambah: tidak ada.

## Backend Components
### `ActivityLogger` service + helper `activity()`
- `activity()->log(string $action, string $module, array $opts = [])`
  - `$opts`: `description`, `recordId`, `recordLabel`, `metadata`.
- Otomatis isi `user_id` (Auth::id(), nullable), `ip_address`, `user_agent`.
- Membangun `description` kalimat lengkap bila tidak diberikan.

### `GlobalActivityObserver` (rombak)
- Daftar pengecualian (pivot/utility): `UserHakAkses`, `ContractUser`, `ContractNewUser`, `LogActivity`, `MonitoringEquipmentLog`, `DetailRkapTa`, `DetailRkapOh`, `DetailRkapRt`, `DetailRkapNr`, `Features`, `HakAkses`, `LevelUser`, `Role`, `Unit`, `Type`, `Category`, `StatusPeralatan`, dll.
- `created` → `create`, `updated` → `update` (diff old→new), `deleted` → `delete`.
- `module` = class basename (+ alias bahasa optional).
- `record_id` = model key; `record_label` = field label per-model (map), fallback `#id`.
- `description` = kalimat lengkap.
- Filter field sensitif (password, remember_token, api_token).
- Jangan mencatat dirinya sendiri.

### `ActivityController` (baru, menggantikan LogActivityController)
- `GET /activity` — list log, filter (user_id, action, module, from, to) + pagination. Item: `id, user{id,fullname}, action, action_label, module, module_label, record_id, record_label, description, metadata, ip_address, time, time_ago`.
- `POST /activity/visit` — body `{feature, duration_seconds}`; rekam hanya jika `duration_seconds >= 5`.
- `GET /activity/ranking/active` — top-10 Most Active (`view,download,visit,export,login`).
- `GET /activity/ranking/contributor` — top-10 Most Contributor (`create,update,delete,import`).
- Statistik (semua menerima filter date_from, date_to, user_id, module):
  - `GET /activity/stats/overview`
  - `GET /activity/stats/trend?group=day|month`
  - `GET /activity/stats/by-action`
  - `GET /activity/stats/by-module`
  - `GET /activity/stats/by-user`

### Logging aksi khusus (manual)
- **login**: `AuthController@login` setelah sukses.
- **logout**: `AuthController@logout`.
- **download**: ~35 method `downloadXxxFile` (single) + batch `downloadXxxCertificates/Files` (zip/url).
- **export**: `MonitoringEquipmentController@export`/`exportLogs`, `UnitController@exportUnit`.
- **import**: `Tag_numberController@import`/`importUpdate`, `MonitoringEquipmentController@import` — **sekali per file**.
- **view**: operasi lihat file non-download (jika ada).

### Routes
- Hapus: `log_activities/*` (lama), `open_file_activity` api resource + `/user/{id}`.
- Tambah: activity list, visit, ranking, stats (di dalam auth middleware).

## Format `description`
Kalimat lengkap dengan nama user di-embed. Format per aksi:
- `delete`: "{name} menghapus data {record_label} di fitur {module}"
- `create`: "{name} menambah data {record_label} di fitur {module}"
- `update`: "{name} mengubah data {record_label} di fitur {module}"
- `import`: "{name} import {N} baris di fitur {module}"
- `export`: "{name} export data di fitur {module}"
- `visit`: "{name} mengunjungi fitur {module}"
- `view`: "{name} melihat file {filename} di fitur {module}"
- `download`: "{name} mengunduh file {filename} di fitur {module}"
- `login`: "{name} login"
- `logout`: "{name} logout"

`metadata` menyimpan detail mentah (muncul saat expand di frontend).

## Tampilan Frontend (dikerjakan nanti, bukan scope backend ini)
- **Dashboard**: list simpel — nama + `description` + badge aksi.
- **Log Activities page**: tabel detail (user, aksi badge, modul, detail/description, waktu) + expand → metadata.
- **Ranking & statistik**: panggil endpoint, render grafik (line/bar/pie) + top-10.
- **Visit >=5s**: kirim `POST /activity/visit` saat pindah halaman jika durasi >=5 detik.

## Retensi
Manual delete by DB. Migration/tabel memastikan index `(created_at)` agar manual delete cepat.

## Pengujian
1. `php artisan migrate` sukses di DB idms.
2. Login/logout → log `login`/`logout`.
3. create/update/delete → ter-record dengan description & metadata benar.
4. visit <5 -> tidak terekam; >=5 -> terekam.
5. download/export/import -> terekam.
6. ranking & stats -> shape benar.
7. `php artisan route:list` -> route baru bersih, lama hilang.
8. Tidak ada error/spam (model pivot ter-exclude).
