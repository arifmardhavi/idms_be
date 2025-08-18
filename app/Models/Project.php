<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\FileHelper;

class Project extends Model
{
    protected $fillable = ['name', 'folder_path'];

    protected $appends = ['storage_size'];

    public function getStorageSizeAttribute()
    {
        $path = storage_path('app/'.$this->folder_path);
        $bytes = FileHelper::folderSize($path);

        // convert ke MB
        return round($bytes / 1024 / 1024, 2); 
    }
}
