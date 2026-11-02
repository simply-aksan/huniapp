<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ReportFilterRequest;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Display print-ready HTML report view.
     */
    public function print(ReportFilterRequest $request, ReportService $reportService): View
    {
        /** @var User $owner */
        $owner = $request->user();

        $data = $reportService->getReportData($owner, $request->validated());

        return view('owner.reports.print', $data);
    }

    /**
     * Stream CSV export download.
     */
    public function export(ReportFilterRequest $request, ReportService $reportService): StreamedResponse
    {
        /** @var User $owner */
        $owner = $request->user();

        return $reportService->streamCsv($owner, $request->validated());
    }
}
