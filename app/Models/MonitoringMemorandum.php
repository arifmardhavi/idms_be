<?php

namespace App\Models;

use App\Support\BacklogDays;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringMemorandum extends BaseModel
{
    use HasFactory;

    /**
     * Wajib eksplisit: tanpa ini Laravel mempluralisasi menjadi
     * "monitoring_memorandums".
     */
    protected $table = 'monitoring_memorandum';

    protected $fillable = [
        'tanggal_issue',
        'issue_operasional',
        'tag_number_ids',
        'nota_dinas',
        'no_notif',
        'status_notif',
        'no_wo',
        'status_wo',
        'no_po',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_issue' => 'date:Y-m-d',
    ];

    public const STATUS_NOTIF_OPTIONS = ['OSNO', 'NOPR'];
    public const STATUS_WO_OPTIONS = ['50', '60'];
    public const STATUS_OPTIONS = ['open', 'close'];
    public const TAG_ALL_MARKER = 'all';

    public function historicalMemorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'nota_dinas');
    }

    public function getIsAllTagNumberAttribute(): bool
    {
        return trim((string) $this->tag_number_ids) === self::TAG_ALL_MARKER;
    }

    /**
     * Alias SQL `backlog_days` dari baseQuery()/dashboard service harus
     * menang atas perhitungan PHP. Tanpa ini, accessor menimpa alias saat
     * query tidak memilih kolom tanggal_issue (mis. agregat dashboard),
     * menghasilkan 0 untuk semua baris.
     */
    public function getBacklogDaysAttribute($value): int
    {
        return $value !== null
            ? (int) $value
            : BacklogDays::forDate($this->tanggal_issue);
    }

    public function getRecordLabelAttribute(): string
    {
        return (string) $this->issue_operasional;
    }
}
