<?php

namespace App\Http\Controllers;

use App\Models\NotifJasaOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotifJasaOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notif_jasa = NotifJasaOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Notif Jasa OH retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'required|exists:readiness_jasa_ohs,id',
            'no_notif' => 'nullable|integer',
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
            $notif_jasa = NotifJasaOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notif Jasa OH created successfully.',
                'data' => $notif_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Notif Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notif_jasa = NotifJasaOh::find($id);
        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif Jasa OH retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $notif_jasa = NotifJasaOh::with(['readiness_jasa_oh'])->where('readiness_jasa_oh_id', $id)->orderby('id', 'desc')->get();

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif Jasa OH retrieved successfully.',
            'data' => $notif_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notif_jasa = NotifJasaOh::find($id);

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Jasa OH not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'sometimes|exists:readiness_jasa_ohs,id',
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
                'message' => 'Notif Jasa OH updated successfully.',
                'data' => $notif_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Notif Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notif_jasa = NotifJasaOh::find($id);

        if (!$notif_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Jasa OH not found.',
            ], 404);
        }

        try {
            $notif_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notif Jasa OH deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Notif Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
