# Panduan Lokasi File - IDMS Backend

## Daftar Isi

1. [Struktur Direktori Utama](#1-struktur-direktori-utama)
2. [Controllers](#2-controllers)
3. [Models](#3-models)
4. [Services](#4-services)
5. [Helpers](#5-helpers)
6. [API Resources](#6-api-resources)
7. [Requests](#7-requests)
8. [Imports & Exports](#8-imports--exports)
9. [Observers](#9-observers)
10. [Middleware](#10-middleware)
11. [Providers](#11-providers)
12. [Migrations](#12-migrations)
13. [Seeders](#13-seeders)
14. [Routes](#14-routes)
15. [Config Files](#15-config-files)
16. [Flow Diagrams](#16-flow-diagrams)

---

## 1. Struktur Direktori Utama

```
idms_be/
├── app/                          # Aplikasi utama Laravel
│   ├── Console/                  # Artisan commands
│   ├── Exceptions/               # Exception handling
│   ├── Exports/                  # Excel export classes
│   ├── Helpers/                  # Helper functions
│   ├── Http/                     # HTTP layer
│   │   ├── Controllers/          # Controllers (122 file)
│   │   ├── Kernel.php            # HTTP Kernel
│   │   ├── Middleware/            # Middleware (11 file)
│   │   ├── Requests/             # Form Request validation (4 file)
│   │   └── Resources/            # API Resources (26 file)
│   ├── Imports/                  # Excel import classes
│   ├── Models/                   # Eloquent Models (125 file)
│   ├── Observers/                # Model Observers
│   ├── Providers/                # Service Providers (5 file)
│   └── Services/                 # Business Logic Services (7 file)
├── bootstrap/                    # Laravel bootstrap
├── config/                       # Konfigurasi aplikasi (16 file)
├── database/                     # Database
│   ├── factories/                # Model Factories
│   ├── migrations/               # Database Migrations (156 file)
│   └── seeders/                  # Database Seeders (6 file)
├── docs/                         # Dokumentasi project
├── flow/                         # Flow Diagrams (7 file)
├── public/                       # Public assets
├── resources/                    # Views, lang, assets
├── routes/                       # Route definitions (4 file)
├── storage/                      # Logs, cache, uploads
├── tests/                        # Tests
└── vendor/                       # Dependencies
```

---

## 2. Controllers

**Lokasi:** `app/Http/Controllers/`

### Auth & User Management

| File | Deskripsi |
|------|-----------|
| `AuthController.php` | Autentikasi (login, logout, register) |
| `UserController.php` | CRUD user |
| `UserHakAksesController.php` | Manajemen hak akses user |
| `HakAksesController.php` | Master hak akses |
| `FeatureController.php` | Master fitur |

### Master Data

| File | Deskripsi |
|------|-----------|
| `UnitController.php` | Master unit/area |
| `CategoryController.php` | Master kategori equipment |
| `TypeController.php` | Master tipe equipment |
| `Tag_numberController.php` | Master tag number |
| `PloController.php` | Master PLO (Plant Location Organization) |
| `CoiController.php` | Master COI (Certificate of Inspection) |

### Contract (outdated/tidak dipakai)
untuk semua fitur contract hanya menggunakan yang new dengan penamaan belakang "New"

| File | Deskripsi |
|------|-----------|
| `ContractController.php` | Contract legacy utama |
| `TerminController.php` | Termin contract legacy |
| `TermBillingController.php` | Term billing |
| `SpkController.php` | SPK legacy |
| `Spk_progressController.php` | Progress SPK legacy |
| `Lumpsum_progressController.php` | Progress lumpsum legacy |
| `AmandemenController.php` | Amandemen contract legacy |

### Contract (New)

| File | Deskripsi |
|------|-----------|
| `ContractNewController.php` | Contract baru |
| `TerminNewController.php` | Termin contract baru |
| `TerminReceiptController.php` | Termin receipt baru |
| `SpkNewController.php` | SPK baru |
| `SpkProgressNewController.php` | Progress SPK baru |
| `LumpsumProgressNewController.php` | Progress lumpsum baru |
| `AmandemenNewController.php` | Amandemen contract baru |

### Contract Jasa (Legacy + Variants)

| File | Deskripsi |
|------|-----------|
| `ContractJasaController.php` | Contract jasa standar |
| `ContractJasaOhController.php` | Contract jasa Overhaul |
| `ContractJasaRtnrtController.php` | Contract jasa RTN/RT |

### Certificate & Permit

| File | Deskripsi |
|------|-----------|
| `SkhpController.php` | SKHP (Surat Keterangan Hasil Pemeriksaan) |
| `SertifikatKalibrasiController.php` | Sertifikat kalibrasi |
| `IzinUsahaController.php` | Izin usaha |
| `IzinDisnakerController.php` | Izin Disnaker |
| `IzinDisnakerController1.php` | Izin Disnaker (variant 1) |
| `IzinOperasiController.php` | Izin operasi |
| `IzinOperasiController1.php` | Izin operasi (variant 1) |
| `PirController.php` | PIR (Pemeriksaan Internal Rutin) |
| `NibController.php` | NIB |
| `ReportCoiController.php` | Report COI |
| `ReportIzinDisnakerController.php` | Report Izin Disnaker |
| `ReportIzinOperasiController.php` | Report Izin Operasi |
| `ReportPloController.php` | Report PLO |
| `BapkCoiController.php` | BAPK COI |
| `BapkPloController.php` | BAPK PLO |
| `HistoricalEquipmentController.php` | Historical equipment |

### Event Readiness (3 Variants)

| File | Deskripsi |
|------|-----------|
| `EventReadinessController.php` | Event Readiness standar |
| `EventReadinessOhController.php` | Event Readiness Overhaul |
| `EventReadinessRtnrtController.php` | Event Readiness RTN/RT |

### RKAP (4 Variants)

| File | Deskripsi |
|------|-----------|
| `DashboardRkapController.php` | Dashboard RKAP |
| `RkapTaController.php` | RKAP Tahunan |
| `RkapOhController.php` | RKAP Overhaul |
| `RkapRtController.php` | RKAP RT (Routine) |
| `RkapNrController.php` | RKAP NR (Non-Routine) |

### Event Readiness - Material Flow (per variant)

#### Standar
| File | Deskripsi |
|------|-----------|
| `ReadinessMaterialController.php` | Readiness material standar |
| `RekomendasiMaterialController.php` | Rekomendasi material standar |
| `NotifMaterialController.php` | Notifikasi material standar |
| `JobPlanMaterialController.php` | Job plan material standar |
| `PrMaterialController.php` | PR material standar |
| `TenderMaterialController.php` | Tender material standar |
| `PoMaterialController.php` | PO material standar |
| `FabrikasiMaterialController.php` | Fabrikasi material standar |
| `DeliveryMaterialController.php` | Delivery material standar |

#### Overhaul
| File | Deskripsi |
|------|-----------|
| `ReadinessMaterialOhController.php` | Readiness material Overhaul |
| `RekomendasiMaterialOhController.php` | Rekomendasi material Overhaul |
| `NotifMaterialOhController.php` | Notifikasi material Overhaul |
| `JobPlanMaterialOhController.php` | Job plan material Overhaul |
| `PrMaterialOhController.php` | PR material Overhaul |
| `TenderMaterialOhController.php` | Tender material Overhaul |
| `PoMaterialOhController.php` | PO material Overhaul |
| `FabrikasiMaterialOhController.php` | Fabrikasi material Overhaul |
| `DeliveryMaterialOhController.php` | Delivery material Overhaul |

#### RTN/RT
| File | Deskripsi |
|------|-----------|
| `ReadinessMaterialRtnrtController.php` | Readiness material RTN/RT |
| `RekomendasiMaterialRtnrtController.php` | Rekomendasi material RTN/RT |
| `NotifMaterialRtnrtController.php` | Notifikasi material RTN/RT |
| `JobPlanMaterialRtnrtController.php` | Job plan material RTN/RT |
| `PrMaterialRtnrtController.php` | PR material RTN/RT |
| `TenderMaterialRtnrtController.php` | Tender material RTN/RT |
| `PoMaterialRtnrtController.php` | PO material RTN/RT |
| `FabrikasiMaterialRtnrtController.php` | Fabrikasi material RTN/RT |
| `DeliveryMaterialRtnrtController.php` | Delivery material RTN/RT |

### Event Readiness - Jasa Flow (per variant)

#### Standar
| File | Deskripsi |
|------|-----------|
| `ReadinessJasaController.php` | Readiness jasa standar |
| `RekomendasiJasaController.php` | Rekomendasi jasa standar |
| `NotifJasaController.php` | Notifikasi jasa standar |
| `JobPlanJasaController.php` | Job plan jasa standar |
| `PrJasaController.php` | PR jasa standar |
| `TenderJasaController.php` | Tender jasa standar |

#### Overhaul
| File | Deskripsi |
|------|-----------|
| `ReadinessJasaOhController.php` | Readiness jasa Overhaul |
| `RekomendasiJasaOhController.php` | Rekomendasi jasa Overhaul |
| `NotifJasaOhController.php` | Notifikasi jasa Overhaul |
| `JobPlanJasaOhController.php` | Job plan jasa Overhaul |
| `PrJasaOhController.php` | PR jasa Overhaul |
| `TenderJasaOhController.php` | Tender jasa Overhaul |

#### RTN/RT
| File | Deskripsi |
|------|-----------|
| `ReadinessJasaRtnrtController.php` | Readiness jasa RTN/RT |
| `RekomendasiJasaRtnrtController.php` | Rekomendasi jasa RTN/RT |
| `NotifJasaRtnrtController.php` | Notifikasi jasa RTN/RT |
| `JobPlanJasaRtnrtController.php` | Job plan jasa RTN/RT |
| `PrJasaRtnrtController.php` | PR jasa RTN/RT |
| `TenderJasaRtnrtController.php` | Tender jasa RTN/RT |

### Monitoring & Inspection

| File | Deskripsi |
|------|-----------|
| `MonitoringEquipmentController.php` | Monitoring equipment |
| `LaporanInspectionController.php` | Laporan inspeksi |
| `InternalInspectionController.php` | Inspeksi internal |
| `ExternalInspectionController.php` | Inspeksi eksternal |
| `OnstreamInspectionController.php` | Inspeksi onstream |
| `SurveillanceController.php` | Surveillance |
| `BreakdownReportController.php` | Breakdown report |

### Engineering & Document

| File | Deskripsi |
|------|-----------|
| `EngineeringDataController.php` | Data engineering |
| `GaDrawingController.php` | GA Drawing |
| `DatasheetController.php` | Datasheet |
| `HistoricalMemorandumController.php` | Historical memorandum |
| `LampiranMemoController.php` | Lampiran memo |
| `MocController.php` | MOC (Management of Change) |
| `MdrFolderController.php` | MDR folder |
| `MdrItemController.php` | MDR item |

### Project & Preventive

| File | Deskripsi |
|------|-----------|
| `ProjectController.php` | Project |
| `ProjectSpecController.php` | Project specification |
| `PreventiveController.php` | Preventive maintenance |
| `OverhaulController.php` | Overhaul |
| `P_IdController.php` | P-ID |
| `KondisiPeralatanController.php` | Kondisi peralatan |
| `StatusPeralatanController.php` | Status peralatan |

### Activity & Log

| File | Deskripsi |
|------|-----------|
| `OpenFileActivityController.php` | Open file activity |
| `LogActivityController.php` | Log activity |
| `StorageHistoryController.php` | Storage history (via observer) |

---

## 3. Models

**Lokasi:** `app/Models/`

### Base
| File | Deskripsi |
|------|-----------|
| `BaseModel.php` | Base model class |

### Auth & User
| File | Deskripsi |
|------|-----------|
| `User.php` | User model |
| `UserHakAkses.php` | User hak akses pivot |
| `HakAkses.php` | Hak akses |
| `Feature.php` | Feature/Fitur |
| `LogActivity.php` | Log aktivitas |

### Master Data
| File | Deskripsi |
|------|-----------|
| `Unit.php` | Unit/area |
| `Category.php` | Kategori equipment |
| `Type.php` | Tipe equipment |
| `Tag_number.php` | Tag number |
| `Plo.php` | PLO |
| `Coi.php` | COI |
| `Project.php` | Project |

### Contract Legacy
| File | Deskripsi |
|------|-----------|
| `Contract.php` | Contract legacy |
| `Termin.php` | Termin legacy |
| `TermBilling.php` | Term billing |
| `Spk.php` | SPK legacy |
| `Spk_progress.php` | Progress SPK legacy |
| `Lumpsum_progress.php` | Progress lumpsum legacy |
| `Amandemen.php` | Amandemen legacy |
| `HistoricalMemorandum.php` | Historical memorandum |
| `LampiranMemo.php` | Lampiran memo |

### Contract New
| File | Deskripsi |
|------|-----------|
| `ContractNew.php` | Contract baru |
| `TerminNew.php` | Termin baru |
| `TerminReceiptNew.php` | Termin receipt baru |
| `SpkNew.php` | SPK baru |
| `SpkProgressNew.php` | Progress SPK baru |
| `LumpsumProgressNew.php` | Progress lumpsum baru |
| `AmandemenNew.php` | Amandemen baru |

### Contract Jasa
| File | Deskripsi |
|------|-----------|
| `ContractJasa.php` | Contract jasa standar |
| `ContractJasaOh.php` | Contract jasa Overhaul |
| `ContractJasaRtnrt.php` | Contract jasa RTN/RT |

### Event Readiness & Material (Standar)
| File | Deskripsi |
|------|-----------|
| `EventReadiness.php` | Event Readiness |
| `ReadinessMaterial.php` | Readiness material |
| `RekomendasiMaterial.php` | Rekomendasi material |
| `NotifMaterial.php` | Notifikasi material |
| `JobPlanMaterial.php` | Job plan material |
| `PrMaterial.php` | PR material |
| `TenderMaterial.php` | Tender material |
| `PoMaterial.php` | PO material |
| `FabrikasiMaterial.php` | Fabrikasi material |
| `DeliveryMaterial.php` | Delivery material |

### Event Readiness & Material (Overhaul)
| File | Deskripsi |
|------|-----------|
| `EventReadinessOh.php` | Event Readiness OH |
| `ReadinessMaterialOh.php` | Readiness material OH |
| `RekomendasiMaterialOh.php` | Rekomendasi material OH |
| `NotifMaterialOh.php` | Notifikasi material OH |
| `JobPlanMaterialOh.php` | Job plan material OH |
| `PrMaterialOh.php` | PR material OH |
| `TenderMaterialOh.php` | Tender material OH |
| `PoMaterialOh.php` | PO material OH |
| `FabrikasiMaterialOh.php` | Fabrikasi material OH |
| `DeliveryMaterialOh.php` | Delivery material OH |

### Event Readiness & Material (RTN/RT)
| File | Deskripsi |
|------|-----------|
| `EventReadinessRtnrt.php` | Event Readiness RTN/RT |
| `ReadinessMaterialRtnrt.php` | Readiness material RTN/RT |
| `RekomendasiMaterialRtnrt.php` | Rekomendasi material RTN/RT |
| `NotifMaterialRtnrt.php` | Notifikasi material RTN/RT |
| `JobPlanMaterialRtnrt.php` | Job plan material RTN/RT |
| `PrMaterialRtnrt.php` | PR material RTN/RT |
| `TenderMaterialRtnrt.php` | Tender material RTN/RT |
| `PoMaterialRtnrt.php` | PO material RTN/RT |
| `FabrikasiMaterialRtnrt.php` | Fabrikasi material RTN/RT |
| `DeliveryMaterialRtnrt.php` | Delivery material RTN/RT |

### Event Readiness & Jasa (Standar)
| File | Deskripsi |
|------|-----------|
| `ReadinessJasa.php` | Readiness jasa |
| `RekomendasiJasa.php` | Rekomendasi jasa |
| `NotifJasa.php` | Notifikasi jasa |
| `JobPlanJasa.php` | Job plan jasa |
| `PrJasa.php` | PR jasa |
| `TenderJasa.php` | Tender jasa |

### Event Readiness & Jasa (Overhaul)
| File | Deskripsi |
|------|-----------|
| `ReadinessJasaOh.php` | Readiness jasa OH |
| `RekomendasiJasaOh.php` | Rekomendasi jasa OH |
| `NotifJasaOh.php` | Notifikasi jasa OH |
| `JobPlanJasaOh.php` | Job plan jasa OH |
| `PrJasaOh.php` | PR jasa OH |
| `TenderJasaOh.php` | Tender jasa OH |

### Event Readiness & Jasa (RTN/RT)
| File | Deskripsi |
|------|-----------|
| `ReadinessJasaRtnrt.php` | Readiness jasa RTN/RT |
| `RekomendasiJasaRtnrt.php` | Rekomendasi jasa RTN/RT |
| `NotifJasaRtnrt.php` | Notifikasi jasa RTN/RT |
| `JobPlanJasaRtnrt.php` | Job plan jasa RTN/RT |
| `PrJasaRtnrt.php` | PR jasa RTN/RT |
| `TenderJasaRtnrt.php` | Tender jasa RTN/RT |

### RKAP
| File | Deskripsi |
|------|-----------|
| `RkapTa.php` | RKAP Tahunan |
| `DetailRkapTa.php` | Detail RKAP Tahunan |
| `RkapOh.php` | RKAP Overhaul |
| `DetailRkapOh.php` | Detail RKAP Overhaul |
| `RkapRt.php` | RKAP Routine |
| `DetailRkapRt.php` | Detail RKAP Routine |
| `RkapNr.php` | RKAP Non-Routine |
| `DetailRkapNr.php` | Detail RKAP Non-Routine |

### Certificate & Permit
| File | Deskripsi |
|------|-----------|
| `Skhp.php` | SKHP |
| `SertifikatKalibrasi.php` | Sertifikat kalibrasi |
| `IzinUsaha.php` | Izin usaha |
| `IzinDisnaker.php` | Izin Disnaker |
| `IzinOperasi.php` | Izin operasi |
| `Pir.php` | PIR |
| `Nib.php` | NIB |
| `ReportCoi.php` | Report COI |
| `ReportIzinDisnaker.php` | Report Izin Disnaker |
| `ReportIzinOperasi.php` | Report Izin Operasi |
| `ReportPlo.php` | Report PLO |
| `BapkCoi.php` | BAPK COI |
| `BapkPlo.php` | BAPK PLO |

### Inspection & Monitoring
| File | Deskripsi |
|------|-----------|
| `LaporanInspection.php` | Laporan inspeksi |
| `InternalInspection.php` | Inspeksi internal |
| `ExternalInspection.php` | Inspeksi eksternal |
| `OnstreamInspection.php` | Inspeksi onstream |
| `Surveillance.php` | Surveillance |
| `BreakdownReport.php` | Breakdown report |
| `MonitoringEquipment.php` | Monitoring equipment |
| `MonitoringEquipmentLog.php` | Log monitoring equipment |
| `KondisiPeralatan.php` | Kondisi peralatan |
| `StatusPeralatan.php` | Status peralatan |

### Engineering & Document
| File | Deskripsi |
|------|-----------|
| `EngineeringData.php` | Data engineering |
| `GaDrawing.php` | GA Drawing |
| `Datasheet.php` | Datasheet |
| `Moc.php` | MOC |
| `MdrFolder.php` | MDR folder |
| `MdrItem.php` | MDR item |
| `ProjectSpec.php` | Project specification |

### Preventive & Maintenance
| File | Deskripsi |
|------|-----------|
| `Preventive.php` | Preventive maintenance |
| `Overhaul.php` | Overhaul |
| `P_id.php` | P-ID |
| `OpenFileActivity.php` | Open file activity |
| `StorageHistory.php` | Storage history |

---

## 4. Services

**Lokasi:** `app/Services/`

| File | Deskripsi |
|------|-----------|
| `DashboardRkapService.php` | Service untuk dashboard RKAP, menghitung agregasi data RKAP dari 4 variant |
| `MonitoringEquipmentDashboardService.php` | Service untuk dashboard monitoring equipment |
| `MonitoringEquipmentImportService.php` | Service untuk import data monitoring equipment dari Excel |
| `RkapNrService.php` | Service untuk RKAP Non-Routine |
| `RkapOhService.php` | Service untuk RKAP Overhaul |
| `RkapRtService.php` | Service untuk RKAP Routine |
| `RkapTaService.php` | Service untuk RKAP Tahunan |

---

## 5. Helpers

**Lokasi:** `app/Helpers/`

| File | Deskripsi |
|------|-----------|
| `BusinessPeriod.php` | Helper untuk perhitungan business period (periode akuntansi) |
| `DateHelper.php` | Helper untuk manipulasi dan format tanggal |
| `FileHelper.php` | Helper untuk operasi file (upload, path, dll) |

---

## 6. API Resources

**Lokasi:** `app/Http/Resources/`

| File | Deskripsi |
|------|-----------|
| `ApiResource.php` | Base API resource |
| `UserResource.php` | Resource untuk user data |
| `UserLoginResource.php` | Resource untuk data user login |
| `UserHakAksesResource.php` | Resource untuk user hak akses |
| `ContractResource.php` | Resource untuk contract |
| `ContractDateRangeResource.php` | Resource untuk contract date range |
| `AmandemenResource.php` | Resource untuk amandemen |
| `TerminResource.php` | Resource untuk termin |
| `TerminReceiptResource.php` | Resource untuk termin receipt |
| `SpkResource.php` | Resource untuk SPK |
| `SpkProgressResource.php` | Resource untuk SPK progress |
| `LumpsumProgressResource.php` | Resource untuk lumpsum progress |
| `SkhpResource.php` | Resource untuk SKHP |
| `SertifikatKalibrasiResource.php` | Resource untuk sertifikat kalibrasi |
| `MdrResource.php` | Resource untuk MDR |
| `MdrFileResource.php` | Resource untuk MDR file |
| `MonitoringEquipmentResource.php` | Resource untuk monitoring equipment |
| `MonitoringEquipmentLogResource.php` | Resource untuk log monitoring equipment |
| `RkapTaResource.php` | Resource untuk RKAP Tahunan |
| `RkapTaCollection.php` | Collection untuk RKAP Tahunan |
| `RkapOhResource.php` | Resource untuk RKAP Overhaul |
| `RkapOhCollection.php` | Collection untuk RKAP Overhaul |
| `RkapRtResource.php` | Resource untuk RKAP Routine |
| `RkapRtCollection.php` | Collection untuk RKAP Routine |
| `RkapNrResource.php` | Resource untuk RKAP Non-Routine |
| `RkapNrCollection.php` | Collection untuk RKAP Non-Routine |

---

## 7. Requests

**Lokasi:** `app/Http/Requests/`

| File | Deskripsi |
|------|-----------|
| `BaseRequest.php` | Base request class |
| `ImportMonitoringEquipmentRequest.php` | Validasi import monitoring equipment |
| `StoreMonitoringEquipmentRequest.php` | Validasi create monitoring equipment |
| `UpdateMonitoringEquipmentRequest.php` | Validasi update monitoring equipment |

---

## 8. Imports & Exports

### Imports
**Lokasi:** `app/Imports/`

| File | Deskripsi |
|------|-----------|
| `TagNumberImport.php` | Import tag number dari Excel |
| `TagNumberImportUpdate.php` | Update tag number dari Excel |

### Exports
**Lokasi:** `app/Exports/`

| File | Deskripsi |
|------|-----------|
| `DynamicExport.php` | Export dinamis berbagai data |
| `MonitoringEquipmentExport.php` | Export data monitoring equipment |
| `MonitoringEquipmentLogExport.php` | Export log monitoring equipment |
| `MonitoringEquipmentReferenceSheet.php` | Sheet referensi monitoring equipment |
| `MonitoringEquipmentTemplateExport.php` | Template export monitoring equipment |
| `MonitoringEquipmentTemplateSheet.php` | Sheet template monitoring equipment |

---

## 9. Observers

**Lokasi:** `app/Observers/`

| File | Deskripsi |
|------|-----------|
| `GlobalActivityObserver.php` | Observer untuk logging aktivitas global (create, update, delete) |

---

## 10. Middleware

**Lokasi:** `app/Http/Middleware/`

| File | Deskripsi |
|------|-----------|
| `Authenticate.php` | Middleware autentikasi |
| `CustomCors.php` | CORS custom |
| `RoleMiddleware.php` | Middleware role-based access |
| `EncryptCookies.php` | Enkripsi cookies |
| `PreventRequestsDuringMaintenance.php` | Maintenance mode |
| `RedirectIfAuthenticated.php` | Redirect jika sudah login |
| `TrimStrings.php` | Trim whitespace dari input |
| `TrustHosts.php` | Trusted hosts |
| `TrustProxies.php` | Trusted proxies |
| `ValidateSignature.php` | Validasi URL signature |
| `VerifyCsrfToken.php` | Verifikasi CSRF token |

---

## 11. Providers

**Lokasi:** `app/Providers/`

| File | Deskripsi |
|------|-----------|
| `AppServiceProvider.php` | Service provider utama |
| `AuthServiceProvider.php` | Auth provider |
| `BroadcastServiceProvider.php` | Broadcast provider |
| `EventServiceProvider.php` | Event & listener provider |
| `RouteServiceProvider.php` | Route provider |

---

## 12. Migrations

**Lokasi:** `database/migrations/`

### Auth & User
| File | Tabel |
|------|-------|
| `2014_10_12_000000_create_users_table.php` | users |
| `2025_01_06_073553_create_level_user_table.php` | level_user |
| `2025_01_06_073916_create_employee_table.php` | employee |
| `2025_01_06_074350_create_role_table.php` | role |

### Master Data
| File | Tabel |
|------|-------|
| `2025_01_09_060909_create_units_table.php` | units |
| `2025_01_09_062227_create_categories_table.php` | categories |
| `2025_01_09_071047_create_types_table.php` | types |
| `2025_01_10_010614_create_tag_numbers_table.php` | tag_numbers |
| `2025_01_10_063435_create_plos_table.php` | plos |
| `2025_01_19_233958_create_cois_table.php` | cois |

### Contract Legacy
| File | Tabel |
|------|-------|
| `2025_03_24_005332_create_contracts_table.php` | contracts |
| `2025_04_14_101714_create_termins_table.php` | termins |
| `2025_04_15_142408_create_term_billings_table.php` | term_billings |
| `2025_04_17_102045_create_spks_table.php` | spks |
| `2025_04_19_144654_create_spk_progresses_table.php` | spk_progresses |
| `2025_04_26_085843_create_lumpsum_progresses_table.php` | lumpsum_progresses |
| `2025_05_12_141501_create_amandemens_table.php` | amandemens |
| `2025_05_22_104610_create_historical_memorandum_table.php` | historical_memorandum |
| `2025_06_10_084927_create_lampiran_memos_table.php` | lampiran_memos |

### Contract New
| File | Tabel |
|------|-------|
| `2026_01_22_142151_create_contract_news_table.php` | contract_news |
| `2026_01_23_091643_create_termin_news_table.php` | termin_news |
| `2026_01_23_103252_create_termin_receipt_news_table.php` | termin_receipt_news |
| `2026_01_24_162053_create_lumpsum_progress_news_table.php` | lumpsum_progress_news |
| `2026_01_24_171029_create_spk_news_table.php` | spk_news |
| `2026_02_09_145831_create_spk_progress_news_table.php` | spk_progress_news |
| `2026_02_14_145030_create_amandemen_news_table.php` | amandemen_news |

### Contract Jasa
| File | Tabel |
|------|-------|
| `2025_09_18_121213_create_contract_jasas_table.php` | contract_jasas |
| `2026_04_25_163612_create_contract_jasa_ohs_table.php` | contract_jasa_ohs |
| `2026_04_27_120841_create_contract_jasa_rtnrts_table.php` | contract_jasa_rtnrts |

### Event Readiness - Material (Standar)
| File | Tabel |
|------|-------|
| `2025_09_11_095808_create_event_readinesses_table.php` | event_readinesses |
| `2025_09_11_100010_create_readiness_materials_table.php` | readiness_materials |
| `2025_09_11_100556_create_rekomendasi_materials_table.php` | rekomendasi_materials |
| `2025_09_11_111833_create_notif_materials_table.php` | notif_materials |
| `2025_09_11_144835_create_job_plan_materials_table.php` | job_plan_materials |
| `2025_09_11_170207_create_pr_materials_table.php` | pr_materials |
| `2025_09_11_171322_create_tender_materials_table.php` | tender_materials |
| `2025_09_11_171956_create_po_materials_table.php` | po_materials |
| `2025_09_11_173149_create_fabrikasi_materials_table.php` | fabrikasi_materials |
| `2025_09_11_173620_create_delivery_materials_table.php` | delivery_materials |

### Event Readiness - Material (Overhaul)
| File | Tabel |
|------|-------|
| `2026_04_18_111340_create_event_readiness_ohs_table.php` | event_readiness_ohs |
| `2026_04_18_112221_create_readiness_material_ohs_table.php` | readiness_material_ohs |
| `2026_04_20_090303_create_rekomendasi_material_ohs_table.php` | rekomendasi_material_ohs |
| `2026_04_20_092135_create_notif_material_ohs_table.php` | notif_material_ohs |
| `2026_04_20_093211_create_job_plan_material_ohs_table.php` | job_plan_material_ohs |
| `2026_04_20_094050_create_pr_material_ohs_table.php` | pr_material_ohs |
| `2026_04_20_095210_create_tender_material_ohs_table.php` | tender_material_ohs |
| `2026_04_20_095945_create_po_material_ohs_table.php` | po_material_ohs |
| `2026_04_20_101151_create_fabrikasi_material_ohs_table.php` | fabrikasi_material_ohs |
| `2026_04_20_101608_create_delivery_material_ohs_table.php` | delivery_material_ohs |

### Event Readiness - Material (RTN/RT)
| File | Tabel |
|------|-------|
| `2026_04_26_164705_create_event_readiness_rtnrts_table.php` | event_readiness_rtnrts |
| `2026_04_26_170305_create_readiness_material_rtnrts_table.php` | readiness_material_rtnrts |
| `2026_04_26_170527_create_rekomendasi_material_rtnrts_table.php` | rekomendasi_material_rtnrts |
| `2026_04_26_171248_create_notif_material_rtnrts_table.php` | notif_material_rtnrts |
| `2026_04_26_171653_create_job_plan_material_rtnrts_table.php` | job_plan_material_rtnrts |
| `2026_04_26_172203_create_pr_material_rtnrts_table.php` | pr_material_rtnrts |
| `2026_04_26_172545_create_tender_material_rtnrts_table.php` | tender_material_rtnrts |
| `2026_04_26_172804_create_po_material_rtnrts_table.php` | po_material_rtnrts |
| `2026_04_26_173458_create_fabrikasi_material_rtnrts_table.php` | fabrikasi_material_rtnrts |
| `2026_04_26_173739_create_delivery_material_rtnrts_table.php` | delivery_material_rtnrts |

### Event Readiness - Jasa (Standar)
| File | Tabel |
|------|-------|
| `2025_09_18_114915_create_readiness_jasas_table.php` | readiness_jasas |
| `2025_09_18_115119_create_rekomendasi_jasas_table.php` | rekomendasi_jasas |
| `2025_09_18_115134_create_notif_jasas_table.php` | notif_jasas |
| `2025_09_18_115144_create_job_plan_jasas_table.php` | job_plan_jasas |
| `2025_09_18_115155_create_pr_jasas_table.php` | pr_jasas |
| `2025_09_18_115207_create_tender_jasas_table.php` | tender_jasas |

### Event Readiness - Jasa (Overhaul)
| File | Tabel |
|------|-------|
| `2026_04_25_150245_create_readiness_jasa_ohs_table.php` | readiness_jasa_ohs |
| `2026_04_25_155251_create_rekomendasi_jasa_ohs_table.php` | rekomendasi_jasa_ohs |
| `2026_04_25_161630_create_notif_jasa_ohs_table.php` | notif_jasa_ohs |
| `2026_04_25_162238_create_job_plan_jasa_ohs_table.php` | job_plan_jasa_ohs |
| `2026_04_25_162814_create_pr_jasa_ohs_table.php` | pr_jasa_ohs |
| `2026_04_25_163235_create_tender_jasa_ohs_table.php` | tender_jasa_ohs |

### Event Readiness - Jasa (RTN/RT)
| File | Tabel |
|------|-------|
| `2026_04_27_110432_create_readiness_jasa_rtnrts_table.php` | readiness_jasa_rtnrts |
| `2026_04_27_114354_create_rekomendasi_jasa_rtnrts_table.php` | rekomendasi_jasa_rtnrts |
| `2026_04_27_115344_create_notif_jasa_rtnrts_table.php` | notif_jasa_rtnrts |
| `2026_04_27_115624_create_job_plan_jasa_rtnrts_table.php` | job_plan_jasa_rtnrts |
| `2026_04_27_120349_create_pr_jasa_rtnrts_table.php` | pr_jasa_rtnrts |
| `2026_04_27_120610_create_tender_jasa_rtnrts_table.php` | tender_jasa_rtnrts |

### RKAP
| File | Tabel |
|------|-------|
| `2026_05_06_092808_create_rkap_tas_table.php` | rkap_tas |
| `2026_05_06_092814_create_detail_rkap_tas_table.php` | detail_rkap_tas |
| `2026_05_07_102001_create_rkap_ohs_table.php` | rkap_ohs |
| `2026_05_07_102014_create_detail_rkap_ohs_table.php` | detail_rkap_ohs |
| `2026_05_07_111734_create_rkap_rts_table.php` | rkap_rts |
| `2026_05_07_111745_create_detail_rkap_rts_table.php` | detail_rkap_rts |
| `2026_05_07_113009_create_rkap_nrs_table.php` | rkap_nrs |
| `2026_05_07_113025_create_detail_rkap_nrs_table.php` | detail_rkap_nrs |

### Certificate & Permit
| File | Tabel |
|------|-------|
| `2025_03_10_001823_create_skhps_table.php` | skhps |
| `2025_11_22_095334_create_sertifikat_kalibrasis_table.php` | sertifikat_kalibrasis |
| `2026_01_06_090028_create_izin_usahas_table.php` | izin_usahas |
| `2026_01_06_134950_create_izin_disnakers_table.php` | izin_disnakers |
| `2026_01_06_135554_create_report_izin_disnakers_table.php` | report_izin_disnakers |
| `2026_01_07_093159_create_izin_operasis_table.php` | izin_operasis |
| `2026_01_07_144508_create_report_izin_operasis_table.php` | report_izin_operasis |
| `2026_01_08_143421_create_pirs_table.php` | pirs |
| `2026_02_17_085522_create_nibs_table.php` | nibs |
| `2025_11_28_085419_create_report_cois_table.php` | report_cois |
| `2025_03_12_231924_create_report_plos_table.php` | report_plos |
| `2026_04_11_092224_create_bapk_cois_table.php` | bapk_cois |
| `2026_04_11_104416_create_bapk_plos_table.php` | bapk_plos |

### Inspection & Monitoring
| File | Tabel |
|------|-------|
| `2025_08_12_160013_create_laporan_inspections_table.php` | laporan_inspections |
| `2025_08_12_160451_create_internal_inspections_table.php` | internal_inspections |
| `2025_08_12_160507_create_external_inspections_table.php` | external_inspections |
| `2025_08_12_160519_create_onstream_inspections_table.php` | onstream_inspections |
| `2025_08_12_160548_create_surveillances_table.php` | surveillances |
| `2025_08_12_160602_create_breakdown_reports_table.php` | breakdown_reports |
| `2026_06_11_150459_create_monitoring_equipment_table.php` | monitoring_equipment |
| `2026_07_07_151752_create_monitoring_equipment_logs_table.php` | monitoring_equipment_logs |
| `2026_07_18_145710_create_kondisi_peralatans_table.php` | kondisi_peralatans |
| `2026_07_18_153205_create_status_peralatans_table.php` | status_peralatans |

### Engineering & Document
| File | Tabel |
|------|-------|
| `2025_06_11_105226_create_engineering_data_table.php` | engineering_data |
| `2025_06_12_145136_create_ga_drawings_table.php` | ga_drawings |
| `2025_06_12_145559_create_datasheets_table.php` | datasheets |
| `2025_09_10_092607_create_mocs_table.php` | mocs |
| `2026_01_12_144310_create_mdr_folders_table.php` | mdr_folders |
| `2026_01_12_154922_create_mdr_items_table.php` | mdr_items |
| `2026_06_06_092417_create_project_specs_table.php` | project_specs |

### Preventive & Activity
| File | Tabel |
|------|-------|
| `2026_01_05_100055_create_preventives_table.php` | preventives |
| `2026_01_05_151046_create_overhauls_table.php` | overhauls |
| `2025_09_19_153239_create_open_file_activities_table.php` | open_file_activities |
| `2025_08_10_155214_create_log_activities_table.php` | log_activities |
| `2025_10_31_111307_create_p_ids_table.php` | p_ids |
| `2026_04_13_145853_create_features_table.php` | features |
| `2026_04_13_151817_create_hak_akses_table.php` | hak_akses |
| `2026_04_13_152359_create_user_hak_akses_table.php` | user_hak_akses |

---

## 13. Seeders

**Lokasi:** `database/seeders/`

| File | Deskripsi |
|------|-----------|
| `DatabaseSeeder.php` | Seeder utama |
| `MonitoringEquipmentSeeder.php` | Seed data monitoring equipment |
| `RkapNrSeeder.php` | Seed data RKAP Non-Routine |
| `RkapOhSeeder.php` | Seed data RKAP Overhaul |
| `RkapRtSeeder.php` | Seed data RKAP Routine |
| `RkapTaSeeder.php` | Seed data RKAP Tahunan |

---

## 14. Routes

**Lokasi:** `routes/`

| File | Deskripsi |
|------|-----------|
| `api.php` | Route API (endpoint utama) |
| `web.php` | Route web (dashboard admin) |
| `channels.php` | Broadcast channels |
| `console.php` | Console commands/artisan schedule |

---

## 15. Config Files

**Lokasi:** `config/`

| File | Deskripsi |
|------|-----------|
| `jwt.php` | Konfigurasi JSON Web Token (authentication) |
| `cors.php` | Konfigurasi CORS (Cross-Origin Resource Sharing) |
| `auth.php` | Konfigurasi autentikasi (guards, providers) |
| `database.php` | Konfigurasi koneksi database |
| `app.php` | Konfigurasi aplikasi (name, env, url, providers) |
| `sanctum.php` | Konfigurasi Laravel Sanctum |
| `filesystems.php` | Konfigurasi filesystem & storage |
| `broadcasting.php` | Konfigurasi broadcasting |
| `cache.php` | Konfigurasi cache |
| `hashing.php` | Konfigurasi password hashing |
| `logging.php` | Konfigurasi logging |
| `mail.php` | Konfigurasi email |
| `queue.php` | Konfigurasi queue |
| `services.php` | Konfigurasi third-party services |
| `session.php` | Konfigurasi session |
| `view.php` | Konfigurasi view/template |

---

## 16. Flow Diagrams

**Lokasi:** `flow/`

| File | Deskripsi |
|------|-----------|
| `flow.drawio` | Flow diagram utama aplikasi |
| `category.drawio` | Flow master category |
| `COI.drawio` | Flow Certificate of Inspection |
| `PLO.drawio` | Flow Plant Location Organization |
| `tag_number.drawio` | Flow tag number |
| `type.drawio` | Flow master type |
| `unit.drawio` | Flow master unit |

---

## Catatan

- **Event Readiness** memiliki 3 variant: Standar, Overhaul (OH), dan RTN/RT
- **RKAP** memiliki 4 variant: Tahunan (Ta), Overhaul (Oh), Routine (Rt), Non-Routine (Nr)
- **Contract** memiliki 2 sistem: Legacy dan New
- **Contract Jasa** memiliki 3 variant sesuai dengan Event Readiness
- File dengan suffix `copy.php` di Models merupakan backup/draft, bukan digunakan aktif
