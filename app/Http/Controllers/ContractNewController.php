<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\ContractDateRangeResource;
use App\Http\Resources\ContractResource;
use App\Models\ContractNew;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class ContractNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = ContractNew::all();
        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => ContractResource::collection($contracts),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_vendor' => 'required|string',
            'vendor_name' => 'required|string',
            'no_contract' => 'required|string|unique:contract_news,no_contract',
            'contract_name' => 'required|string',
            'contract_type' => 'required|integer|in:1,2,3', // 1 = Lumpsum, 2 = Unit Price, 3 = PO Material
            'contract_date' => 'nullable|date',
            'contract_price' => 'required|integer',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'meeting_notes' => 'nullable|file|mimes:pdf|max:5120', // Maksimal 5MB
            'pengawas' => 'required|integer',
            'contract_status' => 'nullable|integer|in:0,1', // 0 = Aktif, 1 = Selesai
            'contract_file' => 'required|file|mimes:pdf|max:30720', // Maksimal 30MB

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        // uppercase fields
        $validatedData['vendor_name'] = strtoupper($request->vendor_name);
        $validatedData['contract_name'] = strtoupper($request->contract_name);

        try {
             
            $validatedData['contract_file'] = FileHelper::uploadWithVersion($request->file('contract_file'), 'contract_new');

            if($request->hasFile('meeting_notes')){
                $validatedData['meeting_notes'] = FileHelper::uploadWithVersion($request->file('meeting_notes'), 'contract_new/meeting_notes');
            }

            $contract = ContractNew::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'contract created successfully.',
                'data' => new ContractResource($contract),
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
        $contract = ContractNew::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => new ContractResource($contract),
        ], 200);    
    }

    /**
     * Display the specified by PO resource.
     */
    public function showByPoMaterialType()
    {
        $contract = ContractNew::where('contract_type', 3)->where('contract_status', 1)->get();
        if ($contract->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => ContractResource::collection($contract),
        ], 200);
    }

    /**
     * Display the specified by Un PO resource.
     */
    public function showByUnPoMaterialType()
    {
        $contract = ContractNew::where('contract_type', '!=', 3)->where('contract_status', 1)->get();
        if ($contract->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => ContractResource::collection($contract),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract = ContractNew::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_vendor' => 'sometimes|required|string',
            'vendor_name' => 'sometimes|required|string',
            'no_contract' => 'sometimes|required|string|unique:contract_news,no_contract,' .$id,
            'contract_name' => 'sometimes|required|string',
            'contract_type' => 'sometimes|required|string|in:1,2,3', // 1 = Lumpsum, 2 = Unit Price, 3 = PO Material
            'contract_date' => 'nullable|date',
            'contract_price' => 'sometimes|required|integer',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'meeting_notes' => 'nullable|file|mimes:pdf|max:5120', // Maksimal 5MB
            'pengawas' => 'sometimes|required|integer',
            'contract_status' => 'sometimes|required|integer|in:0,1', // 0 = Aktif, 1 = Selesai
            'contract_file' => 'nullable|file|mimes:pdf|max:30720', // Maksimal 30MB

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->contract_type == 3){
            $validatedData['contract_date'] = null; 
        }
        
        // uppercase fields
        $validatedData['vendor_name'] = strtoupper($request->vendor_name);
        $validatedData['contract_name'] = strtoupper($request->contract_name);

        try {
            if ($request->hasFile('contract_file')) {
                $validatedData['contract_file'] = FileHelper::uploadWithVersion($request->file('contract_file'), 'contract_new');
                if ($contract->contract_file) {
                    FileHelper::deleteFile($contract->contract_file, 'contract_new');
                }
            }

            if ($request->hasFile('meeting_notes')) {
                $validatedData['meeting_notes'] = FileHelper::uploadWithVersion($request->file('meeting_notes'), 'contract_new/meeting_notes');
                if ($contract->meeting_notes) {
                    FileHelper::deleteFile($contract->meeting_notes, 'contract_new/meeting_notes');
                }
            }

            $contract->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully.',
                'data' => new ContractResource($contract->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract = ContractNew::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        try {
            if ($contract->contract_file) {
                FileHelper::deleteFile($contract->contract_file, 'contract_new');
            }

            if ($contract->meeting_notes) {
                FileHelper::deleteFile($contract->contract_file, 'contract_new/meeting_notes');
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

    public function updateCurrentStatus(Request $request, string $id )
    {
        $contract = ContractNew::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'current_status' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for current status',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $contract->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Current status updated successfully.',
            'data' => new ContractResource($contract->fresh()),
        ], 200);
    }

    public function contractLumpsumProgress(string $id)
    {
        $contract = ContractNew::select('contract_start_date', 'contract_end_date')->find($id);
        
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        $start = Carbon::parse($contract->contract_start_date);
        $end = Carbon::parse($contract->contract_end_date);
        if (!$start->isFriday()) {
            $start = $start->next(Carbon::FRIDAY);
        }

        $totalWeeks = $start->diffInWeeks($end) + 1;

        $contract->total_weeks = $totalWeeks;

        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => new ContractDateRangeResource($contract),
        ], 200);
    }

    public function downloadContractFile(string $id)
    {
        $contract = ContractNew::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }

        if (!$contract->contract_file) {
            return response()->json([
                'success' => false,
                'message' => 'contract file not found.',
            ], 404);
        }

        try {
            $filePath = public_path('contract_new/' . $contract->contract_file);
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist in storage.',
                ], 404);
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download contract file.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    public function monitoringContract(string $id)
    {
        $today = Carbon::today();

        $contracts = ContractNew::all();
        $count = $contracts->count();

        // Hitung total berdasarkan status & tipe
        $blue = ContractNew::where('contract_status', 0)->count(); // kontrak selesai

        $green = 0;
        $yellow = 0;
        $red = 0;
        $active = 0;
        $selesai = 0;
        $lumpsum = 0;
        $unit_price = 0;
        $po_material = 0;

        // Tambahan untuk kontrak aktif per tipe
        $activeLumpsum = 0;
        $activeUnitPrice = 0;
        $activePoMaterial = 0;

        foreach ($contracts as $contract) {
            // Hitung berdasarkan tipe kontrak
            if ($contract->contract_type == 1) {
                $lumpsum++;
            } elseif ($contract->contract_type == 2) {
                $unit_price++;
            } elseif ($contract->contract_type == 3) {
                $po_material++;
            }

            // Jika kontrak aktif (status = 1)
            if ($contract->contract_status == 1) {
                $active++;
                if ($contract->contract_type == 1) {
                    $activeLumpsum++;
                } elseif ($contract->contract_type == 2) {
                    $activeUnitPrice++;
                } elseif ($contract->contract_type == 3) {
                    $activePoMaterial++;
                }
            }

            // Lewati kontrak yang selesai
            if ($contract->contract_status == 0) {
                $selesai++;
            }

            // Hitung warna durasi MPP global
            $endDate = Carbon::parse($contract->contract_end_date);
            $weeksDiff = $today->diffInWeeks($endDate, false);

            if ($weeksDiff >= 4) {
                $green++;
            } elseif ($weeksDiff > 0 && $weeksDiff < 4) {
                $yellow++;
            } else {
                $red++;
            }
        }

        // Monitoring progress pekerjaan
        $statusCounts = [
            'blue' => 0,   // Kontrak selesai
            'green' => 0,  // Deviasi 0%
            'yellow' => 0, // Deviasi ≤ 20%
            'red' => 0,    // Deviasi > 20%
            'black' => 0,  // Belum upload amandemen
        ];

        foreach ($contracts as $contract) {
            $color = $contract->monitoring_progress['color'] ?? null;
            if (isset($statusCounts[$color])) {
                $statusCounts[$color]++;
            }
        }

        // Bagian Tambahan: Data untuk 3 Pie Chart
        $activeContracts = $contracts->where('contract_status', 1);

        // Durasi MPP untuk Lumpsum & Unit Price
        $durasiLumpsumUnit = ['green' => 0, 'yellow' => 0, 'red' => 0];
        // Durasi MPP untuk PO Material
        $durasiPoMaterial = ['green' => 0, 'yellow' => 0, 'red' => 0];
        // Sisa Nilai Kontrak (Lumpsum & Unit Price)
        $sisaNilaiLumpsumUnit = ['green' => 0, 'yellow' => 0, 'red' => 0];

        foreach ($activeContracts as $contract) {
            $durasi = $contract->durasi_mpp['color'];
            $sisaNilai = $contract->sisa_nilai['color'];

            // Durasi MPP (Lumpsum + Unit Price)
            if (in_array($contract->contract_type, [1, 2]) && isset($durasiLumpsumUnit[$durasi])) {
                $durasiLumpsumUnit[$durasi]++;
            }

            // Durasi MPP (PO Material)
            if ($contract->contract_type == 3 && isset($durasiPoMaterial[$durasi])) {
                $durasiPoMaterial[$durasi]++;
            }

            // Sisa Nilai (Lumpsum + Unit Price)
            if (in_array($contract->contract_type, [1, 2]) && isset($sisaNilaiLumpsumUnit[$sisaNilai])) {
                $sisaNilaiLumpsumUnit[$sisaNilai]++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Monitoring data berhasil diambil.',
            'data' => [
                'total_contract' => $count,
                'total_active_contract' => $active,
                'total_selesai_contract' => $selesai,
                'total_lumpsum_contract' => $lumpsum,
                'total_unit_price_contract' => $unit_price,
                'total_po_material_contract' => $po_material,
                // Tambahan field baru
                'active_lumpsum_contract' => $activeLumpsum,
                'active_unit_price_contract' => $activeUnitPrice,
                'active_po_material_contract' => $activePoMaterial,
                'monitoring_durasi_mpp' => [
                    'blue' => $blue,
                    'green' => $green,
                    'yellow' => $yellow,
                    'red' => $red,
                ],
                'monitoring_progress_pekerjaan' => $statusCounts,
                'monitoring_durasi_mpp_lumpsum_unit' => $durasiLumpsumUnit,
                'monitoring_durasi_mpp_po_material' => $durasiPoMaterial,
                'monitoring_sisa_nilai_lumpsum_unit' => $sisaNilaiLumpsumUnit,
            ]
        ], 200);
    }
}
