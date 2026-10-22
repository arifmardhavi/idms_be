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
        'hak_akses'
    ];

    // tambahin biar field baru ikut ke JSON
    // CATATAN: setiap item di $appends WAJIB punya accessor get{Studly}Attribute().
    // Laravel memanggil accessor itu tanpa cek di HasAttributes::mutateAttribute(),
    // jadi append tanpa accessor akan melempar BadMethodCallException saat model
    // diserialisasi (toArray/toJson/response()->json($model)).
    protected $appends = [
        'total_activities',
        'activities_per_feature',
        'akses_list',
    ];

    /**
     * Agregat aktivitas (total + breakdown per modul).
     *
     * Diisi controller lewat setActivityStats() supaya serialisasi collection
     * user tidak melakukan query per user (N+1). Null = belum di-set, accessor
     * fallback ke query sendiri.
     */
    protected ?array $activityStats = null;

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
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'username' => $this->username,
            'level_user' => $this->level_user,
            'status' => $this->status,
            'hak_akses_list' => $this->hak_akses_list ?? [],
        ];
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class)->withTimestamps();
    }

    public function contract_news()
    {
        return $this->belongsToMany(ContractNew::class)->withTimestamps();
    }

    public function hak_akses()
    {
        return $this->hasMany(UserHakAkses::class);
    }


    /** =====================
    * Accessor: List hak akses
    * ===================== */

    // public function getHakAksesListAttribute()
    // {
    //     return $this->hak_akses
    //         ->pluck('hak_akses.hak_akses')
    //         ->filter()
    //         ->values()
    //         ->toArray();
    // }

    public function getAksesListAttribute()
    {
        // Pakai FK langsung (hak_akses_id), bukan relasi hak_akses -> id.
        // pluck('hak_akses.id') memaksa load relasi HakAkses untuk tiap baris
        // user_hak_akses (N+1) padahal yang dibutuhkan hanya nilainya.
        return $this->hak_akses
            ->pluck('hak_akses_id')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Pasang agregat aktivitas hasil hitungan bulk (1 query untuk semua user).
     *
     * @param  array<string, int>  $perFeature  breakdown per modul
     */
    public function setActivityStats(int $total, array $perFeature): static
    {
        $this->activityStats = [
            'total' => $total,
            'per_feature' => collect($perFeature),
        ];

        return $this;
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
        if ($this->activityStats !== null) {
            return $this->activityStats['total'];
        }

        return $this->logActivities()->count();
    }

    /** =====================
     * Accessor: Breakdown aktivitas per fitur
     * ===================== */
    public function getActivitiesPerFeatureAttribute()
    {
        if ($this->activityStats !== null) {
            return $this->activityStats['per_feature'];
        }

        return $this->logActivities()
            ->select('module', DB::raw('COUNT(*) as total'))
            ->groupBy('module')
            ->pluck('total', 'module');
    }
}
