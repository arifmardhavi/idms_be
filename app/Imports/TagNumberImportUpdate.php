<?php

namespace App\Imports;

use App\Models\Tag_number as TagNumber;
use App\Models\Unit;
use App\Models\Type;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TagNumberImportUpdate implements ToCollection, WithHeadingRow
{
    public $errors = [];

    public function collection(Collection $rows)
    {
        // Load reference data (minimize query)
        $units = Unit::pluck('id', 'unit_name')
            ->mapWithKeys(fn ($id, $unit_name) => [strtolower($unit_name) => $id]);

        $types = Type::pluck('id', 'type_name')
            ->mapWithKeys(fn ($id, $type_name) => [strtolower($type_name) => $id]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1; // heading row dihitung

            $tagNumberValue = trim($row['tag_number']);
            $unitName = strtolower(trim($row['unit']));
            $typeName = strtolower(trim($row['tipe']));

            $unitId = $units[$unitName] ?? null;
            $typeId = $types[$typeName] ?? null;

            // Validasi relasi
            if (!$unitId || !$typeId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => "Unit atau Tipe tidak ditemukan (baris $rowNumber).",
                ];
                continue;
            }

            // Cari Tag Number yang sudah ada
            $tagNumber = TagNumber::where('tag_number', $tagNumberValue)->first();

            // Jika tag number TIDAK ADA → GAGAL
            if (!$tagNumber) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => "Tag Number '$tagNumberValue' belum terdaftar (baris $rowNumber).",
                ];
                continue;
            }

            // UPDATE DATA (tanpa mengubah tag_number)
            $tagNumber->update([
                'unit_id'     => $unitId,
                'type_id'     => $typeId,
                'status'      => $row['status'] ?? $tagNumber->status,
                'description' => $row['deskripsi'] ?? $tagNumber->description,
            ]);
        }
    }
}
