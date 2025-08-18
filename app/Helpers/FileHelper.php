<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class FileHelper
{
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
