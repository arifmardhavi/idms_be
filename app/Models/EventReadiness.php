<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReadiness extends BaseModel
{
    use HasFactory;
    protected $fillable = ['event_name', 'tanggal_ta'];

    public function readiness()
    {
        return $this->hasMany(ReadinessMaterial::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($event) {
            foreach ($event->readiness as $readiness) {
                $readiness->delete(); 
                // ini akan otomatis trigger boot()->deleting() di ReadinessMaterial
            }
        });
    }
}
