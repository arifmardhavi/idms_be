# Database Schema IDMS Backend

## 1. ER Diagram (Text-Based)

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    users     │       │   features   │       │  hak_akses   │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id           │       │ id           │       │ id           │
│ fullname     │       │ feature      │◄──┐   │ feature_id   │──►features.id
│ email (UQ)   │       │ group        │   │   │ hak_akses    │
│ username     │       └──────────────┘   │   └──────┬───────┘
│ password     │                          │          │
│ level_user   │    ┌─────────────────┐   │          │
│ status       │    │ user_hak_akses  │   │          │
└──────┬───────┘    ├─────────────────┤   │          │
       │            │ id              │   │          │
       │            │ user_id         │──►users.id  │
       │            │ hak_akses_id    │──►hak_akses.id
       │            └─────────────────┘              │
       │                                             │
       ▼                                             │
┌──────────────┐    ┌──────────────┐                 │
│ log_activities│   │ open_file_   │                 │
├──────────────┤    │ activities   │                 │
│ id           │    ├──────────────┤                 │
│ user_id      │──► │ id           │                 │
│ module       │    │ user_id      │──►users.id     │
│ action       │    │ file_name    │                 │
│ changes (JSON)│   │ features     │                 │
│ ip_address   │    └──────────────┘                 │
│ user_agent   │                                     │
└──────────────┘                                     │
                                                      │
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│    units     │    │  categories  │    │    types     │
├──────────────┤    ├──────────────┤    ├──────────────┤
│ id           │    │ id           │◄───│ category_id  │
│ unit_name    │    │ category_name│    │ id           │
│ unit_type    │    │ description  │    │ type_name    │
│ description  │    │ status       │    │ description  │
│ status       │    └──────────────┘    │ status       │
└──────┬───────┘                        └──────┬───────┘
       │                                       │
       │        ┌──────────────────┐           │
       └───────►│   tag_numbers    │◄──────────┘
                ├──────────────────┤
                │ id               │
                │ unit_id          │──►units.id
                │ type_id          │──►types.id
                │ tag_number       │
                │ criticality      │
                │ sece             │
                │ description      │
                │ status           │
                └────────┬─────────┘
                         │
          ┌──────────────┼──────────────┬──────────────┐
          │              │              │              │
          ▼              ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   cois       │ │   skhps      │ │engineering_  │ │monitoring_   │
│              │ │              │ │   data       │ │  equipment   │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

┌──────────────┐    ┌──────────────┐
│  contracts   │    │ contract_news│
├──────────────┤    ├──────────────┤
│ id           │    │ id           │
│ no_vendor    │    │ no_vendor    │
│ vendor_name  │    │ vendor_name  │
│ no_contract  │    │ no_contract  │
│ contract_name│    │ contract_name│
│ contract_type│    │ contract_type│
│ contract_date│    │ contract_date│
│ contract_price│   │ contract_price│
│ contract_file│    │ contract_file│
│ ...          │    │ tkdn         │
└──────┬───────┘    └──────┬───────┘
       │                   │
       ▼                   ▼
┌──────────────┐    ┌──────────────┐
│   termins    │    │ termin_news  │
│   spks       │    │ spk_news     │
│   spk_       │    │ lumpsum_     │
│   progresses │    │ progress_news│
└──────────────┘    │ amandemen_   │
                    │ news         │
                    └──────────────┘
