<?php

namespace App\Http\Controllers;

use App\Models\Plo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plo = Plo::all();

        return response()->json([
            'success' => true,
            'message' => 'PLO retrieved successfully.',
            'data' => $plo,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255',
            'no_certificate' => 'required|string|max:255',
            'plo_certificate' => 'required|file|mimes:pdf|max:3072',
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
            // Handle plo_certificate upload
            if ($request->hasFile('plo_certificate')) {
                $file = $request->file('plo_certificate');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/plo/certificates
                $path = $file->move(public_path('plo/certificates'), $filename);  
                $validatedData['plo_certificate'] = $filename;
            }

            // Handle file_rla upload (if exists)
            if ($request->hasFile('file_rla')) {
                $file = $request->file('file_rla');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/plo/rla
                $path = $file->move(public_path('plo/rla'), $filename);  
                $validatedData['file_rla'] = $filename;
            }

            // Create new Plo record
            $plo = Plo::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PLO created successfully.',
                'data' => $plo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plo = Plo::find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PLO retrieved successfully.',
            'data' => $plo,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $plo = Plo::find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'tag_number' => 'required|string|max:255',
            'no_certificate' => 'required|string|max:255',
            'plo_certificate' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date|after_or_equal:issue_date',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1',
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue',
            'file_rla' => 'nullable|file|mimes:pdf,doc,docx|max:2048|required_if:rla,1',
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
            if ($request->hasFile('plo_certificate')) {
                if ($plo->plo_certificate) {
                    $validatedData['last_plo_certificate'] = $plo->plo_certificate;
                    if ($plo->last_plo_certificate) {
                        $path = public_path('plo/certificates/' . $plo->last_plo_certificate);
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                }
                if ($request->hasFile('plo_certificate')) {
                    $file = $request->file('plo_certificate');
                    $filename = uniqid().'_' . $file->getClientOriginalName();
                    // Store file in public/plo/certificates
                    $path = $file->move(public_path('plo/certificates'), $filename);  
                    $validatedData['plo_certificate'] = $filename;
                }
            }

            if ($request->hasFile('file_rla')) {
                if ($plo->file_rla) {
                    $path = public_path('plo/rla/' . $plo->file_rla);
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }
                $file = $request->file('file_rla');
                $filename = uniqid().'_' . $file->getClientOriginalName();
                // Store file in public/plo/rla
                $path = $file->move(public_path('plo/rla'), $filename);  
                $validatedData['file_rla'] = $filename;
            }

            $plo->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PLO updated successfully.',
                'data' => $plo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plo = Plo::find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }

        try {
            if ($plo->plo_certificate) {
                $path = public_path('plo/certificates/' . $plo->plo_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $path_last = public_path('plo/certificates/' . $plo->last_plo_certificate);
                if (file_exists($path_last)) {
                    unlink($path_last); // Hapus file
                }
            }
            if ($plo->file_rla) {
                $path = public_path('plo/rla/' . $plo->file_rla);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }            
            $plo->delete();
            return response()->json([
                'success' => true,
                'message' => 'PLO deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
