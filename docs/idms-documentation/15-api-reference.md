# 15. API Reference

## Daftar Isi

1. [Base URL & Authentication](#1-base-url--authentication)
2. [Standard Response Format](#2-standard-response-format)
3. [Error Response Format](#3-error-response-format)
4. [Rate Limiting](#4-rate-limiting)
5. [Auth Endpoints](#5-auth-endpoints)
6. [Master Data Endpoints](#6-master-data-endpoints)
7. [Contract Management Endpoints](#7-contract-management-endpoints)
8. [Certificate & Compliance Endpoints](#8-certificate--compliance-endpoints)
9. [Event Readiness Endpoints](#9-event-readiness-endpoints)
10. [RKAP Endpoints](#10-rkap-endpoints)
11. [Monitoring Endpoints](#11-monitoring-endpoints)
12. [Inspection Endpoints](#12-inspection-endpoints)
13. [Engineering Endpoints](#13-engineering-endpoints)
14. [User Management Endpoints](#14-user-management-endpoints)
15. [File Download Endpoints](#15-file-download-endpoints)
16. [Custom Feature Endpoints](#16-custom-feature-endpoints)

---

## 1. Base URL & Authentication

| Item | Value |
|------|-------|
| **Base URL** | `http://localhost:8000/api` |
| **Content-Type** | `application/json` |
| **Authentication** | JWT Bearer Token |
| **Header** | `Authorization: Bearer {jwt_token}` |

### Cara Mendapatkan Token

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

### Response Login

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@idms.com",
      "role": 1
    }
  }
}
```

---

## 2. Standard Response Format

### Success Response (Single Item)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "name": "Unit Name",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### Success Response (Paginated)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {"id": 1, "name": "Item 1"},
    {"id": 2, "name": "Item 2"}
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

### Query Parameters untuk Paginasi

| Parameter | Default | Deskripsi |
|-----------|---------|-----------|
| `page` | 1 | Nomor halaman |
| `per_page` | 15 | Jumlah item per halaman |
| `search` | - | Kata kunci pencarian |
| `sort_by` | created_at | Field untuk sorting |
| `sort_order` | desc | asc atau desc |

---

## 3. Error Response Format

### Validation Error (422)

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

### Not Found Error (404)

```json
{
  "success": false,
  "message": "Data tidak ditemukan."
}
```

### Unauthorized Error (401)

```json
{
  "success": false,
  "message": "Unauthorized."
}
```

### Forbidden Error (403)

```json
{
  "success": false,
  "message": "Forbidden."
}
```

---

## 4. Rate Limiting

- **Batas**: 60 request per menit per IP
- **Header Response**:
  - `X-RateLimit-Limit: 60`
  - `X-RateLimit-Remaining: 59`
- **Ketika limit tercapai (429)**:

```json
{
  "success": false,
  "message": "Too many requests.",
  "retry_after": 60
}
```

---

## 5. Auth Endpoints

### Public (Tanpa Authentication)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/api/login` | Login dan dapatkan JWT token |
| `GET` | `/api/projects` | Daftar semua project |
| `GET` | `/api/projects/total` | Total ukuran semua project |

### Protected (Memerlukan Authentication)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/api/logout` | Logout (invalidate token) |
| `POST` | `/api/me` | Dapatkan data user yang sedang login |

---

## 6. Master Data Endpoints

### Units

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| `GET` | `/api/units` | ✓ | Semua | Daftar semua unit (paginated) |
| `GET` | `/api/units/{id}` | ✓ | Semua | Detail unit |
| `POST` | `/api/units` | ✓ | Admin | Tambah unit baru |
| `PUT` | `/api/units/{id}` | ✓ | Admin | Update unit |
| `DELETE` | `/api/units/{id}` | ✓ | Admin | Hapus unit |
| `GET` | `/api/activeunits` | ✓ | Semua | Daftar unit aktif |
| `PUT` | `/api/units/nonactive/{id}` | ✓ | Admin | Nonaktifkan unit |
| `GET` | `/api/exportunits` | ✓ | Admin | Export data unit |

### Categories

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| `GET` | `/api/categories` | ✓ | Semua | Daftar semua kategori |
| `GET` | `/api/categories/{id}` | ✓ | Semua | Detail kategori |
| `POST` | `/api/categories` | ✓ | Admin | Tambah kategori |
| `PUT` | `/api/categories/{id}` | ✓ | Admin | Update kategori |
| `DELETE` | `/api/categories/{id}` | ✓ | Admin | Hapus kategori |
| `GET` | `/api/activecategories` | ✓ | Semua | Daftar kategori aktif |
| `GET` | `/api/categories/unit/{unitId}` | ✓ | Semua | Kategori berdasarkan unit |
| `PUT` | `/api/categories/nonactive/{id}` | ✓ | Admin | Nonaktifkan kategori |

### Types

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| `GET` | `/api/types` | ✓ | Semua | Daftar semua tipe |
| `GET` | `/api/types/{id}` | ✓ | Semua | Detail tipe |
| `POST` | `/api/types` | ✓ | Admin | Tambah tipe |
| `PUT` | `/api/types/{id}` | ✓ | Admin | Update tipe |
| `DELETE` | `/api/types/{id}` | ✓ | Admin | Hapus tipe |
| `GET` | `/api/activetypes` | ✓ | Semua | Daftar tipe aktif |
| `GET` | `/api/types/category/{categoryId}` | ✓ | Semua | Tipe berdasarkan kategori |
| `PUT` | `/api/types/nonactive/{id}` | ✓ | Admin | Nonaktifkan tipe |

### Tag Numbers

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| `GET` | `/api/tagnumbers` | ✓ | Semua | Daftar semua tag number |
| `GET` | `/api/tagnumbers/{id}` | ✓ | Semua | Detail tag number |
| `POST` | `/api/tagnumbers` | ✓ | Admin | Tambah tag number |
| `PUT` | `/api/tagnumbers/{id}` | ✓ | Admin | Update tag number |
| `DELETE` | `/api/tagnumbers/{id}` | ✓ | Admin | Hapus tag number |
| `GET` | `/api/tagnumbers/type/{typeId}` | ✓ | Semua | Tag number berdasarkan tipe |
| `GET` | `/api/tagnumbers/typeunit/{typeId}/{unitId}` | ✓ | Semua | Tag number berdasarkan tipe dan unit |
| `GET` | `/api/tagnumbers/unit/{unitId}` | ✓ | Semua | Tag number berdasarkan unit |
| `GET` | `/api/tagnumbers/tag_number/{id}` | ✓ | Semua | Tag number by ID |
| `GET` | `/api/tagname` | ✓ | Semua | Cari tag name |
| `PUT` | `/api/tagnumbers/nonactive/{id}` | ✓ | Admin | Nonaktifkan tag number |
| `POST` | `/api/tagnumbers/import` | ✓ | Admin | Import tag number dari Excel |
| `POST` | `/api/tagnumbers/import_update` | ✓ | Admin | Import update tag number |

---

## 7. Contract Management Endpoints

### Contract (Legacy)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/contract` | Daftar semua kontrak |
| `GET` | `/api/contract/{id}` | Detail kontrak |
| `POST` | `/api/contract` | Tambah kontrak |
| `PUT` | `/api/contract/{id}` | Update kontrak |
| `DELETE` | `/api/contract/{id}` | Hapus kontrak |
| `GET` | `/api/monitoring_contract` | Monitoring kontrak |
| `PUT` | `/api/contract/current_status/{id}` | Update status kontrak |
| `GET` | `/api/contracts/po_material_type` | Kontrak berdasarkan PO material type |
| `GET` | `/api/contracts/un_po_material_type` | Kontrak tanpa PO material type |
| `GET` | `/api/contracts/user` | Kontrak berdasarkan user |

### Contract New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/contract_new` | Daftar kontrak baru |
| `GET` | `/api/contract_new/{id}` | Detail kontrak baru |
| `POST` | `/api/contract_new` | Tambah kontrak baru |
| `PUT` | `/api/contract_new/{id}` | Update kontrak baru |
| `DELETE` | `/api/contract_new/{id}` | Hapus kontrak baru |
| `GET` | `/api/monitoring_contract_new` | Monitoring kontrak baru |
| `PUT` | `/api/contract_new/current_status/{id}` | Update status |
| `PUT` | `/api/contract_new/tkdn/{id}` | Update TKDN |
| `GET` | `/api/contract_new/lumpsum_progress/{id}` | Progress lumpsum |
| `GET` | `/api/contract_new_po_material_type` | Berdasarkan PO material type |
| `GET` | `/api/contract_new_un_po_material_type` | Tanpa PO material type |
| `GET` | `/api/contract_new_user` | Kontrak berdasarkan user |
| `GET` | `/api/contract_new/download/{id}` | Download file kontrak |

### Termin

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/termin` | Daftar termin |
| `GET` | `/api/termin/{id}` | Detail termin |
| `POST` | `/api/termin` | Tambah termin |
| `PUT` | `/api/termin/{id}` | Update termin |
| `DELETE` | `/api/termin/{id}` | Hapus termin |
| `GET` | `/api/termin/contract/{id}` | Termin berdasarkan kontrak |

### Termin New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/termin_new` | Daftar termin baru |
| `GET` | `/api/termin_new/{id}` | Detail termin baru |
| `POST` | `/api/termin_new` | Tambah termin baru |
| `PUT` | `/api/termin_new/{id}` | Update termin baru |
| `DELETE` | `/api/termin_new/{id}` | Hapus termin baru |
| `GET` | `/api/termin_new/contract/{id}` | Termin berdasarkan kontrak |

### Termin Receipt

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/termin_receipt` | Daftar termin receipt |
| `GET` | `/api/termin_receipt/{id}` | Detail termin receipt |
| `POST` | `/api/termin_receipt` | Tambah termin receipt |
| `PUT` | `/api/termin_receipt/{id}` | Update termin receipt |
| `DELETE` | `/api/termin_receipt/{id}` | Hapus termin receipt |
| `GET` | `/api/termin_receipt/contract/{id}` | Termin receipt berdasarkan kontrak |
| `GET` | `/api/termin_receipt/download_file/{id}` | Download file termin receipt |

### Term Billing

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/termbilling` | Daftar term billing |
| `GET` | `/api/termbilling/{id}` | Detail term billing |
| `POST` | `/api/termbilling` | Tambah term billing |
| `PUT` | `/api/termbilling/{id}` | Update term billing |
| `DELETE` | `/api/termbilling/{id}` | Hapus term billing |
| `GET` | `/api/termbilling/contract/{id}` | Term billing berdasarkan kontrak |

### SPK

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/spk` | Daftar SPK |
| `GET` | `/api/spk/{id}` | Detail SPK |
| `POST` | `/api/spk` | Tambah SPK |
| `PUT` | `/api/spk/{id}` | Update SPK |
| `DELETE` | `/api/spk/{id}` | Hapus SPK |
| `GET` | `/api/spk/contract/{id}` | SPK berdasarkan kontrak |

### SPK New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/spk_new` | Daftar SPK baru |
| `GET` | `/api/spk_new/{id}` | Detail SPK baru |
| `POST` | `/api/spk_new` | Tambah SPK baru |
| `PUT` | `/api/spk_new/{id}` | Update SPK baru |
| `DELETE` | `/api/spk_new/{id}` | Hapus SPK baru |
| `GET` | `/api/spk_new/contract/{id}` | SPK berdasarkan kontrak |
| `GET` | `/api/spk_new/download_file/{id}` | Download file SPK |

### SPK Progress

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/spk_progress` | Daftar progress SPK |
| `GET` | `/api/spk_progress/{id}` | Detail progress SPK |
| `POST` | `/api/spk_progress` | Tambah progress SPK |
| `PUT` | `/api/spk_progress/{id}` | Update progress SPK |
| `DELETE` | `/api/spk_progress/{id}` | Hapus progress SPK |
| `GET` | `/api/spk_progress/spk/{id}` | Progress berdasarkan SPK |
| `GET` | `/api/spk_progress/contract/{id}` | Progress berdasarkan kontrak |

### SPK Progress New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/spk_progress_new` | Daftar progress SPK baru |
| `GET` | `/api/spk_progress_new/{id}` | Detail progress SPK baru |
| `POST` | `/api/spk_progress_new` | Tambah progress SPK baru |
| `PUT` | `/api/spk_progress_new/{id}` | Update progress SPK baru |
| `DELETE` | `/api/spk_progress_new/{id}` | Hapus progress SPK baru |
| `GET` | `/api/spk_progress_new/spk/{id}` | Progress berdasarkan SPK |
| `GET` | `/api/spk_progress_new/contract/{id}` | Progress berdasarkan kontrak |
| `GET` | `/api/spk_progress_new/download_file/{id}` | Download file progress |

### Lumpsum Progress

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/lumpsum_progress` | Daftar lumpsum progress |
| `GET` | `/api/lumpsum_progress/{id}` | Detail lumpsum progress |
| `POST` | `/api/lumpsum_progress` | Tambah lumpsum progress |
| `PUT` | `/api/lumpsum_progress/{id}` | Update lumpsum progress |
| `DELETE` | `/api/lumpsum_progress/{id}` | Hapus lumpsum progress |
| `GET` | `/api/lumpsum_progress/contract/{id}` | Lumpsum progress berdasarkan kontrak |

### Lumpsum Progress New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/lumpsum_progress_new` | Daftar lumpsum progress baru |
| `GET` | `/api/lumpsum_progress_new/{id}` | Detail lumpsum progress baru |
| `POST` | `/api/lumpsum_progress_new` | Tambah lumpsum progress baru |
| `PUT` | `/api/lumpsum_progress_new/{id}` | Update lumpsum progress baru |
| `DELETE` | `/api/lumpsum_progress_new/{id}` | Hapus lumpsum progress baru |
| `GET` | `/api/lumpsum_progress_new/contract/{id}` | Lumpsum progress berdasarkan kontrak |
| `GET` | `/api/lumpsum_progress_new/download_file/{id}` | Download file lumpsum |

### Amandemen

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/amandemen` | Daftar amandemen |
| `GET` | `/api/amandemen/{id}` | Detail amandemen |
| `POST` | `/api/amandemen` | Tambah amandemen |
| `PUT` | `/api/amandemen/{id}` | Update amandemen |
| `DELETE` | `/api/amandemen/{id}` | Hapus amandemen |
| `GET` | `/api/amandemen/contract/{id}` | Amandemen berdasarkan kontrak |

### Amandemen New

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/amandemen_new` | Daftar amandemen baru |
| `GET` | `/api/amandemen_new/{id}` | Detail amandemen baru |
| `POST` | `/api/amandemen_new` | Tambah amandemen baru |
| `PUT` | `/api/amandemen_new/{id}` | Update amandemen baru |
| `DELETE` | `/api/amandemen_new/{id}` | Hapus amandemen baru |
| `GET` | `/api/amandemen_new/contract/{id}` | Amandemen berdasarkan kontrak |
| `GET` | `/api/amandemen_new/download_file/{id}` | Download file amandemen |

### Project Spec

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/project_spec` | Daftar project spec |
| `GET` | `/api/project_spec/{id}` | Detail project spec |
| `POST` | `/api/project_spec` | Tambah project spec |
| `PUT` | `/api/project_spec/{id}` | Update project spec |
| `DELETE` | `/api/project_spec/{id}` | Hapus project spec |
| `GET` | `/api/project_spec/download_file/{id}` | Download file project spec |

---

## 8. Certificate & Compliance Endpoints

### PLO (Persetujuan Layanan Operasi)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/plo` | Daftar PLO |
| `GET` | `/api/plo/{id}` | Detail PLO |
| `POST` | `/api/plo` | Tambah PLO |
| `PUT` | `/api/plo/{id}` | Update PLO |
| `DELETE` | `/api/plo/{id}` | Hapus PLO |
| `POST` | `/api/plo/download` | Download sertifikat PLO |
| `PUT` | `/api/plo/deletefile/{id}` | Hapus file PLO |
| `GET` | `/api/plo_countduedays` | Hitung hari jatuh tempo PLO |
| `GET` | `/api/plo/download_file/{id}` | Download file PLO |
| `GET` | `/api/bapk_plos/{id}` | BAPK berdasarkan PLO |

### BAPK PLO

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/bapk_plo` | Daftar BAPK PLO |
| `GET` | `/api/bapk_plo/{id}` | Detail BAPK PLO |
| `POST` | `/api/bapk_plo` | Tambah BAPK PLO |
| `PUT` | `/api/bapk_plo/{id}` | Update BAPK PLO |
| `DELETE` | `/api/bapk_plo/{id}` | Hapus BAPK PLO |
| `GET` | `/api/bapk_plo/download_file/{id}` | Download file BAPK PLO |

### COI (Certificate of Inspection)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/coi` | Daftar COI |
| `GET` | `/api/coi/{id}` | Detail COI |
| `POST` | `/api/coi` | Tambah COI |
| `PUT` | `/api/coi/{id}` | Update COI |
| `DELETE` | `/api/coi/{id}` | Hapus COI |
| `POST` | `/api/coi/download` | Download sertifikat COI |
| `PUT` | `/api/coi/deletefile/{id}` | Hapus file COI |
| `GET` | `/api/coi_countduedays` | Hitung hari jatuh tempo COI |
| `GET` | `/api/coi_filter` | Filter COI |
| `GET` | `/api/coi/tag_number/{id}` | COI berdasarkan tag number |
| `GET` | `/api/coi/download_file/{id}` | Download file COI |
| `GET` | `/api/bapk_cois/{id}` | BAPK berdasarkan COI |

### BAPK COI

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/bapk_coi` | Daftar BAPK COI |
| `GET` | `/api/bapk_coi/{id}` | Detail BAPK COI |
| `POST` | `/api/bapk_coi` | Tambah BAPK COI |
| `PUT` | `/api/bapk_coi/{id}` | Update BAPK COI |
| `DELETE` | `/api/bapk_coi/{id}` | Hapus BAPK COI |
| `GET` | `/api/bapk_coi/download_file/{id}` | Download file BAPK COI |

### SKHP

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/skhp` | Daftar SKHP |
| `GET` | `/api/skhp/{id}` | Detail SKHP |
| `POST` | `/api/skhp` | Tambah SKHP |
| `PUT` | `/api/skhp/{id}` | Update SKHP |
| `DELETE` | `/api/skhp/{id}` | Hapus SKHP |
| `POST` | `/api/skhp/download` | Download sertifikat SKHP |
| `PUT` | `/api/skhp/deletefile/{id}` | Hapus file SKHP |
| `GET` | `/api/skhp_countduedays` | Hitung hari jatuh tempo SKHP |
| `GET` | `/api/skhp/download_file/{id}` | Download file SKHP |

### Sertifikat Kalibrasi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/sertifikat_kalibrasi` | Daftar sertifikat kalibrasi |
| `GET` | `/api/sertifikat_kalibrasi/{id}` | Detail sertifikat kalibrasi |
| `POST` | `/api/sertifikat_kalibrasi` | Tambah sertifikat kalibrasi |
| `PUT` | `/api/sertifikat_kalibrasi/{id}` | Update sertifikat kalibrasi |
| `DELETE` | `/api/sertifikat_kalibrasi/{id}` | Hapus sertifikat kalibrasi |
| `POST` | `/api/sertifikat_kalibrasi/download` | Download sertifikat |
| `PUT` | `/api/sertifikat_kalibrasi/deletefile/{id}` | Hapus file sertifikat |
| `GET` | `/api/sertifikat_kalibrasi_countduedays` | Hitung hari jatuh tempo |
| `GET` | `/api/sertifikat_kalibrasi/download_file/{id}` | Download file sertifikat |

### Izin Usaha

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/izin_usaha` | Daftar izin usaha |
| `GET` | `/api/izin_usaha/{id}` | Detail izin usaha |
| `POST` | `/api/izin_usaha` | Tambah izin usaha |
| `PUT` | `/api/izin_usaha/{id}` | Update izin usaha |
| `DELETE` | `/api/izin_usaha/{id}` | Hapus izin usaha |
| `GET` | `/api/izin_usaha/download_file/{id}` | Download file izin usaha |

### NIB

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/nib` | Daftar NIB |
| `GET` | `/api/nib/{id}` | Detail NIB |
| `POST` | `/api/nib` | Tambah NIB |
| `PUT` | `/api/nib/{id}` | Update NIB |
| `DELETE` | `/api/nib/{id}` | Hapus NIB |
| `GET` | `/api/nib/download_file/{id}` | Download file NIB |

### Izin Disnaker

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/izin_disnaker` | Daftar izin Disnaker |
| `GET` | `/api/izin_disnaker/{id}` | Detail izin Disnaker |
| `POST` | `/api/izin_disnaker` | Tambah izin Disnaker |
| `PUT` | `/api/izin_disnaker/{id}` | Update izin Disnaker |
| `DELETE` | `/api/izin_disnaker/{id}` | Hapus izin Disnaker |
| `POST` | `/api/izin_disnaker/download` | Download sertifikat Disnaker |
| `PUT` | `/api/izin_disnaker/deletefile/{id}` | Hapus file Disnaker |
| `GET` | `/api/izin_disnaker_countduedays` | Hitung hari jatuh tempo |
| `GET` | `/api/izin_disnaker/tag_number/{id}` | Izin berdasarkan tag number |
| `GET` | `/api/izin_disnaker/download_file/{id}` | Download file izin |

### Report Izin Disnaker

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/report_izin_disnaker` | Daftar report izin Disnaker |
| `GET` | `/api/report_izin_disnaker/{id}` | Detail report |
| `POST` | `/api/report_izin_disnaker` | Tambah report |
| `PUT` | `/api/report_izin_disnaker/{id}` | Update report |
| `DELETE` | `/api/report_izin_disnaker/{id}` | Hapus report |
| `GET` | `/api/report_izin_disnakers/{id}` | Report berdasarkan izin |
| `GET` | `/api/report_izin_disnaker/download_file/{id}` | Download file report |

### Izin Operasi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/izin_operasi` | Daftar izin operasi |
| `GET` | `/api/izin_operasi/{id}` | Detail izin operasi |
| `POST` | `/api/izin_operasi` | Tambah izin operasi |
| `PUT` | `/api/izin_operasi/{id}` | Update izin operasi |
| `DELETE` | `/api/izin_operasi/{id}` | Hapus izin operasi |
| `POST` | `/api/izin_operasi/download` | Download sertifikat izin operasi |
| `PUT` | `/api/izin_operasi/deletefile/{id}` | Hapus file izin operasi |
| `GET` | `/api/izin_operasi_countduedays` | Hitung hari jatuh tempo |
| `GET` | `/api/izin_operasi/download_file/{id}` | Download file izin operasi |

### Report Izin Operasi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/report_izin_operasi` | Daftar report izin operasi |
| `GET` | `/api/report_izin_operasi/{id}` | Detail report |
| `POST` | `/api/report_izin_operasi` | Tambah report |
| `PUT` | `/api/report_izin_operasi/{id}` | Update report |
| `DELETE` | `/api/report_izin_operasi/{id}` | Hapus report |
| `GET` | `/api/report_izin_operasis/{id}` | Report berdasarkan izin |
| `GET` | `/api/report_izin_operasi/download_file/{id}` | Download file report |

### Report PLO

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/report_plo` | Daftar report PLO |
| `GET` | `/api/report_plo/{id}` | Detail report |
| `POST` | `/api/report_plo` | Tambah report |
| `PUT` | `/api/report_plo/{id}` | Update report |
| `DELETE` | `/api/report_plo/{id}` | Hapus report |
| `GET` | `/api/report_plos/{id}` | Report berdasarkan PLO |
| `GET` | `/api/report_plo/download_file/{id}` | Download file report |

### Report COI

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/report_coi` | Daftar report COI |
| `GET` | `/api/report_coi/{id}` | Detail report |
| `POST` | `/api/report_coi` | Tambah report |
| `PUT` | `/api/report_coi/{id}` | Update report |
| `DELETE` | `/api/report_coi/{id}` | Hapus report |
| `GET` | `/api/report_cois/{id}` | Report berdasarkan COI |
| `GET` | `/api/report_coi/download_file/{id}` | Download file report |

### P-Id

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/p_id` | Daftar P-Id |
| `GET` | `/api/p_id/{id}` | Detail P-Id |
| `POST` | `/api/p_id` | Tambah P-Id |
| `PUT` | `/api/p_id/{id}` | Update P-Id |
| `DELETE` | `/api/p_id/{id}` | Hapus P-Id |
| `GET` | `/api/p_id/download_file/{id}` | Download file P-Id |

### MOC (Management of Change)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/moc` | Daftar MOC |
| `GET` | `/api/moc/{id}` | Detail MOC |
| `POST` | `/api/moc` | Tambah MOC |
| `PUT` | `/api/moc/{id}` | Update MOC |
| `DELETE` | `/api/moc/{id}` | Hapus MOC |

### Lampiran MOC

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/lampiran_moc` | Daftar lampiran MOC |
| `GET` | `/api/lampiran_moc/{id}` | Detail lampiran |
| `POST` | `/api/lampiran_moc` | Tambah lampiran |
| `PUT` | `/api/lampiran_moc/{id}` | Update lampiran |
| `DELETE` | `/api/lampiran_moc/{id}` | Hapus lampiran |

### Historical Memorandum

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/historical_memorandum` | Daftar memorandum |
| `GET` | `/api/historical_memorandum/{id}` | Detail memorandum |
| `POST` | `/api/historical_memorandum` | Tambah memorandum |
| `PUT` | `/api/historical_memorandum/{id}` | Update memorandum |
| `DELETE` | `/api/historical_memorandum/{id}` | Hapus memorandum |
| `GET` | `/api/historical_memorandum/download_file/{id}` | Download file memorandum |
| `POST` | `/api/historical_memorandum/download` | Download multiple file memorandum |

### Lampiran Memo

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/lampiran_memo` | Daftar lampiran memo |
| `GET` | `/api/lampiran_memo/{id}` | Detail lampiran |
| `POST` | `/api/lampiran_memo` | Tambah lampiran |
| `PUT` | `/api/lampiran_memo/{id}` | Update lampiran |
| `DELETE` | `/api/lampiran_memo/{id}` | Hapus lampiran |
| `GET` | `/api/historical_memorandum/lampiran/{id}` | Lampiran berdasarkan memorandum |
| `GET` | `/api/lampiran_memo/download_file/{id}` | Download file lampiran |
| `POST` | `/api/lampiran_memo/download` | Download multiple file lampiran |

---

## 9. Event Readiness Endpoints

### Event Readiness (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/event_readiness` | Daftar event readiness |
| `GET` | `/api/event_readiness/{id}` | Detail event readiness |
| `POST` | `/api/event_readiness` | Tambah event readiness |
| `PUT` | `/api/event_readiness/{id}` | Update event readiness |
| `DELETE` | `/api/event_readiness/{id}` | Hapus event readiness |
| `PUT` | `/api/event_readiness/status/{id}` | Update status event |

### Event Readiness Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/event_readiness_oh` | Daftar event readiness OH |
| `GET` | `/api/event_readiness_oh/{id}` | Detail event readiness OH |
| `POST` | `/api/event_readiness_oh` | Tambah event readiness OH |
| `PUT` | `/api/event_readiness_oh/{id}` | Update event readiness OH |
| `DELETE` | `/api/event_readiness_oh/{id}` | Hapus event readiness OH |
| `PUT` | `/api/event_readiness_oh/status/{id}` | Update status event OH |

### Event Readiness RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/event_readiness_rtnrt` | Daftar event readiness RT/NRT |
| `GET` | `/api/event_readiness_rtnrt/{id}` | Detail event readiness RT/NRT |
| `POST` | `/api/event_readiness_rtnrt` | Tambah event readiness RT/NRT |
| `PUT` | `/api/event_readiness_rtnrt/{id}` | Update event readiness RT/NRT |
| `DELETE` | `/api/event_readiness_rtnrt/{id}` | Hapus event readiness RT/NRT |
| `PUT` | `/api/event_readiness_rtnrt/status/{id}` | Update status event RT/NRT |

### Readiness Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_material` | Daftar readiness material |
| `GET` | `/api/readiness_material/{id}` | Detail readiness material |
| `POST` | `/api/readiness_material` | Tambah readiness material |
| `PUT` | `/api/readiness_material/{id}` | Update readiness material |
| `DELETE` | `/api/readiness_material/{id}` | Hapus readiness material |
| `GET` | `/api/readiness_material/dashboard/{id}` | Dashboard readiness material |
| `PUT` | `/api/readiness_material/current_status/{id}` | Update current status |
| `PUT` | `/api/readiness_material/status/{id}` | Update status |
| `GET` | `/api/readiness_material/event/{id}` | Readiness berdasarkan event |

### Readiness Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_material_oh` | Daftar readiness material OH |
| `GET` | `/api/readiness_material_oh/{id}` | Detail readiness material OH |
| `POST` | `/api/readiness_material_oh` | Tambah readiness material OH |
| `PUT` | `/api/readiness_material_oh/{id}` | Update readiness material OH |
| `DELETE` | `/api/readiness_material_oh/{id}` | Hapus readiness material OH |
| `GET` | `/api/readiness_material_oh/dashboard/{id}` | Dashboard readiness material OH |
| `PUT` | `/api/readiness_material_oh/current_status/{id}` | Update current status OH |
| `PUT` | `/api/readiness_material_oh/status/{id}` | Update status OH |
| `GET` | `/api/readiness_material_oh/event/{id}` | Readiness berdasarkan event |

### Readiness Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_material_rtnrt` | Daftar readiness material RT/NRT |
| `GET` | `/api/readiness_material_rtnrt/{id}` | Detail readiness material RT/NRT |
| `POST` | `/api/readiness_material_rtnrt` | Tambah readiness material RT/NRT |
| `PUT` | `/api/readiness_material_rtnrt/{id}` | Update readiness material RT/NRT |
| `DELETE` | `/api/readiness_material_rtnrt/{id}` | Hapus readiness material RT/NRT |
| `GET` | `/api/readiness_material_rtnrt/dashboard/{id}` | Dashboard readiness material RT/NRT |
| `PUT` | `/api/readiness_material_rtnrt/current_status/{id}` | Update current status RT/NRT |
| `PUT` | `/api/readiness_material_rtnrt/status/{id}` | Update status RT/NRT |
| `GET` | `/api/readiness_material_rtnrt/event/{id}` | Readiness berdasarkan event |

### Readiness Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_jasa` | Daftar readiness jasa |
| `GET` | `/api/readiness_jasa/{id}` | Detail readiness jasa |
| `POST` | `/api/readiness_jasa` | Tambah readiness jasa |
| `PUT` | `/api/readiness_jasa/{id}` | Update readiness jasa |
| `DELETE` | `/api/readiness_jasa/{id}` | Hapus readiness jasa |
| `GET` | `/api/readiness_jasa/dashboard/{id}` | Dashboard readiness jasa |
| `PUT` | `/api/readiness_jasa/current_status/{id}` | Update current status |
| `PUT` | `/api/readiness_jasa/status/{id}` | Update status |
| `GET` | `/api/readiness_jasa/event/{id}` | Readiness berdasarkan event |

### Readiness Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_jasa_oh` | Daftar readiness jasa OH |
| `GET` | `/api/readiness_jasa_oh/{id}` | Detail readiness jasa OH |
| `POST` | `/api/readiness_jasa_oh` | Tambah readiness jasa OH |
| `PUT` | `/api/readiness_jasa_oh/{id}` | Update readiness jasa OH |
| `DELETE` | `/api/readiness_jasa_oh/{id}` | Hapus readiness jasa OH |
| `GET` | `/api/readiness_jasa_oh/dashboard/{id}` | Dashboard readiness jasa OH |
| `PUT` | `/api/readiness_jasa_oh/current_status/{id}` | Update current status OH |
| `PUT` | `/api/readiness_jasa_oh/status/{id}` | Update status OH |
| `GET` | `/api/readiness_jasa_oh/event/{id}` | Readiness berdasarkan event |

### Readiness Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/readiness_jasa_rtnrt` | Daftar readiness jasa RT/NRT |
| `GET` | `/api/readiness_jasa_rtnrt/{id}` | Detail readiness jasa RT/NRT |
| `POST` | `/api/readiness_jasa_rtnrt` | Tambah readiness jasa RT/NRT |
| `PUT` | `/api/readiness_jasa_rtnrt/{id}` | Update readiness jasa RT/NRT |
| `DELETE` | `/api/readiness_jasa_rtnrt/{id}` | Hapus readiness jasa RT/NRT |
| `GET` | `/api/readiness_jasa_rtnrt/dashboard/{id}` | Dashboard readiness jasa RT/NRT |
| `PUT` | `/api/readiness_jasa_rtnrt/current_status/{id}` | Update current status RT/NRT |
| `PUT` | `/api/readiness_jasa_rtnrt/status/{id}` | Update status RT/NRT |
| `GET` | `/api/readiness_jasa_rtnrt/event/{id}` | Readiness berdasarkan event |

### Rekomendasi Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_material` | Daftar rekomendasi material |
| `GET` | `/api/rekomendasi_material/{id}` | Detail rekomendasi material |
| `POST` | `/api/rekomendasi_material` | Tambah rekomendasi material |
| `PUT` | `/api/rekomendasi_material/{id}` | Update rekomendasi material |
| `DELETE` | `/api/rekomendasi_material/{id}` | Hapus rekomendasi material |
| `GET` | `/api/rekomendasi_material/readiness/{id}` | Rekomendasi berdasarkan readiness |

### Rekomendasi Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_material_oh` | Daftar rekomendasi material OH |
| `GET` | `/api/rekomendasi_material_oh/{id}` | Detail rekomendasi material OH |
| `POST` | `/api/rekomendasi_material_oh` | Tambah rekomendasi material OH |
| `PUT` | `/api/rekomendasi_material_oh/{id}` | Update rekomendasi material OH |
| `DELETE` | `/api/rekomendasi_material_oh/{id}` | Hapus rekomendasi material OH |
| `GET` | `/api/rekomendasi_material_oh/readiness/{id}` | Rekomendasi berdasarkan readiness |

### Rekomendasi Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_material_rtnrt` | Daftar rekomendasi material RT/NRT |
| `GET` | `/api/rekomendasi_material_rtnrt/{id}` | Detail rekomendasi material RT/NRT |
| `POST` | `/api/rekomendasi_material_rtnrt` | Tambah rekomendasi material RT/NRT |
| `PUT` | `/api/rekomendasi_material_rtnrt/{id}` | Update rekomendasi material RT/NRT |
| `DELETE` | `/api/rekomendasi_material_rtnrt/{id}` | Hapus rekomendasi material RT/NRT |
| `GET` | `/api/rekomendasi_material_rtnrt/readiness/{id}` | Rekomendasi berdasarkan readiness |

### Notif Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_material` | Daftar notif material |
| `GET` | `/api/notif_material/{id}` | Detail notif material |
| `POST` | `/api/notif_material` | Tambah notif material |
| `PUT` | `/api/notif_material/{id}` | Update notif material |
| `DELETE` | `/api/notif_material/{id}` | Hapus notif material |
| `GET` | `/api/notif_material/readiness/{id}` | Notif berdasarkan readiness |

### Notif Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_material_oh` | Daftar notif material OH |
| `GET` | `/api/notif_material_oh/{id}` | Detail notif material OH |
| `POST` | `/api/notif_material_oh` | Tambah notif material OH |
| `PUT` | `/api/notif_material_oh/{id}` | Update notif material OH |
| `DELETE` | `/api/notif_material_oh/{id}` | Hapus notif material OH |
| `GET` | `/api/notif_material_oh/readiness/{id}` | Notif berdasarkan readiness |

### Notif Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_material_rtnrt` | Daftar notif material RT/NRT |
| `GET` | `/api/notif_material_rtnrt/{id}` | Detail notif material RT/NRT |
| `POST` | `/api/notif_material_rtnrt` | Tambah notif material RT/NRT |
| `PUT` | `/api/notif_material_rtnrt/{id}` | Update notif material RT/NRT |
| `DELETE` | `/api/notif_material_rtnrt/{id}` | Hapus notif material RT/NRT |
| `GET` | `/api/notif_material_rtnrt/readiness/{id}` | Notif berdasarkan readiness |

### Job Plan Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_material` | Daftar job plan material |
| `GET` | `/api/job_plan_material/{id}` | Detail job plan material |
| `POST` | `/api/job_plan_material` | Tambah job plan material |
| `PUT` | `/api/job_plan_material/{id}` | Update job plan material |
| `DELETE` | `/api/job_plan_material/{id}` | Hapus job plan material |
| `GET` | `/api/job_plan_material/readiness/{id}` | Job plan berdasarkan readiness |

### Job Plan Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_material_oh` | Daftar job plan material OH |
| `GET` | `/api/job_plan_material_oh/{id}` | Detail job plan material OH |
| `POST` | `/api/job_plan_material_oh` | Tambah job plan material OH |
| `PUT` | `/api/job_plan_material_oh/{id}` | Update job plan material OH |
| `DELETE` | `/api/job_plan_material_oh/{id}` | Hapus job plan material OH |
| `GET` | `/api/job_plan_material_oh/readiness/{id}` | Job plan berdasarkan readiness |

### Job Plan Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_material_rtnrt` | Daftar job plan material RT/NRT |
| `GET` | `/api/job_plan_material_rtnrt/{id}` | Detail job plan material RT/NRT |
| `POST` | `/api/job_plan_material_rtnrt` | Tambah job plan material RT/NRT |
| `PUT` | `/api/job_plan_material_rtnrt/{id}` | Update job plan material RT/NRT |
| `DELETE` | `/api/job_plan_material_rtnrt/{id}` | Hapus job plan material RT/NRT |
| `GET` | `/api/job_plan_material_rtnrt/readiness/{id}` | Job plan berdasarkan readiness |

### PR Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_material` | Daftar PR material |
| `GET` | `/api/pr_material/{id}` | Detail PR material |
| `POST` | `/api/pr_material` | Tambah PR material |
| `PUT` | `/api/pr_material/{id}` | Update PR material |
| `DELETE` | `/api/pr_material/{id}` | Hapus PR material |
| `GET` | `/api/pr_material/readiness/{id}` | PR berdasarkan readiness |

### PR Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_material_oh` | Daftar PR material OH |
| `GET` | `/api/pr_material_oh/{id}` | Detail PR material OH |
| `POST` | `/api/pr_material_oh` | Tambah PR material OH |
| `PUT` | `/api/pr_material_oh/{id}` | Update PR material OH |
| `DELETE` | `/api/pr_material_oh/{id}` | Hapus PR material OH |
| `GET` | `/api/pr_material_oh/readiness/{id}` | PR berdasarkan readiness |

### PR Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_material_rtnrt` | Daftar PR material RT/NRT |
| `GET` | `/api/pr_material_rtnrt/{id}` | Detail PR material RT/NRT |
| `POST` | `/api/pr_material_rtnrt` | Tambah PR material RT/NRT |
| `PUT` | `/api/pr_material_rtnrt/{id}` | Update PR material RT/NRT |
| `DELETE` | `/api/pr_material_rtnrt/{id}` | Hapus PR material RT/NRT |
| `GET` | `/api/pr_material_rtnrt/readiness/{id}` | PR berdasarkan readiness |

### Tender Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_material` | Daftar tender material |
| `GET` | `/api/tender_material/{id}` | Detail tender material |
| `POST` | `/api/tender_material` | Tambah tender material |
| `PUT` | `/api/tender_material/{id}` | Update tender material |
| `DELETE` | `/api/tender_material/{id}` | Hapus tender material |
| `GET` | `/api/tender_material/readiness/{id}` | Tender berdasarkan readiness |

### Tender Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_material_oh` | Daftar tender material OH |
| `GET` | `/api/tender_material_oh/{id}` | Detail tender material OH |
| `POST` | `/api/tender_material_oh` | Tambah tender material OH |
| `PUT` | `/api/tender_material_oh/{id}` | Update tender material OH |
| `DELETE` | `/api/tender_material_oh/{id}` | Hapus tender material OH |
| `GET` | `/api/tender_material_oh/readiness/{id}` | Tender berdasarkan readiness |

### Tender Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_material_rtnrt` | Daftar tender material RT/NRT |
| `GET` | `/api/tender_material_rtnrt/{id}` | Detail tender material RT/NRT |
| `POST` | `/api/tender_material_rtnrt` | Tambah tender material RT/NRT |
| `PUT` | `/api/tender_material_rtnrt/{id}` | Update tender material RT/NRT |
| `DELETE` | `/api/tender_material_rtnrt/{id}` | Hapus tender material RT/NRT |
| `GET` | `/api/tender_material_rtnrt/readiness/{id}` | Tender berdasarkan readiness |

### PO Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/po_material` | Daftar PO material |
| `GET` | `/api/po_material/{id}` | Detail PO material |
| `POST` | `/api/po_material` | Tambah PO material |
| `PUT` | `/api/po_material/{id}` | Update PO material |
| `DELETE` | `/api/po_material/{id}` | Hapus PO material |
| `GET` | `/api/po_material/readiness/{id}` | PO berdasarkan readiness |

### PO Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/po_material_oh` | Daftar PO material OH |
| `GET` | `/api/po_material_oh/{id}` | Detail PO material OH |
| `POST` | `/api/po_material_oh` | Tambah PO material OH |
| `PUT` | `/api/po_material_oh/{id}` | Update PO material OH |
| `DELETE` | `/api/po_material_oh/{id}` | Hapus PO material OH |
| `GET` | `/api/po_material_oh/readiness/{id}` | PO berdasarkan readiness |

### PO Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/po_material_rtnrt` | Daftar PO material RT/NRT |
| `GET` | `/api/po_material_rtnrt/{id}` | Detail PO material RT/NRT |
| `POST` | `/api/po_material_rtnrt` | Tambah PO material RT/NRT |
| `PUT` | `/api/po_material_rtnrt/{id}` | Update PO material RT/NRT |
| `DELETE` | `/api/po_material_rtnrt/{id}` | Hapus PO material RT/NRT |
| `GET` | `/api/po_material_rtnrt/readiness/{id}` | PO berdasarkan readiness |

### Fabrikasi Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/fabrikasi_material` | Daftar fabrikasi material |
| `GET` | `/api/fabrikasi_material/{id}` | Detail fabrikasi material |
| `POST` | `/api/fabrikasi_material` | Tambah fabrikasi material |
| `PUT` | `/api/fabrikasi_material/{id}` | Update fabrikasi material |
| `DELETE` | `/api/fabrikasi_material/{id}` | Hapus fabrikasi material |
| `GET` | `/api/fabrikasi_material/readiness/{id}` | Fabrikasi berdasarkan readiness |

### Fabrikasi Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/fabrikasi_material_oh` | Daftar fabrikasi material OH |
| `GET` | `/api/fabrikasi_material_oh/{id}` | Detail fabrikasi material OH |
| `POST` | `/api/fabrikasi_material_oh` | Tambah fabrikasi material OH |
| `PUT` | `/api/fabrikasi_material_oh/{id}` | Update fabrikasi material OH |
| `DELETE` | `/api/fabrikasi_material_oh/{id}` | Hapus fabrikasi material OH |
| `GET` | `/api/fabrikasi_material_oh/readiness/{id}` | Fabrikasi berdasarkan readiness |

### Fabrikasi Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/fabrikasi_material_rtnrt` | Daftar fabrikasi material RT/NRT |
| `GET` | `/api/fabrikasi_material_rtnrt/{id}` | Detail fabrikasi material RT/NRT |
| `POST` | `/api/fabrikasi_material_rtnrt` | Tambah fabrikasi material RT/NRT |
| `PUT` | `/api/fabrikasi_material_rtnrt/{id}` | Update fabrikasi material RT/NRT |
| `DELETE` | `/api/fabrikasi_material_rtnrt/{id}` | Hapus fabrikasi material RT/NRT |
| `GET` | `/api/fabrikasi_material_rtnrt/readiness/{id}` | Fabrikasi berdasarkan readiness |

### Delivery Material (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/delivery_material` | Daftar delivery material |
| `GET` | `/api/delivery_material/{id}` | Detail delivery material |
| `POST` | `/api/delivery_material` | Tambah delivery material |
| `PUT` | `/api/delivery_material/{id}` | Update delivery material |
| `DELETE` | `/api/delivery_material/{id}` | Hapus delivery material |
| `GET` | `/api/delivery_material/readiness/{id}` | Delivery berdasarkan readiness |

### Delivery Material Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/delivery_material_oh` | Daftar delivery material OH |
| `GET` | `/api/delivery_material_oh/{id}` | Detail delivery material OH |
| `POST` | `/api/delivery_material_oh` | Tambah delivery material OH |
| `PUT` | `/api/delivery_material_oh/{id}` | Update delivery material OH |
| `DELETE` | `/api/delivery_material_oh/{id}` | Hapus delivery material OH |
| `GET` | `/api/delivery_material_oh/readiness/{id}` | Delivery berdasarkan readiness |

### Delivery Material RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/delivery_material_rtnrt` | Daftar delivery material RT/NRT |
| `GET` | `/api/delivery_material_rtnrt/{id}` | Detail delivery material RT/NRT |
| `POST` | `/api/delivery_material_rtnrt` | Tambah delivery material RT/NRT |
| `PUT` | `/api/delivery_material_rtnrt/{id}` | Update delivery material RT/NRT |
| `DELETE` | `/api/delivery_material_rtnrt/{id}` | Hapus delivery material RT/NRT |
| `GET` | `/api/delivery_material_rtnrt/readiness/{id}` | Delivery berdasarkan readiness |

### Rekomendasi Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_jasa` | Daftar rekomendasi jasa |
| `GET` | `/api/rekomendasi_jasa/{id}` | Detail rekomendasi jasa |
| `POST` | `/api/rekomendasi_jasa` | Tambah rekomendasi jasa |
| `PUT` | `/api/rekomendasi_jasa/{id}` | Update rekomendasi jasa |
| `DELETE` | `/api/rekomendasi_jasa/{id}` | Hapus rekomendasi jasa |

### Rekomendasi Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_jasa_oh` | Daftar rekomendasi jasa OH |
| `GET` | `/api/rekomendasi_jasa_oh/{id}` | Detail rekomendasi jasa OH |
| `POST` | `/api/rekomendasi_jasa_oh` | Tambah rekomendasi jasa OH |
| `PUT` | `/api/rekomendasi_jasa_oh/{id}` | Update rekomendasi jasa OH |
| `DELETE` | `/api/rekomendasi_jasa_oh/{id}` | Hapus rekomendasi jasa OH |

### Rekomendasi Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rekomendasi_jasa_rtnrt` | Daftar rekomendasi jasa RT/NRT |
| `GET` | `/api/rekomendasi_jasa_rtnrt/{id}` | Detail rekomendasi jasa RT/NRT |
| `POST` | `/api/rekomendasi_jasa_rtnrt` | Tambah rekomendasi jasa RT/NRT |
| `PUT` | `/api/rekomendasi_jasa_rtnrt/{id}` | Update rekomendasi jasa RT/NRT |
| `DELETE` | `/api/rekomendasi_jasa_rtnrt/{id}` | Hapus rekomendasi jasa RT/NRT |

### Notif Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_jasa` | Daftar notif jasa |
| `GET` | `/api/notif_jasa/{id}` | Detail notif jasa |
| `POST` | `/api/notif_jasa` | Tambah notif jasa |
| `PUT` | `/api/notif_jasa/{id}` | Update notif jasa |
| `DELETE` | `/api/notif_jasa/{id}` | Hapus notif jasa |

### Notif Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_jasa_oh` | Daftar notif jasa OH |
| `GET` | `/api/notif_jasa_oh/{id}` | Detail notif jasa OH |
| `POST` | `/api/notif_jasa_oh` | Tambah notif jasa OH |
| `PUT` | `/api/notif_jasa_oh/{id}` | Update notif jasa OH |
| `DELETE` | `/api/notif_jasa_oh/{id}` | Hapus notif jasa OH |

### Notif Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/notif_jasa_rtnrt` | Daftar notif jasa RT/NRT |
| `GET` | `/api/notif_jasa_rtnrt/{id}` | Detail notif jasa RT/NRT |
| `POST` | `/api/notif_jasa_rtnrt` | Tambah notif jasa RT/NRT |
| `PUT` | `/api/notif_jasa_rtnrt/{id}` | Update notif jasa RT/NRT |
| `DELETE` | `/api/notif_jasa_rtnrt/{id}` | Hapus notif jasa RT/NRT |

### Job Plan Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_jasa` | Daftar job plan jasa |
| `GET` | `/api/job_plan_jasa/{id}` | Detail job plan jasa |
| `POST` | `/api/job_plan_jasa` | Tambah job plan jasa |
| `PUT` | `/api/job_plan_jasa/{id}` | Update job plan jasa |
| `DELETE` | `/api/job_plan_jasa/{id}` | Hapus job plan jasa |

### Job Plan Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_jasa_oh` | Daftar job plan jasa OH |
| `GET` | `/api/job_plan_jasa_oh/{id}` | Detail job plan jasa OH |
| `POST` | `/api/job_plan_jasa_oh` | Tambah job plan jasa OH |
| `PUT` | `/api/job_plan_jasa_oh/{id}` | Update job plan jasa OH |
| `DELETE` | `/api/job_plan_jasa_oh/{id}` | Hapus job plan jasa OH |

### Job Plan Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/job_plan_jasa_rtnrt` | Daftar job plan jasa RT/NRT |
| `GET` | `/api/job_plan_jasa_rtnrt/{id}` | Detail job plan jasa RT/NRT |
| `POST` | `/api/job_plan_jasa_rtnrt` | Tambah job plan jasa RT/NRT |
| `PUT` | `/api/job_plan_jasa_rtnrt/{id}` | Update job plan jasa RT/NRT |
| `DELETE` | `/api/job_plan_jasa_rtnrt/{id}` | Hapus job plan jasa RT/NRT |

### PR Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_jasa` | Daftar PR jasa |
| `GET` | `/api/pr_jasa/{id}` | Detail PR jasa |
| `POST` | `/api/pr_jasa` | Tambah PR jasa |
| `PUT` | `/api/pr_jasa/{id}` | Update PR jasa |
| `DELETE` | `/api/pr_jasa/{id}` | Hapus PR jasa |

### PR Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_jasa_oh` | Daftar PR jasa OH |
| `GET` | `/api/pr_jasa_oh/{id}` | Detail PR jasa OH |
| `POST` | `/api/pr_jasa_oh` | Tambah PR jasa OH |
| `PUT` | `/api/pr_jasa_oh/{id}` | Update PR jasa OH |
| `DELETE` | `/api/pr_jasa_oh/{id}` | Hapus PR jasa OH |

### PR Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pr_jasa_rtnrt` | Daftar PR jasa RT/NRT |
| `GET` | `/api/pr_jasa_rtnrt/{id}` | Detail PR jasa RT/NRT |
| `POST` | `/api/pr_jasa_rtnrt` | Tambah PR jasa RT/NRT |
| `PUT` | `/api/pr_jasa_rtnrt/{id}` | Update PR jasa RT/NRT |
| `DELETE` | `/api/pr_jasa_rtnrt/{id}` | Hapus PR jasa RT/NRT |

### Tender Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_jasa` | Daftar tender jasa |
| `GET` | `/api/tender_jasa/{id}` | Detail tender jasa |
| `POST` | `/api/tender_jasa` | Tambah tender jasa |
| `PUT` | `/api/tender_jasa/{id}` | Update tender jasa |
| `DELETE` | `/api/tender_jasa/{id}` | Hapus tender jasa |

### Tender Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_jasa_oh` | Daftar tender jasa OH |
| `GET` | `/api/tender_jasa_oh/{id}` | Detail tender jasa OH |
| `POST` | `/api/tender_jasa_oh` | Tambah tender jasa OH |
| `PUT` | `/api/tender_jasa_oh/{id}` | Update tender jasa OH |
| `DELETE` | `/api/tender_jasa_oh/{id}` | Hapus tender jasa OH |

### Tender Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/tender_jasa_rtnrt` | Daftar tender jasa RT/NRT |
| `GET` | `/api/tender_jasa_rtnrt/{id}` | Detail tender jasa RT/NRT |
| `POST` | `/api/tender_jasa_rtnrt` | Tambah tender jasa RT/NRT |
| `PUT` | `/api/tender_jasa_rtnrt/{id}` | Update tender jasa RT/NRT |
| `DELETE` | `/api/tender_jasa_rtnrt/{id}` | Hapus tender jasa RT/NRT |

### Contract Jasa (Standard)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/contract_jasa` | Daftar contract jasa |
| `GET` | `/api/contract_jasa/{id}` | Detail contract jasa |
| `POST` | `/api/contract_jasa` | Tambah contract jasa |
| `PUT` | `/api/contract_jasa/{id}` | Update contract jasa |
| `DELETE` | `/api/contract_jasa/{id}` | Hapus contract jasa |

### Contract Jasa Overhaul (OH)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/contract_jasa_oh` | Daftar contract jasa OH |
| `GET` | `/api/contract_jasa_oh/{id}` | Detail contract jasa OH |
| `POST` | `/api/contract_jasa_oh` | Tambah contract jasa OH |
| `PUT` | `/api/contract_jasa_oh/{id}` | Update contract jasa OH |
| `DELETE` | `/api/contract_jasa_oh/{id}` | Hapus contract jasa OH |

### Contract Jasa RT/NRT

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/contract_jasa_rtnrt` | Daftar contract jasa RT/NRT |
| `GET` | `/api/contract_jasa_rtnrt/{id}` | Detail contract jasa RT/NRT |
| `POST` | `/api/contract_jasa_rtnrt` | Tambah contract jasa RT/NRT |
| `PUT` | `/api/contract_jasa_rtnrt/{id}` | Update contract jasa RT/NRT |
| `DELETE` | `/api/contract_jasa_rtnrt/{id}` | Hapus contract jasa RT/NRT |

---

## 10. RKAP Endpoints

### RKAP TA (Tahun Anggaran)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rkap_ta` | Daftar RKAP TA |
| `GET` | `/api/rkap_ta/{id}` | Detail RKAP TA |
| `POST` | `/api/rkap_ta` | Tambah RKAP TA |
| `PUT` | `/api/rkap_ta/{id}` | Update RKAP TA |
| `DELETE` | `/api/rkap_ta/{id}` | Hapus RKAP TA |
| `PUT` | `/api/rkap_ta/update_actual/{id}` | Update aktual RKAP TA |

### RKAP OH (Overhaul)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rkap_oh` | Daftar RKAP OH |
| `GET` | `/api/rkap_oh/{id}` | Detail RKAP OH |
| `POST` | `/api/rkap_oh` | Tambah RKAP OH |
| `PUT` | `/api/rkap_oh/{id}` | Update RKAP OH |
| `DELETE` | `/api/rkap_oh/{id}` | Hapus RKAP OH |
| `PUT` | `/api/rkap_oh/update_actual/{id}` | Update aktual RKAP OH |

### RKAP RT (Rutin)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rkap_rt` | Daftar RKAP RT |
| `GET` | `/api/rkap_rt/{id}` | Detail RKAP RT |
| `POST` | `/api/rkap_rt` | Tambah RKAP RT |
| `PUT` | `/api/rkap_rt/{id}` | Update RKAP RT |
| `DELETE` | `/api/rkap_rt/{id}` | Hapus RKAP RT |
| `PUT` | `/api/rkap_rt/update_actual/{id}` | Update aktual RKAP RT |

### RKAP NR (Non-Rutin)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/rkap_nr` | Daftar RKAP NR |
| `GET` | `/api/rkap_nr/{id}` | Detail RKAP NR |
| `POST` | `/api/rkap_nr` | Tambah RKAP NR |
| `PUT` | `/api/rkap_nr/{id}` | Update RKAP NR |
| `DELETE` | `/api/rkap_nr/{id}` | Hapus RKAP NR |
| `PUT` | `/api/rkap_nr/update_actual/{id}` | Update aktual RKAP NR |

### Dashboard RKAP

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/dashboard_rkap` | Dashboard data RKAP |

---

## 11. Monitoring Endpoints

### Monitoring Equipment

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/monitoring_equipment` | Daftar monitoring equipment |
| `GET` | `/api/monitoring_equipment/{id}` | Detail monitoring equipment |
| `POST` | `/api/monitoring_equipment` | Tambah monitoring equipment |
| `PUT` | `/api/monitoring_equipment/{id}` | Update monitoring equipment |
| `DELETE` | `/api/monitoring_equipment/{id}` | Hapus monitoring equipment |
| `PUT` | `/api/monitoring_equipment/update_log/{id}` | Update log monitoring |
| `GET` | `/api/monitoring_equipment/template` | Download template import |
| `POST` | `/api/monitoring_equipment/import` | Import data dari Excel |
| `GET` | `/api/monitoring_equipment/export` | Export data monitoring |
| `GET` | `/api/monitoring_equipment/export/logs` | Export logs monitoring |
| `GET` | `/api/monitoring_equipment/dashboard` | Dashboard monitoring |

### Status Peralatan

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/status_peralatan` | Daftar status peralatan |
| `GET` | `/api/status_peralatan/{id}` | Detail status peralatan |
| `POST` | `/api/status_peralatan` | Tambah status peralatan |
| `PUT` | `/api/status_peralatan/{id}` | Update status peralatan |
| `DELETE` | `/api/status_peralatan/{id}` | Hapus status peralatan |
| `GET` | `/api/status_peralatan/active` | Daftar status peralatan aktif |
| `PUT` | `/api/status_peralatan/update_active/{id}` | Update status aktif |

### Kondisi Peralatan

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/kondisi_peralatan` | Daftar kondisi peralatan |
| `GET` | `/api/kondisi_peralatan/{id}` | Detail kondisi peralatan |
| `POST` | `/api/kondisi_peralatan` | Tambah kondisi peralatan |
| `PUT` | `/api/kondisi_peralatan/{id}` | Update kondisi peralatan |
| `DELETE` | `/api/kondisi_peralatan/{id}` | Hapus kondisi peralatan |
| `GET` | `/api/kondisi_peralatan/active` | Daftar kondisi peralatan aktif |
| `PUT` | `/api/kondisi_peralatan/update_active/{id}` | Update kondisi aktif |

---

## 12. Inspection Endpoints

### Laporan Inspeksi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/laporan_inspection` | Daftar laporan inspeksi |
| `GET` | `/api/laporan_inspection/{id}` | Detail laporan inspeksi |
| `POST` | `/api/laporan_inspection` | Tambah laporan inspeksi |
| `PUT` | `/api/laporan_inspection/{id}` | Update laporan inspeksi |
| `DELETE` | `/api/laporan_inspection/{id}` | Hapus laporan inspeksi |

### Internal Inspection

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/internal_inspection` | Daftar internal inspection |
| `GET` | `/api/internal_inspection/{id}` | Detail internal inspection |
| `POST` | `/api/internal_inspection` | Tambah internal inspection |
| `PUT` | `/api/internal_inspection/{id}` | Update internal inspection |
| `DELETE` | `/api/internal_inspection/{id}` | Hapus internal inspection |
| `GET` | `/api/internal_inspection/laporan_inspection/{id}` | Internal inspection berdasarkan laporan |

### External Inspection

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/external_inspection` | Daftar external inspection |
| `GET` | `/api/external_inspection/{id}` | Detail external inspection |
| `POST` | `/api/external_inspection` | Tambah external inspection |
| `PUT` | `/api/external_inspection/{id}` | Update external inspection |
| `DELETE` | `/api/external_inspection/{id}` | Hapus external inspection |
| `GET` | `/api/external_inspection/laporan_inspection/{id}` | External inspection berdasarkan laporan |

### Onstream Inspection

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/onstream_inspection` | Daftar onstream inspection |
| `GET` | `/api/onstream_inspection/{id}` | Detail onstream inspection |
| `POST` | `/api/onstream_inspection` | Tambah onstream inspection |
| `PUT` | `/api/onstream_inspection/{id}` | Update onstream inspection |
| `DELETE` | `/api/onstream_inspection/{id}` | Hapus onstream inspection |
| `GET` | `/api/onstream_inspection/laporan_inspection/{id}` | Onstream inspection berdasarkan laporan |

### Overhaul

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/overhaul` | Daftar overhaul |
| `GET` | `/api/overhaul/{id}` | Detail overhaul |
| `POST` | `/api/overhaul` | Tambah overhaul |
| `PUT` | `/api/overhaul/{id}` | Update overhaul |
| `DELETE` | `/api/overhaul/{id}` | Hapus overhaul |
| `GET` | `/api/overhaul/laporan_inspection/{id}` | Overhaul berdasarkan laporan |

### Preventive

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/preventive` | Daftar preventive |
| `GET` | `/api/preventive/{id}` | Detail preventive |
| `POST` | `/api/preventive` | Tambah preventive |
| `PUT` | `/api/preventive/{id}` | Update preventive |
| `DELETE` | `/api/preventive/{id}` | Hapus preventive |
| `GET` | `/api/preventive/laporan_inspection/{id}` | Preventive berdasarkan laporan |

### Surveillance

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/surveillance` | Daftar surveillance |
| `GET` | `/api/surveillance/{id}` | Detail surveillance |
| `POST` | `/api/surveillance` | Tambah surveillance |
| `PUT` | `/api/surveillance/{id}` | Update surveillance |
| `DELETE` | `/api/surveillance/{id}` | Hapus surveillance |
| `GET` | `/api/surveillance/laporan_inspection/{id}` | Surveillance berdasarkan laporan |

### Breakdown Report

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/breakdown_report` | Daftar breakdown report |
| `GET` | `/api/breakdown_report/{id}` | Detail breakdown report |
| `POST` | `/api/breakdown_report` | Tambah breakdown report |
| `PUT` | `/api/breakdown_report/{id}` | Update breakdown report |
| `DELETE` | `/api/breakdown_report/{id}` | Hapus breakdown report |
| `GET` | `/api/breakdown_report/laporan_inspection/{id}` | Breakdown report berdasarkan laporan |

---

## 13. Engineering Endpoints

### Engineering Data

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/engineering_data` | Daftar engineering data |
| `GET` | `/api/engineering_data/{id}` | Detail engineering data |
| `POST` | `/api/engineering_data` | Tambah engineering data |
| `PUT` | `/api/engineering_data/{id}` | Update engineering data |
| `DELETE` | `/api/engineering_data/{id}` | Hapus engineering data |

### Datasheet

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/datasheet` | Daftar datasheet |
| `GET` | `/api/datasheet/{id}` | Detail datasheet |
| `POST` | `/api/datasheet` | Tambah datasheet |
| `PUT` | `/api/datasheet/{id}` | Update datasheet |
| `DELETE` | `/api/datasheet/{id}` | Hapus datasheet |
| `GET` | `/api/datasheet/engineering/{id}` | Datasheet berdasarkan engineering |

### GA Drawing

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/ga_drawing` | Daftar GA drawing |
| `GET` | `/api/ga_drawing/{id}` | Detail GA drawing |
| `POST` | `/api/ga_drawing` | Tambah GA drawing |
| `PUT` | `/api/ga_drawing/{id}` | Update GA drawing |
| `DELETE` | `/api/ga_drawing/{id}` | Hapus GA drawing |
| `GET` | `/api/ga_drawing/engineering/{id}` | GA drawing berdasarkan engineering |

### MDR (Material Data Register)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/mdr_folder` | Daftar MDR folder |
| `GET` | `/api/mdr_folder/{id}` | Detail MDR folder |
| `POST` | `/api/mdr_folder` | Tambah MDR folder |
| `PUT` | `/api/mdr_folder/{id}` | Update MDR folder |
| `DELETE` | `/api/mdr_folder/{id}` | Hapus MDR folder |
| `GET` | `/api/mdr_folder/engineering/{id}` | MDR folder berdasarkan engineering |

### MDR Item

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/mdr_item` | Daftar MDR item |
| `GET` | `/api/mdr_item/{id}` | Detail MDR item |
| `POST` | `/api/mdr_item` | Tambah MDR item |
| `PUT` | `/api/mdr_item/{id}` | Update MDR item |
| `DELETE` | `/api/mdr_item/{id}` | Hapus MDR item |
| `GET` | `/api/mdr_item/folder/{id}` | MDR item berdasarkan folder |

### Historical Equipment

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/historical_equipment` | Daftar historical equipment |
| `GET` | `/api/historical_equipment/{id}` | Detail historical equipment |
| `POST` | `/api/historical_equipment` | Tambah historical equipment |
| `PUT` | `/api/historical_equipment/{id}` | Update historical equipment |
| `DELETE` | `/api/historical_equipment/{id}` | Hapus historical equipment |

### PIR (Plant Inspection Report)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/pir` | Daftar PIR |
| `GET` | `/api/pir/{id}` | Detail PIR |
| `POST` | `/api/pir` | Tambah PIR |
| `PUT` | `/api/pir/{id}` | Update PIR |
| `DELETE` | `/api/pir/{id}` | Hapus PIR |

---

## 14. User Management Endpoints

### Users

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/users` | Daftar semua user |
| `GET` | `/api/users/{id}` | Detail user |
| `POST` | `/api/users` | Tambah user baru |
| `PUT` | `/api/users/{id}` | Update user |
| `DELETE` | `/api/users/{id}` | Hapus user |
| `PUT` | `/api/users/nonactive/{id}` | Nonaktifkan user |

### Features

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/features` | Daftar semua fitur |
| `GET` | `/api/features/{id}` | Detail fitur |
| `POST` | `/api/features` | Tambah fitur |
| `PUT` | `/api/features/{id}` | Update fitur |
| `DELETE` | `/api/features/{id}` | Hapus fitur |
| `GET` | `/api/feature/group` | Fitur berdasarkan grup |

### Hak Akses

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/hak_akses` | Daftar hak akses |
| `GET` | `/api/hak_akses/{id}` | Detail hak akses |
| `POST` | `/api/hak_akses` | Tambah hak akses |
| `PUT` | `/api/hak_akses/{id}` | Update hak akses |
| `DELETE` | `/api/hak_akses/{id}` | Hapus hak akses |

### User Hak Akses

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/user_hak_akses` | Daftar user hak akses |
| `GET` | `/api/user_hak_akses/{id}` | Detail user hak akses |
| `POST` | `/api/user_hak_akses` | Tambah user hak akses |
| `PUT` | `/api/user_hak_akses/{id}` | Update user hak akses |
| `DELETE` | `/api/user_hak_akses/{id}` | Hapus user hak akses |
| `GET` | `/api/user_hak_akses/user/{id}` | Hak akses berdasarkan user |

### Log Activities

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/log_activities` | Daftar semua log aktivitas |
| `GET` | `/api/log_activities/user` | Log aktivitas semua user |
| `GET` | `/api/log_activities/user/{user_id}` | Log aktivitas berdasarkan user |

### Open File Activity

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/open_file_activity` | Daftar aktivitas buka file |
| `GET` | `/api/open_file_activity/{id}` | Detail aktivitas |
| `POST` | `/api/open_file_activity` | Tambah aktivitas |
| `PUT` | `/api/open_file_activity/{id}` | Update aktivitas |
| `DELETE` | `/api/open_file_activity/{id}` | Hapus aktivitas |
| `GET` | `/api/open_file_activity/user/{id}` | Aktivitas berdasarkan user |

---

## 15. File Download Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/p_id/download_file/{id}` | Download file P-Id |
| `GET` | `/api/plo/download_file/{id}` | Download file PLO |
| `GET` | `/api/report_plo/download_file/{id}` | Download file Report PLO |
| `GET` | `/api/bapk_plo/download_file/{id}` | Download file BAPK PLO |
| `GET` | `/api/coi/download_file/{id}` | Download file COI |
| `GET` | `/api/report_coi/download_file/{id}` | Download file Report COI |
| `GET` | `/api/bapk_coi/download_file/{id}` | Download file BAPK COI |
| `GET` | `/api/skhp/download_file/{id}` | Download file SKHP |
| `GET` | `/api/sertifikat_kalibrasi/download_file/{id}` | Download file Sertifikat Kalibrasi |
| `GET` | `/api/izin_usaha/download_file/{id}` | Download file Izin Usaha |
| `GET` | `/api/nib/download_file/{id}` | Download file NIB |
| `GET` | `/api/izin_operasi/download_file/{id}` | Download file Izin Operasi |
| `GET` | `/api/report_izin_operasi/download_file/{id}` | Download file Report Izin Operasi |
| `GET` | `/api/izin_disnaker/download_file/{id}` | Download file Izin Disnaker |
| `GET` | `/api/report_izin_disnaker/download_file/{id}` | Download file Report Izin Disnaker |
| `GET` | `/api/contract_new/download/{id}` | Download file Contract New |
| `GET` | `/api/spk_new/download_file/{id}` | Download file SPK |
| `GET` | `/api/spk_progress_new/download_file/{id}` | Download file SPK Progress |
| `GET` | `/api/termin_receipt/download_file/{id}` | Download file Termin Receipt |
| `GET` | `/api/lumpsum_progress_new/download_file/{id}` | Download file Lumpsum Progress |
| `GET` | `/api/amandemen_new/download_file/{id}` | Download file Amandemen |
| `GET` | `/api/historical_memorandum/download_file/{id}` | Download file Historical Memorandum |
| `GET` | `/api/lampiran_memo/download_file/{id}` | Download file Lampiran Memo |
| `GET` | `/api/project_spec/download_file/{id}` | Download file Project Spec |

---

## 16. Custom Feature Endpoints

### Download Batch Files

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/api/coi/download` | Download batch sertifikat COI |
| `POST` | `/api/plo/download` | Download batch sertifikat PLO |
| `POST` | `/api/skhp/download` | Download batch sertifikat SKHP |
| `POST` | `/api/sertifikat_kalibrasi/download` | Download batch sertifikat kalibrasi |
| `POST` | `/api/izin_disnaker/download` | Download batch sertifikat Izin Disnaker |
| `POST` | `/api/izin_operasi/download` | Download batch sertifikat Izin Operasi |
| `POST` | `/api/lampiran_memo/download` | Download batch file lampiran memo |
| `POST` | `/api/historical_memorandum/download` | Download batch file memorandum |

### Delete Files

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `PUT` | `/api/coi/deletefile/{id}` | Hapus file COI |
| `PUT` | `/api/plo/deletefile/{id}` | Hapus file PLO |
| `PUT` | `/api/skhp/deletefile/{id}` | Hapus file SKHP |
| `PUT` | `/api/sertifikat_kalibrasi/deletefile/{id}` | Hapus file sertifikat kalibrasi |
| `PUT` | `/api/izin_disnaker/deletefile/{id}` | Hapus file Izin Disnaker |
| `PUT` | `/api/izin_operasi/deletefile/{id}` | Hapus file Izin Operasi |

### Count Due Days

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/coi_countduedays` | Hitung hari jatuh tempo COI |
| `GET` | `/api/plo_countduedays` | Hitung hari jatuh tempo PLO |
| `GET` | `/api/skhp_countduedays` | Hitung hari jatuh tempo SKHP |
| `GET` | `/api/sertifikat_kalibrasi_countduedays` | Hitung hari jatuh tempo sertifikat kalibrasi |
| `GET` | `/api/izin_disnaker_countduedays` | Hitung hari jatuh tempo Izin Disnaker |
| `GET` | `/api/izin_operasi_countduedays` | Hitung hari jatuh tempo Izin Operasi |

---

## Contoh Penggunaan

### Contoh Request GET dengan Pagination

```bash
curl -X GET "http://localhost:8000/api/units?page=1&per_page=10" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json"
```

### Contoh Request POST

```bash
curl -X POST "http://localhost:8000/api/units" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Unit Baru",
    "description": "Deskripsi unit baru"
  }'
```

### Contoh Request PUT

```bash
curl -X PUT "http://localhost:8000/api/units/1" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Unit Updated"
  }'
```

### Contoh Request DELETE

```bash
curl -X DELETE "http://localhost:8000/api/units/1" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json"
```

---

## Catatan Penting

1. **Role-Based Access Control**:
   - Role 1 = Admin (full access)
   - Role 99 = Super Admin (full access)
   - Role lainnya = read-only untuk master data

2. **File Upload**: Gunakan `multipart/form-data` untuk upload file

3. **Pagination Default**: 15 item per halaman

4. **Sorting**: Gunakan query parameter `sort_by=field_name&sort_order=asc|desc`

5. **Searching**: Gunakan query parameter `search=keyword` untuk pencarian

---

*Dokumentasi ini dibuat berdasarkan analisis routes/api.php v1.0*
*Terakhir diperbarui: Juli 2025*
