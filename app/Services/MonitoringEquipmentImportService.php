<?php

namespace App\Services;

use App\Helpers\BusinessPeriod;
use App\Models\KondisiPeralatan;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MonitoringEquipmentImportService
{
    public function import(UploadedFile $file): array
    {
        $result = null;

        Model::withoutEvents(function () use ($file, &$result) {
            $result = $this->processImport($file);
        });

        return $result;
    }

    private function processImport(UploadedFile $file): array
    {
        $sheet = Excel::toArray([], $file)[0];

        if (count($sheet) <= 1) {
            return [
                'success' => false,
                'message' => 'File Excel kosong.',
            ];
        }

        $headers = array_map(function ($header) {
            return str($header)
                ->trim()
                ->slug('_')
                ->toString();
        }, array_shift($sheet));

        $summary = [
            'total' => count($sheet),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $rows = [];

        foreach ($sheet as $index => $excelRow) {
            if (collect($excelRow)->filter()->isEmpty()) {
                continue;
            }

            $rowNumber = $index + 2;
            $row = array_combine($headers, $excelRow);

            $tagNumber = trim($row['tag_number'] ?? '');

            if ($tagNumber === '') {
                $summary['skipped']++;

                continue;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'tag_number' => $tagNumber,
                'row' => $row,
            ];
        }

        $tagNumbers = collect($rows)->pluck('tag_number')->unique()->values()->all();
        $tags = Tag_number::whereIn('tag_number', $tagNumbers)
            ->get()
            ->keyBy('tag_number');

        $foundTagIds = $tags->pluck('id')->values()->all();
        $existingEquipments = MonitoringEquipment::whereIn('tag_number_id', $foundTagIds)
            ->get()
            ->keyBy('tag_number_id');

        $period = BusinessPeriod::current();
        $periodCode = $period['code'];
        $periodStart = $period['start'];
        $periodEnd = $period['end'];
        $allowedPeriods = BusinessPeriod::allowedPeriods();

        $existingLogs = MonitoringEquipmentLog::whereIn('tag_number_id', $foundTagIds)
            ->where('period_code', $periodCode)
            ->get()
            ->keyBy('tag_number_id');

        $logFillable = (new MonitoringEquipmentLog)->getFillable();

        $kondisiStatus = KondisiPeralatan::query()
            ->where('is_active', 1)
            ->pluck('status', 'kondisi_peralatan')
            ->all();

        $equipmentInsert = [];
        $equipmentUpdate = [];
        $logInsert = [];
        $logUpdate = [];

        foreach ($rows as $item) {

            $tag = $tags->get($item['tag_number']);

            if (! $tag) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $item['row_number'],
                    'tag_number' => $item['tag_number'],
                    'message' => 'Tag Number tidak ditemukan.',
                ];

                continue;
            }

            $row = $item['row'];

            $kondisi = $this->canonicalKondisi(
                $row['kondisi_peralatan'] ?? null,
                $kondisiStatus
            );

            $data = [
                'tag_number_id' => $tag->id,
                'kondisi_peralatan' => $kondisi,
                'status' => $this->resolveStatus(
                    $row['status'] ?? null,
                    $kondisi,
                    $kondisiStatus
                ),
                'jenis_kerusakan' => $row['jenis_kerusakan'] ?? null,
                'penyebab' => $row['penyebab'] ?? null,
                'penanganan_sementara' => $row['penanganan_sementara'] ?? null,
                'perbaikan_permanen' => $row['perbaikan_permanen'] ?? null,
                'progress_perbaikan_permanen' => $row['progress_perbaikan_permanen'] ?? null,
                'kendala_perbaikan' => $row['kendala_perbaikan'] ?? null,
                'estimasi_perbaikan' => $row['estimasi_perbaikan'] ?? null,
                'target' => $this->normalizeTargetDate($row['target'] ?? null),
            ];

            $existing = $existingEquipments->get($tag->id);

            if ($existing) {
                $equipmentUpdate[] = array_merge(['id' => $existing->id], $data);
            } else {
                $equipmentInsert[] = $data;
            }

            $snapshot = array_merge(
                collect($data)->only($logFillable)->toArray(),
                [
                    'tag_number_id' => $tag->id,
                    'period_code' => $periodCode,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]
            );

            $existingLog = $existingLogs->get($tag->id);

            if ($existingLog) {
                $logUpdate[] = array_merge(['id' => $existingLog->id], $snapshot);
            } else {
                $logInsert[] = $snapshot;
            }

            $summary['success']++;
        }

        DB::transaction(function () use (
            $equipmentInsert,
            $equipmentUpdate,
            $logInsert,
            $logUpdate,
            $allowedPeriods,
            $periodCode,
            $periodStart,
            $periodEnd,
            $logFillable
        ) {
            if ($equipmentInsert) {
                MonitoringEquipment::insert($equipmentInsert);
            }

            foreach ($equipmentUpdate as $item) {
                $id = $item['id'];
                MonitoringEquipment::where('id', $id)
                    ->update(collect($item)->except('id')->toArray());
            }

            if ($logInsert) {
                MonitoringEquipmentLog::insert($logInsert);
            }

            foreach ($logUpdate as $item) {
                $id = $item['id'];
                MonitoringEquipmentLog::where('id', $id)
                    ->update(collect($item)->except('id')->toArray());
            }

            $allEquipmentIds = MonitoringEquipment::pluck('tag_number_id')->toArray();

            $currentExistingIds = MonitoringEquipmentLog::where('period_code', $periodCode)
                ->pluck('tag_number_id')
                ->toArray();

            $currentMissingIds = array_values(array_diff($allEquipmentIds, $currentExistingIds));

            if ($currentMissingIds) {
                $latestAvailable = MonitoringEquipmentLog::whereIn('tag_number_id', $currentMissingIds)
                    ->where('period_code', '<', $periodCode)
                    ->max('period_code');

                if ($latestAvailable) {
                    $toCopy = MonitoringEquipmentLog::whereIn('tag_number_id', $currentMissingIds)
                        ->where('period_code', $latestAvailable)
                        ->get()
                        ->map(fn ($log) => array_merge(
                            collect($log->toArray())->only($logFillable)->toArray(),
                            [
                                'period_code' => $periodCode,
                                'period_start' => $periodStart,
                                'period_end' => $periodEnd,
                            ]
                        ))
                        ->toArray();

                    if ($toCopy) {
                        MonitoringEquipmentLog::insert($toCopy);
                    }
                }
            }

            foreach ([BusinessPeriod::previous(2), BusinessPeriod::previous(1)] as $prev) {
                $prevExistingIds = MonitoringEquipmentLog::where('period_code', $prev['code'])
                    ->pluck('tag_number_id')
                    ->toArray();

                $prevMissingIds = array_values(array_diff($allEquipmentIds, $prevExistingIds));

                if (empty($prevMissingIds)) {
                    continue;
                }

                $latestAvailable = MonitoringEquipmentLog::whereIn('tag_number_id', $prevMissingIds)
                    ->where('period_code', '<', $prev['code'])
                    ->max('period_code');

                if (! $latestAvailable) {
                    continue;
                }

                $toCopy = MonitoringEquipmentLog::whereIn('tag_number_id', $prevMissingIds)
                    ->where('period_code', $latestAvailable)
                    ->get()
                    ->map(fn ($log) => array_merge(
                        collect($log->toArray())->only($logFillable)->toArray(),
                        [
                            'period_code' => $prev['code'],
                            'period_start' => $prev['start'],
                            'period_end' => $prev['end'],
                        ]
                    ))
                    ->toArray();

                if ($toCopy) {
                    MonitoringEquipmentLog::insert($toCopy);
                }
            }

            MonitoringEquipmentLog::whereNotIn('period_code', $allowedPeriods)->delete();
        });

        return [
            'success' => true,
            'message' => 'Import selesai.',
            'summary' => $summary,
        ];
    }

    private function normalizeTargetDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial >= 1 && $serial <= 2958465) {
                return ExcelDate::excelToDateTimeObject($serial)
                    ->format('Y-m-d');
            }

            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveStatus($rawStatus, $kondisi, array $map): ?string
    {
        $value = $rawStatus;

        if ($value !== null && $value !== '' && ! str_starts_with((string) $value, '=')) {
            return $value;
        }

        $key = strtolower(trim((string) $kondisi));

        if ($key === '') {
            return null;
        }

        foreach ($map as $mapKey => $status) {
            if (strtolower(trim((string) $mapKey)) === $key) {
                return $status;
            }
        }

        return null;
    }

    private function canonicalKondisi($kondisi, array $map): ?string
    {
        $key = trim((string) $kondisi);

        if ($key === '') {
            return null;
        }

        $normalized = strtolower($key);

        foreach ($map as $mapKey => $status) {
            if (strtolower(trim((string) $mapKey)) === $normalized) {
                return (string) $mapKey;
            }
        }

        return $key;
    }
}
