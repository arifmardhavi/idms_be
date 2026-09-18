<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\IsoMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IsoMetricController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = IsoMetric::query()
            ->orderBy($sortBy, $sortOrder);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%$search%")
                    ->orWhere('no_drawing', 'like', "%$search%");
            });
        }

        if ($tanggalStart) {
            $query->whereDate('tanggal', '>=', $tanggalStart);
        }

        if ($tanggalEnd) {
            $query->whereDate('tanggal', '<=', $tanggalEnd);
        }

        $perPage = $request->get('per_page');

        if ($perPage) {
            $isoMetrics = $query->paginate($perPage);
        } else {
            $isoMetrics = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'ISO Metric retrieved successfully.',
            'data' => $isoMetrics,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_drawing' => 'required|string|max:255|unique:iso_metrics,no_drawing',
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'iso_metric_file' => 'required|file|mimes:pdf|max:102400',
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
            if ($request->hasFile('iso_metric_file')) {
                $validatedData['iso_metric_file'] = FileHelper::uploadWithVersion($request->file('iso_metric_file'), 'iso_metric');
            }

            $isoMetric = IsoMetric::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'ISO Metric created successfully.',
                'data' => $isoMetric,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ISO Metric.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $isoMetric = IsoMetric::find($id);

        if (!$isoMetric) {
            return response()->json([
                'success' => false,
                'message' => 'ISO Metric not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'ISO Metric retrieved successfully.',
            'data' => $isoMetric,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $isoMetric = IsoMetric::find($id);

        if (!$isoMetric) {
            return response()->json([
                'success' => false,
                'message' => 'ISO Metric not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_drawing' => 'sometimes|required|string|max:255|unique:iso_metrics,no_drawing,' . $id,
            'judul' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'iso_metric_file' => 'sometimes|nullable|file|mimes:pdf|max:102400',
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
            if ($request->hasFile('iso_metric_file')) {
                if ($isoMetric->iso_metric_file) {
                    FileHelper::deleteFile($isoMetric->iso_metric_file, 'iso_metric');
                }
                $validatedData['iso_metric_file'] = FileHelper::uploadWithVersion($request->file('iso_metric_file'), 'iso_metric');
            }

            $isoMetric->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'ISO Metric updated successfully.',
                'data' => $isoMetric,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ISO Metric.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $isoMetric = IsoMetric::find($id);

        if (!$isoMetric) {
            return response()->json([
                'success' => false,
                'message' => 'ISO Metric not found.',
            ], 404);
        }

        try {
            $isoMetric->delete();

            return response()->json([
                'success' => true,
                'message' => 'ISO Metric deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ISO Metric.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadIsoMetricFile(string $id)
    {
        $isoMetric = IsoMetric::find($id);

        if (!$isoMetric) {
            return response()->json([
                'success' => false,
                'message' => 'ISO Metric not found.',
            ], 404);
        }

        if (!$isoMetric->iso_metric_file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'IsoMetric', [
            'recordId'    => $isoMetric->id,
            'recordLabel' => $isoMetric->no_drawing ?? $isoMetric->id,
            'metadata'    => ['file' => $isoMetric->iso_metric_file],
        ]);

        return FileHelper::downloadFile('iso_metric', $isoMetric->iso_metric_file);
    }

    public function downloadIsoMetricFiles(Request $request)
    {
        $ids = $request->input('ids');

        $isoMetrics = IsoMetric::whereIn('id', $ids)->get();

        $zip = new \ZipArchive();
        $zipFilePath = public_path('iso_metric_files.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }

        foreach ($isoMetrics as $isoMetric) {
            if ($isoMetric->iso_metric_file) {
                $filePath = public_path('iso_metric/' . $isoMetric->iso_metric_file);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($filePath));
                }
            }
        }

        $zip->close();

        activity()->log('download', 'IsoMetric', [
            'metadata' => ['count' => count($isoMetrics ?? [])],
        ]);

        return response()->json(['success' => true, 'url' => url('iso_metric_files.zip')]);
    }
}