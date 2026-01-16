<?php

namespace App\Http\Controllers;

use App\Models\MdrItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MdrItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mdrItems = MdrItem::with('mdrFolder')->get();
        return response()->json([
            'success' => true,
            'message' => 'MDR Items retrieved successfully.',
            'data' => $mdrItems,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mdr_folder_id' => 'required|exists:mdr_folders,id',
            'file_name' => 'required|array',
            'file_name.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:204800', 
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for MDR Item.',
                'errors' => $validator->errors(),
            ], 422);
        }
        if (count($request->file('file_name')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            $result = [];
            $failedFiles = [];

            foreach ($request->file('file_name') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                    $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                    $version = 0; // Awal versi
                    // Format nama file
                    $filename = $nameOnly . '_' . 'MDR_' . $dateNow . '_' . $version . '.' . $extension;

                    while (file_exists(public_path("engineering_data/mdr/" . $filename))) {
                        $version++;
                        $filename = $nameOnly . '_' . 'MDR_' . $dateNow . '_' . $version . '.' . $extension;
                    }

                    $path = $file->move(public_path('engineering_data/mdr'), $filename);
                    if (!$path) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => 'Gagal memindahkan file ke direktori tujuan.'
                        ];
                        continue;
                    }

                    $mdrItem = MdrItem::create([
                        'mdr_folder_id' => $request->mdr_folder_id,
                        'file_name' => $filename,
                    ]);

                    $result[] = $mdrItem;

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
                'message' => 'Gagal upload datasheet.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mdrItem = MdrItem::with('mdrFolder')->find($id);
        if (!$mdrItem) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Item not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'MDR Item retrieved successfully.',
            'data' => $mdrItem,
        ], 200);
    }
    public function showByFolder(string $id)
    {
        $mdrItem = MdrItem::with('mdrFolder')->where('mdr_folder_id', $id)->get();
        if (!$mdrItem) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Item not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'MDR Item retrieved successfully.',
            'data' => $mdrItem,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mdrItem = MdrItem::find($id);
        if (!$mdrItem) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Item not found.',
            ], 404);
        }
        try {

            // Hapus file dari direktori
            $filePath = public_path('engineering_data/mdr/' . $mdrItem->file_name);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Hapus record dari database
            $mdrItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'MDR Item deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete MDR Item.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
