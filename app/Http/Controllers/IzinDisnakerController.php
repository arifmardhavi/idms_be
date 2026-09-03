<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\IzinDisnaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IzinDisnakerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $izinDisnaker = IzinDisnaker::with(['tag_number', 'plo', 'plo.unit'])->orderBy('overdue_date', 'asc')->get();

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
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:izin_disnakers,tag_number_id',
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'izin_disnaker_certificate' => 'required|file|mimes:pdf|max:25600',
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
            // Handle izin_disnaker_certificate upload
            if ($request->hasFile('izin_disnaker_certificate')) {
                $validatedData['izin_disnaker_certificate'] = FileHelper::uploadWithVersion($request->file('izin_disnaker_certificate'), 'izin_disnaker/certificates');
            }

            // Handle rla_certificate upload (if exists)
            if ($request->hasFile('rla_certificate')) {
                $validatedData['rla_certificate'] = FileHelper::uploadWithVersion($request->file('rla_certificate'), 'izin_disnaker/rla');
            }

            // Handle re_engineer_certificate upload (if exists)
            if ($request->hasFile('re_engineer_certificate')) {
                $validatedData['re_engineer_certificate'] = FileHelper::uploadWithVersion($request->file('re_engineer_certificate'), 'izin_disnaker/re_engineer');
            }

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
        $izinDisnaker = IzinDisnaker::with(['tag_number', 'plo'])->find($id);

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

    public function showByTagNumber(string $tag_number_id)
    {
        $izinDisnaker = IzinDisnaker::with(['tag_number', 'plo'])
            ->where('tag_number_id', $tag_number_id)
            ->first(); // atau ->get() kalau ingin banyak

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found for this tag number.',
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
    public function update(Request $request, string $id)
    {
        $izinDisnaker = IzinDisnaker::find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:izin_disnakers,tag_number_id,' . $id,
            'no_certificate' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'overdue_date' => 'required|date',
            'izin_disnaker_certificate' => $izinDisnaker->izin_disnaker_certificate ? 'nullable|file|mimes:pdf|max:25600' : 'required|file|mimes:pdf|max:25600',
            'izin_disnaker_old_certificate' => 'nullable|file|mimes:pdf|max:25600',
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
                'message' => 'Validasi gagal' . $request->izin_disnaker_certificate,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            // input izin_disnaker certificate ada 
            if ($request->hasFile('izin_disnaker_certificate')) {
                // izin_disnaker certificate sebelumnya ada 
                if ($izinDisnaker->izin_disnaker_certificate) {
                    // input izin_disnaker old certificate tidak ada 
                    if (!$request->hasFile('izin_disnaker_old_certificate')) {
                        // replace izin_disnaker old certificate menjadi izin_disnaker certificate sebelumnya
                        $validatedData['izin_disnaker_old_certificate'] = $izinDisnaker->izin_disnaker_certificate;
                        // izin_disnaker old certificate sebelumnya ada 
                        if ($izinDisnaker->izin_disnaker_old_certificate) {
                            FileHelper::deleteFile($izinDisnaker->izin_disnaker_old_certificate, 'izin_disnaker/certificates');
                        }
                    } 
                }
                // proses simpan file izin_disnaker certificate baru
                $validatedData['izin_disnaker_certificate'] = FileHelper::uploadWithVersion($request->file('izin_disnaker_certificate'), 'izin_disnaker/certificates');
            }
            
            // input rla certificate ada 
            if ($request->hasFile('rla_certificate')) {
                // rla certificate sebelumnya ada 
                if ($izinDisnaker->rla_certificate) {
                    // input rla old certificate tidak ada 
                    if (!$request->hasFile('rla_old_certificate')) {
                        // replace rla old certificate menjadi rla certificate sebelumnya
                        $validatedData['rla_old_certificate'] = $izinDisnaker->rla_certificate;
                        // rla old certificate sebelumnya ada 
                        if ($izinDisnaker->rla_old_certificate) {
                            FileHelper::deleteFile($izinDisnaker->rla_old_certificate, 'izin_disnaker/rla');
                        }
                    } 
                }
                // proses simpan file rla certificate baru
                $validatedData['rla_certificate'] = FileHelper::uploadWithVersion($request->file('rla_certificate'), 'izin_disnaker/rla');
            }

            // input re_engineer certificate ada 
            if ($request->hasFile('re_engineer_certificate')) {
                // re_engineer certificate sebelumnya ada 
                if ($izinDisnaker->re_engineer_certificate) {
                    FileHelper::deleteFile($izinDisnaker->re_engineer_certificate, 'izin_disnaker/re_engineer');
                }
                // proses simpan file re_engineer certificate baru
                $validatedData['re_engineer_certificate'] = FileHelper::uploadWithVersion($request->file('re_engineer_certificate'), 'izin_disnaker/re_engineer');
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
            if ($izinDisnaker->izin_disnaker_certificate) {
                FileHelper::deleteFile($izinDisnaker->izin_disnaker_certificate, 'izin_disnaker/certificates');
            }
            if ($izinDisnaker->izin_disnaker_old_certificate) {
                FileHelper::deleteFile($izinDisnaker->izin_disnaker_old_certificate, 'izin_disnaker/certificates');
            }
            if ($izinDisnaker->rla_certificate) {
                FileHelper::deleteFile($izinDisnaker->rla_certificate, 'izin_disnaker/rla');
            }
            if ($izinDisnaker->rla_old_certificate) {
                FileHelper::deleteFile($izinDisnaker->rla_old_certificate, 'izin_disnaker/rla');
            }
            if ($izinDisnaker->re_engineer_certificate) {
                FileHelper::deleteFile($izinDisnaker->re_engineer_certificate, 'izin_disnaker/re_engineer');
            }
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
                FileHelper::deleteFile($izinDisnaker->izin_disnaker_certificate, 'izin_disnaker/certificates');
                $data = ['izin_disnaker_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Disnaker certificate deleted successfully.',
                ], 200);
            // izin_disnaker old certificate
            }elseif ($request->izin_disnaker_old_certificate) {
                FileHelper::deleteFile($izinDisnaker->izin_disnaker_old_certificate, 'izin_disnaker/certificates');
                $data = ['izin_disnaker_old_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Izin Disnaker old certificate deleted successfully.',
                ], 200);
            // rla certificate
            }elseif ($request->rla_certificate) {
                FileHelper::deleteFile($izinDisnaker->rla_certificate, 'izin_disnaker/rla');
                $data = ['rla_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA certificate deleted successfully.',
                ], 200);
            // rla old certificate
            }elseif ($request->rla_old_certificate) {
                FileHelper::deleteFile($izinDisnaker->rla_old_certificate, 'izin_disnaker/rla');
                $data = ['rla_old_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA old certificate deleted successfully.',
                ], 200);
                // re engineering certificate 
            }elseif ($request->re_engineer_certificate) {
                FileHelper::deleteFile($izinDisnaker->re_engineer_certificate, 'izin_disnaker/re_engineer');
                $data = ['re_engineer_certificate' => null];
                $izinDisnaker->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Re Engineer certificate deleted successfully.',
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
        $izinDisnaker = IzinDisnaker::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan certificate Izin Disnaker
        $zip = new \ZipArchive();
        $zipFilePath = public_path('izin_disnaker_certificates.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
        
        foreach ($izinDisnaker as $item) {
            // Cek jika file Izin Disnaker ada dan file tersebut valid
            if ($item->izin_disnaker_certificate) {
                $filePath = public_path('izin_disnaker/certificates/' . $item->izin_disnaker_certificate);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
        
        $zip->close();
        
        activity()->log('download', 'IzinDisnaker', [
            'metadata' => ['count' => count($izinDisnaker ?? [])],
        ]);

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

        // Ambil semua data izin_disnaker
        $izinDisnaker = IzinDisnaker::all();

        foreach ($izinDisnaker as $item) {
            // Hitung overdue_date untuk izin_disnaker
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

    public function downloadIzinDisnakerFile(string $id)
    {
        $typeFile = request()->get('file', 'izin_disnaker_certificate');

        $izinDisnaker = IzinDisnaker::find($id);

        if (!$izinDisnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Disnaker not found.',
            ], 404);
        }

        // mapping file field + folder
        $fileMap = [
            'izin_disnaker_certificate' => [
                'field' => 'izin_disnaker_certificate',
                'path' => 'izin_disnaker/certificates'
            ],
            'izin_disnaker_old_certificate' => [
                'field' => 'izin_disnaker_old_certificate',
                'path' => 'izin_disnaker/certificates'
            ],
            'rla_certificate' => [
                'field' => 'rla_certificate',
                'path' => 'izin_disnaker/rla'
            ],
            'rla_old_certificate' => [
                'field' => 'rla_old_certificate',
                'path' => 'izin_disnaker/rla'
            ],
            're_engineer_certificate' => [
                'field' => 're_engineer_certificate',
                'path' => 'izin_disnaker/re_engineer'
            ],
        ];

        // validasi type
        if (!isset($fileMap[$typeFile])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type.',
            ], 400);
        }

        $file = $izinDisnaker->{$fileMap[$typeFile]['field']};
        $destinationPath = $fileMap[$typeFile]['path'];

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'IzinDisnaker', [
            'recordId'    => $izinDisnaker->id,
            'recordLabel' => $izinDisnaker->no_certificate ?? $izinDisnaker->id,
            'metadata'    => ['file' => $file],
        ]);

        return FileHelper::downloadFile($destinationPath, $file);
    }
}