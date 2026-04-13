<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $features = Feature::all();
        return response()->json([
            'success' => true,
            'message' => 'Features retrieved successfully.',
            'data' => $features,
        ], 200);
    }

    /**
     * Display a listing of the resource by group.
     */
    public function showByGroup()
    {
        $features = Feature::all()->groupBy('group');

        return response()->json([
            'success' => true,
            'message' => 'Features retrieved successfully.',
            'data' => $features,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        $feature = Feature::create($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Feature created successfully.',
            'data' => $feature,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $features = Feature::find($id);

        if (!$features) {
            return response()->json([
                'success' => false,
                'message' => 'Features not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Features retrieved successfully.',
            'data' => $features,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $features = Feature::find($id);

        if (!$features) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'feature' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        $features->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Feature updated successfully.',
            'data' => $features,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $features = Feature::find($id);

        if (!$features) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found.',
            ], 404);
        }

        $features->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully.',
        ], 200);
    }
}
