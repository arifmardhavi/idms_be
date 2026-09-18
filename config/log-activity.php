<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Label Fitur Utama (Canonical)
    |--------------------------------------------------------------------------
    |
    | Daftar { path => label } fitur utama pada frontend — SATU-SATUNYA sumber
    | penamaan yang sah. FE harus mengirim label persis seperti nilai di bawah
    | ini sebagai `feature` pada POST /activity/visit.
    |
    | Hanya fitur utama yang dicatat saat dikunjungi (visit). Sub-halaman
    | (detail, report, bapk, lampiran, dsb.) TIDAK dicatat sebagai visit.
    |
    | Setiap fitur baru di FE cukup ditambahkan satu baris di sini.
    |
    */

    'features' => [
        '/dashboard'                  => 'Dashboard',
        '/unit'                       => 'Unit',
        '/log-activity'               => 'Log Activity',
        '/kategori-peralatan'         => 'Kategori Peralatan',
        '/tipe-peralatan'             => 'Tipe Peralatan',
        '/tag-number'                 => 'Tag Number',
        '/status-kondisi-peralatan'   => 'Status & Kondisi Peralatan',
        '/user'                       => 'User',

        '/plo'                        => 'Plo',
        '/coi'                        => 'Coi',
        '/skhp'                       => 'Skhp',
        '/sertifikat-kalibrasi'       => 'Sertifikat Kalibrasi',
        '/izin-usaha'                 => 'Izin Usaha',
        '/nib'                        => 'Nib',
        '/izin-operasi'               => 'Izin Operasi',
        '/izin-disnaker'              => 'Izin Disnaker',

        '/contract'                  => 'Contract',
        '/historical-memorandum'     => 'Historical Memorandum',

        '/equipment-data'            => 'Equipment Data',
        '/iso-metric'                => 'ISO Metric',
        '/p-id'                      => 'P Id',
        '/project-spec'              => 'Project Spec',
        '/eca-fsca'                  => 'Eca Fsca',
        '/fitur-gms'                 => 'Fitur Gms',
        '/fitur-ems'                 => 'Fitur Ems',

        '/laporan-inspection'        => 'Laporan Inspection',
        '/historical-equipment'      => 'Historical Equipment',

        '/readiness-ta'              => 'Readiness TA',
        '/pir-readiness-ta'          => 'PIR',

        '/readiness-overhaul'        => 'Readiness Overhaul',
        '/readiness-routine'         => 'Routine Non Routine',
        '/wbs-dashboard'             => 'WBS Dashboard',
        '/wbs-rt'                    => 'WBS RT',
        '/wbs-oh'                    => 'WBS OH',
        '/wbs-nr'                    => 'WBS NR',
        '/wbs-ta'                    => 'WBS TA',

        '/monitoring-equipment'      => 'Monitoring Equipment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alias Identifier BE -> Label Kanonikal
    |--------------------------------------------------------------------------
    |
    | Memetakan nilai `module` mentah yang ditulis oleh backend (observer
    | class-basename, controller download/export/import, dsb.) ke label
    | kanonikal fitur utama.
    |
    | Sub-entity (SPK, Termin, Report, Bapk, Lampiran, Preventive, dsb.)
    | dipetakan ke fitur utama parent-nya agar tidak ada inkonsistensi nama.
    |
    | Entitas "New" hanya yang ADA SAAT INI & ditulis eksplisit (tidak ada
    | aturan generik strip akhiran "New" untuk entitas masa depan).
    |
    */

    'aliases' => [
        // Contract family -> Contract
        'ContractNew'          => 'Contract',
        'SpkNew'               => 'Contract',
        'AmandemenNew'         => 'Contract',
        'SpkProgressNew'       => 'Contract',
        'LumpsumProgressNew'   => 'Contract',
        'TerminReceiptNew'     => 'Contract',
        'TerminNew'            => 'Contract',
        'Termin'               => 'Contract',
        'ContractJasa'         => 'Contract',
        'ContractJasaOh'       => 'Contract',
        'ContractJasaRtnrt'    => 'Contract',
        'Spk'                  => 'Contract',
        'Spk_progress'         => 'Contract',
        'Lumpsum_progress'     => 'Contract',
        'Amandemen'            => 'Contract',

        // Regulatory sub-items -> parent
        'BapkCoi'              => 'Coi',
        'ReportCoi'            => 'Coi',
        'BapkPlo'              => 'Plo',
        'ReportPlo'            => 'Plo',
        'ReportIzinOperasi'    => 'Izin Operasi',
        'ReportIzinDisnaker'   => 'Izin Disnaker',

        // Historical Memorandum sub-item
        'LampiranMemo'         => 'Historical Memorandum',

        // Laporan Inspection sub-items
        'InternalInspection'   => 'Laporan Inspection',
        'ExternalInspection'   => 'Laporan Inspection',
        'OnstreamInspection'   => 'Laporan Inspection',
        'Surveillance'         => 'Laporan Inspection',
        'Preventive'           => 'Laporan Inspection',
        'Overhaul'             => 'Laporan Inspection',
        'BreakdownReport'      => 'Laporan Inspection',

        // Engineering Data sub-items
        'Datasheet'            => 'Equipment Data',
        'GaDrawing'            => 'Equipment Data',
        'MdrFolder'            => 'Equipment Data',
        'MdrItem'              => 'Equipment Data',

        // Labels yang beda dari hasil prettify
        'Ems'                  => 'Fitur Ems',
        'Gms'                  => 'Fitur Gms',
        'Pir'                  => 'PIR',
        'P_id'                 => 'P Id',
        'IsoMetric'            => 'ISO Metric',

        // Explicit (prettify-compatible, memperjelas intent)
        'EcaFsca'              => 'Eca Fsca',
        'Tag_number'           => 'Tag Number',
        'ProjectSpec'          => 'Project Spec',
        'MonitoringEquipment'  => 'Monitoring Equipment',
        'LaporanInspection'    => 'Laporan Inspection',
        'SertifikatKalibrasi'  => 'Sertifikat Kalibrasi',
        'HistoricalMemorandum' => 'Historical Memorandum',
        'Coi'                  => 'Coi',
        'Plo'                  => 'Plo',
        'Skhp'                 => 'Skhp',
        'Contract'             => 'Contract',
        'Unit'                 => 'Unit',
        'Auth'                 => 'Auth',
        'IzinUsaha'            => 'Izin Usaha',
        'IzinOperasi'          => 'Izin Operasi',
        'IzinDisnaker'         => 'Izin Disnaker',
        'Nib'                  => 'Nib',
    ],

];