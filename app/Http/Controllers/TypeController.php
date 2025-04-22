<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
    public function index()
    {
        $types = Type::with('category')->get();

        return response()->json([
            'success' => true,
            'message' => 'Types retrieved successfully.',
            'data' => $types,
        ], 200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $type = Type::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Type created successfully.',
                'data' => $type,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create type.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $type = Type::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $type->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Type updated successfully.',
                'data' => $type,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update type.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $type = Type::with('category')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Type retrieved successfully.',
            'data' => $type,
        ], 200);
    }

    public function showByStatus(){
        $type = Type::where('status', 1)->get();

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Type retrieved successfully.',
            'data' => $type,
        ], 200);
    }

    public function showByCategory($categoryId)
    {
        // take categories with filtered type_id
        $categories = Type::with('category')
            ->where('category_id', $categoryId) // Filter by type_id
            ->where('status', 1) // Filter by status active
            ->get();

        // if not found categories
        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No types found for the specified category.',
            ], 404);
        }

        // if found categories
        return response()->json([
            'success' => true,
            'message' => 'Types retrieved successfully.',
            'data' => $categories,
        ], 200);
    }

    public function destroy($id)
    {
        $type = Type::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type not found.',
            ], 404);
        }

        try {
            $type->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete type.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function nonactive($id) {
        $type = Type::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type not found.',
            ], 404);
        }

        try {
            $type->status = 0;
            $type->save();

            return response()->json([
                'success' => true,
                'message' => 'Type nonaktif successfully.',
            ], 200);        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to nonaktif type.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
