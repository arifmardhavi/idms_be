<?php

namespace App\Http\Controllers;

use App\Models\Plo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plo = Plo::with('unit')->get();

        return response()->json([
            'success' => true,
            'message' => 'PLO retrieved successfully.',
            'data' => $plo,
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
            'plo_certificate' => 'required|file|mimes:pdf|max:2048',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1', // required if rla is 1
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue', // required if rla is 1
            'rla_certificate' => 'nullable|file|mimes:pdf|max:3072|required_if:rla,1', // required if rla is 1
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
            // Handle plo_certificate upload
            if ($request->hasFile('plo_certificate')) {
                $file = $request->file('plo_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("plo/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('plo/certificates'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['plo_certificate'] = $filename;

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
                while (file_exists(public_path("plo/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                
                // Store file in public/plo/rla
                $path = $file->move(public_path('plo/rla'), $filename);  
                $validatedData['rla_certificate'] = $filename;
            }

            // Create new Plo record
            $plo = Plo::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PLO created successfully.',
                'data' => $plo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plo = Plo::with('unit')->find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PLO retrieved successfully.',
            'data' => $plo,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $plo = Plo::find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',    
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date|after_or_equal:issue_date',
            'plo_certificate' => 'nullable|file|mimes:pdf|max:2048',
            'plo_old_certificate' => 'nullable|file|mimes:pdf|max:2048',
            'rla' => 'required|in:0,1',
            'rla_issue' => 'nullable|date|required_if:rla,1',
            'rla_overdue' => 'nullable|date|required_if:rla,1|after_or_equal:rla_issue',
            'rla_certificate' => 'nullable|file|mimes:pdf|max:2048',
            'rla_old_certificate' => 'nullable|file|mimes:pdf|max:2048',
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
            // input plo certificate ada 
            if ($request->hasFile('plo_certificate')) {
                // plo certificate sebelumnya ada 
                if ($plo->plo_certificate) {
                    // input plo old certificate tidak ada 
                    if (!$request->hasFile('plo_old_certificate')) {
                        // replace plo old certificate menjadi plo certificate sebelumnya
                        $validatedData['plo_old_certificate'] = $plo->plo_certificate;
                        // plo old certificate sebelumnya ada 
                        if ($plo->plo_old_certificate) {
                            $path = public_path('plo/certificates/' . $plo->plo_old_certificate);
                            // file ada 
                            if (file_exists($path)) {
                                unlink($path); // Hapus file
                            }
                        }
                    } 
                }
                // proses simpan file plo certificate baru
                $file = $request->file('plo_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("plo/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('plo/certificates'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['plo_certificate'] = $filename;
            }
            // input plo old certificate ada 
            if ($request->hasFile('plo_old_certificate')) {
                // plo old certificate sebelumnya ada 
                if ($plo->plo_old_certificate) {
                    $path = public_path('plo/certificates/' . $plo->plo_old_certificate);
                    // file ada 
                    if (file_exists($path)) {
                        unlink($path); // Hapus file
                    }
                }
                // proses simpan file plo old certificate baru
                $file = $request->file('plo_old_certificate');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("plo/certificates/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('plo/certificates'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['plo_old_certificate'] = $filename;
            }

            // jika rla di update jadi 0

            // if($request->rla == 0){
                //     $validatedData['rla_issue'] = null;
                //     $validatedData['rla_overdue'] = null;
                //     if ($plo->rla_certificate) {
                //         $path = public_path('plo/rla/' . $plo->rla_certificate);
                //         if (file_exists($path)) {
                //             unlink($path); // Hapus file
                //         }
                //         $validatedData['rla_certificate'] = null;
                //     }
                //     if ($plo->rla_old_certificate) {
                //         $path = public_path('plo/rla/' . $plo->rla_old_certificate);
                //         if (file_exists($path)) {
                //             unlink($path); // Hapus file
                //         }
                //         $validatedData['rla_old_certificate'] = null;
                //     }
            // }

            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($plo->rla_certificate) {
                    // input rla old certificate tidak ada 
                    if(!$request->hasFile('rla_old_certificate')){
                        // replace rla old certificate yang ada menjadi rla certificate sebelumnya
                        $validatedData['rla_old_certificate'] = $plo->rla_certificate;
                        // rla old certificate ada 
                        if ($plo->rla_old_certificate) {
                            $path = public_path('plo/rla/' . $plo->rla_old_certificate);
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
                    while (file_exists(public_path("plo/rla/".$filename))) {
                        $version++;
                        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }
    
                    // Pindahkan file ke folder tujuan dengan nama unik
                    $path = $file->move(public_path('plo/rla'), $filename);
    
                    // Simpan nama file ke data yang divalidasi
                    $validatedData['rla_certificate'] = $filename;
                }
            }

            // input rla old certificate ada 
            if ($request->hasFile('rla_old_certificate')) {
                // rla old certificate ada 
                if ($plo->rla_old_certificate) {
                    $path = public_path('plo/rla/' . $plo->rla_old_certificate);
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
                while (file_exists(public_path("plo/rla/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                // Pindahkan file ke folder tujuan dengan nama unik
                $path = $file->move(public_path('plo/rla'), $filename);

                // Simpan nama file ke data yang divalidasi
                $validatedData['rla_old_certificate'] = $filename;
            }

            $plo->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PLO updated successfully.',
                'data' => $plo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plo = Plo::find($id);

        if (!$plo) {
            return response()->json([
                'success' => false,
                'message' => 'PLO not found.',
            ], 404);
        }

        try {
            if ($plo->plo_certificate) {
                $path = public_path('plo/certificates/' . $plo->plo_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($plo->plo_old_certificate) {
                $path_last = public_path('plo/certificates/' . $plo->plo_old_certificate);
                if (file_exists($path_last)) {
                    unlink($path_last); // Hapus file
                }
            }
            if ($plo->rla_certificate) {
                $path = public_path('plo/rla/' . $plo->rla_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if ($plo->rla_old_certificate) {
                $path = public_path('plo/rla/' . $plo->rla_old_certificate);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }            
            $plo->delete();
            return response()->json([
                'success' => true,
                'message' => 'PLO deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadPloCertificates(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data PLO berdasarkan ID yang dipilih
        $plos = Plo::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate PLO
        $zip = new \ZipArchive();
        $zipFilePath = public_path('plo_certificates.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($plos as $plo) {
            // Cek jika file PLO ada dan file tersebut valid
            if ($plo->plo_certificate) {
                $filePath = public_path('plo/certificates/' . $plo->plo_certificate);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('plo_certificates.zip')]);
    }
}
