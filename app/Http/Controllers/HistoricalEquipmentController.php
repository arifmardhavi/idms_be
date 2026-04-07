<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\HistoricalMemorandum;
use App\Models\LaporanInspection;
use App\Models\Tag_number;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HistoricalEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result = [];

        /*
        |--------------------------------------------------------------------------
        | QUERY PARAMS
        |--------------------------------------------------------------------------
        */
        $range = request()->get('range'); // 2025-2026
        $sort = request()->get('sort', 'desc');
        $search = request()->get('search');

        $startYear = null;
        $endYear = null;

        if ($range) {
            if (strpos($range, '-') === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format range harus: YYYY-YYYY'
                ], 400);
            }

            [$startYear, $endYear] = explode('-', $range);
        }

        /*
        |--------------------------------------------------------------------------
        | PRELOAD TAG (ANTI N+1)
        |--------------------------------------------------------------------------
        */
        $tagNumbers = Tag_number::pluck('tag_number', 'id');

        /*
        |--------------------------------------------------------------------------
        | MEMO
        |--------------------------------------------------------------------------
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

            if (!$memo->tag_number_id) continue;

            $tagIds = explode(',', $memo->tag_number_id);

            foreach ($tagIds as $tagId) {

                $tag = $tagNumbers[trim($tagId)] ?? null;
                if (!$tag) continue;

                $tahun = Carbon::parse($memo->tanggal_terbit)->year;

                // RANGE FILTER
                if ($startYear && $endYear) {
                    if ($tahun < $startYear || $tahun > $endYear) continue;
                }

                // SEARCH FILTER
                if ($search) {
                    $keyword = strtolower($search);
                    $normalizedItemDate = DateHelper::normalize($memo->tanggal_terbit);
                    $normalizedKeywordDate = DateHelper::normalize($search);

                    if (
                        !str_contains(strtolower($tag), $keyword) && 
                        !str_contains(strtolower($memo->no_dokumen ?? ''), $keyword) &&
                        !str_contains(strtolower($memo->tanggal_terbit ?? ''), $keyword) &&
                        !(
                            $normalizedItemDate &&
                            $normalizedKeywordDate &&
                            str_contains($normalizedItemDate, $normalizedKeywordDate)
                        ) &&
                        !str_contains(strtolower($memo->perihal ?? ''), $keyword)
                    ) {
                        continue;
                    }
                }

                if (!isset($result[$tag])) {
                    $result[$tag] = [
                        'tag_number' => $tag,
                        'tahun' => []
                    ];
                }

                if (!isset($result[$tag]['tahun'][$tahun])) {
                    $result[$tag]['tahun'][$tahun] = [
                        'memo' => [],
                        'laporan' => []
                    ];
                }

                $result[$tag]['tahun'][$tahun]['memo'][] = [
                    'id' => $memo->id,
                    'no_dokumen' => $memo->no_dokumen,
                    'perihal' => $memo->perihal,
                    'memo_file' => $memo->memorandum_file,
                    'tanggal_terbit' => $memo->tanggal_terbit,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LAPORAN
        |--------------------------------------------------------------------------
        */
        $laporans = LaporanInspection::with([
            'tagNumber:id,tag_number',
            'internalInspection',
            'externalInspection',
            'breakdownReport',
            'surveillance',
            'overhaul',
            'preventive',
            'onstream'
        ])->get();

        foreach ($laporans as $laporan) {

            if (!$laporan->tagNumber) continue;

            $tag = $laporan->tagNumber->tag_number;

            $menus = [
                'Internal Inspection' => ['data' => $laporan->internalInspection, 'date' => 'inspection_date'],
                'External Inspection' => ['data' => $laporan->externalInspection, 'date' => 'inspection_date'],
                'Breakdown Report'    => ['data' => $laporan->breakdownReport,    'date' => 'breakdown_report_date'],
                'Surveillance'        => ['data' => $laporan->surveillance,        'date' => 'surveillance_date'],
                'Overhaul'            => ['data' => $laporan->overhaul,            'date' => 'overhaul_date'],
                'Preventive'          => ['data' => $laporan->preventive,          'date' => 'preventive_date'],
                'Onstream'            => ['data' => $laporan->onstream,            'date' => 'inspection_date'],
            ];

            foreach ($menus as $menuName => $config) {

                if ($config['data']->isEmpty()) continue;

                foreach ($config['data'] as $item) {

                    if (!$item->{$config['date']}) continue;

                    $tahun = Carbon::parse($item->{$config['date']})->year;

                    // RANGE FILTER
                    if ($startYear && $endYear) {
                        if ($tahun < $startYear || $tahun > $endYear) continue;
                    }

                    // SEARCH FILTER
                    if ($search) {
                        $keyword = strtolower($search);
                        $normalizedItemDate = DateHelper::normalize($item->tanggal_report);
                        $normalizedKeywordDate = DateHelper::normalize($search);

                        if (
                            !str_contains(strtolower($tag), $keyword) &&
                            !str_contains(strtolower($item->judul ?? ''), $keyword) &&
                            !str_contains(strtolower($item->tanggal_report ?? ''), $keyword) &&
                            !(
                                $normalizedItemDate &&
                                $normalizedKeywordDate &&
                                str_contains($normalizedItemDate, $normalizedKeywordDate)
                            ) &&
                            !str_contains(strtolower($menuName), $keyword)
                        ) {
                            continue;
                        }
                    }

                    if (!isset($result[$tag])) {
                        $result[$tag] = [
                            'tag_number' => $tag,
                            'tahun' => []
                        ];
                    }

                    if (!isset($result[$tag]['tahun'][$tahun])) {
                        $result[$tag]['tahun'][$tahun] = [
                            'memo' => [],
                            'laporan' => []
                        ];
                    }

                    $result[$tag]['tahun'][$tahun]['laporan'][] = [
                        'id' => $item->laporan_inspection_id,
                        'menu' => $menuName,
                        'judul' => $item->judul,
                        'tanggal_report' => $item->{$config['date']},
                        'historical_memorandum_id' => $item->historical_memorandum_id,
                        'laporan_file' => $item->laporan_file,
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SORTING
        |--------------------------------------------------------------------------
        */
        foreach ($result as &$tagData) {

            if ($sort === 'asc') {
                ksort($tagData['tahun']);
            } else {
                krsort($tagData['tahun']);
            }

            foreach ($tagData['tahun'] as &$tahunData) {

                if (!empty($tahunData['laporan'])) {
                    usort($tahunData['laporan'], function ($a, $b) use ($sort) {
                        if ($sort === 'asc') {
                            return strtotime($a['tanggal_report']) <=> strtotime($b['tanggal_report']);
                        }
                        return strtotime($b['tanggal_report']) <=> strtotime($a['tanggal_report']);
                    });
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
        $page = request()->get('page', 1);
        $perPage = request()->get('per_page', 3);

        $collection = collect(array_values($result));

        $pagedData = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $pagination = new LengthAwarePaginator(
            $pagedData,
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'message' => 'Historical Equipment retrieved successfully.',
            'data' => $pagination->items(),
            'meta' => [
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
            ]
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
