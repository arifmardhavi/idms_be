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
        // $weeks = [];
        // $weekNumber = 1;

        // while ($start->lte($end)) {
        //     $weekStart = $start->copy();
        //     $weekEnd = $weekStart->copy()->addDays(6);
        //     if ($weekEnd->gt($end)) {
        //         $weekEnd = $end->copy();
        //     }

        //     $weeks[] = [
        //         'week' => $weekNumber,
        //         'start' => $weekStart->format('Y-m-d'),
        //         'end' => $weekEnd->format('Y-m-d'),
        //         'label' => "Week {$weekNumber}",
        //         'value' => "{$weekStart->format('Y-m-d')}_{$weekEnd->format('Y-m-d')}",
        //     ];

        //     $weekNumber++;
        //     $start = $weekStart->addDays(7);
        // }
        // $totalWeeks = $weekNumber - 1;

        $contract->total_weeks = $totalWeeks;
        // $contract->weeks = $weeks;
        // $contract->save();

        return response()->json([
            'success' => true,
            'message' => 'contract retrieved successfully.',
            'data' => new ContractDateRangeResource($contract),
        ], 200);
    }
}
