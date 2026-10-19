<?php

namespace App\Services;

use App\Models\MonitoringPipeline;
use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringPipelineImportService
{
    private const SHEET_NAME = 'Database UT Piping';

    /**
     * Slug header file (setelah normalisasi whitespace + slug '_') => field model.
     */
    private const HEADER_MAP = [
        'tag_number' => 'tag_number',
        'tml_drawing_number' => 'tml_drawing_number',
        'api_570_service_class' => 'api_service_class',
        'insulation_or_non_insulation' => 'insulation',
        'grade_material' => 'grade_material',
        'nps' => 'nps',
        'od_mm' => 'od',
        'sch' => 'sch',
        't_nom_mm' => 't_nom',
        'operating_press_kg_cm2' => 'operating_press',
        'design_press_kg_cm2' => 'design_press',
        'design_temp_kg_cm2' => 'design_temp',
        's_mpa' => 's',
        'e' => 'e',
        'w' => 'w',
        'y' => 'y',
        'min_req_thk_mm' => 'min_req_thk',
        '50_nom_thk_mm' => 'half_nom_thk',
        'tanggal_pasang_line' => 'tanggal_pasang_line',
        'actual_thk_mm' => 'actual_thk',
        'tanggal_ut_terakhir' => 'tanggal_ut_terakhir',
        'corrosion_rate' => 'corrosion_rate',
        'remaining_life_asme_31_3' => 'remaining_life',
        'alert_structural_thickness_api_574' => 'alert_structural_thickness',
        'next_plan_inspection_api_570' => 'next_plan_inspection_api',
        'used_plan_inspection' => 'used_plan_inspection',
        'pipe_condition_api_574' => 'pipe_condition',
        'estimated_end_of_life_eol' => 'estimated_end_of_life',
        'maintenance_status' => 'maintenance_status',
        'rencana_tindak_lanjut' => 'rencana_tindak_lanjut',
    ];

    private const DOUBLE_FIELDS = [
        'nps', 'od', 't_nom', 'operating_press', 'design_press', 'design_temp',
        's', 'e', 'w', 'y', 'min_req_thk', 'half_nom_thk', 'actual_thk',
        'corrosion_rate', 'remaining_life', 'alert_structural_thickness',
    ];

    private const DATE_FIELDS = [
        'tanggal_pasang_line', 'tanggal_ut_terakhir', 'next_plan_inspection_api',
        'used_plan_inspection', 'estimated_end_of_life',
    ];

    private const EXCEL_ERRORS = [
        '#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!', 'N/A',
    ];

    private const MAX_WARNINGS = 100;

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
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());

        $sheetNames = $spreadsheet->getSheetNames();

        $sheet = in_array(self::SHEET_NAME, $sheetNames, true)
            ? $spreadsheet->getSheetByName(self::SHEET_NAME)
            : $spreadsheet->getSheetIndex(0);

        $headerRow = $this->findHeaderRow($sheet);

        if ($headerRow === null) {
            $spreadsheet->disconnectWorksheets();

            return [
                'success' => false,
                'message' => 'Baris header dengan kolom TAG NUMBER tidak ditemukan.',
            ];
        }

        $columnMap = $this->mapColumns($sheet, $headerRow);

        if (! isset($columnMap['tag_number'])) {
            $spreadsheet->disconnectWorksheets();

            return [
                'success' => false,
                'message' => 'Kolom TAG NUMBER tidak ditemukan.',
            ];
        }

        $summary = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
            'warnings_total' => 0,
        ];

        $rows = [];

        $highestRow = $sheet->getHighestRow();

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {

            $row = [];
            $isEmpty = true;

            foreach ($columnMap as $field => $letter) {
                $cell = $sheet->getCell($letter.$r);
                $value = $cell->isFormula()
                    ? $cell->getOldCalculatedValue()
                    : $cell->getValue();

                $row[$field] = $value;

                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
            }

            if ($isEmpty) {
                $summary['skipped']++;

                continue;
            }

            $tagNumber = is_string($row['tag_number'])
                ? trim($row['tag_number'])
                : trim((string) ($row['tag_number'] ?? ''));

            if ($tagNumber === '') {
                $summary['skipped']++;

                continue;
            }

            $summary['total']++;

            $rows[] = [
                'row_number' => $r,
                'tag_number' => $tagNumber,
                'row' => $row,
            ];
        }

        $tagNumbers = collect($rows)->pluck('tag_number')->unique()->values()->all();

        $tags = Tag_number::whereIn('tag_number', $tagNumbers)
            ->get()
            ->keyBy('tag_number');

        $foundTagIds = $tags->pluck('id')->values()->all();

        $existingPipelines = MonitoringPipeline::whereIn('tag_number_id', $foundTagIds)
            ->get()
            ->keyBy('tag_number_id');

        $inserts = [];
        $updates = [];

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

            $data = $this->normalizeRow($item['row'], $item['row_number'], $summary);

            $data['tag_number_id'] = $tag->id;

            $existing = $existingPipelines->get($tag->id);

            if ($existing) {
                $updates[] = ['id' => $existing->id, 'data' => $data];
            } else {
                $inserts[] = $data;
            }

            $summary['success']++;
        }

        DB::transaction(function () use ($inserts, $updates) {

            if ($inserts) {
                MonitoringPipeline::insert($inserts);
            }

            foreach ($updates as $item) {
                MonitoringPipeline::where('id', $item['id'])
                    ->update($item['data']);
            }

        });

        $spreadsheet->disconnectWorksheets();

        return [
            'success' => true,
            'message' => 'Import selesai.',
            'summary' => $summary,
        ];
    }

    /**
     * Cari baris header: baris (maks 15 baris pertama) yang sel kolom B-nya
     * slug-nya 'tag_number'.
     */
    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $limit = min(15, $sheet->getHighestRow());

        for ($r = 1; $r <= $limit; $r++) {
            $value = $sheet->getCell('B'.$r)->getValue();

            if ($value === null) {
                continue;
            }

            if ($this->slugHeader($value) === 'tag_number') {
                return $r;
            }
        }

        return null;
    }

    /**
     * Petakan field model => letter kolom berdasarkan slug header.
     * Batas 100 kolom (file asli punya phantom columns di kanan).
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        $highestColumn = Coordinate::columnIndexFromString(
            $sheet->getHighestDataColumn()
        );

        $limit = min($highestColumn, 100);

        $columnMap = [];

        for ($c = 1; $c <= $limit; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);

            $value = $sheet->getCell($letter.$headerRow)->getValue();

            if ($value === null || $value === '') {
                continue;
            }

            $slug = $this->slugHeader($value);

            if (isset(self::HEADER_MAP[$slug])) {
                $columnMap[self::HEADER_MAP[$slug]] = $letter;
            }
        }

        return $columnMap;
    }

    private function slugHeader($value): string
    {
        return str(preg_replace('/[\r\n]+/', ' ', trim((string) $value)))
            ->slug('_')
            ->toString();
    }

    private function normalizeRow(array $row, int $rowNumber, array &$summary): array
    {
        $data = [];

        foreach ($row as $field => $value) {

            if ($field === 'tag_number') {
                continue;
            }

            if ($field === 'insulation') {
                $data[$field] = $this->normalizeInsulation(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if ($field === 'nps') {
                $data[$field] = $this->normalizeNps(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if ($field === 'sch') {
                $data[$field] = $this->normalizeSch($value);

                continue;
            }

            if (in_array($field, self::DOUBLE_FIELDS, true)) {
                $data[$field] = $this->normalizeDouble(
                    $value, $field, $rowNumber, $summary
                );

                continue;
            }

            if (in_array($field, self::DATE_FIELDS, true)) {
                $data[$field] = $this->normalizeDate($value);

                continue;
            }

            $data[$field] = $this->normalizeString($value);
        }

        return $data;
    }

    private function normalizeInsulation(
        $value, string $field, int $rowNumber, array &$summary
    ): ?string {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        if (strcasecmp($string, 'Yes') === 0) {
            return 'Yes';
        }

        if (strcasecmp($string, 'No') === 0) {
            return 'No';
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai insulation bukan Yes/No, disimpan kosong.'
        );

        return null;
    }

    private function normalizeNps(
        $value, string $field, int $rowNumber, array &$summary
    ): ?float {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);

        // Pecahan campuran: "1 1/2" => 1.5
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $string, $m) && (int) $m[3] !== 0) {
            return (float) $m[1] + ((float) $m[2] / (float) $m[3]);
        }

        // Pecahan: "3/4" => 0.75
        if (preg_match('/^(\d+)\/(\d+)$/', $string, $m) && (int) $m[2] !== 0) {
            return (float) $m[1] / (float) $m[2];
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai NPS tidak terbaca sebagai angka atau pecahan.'
        );

        return null;
    }

    private function normalizeSch($value): ?string
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $float = (float) $value;

            return $float == (int) $float
                ? (string) (int) $float
                : (string) $float;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function normalizeDouble(
        $value, string $field, int $rowNumber, array &$summary
    ): ?float {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $this->pushWarning(
            $summary, $rowNumber, $field, $value,
            'Nilai bukan angka (termasuk error Excel), disimpan kosong.'
        );

        return null;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '' || is_bool($value)) {
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

        $string = trim((string) $value);

        if ($string === '' || in_array($string, self::EXCEL_ERRORS, true)) {
            return null;
        }

        try {
            return Carbon::parse($string)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeString($value): ?string
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '' || in_array($string, self::EXCEL_ERRORS, true)) {
            return null;
        }

        return $string;
    }

    private function pushWarning(
        array &$summary, int $rowNumber, string $column, $value, string $message
    ): void {
        $summary['warnings_total']++;

        if (count($summary['warnings']) >= self::MAX_WARNINGS) {
            return;
        }

        $summary['warnings'][] = [
            'row' => $rowNumber,
            'column' => $column,
            'value' => is_scalar($value) ? $value : null,
            'message' => $message,
        ];
    }
}
