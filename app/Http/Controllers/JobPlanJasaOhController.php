<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\JobPlanJasaOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobPlanJasaOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $job_plan_jasa = JobPlanJasaOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Job Plan Jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'required|exists:readiness_jasa_ohs,id',
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
                $validatedData['kak_file'] = FileHelper::uploadWithVersion($request->file('kak_file'), 'readiness_oh/jasa/job_plan/kak/');
            }
            if($request->hasFile('boq_file')){
                $validatedData['boq_file'] = FileHelper::uploadWithVersion($request->file('boq_file'), 'readiness_oh/jasa/job_plan/boq/');
            }
            $job_plan_jasa = JobPlanJasaOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Jasa created successfully.',
                'data' => $job_plan_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Job Plan Jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $job_plan_jasa = JobPlanJasaOh::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan Jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $job_plan_jasa = JobPlanJasaOh::with(['readiness_jasa_oh'])->where('readiness_jasa_oh_id', $id)->orderby('id', 'desc')->get();

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan Jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $job_plan_jasa = JobPlanJasaOh::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Jasa not found.',
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
                $validatedData['kak_file'] = FileHelper::uploadWithVersion($request->file('kak_file'), 'readiness_oh/jasa/job_plan/kak/');
                 // Hapus file lama jika ada
                if ($job_plan_jasa->kak_file) {
                    FileHelper::deleteFile($job_plan_jasa->kak_file, 'readiness_oh/jasa/job_plan/kak/');
                }
            }
            if($request->hasFile('boq_file')){
                $validatedData['boq_file'] = FileHelper::uploadWithVersion($request->file('boq_file'), 'readiness_oh/jasa/job_plan/boq/');
                 // Hapus file lama jika ada
                if ($job_plan_jasa->boq_file) {
                    FileHelper::deleteFile($job_plan_jasa->boq_file, 'readiness_oh/jasa/job_plan/boq/');
                }
            }
            $job_plan_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Jasa updated successfully.',
                'data' => $job_plan_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Job Plan Jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $job_plan_jasa = JobPlanJasaOh::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Jasa not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if ($job_plan_jasa->kak_file) {
                FileHelper::deleteFile($job_plan_jasa->kak_file, 'readiness_oh/jasa/job_plan/kak/');
            }
            if ($job_plan_jasa->boq_file) {
                FileHelper::deleteFile($job_plan_jasa->boq_file, 'readiness_oh/jasa/job_plan/boq/');
            }

            $job_plan_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job Plan Jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Job Plan Jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
