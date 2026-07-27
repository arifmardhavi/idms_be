<?php

namespace App\Services;

use App\Models\Tag_number as TagNumber;
use App\Models\Unit;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TagNumberImportService
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
            return str($header)->trim()->toString();
        }, array_shift($sheet));

        $units = Unit::pluck('id', 'unit_name')
            ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);

        $types = Type::pluck('id', 'type_name')
            ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);

        $rows = [];

        foreach ($sheet as $index => $excelRow) {
            if (collect($excelRow)->filter()->isEmpty()) {
                continue;
            }

            $rowNumber = $index + 2;
            $row = array_combine($headers, $excelRow);

            $rows[] = [
                'row_number' => $rowNumber,
                'row' => $row,
            ];
        }

        $allTagNumbers = collect($rows)
            ->pluck('row.tag_number')
            ->filter()
            ->map(fn($v) => strtoupper(trim($v)))
            ->unique()
            ->values()
            ->all();

        $existingTagNumbers = TagNumber::whereIn('tag_number', $allTagNumbers)
            ->pluck('tag_number')
            ->mapWithKeys(fn($tn) => [strtolower($tn) => true]);

        $toInsert = [];
        $summary = [
            'total' => count($rows),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($rows as $item) {
            $rowNumber = $item['row_number'];
            $row = $item['row'];

            $unitName = strtolower(trim($row['unit'] ?? ''));
            $typeName = strtolower(trim($row['tipe'] ?? ''));
            $tagNumberValue = strtoupper(trim($row['tag_number'] ?? ''));

            $unitId = $units[$unitName] ?? null;
            $typeId = $types[$typeName] ?? null;

            if (!$unitId || !$typeId) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Unit/Tipe tidak ditemukan di baris $rowNumber.",
                ];
                continue;
            }

            if ($existingTagNumbers->has(strtolower($tagNumberValue))) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Tag Number '$tagNumberValue' sudah ada di database (baris $rowNumber).",
                ];
                continue;
            }

            $toInsert[] = [
                'unit_id' => $unitId,
                'type_id' => $typeId,
                'tag_number' => $tagNumberValue,
                'sece' => $this->mapSece($row['sece'] ?? null),
                'criticality' => $this->mapCriticality($row['criticality'] ?? null),
                'status' => $this->mapStatus($row['status'] ?? null) ?? 1,
                'description' => $row['deskripsi'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existingTagNumbers->put(strtolower($tagNumberValue), true);
            $summary['success']++;
        }

        if ($toInsert) {
            DB::transaction(function () use ($toInsert) {
                foreach (array_chunk($toInsert, 500) as $chunk) {
                    TagNumber::insert($chunk);
                }
            });
        }

        return [
            'success' => true,
            'message' => 'Import selesai.',
            'summary' => $summary,
        ];
    }

    private function mapCriticality(?string $criticality): ?int
    {
        if (blank($criticality)) {
            return null;
        }

        return match (strtolower(trim($criticality))) {
            'high' => 0,
            'medium high' => 1,
            'medium' => 2,
            'negligible' => 3,
            'low' => 4,
            default => null,
        };
    }

    private function mapSece(?string $sece): ?int
    {
        if (blank($sece)) {
            return null;
        }

        return match (strtolower(trim($sece))) {
            'iya' => 1,
            'ya' => 1,
            'yes' => 1,
            'tidak' => 0,
            'no' => 0,
            default => null,
        };
    }

    private function mapStatus(?string $status): ?int
    {
        if (blank($status)) {
            return null;
        }

        return match (strtolower(trim($status))) {
            'aktif' => 1,
            'active' => 1,
            'nonaktif' => 0,
            'nonactive' => 0,
            default => 1,
        };
    }
}
