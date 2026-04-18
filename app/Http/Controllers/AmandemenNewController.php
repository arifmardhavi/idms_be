<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\AmandemenResource;
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
            'data' => AmandemenResource::collection($amandemenNews),
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
        ]);

        $contract = ContractNew::find($request->input('contract_new_id'));

        $validator->after(function ($validator) use ($request, $contract) {
            $fields = [
                $request->input('amandemen_price'),
                $request->input('amandemen_end_date'),
                $request->input('amandemen_penalty'),
            ];

            $filled = array_filter($fields, fn($value) => !is_null($value) && $value !== '');

            if (count($filled) === 0) {
                $validator->errors()->add(
                    'amandemen_group',
                    'Minimal salah satu dari perubahan nilai, perubahan waktu, denda, atau perubahan termin harus diisi.'
                );
            }

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
        if ($contract) {
            $validatedData['contract_price_before_amandemen'] = $contract->contract_price;
            $validatedData['contract_end_date_before_amandemen'] = $contract->contract_end_date;
        }
        $amandemenNew = AmandemenNew::create($validatedData);

        if ($amandemenNew) {
            // Update kontrak jika amandemen berhasil dibuat
            if ($request->input('amandemen_price') && $request->input('amandemen_price') != null) {
                $contract->contract_price = $request->input('amandemen_price');
            }
            if ($request->input('amandemen_end_date') && $request->input('amandemen_end_date') != null) {
                $contract->contract_end_date = $request->input('amandemen_end_date');
            }
            $contract->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New created successfully.',
            'data' => new AmandemenResource($amandemenNew),
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
            'data' => new AmandemenResource($amandemenNew),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function showByContract(string $id)
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
            'data' => AmandemenResource::collection($amandemenNew),
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
        ]);
        $contract = ContractNew::find($request->input('contract_new_id'));
        $validator->after(function ($validator) use ($request, $contract) {
            $fields = [
                $request->input('amandemen_price'),
                $request->input('amandemen_end_date'),
                $request->input('amandemen_penalty'),
            ];

            $filled = array_filter($fields, fn($value) => !is_null($value) && $value !== '');

            if (count($filled) === 0) {
                $validator->errors()->add(
                    'amandemen_group',
                    'Minimal salah satu dari perubahan nilai, perubahan waktu, denda, atau perubahan termin harus diisi.'
                );
            }

            $amandemenPrice = $request->input('amandemen_price');
            $principlePermit = $request->file('principle_permit_file');
            $lastPriceAmandemen = $contract->lastPriceAmandemen;
            $lastDateAmandemen = $contract->lastDateAmandemen;
            if ($contract && $amandemenPrice) {

                if ($lastPriceAmandemen && $lastPriceAmandemen->contract_price_before_amandemen > 0) {
                    $increasePercentage = (($amandemenPrice - $lastPriceAmandemen->contract_price_before_amandemen) / $lastPriceAmandemen->contract_price_before_amandemen) * 100;

                    if ($increasePercentage >= 10 && !$principlePermit) {
                        $validator->errors()->add(
                            'principle_permit_file',
                            'File principle permit wajib diunggah karena perubahan nilai amandemen naik lebih dari 10% dari nilai kontrak/amandemen sebelumnya.'
                        );
                    }
                }
            }
            if ($contract) {
                $amandemenEndDate = $request->input('amandemen_end_date');

                if (!is_null($amandemenPrice) && $amandemenPrice <= $lastPriceAmandemen->contract_price_before_amandemen) {
                    $validator->errors()->add(
                        'amandemen_price',
                        'Perubahan Nilai Amandemen tidak boleh lebih kecil dari nilai kontrak/amandemen sebelumnya yaitu ' . number_format($lastPriceAmandemen->contract_price_before_amandemen, 0, ',', '.')
                    );
                }

                if (!is_null($amandemenEndDate) && $amandemenEndDate <= $lastDateAmandemen->contract_end_date_before_amandemen) {
                    $formattedDate = Carbon::parse($lastDateAmandemen->contract_end_date_before_amandemen)->translatedFormat('d F Y'); // Contoh: 11 November 2026
                    $validator->errors()->add(
                        'amandemen_end_date',
                        'Perubahan waktu Amandemen tidak boleh lebih awal dari tanggal akhir kontrak/amandemen sebelumnya yaitu ' . $formattedDate
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

        $amandemenUpdated = $amandemenNew->update($validatedData);

        if ($amandemenUpdated) {
            // Update kontrak jika amandemen berhasil dibuat
            if ($request->input('amandemen_price') && $request->input('amandemen_price') != null) {
                $contract->contract_price = $request->input('amandemen_price');
            }
            if ($request->input('amandemen_end_date') && $request->input('amandemen_end_date') != null) {
                $contract->contract_end_date = $request->input('amandemen_end_date');
            }
            $contract->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New updated successfully.',
            'data' => new AmandemenResource($amandemenNew->fresh()),
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

        $contract = ContractNew::find($amandemenNew->contract_new_id);
        $lastPriceAmandemen = $contract->lastPriceAmandemen;
        $lastDateAmandemen = $contract->lastDateAmandemen;

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

        if ($amandemenNew) {
            // Update kontrak jika amandemen berhasil dibuat
            if ($lastPriceAmandemen->contract_price_before_amandemen && $lastPriceAmandemen->contract_price_before_amandemen != null) {
                $contract->contract_price = $lastPriceAmandemen->contract_price_before_amandemen;
            }
            if ($lastDateAmandemen->contract_end_date_before_amandemen && $lastDateAmandemen->contract_end_date_before_amandemen != null) {
                $contract->contract_end_date = $lastDateAmandemen->contract_end_date_before_amandemen;
            }
            $contract->save();
        }

        $amandemenNew->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amandemen New deleted successfully.',
        ]);
    }

    public function downloadAmandemenFile(string $id)
    {
        $typeFile = request()->get('file', 'ba_agreement_file'); // Default ke 'amandemen_file' jika tidak ada parameter 'file'

        $amandemenNew = AmandemenNew::find($id);

        if (!$amandemenNew) {
            return response()->json([
                'success' => false,
                'message' => 'Amandemen New not found.',
            ], 404);
        }

        // mapping file field + folder
        $fileMap = [
            'ba_agreement_file' => [
                'field' => 'ba_agreement_file',
                'path' => 'contract_new/amandemen/ba_agreement'
            ],
            'result_amandemen_file' => [
                'field' => 'result_amandemen_file',
                'path' => 'contract_new/amandemen/result_amandemen'
            ],
            'principle_permit_file' => [
                'field' => 'principle_permit_file',
                'path' => 'contract_new/amandemen/principle_permit'
            ],
        ];

        // validasi type
        if (!isset($fileMap[$typeFile])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type.',
            ], 400);
        }

        $file = $amandemenNew->{$fileMap[$typeFile]['field']};
        $destinationPath = $fileMap[$typeFile]['path'];

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        return FileHelper::downloadFile($destinationPath, $file);
    }
}
