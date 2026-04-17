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

    protected $appends = [
        'hak_akses_name',
        'feature_name',
        'group_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hak_akses()
    {
        return $this->belongsTo(HakAkses::class);
    }

    public function getHakAksesNameAttribute()
    {
        return $this->hak_akses ? $this->hak_akses?->hak_akses : null;
    }

    public function getFeatureNameAttribute()
    {
        return $this->hak_akses ? $this->hak_akses?->feature?->feature : null;
    }

    public function getGroupNameAttribute()
    {
        return $this->hak_akses ? $this->hak_akses?->feature?->group : null;
    }
}
