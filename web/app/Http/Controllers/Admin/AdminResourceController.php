<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminDownloadResponse;
use App\Support\AdminResourceViewData;
use App\Support\AdminTableViewData;
use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AdminResourceController extends Controller
{
    public function index(string $resource, AdminTableViewData $pageData)
    {
        [$title, $subtitle, $columns, $rows] = $this->page($resource);
        $records = $pageData->records($resource, $rows, session('bbh_api_token'));
        $filterYears = [];
        $filterMonths = [];

        if (in_array($resource, ['certificate-logs', 'activity-logs'], true)) {
            [$records, $filterYears, $filterMonths] = $this->filterLogRecords($records);
        }

        if ($resource === 'rsa-keys') {
            return view('pages.admin.rsa-keys', [
                'slug' => $resource,
                'title' => $title,
                'subtitle' => $subtitle,
                'columns' => $columns,
                'records' => $records,
            ]);
        }

        return view('pages.admin.table', [
            'slug' => $resource,
            'title' => $title,
            'subtitle' => $subtitle,
            'columns' => $columns,
            'records' => $records,
            'filterYears' => $filterYears,
            'filterMonths' => $filterMonths,
        ]);
    }

    public function create(string $resource, AdminResourceViewData $resources)
    {
        [$title, $subtitle, $columns] = $this->page($resource);

        if ($resource === 'pregnancy-checks') {
            $token = $this->token();
            $periodId = request()->integer('period_id') ?: null;
            $femaleAnimalId = request()->integer('female_animal_id') ?: null;

            return view('pages.admin.pregnancy-form', [
                'id' => null,
                'mode' => 'create',
                'values' => $resources->pregnancyFormContext($periodId, $femaleAnimalId, $token),
            ]);
        }

        return view('pages.admin.form', [
            'slug' => $resource,
            'pageTitle' => match ($resource) {
                'certificates' => 'Terbitkan Sertifikat',
                'users' => 'Tambah Admin',
                default => 'Tambah ' . $title,
            },
            'subtitle' => $subtitle,
            'fields' => $resources->fields($resource, $this->form($resource, $columns), session('bbh_api_token')),
            'values' => [],
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, string $resource, AdminResourceViewData $resources)
    {
        $this->page($resource);
        $response = $resources->store($resource, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Data gagal disimpan. Periksa kembali kelengkapan formulir Anda.'),
            ]);
        }

        if ($resource === 'pregnancy-checks') {
            $periodId = (int) ($request->input('breeding_period_id') ?: $response->json('data.breeding_period_id'));

            return redirect()
                ->route('admin.resource.show', ['resource' => 'pregnancy-checks', 'id' => $periodId])
                ->with('formMessage', $resources->successMessage($resource, 'create'));
        }

        return redirect()->route('admin.'.$resource)
            ->with('formMessage', $resources->successMessage($resource, 'create'));
    }

    public function update(Request $request, string $resource, int $id, AdminResourceViewData $resources)
    {
        $this->page($resource);
        $response = $resources->update($resource, $id, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Gagal menyimpan perubahan. Periksa kembali data yang Anda isi.'),
            ]);
        }

        if ($resource === 'pregnancy-checks') {
            $periodId = (int) ($request->input('breeding_period_id') ?: $response->json('data.breeding_period_id'));

            return redirect()
                ->route('admin.resource.show', ['resource' => 'pregnancy-checks', 'id' => $periodId])
                ->with('formMessage', $resources->successMessage($resource, 'update'));
        }

        return redirect()->route('admin.'.$resource)
            ->with('formMessage', $resources->successMessage($resource, 'update'));
    }

    public function action(string $resource, int $id, string $action, AdminResourceViewData $resources)
    {
        $response = match ([$resource, $action]) {
            ['certificates', 'revoke'] => $resources->revokeCertificate($id, $this->token()),
            ['certificates', 'unrevoke'] => $resources->unrevokeCertificate($id, $this->token()),
            ['rsa-keys', 'activate'] => $resources->activateRsaKey($id, $this->token()),
            ['rsa-keys', 'deactivate'] => $resources->deactivateRsaKey($id, $this->token()),
            ['rsa-keys', 'compromise'] => $resources->compromiseRsaKey($id, $this->token()),
            default => abort(404),
        };

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Tindakan gagal diproses. Periksa kembali validitas data terkait.'),
            ]);
        }

        return back()->with('formMessage', $resources->successMessage($resource, $action));
    }

    public function show(string $resource, int $id, AdminTableViewData $pageData)
    {
        [$title, $subtitle, $columns, $rows] = $this->page($resource);
        $record = collect($pageData->records($resource, $rows, session('bbh_api_token')))->firstWhere('id', $id);
        abort_if($record === null, 404);
        $row = $record['cells'] ?? ($rows[max(0, min(count($rows) - 1, $id - 1))] ?? []);

        if ($resource === 'pregnancy-checks') {
            $resources = app(AdminResourceViewData::class);
            $pregnancyPeriod = $resources->pregnancyPeriod($id, $this->token());
            abort_if($pregnancyPeriod === [], 404);

            return view('pages.admin.pregnancy-show', [
                'id' => $id,
                'pregnancyPeriod' => $pregnancyPeriod,
            ]);
        }

        if ($resource === 'certificates') {
            return view('pages.admin.certificate-preview', [
                'id' => $id,
                'row' => $row,
            ]);
        }

        if ($resource === 'animals') {
            $resources = app(AdminResourceViewData::class);
            $animal = $resources->item('animals', $id, $this->token());
            abort_if($animal === [], 404);

            return view('pages.admin.animal-show', [
                'id' => $id,
                'animal' => $animal,
            ]);
        }

        return view('pages.admin.show', [
            'slug' => $resource,
            'id' => $id,
            'pageTitle' => $resource === 'users' ? 'Detail Admin' : 'Detail ' . $title,
            'subtitle' => $subtitle,
            'columns' => $columns,
            'row' => $row,
        ]);
    }

    public function edit(string $resource, int $id, AdminTableViewData $pageData, AdminResourceViewData $resources)
    {
        [$title, $subtitle, $columns, $rows] = $this->page($resource);
        $record = collect($pageData->records($resource, $rows, session('bbh_api_token')))->firstWhere('id', $id);

        if ($resource === 'pregnancy-checks') {
            $values = $resources->item('pregnancy-checks', $id, $this->token());
            abort_if($values === [], 404);

            return view('pages.admin.pregnancy-form', [
                'id' => $id,
                'mode' => 'edit',
                'values' => $values,
            ]);
        }

        abort_if($record === null, 404);
        $values = $record['raw'] ?? [];

        if ($resource === 'users' && ! data_get($values, 'first_name')) {
            $parts = preg_split('/\s+/', trim((string) data_get($values, 'name', '')), 2);
            $values['first_name'] = $parts[0] ?? '';
            $values['last_name'] = $parts[1] ?? '';
        }

        return view('pages.admin.form', [
            'slug' => $resource,
            'pageTitle' => $resource === 'users' ? 'Edit Admin' : 'Edit ' . $title,
            'subtitle' => $subtitle,
            'fields' => $resources->fields($resource, $this->form($resource, $columns), session('bbh_api_token')),
            'values' => $values,
            'id' => $id,
            'mode' => 'edit',
        ]);
    }

    public function previewCertificate(int $id, BbhApiClient $api, AdminDownloadResponse $downloads)
    {
        $response = $api->get("certificates/{$id}/preview", [], $this->token());

        if (! $response->successful()) {
            return response(
                view('pages.admin.certificate-preview-error', [
                    'message' => $downloads->apiFailureMessage($response, 'Gagal: Preview sertifikat gagal ditampilkan.'),
                ])->render(),
                $response->status()
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response($response->body(), 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function downloadCertificate(int $id, BbhApiClient $api, AdminDownloadResponse $downloads)
    {
        $token = $this->token();
        $certificate = $api->get("certificates/{$id}", [], $token);
        $response = $api->get("certificates/{$id}/pdf", [], $token);

        if (! $response->successful()) {
            return redirect()
                ->route('admin.resource.show', ['resource' => 'certificates', 'id' => $id])
                ->withErrors([
                    'download' => $downloads->apiFailureMessage($response, 'Gagal: PDF sertifikat gagal diunduh.'),
                ]);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$downloads->certificateFilename($certificate->successful() ? $certificate->json() : []).'"',
        ]);
    }

    public function downloadReport(Request $request, string $report, BbhApiClient $api, AdminDownloadResponse $downloads)
    {
        $response = $api->get("reports/{$report}/xlsx", $request->only(['date_from', 'date_to']), $this->token());

        if (! $response->successful()) {
            return back()->withErrors([
                'download' => 'Gagal: Laporan XLSX gagal diunduh. Periksa koneksi API lalu coba lagi.',
            ]);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$downloads->reportFilename($report).'"',
        ]);
    }

    public function exitBreedingFemaleForm(int $id, AdminResourceViewData $resources)
    {
        $context = $resources->breedingFemaleExitContext($id, $this->token());
        abort_if($context['breeding_female'] === [], 404);

        return view('pages.admin.breeding-female-exit', [
            'id' => $id,
            'context' => $context,
        ]);
    }

    public function exitBreedingFemale(Request $request, int $id, AdminResourceViewData $resources)
    {
        $response = $resources->exitBreedingFemale($id, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Betina gagal dikeluarkan dari periode kawin.'),
            ]);
        }

        return redirect()->route('admin.breeding-females')
            ->with('formMessage', $resources->successMessage('breeding-females', 'exit'));
    }

    public function matingBreedingFemaleForm(int $id, AdminResourceViewData $resources)
    {
        $context = $resources->breedingFemaleExitContext($id, $this->token());
        abort_if($context['breeding_female'] === [], 404);

        return view('pages.admin.breeding-female-mating', [
            'id' => $id,
            'context' => $context,
        ]);
    }

    public function matingBreedingFemale(Request $request, int $id, AdminResourceViewData $resources)
    {
        $response = $resources->recordBreedingFemaleMating($id, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Tanggal kawin gagal dicatat.'),
            ]);
        }

        return redirect()->route('admin.breeding-females')
            ->with('formMessage', $resources->successMessage('breeding-females', 'mating'));
    }

    /**
     * @return array{0:string,1:string,2:array<int,string>,3:array<int,array<int,string>>}
     */
    private function page(string $resource): array
    {
        $pages = config('admin.pages', []);
        abort_unless(isset($pages[$resource]), 404);

        return $pages[$resource];
    }

    private function form(string $resource, array $fallback): array
    {
        return config("admin.forms.{$resource}", $fallback);
    }

    private function token(): string
    {
        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        return $token;
    }

    private function filterLogRecords(array $records): array
    {
        $dates = collect($records)
            ->map(fn ($record) => $record['raw']['verification_time'] ?? $record['raw']['created_at'] ?? null)
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));

        $filterYears = $dates->map(fn (Carbon $date) => $date->year)->unique()->sortDesc()->values()->all();
        $selectedYear = request('year') ?: ($filterYears[0] ?? null);
        $filterMonths = $dates
            ->filter(fn (Carbon $date) => $selectedYear === null || $date->year === (int) $selectedYear)
            ->map(fn (Carbon $date) => $date->month)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $selectedMonth = request('month');
        $records = collect($records)
            ->filter(function ($record) use ($selectedYear, $selectedMonth) {
                $dateValue = $record['raw']['verification_time'] ?? $record['raw']['created_at'] ?? null;
                if (! $dateValue) {
                    return true;
                }

                $date = Carbon::parse($dateValue);

                return ($selectedYear === null || $date->year === (int) $selectedYear)
                    && ($selectedMonth === null || $selectedMonth === '' || $date->month === (int) $selectedMonth);
            })
            ->values()
            ->all();

        return [$records, $filterYears, $filterMonths];
    }

}
