<?php

namespace App\Http\Controllers;

use App\Models\StatusPeralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StatusPeralatanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statusPeralatan = StatusPeralatan::all();
        return response()->json([
            'success' => true,
            'message' => 'Status Peralatan retrieved successfully.',
            'data' => $statusPeralatan,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status_peralatan' => 'required|string|max:255',
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
            $statusPeralatan = StatusPeralatan::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Status Peralatan created successfully.',
                'data' => $statusPeralatan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Status Peralatan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $statusPeralatan = StatusPeralatan::find($id);
        if (!$statusPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Status Peralatan not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status Peralatan retrieved successfully.',
            'data' => $statusPeralatan,
        ], 200);
    }

    /**
     * Display a listing of active resources.
     */
    public function showActive()
    {
        $statusPeralatan = StatusPeralatan::where('is_active', 1)->get();
        return response()->json([
            'success' => true,
            'message' => 'Status Peralatan retrieved successfully.',
            'data' => $statusPeralatan,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $statusPeralatan = StatusPeralatan::find($id);
        if (!$statusPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Status Peralatan not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_peralatan' => 'required|string|max:255',
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
            $statusPeralatan->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Status Peralatan updated successfully.',
                'data' => $statusPeralatan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Status Peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the is_active field of the specified resource in storage.
     */
    public function updateActive(Request $request, string $id)
    {
        $statusPeralatan = StatusPeralatan::find($id);

        if (!$statusPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Status Peralatan not found.',
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
            $statusPeralatan = StatusPeralatan::find($id);

            if (!$statusPeralatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status Peralatan not found.',
                ], 404);
            }

            $statusPeralatan->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Status Peralatan updated successfully.',
                'data' => $statusPeralatan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $statusPeralatan = StatusPeralatan::find($id);
        if (!$statusPeralatan) {
            return response()->json([
                'success' => false,
                'message' => 'Status Peralatan not found.',
            ], 404);
        }

        try {
            $statusPeralatan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Status Peralatan deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Status Peralatan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