```

## 2. Semua Tabel (Organized by Module)

---

### 2.1 Authentication & Authorization

#### `users`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| fullname | varchar | NOT NULL | Nama lengkap user |
| email | varchar | UNIQUE | Email login |
| username | varchar | NULLABLE | Username login |
| password | text | NOT NULL | Hashed password |
| level_user | integer | NOT NULL | Level hak akses |
| status | integer | DEFAULT 0 | Status aktif (0/1) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `features`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| feature | varchar | NOT NUL | Nama fitur |
| group | varchar | NULLABLE | Kelompok fitur |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `hak_akses`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| feature_id | bigint | FK → features.id, CASCADE | Relasi ke features |
| hak_akses | varchar | NOT NULL | Nama hak akses |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `user_hak_akses`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| user_id | bigint | FK → users.id, CASCADE | Relasi ke users |
| hak_akses_id | bigint | FK → hak_akses.id, CASCADE | Relasi ke hak_akses |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.2 Master Data

#### `units`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_name | varchar | NOT NULL | Nama unit |
| unit_type | integer | DEFAULT 0 | Tipe unit (0/1) |
| description | text | NULLABLE | Deskripsi |
| status | integer | DEFAULT 0 | Status aktif (0/1) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `categories`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| category_name | varchar | NOT NULL | Nama kategori |
| description | text | NULLABLE | Deskripsi |
| status | integer | DEFAULT 0 | Status aktif (0/1) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `types`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| type_name | varchar | NOT NULL | Nama tipe |
| description | text | NULLABLE | Deskripsi |
| status | char(1) | DEFAULT '0' | Status aktif |
| category_id | bigint | FK → categories.id, CASCADE | Relasi ke categories |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `tag_numbers`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_id | bigint | FK → units.id, CASCADE | Relasi ke units |
| type_id | bigint | FK → types.id, CASCADE | Relasi ke types |
| tag_number | varchar | NOT NULL | Nomor tag equipment |
| criticality | char(2) | NULLABLE | Level kritikalitas |
| sece | char(1) | NULLABLE | Status SECE |
| description | text | NULLABLE | Deskripsi |
| status | integer | DEFAULT 0 | Status aktif (0/1) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.3 Contract Management

#### `contracts`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| no_vendor | varchar | NOT NULL | Nomor vendor |
| vendor_name | varchar | NOT NUL | Nama vendor |
| no_contract | varchar | NOT NULL | Nomor kontrak |
| contract_name | varchar | NOT NULL | Nama kontrak |
| contract_type | char(1) | DEFAULT '1' | Tipe kontrak |
| contract_date | date | NOT NULL | Tanggal kontrak |
| contract_price | bigint | NOT NULL | Nilai kontrak |
| contract_file | varchar | NOT NULL | File kontrak |
| kom | char(1) | DEFAULT '0' | Status komisioning |
| contract_start_date | date | NULLABLE | Tanggal mulai |
| contract_end_date | date | NULLABLE | Tanggal berakhir |
| meeting_notes | varchar | NULLABLE | Catatan meeting |
| contract_status | char(1) | DEFAULT '1' | Status kontrak |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `contract_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| no_vendor | varchar | NOT NULL | Nomor vendor |
| vendor_name | varchar | NOT NULL | Nama vendor |
| no_contract | varchar | NOT NULL | Nomor kontrak |
| contract_name | varchar | NOT NULL | Nama kontrak |
| contract_type | tinyint | UNSIGNED | Tipe kontrak |
| contract_date | date | NULLABLE | Tanggal kontrak |
| contract_price | bigint | NOT NULL | Nilai kontrak |
| contract_file | text | NOT NULL | File kontrak |
| current_status | text | NULLABLE | Status saat ini |
| tkdn | bigint | NULLABLE | Tingkat Komponen Dalam Negeri |
| contract_start_date | date | NULLABLE | Tanggal mulai |
| contract_end_date | date | NULLABLE | Tanggal berakhir |
| meeting_notes | text | NULLABLE | Catatan meeting |
| pengawas | tinyint | UNSIGNED | Pengawas kontrak |
| contract_status | tinyint | DEFAULT 0, UNSIGNED | Status kontrak |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `contract_new_user`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| user_id | bigint | FK → users.id, CASCADE | Relasi ke users |
| contract_new_id | bigint | FK → contract_news.id, CASCADE | Relasi ke contract_news |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `termins`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_id | bigint | FK → contracts.id, CASCADE | Relasi ke contracts |
| termin | varchar | NOT NULL | Nama termin |
| description | varchar | NOT NULL | Deskripsi termin |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `termin_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_new_id | bigint | FK → contract_news.id, CASCADE | Relasi ke contract_news |
| termin | varchar | NOT NULL | Nama termin |
| description | varchar | NULLABLE | Deskripsi termin |
| receipt_nominal | bigint | NULLABLE | Nominal penerimaan |
| receipt_file | varchar | NULLABLE | File penerimaan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `termin_receipt_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| termin_new_id | bigint | FK → termin_news.id, CASCADE | Relasi ke termin_news |
| receipt_nominal | bigint | UNSIGNED | Nominal penerimaan |
| receipt_file | varchar | NOT NULL | File penerimaan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `spks`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_id | bigint | FK → contracts.id, CASCADE | Relasi ke contracts |
| no_spk | varchar | NOT NULL | Nomor SPK |
| spk_name | varchar | NOT NUL | Nama SPK |
| spk_start_date | date | NOT NULL | Tanggal mulai SPK |
| spk_end_date | date | NOT NULL | Tanggal berakhir SPK |
| spk_price | bigint | NOT NULL | Nilai SPK |
| spk_file | varchar | NOT NULL | File SPK |
| spk_status | char(1) | DEFAULT '1' | Status SPK |
| invoice | char(1) | DEFAULT '0' | Status invoice |
| invoice_value | bigint | NULLABLE | Nilai invoice |
| invoice_file | varchar | NULLABLE | File invoice |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `spk_progresses`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| spk_id | bigint | FK → spks.id, CASCADE | Relasi ke spks |
| week | integer | NOT NULL | Minggu ke- |
| actual_progress | decimal(5,2) | NOT NULL | Progress aktual |
| plan_progress | decimal(5,2) | NOT NULL | Progress rencana |
| progress_file | varchar | NOT NULL | File progress |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `spk_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_new_id | bigint | FK → contract_news.id, CASCADE | Relasi ke contract_news |
| no_spk | varchar | NOT NULL | Nomor SPK |
| spk_name | varchar | NOT NULL | Nama SPK |
| spk_start_date | date | NOT NULL | Tanggal mulai SPK |
| spk_end_date | date | NOT NULL | Tanggal berakhir SPK |
| spk_price | bigint | NOT NULL | Nilai SPK |
| spk_file | varchar | NOT NULL | File SPK |
| spk_status | tinyint | DEFAULT 0 | Status SPK |
| receipt_nominal | bigint | NULLABLE | Nominal penerimaan |
| receipt_file | varchar | NULLABLE | File penerimaan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `spk_progress_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| spk_new_id | bigint | FK → spk_news.id, CASCADE | Relasi ke spk_news |
| week | tinyint | UNSIGNED | Minggu ke- |
| plan | decimal(5,2) | NOT NULL | Progress rencana |
| actual | decimal(5,2) | NOT NULL | Progress aktual |
| progress_file | varchar | NOT NULL | File progress |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `lumpsum_progress_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_new_id | bigint | FK → contract_news.id, CASCADE | Relasi ke contract_news |
| week | integer | UNSIGNED | Minggu ke- |
| plan | decimal(5,2) | NOT NULL | Progress rencana |
| actual | decimal(5,2) | NOT NULL | Progress aktual |
| progress_file | varchar | NOT NULL | File progress |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `amandemen_news`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| contract_new_id | bigint | FK → contract_news.id, CASCADE | Relasi ke contract_news |
| contract_price_before_amandemen | bigint | NULLABLE | Nilai kontrak sebelum amandemen |
| contract_end_date_before_amandemen | date | NULLABLE | Tanggal berakhir sebelum amandemen |
| ba_agreement_file | text | NULLABLE | File BA Agreement |
| result_amandemen_file | text | NULLABLE | File hasil amandemen |
| principle_permit_file | text | NULLABLE | File Principle Permit |
| amandemen_price | bigint | NULLABLE | Nilai amandemen |
| amandemen_end_date | date | NULLABLE | Tanggal berakhir amandemen |
| amandemen_penalty | tinyint | DEFAULT 0 | Penalty amandemen |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.4 Certificates (PLO, COI, SKHP, Kalibrasi, Izin)

#### `plos`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_id | bigint | FK → units.id, CASCADE | Relasi ke units |
| no_certificate | varchar | NOT NULL | Nomor sertifikat PLO |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| plo_certificate | varchar | NULLABLE | File sertifikat PLO |
| plo_old_certificate | varchar | NULLABLE | File sertifikat PLO lama |
| rla | integer | DEFAULT 0 | Status RLA |
| rla_issue | date | NULLABLE | Tanggal terbit RLA |
| rla_overdue | date | NULLABLE | Tanggal kadaluarsa RLA |
| rla_certificate | text | NULLABLE | File sertifikat RLA |
| rla_old_certificate | text | NULLABLE | File sertifikat RLA lama |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `cois`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| plo_id | bigint | FK → plos.id, CASCADE | Relasi ke plos |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| no_certificate | varchar | NOT NULL | Nomor sertifikat COI |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| coi_certificate | text | NULLABLE | File sertifikat COI |
| coi_old_certificate | text | NULLABLE | File sertifikat COI lama |
| rla | integer | DEFAULT 0 | Status RLA |
| rla_issue | date | NULLABLE | Tanggal terbit RLA |
| rla_overdue | date | NULLABLE | Tanggal kadaluarsa RLA |
| rla_certificate | text | NULLABLE | File sertifikat RLA |
| rla_old_certificate | text | NULLABLE | File sertifikat RLA lama |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `skhps`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| plo_id | bigint | FK → plos.id, CASCADE | Relasi ke plos |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| no_skhp | varchar | NOT NULL | Nomor SKHP |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| file_skhp | text | NULLABLE | File SKHP |
| file_old_skhp | text | NULLABLE | File SKHP lama |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `sertifikat_kalibrasis`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| plo_id | bigint | FK → plos.id, CASCADE | Relasi ke plos |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| no_sertifikat_kalibrasi | varchar | NOT NULL | Nomor sertifikat kalibrasi |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| file_sertifikat_kalibrasi | text | NULLABLE | File sertifikat kalibrasi |
| file_old_sertifikat_kalibrasi | text | NULLABLE | File sertifikat kalibrasi lama |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `izin_usahas`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| no_izin_usaha | varchar | NULLABLE | Nomor izin usaha |
| judul | varchar | NOT NULL | Judul izin usaha |
| tanggal_izin_usaha | date | NOT NULL | Tanggal izin usaha |
| izin_usaha_file | text | NOT NULL | File izin usaha |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `izin_disnakers`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| plo_id | bigint | FK → plos.id, CASCADE | Relasi ke plos |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| no_certificate | varchar | NOT NULL | Nomor sertifikat |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| izin_disnaker_certificate | text | NULLABLE | File sertifikat Disnaker |
| izin_disnaker_old_certificate | text | NULLABLE | File sertifikat Disnaker lama |
| rla | integer | DEFAULT 0 | Status RLA |
| rla_issue | date | NULLABLE | Tanggal terbit RLA |
| rla_overdue | date | NULLABLE | Tanggal kadaluarsa RLA |
| rla_certificate | text | NULLABLE | File sertifikat RLA |
| rla_old_certificate | text | NULLABLE | File sertifikat RLA lama |
| re_engineer | text | NULLABLE | Re-engineer |
| re_engineer_certificate | text | NULLABLE | File re-engineer |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `izin_operasis`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_id | bigint | FK → units.id, CASCADE | Relasi ke units |
| no_certificate | varchar | NOT NULL | Nomor sertifikat |
| issue_date | date | NOT NULL | Tanggal terbit |
| overdue_date | date | NOT NULL | Tanggal kadaluarsa |
| izin_operasi_certificate | varchar | NULLABLE | File sertifikat izin operasi |
| izin_operasi_old_certificate | varchar | NULLABLE | File sertifikat izin operasi lama |
| rla | integer | DEFAULT 0 | Status RLA |
| rla_issue | date | NULLABLE | Tanggal terbit RLA |
| rla_overdue | date | NULLABLE | Tanggal kadaluarsa RLA |
| rla_certificate | text | NULLABLE | File sertifikat RLA |
| rla_old_certificate | text | NULLABLE | File sertifikat RLA lama |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `report_cois`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| coi_id | bigint | FK → cois.id, CASCADE | Relasi ke cois |
| report_coi | text | NOT NULL | File report COI |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `bapk_cois`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| coi_id | bigint | FK → cois.id, CASCADE | Relasi ke cois |
| bapk_coi | text | NOT NULL | File BAPK COI |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `bapk_plos`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| plo_id | bigint | FK → plos.id, CASCADE | Relasi ke plos |
| bapk_plo | text | NOT NULL | File BAPK PLO |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `report_izin_disnakers`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| izin_disnaker_id | bigint | FK → izin_disnakers.id, CASCADE | Relasi ke izin_disnakers |
| report_izin_disnaker | text | NOT NULL | File report izin Disnaker |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `report_izin_operasis`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| izin_operasi_id | bigint | FK → izin_operasis.id, CASCADE | Relasi ke izin_operasis |
| report_izin_operasi | text | NOT NULL | File report izin operasi |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.5 Memorandum & Document Management

#### `historical_memorandum`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_id | bigint | FK → units.id, CASCADE | Relasi ke units |
| category_id | bigint | FK → categories.id, CASCADE | Relasi ke categories |
| tag_number_id | bigint | NULLABLE | Relasi ke tag_numbers |
| no_dokumen | varchar | UNIQUE | Nomor dokumen memo |
| perihal | varchar | NOT NULL | Perihal memo |
| tipe_memorandum | integer | DEFAULT 0 | Tipe memorandum |
| tanggal_terbit | date | NOT NULL | Tanggal terbit |
| memorandum_file | text | NOT NULL | File memorandum |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `lampiran_memos`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| historical_memorandum_id | bigint | FK → historical_memorandum.id, CASCADE | Relasi ke historical_memorandum |
| lampiran_memo | text | NOT NULL | File lampiran memo |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `mocs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| unit_id | bigint | FK → units.id, CASCADE | Relasi ke units |
| category_id | bigint | FK → categories.id, CASCADE | Relasi ke categories |
| tag_number_id | varchar | NULLABLE | Relasi ke tag_numbers |
| no_dokumen | varchar | UNIQUE | Nomor dokumen MOC |
| perihal | varchar | NOT NULL | Perihal MOC |
| tipe_moc | integer | DEFAULT 0 | Tipe MOC |
| tanggal_terbit | date | NOT NULL | Tanggal terbit |
| moc_file | text | NOT NULL | File MOC |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `pirs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| judul | varchar | NOT NULL | Judul PIR |
| tanggal_pir | date | NOT NULL | Tanggal PIR |
| pir_file | text | NOT NULL | File PIR |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `nibs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| no_nib | varchar | NULLABLE | Nomor NIB |
| judul | varchar | NOT NULL | Judul NIB |
| tanggal_nib | date | NOT NULL | Tanggal NIB |
| nib_file | text | NOT NULL | File NIB |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.6 Engineering Data

