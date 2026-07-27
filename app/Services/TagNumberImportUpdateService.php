<?php

namespace App\Services;

use App\Models\Tag_number as TagNumber;
use App\Models\Unit;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TagNumberImportUpdateService
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
            ->map(fn($v) => trim($v))
            ->unique()
            ->values()
            ->all();

        $existingTags = TagNumber::whereIn('tag_number', $allTagNumbers)
            ->get()
            ->keyBy('tag_number');

        $toUpdate = [];
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

            $tagNumberValue = trim($row['tag_number'] ?? '');
            $unitName = strtolower(trim($row['unit'] ?? ''));
            $typeName = strtolower(trim($row['tipe'] ?? ''));

            $unitId = $units[$unitName] ?? null;
            $typeId = $types[$typeName] ?? null;

            if (!$unitId || !$typeId) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Unit atau Tipe tidak ditemukan (baris $rowNumber).",
                ];
                continue;
            }

            $existing = $existingTags->get($tagNumberValue);

            if (!$existing) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Tag Number '$tagNumberValue' belum terdaftar (baris $rowNumber).",
                ];
                continue;
            }

            $status = isset($row['status'])
                ? $this->mapStatus($row['status'])
                : $existing->status;

            $toUpdate[] = [
                'id' => $existing->id,
                'unit_id' => $unitId,
                'type_id' => $typeId,
                'sece' => $this->mapSece($row['sece'] ?? null) ?? $existing->sece,
                'criticality' => $this->mapCriticality($row['criticality'] ?? null) ?? $existing->criticality,
                'status' => $status,
                'description' => $row['deskripsi'] ?? $existing->description,
            ];

            $summary['success']++;
        }

        if ($toUpdate) {
            DB::transaction(function () use ($toUpdate) {
                foreach ($toUpdate as $item) {
                    $id = $item['id'];
                    TagNumber::where('id', $id)
                        ->update(collect($item)->except('id')->toArray());
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
