<?php

namespace App\Http\Controllers;

use App\Models\JobPlanJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobPlanJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $job_plan_jasa = JobPlanJasa::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Job Plan jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'no_wo' => 'required|integer',
            'kak_file' => 'nullable|file',
            'boq_file' => 'nullable|file',
            'durasi_preparation' => 'required|integer',
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
                while (file_exists(public_path("readiness_ta/jasa/job_plan/kak/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/job_plan/kak'), $filename);
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
                while (file_exists(public_path("readiness_ta/jasa/job_plan/boq/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/job_plan/boq'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload BOQ file.',
                    ], 500);
                }
                $validatedData['boq_file'] = $filename;
            }
            $job_plan_jasa = JobPlanJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan jasa created successfully.',
                'data' => $job_plan_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Job Plan jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $job_plan_jasa = JobPlanJasa::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $job_plan_jasa = JobPlanJasa::with(['readiness_jasa'])->where('readiness_jasa_id', $id)->orderby('id', 'desc')->get();

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job Plan jasa retrieved successfully.',
            'data' => $job_plan_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $job_plan_jasa = JobPlanJasa::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_wo' => 'sometimes|integer',
            'kak_file' => 'nullable|file',
            'boq_file' => 'nullable|file',
            'durasi_preparation' => 'sometimes|integer',
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
                while (file_exists(public_path("readiness_ta/jasa/job_plan/kak/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/job_plan/kak'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload KAK file.',
                    ], 500);
                }

                if ($job_plan_jasa->kak_file && file_exists(public_path("readiness_ta/jasa/job_plan/kak/" . $job_plan_jasa->kak_file))) {
                    unlink(public_path("readiness_ta/jasa/job_plan/kak/" . $job_plan_jasa->kak_file));
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
                while (file_exists(public_path("readiness_ta/jasa/job_plan/boq/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/job_plan/boq'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload BOQ file.',
                    ], 500);
                }

                if ($job_plan_jasa->boq_file && file_exists(public_path("readiness_ta/jasa/job_plan/boq/" . $job_plan_jasa->boq_file))) {
                    unlink(public_path("readiness_ta/jasa/job_plan/boq/" . $job_plan_jasa->boq_file));
                }
                
                $validatedData['boq_file'] = $filename;
            }
            $job_plan_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Job Plan jasa updated successfully.',
                'data' => $job_plan_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Job Plan jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $job_plan_jasa = JobPlanJasa::find($id);

        if (!$job_plan_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Job Plan jasa not found.',
            ], 404);
        }

        try {
            if ($job_plan_jasa->kak_file && file_exists(public_path("readiness_ta/jasa/job_plan/kak/" . $job_plan_jasa->kak_file))) {
                unlink(public_path("readiness_ta/jasa/job_plan/kak/" . $job_plan_jasa->kak_file));
            }
            if ($job_plan_jasa->boq_file && file_exists(public_path("readiness_ta/jasa/job_plan/boq/" . $job_plan_jasa->boq_file))) {
                unlink(public_path("readiness_ta/jasa/job_plan/boq/" . $job_plan_jasa->boq_file));
            }

            $job_plan_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job Plan jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Job Plan jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
