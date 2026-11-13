<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const EXPORT_TYPES = ['xlsx' => \Maatwebsite\Excel\Excel::XLSX, 'csv' => \Maatwebsite\Excel\Excel::CSV];

    public function __construct(private ReportService $reports)
    {
    }

    public function index(Request $request)
    {
        [$report, $from, $to, $filters] = $this->filters($request);

        return view('admin.reports.index', [
            'reports' => ReportService::REPORTS,
            'report'  => $report,
            'undated' => in_array($report, ReportService::UNDATED, true),
            'filters' => $filters,
            'result'  => $this->reports->run($report, $from, $to),
        ]);
    }

    public function export(Request $request, string $type)
    {
        abort_unless(isset(self::EXPORT_TYPES[$type]), 404);

        [$report, $from, $to] = $this->filters($request);
        $title = ReportService::REPORTS[$report];

        $fileName = $report . '_report_'
            . (in_array($report, ReportService::UNDATED, true) ? now()->format('Y_m_d') : $from->format('Y_m_d') . '_to_' . $to->format('Y_m_d'))
            . '.' . $type;

        return Excel::download(new ReportExport($this->reports->run($report, $from, $to), $title), $fileName, self::EXPORT_TYPES[$type]);
    }

    /**
     * @return array{0: string, 1: CarbonImmutable, 2: CarbonImmutable, 3: array}
     */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'report'    => 'nullable|in:' . implode(',', array_keys(ReportService::REPORTS)),
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $to = isset($data['to_date']) ? CarbonImmutable::parse($data['to_date'])->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = isset($data['from_date']) ? CarbonImmutable::parse($data['from_date'])->startOfDay() : $to->subDays(29)->startOfDay();

        // Keep the daily breakdown bounded.
        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(366)->startOfDay();
        }

        $report = $data['report'] ?? 'sales';

        return [$report, $from, $to, ['report' => $report, 'from_date' => $from->toDateString(), 'to_date' => $to->toDateString()]];
    }
}
