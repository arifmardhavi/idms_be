<?php

namespace App\Http\Controllers;

use App\Models\Termin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TerminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termin = Termin::with('contract')->get();

        return response()->json([
            'success' => true,
            'message' => 'Termin retrieved successfully.',
            'data' => $termin,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'termin' => 'required|string|max:100',
            'description' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $termin = Termin::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'termin created successfully.',
                'data' => $termin,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create termin.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $termin = Termin::with('contract')->find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'termin not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'termin retrieved successfully.',
            'data' => $termin,
        ], 200);
    }

    public function showByContract(string $id)
    {
        $termin = Termin::where('contract_id', $id)->with('contract')->get();

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'termin not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'termin retrieved successfully.',
            'data' => $termin,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $termin = Termin::find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'termin not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'termin' => 'required|string|max:100',
            'description' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $termin->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'termin updated successfully.',
                'data' => $termin,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update termin.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $termin = Termin::find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'termin not found.',
            ], 404);
        }

        try {
            $termin->delete();

            return response()->json([
                'success' => true,
                'message' => 'termin deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete termin.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
