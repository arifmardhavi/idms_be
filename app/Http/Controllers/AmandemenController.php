<?php

namespace App\Http\Controllers;

use App\Models\Amandemen;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AmandemenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $amandemen = Amandemen::all();

        return response()->json([
            'success' => true,
            'message' => 'amandemen retrieved successfully.',
            'data' => $amandemen,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id', 
            'ba_agreement_file' => 'required|file|mimes:pdf|max:5120' , 
            'result_amendemen_file'=> 'required|file|mimes:pdf|max:5120', 
            'principle_permit_file' => 'nullable|file|mimes:pdf|max:5120',
            'amandemen_price' => 'nullable|integer', 
            'amandemen_end_date' => 'nullable|date', 
            'amandemen_penalty' => 'nullable|integer' , 
            'amandemen_termin' => 'nullable|string|max:255',
        ]);

        $validator->after(function ($validator) use ($request) {
            $fields = [
                $request->input('amandemen_price'),
                $request->input('amandemen_end_date'),
                $request->input('amandemen_penalty'),
                $request->input('amandemen_termin'),
            ];
        
            $filled = array_filter($fields, fn($value) => !is_null($value) && $value !== '');
        
            if (count($filled) === 0) {
                $validator->errors()->add(
                    'amandemen_group',
                    'Minimal salah satu dari perubahan nilai, perubahan waktu, denda, atau perubahan termin harus diisi.'
                );
            }

            $contract = Contract::find($request->input('contract_id'));
            $amandemenPrice = $request->input('amandemen_price');
            $principlePermit = $request->file('principle_permit_file');
            if ($contract && $amandemenPrice) {
                $contractPrice = $contract->initial_contract_price;
        
                if ($contractPrice > 0) {
                    $increasePercentage = (($amandemenPrice - $contractPrice) / $contractPrice) * 100;
        
                    if ($increasePercentage > 10 && !$principlePermit) {
                        $validator->errors()->add(
                            'principle_permit_file',
                            'File principle permit wajib diunggah karena perubahan nilai amandemen naik lebih dari 10% dari nilai kontrak awal.'
                        );
                    }
                }
            }
            if ($contract) {
                $amandemenEndDate = $request->input('amandemen_end_date');
        
                if (!is_null($amandemenPrice) && $amandemenPrice <= $contract->contract_price) {
                    $validator->errors()->add(
                        'amandemen_price',
                        'Perubahan Nilai Amandemen tidak boleh lebih kecil dari nilai kontrak yaitu ' . number_format($contract->contract_price, 0, ',', '.')
                    );
                }
        
                if (!is_null($amandemenEndDate) && $amandemenEndDate <= $contract->contract_end_date) {
                    $formattedDate = Carbon::parse($contract->contract_end_date)->translatedFormat('d F Y'); // Contoh: 11 November 2026
                    $validator->errors()->add(
                        'amandemen_end_date',
                        'Perubahan waktu Amandemen tidak boleh lebih awal dari tanggal akhir kontrak yaitu ' . $formattedDate
                    );
                }
            }
        });
        
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $file = $request->file('ba_agreement_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
            $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
            $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
            $version = 0; // Awal versi
            // Format nama file
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

            // Cek apakah file dengan nama ini sudah ada di folder tujuan
            while (file_exists(public_path("contract/amandemen/ba_agreement/".$filename))) {
                $version++;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
            // Store file in public/contract/amandemen/ba_agreement
            $path = $file->move(public_path('contract/amandemen/ba_agreement'), $filename);
            if(!$path){
                return response()->json([
                    'success' => false,
                    'message' => 'ba_agreement File failed upload.',
                ], 422);
            }  
            $validatedData['ba_agreement_file'] = $filename;

            if($request->hasFile('result_amendemen_file')){
                $file = $request->file('result_amendemen_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/amandemen/result_amendemen/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract/amandemen/result_amendemen'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'result_amendemen File failed upload.',
                    ], 422);
                }  
                $validatedData['result_amendemen_file'] = $filename;
            }
            if($request->hasFile('principle_permit_file')){
                $file = $request->file('principle_permit_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/amandemen/principle_permit/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract/amandemen/principle_permit'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'principle_permit File failed upload.',
                    ], 422);
                }  
                $validatedData['principle_permit_file'] = $filename;
            }
            $amandemen = Amandemen::create($validatedData);

            // Tambahkan logika update contract di sini
            if ($amandemen) {
                $contract = Contract::find($amandemen->contract_id);

                if ($contract) {
                    $updated = false;

                    if (!is_null($amandemen->amandemen_price)) {
                        $contract->contract_price = $amandemen->amandemen_price;
                        $updated = true;
                    }

                    if (!is_null($amandemen->amandemen_end_date)) {
                        $contract->contract_end_date = $amandemen->amandemen_end_date;
                        $updated = true;
                    }

                    if (!is_null($amandemen->amandemen_penalty) && !is_null($contract->initial_contract_price)) {
                        $contract->contract_penalty = ($contract->initial_contract_price * ($amandemen->amandemen_penalty / 100));
                        $updated = true;
                    }

                    if ($updated) {
                        $contract->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'amandemen created successfully.',
                'data' => $amandemen,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create amandemen.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $amandemen = Amandemen::with('contract')->find($id);

        if (!$amandemen) {
            return response()->json([
                'success' => false,
                'message' => 'amandemen not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'amandemen retrieved successfully.',
            'data' => $amandemen,
        ], 200);
    }

    public function showByContract(string $id)
    {
        $amandemen = Amandemen::where('contract_id', $id)->with('contract')->get();

        if (!$amandemen) {
            return response()->json([
                'success' => false,
                'message' => 'amandemen not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'amandemen retrieved successfully.',
            'data' => $amandemen,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $amandemen = Amandemen::find($id);
        
        if (!$amandemen) {
            return response()->json([
                'success' => false,
                'message' => 'amandemen not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id', 
            'ba_agreement_file' => 'required|file|mimes:pdf|max:5120' , 
            'result_amendemen_file'=> 'required|file|mimes:pdf|max:5120', 
            'principle_permit_file' => 'nullable|file|mimes:pdf|max:5120',
            'amandemen_price' => 'nullable|integer', 
            'amandemen_end_date' => 'nullable|date', 
            'amandemen_penalty' => 'nullable|integer' , 
            'amandemen_termin' => 'nullable|string|max:255',
        ]);

        $validator->after(function ($validator) use ($request) {
            $fields = [
                $request->input('amandemen_price'),
                $request->input('amandemen_end_date'),
                $request->input('amandemen_penalty'),
                $request->input('amandemen_termin'),
            ];
        
            $filled = array_filter($fields, fn($value) => !is_null($value) && $value !== '');
        
            if (count($filled) === 0) {
                $validator->errors()->add(
                    'amandemen_group',
                    'Minimal salah satu dari perubahan nilai, perubahan waktu, denda, atau perubahan termin harus diisi.'
                );
            }

            $contract = Contract::find($request->input('contract_id'));
            $amandemenPrice = $request->input('amandemen_price');
            $principlePermit = $request->file('principle_permit_file');
            if ($contract && $amandemenPrice) {
                $contractPrice = $contract->initial_contract_price;
        
                if ($contractPrice > 0) {
                    $increasePercentage = (($amandemenPrice - $contractPrice) / $contractPrice) * 100;
        
                    if ($increasePercentage > 10 && !$principlePermit) {
                        $validator->errors()->add(
                            'principle_permit_file',
                            'File principle permit wajib diunggah karena perubahan nilai amandemen naik lebih dari 10% dari nilai kontrak awal.'
                        );
                    }
                }
            }
            if ($contract) {
                $amandemenEndDate = $request->input('amandemen_end_date');

                if (!is_null($amandemenPrice) && $amandemenPrice <= $contract->contract_price) {
                    $validator->errors()->add(
                        'amandemen_price',
                        'Perubahan Nilai Amandemen tidak boleh lebih kecil dari nilai kontrak yaitu ' . number_format($contract->contract_price, 0, ',', '.')
                    );
                }
        
                if (!is_null($amandemenEndDate) && $amandemenEndDate <= $contract->contract_end_date) {
                    $formattedDate = Carbon::parse($contract->contract_end_date)->translatedFormat('d F Y'); // Contoh: 11 November 2026
                    $validator->errors()->add(
                        'amandemen_end_date',
                        'Perubahan waktu Amandemen tidak boleh lebih awal dari tanggal akhir kontrak yaitu ' . $formattedDate
                    );
                }
            }
        });
        
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('ba_agreement_file')){
                
                $file = $request->file('ba_agreement_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/amandemen/ba_agreement/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract/amandemen/ba_agreement
                $path = $file->move(public_path('contract/amandemen/ba_agreement'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'ba_agreement File failed upload.',
                    ], 422);
                } 
                
                if ($amandemen->ba_agreement_file) {
                    $remove_path = public_path('contract/amandemen/ba_agreement/' . $amandemen->ba_agreement_file);
                    if (file_exists($remove_path)) {
                        unlink($remove_path); // Hapus file
                    }
                }
    
                $validatedData['ba_agreement_file'] = $filename;
            }

            if($request->hasFile('result_amendemen_file')){
                $file = $request->file('result_amendemen_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/amandemen/result_amendemen/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract/amandemen/result_amendemen'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'result_amendemen File failed upload.',
                    ], 422);
                }  
                if ($amandemen->result_amendemen_file) {
                    $remove_path = public_path('contract/amandemen/result_amendemen/' . $amandemen->result_amendemen_file);
                    if (file_exists($remove_path)) {
                        unlink($remove_path); // Hapus file
                    }
                }
                $validatedData['result_amendemen_file'] = $filename;
            }
            if($request->hasFile('principle_permit_file')){
                $file = $request->file('principle_permit_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("contract/amandemen/principle_permit/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/contract
                $path = $file->move(public_path('contract/amandemen/principle_permit'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'principle_permit File failed upload.',
                    ], 422);
                }  
                if ($amandemen->principle_permit_file) {
                    $remove_path = public_path('contract/amandemen/principle_permit/' . $amandemen->principle_permit_file);
                    if (file_exists($remove_path)) {
                        unlink($remove_path); // Hapus file
                    }
                }
                $validatedData['principle_permit_file'] = $filename;
            }
            $amandemen = Amandemen::create($validatedData);

            // Tambahkan logika update contract di sini
            if ($amandemen) {
                $contract = Contract::find($amandemen->contract_id);

                if ($contract) {
                    $updated = false;

                    if (!is_null($amandemen->amandemen_price)) {
                        $contract->contract_price = $amandemen->amandemen_price;
                        $updated = true;
                    }

                    if (!is_null($amandemen->amandemen_end_date)) {
                        $contract->contract_end_date = $amandemen->amandemen_end_date;
                        $updated = true;
                    }

                    if (!is_null($amandemen->amandemen_penalty) && !is_null($contract->initial_contract_price)) {
                        $contract->contract_penalty = ($contract->initial_contract_price * ($amandemen->amandemen_penalty / 100));
                        $updated = true;
                    }

                    if ($updated) {
                        $contract->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'amandemen created successfully.',
                'data' => $amandemen,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create amandemen.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $amandemen = Amandemen::find($id);

        if (!$amandemen) {
            return response()->json([
                'success' => false,
                'message' => 'amandemen not found.',
            ], 404);
        }

        try {
            if ($amandemen->ba_agreement_file) {
                $path = public_path('contract/amandemen/ba_agreement/' . $amandemen->ba_agreement_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($amandemen->result_amendemen_file) {
                $path = public_path('contract/amandemen/result_amendemen/' . $amandemen->result_amendemen_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($amandemen->principle_permit_file) {
                $path = public_path('contract/amandemen/principle_permit/' . $amandemen->principle_permit_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            
            $amandemen->delete();

            return response()->json([
                'success' => true,
                'message' => 'amandemen deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete amandemen.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
