<?php

namespace App\Http\Controllers;

use App\Models\Coi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coi = Coi::with('tag_number')->get();

        return response()->json([
            'success' => true,
            'message' => 'COI retrieved successfully.',
            'data' => $coi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_certificate' => 'required|string|max:255',
            'coi_certificate' => 'required|file|mimes:pdf|max:3072',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'file_rla' => 'nullable|file|mimes:pdf|max:3072|required_if:rla,1', // required if rla is 1
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
            // Handle coi_certificate upload
            if ($request->hasFile('coi_certificate')) {
                $file = $request->file('coi_certificate');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/coi/certificates
                $path = $file->move(public_path('coi/certificates'), $filename);  
                $validatedData['coi_certificate'] = $filename;
            }

            // Handle file_rla upload (if exists)
            if ($request->hasFile('file_rla')) {
                $file = $request->file('file_rla');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/coi/rla
                $path = $file->move(public_path('coi/rla'), $filename);  
                $validatedData['file_rla'] = $filename; 
            }

            $coi = Coi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'COI created successfully.',
                'data' => $coi,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'COI retrieved successfully.',
            'data' => $coi,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_certificate' => 'required|string|max:255',
            'coi_certificate' => 'nullable|file|mimes:pdf|max:3072',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'file_rla' => 'nullable|file|mimes:pdf|max:3072|required_if:rla,1', // required if rla is 1
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
            if ($request->hasFile('coi_certificate')) {
                if ($coi->coi_certificate) {
                    $path = public_path('coi/certificates/' . $coi->coi_certificate);
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }
                if ($request->hasFile('coi_certificate')) {
                    $file = $request->file('coi_certificate');
                    $filename = uniqid().'_' . $file->getClientOriginalName();
                    // Store file in public/coi/certificates
                    $path = $file->move(public_path('coi/certificates'), $filename);  
                    $validatedData['coi_certificate'] = $filename;
                }
            }

            if ($request->hasFile('file_rla')) {
                if ($coi->file_rla) {
                    $path = public_path('coi/rla/' . $coi->file_rla);
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }
                $file = $request->file('file_rla');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/coi/rla
                $path = $file->move(public_path('coi/rla'), $filename);  
                $validatedData['file_rla'] = $filename;
            }

            $coi->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'COI updated successfully.',
                'data' => $coi,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        try {
            if ($coi->coi_certificate) {
                $path = public_path('coi/certificates/' . $coi->coi_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($coi->file_rla) {
                $path = public_path('coi/rla/' . $coi->file_rla);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            $coi->delete();

            return response()->json([
                'success' => true,
                'message' => 'COI deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
