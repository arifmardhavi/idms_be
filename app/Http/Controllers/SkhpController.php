<?php

namespace App\Http\Controllers;

use App\Models\Skhp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class SkhpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $skhp = Skhp::with(['tag_number', 'plo', 'plo.unit'])->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'skhp retrieved successfully.',
            'data' => $skhp,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:skhps,tag_number_id',
            'no_skhp' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'file_skhp' => 'required|file|mimes:pdf|max:15360',
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
            if ($request->hasFile('file_skhp')) {
                $file = $request->file('file_skhp');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("skhp/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/skhp/
                $path = $file->move(public_path('skhp'), $filename);  
                $validatedData['file_skhp'] = $filename;
            }

            $skhp = Skhp::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'skhp created successfully.',
                'data' => $skhp,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SKHP.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $skhp = Skhp::with(['tag_number', 'plo'])->find($id);

        if (!$skhp) {
            return response()->json([
                'success' => false,
                'message' => 'skhp not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'skhp retrieved successfully.',
            'data' => $skhp,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $skhp = Skhp::find($id);

        if (!$skhp) {
            return response()->json([
                'success' => false,
                'message' => 'skhp not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:skhps,tag_number_id,' . $id,
            'no_skhp' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'file_skhp' => $skhp->file_skhp ? 'nullable|file|mimes:pdf|max:15360' : 'required|file|mimes:pdf|max:15360',
            'file_old_skhp' => 'nullable|file|mimes:pdf|max:15360',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal' . $request->file_skhp,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // input skhp certificate ada 
            if ($request->hasFile('file_skhp')) {
                // skhp certificate sebelumnya ada 
                if ($skhp->file_skhp) {
                    // replace file old skhp menjadi skhp certificate sebelumnya
                    $validatedData['file_old_skhp'] = $skhp->file_skhp;
                    // file old skhp sebelumnya ada 
                    if ($skhp->file_old_skhp) {
                        $path = public_path('skhp/' . $skhp->file_old_skhp);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                }
                // proses simpan file skhp certificate baru
                $file = $request->file('file_skhp');
                // dd($file);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("skhp/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('skhp'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['file_skhp'] = $filename;
            }
            
            $skhp->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'skhp updated successfully.',
                'data' => $skhp,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update skhp.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $skhp = Skhp::find($id);

        if (!$skhp) {
            return response()->json([
                'success' => false,
                'message' => 'skhp not found.',
            ], 404);
        }

        try {
            if ($skhp->file_skhp) {
                $path = public_path('skhp/' . $skhp->file_skhp);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }

            if ($skhp->file_old_skhp) {
                $path = public_path('skhp/' . $skhp->file_old_skhp);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            $skhp->delete();

            return response()->json([
                'success' => true,
                'message' => 'skhp deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete skhp.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function deleteFileskhp(Request $request, $id) {
        $skhp = Skhp::find($id);

        if (!$skhp) {
            return response()->json([
                'success' => false,
                'message' => 'skhp not found.',
            ], 404);
        }

        try {
            // skhp certificate 
            if ($request->file_skhp) {
                $path = public_path('skhp/' . $skhp->file_skhp);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['file_skhp' => null];
                $skhp->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'file skhp deleted successfully.',
                ], 200);
            // file old skhp
            }elseif ($request->file_old_skhp) {
                $path = public_path('skhp/' . $skhp->file_old_skhp);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['file_old_skhp' => null];
                $skhp->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'file old skhp deleted successfully.',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete skhp.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadskhpCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data skhp berdasarkan ID yang dipilih
        $skhps = Skhp::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate skhp
        $zip = new \ZipArchive();
        $zipFilePath = public_path('file_skhp.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($skhps as $skhp) {
            // Cek jika file skhp ada dan file tersebut valid
            if ($skhp->file_skhp) {
                $filePath = public_path('skhp/' . $skhp->file_skhp);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('file_skhp.zip')]);
    }

    public function countskhpDueDays() {
        $today = strtotime(date('Y-m-d')); //mengambil tanggal saat ini
        // Inisialisasi variabel count
        // dd($today);
        $skhpMoreThanSixMonths = 0;
        $skhpLessThanSixMonths = 0;
        $skhpExpired = 0;
        

        // Ambil semua data skhp
        $skhp = skhp::all();

        foreach ($skhp as $item) {
            // Hitung overdue_date untuk skhp
            if (!empty($item->overdue_date)) {
                $overdueTimestamp = strtotime($item->overdue_date);
                $sixMonthsLater = strtotime("+6 months", $today);

                if ($overdueTimestamp >= $sixMonthsLater) {
                    $skhpMoreThanSixMonths++;
                } elseif ($overdueTimestamp >= $today && $overdueTimestamp < $sixMonthsLater) {
                    $skhpLessThanSixMonths++;
                } elseif ($overdueTimestamp < $today) {
                    $skhpExpired++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'skhp status count retrieved successfully.',
            'data' => [
                'skhp_more_than_six_months' => $skhpMoreThanSixMonths,
                'skhp_less_than_six_months' => $skhpLessThanSixMonths,
                'skhp_expired' => $skhpExpired,
            ],
        ], 200);

    }
}
