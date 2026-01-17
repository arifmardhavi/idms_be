<?php

namespace App\Http\Controllers;

use App\Http\Resources\SertifikatKalibrasiResource;
use App\Models\SertifikatKalibrasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SertifikatKalibrasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sertifikat_kalibrasi = SertifikatKalibrasi::orderBy('overdue_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'sertifikat kalibrasi retrieved successfully.',
            'data' => SertifikatKalibrasiResource::collection($sertifikat_kalibrasi),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:sertifikat_kalibrasis,tag_number_id',
            'no_sertifikat_kalibrasi' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'file_sertifikat_kalibrasi' => 'required|file|mimes:pdf|max:15360',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('file_sertifikat_kalibrasi')) {
                $file = $request->file('file_sertifikat_kalibrasi');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("sertifikat_kalibrasi/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/sertifikat_kalibrasi/
                $path = $file->move(public_path('sertifikat_kalibrasi'), $filename);  
                $validatedData['file_sertifikat_kalibrasi'] = $filename;
            }

            $sertifikat_kalibrasi = SertifikatKalibrasi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'sertifikat kalibrasi created successfully.',
                'data' => new SertifikatKalibrasiResource($sertifikat_kalibrasi),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sertifikat kalibrasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sertifikat_kalibrasi = SertifikatKalibrasi::find($id);

        if (!$sertifikat_kalibrasi) {
            return response()->json([
                'success' => false,
                'message' => 'sertifikat kalibrasi not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'sertifikat kalibrasi retrieved successfully.',
            'data' => new SertifikatKalibrasiResource($sertifikat_kalibrasi),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sertifikat_kalibrasi = SertifikatKalibrasi::find($id);

        if (!$sertifikat_kalibrasi) {
            return response()->json([
                'success' => false,
                'message' => 'sertifikat_kalibrasi not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:sertifikat_kalibrasis,tag_number_id,' . $id,
            'no_sertifikat_kalibrasi' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'file_sertifikat_kalibrasi' => $sertifikat_kalibrasi->file_sertifikat_kalibrasi ? 'nullable|file|mimes:pdf|max:15360' : 'required|file|mimes:pdf|max:15360',
            'file_old_sertifikat_kalibrasi' => 'nullable|file|mimes:pdf|max:15360',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal' . $request->file_sertifikat_kalibrasi,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // input sertifikat_kalibrasi certificate ada 
            if ($request->hasFile('file_sertifikat_kalibrasi')) {
                // sertifikat_kalibrasi certificate sebelumnya ada 
                if ($sertifikat_kalibrasi->file_sertifikat_kalibrasi) {
                    // replace file old sertifikat_kalibrasi menjadi sertifikat_kalibrasi certificate sebelumnya
                    $validatedData['file_old_sertifikat_kalibrasi'] = $sertifikat_kalibrasi->file_sertifikat_kalibrasi;
                    // file old sertifikat_kalibrasi sebelumnya ada 
                    if ($sertifikat_kalibrasi->file_old_sertifikat_kalibrasi) {
                        $path = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_old_sertifikat_kalibrasi);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                }
                // proses simpan file sertifikat_kalibrasi certificate baru
                $file = $request->file('file_sertifikat_kalibrasi');
                // dd($file);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("sertifikat_kalibrasi/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('sertifikat_kalibrasi'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['file_sertifikat_kalibrasi'] = $filename;
            }
            
            $sertifikat_kalibrasi->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'sertifikat kalibrasi updated successfully.',
                'data' => new SertifikatKalibrasiResource($sertifikat_kalibrasi->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sertifikat kalibrasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sertifikat_kalibrasi = SertifikatKalibrasi::find($id);

        if (!$sertifikat_kalibrasi) {
            return response()->json([
                'success' => false,
                'message' => 'sertifikat kalibrasi not found.',
            ], 404);
        }

        try {
            if ($sertifikat_kalibrasi->file_sertifikat_kalibrasi) {
                $path = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_sertifikat_kalibrasi);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }

            if ($sertifikat_kalibrasi->file_old_sertifikat_kalibrasi) {
                $path = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_old_sertifikat_kalibrasi);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            $sertifikat_kalibrasi->delete();

            return response()->json([
                'success' => true,
                'message' => 'sertifikat kalibrasi deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sertifikat kalibrasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function deleteFileSertifikatKalibrasi(Request $request, $id) {
        $sertifikat_kalibrasi = SertifikatKalibrasi::find($id);

        if (!$sertifikat_kalibrasi) {
            return response()->json([
                'success' => false,
                'message' => 'sertifikat kalibrasi not found.',
            ], 404);
        }

        try {
            // sertifikat_kalibrasi certificate 
            if ($request->file_sertifikat_kalibrasi) {
                $path = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_sertifikat_kalibrasi);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['file_sertifikat_kalibrasi' => null];
                $sertifikat_kalibrasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'file sertifikat kalibrasi deleted successfully.',
                ], 200);
            // file old sertifikat_kalibrasi
            }elseif ($request->file_old_sertifikat_kalibrasi) {
                $path = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_old_sertifikat_kalibrasi);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['file_old_sertifikat_kalibrasi' => null];
                $sertifikat_kalibrasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'file old sertifikat kalibrasi deleted successfully.',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sertifikat kalibrasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadSertifikatKalibrasiCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data sertifikat_kalibrasi berdasarkan ID yang dipilih
        $sertifikat_kalibrasis = SertifikatKalibrasi::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate sertifikat_kalibrasi
        $zip = new \ZipArchive();
        $zipFilePath = public_path('file_sertifikat_kalibrasi.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($sertifikat_kalibrasis as $sertifikat_kalibrasi) {
            // Cek jika file sertifikat_kalibrasi ada dan file tersebut valid
            if ($sertifikat_kalibrasi->file_sertifikat_kalibrasi) {
                $filePath = public_path('sertifikat_kalibrasi/' . $sertifikat_kalibrasi->file_sertifikat_kalibrasi);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('file_sertifikat_kalibrasi.zip')]);
    }

    public function countSertifikatKalibrasiDueDays() {
        $today = strtotime(date('Y-m-d')); //mengambil tanggal saat ini
        // Inisialisasi variabel count
        // dd($today);
        $sertifikat_kalibrasiMoreThanSixMonths = 0;
        $sertifikat_kalibrasiLessThanSixMonths = 0;
        $sertifikat_kalibrasiExpired = 0;
        

        // Ambil semua data sertifikat_kalibrasi
        $sertifikat_kalibrasi = SertifikatKalibrasi::all();

        foreach ($sertifikat_kalibrasi as $item) {
            // Hitung overdue_date untuk sertifikat_kalibrasi
            if (!empty($item->overdue_date)) {
                $overdueTimestamp = strtotime($item->overdue_date);
                $sixMonthsLater = strtotime("+6 months", $today);

                if ($overdueTimestamp >= $sixMonthsLater) {
                    $sertifikat_kalibrasiMoreThanSixMonths++;
                } elseif ($overdueTimestamp >= $today && $overdueTimestamp < $sixMonthsLater) {
                    $sertifikat_kalibrasiLessThanSixMonths++;
                } elseif ($overdueTimestamp < $today) {
                    $sertifikat_kalibrasiExpired++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'sertifikat kalibrasi status count retrieved successfully.',
            'data' => [
                'sertifikat_kalibrasi_more_than_six_months' => $sertifikat_kalibrasiMoreThanSixMonths,
                'sertifikat_kalibrasi_less_than_six_months' => $sertifikat_kalibrasiLessThanSixMonths,
                'sertifikat_kalibrasi_expired' => $sertifikat_kalibrasiExpired,
            ],
        ], 200);

    }
}
