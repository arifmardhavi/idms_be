<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Coi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class CoiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $search = $request->get('search');

        $query = Coi::with(['tag_number', 'plo', 'plo.unit'])
            ->orderBy('overdue_date', 'asc');

        // =====================
        // GLOBAL SEARCH
        // =====================
        if ($search) {
            $query->where(function ($q) use ($search) {

                // field utama COI
                $q->where('no_certificate', 'like', "%$search%")

                // relasi tag_number
                ->orWhereHas('tag_number', function ($q2) use ($search) {
                    $q2->where('tag_number', 'like', "%$search%");
                })

                // relasi PLO (pakai no_certificate)
                ->orWhereHas('plo', function ($q3) use ($search) {
                    $q3->where('no_certificate', 'like', "%$search%");
                })

                // relasi UNIT (unit_name)
                ->orWhereHas('plo.unit', function ($q4) use ($search) {
                    $q4->where('unit_name', 'like', "%$search%");
                });
            });
        }

        // =====================
        // FILTER (PAKAI TANGGAL, BUKAN ACCESSOR)
        // =====================
        if ($filter == 'coi_more_than_nine_months') {
            $query->whereRaw('DATEDIFF(overdue_date, CURDATE()) > 270');
        }

        if ($filter == 'coi_less_than_nine_months') {
            $query->whereRaw('DATEDIFF(overdue_date, CURDATE()) BETWEEN 0 AND 270');
        }

        if ($filter == 'coi_expired') {
            $query->whereRaw('DATEDIFF(overdue_date, CURDATE()) < 0');
        }

        if ($filter == 'rla_more_than_nine_months') {
            $query->whereRaw('DATEDIFF(rla_overdue, CURDATE()) > 270');
        }

        if ($filter == 'rla_less_than_nine_months') {
            $query->whereRaw('DATEDIFF(rla_overdue, CURDATE()) BETWEEN 0 AND 270');
        }

        if ($filter == 'rla_expired') {
            $query->whereRaw('DATEDIFF(rla_overdue, CURDATE()) < 0');
        }

        $coi = $query->get();

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
            if ($request->hasFile('coi_certificate')) {
                $validatedData['coi_certificate'] = FileHelper::uploadWithVersion($request->file('coi_certificate'), 'coi/certificates');
            }

            if ($request->hasFile('rla_certificate')) {
                $validatedData['rla_certificate'] = FileHelper::uploadWithVersion($request->file('rla_certificate'), 'coi/rla');
            }

            if ($request->hasFile('re_engineer_certificate')) {
                $validatedData['re_engineer_certificate'] = FileHelper::uploadWithVersion($request->file('re_engineer_certificate'), 'coi/re_engineer');
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
            if ($request->hasFile('coi_certificate')) {
                if ($coi->coi_certificate) {
                    if (!$request->hasFile('coi_old_certificate')) {
                        $validatedData['coi_old_certificate'] = $coi->coi_certificate;
                        if ($coi->coi_old_certificate) {
                            FileHelper::deleteFile($coi->coi_old_certificate, 'coi/certificates');
                        }
                    }
                }
                $validatedData['coi_certificate'] = FileHelper::uploadWithVersion($request->file('coi_certificate'), 'coi/certificates');
            }

            if ($request->hasFile('rla_certificate')) {
                if ($coi->rla_certificate) {
                    if (!$request->hasFile('rla_old_certificate')) {
                        $validatedData['rla_old_certificate'] = $coi->rla_certificate;
                        if ($coi->rla_old_certificate) {
                            FileHelper::deleteFile($coi->rla_old_certificate, 'coi/rla');
                        }
                    }
                }
                $validatedData['rla_certificate'] = FileHelper::uploadWithVersion($request->file('rla_certificate'), 'coi/rla');
            }

            if ($request->hasFile('re_engineer_certificate')) {
                if ($coi->re_engineer_certificate) {
                    FileHelper::deleteFile($coi->re_engineer_certificate, 'coi/re_engineer');
                }
                $validatedData['re_engineer_certificate'] = FileHelper::uploadWithVersion($request->file('re_engineer_certificate'), 'coi/re_engineer');
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
                FileHelper::deleteFile($coi->coi_certificate, 'coi/certificates');
            }
            if ($coi->rla_certificate) {
                FileHelper::deleteFile($coi->rla_certificate, 'coi/rla');
            }
            if ($coi->rla_old_certificate) {
                FileHelper::deleteFile($coi->rla_old_certificate, 'coi/rla');
            }
            if ($coi->re_engineer_certificate) {
                FileHelper::deleteFile($coi->re_engineer_certificate, 'coi/re_engineer');
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
            if ($request->coi_certificate) {
                FileHelper::deleteFile($coi->coi_certificate, 'coi/certificates');
                $data = ['coi_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'COI certificate deleted successfully.',
                ], 200);
            }elseif ($request->coi_old_certificate) {
                FileHelper::deleteFile($coi->coi_old_certificate, 'coi/certificates');
                $data = ['coi_old_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'COI old certificate deleted successfully.',
                ], 200);
            }elseif ($request->rla_certificate) {
                FileHelper::deleteFile($coi->rla_certificate, 'coi/rla');
                $data = ['rla_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA certificate deleted successfully.',
                ], 200);
            }elseif ($request->rla_old_certificate) {
                FileHelper::deleteFile($coi->rla_old_certificate, 'coi/rla');
                $data = ['rla_old_certificate' => null];
                $coi->update($data);
                return response()->json([
                    'success' => true,
                    'message' => 'RLA old certificate deleted successfully.',
                ], 200);
            }elseif ($request->re_engineer_certificate) {
                FileHelper::deleteFile($coi->re_engineer_certificate, 'coi/re_engineer');
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
    
        activity()->log('download', 'Coi', [
            'metadata' => ['count' => count($cois ?? [])],
        ]);

        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('coi_certificates.zip')]);
    }

    public function countCoiDueDays()
    {
        $coiMoreThanNineMonths = 0;
        $coiLessThanNineMonths = 0;
        $coiExpired = 0;
        $rlaMoreThanNineMonths = 0;
        $rlaLessThanNineMonths = 0;
        $rlaExpired = 0;

        $coi = Coi::all();

        foreach ($coi as $item) {

            if (!is_null($item->due_days)) {

                if ($item->due_days > 270) {
                    $coiMoreThanNineMonths++;
                } elseif ($item->due_days >= 0) {
                    $coiLessThanNineMonths++;
                } else {
                    $coiExpired++;
                }
            }

            if (!is_null($item->rla_due_days)) {

                if ($item->rla_due_days > 270) {
                    $rlaMoreThanNineMonths++;
                } elseif ($item->rla_due_days >= 0) {
                    $rlaLessThanNineMonths++;
                } else {
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


    public function downloadCoiFile(string $id)
    {
        $typeFile = request()->get('file', 'coi_certificate');

        $coi = Coi::find($id);

        if (!$coi) {
            return response()->json([
                'success' => false,
                'message' => 'COI not found.',
            ], 404);
        }

        // mapping file field + folder
        $fileMap = [
            'coi_certificate' => [
                'field' => 'coi_certificate',
                'path' => 'coi/certificates'
            ],
            'coi_old_certificate' => [
                'field' => 'coi_old_certificate',
                'path' => 'coi/certificates'
            ],
            'rla_certificate' => [
                'field' => 'rla_certificate',
                'path' => 'coi/rla'
            ],
            'rla_old_certificate' => [
                'field' => 'rla_old_certificate',
                'path' => 'coi/rla'
            ],
            're_engineer_certificate' => [
                'field' => 're_engineer_certificate',
                'path' => 'coi/re_engineer'
            ],
        ];

        // validasi type
        if (!isset($fileMap[$typeFile])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type.',
            ], 400);
        }

        $file = $coi->{$fileMap[$typeFile]['field']};
        $destinationPath = $fileMap[$typeFile]['path'];

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        activity()->log('download', 'Coi', [
            'recordId'    => $coi->id,
            'recordLabel' => $coi->no_certificate ?? $coi->id,
            'metadata'    => ['file' => $file],
        ]);

        return FileHelper::downloadFile($destinationPath, $file);
    }
    


}
