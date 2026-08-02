<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BirthEvent;
use App\Models\Breed;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use App\Models\HealthTreatment;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use App\Models\PregnancyCheck;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FarmOperationalDummySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $pens = $this->seedPens();
            $breeds = $this->breeds();
            $animals = $this->seedAnimals($breeds, $pens);

            $this->seedWeights($animals);
            $this->seedBreeding($animals, $pens);
            $this->seedBirthsAndPostnatalCare($animals);
            $this->seedHealthTreatments($animals);
            $this->seedVaccinations($animals);
            $this->seedPenMovements($animals, $pens);
        });
    }

    /**
     * @return array<string, ColonyPen>
     */
    private function seedPens(): array
    {
        $rows = [
            ['pen_code' => 'BBH-DMY-KWN-01', 'colony_code' => 'KWN-01', 'colony_name' => 'Koloni Kawin Demo 01', 'colony_type' => 'koloni_kawin', 'colony_phase' => 'koloni_kawin', 'location' => 'Blok Kawin Utara', 'capacity' => 16],
            ['pen_code' => 'BBH-DMY-BTG-01', 'colony_code' => 'BTG-01', 'colony_name' => 'Koloni Bunting Demo 01', 'colony_type' => 'koloni_bunting', 'colony_phase' => 'koloni_bunting', 'location' => 'Blok Bunting Tengah', 'capacity' => 18],
            ['pen_code' => 'BBH-DMY-KRG-01', 'colony_code' => 'KRG-01', 'colony_name' => 'Koloni Kering Demo 01', 'colony_type' => 'koloni_kering', 'colony_phase' => 'koloni_kering', 'location' => 'Blok Kering Selatan', 'capacity' => 12],
            ['pen_code' => 'BBH-DMY-LAK-01', 'colony_code' => 'LAK-01', 'colony_name' => 'Koloni Laktasi Demo 01', 'colony_type' => 'koloni_laktasi', 'colony_phase' => 'koloni_laktasi', 'location' => 'Blok Laktasi Timur', 'capacity' => 20],
            ['pen_code' => 'BBH-DMY-CMP-01', 'colony_code' => 'CMP-01', 'colony_name' => 'Koloni Cempe Demo 01', 'colony_type' => 'koloni_anak', 'colony_phase' => 'koloni_anak', 'location' => 'Blok Anak Barat', 'capacity' => 24],
        ];

        return collect($rows)
            ->mapWithKeys(fn (array $row) => [
                $row['pen_code'] => ColonyPen::query()->updateOrCreate(
                    ['pen_code' => $row['pen_code']],
                    $row + ['is_active' => true]
                ),
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function breeds(): array
    {
        return Breed::query()
            ->whereIn('breed_name', ['Saanen', 'Alpine', 'Sapera', 'Peranakan Etawa', 'Toggenburg'])
            ->pluck('id', 'breed_name')
            ->all();
    }

    /**
     * @param  array<string, int>  $breeds
     * @param  array<string, ColonyPen>  $pens
     * @return array<string, Animal>
     */
    private function seedAnimals(array $breeds, array $pens): array
    {
        $rows = [
            ['tag_number' => 'BBH-DMY-24-001', 'breed_id' => $breeds['Saanen'], 'sex' => 'male', 'male_role' => 'SPB', 'generation' => 'Pure Breed', 'birth_date' => '2024-01-12', 'birth_place' => 'BBH Farm Ajibarang', 'current_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'reproductive_status' => 'kawin', 'status_date' => '2026-03-10'],
            ['tag_number' => 'BBH-DMY-24-002', 'breed_id' => $breeds['Alpine'], 'sex' => 'male', 'male_role' => 'APB', 'generation' => 'Pure Breed', 'birth_date' => '2024-02-20', 'birth_place' => 'BBH Farm Ajibarang', 'current_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'reproductive_status' => 'kawin', 'status_date' => '2026-03-10'],
            ['tag_number' => 'BBH-DMY-25-003', 'breed_id' => $breeds['Sapera'], 'sex' => 'male', 'male_role' => null, 'generation' => 'F2', 'birth_date' => '2025-06-18', 'birth_place' => 'BBH Farm Ajibarang', 'current_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-07-01'],
            ['tag_number' => 'BBH-DMY-24-004', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-03-04', 'current_pen_id' => $pens['BBH-DMY-BTG-01']->id, 'reproductive_status' => 'bunting', 'status_date' => '2026-04-25'],
            ['tag_number' => 'BBH-DMY-24-005', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-04-09', 'current_pen_id' => $pens['BBH-DMY-BTG-01']->id, 'reproductive_status' => 'bunting', 'status_date' => '2026-04-25'],
            ['tag_number' => 'BBH-DMY-24-006', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F3', 'birth_date' => '2024-04-30', 'current_pen_id' => $pens['BBH-DMY-BTG-01']->id, 'reproductive_status' => 'bunting', 'status_date' => '2026-05-01'],
            ['tag_number' => 'BBH-DMY-24-007', 'breed_id' => $breeds['Peranakan Etawa'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-05-11', 'current_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'reproductive_status' => 'kawin', 'status_date' => '2026-05-12'],
            ['tag_number' => 'BBH-DMY-24-008', 'breed_id' => $breeds['Toggenburg'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2024-06-22', 'current_pen_id' => $pens['BBH-DMY-KRG-01']->id, 'reproductive_status' => 'kering', 'status_date' => '2026-06-01'],
            ['tag_number' => 'BBH-DMY-24-009', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-07-16', 'current_pen_id' => $pens['BBH-DMY-LAK-01']->id, 'reproductive_status' => 'laktasi_kosong', 'status_date' => '2026-06-11'],
            ['tag_number' => 'BBH-DMY-24-010', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'generation' => 'F3', 'birth_date' => '2024-08-01', 'current_pen_id' => $pens['BBH-DMY-LAK-01']->id, 'reproductive_status' => 'laktasi_kosong', 'status_date' => '2026-06-11'],
            ['tag_number' => 'BBH-DMY-25-011', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2025-08-12', 'current_pen_id' => $pens['BBH-DMY-LAK-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-07-01'],
            ['tag_number' => 'BBH-DMY-25-012', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2025-09-20', 'current_pen_id' => $pens['BBH-DMY-LAK-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-07-01'],
            ['tag_number' => 'BBH-DMY-25-013', 'breed_id' => $breeds['Peranakan Etawa'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2025-10-07', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-07-01'],
            ['tag_number' => 'BBH-DMY-25-014', 'breed_id' => $breeds['Toggenburg'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2025-11-02', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-07-01'],
            ['tag_number' => 'BBH-DMY-26-015', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'male_role' => 'SPB', 'generation' => 'F3', 'birth_date' => '2026-01-15', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-01-15'],
            ['tag_number' => 'BBH-DMY-26-016', 'breed_id' => $breeds['Saanen'], 'sex' => 'male', 'male_role' => 'SPB', 'generation' => 'F3', 'birth_date' => '2026-01-15', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-01-15'],
            ['tag_number' => 'BBH-DMY-26-017', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'male_role' => 'APB', 'generation' => 'F3', 'birth_date' => '2026-02-18', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-02-18'],
            ['tag_number' => 'BBH-DMY-26-018', 'breed_id' => $breeds['Alpine'], 'sex' => 'male', 'male_role' => 'APB', 'generation' => 'F3', 'birth_date' => '2026-02-18', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-02-18'],
            ['tag_number' => 'BBH-DMY-26-019', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F4', 'birth_date' => '2026-03-21', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-03-21'],
            ['tag_number' => 'BBH-DMY-26-020', 'breed_id' => $breeds['Sapera'], 'sex' => 'male', 'generation' => 'F4', 'birth_date' => '2026-03-21', 'current_pen_id' => $pens['BBH-DMY-CMP-01']->id, 'reproductive_status' => 'kosong', 'status_date' => '2026-03-21'],
        ];

        return collect($rows)
            ->mapWithKeys(fn (array $row) => [
                $row['tag_number'] => Animal::query()->updateOrCreate(
                    ['tag_number' => $row['tag_number']],
                    array_merge([
                        'birth_place' => 'BBH Farm Ajibarang',
                        'life_status' => 'alive',
                        'exit_status' => null,
                        'notes' => 'Data dummy operasional v3.',
                        'is_impor' => false,
                        'origin_type' => 'internal_birth',
                        'origin_detail' => 'Data dummy kelahiran/pencatatan internal BBH Farm.',
                    ], $row)
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, Animal>  $animals
     */
    private function seedWeights(array $animals): void
    {
        foreach (array_values($animals) as $index => $animal) {
            WeightRecord::query()->updateOrCreate(
                ['animal_id' => $animal->id, 'record_date' => '2026-07-01'],
                [
                    'weight_kg' => match ($animal->sex) {
                        'male' => $index < 3 ? 54 + ($index * 3) : 14 + $index,
                        default => $index < 14 ? 38 + ($index % 7) : 10 + ($index % 6),
                    },
                    'notes' => 'Timbang dummy awal Juli.',
                ]
            );
        }
    }

    /**
     * @param  array<string, Animal>  $animals
     * @param  array<string, ColonyPen>  $pens
     */
    private function seedBreeding(array $animals, array $pens): void
    {
        $periodOne = BreedingPeriod::query()->updateOrCreate(
            ['colony_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'period_code' => 'PRD-DMY-2026-01'],
            [
                'start_date' => '2026-01-10',
                'end_date' => '2026-03-10',
                'male_animal_id' => $animals['BBH-DMY-24-001']->id,
                'status' => 'closed',
                'inbreeding_policy' => 'block_high_risk',
                'notes' => 'Periode kawin dummy awal tahun.',
            ]
        );

        $periodTwo = BreedingPeriod::query()->updateOrCreate(
            ['colony_pen_id' => $pens['BBH-DMY-KWN-01']->id, 'period_code' => 'PRD-DMY-2026-02'],
            [
                'start_date' => '2026-03-15',
                'end_date' => '2026-05-15',
                'male_animal_id' => $animals['BBH-DMY-24-002']->id,
                'status' => 'active',
                'inbreeding_policy' => 'block_high_risk',
                'notes' => 'Periode kawin dummy berjalan.',
            ]
        );

        $this->seedBreedingFemale($periodOne, $animals['BBH-DMY-24-004'], '2026-01-15', 'bunting', 'clear', 'Tidak ada hubungan darah dekat terdeteksi.');
        $this->seedBreedingFemale($periodOne, $animals['BBH-DMY-24-005'], '2026-01-18', 'bunting', 'clear', 'Tidak ada hubungan darah dekat terdeteksi.');
        $this->seedBreedingFemale($periodOne, $animals['BBH-DMY-24-006'], '2026-01-22', 'bunting', 'clear', 'Tidak ada hubungan darah dekat terdeteksi.');
        $this->seedBreedingFemale($periodTwo, $animals['BBH-DMY-24-007'], '2026-03-20', 'kawin', 'clear', 'Tidak ada hubungan darah dekat terdeteksi.');
        $this->seedBreedingFemale($periodTwo, $animals['BBH-DMY-24-008'], '2026-03-24', 'kering', 'clear', null);

        foreach (BreedingFemale::query()->whereIn('female_animal_id', [
            $animals['BBH-DMY-24-004']->id,
            $animals['BBH-DMY-24-005']->id,
            $animals['BBH-DMY-24-006']->id,
            $animals['BBH-DMY-24-007']->id,
            $animals['BBH-DMY-24-008']->id,
        ])->get() as $index => $breedingFemale) {
            PregnancyCheck::query()->updateOrCreate(
                ['breeding_female_id' => $breedingFemale->id, 'check_date' => Carbon::parse($breedingFemale->mating_date)->addDays(45)->toDateString()],
                [
                    'breeding_period_id' => $breedingFemale->breeding_period_id,
                    'female_animal_id' => $breedingFemale->female_animal_id,
                    'is_pregnant' => $index < 4,
                    'method' => $index % 2 === 0 ? 'USG' : 'Visual',
                    'estimated_gestation_days' => $index < 4 ? 45 + ($index * 3) : null,
                    'outcome_status' => null,
                    'notes' => $index < 4 ? 'Pemeriksaan dummy: terindikasi bunting.' : 'Pemeriksaan dummy: perlu cek ulang siklus berikutnya.',
                ]
            );
        }
    }

    private function seedBreedingFemale(BreedingPeriod $period, Animal $female, string $matingDate, string $stage, string $inbreedingStatus, ?string $inbreedingNote): void
    {
        BreedingFemale::query()->updateOrCreate(
            ['breeding_period_id' => $period->id, 'female_animal_id' => $female->id],
            [
                'entry_date' => Carbon::parse($period->start_date)->toDateString(),
                'mating_date' => $matingDate,
                'expected_birth_date' => Carbon::parse($matingDate)->addMonthsNoOverflow(5)->addDays(10)->toDateString(),
                'cycle_stage' => $stage,
                'inbreeding_status' => $inbreedingStatus,
                'inbreeding_note' => $inbreedingNote,
                'exit_date' => $stage === 'bunting' ? Carbon::parse($matingDate)->addDays(55)->toDateString() : null,
                'exit_reason' => $stage === 'bunting' ? 'Bunting, pindah ke koloni bunting' : null,
                'exit_reason_code' => $stage === 'bunting' ? 'bunting_pindah_koloni_bunting' : null,
                'exit_notes' => $stage === 'bunting' ? 'Data dummy: betina dipindahkan setelah pengamatan bunting.' : null,
            ]
        );
    }

    /**
     * @param  array<string, Animal>  $animals
     */
    private function seedBirthsAndPostnatalCare(array $animals): void
    {
        $events = [
            ['dam' => 'BBH-DMY-24-009', 'sire' => 'BBH-DMY-24-001', 'date' => '2026-01-15', 'time' => '06:20:00', 'kids' => ['BBH-DMY-26-015' => ['3.20', 'A'], 'BBH-DMY-26-016' => ['3.05', 'B']]],
            ['dam' => 'BBH-DMY-24-010', 'sire' => 'BBH-DMY-24-002', 'date' => '2026-02-18', 'time' => '08:10:00', 'kids' => ['BBH-DMY-26-017' => ['3.10', 'A'], 'BBH-DMY-26-018' => ['2.95', 'B']]],
            ['dam' => 'BBH-DMY-24-008', 'sire' => 'BBH-DMY-25-003', 'date' => '2026-03-21', 'time' => '05:45:00', 'kids' => ['BBH-DMY-26-019' => ['3.00', 'A'], 'BBH-DMY-26-020' => ['2.85', 'B']]],
        ];

        foreach ($events as $event) {
            $birth = BirthEvent::query()->updateOrCreate(
                ['dam_id' => $animals[$event['dam']]->id, 'birth_date' => $event['date']],
                [
                    'sire_id' => $animals[$event['sire']]->id,
                    'birth_time' => $event['time'],
                    'offspring_count' => count($event['kids']),
                    'birth_process' => 'Normal',
                    'dam_grade' => 'A',
                    'birth_place' => 'BBH Farm Ajibarang',
                    'notes' => 'Kelahiran dummy operasional.',
                ]
            );

            foreach ($event['kids'] as $tag => [$weight, $grade]) {
                $offspring = OffspringBirth::query()->updateOrCreate(
                    ['birth_event_id' => $birth->id, 'offspring_animal_id' => $animals[$tag]->id],
                    [
                        'birth_weight_kg' => $weight,
                        'offspring_grade' => $grade,
                        'birth_status' => 'alive',
                        'notes' => 'Cempe dummy sehat.',
                    ]
                );

                PostnatalCareRecord::query()->updateOrCreate(
                    ['offspring_birth_id' => $offspring->id],
                    [
                        'birth_event_id' => $birth->id,
                        'target_animal_id' => $animals[$tag]->id,
                        'care_date' => $event['date'],
                        'administration_method' => 'DOT',
                        'volume_ml' => 250,
                        'navel_iodine_status' => 'Ok',
                        'vitamin_ade_ml' => 1,
                        'vitamin_b_complex_ml' => 1,
                        'intracin_ml' => 0.5,
                        'notes' => 'Perawatan pascalahir dummy lengkap.',
                    ]
                );
            }
        }
    }

    /**
     * @param  array<string, Animal>  $animals
     */
    private function seedHealthTreatments(array $animals): void
    {
        foreach (array_values(array_slice($animals, 0, 8)) as $index => $animal) {
            HealthTreatment::query()->updateOrCreate(
                ['animal_id' => $animal->id, 'treatment_group' => 'Pemeriksaan Rutin', 'product_name' => 'Vitamin B Complex', 'treatment_date' => Carbon::parse('2026-07-05')->addDays($index)->toDateString()],
                [
                    'symptoms' => $index % 3 === 0 ? 'Nafsu makan turun ringan' : 'Tidak ada gejala khusus',
                    'diagnosis' => 'Kondisi stabil, observasi berkala.',
                    'dosage' => '10 ml',
                    'administration_route' => 'Oral',
                    'action_category' => 'Preventif',
                    'handled_by' => 'Petugas Kandang Demo',
                    'next_control_date' => Carbon::parse('2026-07-12')->addDays($index)->toDateString(),
                    'notes' => 'Catatan kesehatan dummy.',
                ]
            );
        }
    }

    /**
     * @param  array<string, Animal>  $animals
     */
    private function seedVaccinations(array $animals): void
    {
        foreach (array_values(array_slice($animals, 0, 12)) as $index => $animal) {
            Vaccination::query()->updateOrCreate(
                ['animal_id' => $animal->id, 'category_name' => 'Clostridial / Enterotoxemia', 'vaccination_date' => '2026-06-20'],
                [
                    'product_name' => 'Enterotoxemia Vaccine Demo',
                    'dosage' => $index < 3 ? '2 ml' : '1 ml',
                    'administration_route' => 'Subkutan (SC)',
                    'notes' => 'Vaksinasi dummy untuk uji dashboard.',
                ]
            );
        }
    }

    /**
     * @param  array<string, Animal>  $animals
     * @param  array<string, ColonyPen>  $pens
     */
    private function seedPenMovements(array $animals, array $pens): void
    {
        foreach (['BBH-DMY-24-004', 'BBH-DMY-24-005', 'BBH-DMY-24-006'] as $index => $tag) {
            AnimalPenMovement::query()->updateOrCreate(
                ['animal_id' => $animals[$tag]->id, 'movement_date' => Carbon::parse('2026-03-10')->addDays($index)->toDateString()],
                [
                    'from_pen_id' => $pens['BBH-DMY-KWN-01']->id,
                    'to_pen_id' => $pens['BBH-DMY-BTG-01']->id,
                    'reason' => 'Masuk koloni bunting',
                    'notes' => 'Perpindahan dummy setelah periode kawin.',
                ]
            );
        }
    }
}
