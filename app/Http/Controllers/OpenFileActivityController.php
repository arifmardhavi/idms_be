<?php

namespace App\Http\Controllers;

use App\Models\OpenFileActivity;
use Illuminate\Http\Request;

class OpenFileActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $open_file_activities = OpenFileActivity::with('user')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Open file activities retrieved successfully.',
            'data' => $open_file_activities,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'file_name' => 'required|string',
            'features' => 'required|string',
        ]);

        try {
            $openFileActivity = OpenFileActivity::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Open file activity logged successfully.',
                'data' => $openFileActivity,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log open file activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $open_file_activities = OpenFileActivity::find($id);
        if (!$open_file_activities) {
            return response()->json([
                'success' => false,
                'message' => 'Open file activity not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Open file activity retrieved successfully.',
            'data' => $open_file_activities,
        ], 200);
    }

    public function showByUserId(string $id)
    {
        $open_file_activities = OpenFileActivity::where('user_id', $id)->orderby('id', 'desc')->get();
        if (!$open_file_activities) {
            return response()->json([
                'success' => false,
                'message' => 'Open file activity not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Open file activity retrieved successfully.',
            'data' => $open_file_activities,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $open_file_activities = OpenFileActivity::find($id);
        if (!$open_file_activities) {
            return response()->json([
                'success' => false,
                'message' => 'Open file activity not found.',
            ], 404);
        }

        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'file_name' => 'sometimes|string',
            'features' => 'sometimes|string',
        ]);

        try {
            $open_file_activities->update($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Open file activity updated successfully.',
                'data' => $open_file_activities,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update open file activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $open_file_activities = OpenFileActivity::find($id);
        if (!$open_file_activities) {
            return response()->json([
                'success' => false,
                'message' => 'Open file activity not found.',
            ], 404);
        }

        try {
            $open_file_activities->delete();
            return response()->json([
                'success' => true,
                'message' => 'Open file activity deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete open file activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
