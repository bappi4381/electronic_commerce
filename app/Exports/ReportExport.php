<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exports any ReportService result (columns + rows).
 */
class ReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(private array $report, private string $title)
    {
    }

    public function array(): array
    {
        $keys = array_keys($this->report['columns']);

        return array_map(fn (array $row) => array_map(fn ($key) => $row[$key] ?? null, $keys), $this->report['rows']);
    }

    public function headings(): array
    {
        return array_values($this->report['columns']);
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}
