<?php

namespace Tests\Feature\Operational;

use App\Models\AnimalPenMovement;
use App\Models\WeightRecord;
use App\Services\AnimalService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Support\ApiTestCase;

class AnimalRecordConsistencyTest extends ApiTestCase
{
    public function test_pen_can_only_be_changed_through_movement_record(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $pen = $this->createPen(['colony_type' => 'koloni_laktasi', 'colony_phase' => 'koloni_laktasi']);

        $this->putJson('/api/v1/animals/'.$animal->id, ['current_pen_id' => $pen->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_pen_id');
        $this->assertNull($animal->fresh()->current_pen_id);
    }

    public function test_identity_changes_cannot_contradict_recorded_history(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $offspring = $workflow['offspring'];

        $this->putJson('/api/v1/animals/'.$offspring->id, ['birth_date' => '2026-05-18'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Tanggal lahir harus sama dengan tanggal pada catatan kelahiran.');
        $this->putJson('/api/v1/animals/'.$offspring->id, ['origin_type' => 'purchase'])
            ->assertUnprocessable();

        $dam = $workflow['dam'];
        $this->putJson('/api/v1/animals/'.$dam->id, ['sex' => 'male'])
            ->assertUnprocessable();

        $animal = $this->createAnimal(['birth_date' => '2024-01-01']);
        WeightRecord::query()->create(['animal_id' => $animal->id, 'record_date' => '2025-01-01', 'weight_kg' => 20]);
        $this->putJson('/api/v1/animals/'.$animal->id, ['birth_date' => '2025-01-02'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Tanggal lahir tidak boleh setelah tanggal catatan pertama kambing.');
    }

    public function test_backdated_movement_is_inserted_without_changing_the_latest_pen(): void
    {
        $this->travelTo(Carbon::parse('2026-10-02'));
        $this->actingAsAdmin();
        $penA = $this->pen('A');
        $penB = $this->pen('B');
        $penC = $this->pen('C');
        $animal = $this->createAnimal(['current_pen_id' => $penA->id, 'status_date' => '2026-06-01']);

        $september = $this->postJson('/api/v1/animal-pen-movements', [
            'animal_id' => $animal->id, 'to_pen_id' => $penC->id, 'movement_date' => '2026-09-01',
        ])->assertCreated()->json('data.id');
        $july = $this->postJson('/api/v1/animal-pen-movements', [
            'animal_id' => $animal->id, 'to_pen_id' => $penB->id, 'movement_date' => '2026-07-01',
        ])->assertCreated()->json('data.id');

        $this->assertSame($penA->id, AnimalPenMovement::query()->findOrFail($july)->from_pen_id);
        $this->assertSame($penB->id, AnimalPenMovement::query()->findOrFail($september)->from_pen_id);
        $this->assertSame($penC->id, $animal->fresh()->current_pen_id);
        $this->assertSame('2026-06-01', $animal->fresh()->status_date->toDateString());

        $this->putJson('/api/v1/animal-pen-movements/'.$july, ['movement_date' => '2026-10-01'])->assertOk();
        $this->assertSame($penA->id, AnimalPenMovement::query()->findOrFail($september)->from_pen_id);
        $this->assertSame($penC->id, AnimalPenMovement::query()->findOrFail($july)->from_pen_id);
        $this->assertSame($penB->id, $animal->fresh()->current_pen_id);
    }

    public function test_old_photo_survives_when_database_update_fails(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('animals/old.jpg', 'old-photo');
        $animal = $this->createAnimal(['photo_path' => 'animals/old.jpg']);
        DB::unprepared("CREATE TRIGGER fail_animal_photo_update BEFORE UPDATE ON animals BEGIN SELECT RAISE(ABORT, 'simulated photo failure'); END");

        try {
            app(AnimalService::class)->update($this->photoRequest(), $animal, []);
            $this->fail('The database update should fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('simulated photo failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER fail_animal_photo_update');
        }

        Storage::disk('public')->assertExists('animals/old.jpg');
        $this->assertSame(['animals/old.jpg'], Storage::disk('public')->allFiles('animals'));
        $this->assertSame('animals/old.jpg', $animal->fresh()->photo_path);
    }

    public function test_old_photo_is_removed_after_successful_replacement(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('animals/old.jpg', 'old-photo');
        $animal = $this->createAnimal(['photo_path' => 'animals/old.jpg']);

        app(AnimalService::class)->update($this->photoRequest(), $animal, []);

        Storage::disk('public')->assertMissing('animals/old.jpg');
        Storage::disk('public')->assertExists($animal->fresh()->photo_path);
    }

    private function pen(string $suffix)
    {
        return $this->createPen([
            'pen_code' => 'PEN-'.$suffix.'-'.uniqid(),
            'colony_type' => 'koloni_laktasi',
            'colony_phase' => 'koloni_laktasi',
        ]);
    }

    private function photoRequest(): Request
    {
        $request = Request::create('/', 'POST');
        $request->files->set('photo', UploadedFile::fake()->image('new.jpg', 20, 20));

        return $request;
    }
}
