<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class FileHelper
{

    public static function uploadWithVersion($file, $destinationFolder){
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
        $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
        $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
        $version = 0; // Awal versi
        // Format nama file
        $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

        // Cek apakah file dengan nama ini sudah ada di folder tujuan
        while (file_exists(public_path($destinationFolder."/".$filename))) {
            $version++;
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
        }
        // Store file in public/contract_new
        $file->move(public_path($destinationFolder), $filename);
        return $filename;
    }

    public static function uploadWithCustomPrefix($file, $destinationFolder, $customPrefix = ''){
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $dateNow = date('dmY');
        $version = 0;

        if ($customPrefix) {
            $filename = $originalName . '_' . $customPrefix . '_' . $dateNow . '_' . $version . '.' . $extension;
        } else {
            $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
        }

        while (file_exists(public_path($destinationFolder."/".$filename))) {
            $version++;
            if ($customPrefix) {
                $filename = $originalName . '_' . $customPrefix . '_' . $dateNow . '_' . $version . '.' . $extension;
            } else {
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
            }
        }

        $file->move(public_path($destinationFolder), $filename);
        return $filename;
    }

    public static function deleteFile($filename, $destinationFolder){
        $remove_path = public_path($destinationFolder."/".$filename);
        if (file_exists($remove_path)) {
            unlink($remove_path); // Hapus file
            return true; // Berhasil dihapus
        }
        return false; // File tidak ada atau gagal dihapus
    }

    public static function folderSize($dir)
    {
        $size = 0;
        if (!File::exists($dir)) {
            return $size;
        }
        foreach (File::allFiles($dir) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
    
    /**
     * Nama file yang berasal dari DB tidak boleh mengandung separator path.
     * Tanpa pemeriksaan ini, nilai seperti "../rahasia.pdf" bisa keluar dari
     * folder upload dan menyebabkan traversal (atau error 500 di header download).
     */
    public static function isSafeFileName(string $fileName): bool
    {
        $normalized = str_replace('\\', '/', $fileName);

        return $fileName !== ''
            && !str_contains($fileName, "\0")
            && $normalized === basename($normalized);
    }

    public static function downloadFile(string $destinationFolder, string $fileName)
    {
        if (!self::isSafeFileName($fileName)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file name.',
            ], 404);
        }

        $filePath = public_path($destinationFolder . '/' . $fileName);
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File does not exist in storage.',
            ], 404);
        }
        $response = response()->download(
            $filePath,
            $fileName,
            [
                'Content-Type' => mime_content_type($filePath),
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"; filename*=UTF-8''" . rawurlencode($fileName),
            ]
        );
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
