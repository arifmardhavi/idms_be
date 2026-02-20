<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkProgressNew extends Model
{
    use HasFactory;
    protected $fillable = [
        'spk_new_id',
        'week',
        'plan',
        'actual',
        'progress_file',
    ];

    protected $appends = ['total_weeks'];

    public function spkNew()
    {
        return $this->belongsTo(SpkNew::class);
    }

    public function getTotalWeeksAttribute()
    {
        if (!$this->spkNew) {
            return 0;
        }

        $start = Carbon::parse($this->spkNew->spk_start_date);
        $end = Carbon::parse($this->spkNew->spk_end_date);

        // Geser ke hari Jumat pertama
        if (!$start->isFriday()) {
            $start = $start->next(Carbon::FRIDAY);
        }

        $weekCount = 0;

        while ($start->lte($end)) {
            $weekCount++;
            $start->addDays(7);
        }

        return $weekCount;
    }


    

}
