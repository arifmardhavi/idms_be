<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\File;

class ProjectController extends Controller
{
    public function index()
    {
        // Ambil semua project dengan storage size
        return response()->json(Project::all());
    }

    public function totalSize()
    {
        $folders = [
            base_path(),          // folder laravel (app, vendor, storage, dll)
            base_path('../public') // folder public (karena posisinya di luar laravel)
        ];

        $total = 0;
        $details = [];

        foreach ($folders as $folder) {
            $size = 0;
            if (File::exists($folder)) {
                foreach (File::allFiles($folder) as $file) {
                    $size += $file->getSize();
                }
            }
            $details[$folder] = [
                'mb' => round($size / 1024 / 1024, 2),
                'gb' => round($size / 1024 / 1024 / 1024, 2),
            ];
            $total += $size;
        }

        return response()->json([
            'total_size_mb' => round($total / 1024 / 1024, 2),
            'total_size_gb' => round($total / 1024 / 1024 / 1024, 2),
            'breakdown' => $details
        ]);
    }
}
