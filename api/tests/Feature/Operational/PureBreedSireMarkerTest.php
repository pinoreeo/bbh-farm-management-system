<?php

namespace Tests\Feature\Operational;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\Breed;
use Tests\Feature\Support\ApiTestCase;

class PureBreedSireMarkerTest extends ApiTestCase
{
    public function test_offspring_marker_is_derived_from_pure_breed_sire(): void
    {
        $this->actingAsAdmin();

        $saanen = Breed::query()->where('breed_name', 'Saanen')->firstOrFail();
        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-SPB-'.uniqid()]);
        $sire = $this->createAnimal([
            'breed_id' => $saanen->id,
            'sex' => 'male',
            'generation' => 'Pure Breed',
            'tag_number' => 'SIRE-SPB-'.uniqid(),
        ]);
        $offspring = $this->createAnimal([
            'sex' => 'female',
            'tag_number' => 'KID-SPB-'.uniqid(),
            'birth_date' => '2026-07-20',
        ]);

        $birthEvent = BirthEvent::query()->create([
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
            'birth_place' => 'BBH Farm',
        ]);

        $this->postJson('/api/v1/offspring-births', [
            'birth_event_id' => $birthEvent->id,
            'offspring_animal_id' => $offspring->id,
            'birth_weight_kg' => 3.2,
            'birth_status' => 'alive',
        ])->assertCreated();

        $this->assertSame('SPB', $offspring->fresh()->male_role);
    }

    public function test_imported_offspring_does_not_keep_sire_marker(): void
    {
        $this->actingAsAdmin();

        $alpine = Breed::query()->where('breed_name', 'Alpine')->firstOrFail();
        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-IMP-'.uniqid()]);
        $sire = $this->createAnimal([
            'breed_id' => $alpine->id,
            'sex' => 'male',
            'generation' => 'Pure Breed',
            'tag_number' => 'SIRE-IMP-'.uniqid(),
        ]);
        $offspring = $this->createAnimal([
            'sex' => 'female',
            'tag_number' => 'KID-IMP-'.uniqid(),
            'birth_date' => '2026-07-20',
            'is_impor' => true,
            'male_role' => 'APB',
        ]);

        $birthEvent = BirthEvent::query()->create([
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
            'birth_place' => 'BBH Farm',
        ]);

        $this->postJson('/api/v1/offspring-births', [
            'birth_event_id' => $birthEvent->id,
            'offspring_animal_id' => $offspring->id,
            'birth_weight_kg' => 3.1,
            'birth_status' => 'alive',
        ])->assertCreated();

        $this->assertNull($offspring->fresh()->male_role);
    }

    public function test_new_animal_eartag_uses_birth_year_and_three_digit_sequence(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/animals', [
            'breed_id' => $this->breed()->id,
            'sex' => 'female',
            'generation' => 'F1',
            'birth_date' => '2026-07-20',
            'birth_place' => 'BBH Farm',
            'life_status' => 'alive',
            'is_impor' => false,
            'photo_path' => '../storage/private.pem',
        ])->assertCreated();

        $this->assertSame('26-001', $response->json('data.tag_number'));
        $this->assertNull(Animal::query()->findOrFail($response->json('data.id'))->photo_path);
    }

    public function test_new_offspring_eartag_appends_jantan_pemacek_marker_from_pure_breed_sire(): void
    {
        $this->actingAsAdmin();

        $saanen = Breed::query()->where('breed_name', 'Saanen')->firstOrFail();
        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-TAG-'.uniqid()]);
        $sire = $this->createAnimal([
            'breed_id' => $saanen->id,
            'sex' => 'male',
            'generation' => 'Pure Breed',
            'tag_number' => 'SIRE-TAG-'.uniqid(),
        ]);

        $birthEvent = BirthEvent::query()->create([
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
            'birth_place' => 'BBH Farm',
        ]);

        $response = $this->postJson('/api/v1/offspring-births', [
            'birth_event_id' => $birthEvent->id,
            'breed_id' => $saanen->id,
            'sex' => 'female',
            'generation' => 'F1',
            'birth_weight_kg' => 3.2,
            'birth_status' => 'alive',
        ])->assertCreated();

        $this->assertMatchesRegularExpression('/^26-\d{3}-SPB$/', $response->json('data.offspring_animal.tag_number'));
    }

    public function test_offspring_records_cannot_exceed_birth_event_count(): void
    {
        $this->actingAsAdmin();

        $saanen = Breed::query()->where('breed_name', 'Saanen')->firstOrFail();
        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-LIMIT-'.uniqid()]);
        $sire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'SIRE-LIMIT-'.uniqid()]);

        $birthEvent = BirthEvent::query()->create([
            'dam_id' => $dam->id,
            'sire_id' => $sire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
            'birth_place' => 'BBH Farm',
        ]);

        $this->postJson('/api/v1/offspring-births', [
            'birth_event_id' => $birthEvent->id,
            'breed_id' => $saanen->id,
            'sex' => 'female',
            'generation' => 'F1',
            'birth_weight_kg' => 3.2,
            'birth_status' => 'alive',
        ])->assertCreated();

        $this->postJson('/api/v1/offspring-births', [
            'birth_event_id' => $birthEvent->id,
            'breed_id' => $saanen->id,
            'sex' => 'male',
            'generation' => 'F1',
            'birth_weight_kg' => 3.1,
            'birth_status' => 'alive',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Jumlah cempe yang dicatat sudah sesuai dengan jumlah anak pada data kelahiran.');
    }
}
