<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReportRequest;
use App\Services\AdminReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly AdminReportService $reports,
    ) {}

    public function index(AdminReportRequest $request): View
    {
        return view('admin.Reports', [
            'report' => $this->reports->getReport($request),
        ]);
    }

    public function export(AdminReportRequest $request): StreamedResponse
    {
        $report = $this->reports->getReport($request);
        $filename = 'MartHire-report-'.$report['dateRange']->start->toDateString().'-'.$report['dateRange']->end->toDateString().'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            foreach ($this->reports->csvRows($report) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
