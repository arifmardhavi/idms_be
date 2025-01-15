<?php

namespace App\Http\Controllers;

use App\Models\Tag_number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Tag_numberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tagnumbers = Tag_number::with('type')->get();

        return response()->json([
            'success' => true,
            'message' => 'Tag numbers retrieved successfully.',
            'data' => $tagnumbers,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:types,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $tagnumber = Tag_number::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tag number created successfully.',
                'data' => $tagnumber,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag number.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $category = Tag_number::with('type')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Tag number not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tag number retrieved successfully.',
            'data' => $category,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Tag_number::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Tag number not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:types,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $category->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tag number updated successfully.',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag number.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Tag_number::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Tag number not found.',
            ], 404);
        }

        try {
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tag number deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag number.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function nonactive($id) {
        $tagnumber = Tag_number::find($id);

        if (!$tagnumber) {
            return response()->json([
                'success' => false,
                'message' => 'Tag number not found.',
            ], 404);
        }

        try {
            $tagnumber->status = 0;
            $tagnumber->save();

            return response()->json([
                'success' => true,
                'message' => 'Tag number nonaktif successfully.',
            ], 200);        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to nonaktif tag number.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
