<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\AmandemenNew;
use App\Models\ContractNew;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AmandemenNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $amandemenNews = AmandemenNew::all();
        return response()->json([
            'success' => true,
            'message' => 'Amandemen News retrieved successfully.',
            'data' => $amandemenNews,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'ba_agreement_file' => 'nullable|file|mimes:pdf|max:5120',
            'result_amandemen_file' => 'nullable|file|mimes:pdf|max:5120',
            'principle_permit_file' => 'nullable|file|mimes:pdf|max:5120',
            'amandemen_price' => 'nullable|integer',
            'amandemen_end_date' => 'nullable|date',
            'amandemen_penalty' => 'nullable|integer',
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

            $contract = ContractNew::find($request->input('contract_new_id'));
            $amandemenPrice = $request->input('amandemen_price');
            $principlePermit = $request->file('principle_permit_file');
            if ($contract && $amandemenPrice) {
                $contractPrice = $contract->contract_price;
        
                if ($contractPrice > 0) {
                    $increasePercentage = (($amandemenPrice - $contractPrice) / $contractPrice) * 100;
        
                    if ($increasePercentage >= 10 && !$principlePermit) {
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
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
            ], 422);   
        }
        
        $validatedData = $validator->validated();
        
        if ($request->hasFile('ba_agreement_file')) {
            $validatedData['ba_agreement_file'] = FileHelper::uploadWithVersion($request->file('ba_agreement_file'), 'contract_new/amandemen/ba_agreement');
        }
        if ($request->hasFile('result_amandemen_file')) {
            $validatedData['result_amandemen_file'] = FileHelper::uploadWithVersion($request->file('result_amandemen_file'), 'contract_new/amandemen/result_amandemen');
        }
        if ($request->hasFile('principle_permit_file')) {
            $validatedData['principle_permit_file'] = FileHelper::uploadWithVersion($request->file('principle_permit_file'), 'contract_new/amandemen/principle_permit');
        }
        // Ambil harga kontrak sebelum amandemen
        if (ContractNew::find($validatedData['contract_new_id'])) {
            $validatedData['contract_price_before_amandemen'] = ContractNew::find($validatedData['contract_new_id'])->contract_price;
        }
        $amandemenNew = AmandemenNew::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New created successfully.',
            'data' => $amandemenNew,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $amandemenNew = AmandemenNew::find($id);
        if (!$amandemenNew) {
            return response()->json([
                'success' => false,
                'message' => 'Amandemen New not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New retrieved successfully.',
            'data' => $amandemenNew,
        ]);
    }
    
    /**
     * Display the specified resource.
     */
    public function showbByContract(string $id)
    {
        $amandemenNew = AmandemenNew::where('contract_new_id', $id)->get();
        if (!$amandemenNew) {
            return response()->json([
                'success' => false,
                'message' => 'Amandemen New not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New retrieved successfully.',
            'data' => $amandemenNew,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $amandemenNew = AmandemenNew::find($id);
        if (!$amandemenNew) {
            return response()->json([
                'success' => false,
                'message' => 'Amandemen New not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'ba_agreement_file' => 'sometimes|nullable|file|mimes:pdf|max:5120',
            'result_amandemen_file' => 'sometimes|nullable|file|mimes:pdf|max:5120',
            'principle_permit_file' => 'sometimes|nullable|file|mimes:pdf|max:5120',
            'amandemen_price' => 'sometimes|nullable|integer',
            'amandemen_end_date' => 'sometimes|nullable|date',
            'amandemen_penalty' => 'sometimes|nullable|integer',
            'amandemen_termin' => 'sometimes|nullable|string|max:255',
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

            $contract = ContractNew::find($request->input('contract_new_id'));
            $amandemenPrice = $request->input('amandemen_price');
            $principlePermit = $request->file('principle_permit_file');
            if ($contract && $amandemenPrice) {
                $contractPrice = $contract->contract_price;
        
                if ($contractPrice > 0) {
                    $increasePercentage = (($amandemenPrice - $contractPrice) / $contractPrice) * 100;
        
                    if ($increasePercentage >= 10 && !$principlePermit) {
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
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        if ($request->hasFile('ba_agreement_file')) {
            $validatedData['ba_agreement_file'] = FileHelper::uploadWithVersion($request->file('ba_agreement_file'), 'contract_new/amandemen/ba_agreement');
            if ($amandemenNew->ba_agreement_file) {
                FileHelper::deleteFile($amandemenNew->ba_agreement_file, 'contract_new/amandemen/ba_agreement');
            }
        }
        if ($request->hasFile('result_amandemen_file')) {
            $validatedData['result_amandemen_file'] = FileHelper::uploadWithVersion($request->file('result_amandemen_file'), 'contract_new/amandemen/result_amandemen');
            if ($amandemenNew->result_amandemen_file) {
                FileHelper::deleteFile($amandemenNew->result_amandemen_file, 'contract_new/amandemen/result_amandemen');
            }
        }
        if ($request->hasFile('principle_permit_file')) {
            $validatedData['principle_permit_file'] = FileHelper::uploadWithVersion($request->file('principle_permit_file'), 'contract_new/amandemen/principle_permit');
            if ($amandemenNew->principle_permit_file) {
                FileHelper::deleteFile($amandemenNew->principle_permit_file, 'contract_new/amandemen/principle_permit');
            }
        }

        $amandemenNew->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New updated successfully.',
            'data' => $amandemenNew,
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $amandemenNew = AmandemenNew::find($id);
        if (!$amandemenNew) {
            return response()->json([
                'success' => false,
                'message' => 'Amandemen New not found.',
            ], 404);
        }

        // Hapus file terkait
        if ($amandemenNew->ba_agreement_file) {
            FileHelper::deleteFile($amandemenNew->ba_agreement_file, 'contract_new/amandemen/ba_agreement');
        }
        if ($amandemenNew->result_amandemen_file) {
            FileHelper::deleteFile($amandemenNew->result_amandemen_file, 'contract_new/amandemen/result_amandemen');
        }
        if ($amandemenNew->principle_permit_file) {
            FileHelper::deleteFile($amandemenNew->principle_permit_file, 'contract_new/amandemen/principle_permit');
        }

        $amandemenNew->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New deleted successfully.',
        ]);
    }
}
