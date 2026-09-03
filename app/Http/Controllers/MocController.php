<?php

namespace App\Http\Controllers;


use App\Helpers\FileHelper;
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
                $validatedData['moc_file'] = FileHelper::uploadWithVersion($request->file('moc_file'), 'moc');
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
                if ($moc->moc_file) {
                    FileHelper::deleteFile($moc->moc_file, 'moc');
                }
                $validatedData['moc_file'] = FileHelper::uploadWithVersion($request->file('moc_file'), 'moc');
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
            if ($moc->moc_file) {
                FileHelper::deleteFile($moc->moc_file, 'moc');
            }
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
