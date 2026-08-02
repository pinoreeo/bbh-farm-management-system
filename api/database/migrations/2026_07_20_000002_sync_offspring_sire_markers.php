<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $offspringRows = DB::table('breed_offsprings')
            ->join('breed_births', 'breed_births.id', '=', 'breed_offsprings.birth_event_id')
            ->join('animals as offspring', 'offspring.id', '=', 'breed_offsprings.offspring_animal_id')
            ->leftJoin('animals as sire', 'sire.id', '=', 'breed_births.sire_id')
            ->leftJoin('animal_breeds as sire_breed', 'sire_breed.id', '=', 'sire.breed_id')
            ->where('offspring.is_impor', false)
            ->select([
                'offspring.id as offspring_id',
                'sire.generation as sire_generation',
                'sire_breed.breed_name as sire_breed_name',
            ])
            ->get();

        foreach ($offspringRows as $row) {
            $breedName = strtolower((string) $row->sire_breed_name);
            $marker = match (true) {
                $row->sire_generation === 'Pure Breed' && str_contains($breedName, 'saanen') => 'SPB',
                $row->sire_generation === 'Pure Breed' && str_contains($breedName, 'alpine') => 'APB',
                default => null,
            };

            DB::table('animals')
                ->where('id', $row->offspring_id)
                ->update(['male_role' => $marker]);
        }

        DB::table('animals')
            ->where('is_impor', true)
            ->update(['male_role' => null]);
    }

    public function down(): void
    {
        // Marker values are derived from parent records, so rollback does not restore manual values.
    }
};
