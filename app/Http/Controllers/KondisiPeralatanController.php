<?php

namespace App\Http\Controllers;

use App\Models\KondisiPeralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KondisiPeralatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kondisiPeralatan = KondisiPeralatan::all();
        return response()->json([
            'success' => true,
            'message' => 'Kondisi Peralatan retrieved successfully.',
            'data' => $kondisiPeralatan,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kondisi_peralatan' => 'required|string|max:255',
            'status' => 'required|string|max:55',
            'is_active' => 'required|in:0,1', // 1 = active, 0 = inactive
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $kondisiPeralatan = KondisiPeralatan::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Kondisi Peralatan created successfully.',
                'data' => $kondisiPeralatan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kondisi peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kondisiPeralatan = KondisiPeralatan::find($id);

        if (!$kondisiPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Kondisi Peralatan not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kondisi Peralatan retrieved successfully.',
            'data' => $kondisiPeralatan,
        ], 200);
    }
    /**
     * Display a listing of the resource with is_active = 1.
     */
    public function showActive()
    {
        $kondisiPeralatan = KondisiPeralatan::where('is_active', 1)->get();
        return response()->json([
            'success' => true,
            'message' => 'Kondisi Peralatan retrieved successfully.',
            'data' => $kondisiPeralatan,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kondisiPeralatan = KondisiPeralatan::find($id);

        if (!$kondisiPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Kondisi Peralatan not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'kondisi_peralatan' => 'required|string|max:255',
            'status' => 'required|string|max:55',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $kondisiPeralatan = KondisiPeralatan::find($id);

            if (!$kondisiPeralatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kondisi Peralatan not found.',
                ], 404);
            }

            $kondisiPeralatan->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Kondisi Peralatan updated successfully.',
                'data' => $kondisiPeralatan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update kondisi peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the is_active field of the specified resource in storage.
     */
    public function updateActive(Request $request, string $id)
    {
        $kondisiPeralatan = KondisiPeralatan::find($id);

        if (!$kondisiPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Kondisi Peralatan not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|in:0,1', // 1 = active, 0 = inactive
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $kondisiPeralatan = KondisiPeralatan::find($id);

            if (!$kondisiPeralatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kondisi Peralatan not found.',
                ], 404);
            }

            $kondisiPeralatan->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Kondisi Peralatan updated successfully.',
                'data' => $kondisiPeralatan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update kondisi peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kondisiPeralatan = KondisiPeralatan::find($id);

        if (!$kondisiPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Kondisi Peralatan not found.',
            ], 404);
        }

        $kondisiPeralatan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kondisi Peralatan deleted successfully.',
        ], 200);
    }
}
