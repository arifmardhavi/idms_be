<?php

namespace App\Http\Controllers;

use App\Models\Lumpsum_progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Lumpsum_progressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $progress = Lumpsum_progress::with(['contract'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Progress Pekerjaan retrieved successfully.',
            'data' => $progress,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'week' => 'required',
            'plan_progress' => 'required|string|max:3',
            'actual_progress' => 'required|string|max:3',
            'progress_file' => 'required|file|mimes:pdf|max:30720',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $latestProgress = Lumpsum_progress::where('contract_id', $request->contract_id)
                    ->latest('id')
                    ->value('actual_progress');

        if ($latestProgress !== null && $request->actual_progress < $latestProgress) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => [
                    'actual_progress' => ['Progress aktual harus sama atau lebih besar dari sebelumnya (' . $latestProgress . '%)']
                ],
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            $file = $request->file('progress_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
            $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
            $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
            $version = 0; // Awal versi
            // Format nama file
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

            // Cek apakah file dengan nama ini sudah ada di folder tujuan
            while (file_exists(public_path("contract/lumpsum/progress/".$filename))) {
                $version++;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
            // Store file in public/contract/lumpsum/progress
            $path = $file->move(public_path('contract/lumpsum/progress'), $filename);
            if(!$path){
                return response()->json([
                    'success' => false,
                    'message' => 'File Progress failed add.',
                ], 422);
            }  
            $validatedData['progress_file'] = $filename;
            $progress = Lumpsum_progress::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Progress Pekerjaan created successfully.',
                'data' => $progress,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Progress Pekerjaan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $progress = Lumpsum_progress::with('contract')->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Progress Pekerjaan retrieved successfully.',
            'data' => $progress,
        ], 200);
    }

    public function showByContract(string $id)
    {
        $progress = Lumpsum_progress::where('contract_id', $id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Progress Pekerjaan retrieved successfully.',
            'data' => $progress,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $progress = Lumpsum_progress::find($id);
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Progress Pekerjaan not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'week' => 'required',
            'plan_progress' => 'required|string|max:3',
            'actual_progress' => 'required|string|max:3',
            'progress_file' => 'nullable|file|mimes:pdf|max:30720',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('progress_file')) {
                $file = $request->file('progress_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/lumpsum/progress/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/lumpsum/progress
                $path = $file->move(public_path('contract/lumpsum/progress'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'File Progress failed update.',
                    ], 422);
                }  
                $validatedData['progress_file'] = $filename;
                if($progress->progress_file){
                    $progressBefore = public_path('contract/lumpsum/progress/' . $progress->progress_file);
                    if (file_exists($progressBefore)) {
                        unlink($progressBefore); // Hapus file
                    }
                }
            }
            
            if($progress->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Progress Pekerjaan updated successfully.',
                    'data' => $progress,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Progress Pekerjaan.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Progress Pekerjaan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $progress = Lumpsum_progress::find($id);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Progress Pekerjaan not found.',
            ], 404);
        }

        try {

            $progressBefore = public_path('contract/lumpsum/progress/' . $progress->progress_file);
            if (file_exists($progressBefore)) {
                unlink($progressBefore); // Hapus file
            }
            $progress->delete();

            return response()->json([
                'success' => true,
                'message' => 'Progress Pekerjaan deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Progress Pekerjaan.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
