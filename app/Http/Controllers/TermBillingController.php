<?php

namespace App\Http\Controllers;

use App\Models\TermBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TermBillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termBilling = TermBilling::with(['termin', 'termin.contract'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termin_id' => 'required|exists:termins,id',
            'billing_value' => 'required|string|max:100',
            'payment_document' => 'required|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $file = $request->file('payment_document');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
            $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
            $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
            $version = 0; // Awal versi
            // Format nama file
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

            // Cek apakah file dengan nama ini sudah ada di folder tujuan
            while (file_exists(public_path("contract/payment/".$filename))) {
                $version++;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
            // Store file in public/contract/payment
            $path = $file->move(public_path('contract/payment'), $filename);
            if(!$path){
                return response()->json([
                    'success' => false,
                    'message' => 'Payment Document failed add.',
                ], 422);
            }  
            $validatedData['payment_document'] = $filename;
            $termBilling = TermBilling::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Term Billing Status created successfully.',
                'data' => $termBilling,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $termBilling = TermBilling::with('termin')->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }
    public function showByContract(string $id)
    {
        $termBilling = TermBilling::with('termin')
        ->whereHas('termin', function ($query) use ($id) {
            $query->where('contract_id', $id);
        })
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $termBilling = TermBilling::find($id);
        if (!$termBilling) {
            return response()->json([
                'success' => false,
                'message' => 'Term Billing Status not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'termin_id' => 'required|exists:termins,id',
            'billing_value' => 'required|string|max:100',
            'payment_document' => 'sometimes|nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('payment_document')){
                $file = $request->file('payment_document');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/payment/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/payment
                $path = $file->move(public_path('contract/payment'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment Document failed update.',
                    ], 422);
                }  
                $validatedData['payment_document'] = $filename;
                if($termBilling->payment_document){
                    $termBillingBefore = public_path('contract/payment/' . $termBilling->payment_document);
                    if (file_exists($termBillingBefore)) {
                        unlink($termBillingBefore); // Hapus file
                    }
                }
            }
            
            if($termBilling->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Term Billing Status updated successfully.',
                    'data' => $termBilling,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Term Billing Status.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $termBilling = TermBilling::find($id);

        if (!$termBilling) {
            return response()->json([
                'success' => false,
                'message' => 'Term Billing Status not found.',
            ], 404);
        }

        try {

            $termBillingBefore = public_path('contract/payment/' . $termBilling->payment_document);
            if (file_exists($termBillingBefore)) {
                unlink($termBillingBefore); // Hapus file
            }
            $termBilling->delete();

            return response()->json([
                'success' => true,
                'message' => 'Term Billing Status deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