#### `engineering_data`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `ga_drawings`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| engineering_data_id | bigint | FK → engineering_data.id, CASCADE | Relasi ke engineering_data |
| drawing_file | varchar | NOT NULL | File GA Drawing |
| nama_dokumen | varchar | NULLABLE | Nama dokumen |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `datasheets`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| engineering_data_id | bigint | FK → engineering_data.id, CASCADE | Relasi ke engineering_data |
| datasheet_file | varchar | NOT NULL | File datasheet |
| nama_dokumen | varchar | NULLABLE | Nama dokumen |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `mdr_folders`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| engineering_data_id | bigint | FK → engineering_data.id, CASCADE | Relasi ke engineering_data |
| folder_name | varchar | NOT NULL | Nama folder MDR |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `mdr_items`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| mdr_folder_id | bigint | FK → mdr_folders.id, CASCADE | Relasi ke mdr_folders |
| file_name | varchar | NOT NULL | Nama file |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `p_ids`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| file_name | varchar | NULLABLE | Nama file |
| p_id_file | text | NULLABLE | File P&ID |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.7 Inspection

#### `laporan_inspections`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `internal_inspections`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| laporan_inspection_id | bigint | FK → laporan_inspections.id, CASCADE | Relasi ke laporan_inspections |
| judul | varchar | NOT NULL | Judul inspeksi |
| inspection_date | date | NOT NULL | Tanggal inspeksi |
| historical_memorandum_id | bigint | NULLABLE, FK → historical_memorandum.id, SET NULL | Relasi ke memorandum |
| laporan_file | text | NULLABLE | File laporan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `overhauls`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| laporan_inspection_id | bigint | FK → laporan_inspections.id, CASCADE | Relasi ke laporan_inspections |
| judul | varchar | NOT NULL | Judul overhaul |
| overhaul_date | date | NOT NULL | Tanggal overhaul |
| historical_memorandum_id | bigint | NULLABLE, FK → historical_memorandum.id, SET NULL | Relasi ke memorandum |
| laporan_file | text | NULLABLE | File laporan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `preventives`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| laporan_inspection_id | bigint | FK → laporan_inspections.id, CASCADE | Relasi ke laporan_inspections |
| judul | varchar | NOT NULL | Judul preventive |
| preventive_date | date | NOT NULL | Tanggal preventive |
| historical_memorandum_id | bigint | NULLABLE, FK → historical_memorandum.id, SET NULL | Relasi ke memorandum |
| laporan_file | text | NULLABLE | File laporan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.8 Readiness (Turnaround & Shutdown)

