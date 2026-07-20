# 07 - Event Readiness

## Overview

Modul Event Readiness memantau kesiapan material dan jasa menjelang pelaksanaan event (Turnaround, Overhaul, atau Routine/Non-Routine). Setiap event memiliki pipeline progresif berisi tahapan proses pengadaan dari rekomendasi hingga delivery/contract.

---

## 1. Arsitektur 3 Varian Paralel

Sistem readiness dibagi menjadi 3 varian yang berjalan paralel:

| Varian | Keterangan | Tabel Event | Field Tanggal |
|--------|-----------|-------------|---------------|
| **TA** (Turnaround) | Event utama dengan jadwal tetap | `event_readinesses` | `tanggal_ta` |
| **OH** (Overhaul) | Perbaikan besar-besaran | `event_readiness_ohs` | `tanggal_target` (per material) |
| **RTNRT** (Routine/Non-Routine) | Pekerjaan rutin & non-rutin | `event_readiness_rtnrts` | `tanggal_target` (per material) |

### Mengapa 3 Varian Terpisah?

- **Timeline berbeda**: TA memiliki tanggal TA tunggal, sedangkan OH/RTNRT memiliki tanggal target per material
- **Anggaran terpisah**: Setiap varian memiliki anggaran dan pelacakan cost yang independen
- **Struktur identik**: Pipeline tahapan, relasi, dan logika status sama persis — hanya nama tabel/field/relationship yang berbeda suffix (`_oh`, `_rtnrt`)
- **Controller terpisah**: Masing-masing varian memiliki controller sendiri (9 controller material + 9 controller jasa)

---

## 2. Pipeline Material (8 Tahap)

Setiap item material melewati 8 tahap progresif:

```
EventReadiness
  └── ReadinessMaterial (item material)
        ├── 1. RekomendasiMaterial    → Rekomendasi awal dari Historical Memorandum
        ├── 2. NotifMaterial          → Nomor notifikasi
        ├── 3. JobPlanMaterial        → Work Order, KAK, BOQ
        ├── 4. PrMaterial             → Purchase Requisition
        ├── 5. TenderMaterial         → Proses tender
        ├── 6. PoMaterial             → Purchase Order & contract
        ├── 7. FabrikasiMaterial      → Proses fabrikasi
        └── 8. DeliveryMaterial       → Pengiriman & delivery confirmation
```

### Detail Field per Tahap

| Tahap | Model | Field Utama | Relasi Khusus |
|-------|-------|-------------|---------------|
| 1 | `RekomendasiMaterial` | `rekomendasi_file`, `target_date`, `status` | `historical_memorandum_id` |
| 2 | `NotifMaterial` | `no_notif`, `target_date`, `status` | — |
| 3 | `JobPlanMaterial` | `no_wo`, `kak_file`, `boq_file`, `target_date`, `status` | — |
| 4 | `PrMaterial` | `no_pr`, `target_date`, `status` | — |
| 5 | `TenderMaterial` | `description`, `target_date`, `status` | — |
| 6 | `PoMaterial` | `no_po`, `delivery_date`, `status` | `contract_new_id` |
| 7 | `FabrikasiMaterial` | `description`, `target_date`, `status` | — |
| 8 | `DeliveryMaterial` | `description`, `delivery_file`, `target_date`, `status` | — |

---

## 3. Pipeline Jasa (6 Tahap)

Pipeline jasa lebih pendek dari material (6 tahap vs 8 tahap):

```
EventReadiness
  └── ReadinessJasa (item jasa)
        ├── 1. RekomendasiJasa    → Rekomendasi awal
        ├── 2. NotifJasa          → Nomor notifikasi
        ├── 3. JobPlanJasa        → Work Order, KAK, BOQ, durasi preparation
        ├── 4. PrJasa             → Purchase Requisition
        ├── 5. TenderJasa         → Proses tender
        └── 6. ContractJasa       → Kontrak & contract_new
```

### Detail Field per Tahap

| Tahap | Model | Field Utama | Relasi Khusus |
|-------|-------|-------------|---------------|
| 1 | `RekomendasiJasa` | `rekomendasi_file`, `target_date`, `status` | `historical_memorandum_id` |
| 2 | `NotifJasa` | `no_notif`, `target_date`, `status` | — |
| 3 | `JobPlanJasa` | `no_wo`, `kak_file`, `boq_file`, `durasi_preparation`, `target_date`, `status` | — |
| 4 | `PrJasa` | `no_pr`, `target_date`, `status` | — |
| 5 | `TenderJasa` | `description`, `target_date`, `status` | — |
| 6 | `ContractJasa` | `status` | `contract_new_id` |

