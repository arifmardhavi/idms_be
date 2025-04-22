<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // Get all categories
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $categories,
        ], 200);
    }

    // Store a new category
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255',
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
            $category = Category::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Get a specific category
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => $category,
        ], 200);
    }

    public function showByStatus(){
        $category = Category::where('status', 1)->get();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'category retrieved successfully.',
            'data' => $category,
        ], 200);
    }


    // Update a specific category
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255',
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
            $category->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific category
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        try {
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function nonactive($id) {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        try {
            $category->status = 0;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category nonaktif successfully.',
            ], 200);        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to nonaktif category.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
