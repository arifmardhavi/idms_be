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
        $coi = Coi::with(['tag_number', 'plo', 'plo.unit'])->get();

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
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'coi_certificate' => 'required|file|mimes:pdf|max:15360',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:15360|required_if:rla,1', // required if rla is 1
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
                $validatedData['rla_certificate'] = $filename; 
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
            'tag_number_id' => 'required|exists:tag_numbers,id',
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'coi_certificate' => $coi->coi_certificate ? 'nullable|file|mimes:pdf|max:15360' : 'required|file|mimes:pdf|max:15360',
            'coi_old_certificate' => 'nullable|file|mimes:pdf|max:15360',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:15360',
            'rla_old_certificate' => 'nullable|file|mimes:pdf|max:15360',
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
            // input coi old certificate ada 
            if ($request->hasFile('coi_old_certificate')) {
                // coi old certificate sebelumnya ada 
                if ($coi->coi_old_certificate) {
                    $path = public_path('coi/certificates/' . $coi->coi_old_certificate);
                    // file ada 
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }
                // proses simpan file coi old certificate baru
                $file = $request->file('coi_old_certificate');
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
                $validatedData['coi_old_certificate'] = $filename;
            }

            // jika rla di update jadi 0

            // if($request->rla == 0){
                //     $validatedData['rla_issue'] = null;
                //     $validatedData['rla_overdue'] = null;
                //     if ($coi->rla_certificate) {
                //         $path = public_path('coi/rla/' . $coi->rla_certificate);
                //         if (file_exists($path)) {
                //             unlink($path); // Hapus file
                //         }
                //         $validatedData['rla_certificate'] = null;
                //     }
                //     if ($coi->rla_old_certificate) {
                //         $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                //         if (file_exists($path)) {
                //             unlink($path); // Hapus file
                //         }
                //         $validatedData['rla_old_certificate'] = null;
                //     }
            // }

            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($coi->rla_certificate) {
                    // input rla old certificate tidak ada 
                    if(!$request->hasFile('rla_old_certificate')){
                        // replace rla old certificate yang ada menjadi rla certificate sebelumnya
                        $validatedData['rla_old_certificate'] = $coi->rla_certificate;
                        // rla old certificate ada 
                        if ($coi->rla_old_certificate) {
                            $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                            // file ada 
                            if (file_exists($path)) {
                                unlink($path); // Hapus file
                            }
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
                    while (file_exists(public_path("coi/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('coi/rla'), $filename);
    
                    // Simpan nama file ke data yang divalidasi
                    $validatedData['rla_certificate'] = $filename;
                }
                // input rla old certificate ada 
                if ($request->hasFile('rla_old_certificate')) {
                    // rla old certificate ada 
                    if ($coi->rla_old_certificate) {
                        $path = public_path('coi/rla/' . $coi->rla_old_certificate);
                        // file ada 
                        if (file_exists($path)) {
                            unlink($path); // Hapus file
                        }
                    }
                    // proses input rla old certificate baru 
                    $file = $request->file('rla_old_certificate');
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
                    $validatedData['rla_old_certificate'] = $filename;
                }
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
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    // Misalnya, di backend Laravel
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
    


}