**Perbedaan kunci**: `JobPlanJasa` memiliki field `durasi_preparation` yang digunakan untuk menghitung prognosa (tanggal mulai preparation = tanggal_ta - durasi_preparation).

---

## 4. Model TA - Field & Relationships

### EventReadiness (TA)

```php
fillable: ['event_name', 'tanggal_ta', 'status']
appends:  ['ta_status'] // computed: days_remaining + color
```

**Relationships:**
- `readiness()` → `hasMany(ReadinessMaterial::class)`
- `readiness_jasa()` → `hasMany(ReadinessJasa::class)`

**Boot (Deleting):** Cascade delete ke semua ReadinessMaterial dan ReadinessJasa (serta file terkait).

### ReadinessMaterial (TA)

```php
fillable: ['event_readiness_id', 'material_name', 'price_estimate', 'type', 'status', 'current_status']
appends:  ['ta_status', 'last_number_status', 'prognosa', 'total_progress', 'nilai_po']
```

| Field | Tipe | Keterangan |
|-------|------|------------|
| `event_readiness_id` | FK | Relasi ke EventReadiness |
| `material_name` | string | Nama material |
| `price_estimate` | int | Estimasi harga |
| `type` | int | 0 = LLDI, 1 = Non LLDI |
| `status` | int | 0 = sudah selesai, 1 = belum |
| `current_status` | string | Status text bebas |

**Relationships:** 8 relasi `hasOne` ke pipeline tahapan (rekomendasi_material s.d. delivery_material).

### ReadinessJasa (TA)

```php
fillable: ['event_readiness_id', 'jasa_name', 'price_estimate', 'status', 'current_status']
appends:  ['ta_status', 'last_number_status', 'prognosa', 'total_progress']
```

**Relationships:** 6 relasi `hasOne` ke pipeline tahapan (rekomendasi_jasa s.d. contract_jasa).

---

## 5. Status & Progress System

### 5.1 Status Warna (Color Coding)

Digunakan pada `ta_status`, `oh_status`, `rtnrt_status`, dan `prognosa`:

| Kondisi | Warna | Keterangan |
|---------|-------|------------|
| `days_remaining > 15` | `green` | Aman, masih > 15 hari |
| `5 ≤ days_remaining ≤ 15` | `yellow` | Perlu perhatian |
| `days_remaining < 5` atau negatif | `red` | Mendesak / sudah terlewat |
| Status = 0 (selesai) | `blue` | Selesai |

### 5.2 total_progress

Menghitung progres berdasarkan tahap terakhir yang terisi:

```
progress = ((stepIndex - 1) + statusValue) / totalSteps × 100%
```

**statusValue:**
- `0` (sudah selesai) → `1.0`
- `1` (belum selesai) → `0.5`
- lainnya → `0`

Contoh: Material di tahap 6 (PO), status 1 (belum):
`((6-1) + 0.5) / 8 × 100% = 68.75%`

### 5.3 Prognosa

**Material:** Mengambil tanggal target dari tahap terakhir yang terisi (prioritas: delivery → fabrikasi → PO → tender), lalu membandingkan dengan tanggal TA:
- Tanggal target > tanggal TA → `red`
- Tanggal target = tanggal TA → `yellow`
- Tanggal target < tanggal TA → `green`

**Jasa:** Menghitung `tanggal_ta - durasi_preparation`, lalu membandingkan sisa hari ke prognosa:
- Sisa > 60 hari → `green`
- 30-60 hari → `yellow`
- < 30 hari → `red`

### 5.4 last_number_status

Menampilkan nomor dokumen terakhir dari pipeline (prioritas dari belakang):
- PO → PR → WO → NOTIF

Contoh: `"PO 12345"`, `"PR 67890"`

### 5.5 nilai_po

- Jika `po_material` memiliki `contract_new_id` → ambil `contract_price` dari ContractNew
- Selainnya → gunakan `price_estimate`

---

## 6. Dashboard Readiness

### Endpoint

