<?php

namespace App\Imports;

use App\Models\Tag_number as TagNumber;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TagNumberImport implements ToCollection, WithHeadingRow
{
    public $errors = [];
    private function mapCriticality(?string $criticality): ?int
    {
        if (blank($criticality)) {
            return null;
        }

        return match (strtolower(trim($criticality))) {

            'high' => 0,

            'medium high' => 1,

            'secondary medium' => 2,

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

    public function collection(Collection $rows)
    {
        // Load reference data to reduce queries
        $units = Unit::pluck('id', 'unit_name')->mapWithKeys(fn($id, $unit_name) => [strtolower($unit_name) => $id]);
        // $categories = Category::pluck('id', 'category_name')->mapWithKeys(fn($id, $category_name) => [strtolower($category_name) => $id]);
        $types = Type::pluck('id', 'type_name')->mapWithKeys(fn($id, $type_name) => [strtolower($type_name) => $id]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1; // karena heading row di baris pertama

            $unitName = strtolower(trim($row['unit']));
            // $categoryName = strtolower(trim($row['kategori']));
            $typeName = strtolower(trim($row['tipe']));
            $tagNumberValue = trim($row['tag_number']);

            $unitId = $units[$unitName] ?? null;
            // $categoryId = $categories[$categoryName] ?? null;
            $typeId = $types[$typeName] ?? null;

            // Cek validitas relasi
            // if (!$unitId || !$categoryId || !$typeId) {
            if (!$unitId || !$typeId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => "Unit/Tipe tidak ditemukan di baris $rowNumber.",
                ];
                continue;
            }

            // Cek duplikat tag_number
            if (TagNumber::where('tag_number', $tagNumberValue)->exists()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => "Tag Number '$tagNumberValue' sudah ada di database (baris $rowNumber).",
                ];
                continue;
            }

            // Simpan jika valid dan tidak duplikat
            TagNumber::create([
                'unit_id' => $unitId,
                // 'category_id' => $categoryId,
                'type_id' => $typeId,
                'tag_number' => strtoupper($tagNumberValue),
                'sece' => $this->mapSece($row['sece']) ?? null,
                'criticality' => $this->mapCriticality($row['criticality']) ?? null,
                'status' => $this->mapStatus($row['status']) ?? 1,
                'description' => $row['deskripsi'] ?? null,
            ]);
        }
    }
}
