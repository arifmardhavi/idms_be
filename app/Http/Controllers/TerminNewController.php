<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\TerminResource;
use App\Models\TerminNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TerminNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termin = TerminNew::all();

        return response()->json([
            'success' => true,
            'message' => 'Termin retrieved successfully.',
            'data' => TerminResource::collection($termin),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'termin' => 'required|string|max:100',
            'description' => 'nullable|string|max:200',
            'receipt_nominal' => 'nullable|integer',
            'receipt_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('receipt_file')) {
                $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/lumpsum/receipt');
            }

            $termin = TerminNew::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Termin created successfully.',
                'data' => new TerminResource($termin),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Termin created failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $termin = TerminNew::find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'Termin not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin retrieved successfully.',
            'data' => new TerminResource($termin),
        ]);
    }
    public function showByContract(string $id)
    {
        $termin = TerminNew::where('contract_new_id', $id)->get();

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'Termin not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin retrieved successfully.',
            'data' => TerminResource::collection($termin),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $termin = TerminNew::find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'Termin not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contracts,id',
            'termin' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string|max:200',
            'receipt_nominal' => 'sometimes|nullable|integer',
            'receipt_file' => 'sometimes|nullable|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('receipt_file')) {
                $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/lumpsum/receipt');
                if ($termin->receipt_file) {
                    FileHelper::deleteFile($termin->receipt_file, 'contract_new/lumpsum/receipt');
                }
            }

            $termin->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Termin updated successfully.',
                'data' => new TerminResource($termin->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Termin updated failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $termin = TerminNew::find($id);

        if (!$termin) {
            return response()->json([
                'success' => false,
                'message' => 'Termin not found.',
            ], 404);
        }

        try {
            if ($termin->receipt_file) {
                FileHelper::deleteFile($termin->receipt_file, 'contract_new/lumpsum/receipt');
            }
            $termin->delete();
            return response()->json([
                'success' => true,
                'message' => 'Termin deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Termin deleted failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