```
GET /api/readiness_material/dashboard/{event_readiness_id}
GET /api/readiness_material_oh/dashboard/{event_readiness_oh_id}
GET /api/readiness_material_rtnrt/dashboard/{event_readiness_rtnrt_id}

GET /api/readiness_jasa/dashboard/{event_readiness_id}
GET /api/readiness_jasa_oh/dashboard/{event_readiness_oh_id}
GET /api/readiness_jasa_rtnrt/dashboard/{event_readiness_rtnrt_id}
```

### Response Structure

```json
{
  "success": true,
  "data": {
    "steps": {
      "rekomendasi_material": 5,
      "notif_material": 3,
      "job_plan_material": 2,
      "pr_material": 1,
      "tender_material": 0,
      "po_material": 0,
      "fabrikasi_material": 0,
      "delivery_material": 0
    },
    "types": {
      "lldi": 4,
      "non_lldi": 7
    },
    "average_total_progress": "32.50%",
    "total_data": 11
  }
}
```

**Yang ditampilkan:**
- **steps**: Jumlah material/jasa yang berada di tahap terakhir masing-masing
- **types**: Jumlah berdasarkan type (LLDI vs Non LLDI)
- **average_total_progress**: Rata-rata progres dari semua item
- **total_data**: Total jumlah item

---

## 7. Perbedaan Varian: TA vs OH vs RTNRT

### EventReadiness

| Aspek | TA | OH | RTNRT |
|-------|----|----|-------|
| Model | `EventReadiness` | `EventReadinessOh` | `EventReadinessRtnrt` |
| Field | `event_name`, `tanggal_ta`, `status` | `event_name`, `status` | `event_name`, `status` |
| Tanggal event | `tanggal_ta` (field di event) | `tanggal_target` (field di material) | `tanggal_target` (field di material) |

### ReadinessMaterial

| Aspek | TA | OH | RTNRT |
|-------|----|----|-------|
| FK field | `event_readiness_id` | `event_readiness_oh_id` | `event_readiness_rtnrt_id` |
| Tanggal target | dari `event_readiness.tanggal_ta` | `tanggal_target` per material | `tanggal_target` per material |
| Status attr | `ta_status` | `oh_status` | `rtnrt_status` |
| File folder | `readiness_ta/material/` | `readiness_oh/material/` | `readiness_rtnrt/material/` |

### Perbedaan Utama

1. **TA**: Tanggal TA ditentukan di level event, semua material berbagi tanggal yang sama
2. **OH & RTNRT**: Setiap material memiliki `tanggal_target` sendiri, tidak ada tanggal tunggal di event
3. **Naming convention**: Semua suffix `_oh` atau `_rtnrt` pada model, relasi, dan controller
4. **Logika identik**: Progress calculation, color coding, dan pipeline steps sama persis

---

## 8. API Endpoints

Total ~96 routes (3 varian × 32 routes).

### Per Varian (contoh TA)

#### apiResource (5 routes)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/event_readiness` | List semua event |
| POST | `/api/event_readiness` | Buat event baru |
| GET | `/api/event_readiness/{id}` | Detail event |
| PUT | `/api/event_readiness/{id}` | Update event |
| DELETE | `/api/event_readiness/{id}` | Hapus event (cascade) |

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/readiness_material` | List semua material |
| POST | `/api/readiness_material` | Tambah material |
| GET | `/api/readiness_material/{id}` | Detail material |
| PUT | `/api/readiness_material/{id}` | Update material |
| DELETE | `/api/readiness_material/{id}` | Hapus material (cascade) |

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/readiness_jasa` | List semua jasa |
| POST | `/api/readiness_jasa` | Tambah jasa |
| GET | `/api/readiness_jasa/{id}` | Detail jasa |
| PUT | `/api/readiness_jasa/{id}` | Update jasa |
| DELETE | `/api/readiness_jasa/{id}` | Hapus jasa (cascade) |

