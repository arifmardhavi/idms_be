<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\LumpsumProgressNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LumpsumProgressNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lumpsum_progress = LumpsumProgressNew::all();

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress retrieved successfully.',
            'data' => $lumpsum_progress,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'week' => 'required|integer',
            'plan' => 'required|numeric|min:0|max:100',
            'actual' => 'required|numeric|min:0|max:100',
            'progress_file' => 'required|file|mimes:pdf|max:30720', // Maksimal 30MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }    

        $validatedData = $validator->validated();
        $validatedData['progress_file'] = FileHelper::uploadWithVersion($request->file('progress_file'), 'contract_new/lumpsum/progress');

        $lumpsum_progress = LumpsumProgressNew::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress created successfully.',
            'data' => $lumpsum_progress,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lumpsum_progress = LumpsumProgressNew::find($id);

        if (!$lumpsum_progress) {
            return response()->json([
                'success' => false,
                'message' => 'Lumpsum Progress not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress retrieved successfully.',
            'data' => $lumpsum_progress,
        ]);
    }
    public function showByContract(string $id)
    {
        $lumpsum_progress = LumpsumProgressNew::where('contract_new_id', $id)->get();

        if (!$lumpsum_progress) {
            return response()->json([
                'success' => false,
                'message' => 'Lumpsum Progress not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress retrieved successfully.',
            'data' => $lumpsum_progress,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $lumpsum_progress = LumpsumProgressNew::find($id);

        if (!$lumpsum_progress) {
            return response()->json([
                'success' => false,
                'message' => 'Lumpsum Progress not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'sometimes|required|exists:contract_news,id',
            'week' => 'sometimes|required|integer',
            'plan' => 'sometimes|required|numeric|min:0|max:100',
            'actual' => 'sometimes|required|numeric|min:0|max:100',
            'progress_file' => 'sometimes|required|file|mimes:pdf|max:30720', // Maksimal 30MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('progress_file')) {
            $validatedData['progress_file'] = FileHelper::uploadWithVersion($request->file('progress_file'), 'contract_new/lumpsum/progress');
            if ($lumpsum_progress->progress_file) {
                FileHelper::deleteFile($lumpsum_progress->progress_file, 'contract_new/lumpsum/progress');
            }
        }

        $lumpsum_progress->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress updated successfully.',
            'data' => $lumpsum_progress,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $lumpsum_progress = LumpsumProgressNew::find($id);

        if (!$lumpsum_progress) {
            return response()->json([
                'success' => false,
                'message' => 'Lumpsum Progress not found.',
            ], 404);
        }

        if ($lumpsum_progress->progress_file) {
            FileHelper::deleteFile($lumpsum_progress->progress_file, 'contract_new/lumpsum/progress');
        }

        $lumpsum_progress->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lumpsum Progress deleted successfully.',
        ], 200);
    }
}
