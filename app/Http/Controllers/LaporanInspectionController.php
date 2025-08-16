<?php

namespace App\Http\Controllers;

use App\Models\LaporanInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $laporanInspections = LaporanInspection::latest()->get();


         return response()->json([
            'success' => true,
            'message' => 'Laporan Inspection retrieved successfully.',
            'data' => $laporanInspections,
        ], 200);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:laporan_inspections,tag_number_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Laporan Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {

            $laporanInspection = LaporanInspection::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Laporan Inspection created successfully.',
                'data' => $laporanInspection,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Laporan Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $laporanInspection = LaporanInspection::with('tagNumber.unit', 'tagNumber.type.category')->find($id);
        if (!$laporanInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Laporan Inspection retrieved successfully.',
            'data' => $laporanInspection,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $laporanInspection = LaporanInspection::find($id);
        if (!$laporanInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan Inspection not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'sometimes|exists:tag_numbers,id|unique:laporan_inspections,tag_number_id,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Laporan Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $laporanInspection->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Inspection updated successfully.',
            'data' => $laporanInspection,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $laporanInspection = LaporanInspection::find($id);
        if (!$laporanInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan Inspection not found.',
            ], 404);
        }
        try {
            $laporanInspection->delete();
            return response()->json([
                'success' => true,
                'message' => 'Laporan Inspection deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Laporan Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
