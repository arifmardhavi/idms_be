<?php

namespace App\Services;

use App\Helpers\BusinessPeriod;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
                'message' => 'File Excel kosong.'
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
            'errors' => []
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

        $logFillable = (new MonitoringEquipmentLog())->getFillable();

        $equipmentInsert = [];
        $equipmentUpdate = [];
        $logInsert = [];
        $logUpdate = [];

        foreach ($rows as $item) {

            $tag = $tags->get($item['tag_number']);

            if (!$tag) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $item['row_number'],
                    'tag_number' => $item['tag_number'],
                    'message' => 'Tag Number tidak ditemukan.',
                ];
                continue;
            }

            $row = $item['row'];

            $data = [
                'tag_number_id' => $tag->id,
                'kondisi_peralatan' => $row['kondisi_peralatan'] ?? null,
                'status' => $row['status'] ?? null,
                'jenis_kerusakan' => $row['jenis_kerusakan'] ?? null,
                'penyebab' => $row['penyebab'] ?? null,
                'penanganan_sementara' => $row['penanganan_sementara'] ?? null,
                'perbaikan_permanen' => $row['perbaikan_permanen'] ?? null,
                'progress_perbaikan_permanen' => $row['progress_perbaikan_permanen'] ?? null,
                'kendala_perbaikan' => $row['kendala_perbaikan'] ?? null,
                'estimasi_perbaikan' => $row['estimasi_perbaikan'] ?? null,
                'target' => $row['target'] ?? null,
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
            $allowedPeriods
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

            MonitoringEquipmentLog::whereNotIn('period_code', $allowedPeriods)->delete();
        });

        return [
            'success' => true,
            'message' => 'Import selesai.',
            'summary' => $summary
        ];
    }
}
