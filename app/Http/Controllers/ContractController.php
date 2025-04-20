<?php

namespace App\Http\Controllers;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contract = Contract::all();

        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => $contract,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_vendor' => 'required|string|max:200', 
            'vendor_name' => 'required|string|max:255' , 
            'no_contract'=> 'required|string|max:200|unique:contracts,no_contract', 
            'contract_name' => 'required|string|max:255',
            'contract_type' => 'required|in:1,2', 
            'contract_date' => 'required|date', 
            'contract_price' => 'required|integer' , 
            'contract_file' => 'required|file|mimes:pdf|max:30720',
            'kom' => 'required|in:0,1',
            'contract_start_date' => 'nullable|date|required_if:kom,1', 
            'contract_end_date' => 'nullable|date|required_if:kom,1', 
            'meeting_notes' => 'nullable|file|mimes:pdf|max:3072', 
            'contract_status' => 'required|in:0,1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $file = $request->file('contract_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
            $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
            $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
            $version = 0; // Awal versi
            // Format nama file
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

            // Cek apakah file dengan nama ini sudah ada di folder tujuan
            while (file_exists(public_path("contract/".$filename))) {
                $version++;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
            // Store file in public/contract
            $path = $file->move(public_path('contract'), $filename);
            if(!$path){
                return response()->json([
                    'success' => false,
                    'message' => 'Contract File failed upload.',
                ], 422);
            }  
            $validatedData['contract_file'] = $filename;

            if($request->hasFile('meeting_notes')){
                $file = $request->file('meeting_notes');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/meeting_notes/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract/meeting_notes'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Meeting Notes File failed upload.',
                    ], 422);
                }  
                $validatedData['meeting_notes'] = $filename;
            }
            $contract = Contract::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'contract created successfully.',
                'data' => $contract,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
{
    // Ambil contract + relasi termins dan masing-masing billing count
    $contract = Contract::withCount(['termin', 'spk'])
        ->with(['termin' => function ($query) {
            $query->withCount('termBilling');
        }])
        ->find($id);

    if (!$contract) {
        return response()->json([
            'success' => false,
            'message' => 'Contract not found.',
        ], 404);
    }

    // Hitung total billing dari semua termin
    $billingCount = $contract->termin->sum('term_billing_count');

    return response()->json([
        'success' => true,
        'message' => 'Contract retrieved successfully.',
        'data' => [
            ...$contract->toArray(),
            'termin_count' => $contract->termin_count,
            'billing_count' => $billingCount,
            'spk_count' => $contract->spk_count,
        ],
    ], 200);
}


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract = Contract::find($id);
        
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_vendor' => 'required|string|max:200', 
            'vendor_name' => 'required|string|max:255' , 
            'no_contract'=> 'required|string|max:200|unique:contracts,no_contract,'  . $id, 
            'contract_name' => 'required|string|max:255',
            'contract_type' => 'required|in:1,2', 
            'contract_date' => 'required|date', 
            'contract_price' => 'required|integer' , 
            'contract_file' => 'nullable|file|mimes:pdf|max:30720',
            'kom' => 'required|in:0,1',
            'contract_start_date' => 'nullable|date|required_if:kom,1', 
            'contract_end_date' => 'nullable|date|required_if:kom,1', 
            'meeting_notes' => 'nullable|file|mimes:pdf|max:3072',  
            'contract_status' => 'required|in:0,1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('contract_file')){
                $file = $request->file('contract_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Contract File failed upload.',
                    ], 422);
                }  

                if ($contract->contract_file) {
                    $remove_path = public_path('contract/' . $contract->contract_file);
                    if (file_exists($remove_path)) {
                        unlink($remove_path); // Hapus file
                    }
                }

                $validatedData['contract_file'] = $filename;
            }

            if($request->hasFile('meeting_notes')){
                $file = $request->file('meeting_notes');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/meeting_notes/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/meeting_notes
                $path = $file->move(public_path('contract/meeting_notes'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Meeting Notes File failed upload.',
                    ], 422);
                }  

                if ($contract->meeting_notes) {
                    $remove_path = public_path('contract/meeting_notes/' . $contract->meeting_notes);
                    if (file_exists($remove_path)) {
                        unlink($remove_path); // Hapus file
                    }
                }

                $validatedData['meeting_notes'] = $filename;
            }

            $contract->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully.',
                'data' => $contract,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Contract.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * remove_path the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        try {
            if ($contract->contract_file) {
                $path = public_path('contract/' . $contract->contract_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            
            $contract->delete();

            return response()->json([
                'success' => true,
                'message' => 'contract deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
