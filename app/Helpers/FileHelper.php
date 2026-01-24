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
}
