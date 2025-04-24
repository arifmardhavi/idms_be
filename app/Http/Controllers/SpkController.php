<?php

namespace App\Http\Controllers;

use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpkController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spk = Spk::with('contract')->get();

        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'required|file|mimes:pdf|max:25600',
            'spk_status' => 'required|in:0,1',
            'invoice' => 'required|in:0,1',
            'invoice_value' => 'nullable|integer',
            'invoice_file' => 'nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $file = $request->file('spk_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
            $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
            $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
            $version = 0; // Awal versi
            // Format nama file
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

            // Cek apakah file dengan nama ini sudah ada di folder tujuan
            while (file_exists(public_path("contract/spk/".$filename))) {
                $version++;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
            // Store file in public/contract/spk
            $path = $file->move(public_path('contract/spk'), $filename);
            if(!$path){
                return response()->json([
                    'success' => false,
                    'message' => 'Spk Document failed add.',
                ], 422);
            }  
            $validatedData['spk_file'] = $filename;
            if ($request->hasFile('invoice_file')) {
                $file = $request->file('invoice_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/spk/invoice/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/spk/invoice
                $path = $file->move(public_path('contract/spk/invoice'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice Document failed add.',
                    ], 422);
                }  
                $validatedData['invoice_file'] = $filename;
            }
            $spk = Spk::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'spk created successfully.',
                'data' => $spk,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $spk = Spk::with('contract')->find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ], 200);
    }

    public function showByContract(string $id)
    {
        $spkList = Spk::where('contract_id', $id)->with('contract')->get();

        if ($spkList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'SPK not found.',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'SPK retrieved successfully.',
            'data' => $spkList,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $spk = Spk::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'nullable|file|mimes:pdf|max:25600',
            'spk_status' => 'required|in:0,1',
            'invoice' => 'required|in:0,1',
            'invoice_value' => 'nullable|integer',
            'invoice_file' => 'nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('spk_file')){
                $file = $request->file('spk_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/spk/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/spk
                $path = $file->move(public_path('contract/spk'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Spk Document failed add.',
                    ], 422);
                }  
                if($spk->spk_file){
                    $spkBefore = public_path('contract/spk/' . $spk->spk_file);
                    if (file_exists($spkBefore)) {
                        unlink($spkBefore); // Hapus file
                    }
                }
                $validatedData['spk_file'] = $filename;
            }
            if ($request->hasFile('invoice_file')) {
                $file = $request->file('invoice_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/spk/invoice/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/spk/invoice
                $path = $file->move(public_path('contract/spk/invoice'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice Document failed add.',
                    ], 422);
                }  
                if($spk->invoice_file){
                    $spkBefore = public_path('contract/spk/invoice/' . $spk->invoice_file);
                    if (file_exists($spkBefore)) {
                        unlink($spkBefore); // Hapus file
                    }
                }
                $validatedData['invoice_file'] = $filename;
            }
            $spk->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'spk updated successfully.',
                'data' => $spk,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $spk = Spk::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        try {
            if($spk->spk_file){
                $spkBefore = public_path('contract/spk/' . $spk->spk_file);
                if (file_exists($spkBefore)) {
                    unlink($spkBefore); // Hapus file
                }
            }
            if($spk->invoice_file){
                $invoiceBefore = public_path('contract/spk/invoice/' . $spk->invoice_file);
                if (file_exists($invoiceBefore)) {
                    unlink($invoiceBefore); // Hapus file
                }
            }
            $spk->delete();

            return response()->json([
                'success' => true,
                'message' => 'spk deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
