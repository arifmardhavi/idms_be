<?php

namespace App\Http\Controllers;

use App\Models\Datasheet;
use App\Models\EngineeringData;
use App\Models\GaDrawing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EngineeringDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $engineering_data = EngineeringData::with('tagNumber')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Engineering Data retrieved successfully.',
            'data' => $engineering_data,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'drawing_file' => 'required|file|mimes:pdf,jpg,jpeg,png,svg,webp|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Engineering Data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Simpan engineering data
            $engineeringData = EngineeringData::create([
                'tag_number_id' => $validatedData['tag_number_id']
            ]);

            // Jika berhasil, simpan drawing
            if ($engineeringData) {
                $file = $request->file('drawing_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("engineering_data/ga_drawing/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('engineering_data/ga_drawing'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'GA Drawing failed upload.',
                    ], 422);
                }  
                $validatedData['drawing_file'] = $filename;
                $validatedData['engineering_data_id'] = $engineeringData->id; // Set engineering_data_id
            }
            
            $gaDrawing = GaDrawing::create($validatedData);

            if ($engineeringData && $gaDrawing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Engineering data and drawing uploaded successfully',
                    'engineering_data' => $engineeringData,
                ]);
            }
            

            return response()->json([
                'success' => false,
                'message' => 'Failed to create engineering data',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $engineering_data = EngineeringData::find($id);
        if (!$engineering_data) {
            return response()->json([
                'success' => false,
                'message' => 'Engineering Data not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Engineering Data retrieved successfully.',
            'data' => $engineering_data,
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $engineering_data = EngineeringData::find($id);
        if (!$engineering_data) {
            return response()->json([
                'success' => false,
                'message' => 'Engineering Data not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'sometimes|exists:tag_numbers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Engineering Data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            $engineering_data->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Engineering Data updated successfully.',
                'data' => $engineering_data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Engineering Data updated failed.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $engineering_data = EngineeringData::find($id);
        if (!$engineering_data) {
            return response()->json([
                'success' => false,
                'message' => 'Engineering Data not found.',
            ], 404);
        }
        try {
            
            $engineering_data->delete();
            return response()->json([
                'success' => true,
                'message' => 'Engineering Data deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Engineering Data deleted failed.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
