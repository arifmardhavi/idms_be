<?php

namespace App\Http\Controllers;

use App\Models\Coi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coi = Coi::with(['tag_number', 'plo', 'plo.unit'])->orderBy('overdue_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'COI retrieved successfully.',
            'data' => $coi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:cois,tag_number_id',
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'coi_certificate' => 'required|file|mimes:pdf|max:25600',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:25600|required_if:rla,1', // required if rla is 1
            're_engineer' => 'required|in:0,1',
            're_engineer_certificate' => 'nullable|file|mimes:pdf|max:25600|required_if:re_engineer,1',
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
            // Handle coi_certificate upload
            if ($request->hasFile('coi_certificate')) {
                $file = $request->file('coi_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("coi/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/coi/certificates
                $path = $file->move(public_path('coi/certificates'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'COI Certificate failed upload.',
                    ], 422);
                }  
                $validatedData['coi_certificate'] = $filename;
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
                while (file_exists(public_path("coi/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/coi/rla
                $path = $file->move(public_path('coi/rla'), $filename);  
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'RLA Certificate failed upload.',
                    ], 422);
                } 
                $validatedData['rla_certificate'] = $filename; 
            }

            // Handle file_re_engineer upload (if exists)
            if ($request->hasFile('re_engineer_certificate')) {
                $file = $request->file('re_engineer_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("coi/re_engineer/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/coi/re_engineer
                $path = $file->move(public_path('coi/re_engineer'), $filename);  
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Re-Engineering Certificate failed upload.',
                    ], 422);
                } 
                $validatedData['re_engineer_certificate'] = $filename;
                // dd($validatedData); 
            }

            $coi = Coi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'COI created successfully.',
                'data' => $coi,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coi = Coi::with(['tag_number', 'plo'])->find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'COI retrieved successfully.',
            'data' => $coi,
        ], 200);
    }

    public function showByTagNumber(string $tag_number_id)
    {
        $coi = Coi::with(['tag_number', 'plo'])
            ->where('tag_number_id', $tag_number_id)
            ->first(); // atau ->get() kalau ingin banyak

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found for this tag number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'COI retrieved successfully.',
            'data' => $coi,
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:cois,tag_number_id,' . $id,
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'coi_certificate' => $coi->coi_certificate ? 'nullable|file|mimes:pdf|max:25600' : 'required|file|mimes:pdf|max:25600',
            'coi_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:25600',
            'rla_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
            're_engineer' => 'nullable|in:0,1',
            're_engineer_certificate' => 'nullable|file|mimes:pdf|max:25600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal' . $request->coi_certificate,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            // input coi certificate ada 
            if ($request->hasFile('coi_certificate')) {
                // coi certificate sebelumnya ada 
                if ($coi->coi_certificate) {
                    // input coi old certificate tidak ada 
                    if (!$request->hasFile('coi_old_certificate')) {
                        // replace coi old certificate menjadi coi certificate sebelumnya
                        $validatedData['coi_old_certificate'] = $coi->coi_certificate;
                        // coi old certificate sebelumnya ada 
                        if ($coi->coi_old_certificate) {
                            $path = public_path('coi/certificates/' . $coi->coi_old_certificate);
                            // file ada 
                            if (file_exists($path)) {
                                unlink($path); // Hapus file
                            }
                        }
                    } 
                }
                // proses simpan file coi certificate baru
                $file = $request->file('coi_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("coi/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('coi/certificates'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['coi_certificate'] = $filename;
            }
            
            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($coi->rla_certificate) {
                    // input rla old certificate tidak ada 
                    if (!$request->hasFile('rla_old_certificate')) {
                        // replace rla old certificate menjadi coi certificate sebelumnya
                        $validatedData['rla_old_certificate'] = $coi->rla_certificate;
                        // coi old certificate sebelumnya ada 
                        if ($coi->rla_old_certificate) {
                            dd($coi->rla_old_certificate);
                            $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                            // file ada 
                            if (file_exists($path)) {
                                unlink($path); // Hapus file
                            }
                        }
                    } 
                }
                // proses simpan file coi certificate baru
                $file = $request->file('rla_certificate');
                // dd($file);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("coi/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('coi/rla'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['rla_certificate'] = $filename;
            }

            // input rla certificate ada 
            if ($request->hasFile('re_engineer_certificate')) {
                // rla certificate sebelumnya ada 
                if ($coi->re_engineer_certificate) {
                    $path = public_path('coi/re_engineer/' . $coi->re_engineer_certificate);
                    // file ada 
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }

                $file = $request->file('re_engineer_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("coi/re_engineer/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('coi/re_engineer'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Re-Engineering Certificate failed upload.',
                    ], 422);
                }

                // Simpan nama file ke data yang divalidasi
                $validatedData['re_engineer_certificate'] = $filename;
            }

            $coi->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'COI updated successfully.',
                'data' => $coi,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        try {
            if ($coi->coi_certificate) {
                $path = public_path('coi/certificates/' . $coi->coi_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($coi->rla_certificate) {
                $path = public_path('coi/rla/' . $coi->rla_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($coi->rla_old_certificate) {
                $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($coi->re_engineer_certificate) {
                $path = public_path('coi/re_engineer/' . $coi->re_engineer_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            $coi->delete();

            return response()->json([
                'success' => true,
                'message' => 'COI deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    function deleteFileCoi(Request $request, $id) {
        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        try {
            // coi certificate 
            if ($request->coi_certificate) {
                $path = public_path('coi/certificates/' . $coi->coi_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['coi_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'COI certificate deleted successfully.',
                ], 200);
            // coi old certificate
            }elseif ($request->coi_old_certificate) {
                $path = public_path('coi/certificates/' . $coi->coi_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['coi_old_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'COI old certificate deleted successfully.',
                ], 200);
            // rla certificate
            }elseif ($request->rla_certificate) {
                $path = public_path('coi/rla/' . $coi->rla_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA certificate deleted successfully.',
                ], 200);
            // rla old certificate
            }elseif ($request->rla_old_certificate) {
                $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['rla_old_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA old certificate deleted successfully.',
                ], 200);
                // re engineering certificate 
            }elseif ($request->re_engineer_certificate) {
                $path = public_path('coi/re_engineer/' . $coi->re_engineer_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
                $data = ['re_engineer_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Re Engineer certificate deleted successfully.',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadCoiCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data COI berdasarkan ID yang dipilih
        $cois = Coi::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate COI
        $zip = new \ZipArchive();
        $zipFilePath = public_path('coi_certificates.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($cois as $coi) {
            // Cek jika file COI ada dan file tersebut valid
            if ($coi->coi_certificate) {
                $filePath = public_path('coi/certificates/' . $coi->coi_certificate);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('coi_certificates.zip')]);
    }

    public function countCoiDueDays() {
        $today = strtotime(date('Y-m-d')); //mengambil tanggal saat ini
        // Inisialisasi variabel count
        // dd($today);
        $coiMoreThanNineMonths = 0;
        $coiLessThanNineMonths = 0;
        $coiExpired = 0;
        $rlaMoreThanNineMonths = 0;
        $rlaLessThanNineMonths = 0;
        $rlaExpired = 0;

        // Ambil semua data coi
        $coi = Coi::all();

        foreach ($coi as $item) {
            // Hitung overdue_date untuk coi
            if (!empty($item->overdue_date)) {
                $overdueTimestamp = strtotime($item->overdue_date);
                $nineMonthsLater = strtotime("+9 months", $today);

                if ($overdueTimestamp >= $nineMonthsLater) {
                    $coiMoreThanNineMonths++;
                } elseif ($overdueTimestamp >= $today && $overdueTimestamp < $nineMonthsLater) {
                    $coiLessThanNineMonths++;
                } elseif ($overdueTimestamp < $today) {
                    $coiExpired++;
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
            'message' => 'COI & RLA status count retrieved successfully.',
            'data' => [
                'coi_more_than_nine_months' => $coiMoreThanNineMonths,
                'coi_less_than_nine_months' => $coiLessThanNineMonths,
                'coi_expired' => $coiExpired,
                'rla_more_than_nine_months' => $rlaMoreThanNineMonths,
                'rla_less_than_nine_months' => $rlaLessThanNineMonths,
                'rla_expired' => $rlaExpired,
            ],
        ], 200);

    }
    


}
