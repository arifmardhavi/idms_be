# 17 - Flow Diagrams

Dokumentasi alur (flow) sistem IDMS Backend, mencakup file `.drawio` yang tersedia, deskripsi text-based alur per modul, dan sistem warna status.

---

## Daftar Isi

1. [File .drawio yang Tersedia](#1-file-drawio-yang-tersedia)
2. [Alur Master Data](#2-alur-master-data)
3. [Alur Kepatuhan Regulasi (Certificate)](#3-alur-kepatuhan-regulasi-certificate)
4. [Alur Event Readiness](#4-alur-event-readiness)
5. [Alur Manajemen Kontrak](#5-alur-manajemen-kontrak)
6. [Alur Laporan Inspeksi](#6-alur-laporan-inspeksi)
7. [Sistem Warna Status](#7-sistem-warna-status)
8. [Cross-Module Flow](#8-cross-module-flow)

---

## 1. File .drawio yang Tersedia

Semua file diagram tersedia di direktori `flow/` pada root project:

| File | Keterangan |
|------|------------|
| `flow/unit.drawio` | Alur CRUD & hierarki Unit |
| `flow/category.drawio` | Alur CRUD Category & relasi ke Unit |
| `flow/type.drawio` | Alur CRUD Type & relasi ke Category |
| `flow/tag_number.drawio` | Alur CRUD Tag Number & import Excel |
| `flow/COI.drawio` | Alur Certificate of Inspection |
| `flow/PLO.drawio` | Alur Persetujuan Layak Operasi |
| `flow/flow.drawio` | Diagram alur umum / overview sistem |

> **Note:** File `.drawio` dapat dibuka menggunakan [draw.io](https://app.diagrams.net) atau extension VS Code.

---

## 2. Alur Master Data

Master Data memiliki hierarki parent-child yang ketat. Urutan pembuatan wajib dari atas ke bawah.

### 2.1 Hierarki

```
Unit (area kilang)
 └── Category (kategori peralatan)
      └── Type (tipe peralatan)
           └── Tag Number (identitas unik peralatan)
```

### 2.2 Alur Pembuatan

```
┌──────────┐    ┌──────────────┐    ┌──────────┐    ┌─────────────┐
│  UNIT    │───▶│  CATEGORY    │───▶│   TYPE   │───▶│ TAG NUMBER  │
│ (area)   │    │ (kategori)   │    │ (tipe)   │    │ (peralatan) │
└──────────┘    └──────────────┘    └──────────┘    └─────────────┘
   │                 │                   │                │
   ▼                 ▼                   ▼                ▼
 CRUD API        CRUD API            CRUD API        CRUD API
 /api/units     /api/categories     /api/types      /api/tagnumbers
```

### 2.3 Flow Detail per Entity

#### Unit → Category

```
1. Buat Unit (POST /api/units)
   ├── Input: unit_name, unit_type, description
   └── Status default: 0 (nonaktif)

2. Buat Category (POST /api/categories)
   ├── Input: category_name, description
   └── Status default: 0 (nonaktif)

3. Category tidak terikat ke Unit secara langsung
   └── Relasi baru terbentuk di Tag Number
```

#### Category → Type

```
1. Pilih Category yang sudah ada
   └── category_id wajib diisi

2. Buat Type (POST /api/types)
   ├── Input: type_name, category_id, description
   └── Validasi: category_id harus exists di categories
```

#### Type → Tag Number

```
1. Pilih Unit dan Type yang sudah ada
   ├── unit_id wajib diisi
   └── type_id wajib diisi

2. Buat Tag Number (POST /api/tagnumbers)
   ├── Input: tag_number, unit_id, type_id, criticality, sece, description
   ├── Auto-normalisasi: uppercase + hapus spasi
   │   └── Contoh: "11-e-101" → "11-E-101"
   └── Validasi: unique per tag_number
```

### 2.4 Flow Import Tag Number (Excel)

```
┌─────────────────┐
│ Upload File     │
│ (.xlsx/.xls/.csv)│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Parse Excel     │
│ (Maatwebsite)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐    ┌──────────────┐
│ Lookup Unit     │───▶│ Unit tidak   │
│ dari nama       │    │ ditemukan?   │
└────────┬────────┘    │ → SKIP+ERROR │
         │             └──────────────┘
         ▼
┌─────────────────┐    ┌──────────────┐
│ Lookup Type     │───▶│ Type tidak   │
│ dari nama       │    │ ditemukan?   │
└────────┬────────┘    │ → SKIP+ERROR │
         │             └──────────────┘
         ▼
┌─────────────────┐    ┌──────────────┐
│ Cek Duplikat    │───▶│ Tag sudah    │
│ Tag Number      │    │ ada?         │
└────────┬────────┘    │ → SKIP+ERROR │
         │             └──────────────┘
         ▼
┌─────────────────┐
│ INSERT ke DB    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Return Response │
│ (200 + errors)  │
└─────────────────┘
```

### 2.5 Flow Nonaktifasi

```
Nonaktifkan Unit (PUT /api/units/nonactive/{id})
├── Status: 0 (nonaktif)
├── TIDAK cascade ke child (Category/Type/Tag Number)
└── Effect: Unit tidak muncul di dropdown activeunits

Nonaktifkan Tag Number (PUT /api/tagnumbers/nonactive/{id})
├── Status: 0 (nonaktif)
├── TIDAK cascade ke modul lain (PLO, COI, Inspeksi)
└── Effect: Tag Number tidak muncul di dropdown aktif
```

---

## 3. Alur Kepatuhan Regulasi (Certificate)

Sistem mengelola **8 jenis** sertifikat/izin yang terbagi dua kategori.

### 3.1 Kategori berdasarkan Cakupan

```
┌─────────────────────────────────────────────┐
│           BERDASARKAN UNIT                  │
│                                             │
│  PLO ──► Izin Operasi ──► Izin Usaha ──► NIB│
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│         BERDASARKAN TAG NUMBER              │
│                                             │
│  COI ──► SKHP ──► Sertifikat ──► Izin      │
│                    Kalibrasi    Disnaker     │
└─────────────────────────────────────────────┘
```

### 3.2 Alur BAPK → Upload → Due Date → Dashboard

```
┌───────────┐     ┌───────────┐     ┌──────────────┐     ┌───────────┐
│   BAPK    │────▶│  Upload   │────▶│  Due Date    │────▶│ Dashboard │
│ (Pemerik- │     │  Dokumen  │     │  Tracking    │     │  Overview │
│  saan)    │     │  (.pdf)   │     │              │     │           │
└───────────┘     └───────────┘     └──────────────┘     └───────────┘
                                          │
                                   ┌──────┴──────┐
                                   │             │
                                   ▼             ▼
                              ┌────────┐   ┌────────┐
                              │ Hijau  │   │ Kuning │   (warning 30-90 hari)
                              │ >90 hr │   │        │
                              └────────┘   └────────┘
                                   │             │
                                   ▼             ▼
                              ┌────────┐   ┌────────┐
                              │ Merah  │   │ Hitam  │   (kritis <30 hari)
                              │ <30 hr │   │(no doc)│
                              └────────┘   └────────┘
```

### 3.3 Flow PLO (Persetujuan Layak Operasi)

```
1. Pilih Unit (unit_id wajib)
   └── Unit harus dalam status aktif

2. Input Data PLO
   ├── no_plo, tanggal_plo, file upload
   └── Due date otomatis berdasarkan tanggal

3. Upload Dokumen
   └── File disimpan ke public/plo/

4. Status Tracking
   ├── Due date > 90 hari  → Hijau (aman)
   ├── Due date 30-90 hari → Kuning (warning)
   ├── Due date < 30 hari  → Merah (kritis)
   └── Dokumen tidak ada   → Hitam
```

### 3.4 Flow COI (Certificate of Inspection)

```
1. Pilih Tag Number (tag_number_id wajib)
   └── Tag Number harus dalam status aktif

2. Input Data COI
   ├── no_coi, tanggal_coi, file upload
   └── Due date otomatis berdasarkan tanggal

3. Upload Dokumen
   └── File disimpan ke public/coi/

4. Status Tracking
   └── Sistem warna yang sama dengan PLO
```

---

## 4. Alur Event Readiness

Sistem readiness memiliki 3 varian paralel: TA, OH, dan RTNRT.

### 4.1 Arsitektur 3 Varian

```
┌─────────────────────────────────────────────────┐
│              EVENT READINESS                     │
├─────────────┬─────────────────┬─────────────────┤
│     TA      │       OH       │     RTNRT       │
│ (Turnaround)│  (Overhaul)    │ (Routine/Non-   │
│             │                │  Routine)       │
├─────────────┼─────────────────┼─────────────────┤
│ event_      │ event_         │ event_          │
│ readinesses │ readiness_ohs  │ readiness_      │
│             │                │ rtnrts          │
│ Tanggal:    │ Tanggal:       │ Tanggal:        │
│ tanggal_ta  │ tanggal_target │ tanggal_target  │
│ (per event) │ (per material) │ (per material)  │
└─────────────┴─────────────────┴─────────────────┘
```

### 4.2 Alur Material (8 Tahap)

```
EventReadiness
  └── ReadinessMaterial
        │
        ▼
┌─── Tahap 1: Rekomendasi ──────────────────────┐
│   ├── Source: Historical Memorandum             │
│   ├── File: rekomendasi_file                    │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 2: Notifikasi ───────────────────────┐
│   ├── Field: no_notif                          │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 3: Job Plan ─────────────────────────┐
│   ├── Fields: no_wo, kak_file, boq_file        │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 4: Purchase Requisition ─────────────┐
│   ├── Field: no_pr                             │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 5: Tender ───────────────────────────┐
│   ├── Field: description                       │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 6: Purchase Order ───────────────────┐
│   ├── Fields: no_po, delivery_date             │
│   └── Relasi: contract_new_id (link ke Kontrak)│
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 7: Fabrikasi ────────────────────────┐
│   ├── Field: description                       │
│   └── Status: pending → selesai                 │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 8: Delivery ─────────────────────────┐
│   ├── Fields: description, delivery_file       │
│   └── Status: pending → selesai                 │
└────────────────────────────────────────────────┘
```

### 4.3 Alur Jasa (6 Tahap)

```
EventReadiness
  └── ReadinessJasa
        │
        ▼
┌─── Tahap 1: Rekomendasi ──────────────────────┐
│   └── Source: Historical Memorandum             │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 2: Notifikasi ───────────────────────┐
│   └── Field: no_notif                          │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 3: Job Plan ─────────────────────────┐
│   ├── Fields: no_wo, kak_file, boq_file        │
│   └── Tambahan: durasi_preparation             │
│       └── Prognosa = tanggal_ta - durasi_prep  │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 4: Purchase Requisition ─────────────┐
│   └── Field: no_pr                             │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 5: Tender ───────────────────────────┐
│   └── Field: description                       │
└────────────────────┬───────────────────────────┘
                     ▼
┌─── Tahap 6: Contract ─────────────────────────┐
│   └── Relasi: contract_new_id (link ke Kontrak)│
└────────────────────────────────────────────────┘
```

### 4.4 Status Warna Event Readiness

```
days_remaining > 15    →  🟢 Hijau (aman)
5 ≤ days_remaining ≤ 15 →  🟡 Kuning (perlu perhatian)
days_remaining < 5      →  🔴 Merah (mendesak)
status = 0 (selesai)    →  🔵 Biru (selesai)
```

### 4.5 Progres & Total Progress

```
total_progress = jumlah tahap terisi / total tahap × 100%

Material: 8 tahap → 12.5% per tahap
Jasa:     6 tahap → 16.7% per tahap
```

---

## 5. Alur Manajemen Kontrak

### 5.1 Dua Sistem Paralel

```
┌─────────────────────────────────────────────────┐
│              MANAJEMEN KONTRAK                   │
├─────────────────────┬───────────────────────────┤
│    LEGACY SYSTEM    │      NEW SYSTEM           │
│     (contracts)     │    (contract_news)        │
├─────────────────────┼───────────────────────────┤
│ Model: Contract     │ Model: ContractNew        │
│ Tabel: contracts    │ Tabel: contract_news      │
│                     │                           │
│ Progress:           │ Progress:                 │
│ - Lumpsum_progress  │ - LumpsumProgressNew      │
│ - Spk               │ - SpkNew                  │
│                     │                           │
│ Billing:            │ Billing:                  │
│ - Termin            │ - TerminNew               │
│ - TermBilling       │ - TerminReceiptNew        │
│                     │                           │
│ Fitur:              │ Fitur:                    │
│ - Tanpa TKDN        │ + Field TKDN              │
│ - Integer progress  │ + Decimal progress        │
│ - Basic billing     │ + Receipt-based billing   │
└─────────────────────┴───────────────────────────┘
```

### 5.2 Flow Kontrak (New System)

```
┌───────────┐     ┌───────────┐     ┌──────────────┐
│  BUAT     │────▶│  UPLOAD   │────▶│  SET STATUS  │
│  KONTRAK  │     │  FILE     │     │  AKTIF       │
└───────────┘     └───────────┘     └──────────────┘
                                              │
                         ┌────────────────────┐│
                         │                    ││
                         ▼                    ▼│
                  ┌─────────────┐    ┌──────────────┐
                  │  PROGRESS   │    │    SPK       │
                  │  TRACKING   │    │ (opsional)   │
                  └──────┬──────┘    └──────┬───────┘
                         │                  │
                         ▼                  ▼
                  ┌─────────────────────────────┐
                  │       TERMIN & BILLING      │
                  │   (TerminNew → ReceiptNew)  │
                  └─────────────────────────────┘
```

### 5.3 Tipe Kontrak & Alur

```
┌─────────────────────────────────────────────────────┐
│ Tipe 1: LUMPSUM                                     │
│ ├── contract_date wajib                             │
│ ├── Progress: LumpsumProgressNew                    │
│ └── Billing: TerminNew → TerminReceiptNew           │
├─────────────────────────────────────────────────────┤
│ Tipe 2: UNIT RATE                                   │
│ ├── contract_date wajib                             │
│ ├── Progress: SpkNew (dari SPK terbaru)             │
│ └── Billing: TerminNew → TerminReceiptNew           │
├─────────────────────────────────────────────────────┤
│ Tipe 3: PO MATERIAL                                 │
│ ├── contract_date = null                            │
│ ├── start_date & end_date wajib                     │
│ ├── kom selalu 0 (tidak ada durasi MPP)             │
│ └── Billing: TerminNew → TerminReceiptNew           │
├─────────────────────────────────────────────────────┤
│ Tipe 4: PO JASA                                     │
│ ├── start_date & end_date wajib                     │
│ ├── kom selalu 0                                    │
│ └── Billing: TerminNew → TerminReceiptNew           │
└─────────────────────────────────────────────────────┘
```

### 5.4 Flow Durasi MPP

```
Durasi MPP = contract_end_date - today
Color:
  ├── > 90 hari  → Hijau (aman)
  ├── 30-90 hari → Kuning (warning)
  └── < 30 hari  → Merah (kritis)
```

### 5.5 Flow Sisa Nilai

```
sisa_nilai = contract_price - totalPenagihan
Color:
  ├── sisa > 0   → Hijau (masih ada sisa)
  └── sisa ≤ 0   → Merah (lunas/over)
```

---

## 6. Alur Laporan Inspeksi

### 6.1 Struktur Parent-Child

```
LaporanInspection (parent, per tag_number)
├── InternalInspection
├── ExternalInspection
├── OnstreamInspection
├── Surveillance
├── BreakdownReport
├── Preventive
└── Overhaul
```

### 6.2 Flow Pembuatan Laporan

```
1. Pilih Tag Number
   └── tag_number_id wajib, harus unik per LaporanInspection

2. Buat LaporanInspection (POST /api/laporan-inspections)
   ├── Input: tag_number_id
   └── Auto: created_at, updated_at

3. Pilih Jenis Inspeksi (1 dari 7)
   ├── InternalInspection
   ├── ExternalInspection
   ├── OnstreamInspection
   ├── Surveillance
   ├── BreakdownReport
   ├── Preventive
   └── Overhaul

4. Input Data Jenis Inspeksi
   ├── laporan_inspection_id wajib
   ├── Fields spesifik per jenis
   └── Upload file pendukung

5. Status Tracking
   └── Berdasarkan Due Date setiap jenis
```

### 6.3 7 Jenis Inspeksi - Flow Detail

```
┌─────────────────────────────────────────────────────────┐
│ INTERNAL INSPECTION                                     │
│ ├── Peralatan berhenti (shutdown)                       │
│ ├── Pemeriksaan dari dalam                              │
│ └── File: laporan + foto                                │
├─────────────────────────────────────────────────────────┤
│ EXTERNAL INSPECTION                                     │
│ ├── Peralatan beroperasi                                │
│ ├── Pemeriksaan dari luar                               │
│ └── File: laporan + foto                                │
├─────────────────────────────────────────────────────────┤
│ ONSTREAM INSPECTION                                     │
│ ├── Saat operasi normal                                 │
│ ├── Monitoring kondisi aktual                           │
│ └── File: laporan + foto                                │
├─────────────────────────────────────────────────────────┤
│ SURVEILLANCE                                            │
│ ├── Inspeksi berkala                                    │
│ ├── Pemantauan rutin                                    │
│ └── File: laporan + foto                                │
├─────────────────────────────────────────────────────────┤
│ BREAKDOWN REPORT                                        │
│ ├── Ketika terjadi kerusakan                            │
│ ├── Laporan insiden                                     │
│ └── File: laporan + foto + rekomendasi                  │
├─────────────────────────────────────────────────────────┤
│ PREVENTIVE                                              │
│ ├── Pemeriksaan preventif                               │
│ ├── Sebelum terjadi kerusakan                           │
│ └── File: laporan + jadwal                              │
├─────────────────────────────────────────────────────────┤
│ OVERHAUL                                                │
│ ├── Perbaikan besar-besaran                             │
│ ├── Pemeriksaan menyeluruh                              │
│ └── File: laporan + rencana + hasil                     │
└─────────────────────────────────────────────────────────┘
```

---

## 7. Sistem Warna Status

Sistem warna digunakan secara konsisten di seluruh modul untuk indikasi visual.

### 7.1 Definisi Warna

| Warna | Kode | Kondisi | Keterangan |
|-------|------|---------|------------|
| 🔵 **Biru** | `blue` | Status = 0 / selesai | Pekerjaan sudah selesai atau dokumen sudah final |
| 🟢 **Hijau** | `green` | Sisa hari > 90 | Aman, masih ada waktu cukup |
| 🟡 **Kuning** | `yellow` | Sisa hari 30-90 | Warning, perlu perhatian |
| 🔴 **Merah** | `red` | Sisa hari < 30 | Kritis, segera perlu tindakan |
| ⚫ **Hitam** | `black` | Dokumen tidak ada | Belum ada dokumen / data kosong |

### 7.2 Penerapan per Modul

```
┌─────────────────────────────────────────────────────────┐
│ MODUL             │ BIRU        │ HIJAU    │ KUNING │MERAH│ HITAM      │
├─────────────────────────────────────────────────────────┤
│ Event Readiness   │ Status=0    │ >15 hari │ 5-15   │<5   │ -          │
│ Kontrak (Durasi)  │ -           │ >90 hari │ 30-90  │<30  │ -          │
│ Kontrak (Sisa)    │ -           │ sisa > 0 │ -      │≤0   │ -          │
│ PLO               │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ COI               │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ SKHP              │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ Sertifikat Kalibr.│ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ Izin Disnaker     │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ Izin Operasi      │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ Izin Usaha        │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
│ NIB               │ Selesai     │ >90 hari │ 30-90  │<30  │ Tidak ada  │
└─────────────────────────────────────────────────────────┘
```

### 7.3 Implementasi di Backend

Warna dihitung di model menggunakan computed attribute (appended):

```php
// Contoh pada model Plo
public function getPloStatusAttribute()
{
    $dueDate = $this->due_date;
    if (!$dueDate) return ['color' => 'black', 'message' => 'Dokumen tidak ada'];

    $daysRemaining = Carbon::now()->diffInDays($dueDate, false);

    if ($this->status == 0) return ['color' => 'blue', 'message' => 'Selesai'];
    if ($daysRemaining > 90) return ['color' => 'green', 'message' => 'Aman'];
    if ($daysRemaining >= 30) return ['color' => 'yellow', 'message' => 'Warning'];
    return ['color' => 'red', 'message' => 'Kritis'];
}
```

---

## 8. Cross-Module Flow

### 8.1 Alur Lintas Modul Utama

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        IDMS CROSS-MODULE FLOW                          │
│                                                                         │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐                  │
│  │  MASTER  │───▶│  KEPATUHAN   │───▶│  INSPEKSI    │                  │
│  │  DATA    │    │  REGULASI    │    │              │                  │
│  │          │    │              │    │              │                  │
│  │ Unit     │    │ PLO (Unit)   │    │ Laporan      │                  │
│  │ Category │    │ COI (Tag#)   │    │ Inspection   │                  │
│  │ Type     │    │ SKHP (Tag#)  │    │ (per Tag#)   │                  │
│  │ Tag#     │    │ Izin (Tag#)  │    │              │                  │
│  └────┬─────┘    └──────────────┘    └──────────────┘                  │
│       │                                                                  │
│       │         ┌──────────────┐    ┌──────────────┐                   │
│       ├────────▶│   KONTRAK    │◀───│   EVENT      │                   │
│       │         │              │    │  READINESS   │                   │
│       │         │ Legacy/New   │    │              │                   │
│       │         │ SPK          │    │ TA/OH/RTNRT  │                   │
│       │         │ Progress     │    │ Material/Jasa│                   │
│       │         │ Termin/Billing│   │ 8/6 Tahap    │                   │
│       │         └──────────────┘    └──────────────┘                   │
│       │                                                                  │
│       │         ┌──────────────┐    ┌──────────────┐                   │
│       └────────▶│  MONITORING  │───▶│   RKAP       │                   │
│                 │  EQUIPMENT   │    │  ANGGARAN    │                   │
│                 └──────────────┘    └──────────────┘                   │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Flow Integrasi Tag Number

```
Tag Number (Master Data)
├── PLO/COI/SKHP (Kepatuhan) → Tag# harus terdaftar
├── Laporan Inspeksi (Inspeksi) → Tag# harus terdaftar
├── Event Readiness (Readiness) → Material/Jasa terkait Tag#
└── Monitoring Equipment (Monitoring) → Equipment by Tag#
```

### 8.3 Flow Integrasi Kontrak dengan Readiness

```
Event Readiness
  └── ReadinessMaterial tahap 6 (PoMaterial)
        ├── contract_new_id → link ke ContractNew
        └── Status PO terintegrasi dengan status kontrak

  └── ReadinessJasa tahap 6 (ContractJasa)
        ├── contract_new_id → link ke ContractNew
        └── Status kontrak terintegrasi
```

---

## Referensi

- File `.drawio`: `flow/` directory
- [04 - Master Data](04-master-data.md)
- [05 - Manajemen Kontrak](05-manajemen-kontrak.md)
- [06 - Kepatuhan Regulasi](06-kepatuhan-regulasi.md)
- [07 - Event Readiness](07-event-readiness.md)
- [10 - Laporan Inspeksi](10-laporan-inspeksi.md)
