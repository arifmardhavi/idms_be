<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Gms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GmsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = Gms::query()
            ->orderBy($sortBy, $sortOrder);

        // GLOBAL SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%");
            });
        }

        // FILTER BY TANGGAL RANGE
        if ($tanggalStart) {
            $query->whereDate('tanggal', '>=', $tanggalStart);
        }

        if ($tanggalEnd) {
            $query->whereDate('tanggal', '<=', $tanggalEnd);
        }

        // OPSIONAL PAGINATION (default semua data)
        $perPage = $request->get('per_page');

        if ($perPage) {
            $gms = $query->paginate($perPage);
        } else {
            $gms = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'GMS retrieved successfully.',
            'data' => $gms,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'gms_file' => 'nullable|file',
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
            if ($request->hasFile('gms_file')) {
                $validatedData['gms_file'] = FileHelper::uploadWithVersion($request->file('gms_file'), 'gms');
            }

            $gms = Gms::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'GMS created successfully.',
                'data' => $gms,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create GMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gms = Gms::find($id);

        if (!$gms) {
            return response()->json([
                'success' => false,
                'message' => 'GMS not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'GMS retrieved successfully.',
            'data' => $gms,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $gms = Gms::find($id);

        if (!$gms) {
            return response()->json([
                'success' => false,
                'message' => 'GMS not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'gms_file' => 'sometimes|nullable|file',
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
            if ($request->hasFile('gms_file')) {
                // Hapus file lama jika ada
                if ($gms->gms_file) {
                    FileHelper::deleteFile($gms->gms_file, 'gms');
                }
                $validatedData['gms_file'] = FileHelper::uploadWithVersion($request->file('gms_file'), 'gms');
            }

            $gms->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'GMS updated successfully.',
                'data' => $gms,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update GMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $gms = Gms::find($id);

        if (!$gms) {
            return response()->json([
                'success' => false,
                'message' => 'GMS not found.',
            ], 404);
        }

        try {
            // File dihapus otomatis lewat Model boot()->deleting()
            $gms->delete();

            return response()->json([
                'success' => true,
                'message' => 'GMS deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete GMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadGmsFile(string $id)
    {
        $gms = Gms::find($id);

        if (!$gms) {
            return response()->json([
                'success' => false,
                'message' => 'GMS not found.',
            ], 404);
        }

        if (!$gms->gms_file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'Gms', [
            'recordId'    => $gms->id,
            'recordLabel' => $gms->title ?? $gms->id,
            'metadata'    => ['file' => $gms->gms_file],
        ]);

        return FileHelper::downloadFile('gms', $gms->gms_file);
    }

    public function downloadGmsFiles(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend

        // Ambil data GMS berdasarkan ID yang dipilih
        $gmss = Gms::whereIn('id', $ids)->get();

        // Buat file ZIP untuk menyimpan file GMS
        $zip = new \ZipArchive();
        $zipFilePath = public_path('gms_files.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }

        foreach ($gmss as $gms) {
            // Cek jika file GMS ada dan file tersebut valid
            if ($gms->gms_file) {
                $filePath = public_path('gms/' . $gms->gms_file);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));
                }
            }
        }

        $zip->close();

        activity()->log('download', 'Gms', [
            'metadata' => ['count' => count($gmss ?? [])],
        ]);

        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('gms_files.zip')]);
    }
}
