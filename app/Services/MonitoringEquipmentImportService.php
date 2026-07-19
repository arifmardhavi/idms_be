<?php

namespace App\Services;

use App\Helpers\BusinessPeriod;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use App\Models\Tag_number;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringEquipmentImportService
{

    private function mapStatus(?string $status): ?int
    {
        if (blank($status)) {
            return null;
        }

        return match (strtolower(trim($status))) {

            'high' => 0,

            'medium' => 1,

            'low' => 2,

            'breakdown' => 3,

            default => null,

        };
    }
    public function import(UploadedFile $file): array
    {
        $sheet = Excel::toArray([], $file)[0];

        if (count($sheet) <= 1) {
            return [
                'success' => false,
                'message' => 'File Excel kosong.'
            ];
        }

        /**
         * Header
         */
        $headers = array_map(function ($header) {

            return str($header)
                ->trim()
                ->snake()
                ->toString();

        }, array_shift($sheet));

        /**
         * Summary
         */
        $summary = [

            'total' => count($sheet),

            'success' => 0,

            'failed' => 0,

            'skipped' => 0,

            'errors' => []

        ];

        foreach ($sheet as $index => $excelRow) {

            /**
             * Skip Empty Row
             */
            if (collect($excelRow)->filter()->isEmpty()) {
                continue;
            }

            $rowNumber = $index + 2;

            /**
             * Convert Row
             */
            $row = array_combine($headers, $excelRow);

            try {

                DB::transaction(function () use ($row, &$summary, $rowNumber) {

                    /**
                     * Tag Number
                     */
                    $tag = Tag_number::where(
                        'tag_number',
                        trim($row['tag_number'])
                    )->first();

                    if (!$tag) {

                        $summary['failed']++;

                        $summary['errors'][] = [

                            'row' => $rowNumber,

                            'tag_number' => $row['tag_number'],

                            'message' => 'Tag Number tidak ditemukan.'

                        ];

                        return;
                    }

                    /**
                     * Monitoring Equipment Data
                     */
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

                    /**
                     * Create / Update Monitoring Equipment
                     */
                    $equipment = MonitoringEquipment::updateOrCreate(

                        [

                            'tag_number_id' => $tag->id

                        ],

                        $data

                    );

                    /**
                     * Current Period
                     */
                    $period = BusinessPeriod::current();

                    /**
                     * Snapshot
                     */
                    $snapshot = array_merge(

                        collect($equipment->fresh()->getAttributes())
                            ->only((new MonitoringEquipmentLog())->getFillable())
                            ->toArray(),

                        [

                            'period_code' => $period['code'],

                            'period_start' => $period['start'],

                            'period_end' => $period['end'],

                        ]

                    );

                    /**
                     * Save Log
                     */
                    MonitoringEquipmentLog::updateOrCreate(

                        [

                            'tag_number_id' => $equipment->tag_number_id,

                            'period_code' => $period['code'],

                        ],

                        $snapshot

                    );

                    /**
                     * Cleanup
                     */
                    MonitoringEquipmentLog::where(
                        'tag_number_id',
                        $equipment->tag_number_id
                    )
                        ->whereNotIn(
                            'period_code',
                            BusinessPeriod::allowedPeriods()
                        )
                        ->delete();

                    $summary['success']++;

                });

            } catch (\Throwable $e) {

                $summary['failed']++;

                $summary['errors'][] = [

                    'row' => $rowNumber,

                    'tag_number' => $row['tag_number'] ?? null,

                    'message' => $e->getMessage(),

                ];

            }

        }

        return [

            'success' => true,

            'message' => 'Import selesai.',

            'summary' => $summary

        ];
    }
}