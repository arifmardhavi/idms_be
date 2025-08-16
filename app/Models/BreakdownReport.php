<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakdownReport extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'laporan_inspection_id',
        'judul',
        'breakdown_report_date',
        'historical_memorandum_id',
        'laporan_file'
    ];

    public function laporan_inspection()
    {
        return $this->belongsTo(LaporanInspection::class);
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class);
    }
}
