<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHakAkses extends Model
{
    use HasFactory;
    protected $table = 'user_hak_akses';
    protected $fillable = [
        'user_id',
        'hak_akses_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hak_akses()
    {
        return $this->belongsTo(HakAkses::class);
    }
}