#### 2.8.1 TAS (Turnaround & Shutdown) - Master

##### `event_readinesses`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_name | varchar | NOT NULL | Nama event |
| tanggal_ta | date | NOT NULL | Tanggal TA |
| status | tinyint | DEFAULT 1 | Status event |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_materials`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_id | bigint | FK → event_readinesses.id, CASCADE | Relasi ke event_readinesses |
| material_name | varchar | NOT NULL | Nama material |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| type | integer | DEFAULT 0 | Tipe (0: LLDI, 1: Non LLDI) |
| current_status | text | NULLABLE | Status saat ini |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_jasas`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_id | bigint | FK → event_readinesses.id, CASCADE | Relasi ke event_readinesses |
| jasa_name | varchar | NOT NULL | Nama jasa |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| current_status | text | NULLABLE | Status saat ini |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### 2.8.2 Material Pipeline (TAS)

| Tabel | FK ke | Field Tambahan |
|-------|-------|----------------|
| `rekomendasi_materials` | readiness_materials | rekomendasi_file, target_date, status, historical_memorandum_id (FK) |
| `notif_materials` | readiness_materials | no_notif, target_date, status |
| `job_plan_materials` | readiness_materials | no_wo, kak_file, boq_file, target_date, status |
| `pr_materials` | readiness_materials | no_pr, target_date, status |
| `tender_materials` | readiness_materials | description, target_date, status |
| `po_materials` | readiness_materials | contract_id (FK→contracts), no_po, delivery_date, target_date, status |
| `fabrikasi_materials` | readiness_materials | description, target_date, status |
| `delivery_materials` | readiness_materials | description, delivery_file, target_date, status |

