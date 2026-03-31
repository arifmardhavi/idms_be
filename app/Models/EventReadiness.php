<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReadiness extends BaseModel
{
    use HasFactory;
    protected $fillable = ['event_name', 'tanggal_ta'];
    protected $appends = [
        'ta_status',
    ];

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

    public function getTaStatusAttribute()
    {
        if (empty($this->tanggal_ta)) {
            return null;
        }

        if ($this->status === 0) { // sudah selesai
            return [
                'days_remaining' => 0,
                'color' => 'blue',
            ];
        }

        $taDate = Carbon::parse($this->tanggal_ta);
        $diff = Carbon::now()->diffInDays($taDate, false);

        return [
            'days_remaining' => $diff,
            'color' => $this->getColorByDiff($diff),
        ];
    }

    /**
     * helper color rule
     */
    private function getColorByDiff($diff)
    {
        // jika diff null, kembalikan null (tidak ada data)
        if ($diff === null) {
            return null;
        }

        // diff negatif => sudah terlewat => merah
        if ($diff < 0) {
            return 'red';
        }

        if ($diff > 15) {
            return 'green';
        } elseif ($diff >= 5) {
            return 'yellow';
        }

        return 'red';
    }
}
