<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\ProjectSpec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectSpecController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projectSpecs = ProjectSpec::orderBy('tanggal_project_spec', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Project Specs retrieved successfully.',
            'data' => $projectSpecs,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_project_spec' => 'required|string|max:200|unique:project_specs,no_project_spec',
            'judul' => 'required|string|max:255',
            'tanggal_project_spec' => 'required|date',
            'project_spec_file' => 'required|file|mimes:pdf',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Project Spec',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('project_spec_file')) {
            $validatedData['project_spec_file'] = FileHelper::uploadWithVersion($request->file('project_spec_file'), 'project_specs');
        }

        $projectSpec = ProjectSpec::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Project Spec created successfully.',
            'data' => $projectSpec,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $projectSpec = ProjectSpec::find($id);
        if (!$projectSpec) {
            return response()->json([
                'success' => false,
                'message' => 'Project Spec not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Project Spec retrieved successfully.',
            'data' => $projectSpec,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $projectSpec = ProjectSpec::find($id);
        if (!$projectSpec) {
            return response()->json([
                'success' => false,
                'message' => 'Project Spec not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_project_spec' => 'sometimes|required|string|max:200|unique:project_specs,no_project_spec,' . $id,
            'judul' => 'sometimes|required|string|max:255',
            'tanggal_project_spec' => 'sometimes|required|date',
            'project_spec_file' => 'sometimes|required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Project Spec',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('project_spec_file')) {
            $validatedData['project_spec_file'] = FileHelper::uploadWithVersion($request->file('project_spec_file'), 'project_specs');
                // Hapus file lama jika ada
                if ($projectSpec->project_spec_file) {
                    FileHelper::deleteFile($projectSpec->project_spec_file, 'project_specs');
                }
        }

        $projectSpec->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Project Spec updated successfully.',
            'data' => $projectSpec,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $projectSpec = ProjectSpec::find($id);
        if (!$projectSpec) {
            return response()->json([
                'success' => false,
                'message' => 'Project Spec not found.',
            ], 404);
        }

        // Hapus file terkait jika ada
        if ($projectSpec->project_spec_file) {
            FileHelper::deleteFile($projectSpec->project_spec_file, 'project_specs');
        }

        $projectSpec->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project Spec deleted successfully.',
        ], 200);
    }

    public function downloadProjectSpecFile(string $id)
    {
        $projectSpec = ProjectSpec::find($id);

        if (!$projectSpec) {
            return response()->json([
                'success' => false,
                'message' => 'Project Spec not found.',
            ], 404);
        }

        if (!$projectSpec->project_spec_file) {
            return response()->json([
                'success' => false,
                'message' => 'Project Spec file not found.',
            ], 404);
        }

        return FileHelper::downloadFile('project_specs', $projectSpec->project_spec_file);
    }
}
