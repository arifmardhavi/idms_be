<?php

namespace App\Http\Controllers;

use App\Models\Datasheet;
use App\Models\EngineeringData;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DatasheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datasheets = Datasheet::with(['engineeringData.tagNumber'])->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Datasheets retrieved successfully.',
            'data' => $datasheets,
        ], 200);
    }

    public function showWithEngineeringDataId($id)
    {
        $datasheet = Datasheet::with(['engineeringData', 'engineeringData.tagNumber'])
            ->where('engineering_data_id', $id)
            ->get();

        if ($datasheet->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Datasheet not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Datasheet retrieved successfully.',
            'data' => $datasheet,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_dokumen' => 'nullable|string|max:255',
            'no_dokumen' => 'nullable|string|max:255|unique:datasheets,no_dokumen', // Validasi no_dokumen unik
            'engineering_data_id' => 'required|exists:engineering_data,id',
            'date_datasheet' => 'nullable|date',
            'datasheet_file' => 'required|array',
            'datasheet_file.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Datasheet',
                'errors' => $validator->errors(),
            ], 422);
        }
        if (count($request->file('datasheet_file')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            $result = [];
            $failedFiles = [];

            foreach ($request->file('datasheet_file') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $dateNow = date('dmY');
                    $tag_number = EngineeringData::find($request->engineering_data_id)->tagNumber->tag_number; // Ambil tag number dari engineering data
                    $cleanTagNumber = str_replace('/00', '', $tag_number); // hasil: '1-C-25'
                    $version = 0;

                    $filename = $nameOnly . '_' . 'datasheet_' . $cleanTagNumber . '_' . $dateNow . '_' . $version . '.' . $extension;
                    while (file_exists(public_path("engineering_data/datasheet/" . $filename))) {
                        $version++;
                        $filename = $nameOnly . '_' . 'datasheet_' . $cleanTagNumber . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }

                    $path = $file->move(public_path('engineering_data/datasheet'), $filename);
                    if (!$path) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => 'Gagal memindahkan file ke direktori tujuan.'
                        ];
                        continue;
                    }

                    $datasheet = Datasheet::create([
                        'engineering_data_id' => $request->engineering_data_id,
                        'no_dokumen' => $request->no_dokumen,
                        'date_datasheet' => $request->date_datasheet,
                        'datasheet_file' => $filename,
                    ]);

                    $result[] = $datasheet;

                } catch (\Throwable $fileError) {
                    $failedFiles[] = [
                        'name' => $originalName,
                        'error' => $fileError->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload selesai.',
                'data' => $result,
                'failed_files' => $failedFiles,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload datasheet.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $datasheet = Datasheet::with(['engineeringData.tagNumber'])->find($id);
        if (!$datasheet) {
            return response()->json([
                'success' => false,
                'message' => 'Datasheet not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Datasheet retrieved successfully.',
            'data' => $datasheet,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $datasheet = Datasheet::find($id);

        if (!$datasheet) {
            return response()->json([
                'success' => false,
                'message' => 'Datasheet not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'nama_dokumen' => 'nullable|string|max:255',
            'no_dokumen' => 'nullable|string|max:255',
            'engineering_data_id' => 'sometimes|required|exists:engineering_data,id',
            'datasheet_file' => 'sometimes|required|file|mimes:pdf,jpg,jpeg,png,svg|max:204800',
            'date_datasheet' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Datasheet',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('datasheet_file')) {
                $file = $request->file('datasheet_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $tag_number = EngineeringData::find($validatedData['engineering_data_id'])->tagNumber->tag_number; // Ambil tag number dari engineering data
                $cleanTagNumber = str_replace('/00', '', $tag_number); // hasil: '1-C-25'
                $filename =  $originalName . '_' . 'datasheet_' . $cleanTagNumber . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                // $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("engineering_data/datasheet/".$filename))) {
                    $version++; // Increment versi
                    $filename =  $originalName . '_' . 'datasheet_' . $cleanTagNumber . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru 
                }
                if ($datasheet->datasheet_file) {
                    unlink(public_path("engineering_data/datasheet/".$datasheet->datasheet_file)); // Hapus file lama jika ada
                }
                $path = $file->move(public_path('engineering_data/datasheet'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Datasheet failed upload.',
                    ], 422);
                }  
                if($datasheet->datasheet_file){
                    $datasheetBefore = public_path('engineering_data/datasheet/' . $datasheet->datasheet_file);
                    if (file_exists($datasheetBefore)) {
                        unlink($datasheetBefore); // Hapus file
                    }
                }
                $validatedData['datasheet_file'] = $filename;
            } 
            
            if ($datasheet->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Datasheet updated successfully.',
                    'data' => $datasheet,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Datasheet.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Datasheet',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $datasheet = Datasheet::find($id);
        if (!$datasheet) {
            return response()->json([
                'success' => false,
                'message' => 'Datasheet not found.',
            ], 404);
        }

        try {
            if ($datasheet->datasheet_file) {
                // Hapus file dari direktori
                $path = public_path('engineering_data/datasheet/' . $datasheet->datasheet_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if($datasheet->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'Datasheet deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete Datasheet.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Datasheet.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
                
}
