<?php

namespace App\Http\Controllers;

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
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $dateNow = date('dmY');
                    $version = 0;

                    $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    while (file_exists(public_path("p_id/" . $filename))) {
                        $version++;
                        $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }

                    $path = $file->move(public_path('p_id'), $filename);
                    if (!$path) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => 'Gagal memindahkan file ke direktori tujuan.'
                        ];
                        continue;
                    }

                    $file_name = $request->input('file_name') ?? $filename;

                    $p_id = P_id::create([
                        'p_id_file' => $filename,
                        'file_name' => $file_name,
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
                $file = $request->file('p_id_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("p_id/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('p_id'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload P&ID file.',
                    ], 500);
                }
                if (file_exists(public_path("p_id/" . $p_id->p_id_file))) {
                    unlink(public_path("p_id/" . $p_id->p_id_file));
                }
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
            if (file_exists(public_path("p_id/" . $p_id->p_id_file))) {
                unlink(public_path("p_id/" . $p_id->p_id_file));
            }
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
}
