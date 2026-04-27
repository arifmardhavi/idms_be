<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReadinessRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['event_name', 'status'];

    public function readiness_material()
    {
        return $this->hasMany(ReadinessMaterialRtnrt::class);
    }
    public function readiness_jasa()
    {
        return $this->hasMany(ReadinessJasaRtnrt::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($event) {
            foreach ($event->readiness_material as $readiness_material) {
                $readiness_material->delete();
                // ini akan otomatis trigger boot()->deleting() di ReadinessMaterial Rtnrt
            }
            foreach ($event->readiness_jasa as $readiness) {
                $readiness->delete();
                // ini akan otomatis trigger boot()->deleting() di ReadinessJasa Rtnrt
            }
        });
    }
}
