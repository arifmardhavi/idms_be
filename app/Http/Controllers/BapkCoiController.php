<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\BapkCoi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BapkCoiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bapkCoi = BapkCoi::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Bapk COI retrieved successfully.',
            'data' => $bapkCoi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coi_id' => 'required|exists:cois,id',
            'bapk_coi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi BAPK COI gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('bapk_coi')) {
                $validatedData['bapk_coi'] = FileHelper::uploadWithVersion($request->file('bapk_coi'), 'coi/bapk');
            }
            $bapkCoi = BapkCoi::create($validatedData);
            if($bapkCoi){
                return response()->json([
                    'success' => true,
                    'message' => 'BAPK COI created successfully.',
                    'data' => $bapkCoi,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create BAPK COI.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create BAPK COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bapkCoi = BapkCoi::with('coi')->find($id);

        if (!$bapkCoi) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK COI not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'BAPK COI retrieved successfully.',
            'data' => $bapkCoi,
        ], 200);
    }

    /**
     * Display the specified resource by coi.
     */
    public function showByCoi(string $id)
    {
        $bapkCoi = BapkCoi::with('coi')->where('coi_id', $id)->get();

        if (!$bapkCoi) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK COI not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'BAPK COI retrieved successfully.',
            'data' => $bapkCoi,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bapkCoi = BapkCoi::find($id);
        if (!$bapkCoi) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK COI not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'coi_id' => 'required|exists:cois,id',
            'bapk_coi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi BAPK COI gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('bapk_coi')) {
                $validatedData['bapk_coi'] = FileHelper::uploadWithVersion($request->file('bapk_coi'), 'coi/bapk');
            }
            $bapkCoi->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'BAPK COI updated successfully.',
                'data' => $bapkCoi,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update BAPK COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bapkCoi = BapkCoi::find($id);
        if (!$bapkCoi) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK COI not found.',
            ], 404);
        }

        try {
            if ($bapkCoi->bapk_coi) {
                FileHelper::deleteFile($bapkCoi->bapk_coi, 'coi/bapk');
            }
            if($bapkCoi->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'BAPK COI deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete BAPK COI.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete BAPK COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
