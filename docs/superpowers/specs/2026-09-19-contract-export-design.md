# Design: Export Excel Contract (ContractNew)

**Tanggal:** 2026-09-19
**Status:** Draft untuk review

## Latar Belakang

Fitur export Excel untuk modul Contract, dengan sumber data **ContractNew**
(model `App\Models\ContractNew`, tabel `contract_news`). Sistem legacy
(`Contract`) sudah tidak dipakai.

Export memiliki 2 cakupan:
1. Export semua kontrak.
2. Export hanya kontrak terpilih — FE mengirim daftar `id` contract.

## Endpoint

`POST /api/contract_new/export`

```json
{ "ids": [3, 7, 12] }
```

- `ids` opsional + nullable, harus `array` , tiap elemen `integer`.
- `ids` kosong / tidak dikirim → export **semua** kontrak.
- `ids` terisi → `ContractNew::whereIn('id', $ids)`.
- Respons: `Excel::download(..., 'Contract_' . now()->format('Ymd_His') . '.xlsx')`
  + `activity()->log('export', 'ContractNew')` (pola monitoring_equipment).

## Kolom Export (15 kolom)

| # | Label | Sumber | Format / Mapping |
|---|-------|--------|------------------|
| 1 | No Vendor | `no_vendor` | kosong → `-` |
| 2 | Nama Vendor | `vendor_name` | kosong → `-` |
| 3 | No SP/PO | `no_contract` | kosong → `-` |
| 4 | Nama Contract | `contract_name` | kosong → `-` |
| 5 | Tipe | `contract_type` | 1=Lumpsum, 2=Unit Price, 3=PO Material, 4=PO Jasa |
| 6 | Pengawas | `pengawas` | 0=Inspection, 1=Maintenance Execution, 2=Procurement |
| 7 | Price | `contract_price` | angka, format `#,##0` |
| 8 | Contract Date | `contract_date` | `dd-mm-yyyy`, kosong → `-` |
| 9 | Contract Start | `contract_start_date` | `dd-mm-yyyy`, kosong → `-` |
| 10 | Contract End | `contract_end_date` | `dd-mm-yyyy`, kosong → `-` |
| 11 | Sisa MPP | `durasi_mpp['sisa']` (sisa hari) | warna dihitung ulang |
| 12 | Deviasi Progress | `deviation_progress['deviation']` | format `#,##0.00`, warna dari accessor |
| 13 | Current Status | `current_status` | kosong → `-` |
| 14 | Sisa Nilai Kontrak | `sisa_nilai['sisa']` | angka, format `#,##0`, warna dari accessor |
| 15 | Status | `contract_status` | 0=Selesai, 1=Aktif |

Semua value yang tidak ada (null / kosong) diisi `-`.

## Warna Sel

Tulisan **putih** untuk semua warna kecuali **kuning** (tulisan hitam).

| Warna | Hex | Sisa MPP | Deviasi Progress | Sisa Nilai Kontrak |
|-------|-----|----------|------------------|--------------------|
| Blue | `1E88E5` | `contract_status == 0` | `contract_status == 0` | `contract_status == 0` |
| Red | `E53935` | `sisa <= 0` | `deviation <= -20` | `sisa <= 0` |
| Yellow | `FFEB3B` | `sisa <= 28` | `-20 < deviation < 0` | `sisa <= 20% price` |
| Green | `43A047` | lainnya | `deviation >= 0` | lainnya |
| Black | `000000` | — | amandemen belum diupload | — |

> Catatan: warna **Sisa MPP** **dihitung manual** di controller export
> (blue→red→yellow→green), TIDAK memakai `durasi_mpp['color']` karena
> accessor memiliki klausul ekstra (amandemen / penagihan) yang tidak
> diinginkan di export. Value tetap dari `durasi_mpp['sisa']`.

Warna Deviasi & Sisa Nilai memakai `color` dari accessor masing-masing
(`deviation_progress['color']`, `sisa_nilai['color']`) agar konsisten dengan
dashboard monitoring.

## Implementasi

### 1. `app/Exports/DynamicExport.php` — tambah 2 opsi (additive)

- `headerStyle` (array style) → diterapkan ke range baris judul
  `A{headingRow}:{lastColLetter}{headingRow}` di `AfterSheet`.
- `cellConditional` (array rules `['column' => key, 'condition' => fn($row) => bool, 'style' => [...]]`)
  → untuk tiap baris data, jika `condition($row)` true, terapkan style ke **1 sel**
  pada kolom tsb (`{colLetter}{row}`). Berbeda dari `conditional` existing yang
  mewarnai 1 baris penuh. `condition` menerima seluruh array row agar bisa
  membaca key internal `_*_color`.

### 2. `app/Http/Controllers/ContractNewController.php` — method `export`

- Validasi `ids`.
- Ambil data sesuai `ids` / semua.
- Bangun array per baris (mapping label + accessor + key internal
  `_mpp_color`, `_dev_color`, `_sisa_color` yang TIDAK ditampilkan sebagai kolom).
- Panggil `Excel::download(new DynamicExport($data, $columns, $options), 'Contract_...xlsx')`
  dengan opsi: `headerStyle` (bold + fill `D9E1F2`), `border` (header+data),
  `autoWidth` (max 50), `freezeHeader`, `filter`, `numberFormat`.

### 3. `routes/api.php`

```php
Route::post('/contract_new/export', [ContractNewController::class, 'export']);
```

### 4. `docs/idms-documentation/05-manajemen-kontrak.md`

Tambahkan baris endpoint `POST /api/contract_new/export` di tabel API Contract New.

## Gaya Sheet

- Header: bold + fill `D9E1F2`.
- Auto width (max 50), border tipis, freeze baris header, auto-filter aktif.