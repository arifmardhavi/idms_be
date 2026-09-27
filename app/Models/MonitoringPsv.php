<?php

namespace App\Models;

use App\Support\PsvTagNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Baris monitoring untuk satu COI PSV/TSV.
 *
 * Tabel ini TIDAK menyimpan tag_number maupun masa berlaku — keduanya dibaca
 * live dari `cois` + `tag_numbers`. Jadi koreksi pada COI langsung tercermin
 * di halaman ini tanpa perlu sinkronisasi.
 */
class MonitoringPsv extends BaseModel
{
    use HasFactory;

    /**
     * Wajib ditulis eksplisit: tanpa ini Laravel akan mengpluralisasi
     * "MonitoringPsv" menjadi "monitoring_psvs".
     */
    protected $table = 'monitoring_psv';

    protected $fillable = [
        'status_redundant',
        'pid_no',
        'kategori',
        'keterangan',
    ];

    /**
     * `sisa_hari` & `overdue_date` berasal dari scope withCoiData() (alias dari
     * query), bukan kolom fisik di tabel ini.
     */
    protected $casts = [
        'sisa_hari'   => 'integer',
        'overdue_date' => 'date',
    ];

    /**
     * Relasi ke COI. Nama `tag_number()` pada model Coi waris dari model lama
     * (snake_case), jadi aksesnya `$this->coi->tag_number->tag_number`.
     */
    public function coi()
    {
        return $this->belongsTo(Coi::class);
    }

    /**
     * Label human-readable untuk GlobalActivityObserver.
     * Dipakai karena tabel ini tidak punya kolom tag_number.
     */
    public function getRecordLabelAttribute(): ?string
    {
        if (! empty($this->attributes['tag_number'])) {
            return $this->attributes['tag_number'];
        }

        return $this->coi?->tag_number?->tag_number;
    }

    /**
     * Join ke cois + tag_numbers.
     */
    public function scopeJoinedToCoi(Builder $query): Builder
    {
        return $query
            ->join('cois', 'cois.id', '=', 'monitoring_psv.coi_id')
            ->join('tag_numbers', 'tag_numbers.id', '=', 'cois.tag_number_id');
    }

    /**
     * Join ke cois + tag_numbers lalu batasi hanya tag PSV/TSV.
     *
     * Filter ini yang membuat kelengkapan list tidak bergantung pada listener:
     * kalau tag_number di-rename sehingga tidak lagi PSV/TSV, barisnya otomatis
     * hilang dari list tanpa perlu penghapusan.
     *
     * Sengaja TIDAK menambah select supaya scope ini bisa dipakai ulang oleh
     * query agregat (dashboard) yang butuh selectRaw sendiri.
     */
    public function scopePsvTsv(Builder $query): Builder
    {
        return PsvTagNumber::applyToQuery(
            $this->scopeJoinedToCoi($query),
            'tag_numbers.tag_number'
        );
    }

    /**
     * Tambahkan kolom dari tabel yang di-join + alias `sisa_hari`.
     * Berpasangan dengan scope psvTsv().
     *
     * Sengaja hanya 2 kolom join yang dipilih: tag_number untuk ditampilkan,
     * dan overdue_date untuk masa_berlaku + sisa_hari. Kolom tag_numbers
     * (description/criticality/sece/unit_id) dan cois (no_certificate/
     * issue_date/coi_certificate) tidak lagi dipakai response maupun export.
     *
     * Detail COI pada halaman detail TIDAK bergantung pada select ini — ia
     * dibaca lewat relasi cois(), yang mengambil SELECT * sendiri.
     */
    public function scopeWithCoiData(Builder $query): Builder
    {
        return $query->select('monitoring_psv.*')
            ->addSelect('tag_numbers.tag_number')
            ->addSelect('cois.overdue_date')
            ->selectRaw(
                'DATEDIFF(cois.overdue_date, CURDATE()) as sisa_hari'
            );
    }
}
