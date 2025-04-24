<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'no_spk',
        'spk_name',
        'spk_start_date',
        'spk_end_date',
        'spk_price',
        'spk_file',
        'spk_status',
        'invoice',
        'invoice_value',
        'invoice_file',
    ];
    
    protected $appends = ['weeks'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getWeeksAttribute()
    {
        $start = Carbon::parse($this->spk_start_date);
        $end = Carbon::parse($this->spk_end_date);

        if (!$start->isFriday()) {
            $start = $start->next(Carbon::FRIDAY);
        }

        $weeks = [];
        $weekNumber = 1;

        while ($start->lte($end)) {
            $weekStart = $start->copy();
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $weeks[] = [
                'week' => $weekNumber,
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
                'label' => "Week {$weekNumber} ({$weekStart->format('d M')} - {$weekEnd->format('d M Y')})",
                'value' => "{$weekStart->format('Y-m-d')}_{$weekEnd->format('Y-m-d')}",
            ];

            $weekNumber++;
            $start = $weekStart->addDays(7);
        }

        return $weeks;
    }
}
