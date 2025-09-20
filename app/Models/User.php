<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'fullname',
        'email',
        'username',
        'password',
        'level_user',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    // tambahin biar field baru ikut ke JSON
    protected $appends = ['total_file_open', 'file_open_per_feature', 'total_activities', 'activities_per_feature'];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return $this->toArray();
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class)->withTimestamps();
    }

    /** =====================
     * Relasi ke OpenFileActivity
     * ===================== */
    public function openFileActivities()
    {
        return $this->hasMany(OpenFileActivity::class, 'user_id');
    }

    /** =====================
     * Accessor: Total semua file open
     * ===================== */
    public function getTotalFileOpenAttribute()
    {
        return $this->openFileActivities()->count();
    }

    /** =====================
     * Accessor: Breakdown file open per fitur
     * ===================== */
    public function getFileOpenPerFeatureAttribute()
    {
        return $this->openFileActivities()
            ->select('features', DB::raw('COUNT(*) as total'))
            ->groupBy('features')
            ->pluck('total', 'features');
    }
    

    /** =====================
     * Relasi ke LogActivity
     * ===================== */
    public function logActivities()
    {
        return $this->hasMany(LogActivity::class, 'user_id');
    }

    /** =====================
     * Accessor: Total semua aktivitas 
     * ===================== */
    public function getTotalActivitiesAttribute()
    {
        return $this->logActivities()->count();
    }

    /** =====================
     * Accessor: Breakdown aktivitas per fitur
     * ===================== */
    public function getActivitiesPerFeatureAttribute()
    {
        return $this->logActivities()
            ->select('module', DB::raw('COUNT(*) as total'))
            ->groupBy('module')
            ->pluck('total', 'module');
    }
}
