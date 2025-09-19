<?php

namespace App\Http\Controllers;


use App\Models\Moc;
use App\Models\Tag_number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MocController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $moc = Moc::orderBy('tanggal_terbit', 'desc')->with('unit','category')->get()
        ->map(function ($item) {
            $data = $item->toArray();

            // Parse tag_number_id string into array of IDs
            if (!empty($item->tag_number_id)) {
                $tagNumberIds = explode(',', $item->tag_number_id);
                // Query tag numbers by IDs
                $tagNumbers = Tag_number::whereIn('id', $tagNumberIds)->pluck('tag_number')->toArray();
                $data['tag_numbers'] = $tagNumbers;
            } else {
                $data['tag_numbers'] = [];
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'message' => 'Moc retrieved successfully.',
            'data' => $moc,
        ], 200);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'category_id' => 'required|exists:categories,id',
            'tag_number_id' => 'nullable|string',
            'no_dokumen' => 'required|string|max:255|unique:mocs,no_dokumen',
            'perihal' => 'required|string|max:255',
            'tipe_moc' => 'required',
            'tanggal_terbit' => 'required|date',
            'moc_file' => 'required|file|mimes:pdf|max:30720', // 30 MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('moc_file')) {
                $file = $request->file('moc_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("moc/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('moc'), $filename);
                $validatedData['moc_file'] = $filename;
            }
            $moc = Moc::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Moc created successfully.',
                'data' => $moc,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moc created failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $moc = Moc::with('unit','category')->find($id);

        if (!$moc) {
            return response()->json([
                'success' => false,
                'message' => 'Moc not found.',
            ], 404);
        }

        // Parse tag_number_id string into array of IDs
        if (!empty($moc->tag_number_id)) {
            $tagNumberIds = explode(',', $moc->tag_number_id);
            // Query tag numbers by IDs
            $tagNumbers = Tag_number::whereIn('id', $tagNumberIds)->pluck('tag_number')->toArray();
            $moc->tag_numbers = $tagNumbers;
        } else {
            $moc->tag_numbers = [];
        }

        return response()->json([
            'success' => true,
            'message' => 'Moc retrieved successfully.',
            'data' => $moc,
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $moc = Moc::find($id);
        if (!$moc) {
            return response()->json([
                'success' => false,
                'message' => 'Moc not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'category_id' => 'required|exists:categories,id',
            'tag_number_id' => 'nullable|string',
            'no_dokumen' => 'required|string|max:255|unique:mocs,no_dokumen,' . $moc->id,
            'perihal' => 'required|string|max:255',
            'tipe_moc' => 'required',
            'tanggal_terbit' => 'required|date',
            'moc_file' => 'nullable|file|mimes:pdf|max:30720', // 30 MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('moc_file')) {
                $file = $request->file('moc_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("moc/" .$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Hapus file lama jika ada
                if (file_exists(public_path("moc/" .$moc->moc_file))) {
                    unlink(public_path("moc/" .$moc->moc_file));
                }
                $path = $file->move(public_path('moc'), $filename);
                $validatedData['moc_file'] = $filename;
            }
            $moc->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Moc updated successfully.',
                'data' => $moc,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moc updated failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $moc = Moc::find($id);
        if (!$moc) {
            return response()->json([
                'success' => false,
                'message' => 'Moc not found.',
            ], 404);
        }

        try {
            
            $moc->delete();
            return response()->json([
                'success' => true,
                'message' => 'Moc deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moc deleted failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
