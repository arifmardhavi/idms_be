<?php

namespace App\Http\Controllers;

use App\Models\PrJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pr_jasa = PrJasa::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'PR jasa retrieved successfully.',
            'data' => $pr_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'no_pr' => 'nullable|integer',
            'target_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }


        $validatedData = $validator->validated();

        try {
            $pr_jasa = PrJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PR jasa created successfully.',
                'data' => $pr_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PR jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pr_jasa = PrJasa::find($id);

        if (!$pr_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'PR jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PR jasa retrieved successfully.',
            'data' => $pr_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $pr_jasa = PrJasa::with(['readiness_jasa'])->where('readiness_jasa_id', $id)->orderby('id', 'desc')->get();

        if (!$pr_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'PR jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PR jasa retrieved successfully.',
            'data' => $pr_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pr_jasa = PrJasa::find($id);

        if (!$pr_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'PR jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'sometimes|exists:readiness_jasas,id',
            'no_pr' => 'sometimes|integer',
            'target_date' => 'sometimes|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $pr_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PR jasa updated successfully.',
                'data' => $pr_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PR jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pr_jasa = PrJasa::find($id);

        if (!$pr_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'PR jasa not found.',
            ], 404);
        }

        try {
            $pr_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'PR jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PR jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
