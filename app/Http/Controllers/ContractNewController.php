<?php

namespace App\Http\Controllers;

use App\Exports\DynamicExport;
use App\Helpers\FileHelper;
use App\Http\Resources\ContractDateRangeResource;
use App\Http\Resources\ContractResource;
use App\Models\ContractNew;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
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
     * Display contracts related to the logged-in vendor user.
     */
    public function contractsByUser()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        // Ambil contract yang terkait dengan user vendor
        $contracts = $user->contract_news()->get();

        return response()->json([
            'success' => true,
            'message' => 'Contracts retrieved successfully for user.',
            'data' => $contracts,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->contract_type == 3 || $request->contract_type == 4){
            $validator = Validator::make($request->all(), [
                'no_vendor' => 'required|string',
                'vendor_name' => 'required|string',
                'no_contract' => 'required|string|unique:contract_news,no_contract',
                'contract_name' => 'required|string',
                'pengawas' => 'required|integer',
                'contract_type' => 'required|integer|in:3,4', // 3 = PO Material, 4 = PO Jasa
                'contract_price' => 'required|integer',
                'contract_start_date' => 'required|date',
                'contract_end_date' => 'required|date|after_or_equal:contract_start_date',
                'contract_file' => 'required|file|mimes:pdf|max:51200', // Maksimal 50MB
                'contract_status' => 'nullable|integer|in:0,1', // 0 = Aktif, 1 = Selesai
                'tkdn' => 'nullable|integer', 

            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'no_vendor' => 'required|string',
                'vendor_name' => 'required|string',
                'no_contract' => 'required|string|unique:contract_news,no_contract',
                'contract_name' => 'required|string',
                'contract_type' => 'required|integer|in:1,2', // 1 = Lumpsum, 2 = Unit Price, 3 = PO Material, 4 = PO Jasa
                'contract_date' => 'required|date',
                'contract_price' => 'required|integer',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date',
                'meeting_notes' => 'nullable|file|mimes:pdf|max:5120', // Maksimal 5MB
                'pengawas' => 'required|integer',
                'contract_status' => 'required|integer|in:0,1', // 0 = Aktif, 1 = Selesai
                'tkdn' => 'nullable|integer',
                'contract_file' => 'required|file|mimes:pdf|max:30720', // Maksimal 30MB

            ]);
        }

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
        $contract = ContractNew::where('contract_type', 3)->get();
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
        $contract = ContractNew::where('contract_type', '!=', 3)->get();
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

        if ($contract->contract_status == 3 || $contract->contract_status == 4){
            $validator = Validator::make($request->all(), [
                'no_vendor' => 'sometimes|required|string',
                'vendor_name' => 'sometimes|required|string',
                'no_contract' => 'sometimes|required|string|unique:contract_news,no_contract,' .$id,
                'contract_name' => 'sometimes|required|string',
                'pengawas' => 'sometimes|required|integer',
                'contract_type' => 'sometimes|required|string|in:1,2,3,4', // 1 = Lumpsum, 2 = Unit Price, 3 = PO Material, 4 = PO Jasa
                'contract_price' => 'sometimes|required|integer',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date',
                'contract_file' => 'nullable|file|mimes:pdf|max:30720', // Maksimal 30MB
                'contract_status' => 'sometimes|required|integer|in:0,1', // 0 = Aktif, 1 = Selesai
                'tkdn' => 'nullable|integer',

            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'no_vendor' => 'sometimes|required|string',
                'vendor_name' => 'sometimes|required|string',
                'no_contract' => 'sometimes|required|string|unique:contract_news,no_contract,' .$id,
                'contract_name' => 'sometimes|required|string',
                'pengawas' => 'sometimes|required|integer',
                'contract_type' => 'sometimes|required|string|in:1,2,3,4', // 1 = Lumpsum, 2 = Unit Price, 3 = PO Material, 4 = PO Jasa
                'contract_date' => 'nullable|date',
                'contract_price' => 'sometimes|required|integer',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date',
                'contract_file' => 'nullable|file|mimes:pdf|max:30720', // Maksimal 30MB
                'meeting_notes' => 'nullable|file|mimes:pdf|max:5120', // Maksimal 5MB
                'contract_status' => 'sometimes|required|integer|in:0,1', // 0 = Aktif, 1 = Selesai
                'tkdn' => 'nullable|integer',

            ]);
        }


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
                FileHelper::deleteFile($contract->meeting_notes, 'contract_new/meeting_notes');
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

    public function updateTkdn(Request $request, string $id )
    {
        $contract = ContractNew::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'contract not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'tkdn' => 'sometimes|nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for tkdn',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $contract->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'TKDN updated successfully.',
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
            $fileName = $contract->contract_file;

            activity()->log('download', 'ContractNew', [
                'recordId'    => $contract->id,
                'recordLabel' => $contract->no_contract ?? $contract->id,
                'metadata'    => ['file' => $contract->contract_file],
            ]);

            return FileHelper::downloadFile('contract_new', $contract->contract_file);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download contract file.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    public function monitoringContract()
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
                continue;
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

    /**
     * Export contract data (ContractNew) to Excel.
     * Body: {"ids": [1,2,3]} -> export selected only; empty/absent -> export all.
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'sometimes|nullable|array',
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = ContractNew::query();

        $ids = $request->input('ids');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $contracts = $query->get();

        $data = [];
        foreach ($contracts as $contract) {
            $durasi = $contract->durasi_mpp;
            $deviasi = $contract->deviation_progress;
            $sisaNilai = $contract->sisa_nilai;

            $sisaHari = $durasi['sisa'] ?? null;
            if ($contract->contract_status == 0) {
                $mppColor = 'blue';
            } elseif ($sisaHari !== null && $sisaHari <= 0) {
                $mppColor = 'red';
            } elseif ($sisaHari !== null && $sisaHari <= 28) {
                $mppColor = 'yellow';
            } else {
                $mppColor = 'green';
            }

            $data[] = [
                'no_vendor' => $contract->no_vendor ?: '-',
                'vendor_name' => $contract->vendor_name ?: '-',
                'no_contract' => $contract->no_contract ?: '-',
                'contract_name' => $contract->contract_name ?: '-',
                'contract_type' => $this->contractTypeLabel($contract->contract_type),
                'pengawas' => $this->pengawasLabel($contract->pengawas),
                'contract_price' => $contract->contract_price !== null ? $contract->contract_price : '-',
                'contract_date' => $contract->contract_date ? $contract->contract_date->format('d-m-Y') : '-',
                'contract_start_date' => $contract->contract_start_date ? $contract->contract_start_date->format('d-m-Y') : '-',
                'contract_end_date' => $contract->contract_end_date ? $contract->contract_end_date->format('d-m-Y') : '-',
                'sisa_mpp' => $sisaHari !== null ? $sisaHari : '-',
                'deviasi_progress' => ($deviasi['deviation'] ?? null) !== null ? $deviasi['deviation'] : '-',
                'current_status' => $contract->current_status ?: '-',
                'sisa_nilai' => ($sisaNilai['sisa'] ?? null) !== null ? $sisaNilai['sisa'] : '-',
                'status' => $this->contractStatusLabel($contract->contract_status),
                '_mpp_color' => $mppColor,
                '_dev_color' => $deviasi['color'] ?? 'green',
                '_sisa_color' => $sisaNilai['color'] ?? 'green',
            ];
        }

        $columns = [
            'no_vendor' => 'No Vendor',
            'vendor_name' => 'Nama Vendor',
            'no_contract' => 'No SP/PO',
            'contract_name' => 'Nama Contract',
            'contract_type' => 'Tipe',
            'pengawas' => 'Pengawas',
            'contract_price' => 'Price',
            'contract_date' => 'Contract Date',
            'contract_start_date' => 'Contract Start',
            'contract_end_date' => 'Contract End',
            'sisa_mpp' => 'Sisa MPP',
            'deviasi_progress' => 'Deviasi Progress',
            'current_status' => 'Current Status',
            'sisa_nilai' => 'Sisa Nilai Kontrak',
            'status' => 'Status',
        ];

        $options = [
            'headerStyle' => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFD9E1F2']],
            ],
            'border' => ['onlyHeader' => true, 'onlyData' => true],
            'autoWidth' => true,
            'autoWidthMax' => 50,
            'freezeHeader' => true,
            'filter' => true,
            'numberFormat' => [
                'contract_price' => '#,##0',
                'sisa_nilai' => '#,##0',
                'deviasi_progress' => '#,##0.00',
            ],
            'cellConditional' => $this->contractColorRules(),
        ];

        activity()->log('export', 'ContractNew');

        return Excel::download(
            new DynamicExport($data, $columns, $options),
            'Contract_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function contractTypeLabel($value): string
    {
        return match ((int) $value) {
            1 => 'Lumpsum',
            2 => 'Unit Price',
            3 => 'PO Material',
            4 => 'PO Jasa',
            default => '-',
        };
    }

    private function pengawasLabel($value): string
    {
        return match ((int) $value) {
            0 => 'Inspection',
            1 => 'Maintenance Execution',
            2 => 'Procurement',
            default => '-',
        };
    }

    private function contractStatusLabel($value): string
    {
        return match ((int) $value) {
            0 => 'Selesai',
            1 => 'Aktif',
            default => '-',
        };
    }

    private function contractColorRules(): array
    {
        $styles = [
            'blue' => [
                'font' => ['color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF1E88E5']],
            ],
            'red' => [
                'font' => ['color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE53935']],
            ],
            'yellow' => [
                'font' => ['color' => ['argb' => 'FF000000']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFFFEB3B']],
            ],
            'green' => [
                'font' => ['color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF43A047']],
            ],
            'black' => [
                'font' => ['color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FF000000']],
            ],
        ];

        $rules = [];
        foreach ($styles as $color => $style) {
            $rules[] = [
                'column' => 'sisa_mpp',
                'condition' => fn ($row) => ($row['_mpp_color'] ?? null) === $color,
                'style' => $style,
            ];
            $rules[] = [
                'column' => 'deviasi_progress',
                'condition' => fn ($row) => ($row['_dev_color'] ?? null) === $color,
                'style' => $style,
            ];
            $rules[] = [
                'column' => 'sisa_nilai',
                'condition' => fn ($row) => ($row['_sisa_color'] ?? null) === $color,
                'style' => $style,
            ];
        }

        return $rules;
    }
}
