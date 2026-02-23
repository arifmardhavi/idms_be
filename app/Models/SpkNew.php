<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkNew extends Model
{
    use HasFactory;

    protected $table = 'spk_news';

    protected $fillable = [
        'contract_new_id',
        'no_spk',
        'spk_name',
        'spk_start_date',
        'spk_end_date',
        'spk_price',
        'spk_file',
        'spk_status',
        'receipt_nominal',
        'receipt_file',
    ];

    protected $appends = ['total_weeks'];

    public function contracttNew()
    {
        return $this->belongsTo(ContractNew::class);
    }

    public function getTotalWeeksAttribute()
    {
        if (!$this->spk_start_date || !$this->spk_end_date) {
            return 0;
        }

        $start = Carbon::parse($this->spk_start_date);
        $end = Carbon::parse($this->spk_end_date);

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
