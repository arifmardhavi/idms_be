<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSpec extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'no_project_spec',
        'judul',
        'tanggal_project_spec',
        'project_spec_file',
    ];
}
