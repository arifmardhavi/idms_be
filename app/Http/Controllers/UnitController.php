<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    // Get all units
    public function index(){
        $units = Unit::all();

        return response()->json([
            'success' => true,
            'message' => 'Units retrieved successfully.',
            'data' => $units,
        ], 200);
    }

    // Store a new unit
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'unit_name' => 'required|string|max:100',
            'unit_type' => 'required|integer',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $unit = Unit::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully.',
                'data' => $unit,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create unit.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Get a specific unit
    public function showByStatus(){
        $unit = Unit::where('status', 1)->get();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unit retrieved successfully.',
            'data' => $unit,
        ], 200);
    }

    public function show($id){
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unit retrieved successfully.',
            'data' => $unit,
        ], 200);
    }

    // Update a specific unit
    public function update(Request $request, $id){
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit_name' => 'required|string|max:100',
            'unit_type' => 'required|integer',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $unit->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully.',
                'data' => $unit,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific unit
    public function destroy($id){
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        try {
            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function nonactive($id) {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        try {
            $unit->status = 0;
            $unit->save();

            return response()->json([
                'success' => true,
                'message' => 'Unit nonaktif successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to nonaktif unit.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportUnit(){
        $data = Unit::all()->map(function($item){
            return [
                'unit_name' => $item->unit_name,
                'unit_type' => $item->unit_type == 1 ? 'Pipa Penyalur' : 'Instalasi',
                'description' => $item->description,
                'status' => $item->status == 1 ? 'Active' : 'Nonactive',
            ];
        })->toArray();
        $columns = [
            'unit_name' => 'Nama Unit',
            'unit_type' => 'Tipe Unit',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
        $options = [
            'headerRows' => [
                ['PT Kilang Pertamina International'],
                ['Laporan Kontrak - Periode: 2025'],
                // custom grouping: shorter row will auto-merge to remaining columns
                ['Informasi Kontrak', '', '', 'Tanggal Kontrak', '', '', '', ''],
            ],
            'filter' => true,
            'freezeHeader' => false,
            'styles' => [
                'font' => ['name' => 'Calibri', 'size' => 11]
            ],
            // manual number & date formats (key-based)
            // 'numberFormat' => [
            //     'nilai_kontrak' => '#,##0',        // integer rupiah
            //     'deviasi' => '0.00',               // numeric with decimals
            // ],
            // 'dateFormat' => [
            //     'start_date' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            //     'end_date' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            // ],
            // conditional styling: highlight overdue
            'conditional' => [
                [
                    'column' => 'status',
                    'condition' => function($v){ return $v !== 'Active'; },
                    'style' => [
                        'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true],
                        'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFCC0000']],
                    ],
                ],
                // [
                //     'column' => 'deviasi',
                //     'condition' => function($v){ return is_numeric($v) && $v < -20; },
                //     'style' => [
                //         'font' => ['color' => ['argb' => 'FF000000']],
                //         'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFFFC000']],
                //     ],
                // ],
            ],
            // columnWidth: manual by key or letter
            // 'columnWidth' => [
            //     'no_contract' => 20,
            //     'vendor' => 30,
            //     'A' => 10, // also allowed
            // ],
            // auto width: enable combined ShouldAutoSize + custom
            'autoWidth' => true,
            'autoWidthMax' => 50,
            // border
            'border' => ['onlyHeader' => true, 'onlyData' => true],
        ];
        return Excel::download(new DynamicExport($data, $columns, $options), 'Unit-report.xlsx');

    }
}
