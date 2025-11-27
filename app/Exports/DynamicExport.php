<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DynamicExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    protected array $data;
    protected array $columns; // key => label
    protected array $options;
    protected int $headerRowCount = 0;

    public function __construct(array $data, array $columns, array $options = [])
    {
        $this->data = $data;
        $this->columns = $columns;

        $this->options = array_merge([
            // basic
            'filter' => false,
            'freezeHeader' => false,
            // header multi-row: array of rows (each row = array of cell values)
            'headerRows' => [],
            // conditional rules: array of ['column' => key, 'condition' => fn($value) => bool, 'style' => [...]]
            'conditional' => [],
            // rowStyles: [1 => styleArray, 2 => styleArray] (1 = first data row)
            'rowStyles' => [],
            // global styles applyFromArray to whole table
            'styles' => [],
            // number formats (manual): key => phpSpreadsheet format string
            'numberFormat' => [],
            // date formats (manual): key => excel format string like 'dd-mm-yyyy' or NumberFormat::FORMAT_DATE_DDMMYYYY
            'dateFormat' => [],
            // column width by letter or by key: ['A' => 8, 'vendor' => 30]
            'columnWidth' => [],
            // auto width boolean (if true uses ShouldAutoSize + custom adjust)
            'autoWidth' => false,
            // autoWidthMax to avoid huge widths
            'autoWidthMax' => 60,
            // border options: true or array('onlyHeader'=>true,'onlyData'=>false,'all'=>false)
            'border' => false,
            // custom merge rules (optional) : array of ['range' => 'A1:C1']
            'merge' => [],
            // limit auto-fit to these columns (optional keys)
            'autoWidthColumns' => [],
        ], $options);
    }

    /**
     * FromArray: returns plain array of rows (ordered by $this->columns keys)
     */
    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $item) {
            $row = [];
            foreach ($this->columns as $key => $label) {
                $row[] = $item[$key] ?? null;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Headings used by WithHeadings (will be shifted down if headerRows inserted)
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Map data key to column index (1-based) and letter
     */
    protected function keyToColumnIndex(string $key): ?int
    {
        $keys = array_keys($this->columns);
        $pos = array_search($key, $keys, true);
        if ($pos === false) return null;
        return $pos + 1; // 1-based
    }

    protected function keyToColumnLetter(string $key): ?string
    {
        $idx = $this->keyToColumnIndex($key);
        if (!$idx) return null;
        return Coordinate::stringFromColumnIndex($idx);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;
                $worksheet = $sheet->getDelegate();

                $columnCount = count($this->columns);
                $lastColLetter = Coordinate::stringFromColumnIndex($columnCount);

                // ---------------------------
                // Insert headerRows BEFORE current headings (headings are initially on row 1)
                // ---------------------------
                if (!empty($this->options['headerRows'])) {
                    $this->headerRowCount = count($this->options['headerRows']);
                    // insert empty rows at top so current headings move down
                    $worksheet->insertNewRowBefore(1, $this->headerRowCount);

                    // write header rows
                    foreach ($this->options['headerRows'] as $i => $headerRow) {
                        $rowNum = $i + 1;
                        foreach ($headerRow as $colIndex => $value) {
                            $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                            $worksheet->setCellValue("{$col}{$rowNum}", $value);
                        }
                        // auto-merge if headerRow shorter than total columns and subsequent values empty
                        if (count($headerRow) < $columnCount) {
                            $worksheet->mergeCells("A{$rowNum}:{$lastColLetter}{$rowNum}");
                        } else {
                            // also merge contiguous empty cells to the right if headerRow contains '' placeholders
                            $start = null;
                            for ($c = 1; $c <= $columnCount; $c++) {
                                $val = $headerRow[$c - 1] ?? null;
                                if ($val === '') {
                                    if ($start === null) $start = $c;
                                } else {
                                    if ($start !== null) {
                                        $from = Coordinate::stringFromColumnIndex($start);
                                        $to = Coordinate::stringFromColumnIndex($c - 1);
                                        $worksheet->mergeCells("{$from}{$rowNum}:{$to}{$rowNum}");
                                        $start = null;
                                    }
                                }
                            }
                            if ($start !== null) {
                                $from = Coordinate::stringFromColumnIndex($start);
                                $to = $lastColLetter;
                                $worksheet->mergeCells("{$from}{$rowNum}:{$to}{$rowNum}");
                            }
                        }
                    }
                } else {
                    $this->headerRowCount = 0;
                }

                // Data headings row is now at row = headerRowCount + 1
                $headingRowNum = $this->headerRowCount + 1;
                // Data rows start at headingRowNum + 1
                $startDataRow = $headingRowNum + 1;
                $endDataRow = $startDataRow + count($this->data) - 1;
                if ($endDataRow < $startDataRow) $endDataRow = $startDataRow; // handle no data

                // ---------------------------
                // Freeze header
                // ---------------------------
                if (!empty($this->options['freezeHeader'])) {
                    $freezeAt = $startDataRow;
                    $worksheet->freezePane("A{$freezeAt}");
                }

                // ---------------------------
                // Auto-filter
                // ---------------------------
                if (!empty($this->options['filter'])) {
                    $worksheet->setAutoFilter("A{$headingRowNum}:{$lastColLetter}{$headingRowNum}");
                }

                // ---------------------------
                // Global styles
                // ---------------------------
                if (!empty($this->options['styles'])) {
                    $worksheet->getStyle("A1:{$lastColLetter}{$endDataRow}")->applyFromArray($this->options['styles']);
                }

                // ---------------------------
                // Borders
                // ---------------------------
                if (!empty($this->options['border'])) {
                    $borderAll = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ];

                    if ($this->options['border'] === true || !empty($this->options['border']['all'])) {
                        $worksheet->getStyle("A{$headingRowNum}:{$lastColLetter}{$endDataRow}")->applyFromArray($borderAll);
                    } else {
                        if (!empty($this->options['border']['onlyHeader'])) {
                            $worksheet->getStyle("A{$headingRowNum}:{$lastColLetter}{$headingRowNum}")->applyFromArray($borderAll);
                        }
                        if (!empty($this->options['border']['onlyData'])) {
                            $worksheet->getStyle("A{$startDataRow}:{$lastColLetter}{$endDataRow}")->applyFromArray($borderAll);
                        }
                    }
                }

                // ---------------------------
                // Merge custom ranges if provided
                // ---------------------------
                if (!empty($this->options['merge']) && is_array($this->options['merge'])) {
                    foreach ($this->options['merge'] as $range) {
                        // range example: 'A1:C1'
                        $worksheet->mergeCells($range);
                    }
                }

                // ---------------------------
                // Row styles (relative to data start)
                // ---------------------------
                if (!empty($this->options['rowStyles'])) {
                    foreach ($this->options['rowStyles'] as $relativeRow => $style) {
                        $actual = $startDataRow + ($relativeRow - 1);
                        $worksheet->getStyle("A{$actual}:{$lastColLetter}{$actual}")->applyFromArray($style);
                    }
                }

                // ---------------------------
                // Conditional formatting per-row (apply style array to full row if condition true)
                // ---------------------------
                if (!empty($this->options['conditional'])) {
                    foreach ($this->data as $index => $row) {
                        $actualRow = $startDataRow + $index;
                        foreach ($this->options['conditional'] as $rule) {
                            $colKey = $rule['column'];
                            $value = $row[$colKey] ?? null;
                            try {
                                $ok = ($rule['condition'])($value);
                            } catch (\Throwable $e) {
                                $ok = false;
                            }
                            if ($ok) {
                                $worksheet->getStyle("A{$actualRow}:{$lastColLetter}{$actualRow}")->applyFromArray($rule['style']);
                            }
                        }
                    }
                }

                // ---------------------------
                // Number & Date formatting (manual mapping by key)
                // ---------------------------
                // numberFormat: ['nilai_kontrak' => '#,##0']
                if (!empty($this->options['numberFormat'])) {
                    foreach ($this->options['numberFormat'] as $key => $format) {
                        $colIndex = $this->keyToColumnIndex($key);
                        if (!$colIndex) continue;
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $range = "{$colLetter}{$startDataRow}:{$colLetter}{$endDataRow}";
                        $worksheet->getStyle($range)->getNumberFormat()->setFormatCode($format);
                    }
                }

                if (!empty($this->options['dateFormat'])) {
                    foreach ($this->options['dateFormat'] as $key => $format) {
                        $colIndex = $this->keyToColumnIndex($key);
                        if (!$colIndex) continue;
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $range = "{$colLetter}{$startDataRow}:{$colLetter}{$endDataRow}";
                        // Accept either format string or constant; user may pass 'dd-mm-yyyy'
                        $excelFormat = $format;
                        $worksheet->getStyle($range)->getNumberFormat()->setFormatCode($excelFormat);
                    }
                }

                // ---------------------------
                // Column width manual mapping (A => 20 or key => 30)
                // ---------------------------
                if (!empty($this->options['columnWidth'])) {
                    foreach ($this->options['columnWidth'] as $col => $width) {
                        if (ctype_alpha(str_replace('$','',$col))) {
                            // assume letter like 'A' or 'AA'
                            $worksheet->getColumnDimension(strtoupper($col))->setWidth((float)$width);
                        } else {
                            // assume key
                            $colLetter = $this->keyToColumnLetter($col);
                            if ($colLetter) $worksheet->getColumnDimension($colLetter)->setWidth((float)$width);
                        }
                    }
                }

                // ---------------------------
                // Auto-width behavior: combine ShouldAutoSize (already active) + custom fit
                // ---------------------------
                if (!empty($this->options['autoWidth'])) {
                    // Optionally restrict to certain columns
                    $onlyCols = $this->options['autoWidthColumns'] ?? [];

                    // include headings length + data length
                    // compute max length for each column (in characters)
                    $keys = array_keys($this->columns);
                    foreach ($keys as $i => $key) {
                        $colIndex = $i + 1;
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        if (!empty($onlyCols) && !in_array($key, $onlyCols) && !in_array($colLetter, $onlyCols)) {
                            continue;
                        }

                        $maxLen = mb_strlen((string)$this->columns[$key]);

                        // check headerRows values in same column if present
                        if (!empty($this->options['headerRows'])) {
                            foreach ($this->options['headerRows'] as $hr) {
                                $val = $hr[$i] ?? null;
                                if ($val !== null && $val !== '') {
                                    $maxLen = max($maxLen, mb_strlen((string)$val));
                                }
                            }
                        }

                        // check each data item for this key
                        foreach ($this->data as $row) {
                            $cellVal = $row[$key] ?? '';
                            // format numbers as strings for length calc (thousand separators omitted here)
                            $len = mb_strlen((string)$cellVal);
                            if ($len > $maxLen) $maxLen = $len;
                        }

                        // convert char length to width (approximation). tweak factor if needed.
                        $calculated = max(8, min($this->options['autoWidthMax'], ($maxLen * 1.2)));
                        $worksheet->getColumnDimension($colLetter)->setWidth($calculated);
                    }
                }

                // ---------------------------
                // Final tidy: if no autoWidth and no manual width, ShouldAutoSize will handle because of interface
                // ---------------------------

                // done
            },
        ];
    }
}
