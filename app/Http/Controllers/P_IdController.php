<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\P_id;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class P_IdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $p_id = P_id::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Notif jasa retrieved successfully.',
            'data' => $p_id,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_name' => 'nullable|string',
            'p_id_file' => 'required|array',
            'p_id_file*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
            'tanggal' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }
        if (count($request->file('p_id_file')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            $result = [];
            $failedFiles = [];

            foreach ($request->file('p_id_file') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $filename = FileHelper::uploadWithVersion($file, 'p_id');

                    $file_name = $request->input('file_name') ?? $filename;

                    $p_id = P_id::create([
                        'p_id_file' => $filename,
                        'file_name' => $file_name,
                        'tanggal' => $request->input('tanggal'),
                    ]);

                    $result[] = $p_id;

                } catch (\Throwable $fileError) {
                    $failedFiles[] = [
                        'name' => $originalName,
                        'error' => $fileError->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload selesai.',
                'data' => $result,
                'failed_files' => $failedFiles,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload lampiran.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $p_id = P_id::find($id);
        if (!$p_id) {
            return response()->json([
                'success' => false,
                'message' => 'P_ID not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'P_ID retrieved successfully.',
            'data' => $p_id,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $p_id = P_id::find($id);
        if (!$p_id) {
            return response()->json([
                'success' => false,
                'message' => 'P_ID not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'file_name' => 'sometimes|nullable|string',
            'p_id_file' => 'sometimes|nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
            'tanggal' => 'sometimes|nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        try {
            if($request->hasFile('p_id_file')){
                FileHelper::deleteFile($p_id->p_id_file, 'p_id');

                $filename = FileHelper::uploadWithVersion($request->file('p_id_file'), 'p_id');

                $validatedData['p_id_file'] = $filename;
            }
            $p_id = P_id::find($id);
            if (!$p_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'P_ID not found.',
                ], 404);
            }
            $p_id->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'P_ID updated successfully.',
                'data' => $p_id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update P_ID.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $p_id = P_id::find($id);
        if (!$p_id) {
            return response()->json([
                'success' => false,
                'message' => 'P_ID not found.',
            ], 404);
        }
        try {
            FileHelper::deleteFile($p_id->p_id_file, 'p_id');

            $p_id->delete();
            return response()->json([
                'success' => true,
                'message' => 'P_ID deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete P_ID.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function downloadPIdFile(string $id)
    {
        $p_id = P_id::find($id);

        if (!$p_id) {
            return response()->json([
                'success' => false,
                'message' => 'P & ID not found.',
            ], 404);
        }

        if (!$p_id->p_id_file) {
            return response()->json([
                'success' => false,
                'message' => 'P & ID file not found.',
            ], 404);
        }

        activity()->log('download', 'P_id', [
            'recordId'    => $p_id->id,
            'recordLabel' => $p_id->p_id ?? $p_id->id,
            'metadata'    => ['file' => $p_id->p_id_file],
        ]);

        return FileHelper::downloadFile('p_id', $p_id->p_id_file);
    }
}
