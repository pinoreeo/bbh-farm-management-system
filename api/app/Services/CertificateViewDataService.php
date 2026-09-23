<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\PostnatalCareRecord;
use App\Support\PureBreedSireMarker;
use App\Support\TypeValue;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Stringable;

class CertificateViewDataService
{
    public function __construct(private readonly PureBreedSireMarker $sireMarker) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Certificate $certificate): array
    {
        $snapshot = json_decode((string) $certificate->payload_snapshot, true);
        if (is_array($snapshot) && isset($snapshot['view_data']) && is_array($snapshot['view_data'])) {
            return [...TypeValue::stringKeyArray($snapshot['view_data']), 'certificate_status' => $certificate->status ?? '-'];
        }

        $animal = $certificate->animal;
        $birthEvent = $certificate->birthEvent;

        $offspringBirth = $birthEvent?->offspringBirths
            ?->firstWhere('offspring_animal_id', $certificate->animal_id);

        $postnatalCares = $birthEvent?->postnatalCareRecords
            ?->where('target_animal_id', $certificate->animal_id)
            ?? collect();

        $animalGeneration = $animal?->generation;
        $animalBreed = $animal?->breed?->breed_name;
        $lastPen = $animal?->currentPen;
        if (! $lastPen && $animal?->life_status === 'dead') {
            $lastPen = $animal->penMovements()->with('fromPen')
                ->whereNull('to_pen_id')->where('reason', 'Kambing mati')
                ->orderByDesc('movement_date')->orderByDesc('id')->first()?->fromPen;
        }

        $data = [
            'certificate_number' => $certificate->certificate_number ?? '-',
            'certificate_type' => $certificate->certificateType?->type_name ?? '-',
            'certificate_type_code' => $certificate->certificateType?->type_code ?? '-',
            'certificate_status' => $certificate->status ?? '-',
            'issue_date' => $this->formatDate($certificate->issue_date),
            'issue_date_full' => $this->formatDateFull($certificate->issue_date),
            'issue_day_date' => $this->formatDayDate($certificate->issue_date),
            'issue_place' => $certificate->issue_place ?? '-',
            'valid_from' => $this->formatDate($certificate->valid_from),
            'valid_until' => $this->formatDate($certificate->valid_until),
            'animal_tag' => $animal?->tag_number ?? '-',
            'animal_name' => $animal?->tag_number ?? '-',
            'animal_sex' => $this->formatSex($animal?->sex),
            'animal_birth_date' => $this->formatDate($animal?->birth_date),
            'animal_birth_date_full' => $this->formatDateFull($animal?->birth_date),
            'animal_birth_place' => $animal?->birth_place ?? '-',
            'animal_generation' => $animalGeneration ?? '-',
            'animal_breed' => $animalBreed ?? '-',
            'animal_generation_breed' => $this->combineGenerationBreed($animalGeneration, $animalBreed),
            'animal_current_pen' => $this->formatPen($lastPen),
            'animal_reproductive_status' => $this->formatReproductiveStatus($animal?->reproductive_status),
            'animal_status_date' => $this->formatDate($animal?->status_date),
            'animal_life_status' => $this->formatLifeStatus($animal?->life_status),
            'animal_male_role' => $this->formatMaleRole($animal?->male_role),
            'animal_source' => (bool) ($animal?->is_impor) ? 'Impor' : 'Lahir di Kandang',
            'birth_event_date' => $this->formatDate($birthEvent?->birth_date),
            'birth_event_time' => $this->formatTime($birthEvent?->birth_time),
            'birth_process' => $birthEvent?->birth_process ?? '-',
            'dam_tag' => $birthEvent?->dam?->tag_number ?? '-',
            'dam_breed' => $birthEvent?->dam?->breed?->breed_name ?? '-',
            'dam_generation' => $birthEvent?->dam?->generation ?? '-',
            'dam_generation_breed' => $this->combineGenerationBreed(
                $birthEvent?->dam?->generation,
                $birthEvent?->dam?->breed?->breed_name
            ),
            'offspring_grade' => $offspringBirth?->offspring_grade ?? '-',
            'sire_tag' => $birthEvent?->sire?->tag_number ?? '-',
            'sire_male_role' => $this->formatMaleRole($this->sireMarker->markerForSire($birthEvent?->sire)),
            'sire_breed' => $birthEvent?->sire?->breed?->breed_name ?? '-',
            'sire_generation' => $birthEvent?->sire?->generation ?? '-',
            'sire_generation_breed' => $this->combineGenerationBreed(
                $birthEvent?->sire?->generation,
                $birthEvent?->sire?->breed?->breed_name
            ),
            'birth_weight_kg' => $this->formatNumber($offspringBirth?->birth_weight_kg),
            'death_date' => $this->formatDate($certificate->death_date),
            'death_time' => $this->formatTime($certificate->death_time),
            'cause_of_death' => $certificate->cause_of_death ?? '-',
            'verification_url' => $certificate->public_verification_url
                ?? $certificate->barcode_value
                ?? '-',
            'postnatal_cares' => $this->mapPostnatalCares($postnatalCares),
        ];

        if (is_array($snapshot) && array_key_exists('animal_tag_number', $snapshot)) {
            $generation = $this->snapshotString($snapshot, 'animal_generation');
            $breed = $this->snapshotString($snapshot, 'animal_breed_name');
            $data['animal_tag'] = $this->snapshotString($snapshot, 'animal_tag_number') ?? '-';
            $data['animal_name'] = $data['animal_tag'];
            $data['animal_sex'] = $this->formatSex($this->snapshotString($snapshot, 'animal_sex'));
            $data['animal_birth_date'] = $this->formatDate($this->snapshotString($snapshot, 'animal_birth_date'));
            $data['animal_birth_date_full'] = $this->formatDateFull($this->snapshotString($snapshot, 'animal_birth_date'));
            $data['animal_generation'] = $generation ?? '-';
            $data['animal_breed'] = $breed ?? '-';
            $data['animal_generation_breed'] = $this->combineGenerationBreed($generation, $breed);
            if (array_key_exists('animal_life_status', $snapshot)) {
                $data['animal_life_status'] = $this->formatLifeStatus($this->snapshotString($snapshot, 'animal_life_status'));
            }
        }

        return $data;
    }

    /** @param array<array-key, mixed> $snapshot */
    private function snapshotString(array $snapshot, string $key): ?string
    {
        $value = $snapshot[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function makeQrBase64(?string $value): string
    {
        $svg = QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($value ?: '-');

        if (is_string($svg)) {
            return base64_encode($svg);
        }

        return $svg instanceof Stringable ? base64_encode((string) $svg) : '';
    }

    /**
     * @param  Collection<int, PostnatalCareRecord>  $postnatalCares
     * @return Collection<int, array{care_name: string, administration_method: string, dose: string}>
     */
    private function mapPostnatalCares(Collection $postnatalCares): Collection
    {
        return $postnatalCares
            ->flatMap(fn (PostnatalCareRecord $care): array => [
                [
                    'care_name' => 'Metode Pemberian',
                    'administration_method' => $care->administration_method ?? '-',
                    'dose' => '-',
                ],
                [
                    'care_name' => 'Volume Perawatan',
                    'administration_method' => '-',
                    'dose' => $this->formatNumber($care->volume_ml, ' ml'),
                ],
                [
                    'care_name' => 'Iodin Pusar',
                    'administration_method' => $care->navel_iodine_status ?? '-',
                    'dose' => '-',
                ],
                [
                    'care_name' => 'Vitamin ADE',
                    'administration_method' => '-',
                    'dose' => $this->formatNumber($care->vitamin_ade_ml, ' ml'),
                ],
                [
                    'care_name' => 'Vitamin B-Complex',
                    'administration_method' => '-',
                    'dose' => $this->formatNumber($care->vitamin_b_complex_ml, ' ml'),
                ],
                [
                    'care_name' => 'Intracin',
                    'administration_method' => '-',
                    'dose' => $this->formatNumber($care->intracin_ml, ' ml'),
                ],
            ])
            ->values();
    }

    private function combineGenerationBreed(?string $generation, ?string $breed): string
    {
        $generation = trim($generation ?? '');
        $breed = trim($breed ?? '');

        if ($generation === 'Pure Breed') {
            $value = trim($breed.' PB');
        } else {
            $value = trim($generation.' '.$breed);
        }

        return $value !== '' ? $value : '-';
    }

    private function formatSex(?string $sex): string
    {
        return match (strtolower($sex ?? '')) {
            'male' => 'Jantan',
            'female' => 'Betina',
            default => '-',
        };
    }

    private function formatLifeStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => '-',
        };
    }

    private function formatMaleRole(?string $role): string
    {
        return match (strtoupper($role ?? '')) {
            'SPB' => 'SPB',
            'APB' => 'APB',
            default => '-',
        };
    }

    private function formatPen(mixed $pen): string
    {
        if (! $pen) {
            return '-';
        }

        $parts = array_filter([
            $this->formatScalar(data_get($pen, 'pen_code')),
            $this->formatScalar(data_get($pen, 'colony_code')),
            $this->formatScalar(data_get($pen, 'colony_name')),
        ], fn (?string $part): bool => $part !== null && $part !== '');

        return $parts !== [] ? implode(' - ', $parts) : '-';
    }

    private function formatReproductiveStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'kosong' => 'Kosong',
            'kawin' => 'Kawin',
            'bunting' => 'Bunting',
            'kering' => 'Kering',
            'laktasi_kosong' => 'Laktasi Kosong',
            'melahirkan' => 'Melahirkan',
            'afkir' => 'Afkir',
            default => '-',
        };
    }

    private function formatDate(DateTimeInterface|string|int|float|null $date): string
    {
        $carbon = $this->carbon($date);

        return $carbon ? $carbon->format('d-m-Y') : '-';
    }

    private function formatDateFull(DateTimeInterface|string|int|float|null $date): string
    {
        $carbon = $this->carbon($date);

        if (! $carbon) {
            return '-';
        }

        $carbon->locale('id');

        return $carbon->translatedFormat('j F Y');
    }

    private function formatDayDate(DateTimeInterface|string|int|float|null $date): string
    {
        $carbon = $this->carbon($date);

        if (! $carbon) {
            return '-';
        }

        $carbon->locale('id');

        return $carbon->translatedFormat('l, j F Y');
    }

    private function formatTime(DateTimeInterface|string|int|float|null $time): string
    {
        $carbon = $this->carbon($time);

        return $carbon ? $carbon->format('H.i').' WIB' : '-';
    }

    private function formatNumber(mixed $value, string $suffix = ''): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $scalar = $this->formatScalar($value);
        if ($scalar === null) {
            return '-';
        }

        $formatted = rtrim(rtrim($scalar, '0'), '.');

        return ($formatted === '' ? '0' : $formatted).$suffix;
    }

    private function carbon(DateTimeInterface|string|int|float|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }

    private function formatScalar(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
