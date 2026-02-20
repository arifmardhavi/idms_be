<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\SpkProgressNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpkProgressNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spkProgressNews = SpkProgressNew::all();
        return response()->json([
            'success' => true,
            'message' => 'spk Progress retrieved successfully.',
            'data' => $spkProgressNews,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'spk_new_id' => 'required|exists:spk_news,id',
            'week' => 'required|integer',
            'plan' => 'required|numeric',
            'actual' => 'nullable|numeric',
            'progress_file' => 'nullable|file|mimes:pdf,jpg,png',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        if ($request->hasFile('progress_file')) {
            $validatedData['progress_file'] = FileHelper::uploadWithVersion($request->file('progress_file'), 'contract_new/spk/progress');
        }
        $spkProgressNew = SpkProgressNew::create($validatedData);
        return response()->json([
            'success' => true,
            'message' => 'spk Progress created successfully.',
            'data' => $spkProgressNew,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $spkProgressNew = SpkProgressNew::find($id);
        if (!$spkProgressNew) {
            return response()->json([
                'success' => false,
                'message' => 'spk Progress not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'spk Progress retrieved successfully.',
            'data' => $spkProgressNew,
        ]);
    }

    /**
     * Display the specified by spk resource.
     */
    public function showBySpk(string $id)
    {
        $spkProgressNew = SpkProgressNew::where('spk_new_id', $id)->get();
        if (!$spkProgressNew) {
            return response()->json([
                'success' => false,
                'message' => 'spk Progress not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'spk Progress retrieved successfully.',
            'data' => $spkProgressNew,
        ]);
    }

    /**
     * Display the specified by contract resource.
     */
    public function showByContract(string $id)
    {
        $spkProgressNew = SpkProgressNew::whereHas('spkNew', function ($query) use ($id) {
            $query->where('contract_new_id', $id);
        })->get();
        if (!$spkProgressNew) {
            return response()->json([
                'success' => false,
                'message' => 'spk Progress not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'spk Progress retrieved successfully.',
            'data' => $spkProgressNew,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $spkProgressNew = SpkProgressNew::find($id);
        if (!$spkProgressNew) {
            return response()->json([
                'success' => false,
                'message' => 'spk Progress not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'spk_new_id' => 'sometimes|required|exists:spk_news,id',
            'week' => 'sometimes|required|integer',
            'plan' => 'sometimes|required|numeric',
            'actual' => 'nullable|numeric',
            'progress_file' => 'nullable|file|mimes:pdf,jpg,png',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('progress_file')) {
            $validatedData['progress_file'] = FileHelper::uploadWithVersion($request->file('progress_file'), 'contract_new/spk/progress');
            // Delete old file if exists
            if ($spkProgressNew->progress_file) {
                FileHelper::deleteFile($spkProgressNew->progress_file, 'contract_new/spk/progress');
            }
        }

        $spkProgressNew->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'spk Progress updated successfully.',
            'data' => $spkProgressNew,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $spkProgressNew = SpkProgressNew::find($id);
        if (!$spkProgressNew) {
            return response()->json([
                'success' => false,
                'message' => 'spk Progress not found.',
            ], 404);
        }

        // Delete associated file if exists
        if ($spkProgressNew->progress_file) {
            FileHelper::deleteFile($spkProgressNew->progress_file, 'contract_new/spk/progress');
        }

        $spkProgressNew->delete();

        return response()->json([
            'success' => true,
            'message' => 'spk Progress deleted successfully.',
        ]);
    }
}
