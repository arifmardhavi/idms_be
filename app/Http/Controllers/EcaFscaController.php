<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\EcaFsca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EcaFscaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $unitId = $request->get('unit_id');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = EcaFsca::with(['unit'])
            ->orderBy($sortBy, $sortOrder);

        // GLOBAL SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('functional_location', 'like', "%$search%")
                  ->orWhereHas('unit', function ($q2) use ($search) {
                      $q2->where('unit_name', 'like', "%$search%");
                  });
            });
        }

        // FILTER BY UNIT
        if ($unitId) {
            $query->where('unit_id', $unitId);
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
            $ecaFscas = $query->paginate($perPage);
        } else {
            $ecaFscas = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'ECA FSCA retrieved successfully.',
            'data' => $ecaFscas,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'functional_location' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'eca_fsca_file' => 'nullable|file',
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
            if ($request->hasFile('eca_fsca_file')) {
                $validatedData['eca_fsca_file'] = FileHelper::uploadWithVersion($request->file('eca_fsca_file'), 'eca_fsca');
            }

            $ecaFsca = EcaFsca::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'ECA FSCA created successfully.',
                'data' => $ecaFsca->load('unit'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ECA FSCA.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ecaFsca = EcaFsca::with(['unit'])->find($id);

        if (!$ecaFsca) {
            return response()->json([
                'success' => false,
                'message' => 'ECA FSCA not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'ECA FSCA retrieved successfully.',
            'data' => $ecaFsca,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ecaFsca = EcaFsca::find($id);

        if (!$ecaFsca) {
            return response()->json([
                'success' => false,
                'message' => 'ECA FSCA not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit_id' => 'sometimes|required|exists:units,id',
            'functional_location' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'eca_fsca_file' => 'sometimes|nullable|file',
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
            if ($request->hasFile('eca_fsca_file')) {
                // Hapus file lama jika ada
                if ($ecaFsca->eca_fsca_file) {
                    FileHelper::deleteFile($ecaFsca->eca_fsca_file, 'eca_fsca');
                }
                $validatedData['eca_fsca_file'] = FileHelper::uploadWithVersion($request->file('eca_fsca_file'), 'eca_fsca');
            }

            $ecaFsca->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'ECA FSCA updated successfully.',
                'data' => $ecaFsca->load('unit'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ECA FSCA.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ecaFsca = EcaFsca::find($id);

        if (!$ecaFsca) {
            return response()->json([
                'success' => false,
                'message' => 'ECA FSCA not found.',
            ], 404);
        }

        try {
            // File dihapus otomatis lewat Model boot()->deleting()
            $ecaFsca->delete();

            return response()->json([
                'success' => true,
                'message' => 'ECA FSCA deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ECA FSCA.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadEcaFscaFile(string $id)
    {
        $ecaFsca = EcaFsca::find($id);

        if (!$ecaFsca) {
            return response()->json([
                'success' => false,
                'message' => 'ECA FSCA not found.',
            ], 404);
        }

        if (!$ecaFsca->eca_fsca_file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'EcaFsca', [
            'recordId'    => $ecaFsca->id,
            'recordLabel' => $ecaFsca->functional_location ?? $ecaFsca->id,
            'metadata'    => ['file' => $ecaFsca->eca_fsca_file],
        ]);

        return FileHelper::downloadFile('eca_fsca', $ecaFsca->eca_fsca_file);
    }

    public function downloadEcaFscaFiles(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend

        // Ambil data ECA FSCA berdasarkan ID yang dipilih
        $ecaFscas = EcaFsca::whereIn('id', $ids)->get();

        // Buat file ZIP untuk menyimpan file ECA FSCA
        $zip = new \ZipArchive();
        $zipFilePath = public_path('eca_fsca_files.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }

        foreach ($ecaFscas as $ecaFsca) {
            // Cek jika file ECA FSCA ada dan file tersebut valid
            if ($ecaFsca->eca_fsca_file) {
                $filePath = public_path('eca_fsca/' . $ecaFsca->eca_fsca_file);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));
                }
            }
        }

        $zip->close();

        activity()->log('download', 'EcaFsca', [
            'metadata' => ['count' => count($ecaFscas ?? [])],
        ]);

        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('eca_fsca_files.zip')]);
    }
}
