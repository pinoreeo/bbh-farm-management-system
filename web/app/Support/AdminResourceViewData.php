<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class AdminResourceViewData
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $itemsCache = [];

    public function __construct(private readonly BbhApiClient $api) {}

    public function endpoint(string $slug): ?string
    {
        return [
            'users' => 'users',
            'animals' => 'animals',
            'weight-records' => 'weight-records',
            'pens' => 'colony-pens',
            'pen-movements' => 'animal-pen-movements',
            'breeding-periods' => 'breeding-periods',
            'breeding-females' => 'breeding-females',
            'pregnancy-checks' => 'pregnancy-checks',
            'birth-events' => 'birth-events',
            'offspring-births' => 'offspring-births',
            'health-treatments' => 'health-treatments',
            'vaccinations' => 'vaccinations',
            'postnatal-care' => 'postnatal-care-records',
            'certificates' => 'certificates',
            'certificate-logs' => 'certificate-verification-logs',
            'activity-logs' => 'admin-activity-logs',
            'rsa-keys' => 'rsa-keys',
        ][$slug] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function fields(string $slug, array $fields, ?string $token, ?array $values = null): array
    {
        if (! is_string($token) || $token === '') {
            return $fields;
        }

        return array_map(function ($field) use ($slug, $token, $values) {
            if (! is_array($field)) {
                return $field;
            }

            if ($slug === 'animals' && ($field['name'] ?? '') === 'life_status'
                && data_get($values, 'life_status') === 'dead') {
                $field['options'] = ['dead' => 'Mati'];
            }

            if ($slug === 'birth-events' && $values !== null && in_array($field['name'] ?? '', ['dam_id', 'sire_id'], true)) {
                $name = $field['name'];
                $relation = $name === 'dam_id' ? 'dam' : 'sire';
                $field['readonly'] = true;
                $field['options'] = [(string) ($values[$name] ?? '') => (string) data_get($values, $relation.'.tag_number', '-')];
                unset($field['depends_on'], $field['option_meta']);

                return $field;
            }

            $options = $this->optionsFor($slug, (string) ($field['name'] ?? ''), $token);
            if ($options !== null) {
                $field['options'] = $options;
            }

            if (($field['name'] ?? '') === 'certificate_type_id') {
                $deathTypeId = $this->certificateTypeId('KEMATIAN', $token);
                if ($deathTypeId !== null) {
                    $field['death_type_id'] = (string) $deathTypeId;
                }
            }

            if (Arr::get($field, 'show_when.certificate_type_id') === 'KEMATIAN') {
                $deathTypeId = $this->certificateTypeId('KEMATIAN', $token);
                if ($deathTypeId !== null) {
                    $field['show_when'] = ['certificate_type_id' => (string) $deathTypeId];
                }
            }

            if ($slug === 'certificates' && ($field['name'] ?? '') === 'animal_id') {
                $deathTypeId = $this->certificateTypeId('KEMATIAN', $token);
                if ($deathTypeId !== null) {
                    $field['option_meta'] = $this->animalLifeStatusMap($token);
                    $field['filter_dead_when'] = 'certificate_type_id';
                    $field['filter_dead_value'] = (string) $deathTypeId;
                }
            }

            if ($slug === 'postnatal-care' && ($field['name'] ?? '') === 'target_animal_id') {
                $field['option_meta'] = $this->offspringBirthEventMap($token);
                $field['depends_on'] = 'birth_event_id';
            }

            if ($slug === 'birth-events' && ($field['name'] ?? '') === 'sire_id') {
                $field['option_meta'] = $this->pregnantDamSireMap($token);
                $field['depends_on'] = 'dam_id';
                $field['readonly'] = true;
            }

            if ($slug === 'pen-movements' && ($field['name'] ?? '') === 'animal_id') {
                $field['option_meta'] = $this->animalCurrentPenMap($token);
            }

            return $field;
        }, $fields);
    }

    /**
     * @return array<string, string>|null
     */
    private function optionsFor(string $slug, string $name, string $token): ?array
    {
        if ($slug === 'postnatal-care' && $name === 'target_animal_id') {
            return $this->offspringAnimalOptions($token);
        }

        if ($slug === 'breeding-females' && $name === 'female_animal_ids') {
            return $this->animalOptions($token, 'female');
        }

        if ($slug === 'birth-events' && $name === 'dam_id') {
            return $this->pregnantDamOptions($token);
        }

        return match ($name) {
            'breed_id' => $this->options('breeds', 'breed_name', $token),
            'animal_id', 'offspring_animal_id', 'target_animal_id' => $this->options('animals', 'tag_number', $token),
            'female_animal_id', 'dam_id' => $this->animalOptions($token, 'female'),
            'male_animal_id', 'sire_id' => $this->animalOptions($token, 'male'),
            'colony_pen_id' => $this->options('colony-pens', 'pen_code', $token),
            'to_pen_id' => $this->options('colony-pens', 'pen_code', $token),
            'breeding_period_id' => $this->options('breeding-periods', 'period_code', $token),
            'birth_event_id' => $this->birthEventOptions($token),
            'certificate_type_id' => $this->options('certificate-types', 'type_name', $token),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function options(string $endpoint, string $labelKey, string $token): array
    {
        $items = $this->items($endpoint, $token);

        return collect($items)
            ->mapWithKeys(fn ($item) => [(string) $item['id'] => $this->optionLabel($endpoint, (string) Arr::get($item, $labelKey, $item['id']))])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function animalOptions(string $token, ?string $sex = null): array
    {
        $items = $this->items('animals', $token);

        return collect($items)
            ->filter(fn ($item) => $sex === null || Arr::get($item, 'sex') === $sex)
            ->mapWithKeys(fn ($item) => [(string) $item['id'] => (string) Arr::get($item, 'tag_number', $item['id'])])
            ->all();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function animalCurrentPenMap(string $token): array
    {
        return collect($this->items('animals', $token))
            ->mapWithKeys(fn ($item) => [
                (string) $item['id'] => [
                    'current_pen_label' => (string) (
                        Arr::get($item, 'current_pen.pen_code')
                        ?: Arr::get($item, 'current_pen.colony_name')
                        ?: 'Belum ada koloni aktif'
                    ),
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function birthEventOptions(string $token): array
    {
        return collect($this->items('birth-events', $token))
            ->mapWithKeys(fn ($item) => [
                (string) $item['id'] => trim(
                    substr((string) Arr::get($item, 'birth_date', '-'), 0, 10)
                    .' | Tag Induk: '.$this->tagLabel($item, 'dam')
                    .' | Tag Pejantan: '.$this->tagLabel($item, 'sire')
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function tagLabel(array $item, string $relation): string
    {
        $tag = Arr::get($item, "{$relation}.tag_number");

        return is_string($tag) && $tag !== '' ? $tag : 'Belum ada tag';
    }

    /**
     * @return array<string, string>
     */
    private function pregnantDamOptions(string $token): array
    {
        return collect($this->items('pregnancy-checks', $token))
            ->filter(fn ($item) => (bool) Arr::get($item, 'is_pregnant') && Arr::get($item, 'outcome_status') !== 'born')
            ->sortByDesc('check_date')
            ->unique('female_animal_id')
            ->mapWithKeys(fn ($item) => [
                (string) Arr::get($item, 'female_animal_id') => (string) Arr::get($item, 'female_animal.tag_number', Arr::get($item, 'female_animal_id')),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function pregnantDamSireMap(string $token): array
    {
        return collect($this->items('pregnancy-checks', $token))
            ->filter(fn ($item) => (bool) Arr::get($item, 'is_pregnant') && Arr::get($item, 'outcome_status') !== 'born')
            ->sortByDesc('check_date')
            ->unique('female_animal_id')
            ->mapWithKeys(fn ($item) => [
                (string) Arr::get($item, 'breeding_period.male_animal_id') => (string) Arr::get($item, 'female_animal_id'),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function offspringBirthEventMap(string $token): array
    {
        return collect($this->items('offspring-births', $token))
            ->mapWithKeys(fn ($item) => [
                (string) Arr::get($item, 'offspring_animal_id') => (string) Arr::get($item, 'birth_event_id'),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function offspringAnimalOptions(string $token): array
    {
        return collect($this->items('offspring-births', $token))
            ->mapWithKeys(fn ($item) => [
                (string) Arr::get($item, 'offspring_animal_id') => (string) Arr::get($item, 'offspring_animal.tag_number', Arr::get($item, 'offspring_animal_id')),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function animalLifeStatusMap(string $token): array
    {
        return collect($this->items('animals', $token))
            ->mapWithKeys(fn ($item) => [(string) $item['id'] => (string) Arr::get($item, 'life_status', '')])
            ->all();
    }

    private function certificateTypeId(string $typeCode, string $token): ?int
    {
        foreach ($this->items('certificate-types', $token) as $item) {
            if (Arr::get($item, 'type_code') === $typeCode) {
                return (int) $item['id'];
            }
        }

        return null;
    }

    private function optionLabel(string $endpoint, string $label): string
    {
        if ($endpoint === 'certificate-types' && str_contains(strtolower($label), 'keaslian')) {
            return 'Sertifikat Bibit Unggul';
        }

        return $label;
    }

    private function historyActionLabel(string $action): string
    {
        return match ($action) {
            'create' => 'Data dibuat',
            'update' => 'Data diperbarui',
            'activate' => 'Data diaktifkan',
            'deactivate' => 'Data dinonaktifkan',
            'revoke' => 'Data dicabut',
            'unrevoke' => 'Data diaktifkan kembali',
            'mating' => 'Tanggal kawin dicatat',
            'exit' => 'Data dikeluarkan',
            default => str($action)->replace('-', ' ')->title()->toString(),
        };
    }

    private function historyTimestamp(string $value): string
    {
        return $value === '' ? '-' : str_replace('T', ' ', substr($value, 0, 16));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(string $endpoint, string $token): array
    {
        $cacheKey = $endpoint.'|'.hash('sha256', $token);
        if (array_key_exists($cacheKey, $this->itemsCache)) {
            return $this->itemsCache[$cacheKey];
        }

        $query = [];

        if ($endpoint === 'breeding-periods') {
            $query['include_closed'] = 1;
        }

        if (in_array($endpoint, ['breeds', 'certificate-types', 'rsa-keys'], true)) {
            $query['include_inactive'] = 1;
        }

        $result = $this->api->paginatedBatchData([
            'items' => ['path' => $endpoint, 'query' => $query],
        ], $token, PHP_INT_MAX)['items'];

        return $this->itemsCache[$cacheKey] = $result['ok'] ? $result['data'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function item(string $slug, int $id, string $token): array
    {
        $endpoint = $this->endpoint($slug);
        if ($endpoint === null) {
            return [];
        }

        $response = $this->api->get("{$endpoint}/{$id}", [], $token);

        return $response->successful() && is_array($response->json())
            ? $response->json()
            : [];
    }

    /**
     * Resolve the public-facing animal eartag to its internal API record.
     *
     * @return array<string, mixed>
     */
    public function animalByTag(string $tag, string $token): array
    {
        $normalizedTag = trim($tag);
        if ($normalizedTag === '') {
            return [];
        }

        $result = $this->api->paginatedData('animals', ['tag_number' => $normalizedTag], $token, 1);
        $animal = collect($result['ok'] ? $result['data'] : [])
            ->first(fn ($item) => is_array($item)
                && strcasecmp(trim((string) Arr::get($item, 'tag_number')), $normalizedTag) === 0);

        return is_array($animal) ? $animal : [];
    }

    /**
     * @return array<int, array{action:string, description:string, admin:string, performed_at:string}>
     */
    public function activityHistory(string $slug, int $id, string $token): array
    {
        $module = $this->endpoint($slug);
        if ($module === null) {
            return [];
        }

        $response = $this->api->get('admin-activity-logs', [
            'module' => $module,
            'subject_id' => $id,
            'per_page' => 8,
        ], $token);

        if (! $response->successful()) {
            return [];
        }

        $items = $response->json('data', []);
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item) && (int) Arr::get($item, 'subject_id') === $id)
            ->map(fn (array $item) => [
                'action' => $this->historyActionLabel((string) Arr::get($item, 'action', 'update')),
                'description' => (string) Arr::get($item, 'description', 'Perubahan data dicatat.'),
                'admin' => (string) (Arr::get($item, 'admin_name') ?: Arr::get($item, 'admin.name') ?: 'Sistem'),
                'performed_at' => $this->historyTimestamp((string) Arr::get($item, 'created_at', '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function breedingPeriodContext(int $periodId, string $token): array
    {
        $period = $this->api->get("breeding-periods/{$periodId}", [], $token);
        if (! $period->successful() || ! is_array($period->json())) {
            return [];
        }

        $related = $this->api->paginatedBatchData([
            'females' => [
                'path' => 'breeding-females',
                'query' => ['breeding_period_id' => $periodId],
            ],
            'checks' => [
                'path' => 'pregnancy-checks',
                'query' => ['breeding_period_id' => $periodId],
            ],
        ], $token, PHP_INT_MAX);
        $females = $related['females'];
        $checks = $related['checks'];

        $checkItems = $checks['ok'] ? $checks['data'] : [];
        $latestChecks = collect($checkItems)
            ->sortByDesc('check_date')
            ->groupBy('female_animal_id')
            ->map(fn ($items) => $items->first());

        $femaleItems = $females['ok'] ? $females['data'] : [];
        $femaleRows = collect($femaleItems)->map(function ($female) use ($latestChecks) {
            $animalId = (string) Arr::get($female, 'female_animal_id');
            $check = $latestChecks->get($animalId);

            return [
                'id' => Arr::get($female, 'id'),
                'female_animal_id' => Arr::get($female, 'female_animal_id'),
                'tag' => Arr::get($female, 'female_animal.tag_number', '-'),
                'entry_date' => $this->shortDate(Arr::get($female, 'entry_date')),
                'exit_date' => $this->shortDate(Arr::get($female, 'exit_date')),
                'mating_date' => $this->shortDate(Arr::get($female, 'mating_date')),
                'expected_birth_date' => $this->shortDate(Arr::get($female, 'expected_birth_date')),
                'check_id' => Arr::get($check, 'id'),
                'last_check_date' => $this->shortDate(Arr::get($check, 'check_date')),
                'pregnancy_status' => $check === null ? 'Belum Dicek' : $this->pregnancyStatus($check),
                'method' => Arr::get($check, 'method', '-'),
                'estimated_gestation_days' => Arr::get($check, 'estimated_gestation_days') ? Arr::get($check, 'estimated_gestation_days').' hari' : '-',
            ];
        })->values()->all();

        return [
            'period' => $period->json(),
            'females' => $femaleRows,
            'summary' => [
                'total' => count($femaleRows),
                'pregnant' => collect($femaleRows)->where('pregnancy_status', 'Bunting')->count(),
                'not_pregnant' => collect($femaleRows)->where('pregnancy_status', 'Tidak Bunting')->count(),
                'unchecked' => collect($femaleRows)->where('pregnancy_status', 'Belum Dicek')->count(),
                'born' => collect($femaleRows)->where('pregnancy_status', 'Lahir')->count(),
            ],
        ];
    }

    private function pregnancyStatus(array $check): string
    {
        if (Arr::get($check, 'outcome_status') === 'born') {
            return 'Lahir';
        }

        return ((bool) Arr::get($check, 'is_pregnant')) ? 'Bunting' : 'Tidak Bunting';
    }

    /**
     * @return array<string, mixed>
     */
    public function pregnancyFormContext(?int $periodId, ?int $femaleAnimalId, string $token): array
    {
        $period = $periodId ? $this->api->get("breeding-periods/{$periodId}", [], $token) : null;
        $animal = $femaleAnimalId ? $this->api->get("animals/{$femaleAnimalId}", [], $token) : null;

        return [
            'breeding_period_id' => $periodId,
            'female_animal_id' => $femaleAnimalId,
            'breeding_period' => $period?->successful() ? $period->json() : [],
            'female_animal' => $animal?->successful() ? $animal->json() : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function breedingFemaleExitContext(int $id, string $token): array
    {
        return [
            'breeding_female' => $this->item('breeding-females', $id, $token),
            'pen_options' => $this->nonBreedingPenOptions($token),
            'reason_options' => $this->breedingFemaleExitReasons(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function nonBreedingPenOptions(string $token): array
    {
        return collect($this->items('colony-pens', $token))
            ->filter(fn ($item) => ! in_array(Arr::get($item, 'colony_phase') ?: Arr::get($item, 'colony_type'), ['koloni_kawin'], true))
            ->mapWithKeys(fn ($item) => [(string) $item['id'] => (string) Arr::get($item, 'pen_code', $item['id'])])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function breedingFemaleExitReasons(): array
    {
        return [
            'bunting_pindah_koloni_bunting' => 'Bunting, pindah ke koloni bunting',
            'tidak_bunting' => 'Tidak bunting / gagal kawin',
            'sakit' => 'Sakit',
            'pejantan_mati' => 'Pejantan mati / periode dihentikan',
            'periode_selesai' => 'Periode selesai',
            'salah_input' => 'Salah input',
            'lainnya' => 'Lainnya',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(string $slug, array $payload, string $token): Response
    {
        if ($slug === 'rsa-keys') {
            return $this->api->post('rsa-keys/generate', $this->payload($slug, $payload, false), $token);
        }

        [$data, $files] = $this->splitPayloadAndFiles($this->payload($slug, $payload, false));

        if ($files !== []) {
            return $this->api->postMultipart((string) $this->endpoint($slug), $data, $files, $token);
        }

        return $this->api->post((string) $this->endpoint($slug), $data, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $slug, int $id, array $payload, string $token): Response
    {
        [$data, $files] = $this->splitPayloadAndFiles($this->payload($slug, $payload, true));

        if ($files !== []) {
            return $this->api->putMultipart($this->endpoint($slug)."/{$id}", $data, $files, $token);
        }

        return $this->api->put($this->endpoint($slug)."/{$id}", $data, $token);
    }

    public function sendUserPasswordResetLink(int $id, string $token): Response
    {
        return $this->api->post("users/{$id}/send-password-reset", [], $token);
    }

    public function revokeCertificate(int $id, string $token): Response
    {
        return $this->api->post("certificates/{$id}/revoke", ['reason' => 'Dicabut melalui dashboard Laravel.'], $token);
    }

    public function unrevokeCertificate(int $id, string $token): Response
    {
        return $this->api->post("certificates/{$id}/unrevoke", [], $token);
    }

    public function activateRsaKey(int $id, string $token): Response
    {
        return $this->api->post("rsa-keys/{$id}/activate", [], $token);
    }

    public function deactivateRsaKey(int $id, string $token): Response
    {
        return $this->api->post("rsa-keys/{$id}/deactivate", [], $token);
    }

    public function compromiseRsaKey(int $id, string $token): Response
    {
        return $this->api->post("rsa-keys/{$id}/compromise", [
            'status_reason' => 'Dinonaktifkan melalui dashboard karena kunci dicurigai tidak aman.',
        ], $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function exitBreedingFemale(int $id, array $payload, string $token): Response
    {
        $data = collect($payload)
            ->except(['_token'])
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        return $this->api->post("breeding-females/{$id}/exit", $data, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordBreedingFemaleMating(int $id, array $payload, string $token): Response
    {
        $data = collect($payload)
            ->except(['_token'])
            ->reject(fn ($value) => $value === '')
            ->all();

        return $this->api->post("breeding-females/{$id}/mating", $data, $token);
    }

    public function successMessage(string $slug, string $action): string
    {
        $resource = $this->resourceLabel($slug);

        return match ($action) {
            'create' => $slug === 'rsa-keys'
                ? 'Sukses: RSA Key baru berhasil dibuat dan diaktifkan untuk penerbitan sertifikat.'
                : "Sukses: {$resource} berhasil disimpan.",
            'update' => "Sukses: {$resource} berhasil diperbarui.",
            'revoke' => 'Sukses: Sertifikat berhasil dicabut dan tidak lagi berstatus aktif.',
            'unrevoke' => 'Sukses: Sertifikat berhasil diaktifkan kembali.',
            'activate' => "Sukses: {$resource} berhasil diaktifkan.",
            'deactivate' => "Sukses: {$resource} berhasil dinonaktifkan.",
            'compromise' => 'Peringatan: RSA Key dinonaktifkan dari sistem penandatanganan.',
            'exit' => 'Sukses: Catatan keluar betina dari periode perkawinan berhasil disimpan.',
            'mating' => 'Sukses: Tanggal kawin berhasil dicatat.',
            default => 'Sukses: Perubahan data berhasil disimpan.',
        };
    }

    public function failureMessage(Response $response, string $fallback): string
    {
        return implode(' ', $this->failureMessages($response, $fallback));
    }

    /**
     * @return array<int, string>
     */
    public function failureMessages(Response $response, string $fallback): array
    {
        if ($response->status() === 401) {
            return ['Sesi Berakhir: Sesi login Anda telah berakhir. Silakan login kembali.'];
        }

        if ($response->status() === 403) {
            return ['Peringatan: Akun Anda tidak memiliki izin untuk melakukan tindakan ini.'];
        }

        if ($response->serverError()) {
            return ['Gagal: Layanan belum dapat diakses. Silakan coba lagi.'];
        }

        $errors = $response->json('errors');

        if (is_array($errors) && $errors !== []) {
            $messages = [];

            foreach ($errors as $field => $fieldErrors) {
                foreach ((array) $fieldErrors as $message) {
                    if (is_string($message) && $message !== '') {
                        $messages[] = $this->translateValidationMessage((string) $field, $message);
                    }
                }
            }

            return array_values(array_unique($messages)) ?: [$fallback];
        }

        $message = $response->json('message');

        if (is_string($message) && $message !== '') {
            return [$this->translateBusinessMessage($message)];
        }

        return [$fallback];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function payload(string $slug, array $payload, bool $editing): array
    {
        if ($slug === 'pens' && ! isset($payload['colony_type']) && isset($payload['colony_phase'])) {
            $payload['colony_type'] = $payload['colony_phase'];
        }

        $nullableFields = $this->nullableFields($slug);
        $data = collect($payload)
            ->except(['_token', '_method'])
            ->map(fn ($value, $key) => $value === '' && in_array((string) $key, $nullableFields, true) ? null : $value)
            ->reject(fn ($value, $key) => $value === '' && ! in_array((string) $key, $nullableFields, true))
            ->all();

        foreach (['is_impor', 'is_pregnant', 'auto_sign', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }

        foreach (['birth_time', 'death_time'] as $key) {
            if (isset($data[$key]) && preg_match('/^\d{2}:\d{2}$/', (string) $data[$key])) {
                $data[$key] .= ':00';
            }
        }

        if ($slug === 'certificates') {
            $data['auto_sign'] = true;
        }

        if ($slug === 'users' && ! $editing) {
            $data['is_active'] = true;
        }

        if ($slug === 'animals') {
            if (($data['life_status'] ?? null) !== 'dead') {
                unset($data['status_date']);
            }
            if (array_key_exists('exit_status', $data) && $data['exit_status'] !== null && ! array_key_exists('status_date', $data)) {
                $data['status_date'] = now()->toDateString();
            }
        }

        if ($slug === 'rsa-keys') {
            $data['key_length'] = 2048;
        }

        if ($editing) {
            foreach ([
                'weight-records' => ['animal_id'],
                'birth-events' => ['dam_id', 'sire_id'],
                'breeding-females' => ['breeding_period_id', 'female_animal_id'],
                'offspring-births' => ['birth_event_id', 'offspring_animal_id', 'tag_number', 'breed_id', 'generation', 'sex'],
                'postnatal-care' => ['birth_event_id', 'target_animal_id'],
            ][$slug] ?? [] as $lockedField) {
                unset($data[$lockedField]);
            }
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function nullableFields(string $slug): array
    {
        return [
            'users' => ['password', 'password_confirmation', 'last_name'],
            'animals' => ['tag_number', 'photo', 'birth_date', 'birth_place', 'current_pen_id', 'reproductive_status', 'status_date', 'life_status', 'exit_status', 'origin_type', 'origin_detail', 'notes'],
            'pen-movements' => ['from_pen_preview', 'reason', 'notes'],
            'weight-records' => ['notes'],
            'pens' => ['colony_code', 'colony_name', 'location', 'capacity'],
            'breeding-periods' => ['end_date', 'status', 'notes'],
            'breeding-females' => ['mating_date', 'exit_date', 'exit_reason'],
            'pregnancy-checks' => ['method', 'estimated_gestation_days', 'notes'],
            'birth-events' => ['birth_time', 'sire_id', 'birth_place', 'notes'],
            'offspring-births' => ['offspring_animal_id', 'tag_number', 'offspring_grade', 'birth_status', 'notes'],
            'postnatal-care' => ['care_date', 'administration_method', 'volume_ml', 'navel_iodine_status', 'vitamin_ade_ml', 'vitamin_b_complex_ml', 'intracin_ml', 'notes'],
            'health-treatments' => ['symptoms', 'diagnosis', 'dosage', 'administration_route', 'action_category', 'handled_by', 'next_control_date', 'notes'],
            'vaccinations' => ['dosage', 'administration_route', 'notes'],
            'certificates' => ['issue_place', 'death_date', 'death_time', 'cause_of_death'],
            'rsa-keys' => ['key_identifier', 'key_length'],
        ][$slug] ?? [];
    }

    private function shortDate(mixed $value): string
    {
        return is_string($value) && $value !== '' ? substr($value, 0, 10) : '-';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:array<string, mixed>,1:array<string, UploadedFile>}
     */
    private function splitPayloadAndFiles(array $payload): array
    {
        $files = [];
        foreach ($payload as $key => $value) {
            if ($value instanceof UploadedFile) {
                if ($value->isValid()) {
                    $files[$key] = $value;
                }
                unset($payload[$key]);
            }
        }

        return [$payload, $files];
    }

    private function translateValidationMessage(string $field, string $message): string
    {
        // Pesan dari API yang sudah diterjemahkan tidak perlu diberi label kedua.
        if (preg_match('/^(Peringatan|Gagal|Sukses|Info|Gagal Masuk): /', $message)) {
            return $message;
        }

        $label = $this->fieldLabel($field);
        $lower = strtolower($message);

        if (str_contains($lower, 'field is required')) {
            return "Peringatan: {$label} wajib diisi.";
        }

        if (str_contains($lower, 'already been taken')) {
            return "Peringatan: {$label} ini sudah digunakan.";
        }

        if (str_contains($lower, 'must be an integer') || str_contains($lower, 'must be a valid integer')) {
            return "Peringatan: {$label} harus berupa angka bulat.";
        }

        if (str_contains($lower, 'selected') && str_contains($lower, 'invalid')) {
            return "Peringatan: Pilih {$label} dari pilihan yang tersedia.";
        }

        if (str_contains($lower, 'valid email')) {
            return 'Peringatan: Masukkan alamat email yang benar.';
        }

        if (str_contains($lower, 'must be a date') || str_contains($lower, 'not a valid date')) {
            return "Peringatan: Masukkan tanggal yang benar untuk {$label}.";
        }

        if (str_contains($lower, 'must be true or false')) {
            return "Peringatan: Pilih {$label} dari pilihan yang tersedia.";
        }

        if (str_contains($lower, 'must be a number')) {
            return "Peringatan: {$label} harus berupa angka.";
        }

        if (preg_match('/(?:must be at least|must not be greater than|may not be greater than) (-?[0-9.]+)( characters| kilobytes| items)?\\./', $message, $matches)) {
            $limit = str_contains($lower, 'at least') ? 'minimal' : 'maksimal';
            $unit = match ($matches[2] ?? '') {
                ' characters' => ' karakter',
                ' kilobytes' => ' KB',
                ' items' => ' data',
                default => '',
            };

            return "Peringatan: {$label} {$limit} {$matches[1]}{$unit}.";
        }

        if (str_contains($lower, 'confirmation') && str_contains($lower, 'match')) {
            return $field === 'password'
                ? 'Peringatan: Konfirmasi password harus sama dengan password baru.'
                : "Peringatan: Konfirmasi {$label} harus sama dengan {$label}.";
        }

        return $this->translateBusinessMessage($message);
    }

    private function translateBusinessMessage(string $message): string
    {
        return match ($message) {
            'Animal created.' => 'Sukses: Data kambing berhasil disimpan.',
            'Animal updated.' => 'Sukses: Data kambing berhasil diperbarui.',
            'Hanya super admin yang dapat mengelola pengguna.' => 'Peringatan: Hanya super admin yang dapat mengelola pengguna.',
            'Minimal harus ada satu super admin aktif.' => 'Peringatan: Minimal harus tersedia satu akun super admin aktif agar pengelolaan sistem tetap dapat dilakukan.',
            'animal_id must refer to an active animal.' => 'Peringatan: Kambing yang dipilih tidak aktif atau sudah tidak tersedia.',
            'record_date cannot be earlier than animal birth_date.' => 'Peringatan: Tanggal pencatatan tidak boleh lebih awal dari tanggal lahir kambing.',
            'Weight record already exists for this animal & date.' => 'Peringatan: Catatan bobot kambing ini pada tanggal tersebut sudah tersedia.',
            'Another weight record already exists for this animal & date.' => 'Peringatan: Catatan bobot kambing ini pada tanggal tersebut sudah tersedia.',
            'colony_pen_id must refer to a breeding pen.' => 'Peringatan: Kode kandang harus mengarah ke kandang perkawinan.',
            'male_animal_id must refer to a male animal.' => 'Peringatan: Tag pejantan harus mengarah ke kambing jantan.',
            'female_animal_id must refer to a female animal.' => 'Peringatan: Tag betina harus mengarah ke kambing betina.',
            'Tag pejantan harus mengarah ke kambing jantan yang masih hidup.' => 'Peringatan: Tag pejantan harus mengarah ke kambing jantan yang masih hidup.',
            'Kode kandang harus mengarah ke kandang perkawinan yang masih aktif.' => 'Peringatan: Kode kandang harus mengarah ke kandang perkawinan yang masih aktif.',
            'Tag betina harus mengarah ke kambing betina yang masih hidup dan tersedia.' => 'Peringatan: Tag betina harus mengarah ke kambing betina yang masih hidup dan tersedia.',
            'Female already exists in this period.' => 'Peringatan: Betina ini sudah terdaftar pada periode perkawinan tersebut.',
            'Breeding period capacity exceeded.' => 'Peringatan: Kapasitas kandang pada periode kawin ini sudah penuh.',
            'Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.' => 'Peringatan: Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.',
            'entry_date cannot be later than breeding period end_date.' => 'Peringatan: Tanggal masuk tidak boleh melewati tanggal selesai periode kawin.',
            'breeding_period_id must refer to an active breeding period.' => 'Peringatan: Kode periode harus mengarah ke periode kawin yang masih aktif.',
            'Keluar dari periode kawin hanya dapat diproses melalui aksi Keluarkan Betina.' => 'Peringatan: Keluar dari periode kawin hanya dapat diproses melalui tombol Keluarkan.',
            'Betina ini sudah keluar dari periode kawin.' => 'Peringatan: Betina ini sudah tercatat keluar dari periode kawin.',
            'Betina berhasil dikeluarkan dari periode kawin.' => 'Sukses: Catatan keluar betina dari periode perkawinan berhasil disimpan.',
            'Tanggal kawin hanya dapat dicatat melalui aksi Catat Kawin.' => 'Peringatan: Tanggal kawin hanya dapat dicatat melalui tombol Catat Kawin.',
            'Tanggal kawin tidak dapat dicatat karena betina sudah keluar dari periode kawin.' => 'Peringatan: Tanggal kawin tidak dapat dicatat karena betina sudah keluar dari periode kawin.',
            'Tanggal kawin tidak boleh lebih awal dari tanggal masuk betina.' => 'Peringatan: Tanggal kawin tidak boleh sebelum tanggal masuk betina.',
            'Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.' => 'Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.',
            'Tanggal kawin tidak boleh melewati tanggal selesai periode kawin.' => 'Peringatan: Tanggal kawin tidak boleh melewati tanggal selesai periode kawin.',
            'Tanggal masuk tidak boleh melewati tanggal kawin yang sudah dicatat.' => 'Peringatan: Tanggal masuk tidak boleh melewati tanggal kawin yang sudah dicatat.',
            'Tanggal keluar tidak boleh lebih awal dari tanggal masuk betina.' => 'Peringatan: Tanggal keluar tidak boleh lebih awal dari tanggal masuk betina.',
            'Tanggal keluar tidak boleh lebih awal dari tanggal kawin yang sudah dicatat.' => 'Peringatan: Tanggal keluar tidak boleh sebelum tanggal kawin.',
            'Pindah ke koloni kawin harus melalui alur Periode Kawin agar pengecekan inbreeding tetap berjalan.' => 'Peringatan: Pindah ke koloni kawin harus dilakukan melalui menu Periode Kawin agar pemeriksaan hubungan darah tetap berjalan.',
            'Pindah ke koloni kawin harus diproses melalui menu Periode Kawin agar pengecekan hubungan darah tetap berjalan.' => 'Peringatan: Pindah ke koloni kawin harus dilakukan melalui menu Periode Kawin agar pemeriksaan hubungan darah tetap berjalan.',
            'Koloni tujuan tidak aktif atau tidak ditemukan.' => 'Peringatan: Koloni tujuan tidak aktif atau tidak ditemukan.',
            'Kambing sudah berada di koloni tujuan yang dipilih.' => 'Peringatan: Pilih koloni tujuan yang berbeda dari koloni asal.',
            'Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.' => 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.',
            'Koloni anak hanya dapat diisi oleh cempe berdasarkan kategori umur ternak.' => 'Peringatan: Koloni anak hanya dapat diisi oleh cempe berdasarkan kategori umur ternak.',
            'Koloni bunting, kering, dan laktasi hanya dapat diisi oleh kambing betina.' => 'Peringatan: Koloni bunting, kering, dan laktasi hanya dapat diisi oleh kambing betina.',
            'Koloni, kode periode, tanggal mulai, dan pejantan tidak dapat diubah karena periode kawin sudah berisi betina.' => 'Peringatan: Koloni, kode periode, tanggal mulai, dan pejantan tidak dapat diubah karena periode kawin sudah berisi betina.',
            'Koloni kawin ini masih memiliki periode aktif. Tutup periode sebelumnya sebelum membuat periode baru.' => 'Peringatan: Tutup periode perkawinan yang masih aktif sebelum membuat periode baru pada koloni ini.',
            'Koloni kawin ini masih memiliki periode aktif lain. Tutup periode tersebut sebelum mengaktifkan periode ini.' => 'Peringatan: Koloni kawin ini masih memiliki periode aktif lain. Tutup periode tersebut sebelum mengaktifkan periode ini.',
            'Tanggal selesai periode kawin tidak boleh lebih awal dari tanggal mulai.' => 'Peringatan: Tanggal selesai periode kawin tidak boleh lebih awal dari tanggal mulai.',
            'female_animal_id is not registered as an active female in the selected breeding period.' => 'Peringatan: Betina yang dipilih belum terdaftar aktif pada periode kawin tersebut.',
            'breeding_female_id must refer to an active female registration in the selected breeding period.' => 'Peringatan: Data betina dalam periode kawin tidak valid atau sudah tidak aktif.',
            'check_date cannot be earlier than breeding period start_date.' => 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal mulai periode.',
            'check_date cannot be earlier than female entry_date in the breeding period.' => 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal masuk betina.',
            'Status bunting hanya dapat dicatat setelah tanggal kawin betina tersebut diisi.' => 'Peringatan: Catat tanggal kawin terlebih dahulu sebelum mencatat hasil pemeriksaan bunting.',
            'Tanggal periksa tidak boleh lebih awal dari tanggal kawin betina tersebut.' => 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal kawin betina tersebut.',
            'check_date cannot be later than female exit_date in the breeding period.' => 'Peringatan: Tanggal periksa tidak boleh melewati tanggal keluar betina.',
            'Pregnancy check already exists for this period, female, and date.' => 'Peringatan: Pemeriksaan kebuntingan untuk betina dan tanggal tersebut sudah terdaftar.',
            'Another pregnancy check already exists for this period, female, and date.' => 'Peringatan: Pemeriksaan kebuntingan lain untuk betina dan tanggal tersebut sudah terdaftar.',
            'dam_id must refer to a female animal.' => 'Peringatan: Tag betina/induk harus mengarah ke kambing betina.',
            'sire_id must refer to a male animal.' => 'Peringatan: Tag pejantan harus mengarah ke kambing jantan.',
            'dam_id and sire_id cannot be the same animal.' => 'Peringatan: Induk dan pejantan tidak boleh kambing yang sama.',
            'dam_id must refer to a pregnant female animal.' => 'Peringatan: Kelahiran hanya dapat dicatat untuk betina yang sudah berstatus bunting dan belum tercatat melahirkan.',
            'Tanggal lahir tidak boleh lebih awal dari tanggal kawin induk.' => 'Peringatan: Tanggal lahir tidak boleh lebih awal dari tanggal kawin induk.',
            'birth_event_id must refer to an active birth event.' => 'Peringatan: Data kelahiran yang dipilih tidak aktif atau tidak tersedia.',
            'offspring_animal_id is invalid.' => 'Peringatan: Tag cempe yang dipilih tidak valid atau tidak tersedia.',
            'This animal has already been registered in another birth event.' => 'Peringatan: Kambing/cempe tersebut sudah terdaftar pada data kelahiran lain.',
            'Offspring already registered for this birth event.' => 'Peringatan: Cempe tersebut sudah terdaftar pada data kelahiran ini.',
            'offspring_animal_id birth_date must match birth_event birth_date.' => 'Peringatan: Tanggal lahir cempe harus sama dengan tanggal pada data kelahiran.',
            'birth_status must be consistent with the animal life_status.' => 'Peringatan: Status hidup cempe harus sesuai dengan status hidup pada data kambing.',
            'offspring_birth_id must refer to an active offspring record from a birth event.' => 'Peringatan: Data cempe lahir yang dipilih tidak aktif atau tidak tersedia.',
            'target_animal_id is not registered as offspring in the selected birth event.' => 'Peringatan: Cempe yang dipilih bukan bagian dari data kelahiran tersebut.',
            'care_date cannot be earlier than birth_event birth_date.' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal kelahiran.',
            'Postnatal care record already exists for this event/animal.' => 'Peringatan: Data pascalahir untuk cempe tersebut sudah terdaftar.',
            'Health treatment already exists for this animal, group, product, and date.' => 'Peringatan: Data kesehatan untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah terdaftar.',
            'Vaccination already exists for this animal, category, date, and product.' => 'Peringatan: Data vaksinasi untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah terdaftar.',
            'certificate_type_id is invalid.' => 'Peringatan: Jenis sertifikat tidak valid.',
            'certificate already exists for this animal and type.' => 'Peringatan: Sertifikat untuk kambing dan jenis tersebut sudah pernah diterbitkan.',
            'RSA key generation failed.' => 'Gagal: RSA Key belum dapat dibuat. Silakan coba lagi.',
            'No active RSA key found for authenticated user.' => 'Peringatan: Penandatangan belum memiliki RSA Key aktif. Buat atau aktifkan RSA Key terlebih dahulu.',
            'User ini sudah memiliki RSA Key.' => 'Peringatan: Admin sudah memiliki RSA Key. Gunakan fitur rotasi untuk memperbarui kunci.',
            'Data birth event untuk hewan ini tidak ditemukan.' => 'Peringatan: Hewan yang dipilih tidak memiliki data kejadian kelahiran di peternakan.',
            'Certificate PDF could not be generated.' => 'Gagal: PDF sertifikat belum dapat dibuat. Silakan coba lagi.',
            'Barcode value is not available for this certificate.' => 'Peringatan: QR code belum tersedia untuk sertifikat ini.',
            'QR code is only available for BIBIT_UNGGUL certificates.' => 'Peringatan: QR code hanya tersedia untuk Sertifikat Bibit Unggul.',
            'Already revoked.' => 'Peringatan: Sertifikat ini sudah dicabut.',
            'Certificate is not revoked.' => 'Peringatan: Sertifikat ini belum dalam status dicabut.',
            'Expired certificate cannot be revoked.' => 'Peringatan: Masa berlaku sertifikat telah habis (Kedaluwarsa).',
            'Only active certificate can be signed.' => 'Peringatan: Hanya sertifikat aktif yang dapat ditandatangani.',
            'Signing failed.' => 'Gagal: Sertifikat belum berhasil ditandatangani. Silakan coba lagi.',
            'Invalid RSA public key PEM.' => 'Peringatan: Format public key RSA tidak valid.',
            'RSA key fingerprint already exists.' => 'Peringatan: RSA Key dengan fingerprint tersebut sudah terdaftar.',
            'At least one RSA key must remain active.' => 'Peringatan: Minimal harus ada satu RSA Key yang aktif.',
            'RSA key already inactive.' => 'Peringatan: RSA Key ini sudah nonaktif.',
            'Hanya super admin yang dapat mengelola RSA Key.' => 'Peringatan: Hanya super admin yang dapat mengelola RSA Key.',
            'RSA Key tanpa private key tidak dapat dijadikan key aktif.' => 'Peringatan: RSA Key tanpa private key tidak dapat dijadikan key aktif. Gunakan fitur Generate atau Rotasi RSA Key dari dashboard.',
            'RSA Key yang berstatus compromised tidak dapat diaktifkan kembali.' => 'Peringatan: RSA Key tidak dapat diaktifkan kembali karena sudah dinonaktifkan.',
            'Peringatan: RSA Key tidak dapat diaktifkan kembali karena sudah dinonaktifkan.' => 'Peringatan: RSA Key tidak dapat diaktifkan kembali karena sudah dinonaktifkan.',
            'RSA Key ini sudah ditandai compromised.' => 'Peringatan: RSA Key ini sudah dinonaktifkan.',
            'Peringatan: RSA Key ini sudah dinonaktifkan.' => 'Peringatan: RSA Key ini sudah dinonaktifkan.',
            'RSA Key berhasil dibuat dan diaktifkan.' => 'Sukses: RSA Key baru berhasil dibuat dan diaktifkan untuk penerbitan sertifikat.',
            'RSA Key berhasil diaktifkan.' => 'Sukses: RSA Key berhasil diaktifkan. Kunci sebelumnya otomatis dinonaktifkan.',
            'RSA Key berhasil ditandai compromised dan tidak dapat digunakan untuk tanda tangan baru.' => 'Peringatan: RSA Key berhasil dinonaktifkan dan tidak dapat digunakan untuk tanda tangan baru.',
            'Sukses: RSA Key berhasil dinonaktifkan dan tidak dapat digunakan untuk tanda tangan baru.' => 'Sukses: RSA Key berhasil dinonaktifkan dan tidak dapat digunakan untuk tanda tangan baru.',
            'RSA Key berhasil disimpan.' => 'Sukses: RSA Key berhasil disimpan.',
            'RSA Key berhasil diperbarui.' => 'Sukses: RSA Key berhasil diperbarui.',
            default => $this->friendlyFallbackMessage($message),
        };
    }

    private function friendlyFallbackMessage(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'no query results') || str_contains($lower, 'not found')) {
            return 'Gagal: Data tidak ditemukan. Silakan muat ulang halaman.';
        }

        if (str_contains($lower, 'unauthenticated')) {
            return 'Sesi Berakhir: Sesi login Anda telah berakhir. Silakan login kembali.';
        }

        if (str_contains($lower, 'unauthorized') || str_contains($lower, 'forbidden')) {
            return 'Peringatan: Akun Anda tidak memiliki izin untuk melakukan tindakan ini.';
        }

        if (str_contains($lower, 'failed') || str_contains($lower, 'error') || str_contains($lower, 'exception')) {
            return 'Gagal: Tindakan belum berhasil diproses. Silakan coba lagi.';
        }

        if (preg_match('/\b(must|cannot|failed|invalid|exception|error|required|exists|already|only|not found)\b/i', $message)
            || preg_match('/\b[a-z]+_[a-z_]+\b/', $message)
        ) {
            return 'Gagal: Data belum berhasil disimpan. Silakan coba lagi.';
        }

        return $message;
    }

    private function fieldLabel(string $field): string
    {
        return [
            'name' => 'Nama pengguna',
            'first_name' => 'Nama Depan',
            'last_name' => 'Nama Belakang',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi Password',
            'role' => 'Role',
            'is_active' => 'Status Aktif',
            'tag_number' => 'Eartag',
            'photo' => 'Foto Kambing',
            'breed_id' => 'Ras Kambing',
            'sex' => 'Jenis Kelamin',
            'male_role' => 'Jantan Pemacek',
            'generation' => 'Generasi',
            'birth_date' => 'Tanggal Lahir',
            'birth_place' => 'Tempat Lahir',
            'current_pen_id' => 'Kandang/Koloni Saat Ini',
            'reproductive_status' => 'Status Reproduksi',
            'status_date' => 'Tanggal Status',
            'life_status' => 'Status Hidup',
            'exit_status' => 'Status Ternak',
            'is_impor' => 'Kambing Impor',
            'origin_type' => 'Asal Ternak',
            'origin_detail' => 'Detail Asal',
            'animal_id' => 'Kambing',
            'record_date' => 'Tanggal Timbang',
            'weight_kg' => 'Bobot',
            'pen_code' => 'Kode Kandang',
            'colony_code' => 'Kode Koloni',
            'colony_name' => 'Nama Koloni',
            'colony_type' => 'Jenis Kandang',
            'colony_phase' => 'Fase Koloni',
            'location' => 'Lokasi Kandang',
            'capacity' => 'Kapasitas',
            'period_code' => 'Kode Periode',
            'colony_pen_id' => 'Kode Kandang',
            'start_date' => 'Tanggal Mulai',
            'mating_date' => 'Tanggal Kawin',
            'expected_birth_date' => 'Perkiraan Lahir',
            'end_date' => 'Tanggal Selesai',
            'male_animal_id' => 'Tag Pejantan',
            'female_animal_id' => 'Tag Betina',
            'breeding_period_id' => 'Kode Periode',
            'breeding_female_id' => 'Betina Dalam Periode',
            'entry_date' => 'Tanggal Masuk',
            'exit_date' => 'Tanggal Keluar',
            'exit_reason_code' => 'Kategori Alasan Keluar',
            'exit_reason' => 'Alasan Keluar',
            'exit_notes' => 'Catatan Keluar',
            'to_pen_id' => 'Koloni Tujuan',
            'check_date' => 'Tanggal Periksa',
            'is_pregnant' => 'Status Bunting',
            'method' => 'Metode Periksa',
            'estimated_gestation_days' => 'Estimasi Usia Kebuntingan',
            'dam_id' => 'Tag Betina/Induk',
            'sire_id' => 'Tag Pejantan',
            'birth_time' => 'Jam Lahir',
            'offspring_count' => 'Jumlah Anak',
            'birth_process' => 'Proses Kelahiran',
            'offspring_grade' => 'Grade Anak',
            'birth_event_id' => 'Data Kelahiran',
            'offspring_birth_id' => 'Data Cempe Lahir',
            'offspring_animal_id' => 'Tag Cempe',
            'birth_weight_kg' => 'Berat Lahir',
            'birth_status' => 'Status Hidup Cempe',
            'target_animal_id' => 'Tag Cempe Lahir',
            'care_date' => 'Tanggal Perawatan',
            'administration_method' => 'Metode Pemberian',
            'volume_ml' => 'Volume',
            'navel_iodine_status' => 'Iodin Pusar',
            'vitamin_ade_ml' => 'Vitamin ADE',
            'vitamin_b_complex_ml' => 'Vitamin B-Complex',
            'intracin_ml' => 'Intracin',
            'treatment_date' => 'Tanggal Perawatan',
            'treatment_group' => 'Jenis Perawatan',
            'symptoms' => 'Gejala',
            'diagnosis' => 'Diagnosis',
            'product_name' => 'Nama Produk',
            'dosage' => 'Dosis',
            'administration_route' => 'Cara Pemberian',
            'action_category' => 'Kategori Tindakan',
            'handled_by' => 'Ditangani Oleh',
            'next_control_date' => 'Tanggal Kontrol Berikutnya',
            'category_name' => 'Jenis Vaksin',
            'vaccination_date' => 'Tanggal Vaksin',
            'certificate_type_id' => 'Jenis Sertifikat',
            'issue_place' => 'Tempat Terbit',
            'death_date' => 'Tanggal Kematian',
            'death_time' => 'Waktu Kematian',
            'cause_of_death' => 'Penyebab Kematian',
            'key_identifier' => 'Key Identifier',
            'key_length' => 'Panjang Kunci',
            'notes' => 'Catatan',
            'status' => 'Status',
        ][$field] ?? str($field)->replace('_', ' ')->title()->toString();
    }

    private function resourceLabel(string $slug): string
    {
        return [
            'users' => 'Data pengguna',
            'animals' => 'Data kambing',
            'weight-records' => 'Catatan bobot',
            'pen-movements' => 'Riwayat pindah koloni',
            'pens' => 'Data kandang',
            'breeding-periods' => 'Periode kawin',
            'breeding-females' => 'Data betina kawin',
            'pregnancy-checks' => 'Pemeriksaan kebuntingan',
            'birth-events' => 'Data kelahiran',
            'offspring-births' => 'Data cempe lahir',
            'health-treatments' => 'Catatan kesehatan',
            'vaccinations' => 'Data vaksinasi',
            'postnatal-care' => 'Data pascalahir',
            'certificates' => 'Sertifikat',
            'rsa-keys' => 'RSA Key',
        ][$slug] ?? 'Data';
    }
}
