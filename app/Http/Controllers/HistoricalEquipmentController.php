<?php

namespace App\Http\Controllers;

use App\Models\HistoricalMemorandum;
use App\Models\LaporanInspection;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistoricalEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result = [];

        /**
         * =========================
         * 1. MEMO
         * =========================
         */
        $memos = HistoricalMemorandum::select(
                'id',
                'tag_number_id',
                'no_dokumen',
                'perihal',
                'memorandum_file',
                'tanggal_terbit'
            )
            ->orderBy('tanggal_terbit', 'desc')
            ->get();

        foreach ($memos as $memo) {
            $tahun = Carbon::parse($memo->tanggal_terbit)->year;

            if (!isset($result[$tahun])) {
                $result[$tahun] = [
                    'tahun' => $tahun,
                    'memo' => [],
                    'laporan' => []
                ];
            }

            $result[$tahun]['memo'][] = [
                'id' => $memo->id,
                'tag_number_id' => $memo->tag_number_id,
                'no_dokumen' => $memo->no_dokumen,
                'perihal' => $memo->perihal,
                'memo_file' => $memo->memorandum_file,
                'tanggal_terbit' => $memo->tanggal_terbit,
            ];
        }

        /**
         * =========================
         * 2. LAPORAN INSPECTION
         * =========================
         */
        $laporans = LaporanInspection::with([
            'tagNumber:id,tag_number',
            'internalInspection',
            'externalInspection',
            'breakdownReport',
            'surveillance',
            'overhaul',
            'preventive',
        ])->get();

        foreach ($laporans as $laporan) {

            $menus = [
                'Internal Inspection' => ['data' => $laporan->internalInspection, 'date' => 'inspection_date'],
                'External Inspection' => ['data' => $laporan->externalInspection, 'date' => 'inspection_date'],
                'Breakdown Report'    => ['data' => $laporan->breakdownReport,    'date' => 'breakdown_report_date'],
                'Surveillance'        => ['data' => $laporan->surveillance,        'date' => 'surveillance_date'],
                'Overhaul'            => ['data' => $laporan->overhaul,            'date' => 'overhaul_date'],
                'Preventive'          => ['data' => $laporan->preventive,          'date' => 'preventive_date'],
            ];

            foreach ($menus as $menuName => $config) {

                if ($config['data']->isEmpty()) {
                    continue;
                }

                foreach ($config['data'] as $item) {

                    if (!$item->{$config['date']}) {
                        continue;
                    }

                    $tahun = Carbon::parse($item->{$config['date']})->year;

                    if (!isset($result[$tahun])) {
                        $result[$tahun] = [
                            'tahun' => $tahun,
                            'memo' => [],
                            'laporan' => []
                        ];
                    }

                    $result[$tahun]['laporan'][] = [
                        'tag_number' => $laporan->tag_number_id,
                        'menu' => $menuName,
                        'judul' => $item->judul,
                        'tanggal_report' => $item->{$config['date']},
                        'historical_memorandum_id' => $item->historical_memorandum_id,
                        'laporan_file' => $item->laporan_file,
                    ];

                }
            }
        }

        // SORT LAPORAN PER TAHUN BERDASARKAN TANGGAL TERBARU
        foreach ($result as $tahun => $data) {
            if (!empty($data['laporan'])) {
                usort($result[$tahun]['laporan'], function ($a, $b) {
                    return strtotime($b['tanggal_report']) <=> strtotime($a['tanggal_report']);
                });
            }
        }

        // SORT TAHUN (2026, 2025, 2024)
        ksort($result);
        
        /**
         * =========================
         * FINAL RESPONSE
         * =========================
         */
        return response()->json([
            'success' => true,
            'message' => 'Historical Equipment retrieved successfully.',
            'data' => array_values($result)
        ], 200);

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