#### 2.8.3 Jasa Pipeline (TAS)

| Tabel | FK ke | Field Tambahan |
|-------|-------|----------------|
| `rekomendasi_jasas` | readiness_jasas | rekomendasi_file, target_date, status, historical_memorandum_id (FK) |
| `notif_jasas` | readiness_jasas | no_notif, target_date, status |
| `job_plan_jasas` | readiness_jasas | no_wo, kak_file, boq_file, durasi_preparation, target_date, status |
| `pr_jasas` | readiness_jasas | no_pr, target_date, status |
| `tender_jasas` | readiness_jasas | description, target_date, status |
| `contract_jasas` | readiness_jasas | contract_id (FK→contracts), status |

#### 2.8.4 OHS (Overhaul & Shutdown) - Variant

##### `event_readiness_ohs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_name | varchar | NOT NULL | Nama event |
| status | tinyint | DEFAULT 1 | Status event |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_material_ohs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_oh_id | bigint | FK → event_readiness_ohs.id, CASCADE | |
| material_name | varchar | NOT NULL | Nama material |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| type | integer | DEFAULT 0 | Tipe (0: LLDI, 1: Non LLDI) |
| current_status | text | NULLABLE | Status saat ini |
| tanggal_target | date | NOT NULL | Tanggal target |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_jasa_ohs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_oh_id | bigint | FK → event_readiness_ohs.id, CASCADE | |
| jasa_name | varchar | NOT NULL | Nama jasa |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| tanggal_target | date | NOT NULL | Tanggal target |
| current_status | text | NULLABLE | Status saat ini |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**OHS Material Pipeline:** `rekomendasi_material_ohs`, `notif_material_ohs`, `job_plan_material_ohs`, `pr_material_ohs`, `tender_material_ohs`, `po_material_ohs` (FK→contract_news), `fabrikasi_material_ohs`, `delivery_material_ohs`

