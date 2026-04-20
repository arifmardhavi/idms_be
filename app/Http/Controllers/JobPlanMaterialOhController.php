<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\JobPlanMaterialOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobPlanMaterialOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $job_plan_material = JobPlanMaterialOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Job Plan Material retrieved successfully.',
            'data' => $job_plan_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_material_oh_id' => 'required|exists:readiness_material_ohs,id',
            'no_wo' => 'nullable|integer',
            'kak_file' => 'nullable|file',
            'boq_file' => 'nullable|file',
            'target_date' => 'nullable|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();

        try {
            if($request->hasFile('kak_file')){
                $validatedData['kak_file'] = FileHelper::uploadWithVersion($request->file('kak_file'), 'readiness_oh/material/job_plan/kak/');
            }
            if($request->hasFile('boq_file')){
                $validatedData['boq_file'] = FileHelper::uploadWithVersion($request->file('boq_file'), 'readiness_oh/material/job_plan/boq/');
            }
            $job_plan_material = JobPlanMaterialOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Material created successfully.',
                'data' => $job_plan_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Job Plan Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $job_plan_material = JobPlanMaterialOh::find($id);

        if (!$job_plan_material) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan Material retrieved successfully.',
            'data' => $job_plan_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $job_plan_material = JobPlanMaterialOh::with(['readiness_material_oh'])->where('readiness_material_oh_id', $id)->orderby('id', 'desc')->get();

        if (!$job_plan_material) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan Material retrieved successfully.',
            'data' => $job_plan_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $job_plan_material = JobPlanMaterialOh::find($id);

        if (!$job_plan_material) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_wo' => 'sometimes|integer',
            'kak_file' => 'nullable|file',
            'boq_file' => 'nullable|file',
            'target_date' => 'sometimes|nullable|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('kak_file')){
                $validatedData['kak_file'] = FileHelper::uploadWithVersion($request->file('kak_file'), 'readiness_oh/material/job_plan/kak/');
                 // Hapus file lama jika ada
                if ($job_plan_material->kak_file) {
                    FileHelper::deleteFile($job_plan_material->kak_file, 'readiness_oh/material/job_plan/kak/');
                }
            }
            if($request->hasFile('boq_file')){
                $validatedData['boq_file'] = FileHelper::uploadWithVersion($request->file('boq_file'), 'readiness_oh/material/job_plan/boq/');
                 // Hapus file lama jika ada
                if ($job_plan_material->boq_file) {
                    FileHelper::deleteFile($job_plan_material->boq_file, 'readiness_oh/material/job_plan/boq/');
                }
            }
            $job_plan_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Material updated successfully.',
                'data' => $job_plan_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Job Plan Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $job_plan_material = JobPlanMaterialOh::find($id);

        if (!$job_plan_material) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Material not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if ($job_plan_material->kak_file) {
                FileHelper::deleteFile($job_plan_material->kak_file, 'readiness_oh/material/job_plan/kak/');
            }
            if ($job_plan_material->boq_file) {
                FileHelper::deleteFile($job_plan_material->boq_file, 'readiness_oh/material/job_plan/boq/');
            }

            $job_plan_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Job Plan Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
