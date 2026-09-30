<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminDownloadResponse;
use App\Support\AdminResourceViewData;
use App\Support\AdminTableViewData;
use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminResourceController extends Controller
{
    public function index(Request $request, string $resource, AdminTableViewData $pageData)
    {
        [$title, $subtitle, $columns] = $this->page($resource);
        $listing = $pageData->browse($resource, session('bbh_api_token'));
        $records = $listing['records'];
        $filterYears = $listing['filterYears'];
        $filterMonths = $listing['filterMonths'];
        $periodFemaleCounts = $resource === 'breeding-periods'
            ? collect($records->items())->mapWithKeys(fn ($record) => [
                (string) $record['id'] => (int) data_get($record, 'raw.females_count', 0),
            ])->all()
            : [];

        if ($resource === 'rsa-keys') {
            return view('pages.admin.rsa-keys', [
                'slug' => $resource,
                'title' => $title,
                'subtitle' => $subtitle,
                'columns' => $columns,
                'records' => $records,
                'apiFailureMessage' => $pageData->failureMessage(),
                'dataTruncated' => $pageData->isTruncated(),
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
            'periodFemaleCounts' => $periodFemaleCounts,
            'apiFailureMessage' => $pageData->failureMessage(),
            'dataTruncated' => $pageData->isTruncated(),
        ]);
    }

    public function create(string $resource, AdminResourceViewData $resources)
    {
        [$title, $subtitle, $columns] = $this->page($resource);

        if ($resource === 'pregnancy-checks') {
            $token = $this->token();
            $periodId = request()->integer('period_id') ?: null;
            $femaleAnimalId = request()->integer('female_animal_id') ?: null;
            if ($periodId === null || $femaleAnimalId === null) {
                return redirect()->route('admin.pregnancy-checks')
                    ->withErrors(['form' => 'Pilih betina dari rincian periode sebelum mencatat pemeriksaan.']);
            }

            $context = $resources->pregnancyFormContext($periodId, $femaleAnimalId, $token);
            if (empty($context['breeding_period']) || empty($context['female_animal'])) {
                return redirect()->route('admin.pregnancy-checks')
                    ->withErrors(['form' => 'Data periode atau betina belum dapat dimuat. Silakan coba lagi.']);
            }

            return view('pages.admin.pregnancy-form', [
                'id' => null,
                'mode' => 'create',
                'values' => $context,
            ]);
        }

        if ($resource === 'users') {
            return view('pages.admin.user-invitation', [
                'pageTitle' => 'Tambah Admin',
            ]);
        }

        $selectedPeriodId = $resource === 'breeding-females' ? request()->integer('period_id') : null;

        return view('pages.admin.form', [
            'slug' => $resource,
            'pageTitle' => match ($resource) {
                'certificates' => 'Terbitkan Sertifikat',
                'users' => 'Tambah Admin',
                'breeding-females' => $selectedPeriodId ? 'Masukkan Betina ke Periode' : 'Masukkan Betina ke Periode Kawin',
                default => 'Tambah '.$title,
            },
            'collectionTitle' => $title,
            'subtitle' => $selectedPeriodId
                ? 'Pilih satu atau beberapa betina untuk periode kawin yang sudah dipilih.'
                : $subtitle,
            'fields' => $resources->fields($resource, $this->form($resource, $columns), session('bbh_api_token')),
            'values' => $selectedPeriodId ? ['breeding_period_id' => (string) $selectedPeriodId] : [],
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, string $resource, AdminResourceViewData $resources)
    {
        $this->page($resource);
        $response = $resources->store($resource, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Data belum berhasil disimpan. Silakan coba lagi.'),
            ]);
        }

        if ($resource === 'pregnancy-checks') {
            $periodId = (int) ($request->input('breeding_period_id') ?: $response->json('data.breeding_period_id'));

            return redirect()
                ->route('admin.resource.show', ['resource' => 'pregnancy-checks', 'id' => $periodId])
                ->with('formMessage', $resources->successMessage($resource, 'create'));
        }

        if ($resource === 'animals') {
            $tag = trim((string) data_get($response->json(), 'data.tag_number'));

            if ($tag !== '') {
                return redirect()
                    ->route('admin.animals.show', ['tag' => $tag])
                    ->with('formMessage', $resources->successMessage($resource, 'create'));
            }
        }

        $message = $resource === 'users'
            ? 'Sukses: Undangan untuk membuat password telah dikirim ke email admin.'
            : $resources->successMessage($resource, 'create');

        return redirect()->route('admin.'.$resource)
            ->with('formMessage', $message);
    }

    public function update(Request $request, string $resource, int $id, AdminResourceViewData $resources)
    {
        $this->page($resource);
        $response = $resources->update($resource, $id, $request->all(), $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Data belum berhasil disimpan. Silakan coba lagi.'),
            ]);
        }

        if ($resource === 'pregnancy-checks') {
            $periodId = (int) ($request->input('breeding_period_id') ?: $response->json('data.breeding_period_id'));

            return redirect()
                ->route('admin.resource.show', ['resource' => 'pregnancy-checks', 'id' => $periodId])
                ->with('formMessage', $resources->successMessage($resource, 'update'));
        }

        if ($resource === 'animals') {
            $animal = $resources->item('animals', $id, $this->token());
            $tag = trim((string) data_get($animal, 'tag_number'));

            if ($tag !== '') {
                return redirect()
                    ->route('admin.animals.show', ['tag' => $tag])
                    ->with('formMessage', $resources->successMessage($resource, 'update'));
            }
        }

        return redirect()->route('admin.'.$resource)
            ->with('formMessage', $resources->successMessage($resource, 'update'));
    }

    public function sendUserPasswordResetLink(int $id, AdminResourceViewData $resources)
    {
        $this->page('users');
        $response = $resources->sendUserPasswordResetLink($id, $this->token());

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'form' => $resources->failureMessages($response, 'Gagal: Tautan reset password tidak dapat dikirim.'),
            ]);
        }

        return back()->with('formMessage', 'Sukses: Tautan reset password telah dikirim ke email admin.');
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
                'form' => $resources->failureMessages($response, 'Gagal: Tindakan belum berhasil diproses. Silakan coba lagi.'),
            ]);
        }

        return back()->with('formMessage', $resources->successMessage($resource, $action));
    }

    public function show(string $resource, int $id, AdminTableViewData $pageData, AdminResourceViewData $resources)
    {
        [$title, $subtitle, $columns] = $this->page($resource);

        if (in_array($resource, ['pregnancy-checks', 'breeding-periods'], true)) {
            $breedingPeriod = $resources->breedingPeriodContext($id, $this->token());
            abort_if($breedingPeriod === [], 404);

            if ($resource === 'breeding-periods') {
                return view('pages.admin.breeding-period-show', [
                    'id' => $id,
                    'breedingPeriod' => $breedingPeriod,
                    'history' => $this->isSuperAdmin() ? $resources->activityHistory($resource, $id, $this->token()) : [],
                ]);
            }

            return view('pages.admin.pregnancy-show', [
                'id' => $id,
                'pregnancyPeriod' => $breedingPeriod,
            ]);
        }

        if ($resource === 'animals') {
            $animal = $resources->item('animals', $id, $this->token());
            abort_if($animal === [], 404);
            $tag = trim((string) data_get($animal, 'tag_number'));
            abort_if($tag === '', 404);

            return redirect()->route('admin.animals.show', ['tag' => $tag]);
        }

        $item = $resources->item($resource, $id, $this->token());
        abort_if($item === [], 404);
        $record = $pageData->recordFromItem($resource, $item);
        $row = $record['cells'];

        if ($resource === 'certificates') {
            return view('pages.admin.certificate-preview', [
                'id' => $id,
                'row' => $row,
            ]);
        }

        return view('pages.admin.show', [
            'slug' => $resource,
            'id' => $id,
            'pageTitle' => (string) ($row[0] ?? $title),
            'collectionTitle' => $title,
            'recordTitle' => (string) ($row[0] ?? $title),
            'subtitle' => $subtitle,
            'columns' => $columns,
            'row' => $row,
            'history' => $this->isSuperAdmin() ? $resources->activityHistory($resource, $id, $this->token()) : [],
        ]);
    }

    public function edit(string $resource, int $id, AdminResourceViewData $resources, AdminTableViewData $pageData)
    {
        [$title, $subtitle, $columns] = $this->page($resource);

        if ($resource === 'pregnancy-checks') {
            $values = $resources->item('pregnancy-checks', $id, $this->token());
            abort_if($values === [], 404);

            return view('pages.admin.pregnancy-form', [
                'id' => $id,
                'mode' => 'edit',
                'values' => $values,
            ]);
        }

        $values = $resources->item($resource, $id, $this->token());
        abort_if($values === [], 404);

        if ($resource === 'animals') {
            $tag = trim((string) data_get($values, 'tag_number'));
            abort_if($tag === '', 404);

            return redirect()->route('admin.animals.edit', ['tag' => $tag]);
        }

        if ($resource === 'users' && ! data_get($values, 'first_name')) {
            $parts = preg_split('/\s+/', trim((string) data_get($values, 'name', '')), 2);
            $values['first_name'] = $parts[0] ?? '';
            $values['last_name'] = $parts[1] ?? '';
        }

        if ($resource === 'users') {
            return view('pages.admin.user-edit', [
                'id' => $id,
                'user' => $values,
                'pageTitle' => 'Edit '.(string) data_get($values, 'name', 'Admin'),
                'recordTitle' => (string) data_get($values, 'name', 'Admin'),
            ]);
        }

        $record = $pageData->recordFromItem($resource, $values);
        $recordTitle = (string) ($record['cells'][0] ?? $title);

        return view('pages.admin.form', [
            'slug' => $resource,
            'pageTitle' => 'Edit '.$recordTitle,
            'collectionTitle' => $title,
            'recordTitle' => $recordTitle,
            'subtitle' => $subtitle,
            'fields' => $resources->fields($resource, $this->form($resource, $columns), session('bbh_api_token'), $values),
            'values' => $values,
            'id' => $id,
            'mode' => 'edit',
        ]);
    }

    public function showAnimalByTag(string $tag, AdminResourceViewData $resources)
    {
        $animal = $resources->animalByTag($tag, $this->token());
        abort_if($animal === [], 404);

        return $this->animalShowView($animal, $resources);
    }

    public function editAnimalByTag(string $tag, AdminResourceViewData $resources, AdminTableViewData $pageData)
    {
        $values = $resources->animalByTag($tag, $this->token());
        abort_if($values === [], 404);

        return $this->animalEditView($values, $resources, $pageData);
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
                'download' => 'Gagal: Laporan XLSX belum dapat diunduh. Silakan coba lagi.',
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
                'form' => $resources->failureMessages($response, 'Gagal: Catatan keluar betina belum berhasil disimpan. Silakan coba lagi.'),
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
        abort_if(in_array($resource, ['users', 'activity-logs'], true) && ! $this->isSuperAdmin(), 403);

        return $pages[$resource];
    }

    /**
     * @param  array<string, mixed>  $animal
     */
    private function animalShowView(array $animal, AdminResourceViewData $resources)
    {
        $id = (int) data_get($animal, 'id');
        abort_if($id <= 0, 404);

        return view('pages.admin.animal-show', [
            'id' => $id,
            'animal' => $animal,
            'history' => $this->isSuperAdmin() ? $resources->activityHistory('animals', $id, $this->token()) : [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function animalEditView(array $values, AdminResourceViewData $resources, AdminTableViewData $pageData)
    {
        [$title, $subtitle, $columns] = $this->page('animals');
        $id = (int) data_get($values, 'id');
        abort_if($id <= 0, 404);
        $record = $pageData->recordFromItem('animals', $values);
        $recordTitle = (string) ($record['cells'][0] ?? $title);

        return view('pages.admin.form', [
            'slug' => 'animals',
            'pageTitle' => 'Edit '.$recordTitle,
            'collectionTitle' => $title,
            'recordTitle' => $recordTitle,
            'subtitle' => $subtitle,
            'fields' => $resources->fields('animals', $this->form('animals', $columns), session('bbh_api_token')),
            'values' => $values,
            'id' => $id,
            'mode' => 'edit',
        ]);
    }

    private function isSuperAdmin(): bool
    {
        return (session('bbh_admin_user.role') ?? null) === 'super_admin';
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

    private function apiErrorMessage($response, string $fallback): string
    {
        $message = $response->json('message') ?? $fallback;
        $errors = $response->json('errors');

        if (is_array($errors)) {
            $first = collect($errors)->flatten()->first();
            $message = is_string($first) ? $first : $message;
        }

        return is_string($message) ? $message : $fallback;
    }

}
