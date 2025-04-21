<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
}
