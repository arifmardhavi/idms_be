<?php

namespace App\Http\Controllers;

use App\Models\NotifJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotifJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notif_jasa = NotifJasa::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Notif jasa retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'no_notif' => 'required|integer',
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
            $notif_jasa = NotifJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notif jasa created successfully.',
                'data' => $notif_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Notif jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notif_jasa = NotifJasa::find($id);
        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif jasa retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $notif_jasa = NotifJasa::with(['readiness_jasa'])->where('readiness_jasa_id', $id)->orderby('id', 'desc')->get();

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif jasa retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notif_jasa = NotifJasa::find($id);

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'sometimes|exists:readiness_jasas,id',
            'no_notif' => 'sometimes|integer',
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
            $notif_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notif jasa updated successfully.',
                'data' => $notif_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Notif jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notif_jasa = NotifJasa::find($id);

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif jasa not found.',
            ], 404);
        }

        try {
            $notif_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notif jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Notif jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
