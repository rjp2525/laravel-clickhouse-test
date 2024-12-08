<?php

namespace App\Http\Controllers;

use App\Services\ReportService;

class ReportController extends Controller
{
    public function __invoke(ReportService $reportService)
    {
        ini_set('max_execution_time', 86400);

        return response()->json($reportService->generateReport());
    }
}