**OHS Jasa Pipeline:** `rekomendasi_jasa_ohs`, `notif_jasa_ohs`, `job_plan_jasa_ohs`, `pr_jasa_ohs`, `tender_jasa_ohs`, `contract_jasa_ohs`

#### 2.8.5 RTNRTS (Return to Normal / Return to Service) - Variant

##### `event_readiness_rtnrts`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_name | varchar | NOT NULL | Nama event |
| status | tinyint | DEFAULT 1 | Status event |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_material_rtnrts`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_rtnrt_id | bigint | FK → event_readiness_rtnrts.id, CASCADE | |
| material_name | varchar | NOT NULL | Nama material |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| type | integer | DEFAULT 0 | Tipe (0: LLDI, 1: Non LLDI) |
| current_status | text | NULLABLE | Status saat ini |
| tanggal_target | date | NOT NULL | Tanggal target |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

##### `readiness_jasa_rtnrts`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| event_readiness_rtnrt_id | bigint | FK → event_readiness_rtnrts.id, CASCADE | |
| jasa_name | varchar | NOT NULL | Nama jasa |
| price_estimate | bigint | NULLABLE | Estimasi harga |
| tanggal_target | date | NOT NULL | Tanggal target |
| current_status | text | NULLABLE | Status saat ini |
| status | integer | DEFAULT 0 | Status |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**RTNRTS Material Pipeline:** `rekomendasi_material_rtnrts`, `notif_material_rtnrts`, `job_plan_material_rtnrts`, `pr_material_rtnrts`, `tender_material_rtnrts`, `po_material_rtnrts` (FK→contract_news), `fabrikasi_material_rtnrts`, `delivery_material_rtnrts`

**RTNRTS Jasa Pipeline:** `rekomendasi_jasa_rtnrts`, `notif_jasa_rtnrts`, `job_plan_jasa_rtnrts`, `pr_jasa_rtnrts`, `tender_jasa_rtnrts`, `contract_jasa_rtnrts`

---

### 2.9 RKAP (Rencana Kerja Anggaran Perusahaan)

#### `rkap_tas` / `rkap_ohs` / `rkap_rts` / `rkap_nrs`

| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| judul | varchar | NOT NULL | Judul RKAP |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `detail_rkap_tas` / `detail_rkap_ohs` / `detail_rkap_rts` / `detail_rkap_nrs`

| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| rkap_ta_id / rkap_oh_id / rkap_rt_id / rkap_nr_id | bigint | FK → rkap_*.id, CASCADE | Relasi ke RKAP parent |
| periode | tinyint | NOT NULL | Bulan ke- (1–12) |
| plan | bigint | DEFAULT 0 | Rencana anggaran |
| actual | bigint | NULLABLE | Realisasi anggaran |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.10 Monitoring Equipment

#### `monitoring_equipment`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE, UNIQUE | Relasi ke tag_numbers |
| kondisi_peralatan | varchar | NULLABLE | Kondisi peralatan |
| status | varchar | NULLABLE | Status equipment |
| jenis_kerusakan | varchar | NULLABLE | Jenis kerusakan |
| penyebab | varchar | NULLABLE | Penyebab kerusakan |
| penanganan_sementara | varchar | NULLABLE | Penanganan sementara |
| perbaikan_permanen | varchar | NULLABLE | Perbaikan permanen |
| progress_perbaikan_permanen | varchar | NULLABLE | Progress perbaikan |
| kendala_perbaikan | varchar | NULLABLE | Kendala perbaikan |
| estimasi_perbaikan | bigint | NULLABLE | Estimasi biaya perbaikan |
| target | varchar | NULLABLE | Target perbaikan |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `monitoring_equipment_logs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| tag_number_id | bigint | FK → tag_numbers.id, CASCADE | Relasi ke tag_numbers |
| kondisi_peralatan | varchar | NULLABLE | Kondisi peralatan |
| status | varchar | NULLABLE | Status equipment |
| jenis_kerusakan | varchar | NULLABLE | Jenis kerusakan |
| penyebab | varchar | NULLABLE | Penyebab kerusakan |
| penanganan_sementara | varchar | NULLABLE | Penanganan sementara |
| perbaikan_permanen | varchar | NULLABLE | Perbaikan permanen |
| progress_perbaikan_permanen | varchar | NULLABLE | Progress perbaikan |
| kendala_perbaikan | varchar | NULLABLE | Kendala perbaikan |
| estimasi_perbaikan | bigint | NULLABLE | Estimasi biaya perbaikan |
| target | varchar | NULLABLE | Target perbaikan |
| period_code | char(7) | NOT NULL | Kode periode bisnis (YYYY-MM) |
| period_start | date | NOT NULL | Tanggal awal periode |
| period_end | date | NOT NULL | Tanggal akhir periode |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indeks:** `tag_number_id`, `period_code`
**Unique:** `(tag_number_id, period_code)`

