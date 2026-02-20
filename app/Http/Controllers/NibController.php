<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Nib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NibController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nib =  Nib::orderBy('tanggal_nib', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'NIB retrieved successfully.',
            'data' => $nib,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_nib' => 'required|string|max:200|unique:nibs,no_nib',
            'judul' => 'required|string|max:255',
            'tanggal_nib' => 'required|date',
            'nib_file' => 'required|file|mimes:pdf',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for NIB',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('nib_file')) {
            $validatedData['nib_file'] = FileHelper::uploadWithVersion($request->file('nib_file'), 'nib');
        }

        $nib = Nib::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'NIB created successfully.',
            'data' => $nib,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $nib = Nib::find($id);
        if (!$nib) {
            return response()->json([
                'success' => false,
                'message' => 'NIB not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'NIB retrieved successfully.',
            'data' => $nib,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $nib = Nib::find($id);
        if (!$nib) {
            return response()->json([
                'success' => false,
                'message' => 'NIB not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_nib' => 'sometimes|required|string|max:200|unique:nibs,no_nib,' . $id,
            'judul' => 'sometimes|required|string|max:255',
            'tanggal_nib' => 'sometimes|required|date',
            'nib_file' => 'sometimes|required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for NIB',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('nib_file')) {
            $validatedData['nib_file'] = FileHelper::uploadWithVersion($request->file('nib_file'), 'nib');
                // Hapus file lama jika ada
                if ($nib->nib_file) {
                    FileHelper::deleteFile($nib->nib_file, 'nib');
                }
        }

        $nib->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'NIB updated successfully.',
            'data' => $nib,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $nib = Nib::find($id);
        if (!$nib) {
            return response()->json([
                'success' => false,
                'message' => 'NIB not found.',
            ], 404);
        }

        // Hapus file terkait jika ada
        if ($nib->nib_file) {
            FileHelper::deleteFile($nib->nib_file, 'nib');
        }

        $nib->delete();

        return response()->json([
            'success' => true,
            'message' => 'NIB deleted successfully.',
        ], 200);
    }
}
