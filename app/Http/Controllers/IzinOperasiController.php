<?php

namespace App\Http\Controllers;

use App\Models\IzinOperasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IzinOperasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $izinOperasi = IzinOperasi::with('unit')->orderBy('overdue_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Izin Operasi retrieved successfully.',
            'data' => $izinOperasi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'izin_operasi_certificate' => 'required|file|mimes:pdf|max:25600',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:25600|required_if:rla,1', // required if rla is 1
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
            // Handle izin_operasi_certificate upload
            if ($request->hasFile('izin_operasi_certificate')) {
                $file = $request->file('izin_operasi_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_operasi/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('izin_operasi/certificates'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Izin Operasi Certificate failed upload.',
                    ], 422);
                }

                // Simpan nama file ke data yang divalidasi
                $validatedData['izin_operasi_certificate'] = $filename;

            }

            // Handle file_rla upload (if exists)
            if ($request->hasFile('rla_certificate')) {
                $file = $request->file('rla_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_operasi/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                
                // Store file in public/izin_operasi/rla
                $path = $file->move(public_path('izin_operasi/rla'), $filename);  
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'RLA Certificate failed upload.',
                    ], 422);
                }
                $validatedData['rla_certificate'] = $filename;
            }

            // Create new IzinOperasi record
            $izinOperasi = IzinOperasi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Operasi created successfully.',
                'data' => $izinOperasi,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $izinOperasi = IzinOperasi::with('unit')->find($id);

        if (!$izinOperasi) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Operasi not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Izin Operasi retrieved successfully.',
            'data' => $izinOperasi,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $izinOperasi = IzinOperasi::find($id);

        if (!$izinOperasi) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Operasi not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',    
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date|after_or_equal:issue_date',
            'izin_operasi_certificate' => $izinOperasi->izin_operasi_certificate ? 'nullable|file|mimes:pdf|max:25600' : 'required|file|mimes:pdf|max:25600',
            'izin_operasi_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1',
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue',
            'rla_certificate' => 'nullable|file|mimes:pdf|max:25600',
            'rla_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
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
            // input izin_operasi certificate ada 
            if ($request->hasFile('izin_operasi_certificate')) {
                // izin_operasi certificate sebelumnya ada 
                if ($izinOperasi->izin_operasi_certificate) {
                    // replace izin_operasi old certificate menjadi izin_operasi certificate sebelumnya
                    $validatedData['izin_operasi_old_certificate'] = $izinOperasi->izin_operasi_certificate;
                    // izin_operasi old certificate sebelumnya ada 
                    if ($izinOperasi->izin_operasi_old_certificate) {
                        $path = public_path('izin_operasi/certificates/' . $izinOperasi->izin_operasi_old_certificate);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                }
                // proses simpan file izin_operasi certificate baru
                $file = $request->file('izin_operasi_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_operasi/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('izin_operasi/certificates'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Izin Operasi Certificate failed upload.',
                    ], 422);
                }

                // Simpan nama file ke data yang divalidasi
                $validatedData['izin_operasi_certificate'] = $filename;
            }

            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($izinOperasi->rla_certificate) {
                    // replace rla old certificate yang ada menjadi rla certificate sebelumnya
                    $validatedData['rla_old_certificate'] = $izinOperasi->rla_certificate;
                    // rla old certificate ada 
                    if ($izinOperasi->rla_old_certificate) {
                        $path = public_path('izin_operasi/rla/' . $izinOperasi->rla_old_certificate);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                    // proses simpan file rla certificate baru
                    $file = $request->file('rla_certificate');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                    $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                    $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                    $version = 0; // Awal versi
                    // Format nama file
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                    // Cek apakah file dengan nama ini sudah ada di folder tujuan
                    while (file_exists(public_path("izin_operasi/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('izin_operasi/rla'), $filename);
                    if(!$path){
                        return response()->json([
                            'success' => false,
                            'message' => 'RLA Certificate failed upload.',
                        ], 422);
                    }
    
                    // Simpan nama file ke data yang divalidasi
                    $validatedData['rla_certificate'] = $filename;
                }else{
                    $file = $request->file('rla_certificate');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                    $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                    $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                    $version = 0; // Awal versi
                    // Format nama file
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
    
                    // Cek apakah file dengan nama ini sudah ada di folder tujuan
                    while (file_exists(public_path("izin_operasi/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('izin_operasi/rla'), $filename);
                    if(!$path){
                        return response()->json([
                            'success' => false,
                            'message' => 'RLA Certificate failed upload.',
                        ], 422);
                    }
    
                    // Simpan nama file ke data yang divalidasi
                    $validatedData['rla_certificate'] = $filename;
                }
            }

            $izinOperasi->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Operasi updated successfully.',
                'data' => $izinOperasi,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $izinOperasi = IzinOperasi::find($id);

        if (!$izinOperasi) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Operasi not found.',
            ], 404);
        }

        try {
                        
            $izinOperasi->delete();
            return response()->json([
                'success' => true,
                'message' => 'Izin Operasi deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function deleteFileIzinOperasi(Request $request, $id) {
        $izinOperasi = IzinOperasi::find($id);

        if (!$izinOperasi) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Operasi not found.',
            ], 404);
        }

        try {
            // izin_operasi certificate 
            if ($request->izin_operasi_certificate) {
                $path = public_path('izin_operasi/certificates/' . $izinOperasi->izin_operasi_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['izin_operasi_certificate' => null];
                $izinOperasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Operasi certificate deleted successfully.',
                ], 200);
            // izin_operasi old certificate
            }elseif ($request->izin_operasi_old_certificate) {
                $path = public_path('izin_operasi/certificates/' . $izinOperasi->izin_operasi_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['izin_operasi_old_certificate' => null];
                $izinOperasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Operasi old certificate deleted successfully.',
                ], 200);
            // rla certificate
            }elseif ($request->rla_certificate) {
                $path = public_path('izin_operasi/rla/' . $izinOperasi->rla_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_certificate' => null];
                $izinOperasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA certificate deleted successfully.',
                ], 200);
            // rla old certificate
            }elseif ($request->rla_old_certificate) {
                $path = public_path('izin_operasi/rla/' . $izinOperasi->rla_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_old_certificate' => null];
                $izinOperasi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA old certificate deleted successfully.',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadIzinOperasiCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data Izin Operasi berdasarkan ID yang dipilih
        $izinOperasis = IzinOperasi::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate Izin Operasi
        $zip = new \ZipArchive();
        $zipFilePath = public_path('izin_operasi_certificates.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($izinOperasis as $izinOperasi) {
            // Cek jika file Izin Operasi ada dan file tersebut valid
            if ($izinOperasi->izin_operasi_certificate) {
                $filePath = public_path('izin_operasi/certificates/' . $izinOperasi->izin_operasi_certificate);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('izin_operasi_certificates.zip')]);
    }
    public function countIzinOperasiDueDays() {
        $today = strtotime(date('Y-m-d')); //mengambil tanggal saat ini
        // Inisialisasi variabel count
        // dd($today);
        $izinOperasiMoreThanNineMonths = 0;
        $izinOperasiLessThanNineMonths = 0;
        $izinOperasiExpired = 0;
        $rlaMoreThanNineMonths = 0;
        $rlaLessThanNineMonths = 0;
        $rlaExpired = 0;

        // Ambil semua data Izin Operasi
        $izinOperasi = IzinOperasi::all();

        foreach ($izinOperasi as $item) {
            // Hitung overdue_date untuk Izin Operasi
            if (!empty($item->overdue_date)) {
                $overdueTimestamp = strtotime($item->overdue_date);
                $nineMonthsLater = strtotime("+9 months", $today);

                if ($overdueTimestamp >= $nineMonthsLater) {
                    $izinOperasiMoreThanNineMonths++;
                } elseif ($overdueTimestamp >= $today && $overdueTimestamp < $nineMonthsLater) {
                    $izinOperasiLessThanNineMonths++;
                } elseif ($overdueTimestamp < $today) {
                    $izinOperasiExpired++;
                }
            }

            // Hitung rla_overdue untuk RLA
            if (!empty($item->rla_overdue)) {
                $rlaOverdueTimestamp = strtotime($item->rla_overdue);
                $nineMonthsLater = strtotime("+9 months", $today);

                if ($rlaOverdueTimestamp >= $nineMonthsLater) {
                    $rlaMoreThanNineMonths++;
                } elseif ($rlaOverdueTimestamp >= $today && $rlaOverdueTimestamp < $nineMonthsLater) {
                    $rlaLessThanNineMonths++;
                } elseif ($rlaOverdueTimestamp < $today) {
                    $rlaExpired++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Izin Operasi & RLA status count retrieved successfully.',
            'data' => [
                'izin_operasi_more_than_nine_months' => $izinOperasiMoreThanNineMonths,
                'izin_operasi_less_than_nine_months' => $izinOperasiLessThanNineMonths,
                'izin_operasi_expired' => $izinOperasiExpired,
                'rla_more_than_nine_months' => $rlaMoreThanNineMonths,
                'rla_less_than_nine_months' => $rlaLessThanNineMonths,
                'rla_expired' => $rlaExpired,
            ],
        ], 200);

    }
}