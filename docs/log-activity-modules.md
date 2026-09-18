# Log Activity - Standarisasi Penamaan Modul

Standarisasi penamaan `module` pada log activity. **BE adalah satu-satunya sumber penamaan yang sah.** FE hanya mengikuti daftar ini saat mengirim `feature` pada `POST /activity/visit`.

## Aturan Inti

1. **Hanya fitur utama yang dicatat saat dikunjungi (visit).** Sub-halaman (detail, report, bapk, lampiran, dsb.) TIDAK dicatat sebagai visit.
2. FE mengirim **label persis** (bukan path, bukan judul menu) sebagai `feature` pada `POST /activity/visit`.
3. Semua nilai `module` di database harus salah satu label kanonikal di bawah (kecuali `Auth` untuk login/logout).
4. Fitur baru di FE cukup ditambahkan satu baris di `config/log-activity.php` bagian `features`, lalu FE mengambil daftar terbaru lewat `GET /activity/features`.

## Endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/activity/features` | Daftar label fitur utama (kanonikal) yang boleh dikirim FE |
| POST | `/activity/visit` | Mencatat kunjungan fitur `{ "feature": "<label>", "duration_seconds": 10 }` |

## Daftar Label Kanonikal

| Path | Label (kirim persis ini) | Group |
|------|--------------------------|-------|
| `/dashboard` | `Dashboard` | Utama |
| `/unit` | `Unit` | Master Data |
| `/log-activity` | `Log Activity` | Master Data |
| `/kategori-peralatan` | `Kategori Peralatan` | Master Data |
| `/tipe-peralatan` | `Tipe Peralatan` | Master Data |
| `/tag-number` | `Tag Number` | Master Data |
| `/status-kondisi-peralatan` | `Status & Kondisi Peralatan` | Master Data |
| `/user` | `User` | Master Data |
| `/plo` | `Plo` | Regulatory |
| `/coi` | `Coi` | Regulatory |
| `/skhp` | `Skhp` | Regulatory |
| `/sertifikat-kalibrasi` | `Sertifikat Kalibrasi` | Regulatory |
| `/izin-usaha` | `Izin Usaha` | Regulatory |
| `/nib` | `Nib` | Regulatory |
| `/izin-operasi` | `Izin Operasi` | Regulatory |
| `/izin-disnaker` | `Izin Disnaker` | Regulatory |
| `/contract` | `Contract` | Kontrak |
| `/historical-memorandum` | `Historical Memorandum` | Memo |
| `/equipment-data` | `Equipment Data` | Engineering Data |
| `/iso-metric` | `ISO Metric` | Engineering Data |
| `/p-id` | `P Id` | Engineering Data |
| `/project-spec` | `Project Spec` | Engineering Data |
| `/eca-fsca` | `Eca Fsca` | Engineering Data |
| `/fitur-gms` | `Fitur Gms` | Engineering Data |
| `/fitur-ems` | `Fitur Ems` | Engineering Data |
| `/laporan-inspection` | `Laporan Inspection` | Inspeksi |
| `/historical-equipment` | `Historical Equipment` | Inspeksi |
| `/readiness-ta` | `Readiness TA` | Plant Stop |
| `/pir-readiness-ta` | `PIR` | Plant Stop |
| `/readiness-overhaul` | `Readiness Overhaul` | Monitoring RKAP |
| `/readiness-routine` | `Routine Non Routine` | Monitoring RKAP |
| `/wbs-dashboard` | `WBS Dashboard` | Monitoring RKAP |
| `/wbs-rt` | `WBS RT` | Monitoring RKAP |
| `/wbs-oh` | `WBS OH` | Monitoring RKAP |
| `/wbs-nr` | `WBS NR` | Monitoring RKAP |
| `/wbs-ta` | `WBS TA` | Monitoring RKAP |
| `/monitoring-equipment` | `Monitoring Equipment` | Monitoring |

> Catatan: `Auth` (login/logout) adalah label khusus sistem, bukan fitur dan tidak dikirim FE.

## Contoh Request Visit

```json
POST /activity/visit
{
  "feature": "Contract",
  "duration_seconds": 12
}
```

> Jangan kirim `feature: "/contract"`, judul menu `"Contract"` tidak masalah karena sama, tetapi yang wajib adalah **label** dari tabel di atas. Kunjungan ke `/contract/123` (halaman detail) TIDAK dikirim.

## Pemetaan Sub-Entity BE ke Label Kanonikal

Ditangani otomatis oleh `App\Support\ActivityModule::label()` lewat `config/log-activity.php` bagian `aliases`. FE tidak perlu tahu hal ini.

| Identifier BE | → Label |
|---|---|
| ContractNew, SpkNew, AmandemenNew, SpkProgressNew, LumpsumProgressNew, TerminReceiptNew, TerminNew, Termin, ContractJasa, ContractJasaOh, ContractJasaRtnrt, Spk, Spk_progress, Lumpsum_progress, Amandemen | `Contract` |
| BapkCoi, ReportCoi | `Coi` |
| BapkPlo, ReportPlo | `Plo` |
| ReportIzinOperasi | `Izin Operasi` |
| ReportIzinDisnaker | `Izin Disnaker` |
| LampiranMemo | `Historical Memorandum` |
| InternalInspection, ExternalInspection, OnstreamInspection, Surveillance, Preventive, Overhaul, BreakdownReport | `Laporan Inspection` |
| Datasheet, GaDrawing, MdrFolder, MdrItem | `Equipment Data` |
| Ems | `Fitur Ems` |
| Gms | `Fitur Gms` |
| Pir | `PIR` |
| P_id | `P Id` |
| IsoMetric | `ISO Metric` |
| EcaFsca | `Eca Fsca` |
| Tag_number | `Tag Number` |
| ProjectSpec | `Project Spec` |
| MonitoringEquipment | `Monitoring Equipment` |
| LaporanInspection | `Laporan Inspection` |
| SertifikatKalibrasi | `Sertifikat Kalibrasi` |
| HistoricalMemorandum | `Historical Memorandum` |
| IzinUsaha, IzinOperasi, IzinDisnaker, Nib | masing-masing label |
| Coi, Plo, Skhp, Contract, Unit | masing-masing label |
| Auth | `Auth` (sistem, bukan fitur) |

## Cara Menambahkan Fitur Baru

1. FE: tambahkan item navigasi (seperti biasa).
2. BE: tambahkan satu baris di `config/log-activity.php` bagian `features`:
   ```php
   '/fitur-baru' => 'Fitur Baru',
   ```
3. Kalau ada model/class BE dengan nama yang prettify-nya beda dari label (mis. `FiturBaruNew`), tambahkan alias di bagian `aliases`:
   ```php
   'FiturBaruNew' => 'Fitur Baru',
   ```
4. FE mengambil daftar terbaru dari `GET /activity/features` dan mengirim `feature` sesuai label.

Tidak perlu mengubah `ActivityModule.php`, `ActivityLogger.php`, atau observer — semua otomatis.

## File Terkait

- `config/log-activity.php` — sumber penamaan (features + aliases)
- `app/Support/ActivityModule.php` — resolver label()
- `app/Services/ActivityLogger.php` — normalisasi saat menulis log
- `app/Http/Controllers/ActivityController.php` — endpoint visit & features
- `app/Console/Commands/NormalizeActivityModules.php` — backfill data lama (`php artisan activity:normalize-modules`)