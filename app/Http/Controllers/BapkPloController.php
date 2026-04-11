<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\BapkPlo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BapkPloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bapkPlo = BapkPlo::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Bapk PLO retrieved successfully.',
            'data' => $bapkPlo,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'bapk_plo' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi BAPK PLO gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('bapk_plo')) {
                $validatedData['bapk_plo'] = FileHelper::uploadWithVersion($request->file('bapk_plo'), 'plo/bapk');
            }
            $bapkPlo = BapkPlo::create($validatedData);
            if($bapkPlo){
                return response()->json([
                    'success' => true,
                    'message' => 'BAPK PLO created successfully.',
                    'data' => $bapkPlo,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create BAPK PLO.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create BAPK PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bapkPlo = BapkPlo::with('plo')->find($id);

        if (!$bapkPlo) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK PLO not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'BAPK PLO retrieved successfully.',
            'data' => $bapkPlo,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bapkPlo = BapkPlo::find($id);
        if (!$bapkPlo) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK PLO not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'bapk_plo' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi BAPK PLO gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('bapk_plo')) {
                $validatedData['bapk_plo'] = FileHelper::uploadWithVersion($request->file('bapk_plo'), 'plo/bapk');
            }
            $bapkPlo->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'BAPK PLO updated successfully.',
                'data' => $bapkPlo,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update BAPK PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bapkPlo = BapkPlo::find($id);
        if (!$bapkPlo) {
            return response()->json([
                'success' => false,
                'message' => 'BAPK PLO not found.',
            ], 404);
        }

        try {
            if ($bapkPlo->bapk_plo) {
                FileHelper::deleteFile($bapkPlo->bapk_plo, 'plo/bapk');
            }
            if($bapkPlo->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'BAPK PLO deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete BAPK PLO.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete BAPK PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
