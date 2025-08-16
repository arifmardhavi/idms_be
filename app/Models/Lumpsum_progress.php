<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lumpsum_progress extends Model
{
    use HasFactory;
    protected $fillable = [
        'contract_id',
        'week',
        'actual_progress',
        'plan_progress',
        'progress_file',
    ];

    protected $appends = ['weeks', 'week_label'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getWeeksAttribute()
    {
        // Pastikan relasi contract tersedia
        if (!$this->contract) {
            return [];
        }

        $start = Carbon::parse($this->contract->contract_start_date);
        $end = Carbon::parse($this->contract->contract_end_date);

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
                'label' => "Week {$weekNumber}",
                'value' => "{$weekStart->format('Y-m-d')}_{$weekEnd->format('Y-m-d')}",
            ];

            $weekNumber++;
            $start = $weekStart->addDays(7);
        }

        return $weeks;
    }

    public function getWeekLabelAttribute()
    {
        $week = $this->week;

        if (!$week || !$this->weeks) {
            return null;
        }

        return collect($this->weeks)->firstWhere('week', (int) $week)['label'] ?? null;
    }


}
