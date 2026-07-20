# IDMS Backend - Dokumentasi Lengkap

Inspection Data Management System - Backend API Documentation

## Penjelasan Singkat

IDMS adalah platform digital untuk mengelola data inspeksi teknis, kepatuhan regulasi, manajemen kontrak, dan kesiapan pemeliharaan pada unit pengolahan kilang minyak (Refinery Unit).

## Arsitektur Sistem

```
┌──────────────────┐     ┌──────────────────────────┐     ┌──────────────┐
│   Frontend       │     │       Backend             │     │   Database   │
│   Next.js 16 SPA │────▶│   Laravel 10 REST API    │────▶│  MySQL 8.0   │
│                  │◀────│                           │◀────│              │
└──────────────────┘     └──────────────────────────┘     └──────────────┘
                                  │
                          ┌───────┴───────┐
                          │  Auth: JWT    │
                          │ (Tymon JWTAuth)│
                          └───────────────┘
```

## Daftar Modul

| No | File | Deskripsi |
|----|------|-----------|
| 01 | [Arsitektur & Tech Stack](01-arsitektur-sistem.md) | Arsitektur & Tech Stack |
| 02 | [Struktur Direktori](02-panduan-lokasi-file.md) | Panduan Lokasi File |
| 03 | [Autentikasi & Autorisasi](03-autentikasi-autorisasi.md) | JWT, RBAC |
| 04 | [Master Data](04-master-data.md) | Unit, Kategori, Tipe, Tag Number |
| 05 | [Manajemen Kontrak](05-manajemen-kontrak.md) | Legacy & New |
| 06 | [Kepatuhan Regulasi](06-kepatuhan-regulasi.md) | PLO, COI, SKHP, Izin |
| 07 | [Event Readiness](07-event-readiness.md) | TA, OH, RTNRT |
| 08 | [RKAP Anggaran](08-rkap-anggaran.md) | Rencana Kerja Anggaran Perusahaan |
| 09 | [Monitoring Equipment](09-monitoring-equipment.md) | Monitoring Peralatan |
| 10 | [Laporan Inspeksi](10-laporan-inspeksi.md) | Laporan Hasil Inspeksi |
| 11 | [Data Rekayasa](11-data-rekayasa.md) | Data Rekayasa / Engineering |
| 12 | [Memorandum & Dokumen](12-memorandum-dokumen.md) | Memorandum & Dokumen |
| 13 | [Manajemen User](13-manajemen-user.md) | Manajemen User |
| 14 | [Audit Trail](14-audit-trail.md) | Audit Trail |
| 15 | [Referensi API](15-api-reference.md) | Referensi API |
| 16 | [Database Schema](16-database-schema.md) | Database Schema |
| 17 | [Flow Diagrams](17-flow-diagrams.md) | Flow Diagrams |
| 18 | [Troubleshooting](18-troubleshooting-guide.md) | Troubleshooting Guide |
| 19 | [Catatan Penting](19-catatan-penting.md) | Catatan Penting |

## Daftar Istilah Teknis

| Istilah | Keterangan |
|---------|------------|
| **Tag Number** | Identitas unik peralatan (contoh: 11-E-101) |
| **PLO** | Persetujuan Layak Operasi |
| **COI** | Certificate of Inspection |
| **SKHP** | Surat Keterangan Hasil Pemeriksaan |
| **SPK** | Surat Perintah Kerja |
| **RKAP** | Rencana Kerja Anggaran Perusahaan |
| **SECE** | Safety Critical Equipment |
| **TKDN** | Tingkat Komponen Dalam Negeri |
| **MDR** | Manufacturing Data Record |
| **RLA** | Remaining Life Assessment |
| **BAPK** | Berita Acara Pemeriksaan Keandalan |
| **PIR** | Plant Inspection Report |
| **MOC** | Management of Change |

## Cara Membaca Dokumentasi

Mulai dari index ini, pilih modul yang ingin dipahami, buka file dokumentasi terkait. Setiap modul berdiri sendiri namun saling terkait satu sama lain.
