<?php

namespace App\Services;

use App\Models\Certificate;
use App\Support\PureBreedSireMarker;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateViewDataService
{
    public function __construct(private readonly PureBreedSireMarker $sireMarker) {}

    public function build(Certificate $certificate): array
    {
        $animal = $certificate->animal;
        $birthEvent = $certificate->birthEvent;

        $offspringBirth = $birthEvent?->offspringBirths
            ?->firstWhere('offspring_animal_id', $certificate->animal_id);

        $postnatalCares = $birthEvent?->postnatalCareRecords
            ?->where('target_animal_id', $certificate->animal_id)
            ?? collect();

        $animalGeneration = $animal?->generation;
        $animalBreed = $animal?->breed?->breed_name;

        return [
            'certificate_number' => $certificate->certificate_number ?? '-',
            'certificate_type' => $certificate->certificateType?->type_name ?? '-',
            'certificate_type_code' => $certificate->certificateType?->type_code ?? '-',
            'certificate_status' => $certificate->status ?? '-',
            'issue_date' => $this->formatDate($certificate->issue_date),
            'issue_date_full' => $this->formatDateFull($certificate->issue_date),
            'issue_day_date' => $certificate->issue_date
                ? Carbon::parse($certificate->issue_date)->locale('id')->translatedFormat('l, j F Y')
                : '-',
            'issue_place' => $certificate->issue_place ?? '-',
            'valid_from' => $this->formatDate($certificate->valid_from),
            'valid_until' => $this->formatDate($certificate->valid_until),
            'animal_tag' => $animal?->tag_number ?? '-',
            'animal_name' => $animal?->name ?? '-',
            'animal_sex' => $this->formatSex($animal?->sex),
            'animal_birth_date' => $this->formatDate($animal?->birth_date),
            'animal_birth_date_full' => $this->formatDateFull($animal?->birth_date),
            'animal_birth_place' => $animal?->birth_place ?? '-',
            'animal_generation' => $animalGeneration ?? '-',
            'animal_breed' => $animalBreed ?? '-',
            'animal_generation_breed' => $this->combineGenerationBreed($animalGeneration, $animalBreed),
            'animal_current_pen' => $this->formatPen($animal?->currentPen),
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
    }

    public function makeQrBase64(?string $value): string
    {
        return base64_encode(
            QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->generate($value ?: '-')
        );
    }

    private function mapPostnatalCares(Collection $postnatalCares): Collection
    {
        return $postnatalCares
            ->flatMap(fn ($care) => [
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
            $pen->pen_code ?? null,
            $pen->colony_code ?? null,
            $pen->colony_name ?? null,
        ]);

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

    private function formatDate($date): string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '-';
    }

    private function formatDateFull($date): string
    {
        return $date ? Carbon::parse($date)->locale('id')->translatedFormat('j F Y') : '-';
    }

    private function formatTime($time): string
    {
        return $time ? Carbon::parse($time)->format('H.i').' WIB' : '-';
    }

    private function formatNumber(mixed $value, string $suffix = ''): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $formatted = rtrim(rtrim((string) $value, '0'), '.');

        return ($formatted === '' ? '0' : $formatted).$suffix;
    }
}
