<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = ['no_vendor', 'vendor_name', 'no_contract', 'contract_name', 'contract_type', 'contract_date', 'contract_price', 'contract_file', 'kom', 'contract_start_date', 'contract_end_date', 'meeting_notes', 'contract_status'];
    protected $appends = ['weeks'];
    public function termin()
    {
        return $this->hasMany(Termin::class);
    }

    public function spk()
    {
        return $this->hasMany(Spk::class);
    }

    public function getWeeksAttribute()
    {
        $start = Carbon::parse($this->contract_start_date);
        $end = Carbon::parse($this->contract_end_date);

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
