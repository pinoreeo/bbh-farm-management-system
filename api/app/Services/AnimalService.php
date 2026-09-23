<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\Certificate;
use App\Models\PregnancyCheck;
use App\Support\AnimalEartag;
use App\Support\PureBreedSireMarker;
use App\Support\TypeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AnimalService
{
    public function __construct(
        private readonly AnimalEartag $eartag,
        private readonly PureBreedSireMarker $sireMarker,
        private readonly AnimalPenMovementService $movements,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Animal>
     */
    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = Animal::query()->with([
            'breed:id,breed_name',
            'currentPen:id,pen_code,colony_phase,colony_type,capacity,is_active',
        ]);

        foreach ([
            'life_status',
            'sex',
            'reproductive_status',
            'exit_status',
            'male_role',
            'is_impor',
            'origin_type',
        ] as $field) {
            if ($request->filled($field)) {
                $query->where($field, TypeValue::string($request->query($field)));
            }
        }

        foreach (['breed_id', 'current_pen_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, TypeValue::int($request->query($field)));
            }
        }

        if ($request->filled('tag_number')) {
            $query->where('tag_number', TypeValue::string($request->query('tag_number')));
        }

        if ($request->filled('search')) {
            $search = trim(TypeValue::string($request->query('search')));
            $query->where('tag_number', 'like', "%{$search}%");
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Request $request, array $data): array
    {
        unset($data['photo_path']);

        $data['life_status'] = $data['life_status'] ?? 'alive';
        if ($data['life_status'] === 'dead') {
            $data['current_pen_id'] = null;
            $data['status_date'] = $data['status_date'] ?? now()->toDateString();
            $this->validateStatusDate(TypeValue::string($data['status_date']), TypeValue::nullableString($data['birth_date'] ?? null));
        }
        $data['reproductive_status'] = $data['reproductive_status'] ?? 'kosong';
        $data = $this->syncOriginFields($data);
        $data = $this->fillStatusDate($data);
        $automaticTag = ! isset($data['tag_number']);
        $newPhotoPath = $this->storePhoto($request);
        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
        }

        try {
            $row = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                if ($automaticTag) {
                    $data['tag_number'] = $this->eartag->next(TypeValue::nullableString($data['birth_date'] ?? null), null, $attempt);
                }

                try {
                    $row = Animal::query()->create($data);
                    break;
                } catch (QueryException $exception) {
                    if (! $automaticTag || ! $this->eartag->isDuplicateTag($exception) || $attempt === 4) {
                        throw $exception;
                    }
                }
            }
            if ($row === null) {
                throw new \RuntimeException('Nomor eartag belum berhasil dibuat. Silakan coba lagi.');
            }
        } catch (\Throwable $exception) {
            if ($newPhotoPath !== null) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        return $this->success('Sukses: Data kambing berhasil disimpan.', $this->loadSummary($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Request $request, Animal $animal, array $data): array
    {
        unset($data['photo_path']);

        $newPhotoPath = $this->storePhoto($request);
        if ($newPhotoPath !== null) {
            $data['photo_path'] = $newPhotoPath;
        }

        try {
            $oldPhotoPath = DB::transaction(function () use ($animal, $data): ?string {
                $animal = Animal::query()->whereKey($animal->id)->lockForUpdate()->firstOrFail();
                $guard = $this->validateIdentityChanges($animal, $data);
                if ($guard !== null) {
                    throw ValidationException::withMessages(['animal' => [$guard['message']]]);
                }
                $data = $this->syncOriginFields($data, $animal);
                $data = $this->fillStatusDate($data, $animal);
                $oldPhotoPath = $animal->photo_path;
                $this->applyLifeStatusTransition($animal, $data);
                $animal->fill($data)->save();
                $this->sireMarker->syncFromRecordedBirth($animal);

                return $oldPhotoPath;
            }, 3);
        } catch (\Throwable $exception) {
            if ($newPhotoPath !== null) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath !== null && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return $this->success('Sukses: Data kambing berhasil diperbarui.', $this->loadSummary($animal->refresh()));
    }

    public function loadDetail(Animal $animal): Animal
    {
        return $animal->load([
            'breed',
            'currentPen',
            'penMovements.fromPen',
            'penMovements.toPen',
            'healthTreatments',
            'vaccinations',
            'weightRecords',
            'breedingFemales.breedingPeriod.maleAnimal',
            'pregnancyChecks',
            'birthEventsAsDam.offspringBirths.offspringAnimal',
            'birthEventsAsSire.offspringBirths.offspringAnimal',
            'offspringBirths.birthEvent.dam',
            'offspringBirths.birthEvent.sire',
        ]);
    }

    private function loadSummary(Animal $animal): Animal
    {
        return $animal->load(['breed', 'currentPen']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncOriginFields(array $data, ?Animal $animal = null): array
    {
        $originType = $data['origin_type'] ?? null;

        if (! $originType) {
            if (array_key_exists('is_impor', $data)) {
                $originType = (bool) $data['is_impor'] ? 'import' : 'unknown';
            } else {
                $originType = $animal?->origin_type ?? 'unknown';
            }
        }

        $data['origin_type'] = $originType;
        $data['is_impor'] = $originType === 'import';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fillStatusDate(array $data, ?Animal $animal = null): array
    {
        if (! empty($data['exit_status']) && empty($data['status_date']) && $animal?->life_status !== 'dead') {
            $data['status_date'] = now()->toDateString();
        }

        return $data;
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        $path = $request->file('photo')->store('animals', 'public');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Foto kambing belum berhasil disimpan.');
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function validateIdentityChanges(Animal $animal, array $data): ?array
    {
        if (isset($data['sex']) && $data['sex'] !== $animal->sex && $this->hasSexDependentHistory($animal)) {
            return $this->error('Peringatan: Jenis kelamin tidak dapat diubah karena kambing sudah memiliki catatan reproduksi atau kelahiran.');
        }

        if (isset($data['sex']) && $data['sex'] !== $animal->sex) {
            $phase = $animal->currentPen?->colony_phase ?? $animal->currentPen?->colony_type;
            if ($data['sex'] !== 'female' && in_array($phase, ['koloni_bunting', 'koloni_kering', 'koloni_laktasi'], true)) {
                return $this->error('Peringatan: Jenis kelamin tidak dapat diubah selama kambing tercatat di koloni khusus betina.');
            }
        }

        if (array_key_exists('birth_date', $data) && $data['birth_date'] === null && $animal->offspringBirths()->exists()) {
            return $this->error('Peringatan: Tanggal lahir tidak dapat dikosongkan karena kambing sudah memiliki catatan kelahiran.');
        }

        if (array_key_exists('birth_date', $data) && $data['birth_date'] !== null) {
            $newDate = TypeValue::string($data['birth_date']);
            $birthEventDate = $animal->offspringBirths()->with('birthEvent')->first()?->birthEvent?->birth_date?->toDateString();
            if ($birthEventDate !== null && $newDate !== $birthEventDate) {
                return $this->error('Peringatan: Tanggal lahir harus sama dengan tanggal pada catatan kelahiran.');
            }

            $earliestRecordDate = collect([
                $animal->weightRecords()->min('record_date'),
                $animal->penMovements()->min('movement_date'),
                $animal->healthTreatments()->min('treatment_date'),
                $animal->vaccinations()->min('vaccination_date'),
                $animal->postnatalCareRecords()->min('care_date'),
                $animal->breedingFemales()->min('entry_date'),
                $animal->pregnancyChecks()->min('check_date'),
                $animal->birthEventsAsDam()->min('birth_date'),
                $animal->birthEventsAsSire()->min('birth_date'),
            ])->filter()->min();

            if (is_string($earliestRecordDate) && $newDate > substr($earliestRecordDate, 0, 10)) {
                return $this->error('Peringatan: Tanggal lahir tidak boleh setelah tanggal catatan pertama kambing.');
            }
        }

        if ($animal->offspringBirths()->exists()
            && ((isset($data['origin_type']) && $data['origin_type'] !== 'internal_birth')
                || (array_key_exists('is_impor', $data) && (bool) $data['is_impor']))) {
            return $this->error('Peringatan: Asal ternak tidak dapat diubah karena kambing tercatat lahir di peternakan.');
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function applyLifeStatusTransition(Animal $animal, array &$data): void
    {
        $newStatus = $data['life_status'] ?? $animal->life_status;
        if ($newStatus === $animal->life_status) {
            if ($newStatus === 'dead' && array_key_exists('status_date', $data)) {
                $date = TypeValue::nullableString($data['status_date']);
                if ($date === null) {
                    throw ValidationException::withMessages(['status_date' => ['Peringatan: Tanggal kematian tidak boleh dikosongkan.']]);
                }
                if ($date !== $animal->status_date?->toDateString()) {
                    $this->correctDeathDate($animal, $date);
                }
            }

            return;
        }

        $date = TypeValue::nullableString($data['status_date'] ?? null) ?? now()->toDateString();
        $this->validateStatusDate($date, $animal->birth_date?->toDateString());

        if ($newStatus === 'alive') {
            throw ValidationException::withMessages(['life_status' => ['Peringatan: Status mati tidak dapat diubah langsung menjadi hidup. Jika ada salah catat saat lahir, perbaiki melalui data kelahiran cempe.']]);
        }

        $latestRecord = $this->latestActivityDate($animal);
        if ($latestRecord !== null && $date < $latestRecord) {
            throw ValidationException::withMessages(['status_date' => ['Peringatan: Tanggal kematian tidak boleh lebih awal dari catatan terakhir kambing.']]);
        }

        BreedingFemale::query()->where('female_animal_id', $animal->id)->whereNull('exit_date')->update([
            'exit_date' => $date, 'exit_reason_code' => 'lainnya', 'exit_reason' => 'Kambing mati',
        ]);
        $activePeriods = BreedingPeriod::query()->where('male_animal_id', $animal->id)->where('status', 'active')->lockForUpdate()->get();
        foreach ($activePeriods as $period) {
            $period->forceFill(['status' => 'closed', 'end_date' => $date, 'closed_by_male_death' => true])->save();
            $period->females()->whereNull('exit_date')->update([
                'exit_date' => $date, 'exit_reason_code' => 'pejantan_mati',
                'exit_reason' => BreedingFemaleService::EXIT_REASONS['pejantan_mati'],
            ]);
        }

        if ($animal->current_pen_id !== null) {
            $departure = $this->movements->record($animal, null, $date, 'Kambing mati');
            if (isset($departure['error'])) {
                throw ValidationException::withMessages(['status_date' => [$departure['error']]]);
            }
        }
        $data['current_pen_id'] = null;
        $data['status_date'] = $date;
    }

    private function latestActivityDate(Animal $animal, ?int $excludeMovementId = null): ?string
    {
        $movements = $animal->penMovements();
        if ($excludeMovementId !== null) {
            $movements->whereKeyNot($excludeMovementId);
        }

        $latestRecord = collect([
            $animal->weightRecords()->max('record_date'),
            $animal->healthTreatments()->max('treatment_date'),
            $animal->vaccinations()->max('vaccination_date'),
            $animal->postnatalCareRecords()->max('care_date'),
            $movements->max('movement_date'),
            $animal->breedingFemales()->max('entry_date'),
            $animal->breedingFemales()->max('mating_date'),
            $animal->breedingPeriodsAsMale()->max('start_date'),
            BreedingFemale::query()->whereHas('breedingPeriod', fn ($query) => $query->where('male_animal_id', $animal->id))->max('entry_date'),
            BreedingFemale::query()->whereHas('breedingPeriod', fn ($query) => $query->where('male_animal_id', $animal->id))->max('mating_date'),
            PregnancyCheck::query()->whereHas('breedingPeriod', fn ($query) => $query->where('male_animal_id', $animal->id))->max('check_date'),
            $animal->pregnancyChecks()->max('check_date'),
            $animal->birthEventsAsDam()->max('birth_date'),
            $animal->birthEventsAsSire()->max('birth_date'),
        ])->filter()->max();

        return is_string($latestRecord) ? substr($latestRecord, 0, 10) : null;
    }

    private function validateStatusDate(string $date, ?string $birthDate): void
    {
        if ($date > now()->toDateString() || ($birthDate !== null && $date < $birthDate)) {
            throw ValidationException::withMessages(['status_date' => ['Peringatan: Tanggal perubahan status hidup harus berada antara tanggal lahir dan hari ini.']]);
        }
    }

    private function correctDeathDate(Animal $animal, string $date): void
    {
        $this->validateStatusDate($date, $animal->birth_date?->toDateString());
        if ($animal->offspringBirths()->where('birth_status', 'dead')->exists()
            && $date !== $animal->birth_date?->toDateString()) {
            throw ValidationException::withMessages(['status_date' => ['Peringatan: Cempe yang tercatat mati saat lahir harus menggunakan tanggal kelahirannya.']]);
        }
        if ($this->hasActiveDeathCertificate($animal)) {
            throw ValidationException::withMessages(['status_date' => ['Peringatan: Cabut akta kematian yang aktif sebelum mengoreksi tanggal kematian.']]);
        }

        $oldDate = $animal->status_date?->toDateString();
        $departure = $oldDate === null ? null : AnimalPenMovement::query()
            ->where('animal_id', $animal->id)->whereDate('movement_date', $oldDate)
            ->whereNull('to_pen_id')->where('reason', 'Kambing mati')->lockForUpdate()->first();
        $latestRecord = $this->latestActivityDate($animal, $departure?->id);
        if ($latestRecord !== null && $date < $latestRecord) {
            throw ValidationException::withMessages(['status_date' => ['Peringatan: Tanggal kematian tidak boleh lebih awal dari catatan terakhir kambing.']]);
        }
        if ($departure !== null) {
            $error = $this->movements->reschedule($animal, $departure, $date);
            if ($error !== null) {
                throw ValidationException::withMessages(['status_date' => [$error]]);
            }
        }
        if ($oldDate !== null) {
            BreedingFemale::query()->where('female_animal_id', $animal->id)
                ->whereDate('exit_date', $oldDate)->where('exit_reason', 'Kambing mati')
                ->update(['exit_date' => $date]);
            BreedingFemale::query()->whereHas('breedingPeriod', fn ($query) => $query->where('male_animal_id', $animal->id))
                ->whereDate('exit_date', $oldDate)->where('exit_reason_code', 'pejantan_mati')
                ->update(['exit_date' => $date]);
            BreedingPeriod::query()->where('male_animal_id', $animal->id)
                ->where('status', 'closed')->where('closed_by_male_death', true)->whereDate('end_date', $oldDate)
                ->update(['end_date' => $date]);
        }
    }

    private function hasActiveDeathCertificate(Animal $animal): bool
    {
        return Certificate::query()->where('animal_id', $animal->id)
            ->whereHas('certificateType', fn ($query) => $query->where('type_code', 'KEMATIAN'))
            ->where('status', 'active')->exists();
    }

    public function syncBirthLifeStatus(Animal $animal, string $birthStatus, string $birthDate, bool $wasDeadAtBirth): void
    {
        $animal = Animal::query()->whereKey($animal->id)->lockForUpdate()->firstOrFail();
        $deathDate = $animal->status_date?->toDateString();

        if ($birthStatus === 'dead') {
            if ($animal->life_status === 'dead') {
                if ($deathDate !== null && $deathDate !== $birthDate) {
                    throw ValidationException::withMessages(['birth_status' => ['Peringatan: Kambing sudah tercatat mati pada tanggal lain. Periksa data kambing sebelum mengubah status lahir.']]);
                }
                if ($deathDate === null) {
                    if ($this->hasActiveDeathCertificate($animal)) {
                        throw ValidationException::withMessages(['birth_status' => ['Peringatan: Cabut akta kematian yang aktif sebelum mengoreksi tanggal kematian cempe.']]);
                    }
                    $latestRecord = $this->latestActivityDate($animal);
                    if ($latestRecord !== null && $birthDate < $latestRecord) {
                        throw ValidationException::withMessages(['birth_status' => ['Peringatan: Status mati saat lahir tidak sesuai dengan riwayat kambing setelah kelahiran.']]);
                    }
                    $animal->forceFill(['status_date' => $birthDate])->save();
                }

                return;
            }

            $transition = ['life_status' => 'dead', 'status_date' => $birthDate];
            $this->applyLifeStatusTransition($animal, $transition);
            $animal->fill($transition)->save();

            return;
        }

        if ($animal->life_status !== 'dead' || ($deathDate !== null && $deathDate !== $birthDate)) {
            return;
        }
        if (! $wasDeadAtBirth) {
            throw ValidationException::withMessages(['birth_status' => ['Peringatan: Kambing tercatat mati saat lahir. Periksa status pada data kambing.']]);
        }
        if ($this->hasActiveDeathCertificate($animal)) {
            throw ValidationException::withMessages(['birth_status' => ['Peringatan: Cabut akta kematian yang aktif sebelum mengoreksi status lahir cempe.']]);
        }
        if ($animal->breedingFemales()->where('exit_reason', 'Kambing mati')->exists()
            || $animal->breedingPeriodsAsMale()->whereDate('end_date', $birthDate)->exists()) {
            throw ValidationException::withMessages(['birth_status' => ['Peringatan: Status lahir belum dapat diubah karena sudah ada riwayat perkawinan yang ditutup saat kematian.']]);
        }

        $departure = AnimalPenMovement::query()->where('animal_id', $animal->id)
            ->whereDate('movement_date', $birthDate)->whereNull('to_pen_id')
            ->where('reason', 'Kambing mati')->lockForUpdate()->first();
        if ($departure !== null) {
            $initialPenId = $departure->from_pen_id;
            $departure->delete();
            $this->movements->rebuildMovementChain($animal, $initialPenId);
        }
        $animal->forceFill(['life_status' => 'alive', 'status_date' => null])->save();
    }

    private function hasSexDependentHistory(Animal $animal): bool
    {
        return $animal->breedingFemales()->exists()
            || $animal->pregnancyChecks()->exists()
            || $animal->breedingPeriodsAsMale()->exists()
            || $animal->birthEventsAsDam()->exists()
            || $animal->birthEventsAsSire()->exists();
    }

    /** @return array<string, mixed> */
    private function error(string $message, int $status = 422): array
    {
        return compact('message', 'status') + ['ok' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private function success(string $message, mixed $data, int $status = 200): array
    {
        return compact('message', 'data', 'status') + ['ok' => true];
    }
}