#### `status_peralatans`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| status_peralatan | varchar | NOT NULL | Status peralatan |
| is_active | tinyint | DEFAULT 1 | Status aktif (1=aktif, 0=nonaktif) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `kondisi_peralatans`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| kondisi_peralatan | varchar | NOT NULL | Kondisi peralatan |
| status | varchar | NOT NULL | Status terkait |
| is_active | tinyint | DEFAULT 1 | Status aktif (1=aktif, 0=nonaktif) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.11 Project Specification

#### `project_specs`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| no_project_spec | varchar | NULLABLE | Nomor project spec |
| judul | varchar | NOT NULL | Judul project spec |
| tanggal_project_spec | date | NOT NULL | Tanggal project spec |
| project_spec_file | text | NOT NULL | File project spec |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

### 2.12 Audit & Activity Log

#### `log_activities`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| user_id | bigint | NULLABLE, FK → users.id, SET NULL | Relasi ke users |
| module | varchar | NOT NULL | Nama modul |
| action | varchar | NOT NULL | Aksi (create/update/delete) |
| changes | json | NULLABLE | Data perubahan (JSON) |
| ip_address | varchar | NULLABLE | Alamat IP |
| user_agent | text | NULLABLE | User agent browser |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### `open_file_activities`
| Field | Type | Constraint | Keterangan |
|-------|------|------------|------------|
| id | bigint | PK, auto-increment | |
| user_id | bigint | FK → users.id, CASCADE | Relasi ke users |
| file_name | varchar | NOT NULL | Nama file |
| features | varchar | NOT NUL | Nama fitur |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

