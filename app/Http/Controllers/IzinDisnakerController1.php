<?php

namespace App\Http\Controllers;

use App\Models\IzinDisnaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IzinDisnakerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $izinDisnaker = IzinDisnaker::with('unit')->orderBy('overdue_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Izin Disnaker retrieved successfully.',
            'data' => $izinDisnaker,
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
            'izin_disnaker_certificate' => 'required|file|mimes:pdf|max:25600',
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
            // Handle izin_disnaker_certificate upload
            if ($request->hasFile('izin_disnaker_certificate')) {
                $file = $request->file('izin_disnaker_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_disnaker/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('izin_disnaker/certificates'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Izin Disnaker Certificate failed upload.',
                    ], 422);
                }

                // Simpan nama file ke data yang divalidasi
                $validatedData['izin_disnaker_certificate'] = $filename;

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
                while (file_exists(public_path("izin_disnaker/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                
                // Store file in public/izin_disnaker/rla
                $path = $file->move(public_path('izin_disnaker/rla'), $filename);  
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'RLA Certificate failed upload.',
                    ], 422);
                }
                $validatedData['rla_certificate'] = $filename;
            }

            // Create new IzinDisnaker record
            $izinDisnaker = IzinDisnaker::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Disnaker created successfully.',
                'data' => $izinDisnaker,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $izinDisnaker = IzinDisnaker::with('unit')->find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Izin Disnaker retrieved successfully.',
            'data' => $izinDisnaker,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $izinDisnaker = IzinDisnaker::find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',    
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date|after_or_equal:issue_date',
            'izin_disnaker_certificate' => $izinDisnaker->izin_disnaker_certificate ? 'nullable|file|mimes:pdf|max:25600' : 'required|file|mimes:pdf|max:25600',
            'izin_disnaker_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
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
            // input izin_disnaker certificate ada 
            if ($request->hasFile('izin_disnaker_certificate')) {
                // izin_disnaker certificate sebelumnya ada 
                if ($izinDisnaker->izin_disnaker_certificate) {
                    // replace izin_disnaker old certificate menjadi izin_disnaker certificate sebelumnya
                    $validatedData['izin_disnaker_old_certificate'] = $izinDisnaker->izin_disnaker_certificate;
                    // izin_disnaker old certificate sebelumnya ada 
                    if ($izinDisnaker->izin_disnaker_old_certificate) {
                        $path = public_path('izin_disnaker/certificates/' . $izinDisnaker->izin_disnaker_old_certificate);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                }
                // proses simpan file izin_disnaker certificate baru
                $file = $request->file('izin_disnaker_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_disnaker/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('izin_disnaker/certificates'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Izin Disnaker Certificate failed upload.',
                    ], 422);
                }

                // Simpan nama file ke data yang divalidasi
                $validatedData['izin_disnaker_certificate'] = $filename;
            }

            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($izinDisnaker->rla_certificate) {
                    // replace rla old certificate yang ada menjadi rla certificate sebelumnya
                    $validatedData['rla_old_certificate'] = $izinDisnaker->rla_certificate;
                    // rla old certificate ada 
                    if ($izinDisnaker->rla_old_certificate) {
                        $path = public_path('izin_disnaker/rla/' . $izinDisnaker->rla_old_certificate);
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
                    while (file_exists(public_path("izin_disnaker/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('izin_disnaker/rla'), $filename);
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
                    while (file_exists(public_path("izin_disnaker/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('izin_disnaker/rla'), $filename);
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

            $izinDisnaker->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Disnaker updated successfully.',
                'data' => $izinDisnaker,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $izinDisnaker = IzinDisnaker::find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }

        try {
                        
            $izinDisnaker->delete();
            return response()->json([
                'success' => true,
                'message' => 'Izin Disnaker deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function deleteFileIzinDisnaker(Request $request, $id) {
        $izinDisnaker = IzinDisnaker::find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }

        try {
            // izin_disnaker certificate 
            if ($request->izin_disnaker_certificate) {
                $path = public_path('izin_disnaker/certificates/' . $izinDisnaker->izin_disnaker_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['izin_disnaker_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Disnaker certificate deleted successfully.',
                ], 200);
            // izin_disnaker old certificate
            }elseif ($request->izin_disnaker_old_certificate) {
                $path = public_path('izin_disnaker/certificates/' . $izinDisnaker->izin_disnaker_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['izin_disnaker_old_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Disnaker old certificate deleted successfully.',
                ], 200);
            // rla certificate
            }elseif ($request->rla_certificate) {
                $path = public_path('izin_disnaker/rla/' . $izinDisnaker->rla_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA certificate deleted successfully.',
                ], 200);
            // rla old certificate
            }elseif ($request->rla_old_certificate) {
                $path = public_path('izin_disnaker/rla/' . $izinDisnaker->rla_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_old_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA old certificate deleted successfully.',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadIzinDisnakerCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data Izin Disnaker berdasarkan ID yang dipilih
        $izinDisnakers = IzinDisnaker::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate Izin Disnaker
        $zip = new \ZipArchive();
        $zipFilePath = public_path('izin_disnaker_certificates.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($izinDisnakers as $izinDisnaker) {
            // Cek jika file Izin Disnaker ada dan file tersebut valid
            if ($izinDisnaker->izin_disnaker_certificate) {
                $filePath = public_path('izin_disnaker/certificates/' . $izinDisnaker->izin_disnaker_certificate);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('izin_disnaker_certificates.zip')]);
    }
    public function countIzinDisnakerDueDays() {
        $today = strtotime(date('Y-m-d')); //mengambil tanggal saat ini
        // Inisialisasi variabel count
        // dd($today);
        $izinDisnakerMoreThanNineMonths = 0;
        $izinDisnakerLessThanNineMonths = 0;
        $izinDisnakerExpired = 0;
        $rlaMoreThanNineMonths = 0;
        $rlaLessThanNineMonths = 0;
        $rlaExpired = 0;

        // Ambil semua data Izin Disnaker
        $izinDisnaker = IzinDisnaker::all();

        foreach ($izinDisnaker as $item) {
            // Hitung overdue_date untuk Izin Disnaker
            if (!empty($item->overdue_date)) {
                $overdueTimestamp = strtotime($item->overdue_date);
                $nineMonthsLater = strtotime("+9 months", $today);

                if ($overdueTimestamp >= $nineMonthsLater) {
                    $izinDisnakerMoreThanNineMonths++;
                } elseif ($overdueTimestamp >= $today && $overdueTimestamp < $nineMonthsLater) {
                    $izinDisnakerLessThanNineMonths++;
                } elseif ($overdueTimestamp < $today) {
                    $izinDisnakerExpired++;
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
            'message' => 'Izin Disnaker & RLA status count retrieved successfully.',
            'data' => [
                'izin_disnaker_more_than_nine_months' => $izinDisnakerMoreThanNineMonths,
                'izin_disnaker_less_than_nine_months' => $izinDisnakerLessThanNineMonths,
                'izin_disnaker_expired' => $izinDisnakerExpired,
                'rla_more_than_nine_months' => $rlaMoreThanNineMonths,
                'rla_less_than_nine_months' => $rlaLessThanNineMonths,
                'rla_expired' => $rlaExpired,
            ],
        ], 200);

    }
}
