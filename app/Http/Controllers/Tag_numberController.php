<?php

namespace App\Http\Controllers;

use App\Models\Tag_number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Tag_numberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tagnumbers = Tag_number::with(['type', 'unit'])->orderBy('id', 'desc')->get();

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
        $request->merge([
            'tag_number' => strtoupper(preg_replace('/\s+/', '', $request->tag_number)),
        ]);
        
        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255|unique:tag_numbers,tag_number',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:types,id',
            'unit_id' => 'required|exists:units,id',
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
        $category = Tag_number::with(['type', 'unit'])->find($id);

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

    function showByType($typeId){
        $tag_numbers = Tag_number::with(['type'])->where('type_id', $typeId)->where('status', 1)->get();

        return response()->json([
            'success' => true,
            'message' => 'Tag numbers retrieved successfully.',
            'data' => $tag_numbers,
        ], 200);
    }

    function showByTypeUnit($typeId, $unitId) {
        $tag_numbers = Tag_number::where('type_id', $typeId)->where('unit_id', $unitId)->where('status', 1)->get();
    
        if ($tag_numbers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tag numbers not found.',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Tag numbers retrieved successfully.',
            'data' => $tag_numbers,
        ], 200);
    }
    

    function showByTagNumber(Request $request) {
        $tag_number = $request->query('tag_number');
        // $tag_number = urldecode($tag_number);

        $result = DB::table('tag_numbers')
        ->join('types', 'tag_numbers.type_id', '=', 'types.id')
        ->join('categories', 'types.category_id', '=', 'categories.id')
        ->join('units', 'tag_numbers.unit_id', '=', 'units.id')
        ->select(
            'tag_numbers.id as tag_number_id',
            'tag_numbers.tag_number',
            'types.id as type_id',
            'categories.id as category_id',
            'units.id as unit_id'
        )
        ->where('tag_numbers.tag_number', $tag_number)
        ->first();

        if($result) {
            return response()->json([
                'success' => true,
                'message' => 'Tag number retrieved successfully.',
                'data' => $result,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $tag_number . ' not found.',
            ], 404);
        }
        
    }

    function showByTagNumberId($id) {

        $result = DB::table('tag_numbers')
        ->join('types', 'tag_numbers.type_id', '=', 'types.id')
        ->join('categories', 'types.category_id', '=', 'categories.id')
        ->join('units', 'tag_numbers.unit_id', '=', 'units.id')
        ->select(
            'tag_numbers.id as tag_number_id',
            'tag_numbers.tag_number',
            'types.id as type_id',
            'categories.id as category_id',
            'units.id as unit_id'
        )
        ->where('tag_numbers.id', $id)
        ->first();

        if($result) {
            return response()->json([
                'success' => true,
                'message' => 'Tag number retrieved successfully.',
                'data' => $result,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $id . ' not found.',
            ], 404);
        }
        
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

        $request->merge([
            'tag_number' => strtoupper(preg_replace('/\s+/', '', $request->tag_number)),
        ]);
        
        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255|unique:tag_numbers,tag_number,' . $id,
            'description' => 'nullable|string',
            'type_id' => 'required|exists:types,id',
            'unit_id' => 'required|exists:units,id',
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
