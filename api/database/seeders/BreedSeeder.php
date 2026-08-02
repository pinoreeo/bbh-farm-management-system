<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreedSeeder extends Seeder
{
    /**
     * Seed goat breed master data.
     */
    public function run(): void
    {
        foreach ($this->animal_breeds() as $breed) {
            DB::table('animal_breeds')->updateOrInsert(
                ['breed_name' => $breed['breed_name']],
                array_merge($breed, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function animal_breeds(): array
    {
        return [
            [
                'breed_name' => 'Saanen',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Alpine',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Toggenburg',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Alpera',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Alsapera',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Sapera',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Peranakan Etawa',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Anglo-Nubian',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'LaMancha',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Oberhasli',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Nigerian Dwarf',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Golden Guernsey',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Sable goat',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Murciano-Granadina',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'British Alpine',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Jamnapari',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Beetal',
                'description' => 'Dummy',
                'is_active' => true,
            ],
            [
                'breed_name' => 'Damascus goat',
                'description' => 'Dummy',
                'is_active' => true,
            ],
        ];
    }
}
