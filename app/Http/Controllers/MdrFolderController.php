<?php

namespace App\Http\Controllers;

use App\Http\Resources\MdrResource;
use App\Models\MdrFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MdrFolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mdrFolders = MdrFolder::with('mdrItems')->get();
        return response()->json([
            'success' => true,
            'message' => 'MDR Folders retrieved successfully.',
            'data' => MdrResource::collection($mdrFolders),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:100',
            'engineering_data_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $mdrFolder = MdrFolder::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'MDR Folder created successfully.',
                'data' => $mdrFolder,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create MDR Folder.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Get a specific MDR Folder
    public function showByEngineering($id){
        $mdrFolder = MdrFolder::with('mdrItems')->where('engineering_data_id', $id)->get();

        if (!$mdrFolder) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Folder not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'MDR Folder retrieved successfully.',
            'data' => MdrResource::collection($mdrFolder),
        ], 200);
    }

    public function show($id){
        $mdrFolder = MdrFolder::find($id);

        if (!$mdrFolder) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Folder not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'MDR Folder retrieved successfully.',
            'data' => new MdrResource($mdrFolder),
        ], 200);
    }

    // Update a specific MDR Folder
    public function update(Request $request, $id){
        $mdrFolder = MdrFolder::find($id);

        if (!$mdrFolder) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Folder not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'folder_name' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $mdrFolder->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'MDR Folder updated successfully.',
                'data' => new MdrResource($mdrFolder->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update MDR Folder.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific MDR Folder
    public function destroy($id){
        $mdrFolder = MdrFolder::find($id);

        if (!$mdrFolder) {
            return response()->json([
                'success' => false,
                'message' => 'MDR Folder not found.',
            ], 404);
        }

        try {
            $mdrFolder->delete();

            return response()->json([
                'success' => true,
                'message' => 'MDR Folder deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete MDR Folder.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
