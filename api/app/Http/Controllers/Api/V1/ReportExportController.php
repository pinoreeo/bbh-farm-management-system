<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportExportDataService;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $report,
        SimpleXlsxWriter $writer,
        ReportExportDataService $exports
    ): BinaryFileResponse {
        [$title, $headers, $rows] = $exports->data($report, $request);
        $path = $writer->write($title, $headers, $rows);
        $filename = str($title)->lower()->replace(' ', '-')->append('-'.now()->format('Ymd-His').'.xlsx')->toString();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
