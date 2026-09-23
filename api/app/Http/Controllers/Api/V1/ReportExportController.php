<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportExportDataService;
use App\Services\SimpleXlsxWriter;
use App\Support\TypeValue;
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
        $title = TypeValue::string($title);
        $headers = $this->headerRow($headers);
        $rows = $this->exportRows($rows);
        $path = $writer->write($title, $headers, $rows);
        $filename = str($title)->lower()->replace(' ', '-')->append('-'.now()->format('Ymd-His').'.xlsx')->toString();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array<int, string>
     */
    private function headerRow(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $header): string => TypeValue::string($header),
            $headers,
        ));
    }

    /**
     * @return iterable<int, array<int, bool|float|int|string|null>>
     */
    private function exportRows(mixed $rows): iterable
    {
        if (! is_iterable($rows)) {
            return;
        }

        foreach ($rows as $row) {
            yield is_array($row)
                ? array_values(array_map(fn (mixed $value): bool|float|int|string|null => $this->cellValue($value), $row))
                : [];
        }
    }

    private function cellValue(mixed $value): bool|float|int|string|null
    {
        return is_bool($value) || is_float($value) || is_int($value) || is_string($value) || $value === null
            ? $value
            : TypeValue::string($value);
    }
}
