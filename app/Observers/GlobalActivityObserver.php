<?php

namespace App\Observers;

use App\Models\LogActivity;

class GlobalActivityObserver
{
    /**
     * Model yang TIDAK perlu direkam (join pivot / utility / log internal).
     */
    private array $exclude = [
        LogActivity::class,
        \App\Models\OpenFileActivity::class,
        \App\Models\UserHakAkses::class,
        \App\Models\MonitoringEquipmentLog::class,
        \App\Models\HakAkses::class,
        \App\Models\Feature::class,
    ];

    private array $ignoreFields = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Peta nama field label per model (untuk human-readable record_label).
     * Kay = class basename. Fallback: 'id'.
     */
    private array $labelFields = [
        'Coi'                   => 'no_certificate',
        'ReportCoi'             => 'no_certificate',
        'Plo'                   => 'no_certificate',
        'ReportPlo'             => 'no_certificate',
        'BapkCoi'               => 'no_certificate',
        'BapkPlo'               => 'no_certificate',
        'Skhp'                  => 'no_skhp',
        'SertifikatKalibrasi'   => 'no_sertifikat',
        'IzinUsaha'             => 'no_izin',
        'IzinOperasi'           => 'no_izin',
        'IzinDisnaker'          => 'no_izin',
        'Nib'                   => 'no_nib',
        'Ems'                   => 'no_ems',
        'Gms'                   => 'title',
        'IsoMetric'             => 'no_drawing',
        'EcaFsca'               => 'functional_location',
        'Contract'              => 'no_contract',
        'ContractNew'           => 'no_contract',
        'ContractJasa'          => 'no_contract',
        'ContractJasaOh'        => 'no_contract',
        'ContractJasaRtnrt'     => 'no_contract',
        'Spk'                   => 'no_spk',
        'SpkNew'                => 'no_spk',
        'Spk_progress'          => 'no_progress',
        'Lumpsum_progress'      => 'no_progress',
        'SpkProgressNew'        => 'no_progress',
        'LumpsumProgressNew'    => 'no_progress',
        'Amandemen'             => 'no_amandemen',
        'AmandemenNew'          => 'no_amandemen',
        'Tag_number'            => 'tag_number',
        'Datasheet'             => 'no_dokumen',
        'GaDrawing'             => 'no_dokumen',
        'P_id'                  => 'p_id',
        'Pir'                   => 'no_pir',
        'Moc'                   => 'no_moc',
        'MdrFolder'             => 'nama_mdr',
        'MdrItem'               => 'nama_mdr',
        'ProjectSpec'           => 'nama_spesifikasi',
        'HistoricalMemorandum'  => 'no_memo',
        'LampiranMemo'          => 'no_memo',
        'Project'               => 'nama_project',
        'MonitoringEquipment'   => 'tag_number',
        'Preventive'            => 'no_dokumen',
        'Overhaul'              => 'no_dokumen',
        'BreakdownReport'       => 'no_laporan',
        'LaporanInspection'     => 'no_laporan',
        'InternalInspection'    => 'no_laporan',
        'ExternalInspection'    => 'no_laporan',
        'OnstreamInspection'    => 'no_laporan',
        'Surveillance'          => 'no_laporan',
    ];

    public function created($model)
    {
        if ($this->shouldLog($model)) {
            activity()->log('create', $this->moduleName($model), [
                'recordId'    => $model->getKey(),
                'recordLabel' => $this->recordLabel($model),
                'metadata'    => $this->filterFields($model->getAttributes()),
            ]);
        }
    }

    public function updated($model)
    {
        if ($this->shouldLog($model)) {
            $changes = $this->formatChanges($model);
            if (!empty($changes)) {
                activity()->log('update', $this->moduleName($model), [
                    'recordId'    => $model->getKey(),
                    'recordLabel' => $this->recordLabel($model),
                    'metadata'    => $changes,
                ]);
            }
        }
    }

    public function deleted($model)
    {
        if ($this->shouldLog($model)) {
            activity()->log('delete', $this->moduleName($model), [
                'recordId'    => $model->getKey(),
                'recordLabel' => $this->recordLabel($model),
                'metadata'    => $this->filterFields($model->getOriginal()),
            ]);
        }
    }

    private function shouldLog($model): bool
    {
        return !in_array(get_class($model), $this->exclude, true);
    }

    private function moduleName($model): string
    {
        return class_basename($model);
    }

    private function recordLabel($model): string
    {
        $field = $this->labelFields[class_basename($model)] ?? null;
        if ($field && isset($model->{$field})) {
            return (string) $model->{$field};
        }
        return '#' . $model->getKey();
    }

    private function formatChanges($model): array
    {
        $changes = [];
        $dirty   = $model->getChanges();
        unset($dirty['updated_at']);

        foreach ($dirty as $field => $newValue) {
            if (in_array($field, $this->ignoreFields)) {
                continue;
            }
            $changes[$field] = [
                'old' => $model->getOriginal($field),
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    private function filterFields(array $attributes): array
    {
        return collect($attributes)
            ->except($this->ignoreFields)
            ->toArray();
    }
}
