<?php

namespace App\Http\Controllers;
use App\Models\Contract;
use Carbon\Carbon;
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
            'contract_type' => 'required|in:1,2,3', 
            'contract_date' => 'nullable|date|required_if:contract_type,!=3', // contract_date is required if contract_type is not 3
            'contract_price' => 'required|integer' , 
            'contract_file' => 'required|file|mimes:pdf|max:30720',
            'kom' => 'nullable|in:0,1|required_if:contract_type,!=3', // Kom is required if contract_type is not 3
            'contract_start_date' => 'nullable|date|required_if:kom,1', 
            'contract_end_date' => 'nullable|date|required_if:kom,1', 
            'meeting_notes' => 'nullable|file|mimes:pdf|max:3072', 
            'pengawas' => 'required|in:0,1,2',
            'contract_status' => 'required|in:0,1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['initial_contract_price'] = $request->contract_price;
        $validatedData['total_contract_price'] = $request->contract_price;
        $validatedData['vendor_name'] = strtoupper($request->vendor_name);
        $validatedData['contract_name'] = strtoupper($request->contract_name);

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
    $contract = Contract::withCount(['termin', 'spk', 'amandemen'])
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
            'amandemen_count' => $contract->amandemen_count,
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
            'contract_type' => 'required|in:1,2,3', 
            'contract_date' => 'nullable|date|required_if:contract_type,!=3', // contract_date is required if contract_type is not 3
            'contract_price' => 'nullable|integer|required_if:contract_type,3', // contract_price is required if contract_type is not 3, 
            'initial_contract_price' => 'required|integer',
            'contract_file' => 'nullable|file|mimes:pdf|max:30720',
            'kom' => 'nullable|in:0,1|required_if:contract_type,!=3', // Kom is required if contract_type is not 3
            'contract_start_date' => 'nullable|date|required_if:kom,1', 
            'contract_end_date' => 'nullable|date|required_if:kom,1', 
            'meeting_notes' => 'nullable|file|mimes:pdf|max:3072',
            'pengawas' => 'required|in:0,1,2',  
            'contract_status' => 'required|in:0,1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['contract_penalty'] = ($request->initial_contract_price * ((Contract::find($id)->amandemen()->latest()->first()?->amandemen_penalty ?? 0) / 100))?? 0;
        if ($request->contract_type == 3){
            $validatedData['contract_date'] = null; 
        }      // console.log($validatedData['contract_penalty']);
        // dd($validatedData);
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
            
            $contract = Contract::find($id);
            $latestPenalty = $contract->amandemen()->latest()->first()?->amandemen_penalty ?? 0;
            $latestPrice = $contract->amandemen()->latest()->first()?->amandemen_price ?? 0;

            $validatedData['contract_penalty'] = $request->initial_contract_price * ($latestPenalty / 100);
            $hasAmandemen = $contract->amandemen()->exists();
            if ($hasAmandemen) {
                $validatedData['contract_price'] = $latestPrice;
            }else{
                $validatedData['contract_price'] = $request->initial_contract_price;
            }
            $validatedData['vendor_name'] = strtoupper($request->vendor_name);
            $validatedData['contract_name'] = strtoupper($request->contract_name);
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



    public function monitoring()
    {
        $today = Carbon::today();

         $contracts = Contract::all();
         $count = $contracts->count();

        $blue = Contract::where('contract_status', 0)->count(); // kontrak selesai

        $green = 0;
        $yellow = 0;
        $red = 0;
        $active = 0;
        $lumpsum = 0;
        $unit_price = 0;

        foreach ($contracts as $contract) {
            // Skip kontrak yang sudah selesai (status 0) agar tidak dihitung ganda
            if ($contract->contract_type == 1) {
                $lumpsum++;
            }else{
                $unit_price++;
            }
            if ($contract->contract_status == 0) {
                continue;
            }

            if ($contract->contract_status == 1) {
                $active++;
            }


            $endDate = Carbon::parse($contract->contract_end_date);
            $weeksDiff = $today->diffInWeeks($endDate, false); // false agar bisa negatif

            if ($weeksDiff >= 4) {
                $green++;
            } elseif ($weeksDiff > 0 && $weeksDiff < 4) {
                $yellow++;
            } else {
                $red++;
            }
        }

        // monitoring progress pekerjaan 
        $statusCounts = [
            'blue' => 0,   // Kontrak selesai
            'green' => 0,  // Deviasi 0%
            'yellow' => 0, // Deviasi ≤ 20%
            'red' => 0,  // Deviasi > 20%
            'black' => 0,  // Belum upload amandemen
        ];

        foreach ($contracts as $contract) {
            $color = $contract->monitoring_progress['color'];

            switch ($color) {
                case 'blue':
                    $statusCounts['blue']++;
                    break;
                case 'green':
                    $statusCounts['green']++;
                    break;
                case 'yellow':
                    $statusCounts['yellow']++;
                    break;
                case 'red':
                    $statusCounts['red']++;
                    break;
                case 'black':
                    $statusCounts['black']++;
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Monitoring durasi MPP berhasil.',
            'data' => [
                'total_contract' => $count, 
                'total_active_contract' => $active,
                'total_lumpsum_contract' => $lumpsum,
                'total_unit_price_contract' => $unit_price,
                'monitoring_durasi_mpp' => [
                    'blue' => $blue,
                    'green' => $green,
                    'yellow' => $yellow,
                    'red' => $red,
                ],
                'monitoring_progress_pekerjaan' => [
                    'blue' => $statusCounts['blue'],
                    'green' => $statusCounts['green'],
                    'yellow' => $statusCounts['yellow'],
                    'red' => $statusCounts['red'],
                    'black' => $statusCounts['black'],
                ],
            ]
        ], 200);
    }

}