#### Custom Routes (17 routes)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| PUT | `/api/event_readiness/status/{id}` | Update status event |
| GET | `/api/readiness_material/dashboard/{id}` | Dashboard summary |
| PUT | `/api/readiness_material/current_status/{id}` | Update current_status |
| PUT | `/api/readiness_material/status/{id}` | Update status material |
| GET | `/api/readiness_material/event/{id}` | Material by event (eager load) |
| GET | `/api/readiness_jasa/dashboard/{id}` | Dashboard jasa summary |
| PUT | `/api/readiness_jasa/current_status/{id}` | Update current_status jasa |
| PUT | `/api/readiness_jasa/status/{id}` | Update status jasa |
| GET | `/api/readiness_jasa/event/{id}` | Jasa by event (eager load) |
| GET | `/api/rekomendasi_material/readiness/{id}` | Rekomendasi by readiness |
| GET | `/api/notif_material/readiness/{id}` | Notif by readiness |
| GET | `/api/job_plan_material/readiness/{id}` | JobPlan by readiness |
| GET | `/api/pr_material/readiness/{id}` | PR by readiness |
| GET | `/api/tender_material/readiness/{id}` | Tender by readiness |
| GET | `/api/po_material/readiness/{id}` | PO by readiness |
| GET | `/api/fabrikasi_material/readiness/{id}` | Fabrikasi by readiness |
| GET | `/api/delivery_material/readiness/{id}` | Delivery by readiness |

> **OH & RTNRT**: Endpoint yang sama dengan suffix `_oh` atau `_rtnrt` (contoh: `/api/readiness_material_oh/dashboard/{id}`)

---

## 9. Cascade Delete & File Management

Ketika event/readiness dihapus, sistem otomatis:

1. **EventReadiness** → hapus semua ReadinessMaterial & ReadinessJasa
2. **ReadinessMaterial** → hapus file terkait + hapus semua child records:
   - `rekomendasi_material` → hapus `rekomendasi_file`
   - `po_material` → hapus `po_file`
   - `job_plan_material` → hapus `boq_file`, `kak_file`
   - `delivery_material` → hapus `delivery_file`
3. **ReadinessJasa** → pola yang sama untuk file rekomendasi, PO, dan job plan

**Lokasi file:**
```
public/
  readiness_ta/material/{rekomendasi,po,job_plan/boq,job_plan/kak,delivery}/
  readiness_oh/material/{...}/
  readiness_rtnrt/material/{...}/
  readiness_ta/jasa/{...}/
```

---

## 10. Model Reference

### TA Models (11 model)
`EventReadiness`, `ReadinessMaterial`, `RekomendasiMaterial`, `NotifMaterial`, `JobPlanMaterial`, `PrMaterial`, `TenderMaterial`, `PoMaterial`, `FabrikasiMaterial`, `DeliveryMaterial`

### TA Jasa Models (6 model)
`ReadinessJasa`, `RekomendasiJasa`, `NotifJasa`, `JobPlanJasa`, `PrJasa`, `TenderJasa`, `ContractJasa`

### OH Models (mirror TA + suffix `_oh`)
`EventReadinessOh`, `ReadinessMaterialOh`, `RekomendasiMaterialOh`, `NotifMaterialOh`, `JobPlanMaterialOh`, `PrMaterialOh`, `TenderMaterialOh`, `PoMaterialOh`, `FabrikasiMaterialOh`, `DeliveryMaterialOh`

### OH Jasa Models
`ReadinessJasaOh`, `RekomendasiJasaOh`, `NotifJasaOh`, `JobPlanJasaOh`, `PrJasaOh`, `TenderJasaOh`, `ContractJasaOh`

### RTNRT Models (mirror TA + suffix `_rtnrt`)
`EventReadinessRtnrt`, `ReadinessMaterialRtnrt`, `RekomendasiMaterialRtnrt`, `NotifMaterialRtnrt`, `JobPlanMaterialRtnrt`, `PrMaterialRtnrt`, `TenderMaterialRtnrt`, `PoMaterialRtnrt`, `FabrikasiMaterialRtnrt`, `DeliveryMaterialRtnrt`

### RTNRT Jasa Models
`ReadinessJasaRtnrt`, `RekomendasiJasaRtnrt`, `NotifJasaRtnrt`, `JobPlanJasaRtnrt`, `PrJasaRtnrt`, `TenderJasaRtnrt`, `ContractJasaRtnrt`

### Controllers (18 controller)
- 3× `EventReadinessController` (TA, OH, RTNRT)
- 3× `ReadinessMaterialController` (TA, OH, RTNRT)
- 3× `ReadinessJasaController` (TA, OH, RTNRT)
- 3× per tahap material (8 tahap × 3 varian) — contoh: `RekomendasiMaterialController`, `RekomendasiMaterialOhController`, `RekomendasiMaterialRtnrtController`
- 3× per tahap jasa (6 tahap × 3 varian)
