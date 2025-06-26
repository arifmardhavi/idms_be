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
        $datasheets = Datasheet::orderBy('id', 'desc')->get();
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
            'engineering_data_id' => 'required|exists:engineering_data,id',
            'datasheet_file' => 'required|file|mimes:pdf,jpg,jpeg,png,svg,webp|max:20480',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Datasheet',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        // dd($validatedData);
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
                $path = $file->move(public_path('engineering_data/datasheet'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Datasheet failed upload.',
                    ], 422);
                }  
                $validatedData['datasheet_file'] = $filename;
            }
            $datasheet = Datasheet::create($validatedData);
            if ($datasheet) {
                return response()->json([
                    'success' => true,
                    'message' => 'Datasheet created successfully.',
                    'data' => $datasheet,
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Datasheet.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store Datasheet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $datasheet = Datasheet::find($id);
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
            'engineering_data_id' => 'sometimes|required|exists:engineering_data,id',
            'datasheet_file' => 'sometimes|required|file|mimes:pdf,jpg,jpeg,png,svg,webp|max:20480',
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
                $filename =  $originalName . '_' . 'datasheet_' . $tag_number . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("engineering_data/datasheet/".$filename))) {
                    $version++; // Increment versi
                    $filename =  $originalName . '_' . 'datasheet_' . $tag_number . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru 
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