## 3. Ringkasan Jumlah Tabel

| Module | Jumlah Tabel |
|--------|-------------|
| Authentication & Authorization | 4 |
| Master Data | 4 |
| Contract Management | 11 |
| Certificates (PLO/COI/SKHP/Kalibrasi/Izin) | 13 |
| Memorandum & Document | 5 |
| Engineering Data | 6 |
| Inspection | 4 |
| Readiness TAS (Material + Jasa pipeline) | 18 |
| Readiness OHS (Material + Jasa pipeline) | 18 |
| Readiness RTNRTS (Material + Jasa pipeline) | 18 |
| RKAP | 8 |
| Monitoring Equipment | 4 |
| Project Specification | 1 |
| Audit & Activity Log | 2 |
| **Total** | **~116** |

---

## 4. Seeders

DatabaseSeeder menjalankan 5 seeder berikut:

### 4.1 RkapTaSeeder
- Membuat 3 record di `rkap_tas`: "RKAP TA 2025", "RKAP Maintenance", "RKAP Produksi"
- Untuk setiap record, membuat 12 detail periode (1–12) di `detail_rkap_tas` dengan plan random (1.000.000–5.000.000) dan actual random (500.000–plan)

### 4.2 RkapNrSeeder
- Membuat 3 record di `rkap_nrs`: "RKAP NR 2025", "RKAP Maintenance", "RKAP Produksi"
- Untuk setiap record, membuat 12 detail periode di `detail_rkap_nrs` dengan pola random yang sama

### 4.3 RkapOhSeeder
- Membuat 3 record di `rkap_ohs`: "RKAP OH 2025", "RKAP Maintenance", "RKAP Produksi"
- Untuk setiap record, membuat 12 detail periode di `detail_rkap_ohs` dengan pola random yang sama

### 4.4 RkapRtSeeder
- Membuat 3 record di `rkap_rts`: "RKAP RT 2025", "RKAP Maintenance", "RKAP Produksi"
- Untuk setiap record, membuat 12 detail periode di `detail_rkap_rts` dengan pola random yang sama

### 4.5 MonitoringEquipmentSeeder
- Menggunakan `updateOrCreate` berdasarkan `tag_number_id`
- Membuat 3 record di `monitoring_equipment` dengan data sample (tag_number_id: 2527, 2528, 2529)

### Command
```bash
php artisan db:seed
```

---

## 5. Catatan Teknis

- **Framework:** Laravel (Eloquent ORM)
- **Database:** MySQL
- **Primary Key:** Semua tabel menggunakan auto-increment bigint
- **Timestamps:** Semua tabel memiliki `created_at` dan `updated_at`
- **Cascade Delete:** Sebagian besar foreign key menggunakan `onDelete('cascade')`
- **Set Null:** Beberapa FK menggunakan `onDelete('set null')` (misal: `historical_memorandum_id` di pipeline tables)
- **File Storage:** Kolom file menggunakan `text` atau `varchar` untuk menyimpan path/URL file (bukan binary)
