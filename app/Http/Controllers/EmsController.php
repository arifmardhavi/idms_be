<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Ems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $tagNumberId = $request->get('tag_number_id');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = Ems::with(['tag_number'])
            ->orderBy($sortBy, $sortOrder);

        // GLOBAL SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_ems', 'like', "%$search%")
                  ->orWhereHas('tag_number', function ($q2) use ($search) {
                      $q2->where('tag_number', 'like', "%$search%");
                  });
            });
        }

        // FILTER BY TAG NUMBER
        if ($tagNumberId) {
            $query->where('tag_number_id', $tagNumberId);
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
            $ems = $query->paginate($perPage);
        } else {
            $ems = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'EMS retrieved successfully.',
            'data' => $ems,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_ems' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'ems_file' => 'nullable|file',
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
            if ($request->hasFile('ems_file')) {
                $validatedData['ems_file'] = FileHelper::uploadWithVersion($request->file('ems_file'), 'ems');
            }

            $ems = Ems::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'EMS created successfully.',
                'data' => $ems->load('tag_number'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create EMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ems = Ems::with(['tag_number'])->find($id);

        if (!$ems) {
            return response()->json([
                'success' => false,
                'message' => 'EMS not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'EMS retrieved successfully.',
            'data' => $ems,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ems = Ems::find($id);

        if (!$ems) {
            return response()->json([
                'success' => false,
                'message' => 'EMS not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'sometimes|required|exists:tag_numbers,id',
            'no_ems' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'ems_file' => 'sometimes|nullable|file',
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
            if ($request->hasFile('ems_file')) {
                // Hapus file lama jika ada
                if ($ems->ems_file) {
                    FileHelper::deleteFile($ems->ems_file, 'ems');
                }
                $validatedData['ems_file'] = FileHelper::uploadWithVersion($request->file('ems_file'), 'ems');
            }

            $ems->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'EMS updated successfully.',
                'data' => $ems->load('tag_number'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update EMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ems = Ems::find($id);

        if (!$ems) {
            return response()->json([
                'success' => false,
                'message' => 'EMS not found.',
            ], 404);
        }

        try {
            // File dihapus otomatis lewat Model boot()->deleting()
            $ems->delete();

            return response()->json([
                'success' => true,
                'message' => 'EMS deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete EMS.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function showByTagNumber(string $tag_number_id)
    {
        $ems = Ems::with(['tag_number'])
            ->where('tag_number_id', $tag_number_id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'EMS retrieved successfully.',
            'data' => $ems,
        ], 200);
    }

    public function downloadEmsFile(string $id)
    {
        $ems = Ems::find($id);

        if (!$ems) {
            return response()->json([
                'success' => false,
                'message' => 'EMS not found.',
            ], 404);
        }

        if (!$ems->ems_file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'Ems', [
            'recordId'    => $ems->id,
            'recordLabel' => $ems->no_ems ?? $ems->id,
            'metadata'    => ['file' => $ems->ems_file],
        ]);

        return FileHelper::downloadFile('ems', $ems->ems_file);
    }

    public function downloadEmsFiles(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend

        // Ambil data EMS berdasarkan ID yang dipilih
        $emss = Ems::whereIn('id', $ids)->get();

        // Buat file ZIP untuk menyimpan file EMS
        $zip = new \ZipArchive();
        $zipFilePath = public_path('ems_files.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }

        foreach ($emss as $ems) {
            // Cek jika file EMS ada dan file tersebut valid
            if ($ems->ems_file) {
                $filePath = public_path('ems/' . $ems->ems_file);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));
                }
            }
        }

        $zip->close();

        activity()->log('download', 'Ems', [
            'metadata' => ['count' => count($emss ?? [])],
        ]);

        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('ems_files.zip')]);
    }
}
