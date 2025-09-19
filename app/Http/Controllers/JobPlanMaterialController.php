<?php

namespace App\Http\Controllers;

use App\Models\JobPlanMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobPlanMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $job_plan_material = JobPlanMaterial::orderBy('id', 'desc')->get();
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
            'readiness_material_id' => 'required|exists:readiness_materials,id',
            'no_wo' => 'required|integer',
            'kak_file' => 'nullable|file',
            'boq_file' => 'nullable|file',
            'target_date' => 'required|date',
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
                $file = $request->file('kak_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/job_plan/kak/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/job_plan/kak'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload KAK file.',
                    ], 500);
                }
                $validatedData['kak_file'] = $filename;
            }
            if($request->hasFile('boq_file')){
                $file = $request->file('boq_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/job_plan/boq/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/job_plan/boq'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload BOQ file.',
                    ], 500);
                }
                $validatedData['boq_file'] = $filename;
            }
            $job_plan_material = JobPlanMaterial::create($validatedData);

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
        $job_plan_material = JobPlanMaterial::find($id);

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
        $job_plan_material = JobPlanMaterial::with(['readiness_material'])->where('readiness_material_id', $id)->orderby('id', 'desc')->get();

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
        $job_plan_material = JobPlanMaterial::find($id);

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
            'target_date' => 'sometimes|date',
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
                $file = $request->file('kak_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/job_plan/kak/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/job_plan/kak'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload KAK file.',
                    ], 500);
                }
                if ($job_plan_material->kak_file && file_exists(public_path("readiness_ta/material/job_plan/kak/" . $job_plan_material->kak_file))) {
                    unlink(public_path("readiness_ta/material/job_plan/kak/" . $job_plan_material->kak_file));
                }
                $validatedData['kak_file'] = $filename;
            }
            if($request->hasFile('boq_file')){
                $file = $request->file('boq_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/job_plan/boq/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/job_plan/boq'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload BOQ file.',
                    ], 500);
                }
                if ($job_plan_material->boq_file && file_exists(public_path("readiness_ta/material/job_plan/boq/" . $job_plan_material->boq_file))) {
                    unlink(public_path("readiness_ta/material/job_plan/boq/" . $job_plan_material->boq_file));
                }
                $validatedData['boq_file'] = $filename;
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
        $job_plan_material = JobPlanMaterial::find($id);

        if (!$job_plan_material) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan Material not found.',
            ], 404);
        }

        try {
            if ($job_plan_material->kak_file && file_exists(public_path("readiness_ta/material/job_plan/kak/" . $job_plan_material->kak_file))) {
                unlink(public_path("readiness_ta/material/job_plan/kak/" . $job_plan_material->kak_file));
            }
            if ($job_plan_material->boq_file && file_exists(public_path("readiness_ta/material/job_plan/boq/" . $job_plan_material->boq_file))) {
                unlink(public_path("readiness_ta/material/job_plan/boq/" . $job_plan_material->boq_file));
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
