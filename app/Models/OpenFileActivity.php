<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenFileActivity extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_name', 'features'];

    protected $appends = ['timestamp']; // field tambahan

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // accessor untuk bikin field baru "timestamp"
    public function getTimestampAttribute()
    {
        return $this->created_at 
            ? $this->created_at->translatedFormat('d F Y H:i:s')
            : null;
    }
}
