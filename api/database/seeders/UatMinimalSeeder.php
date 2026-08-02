<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UatMinimalSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $breeds = $this->seedBreeds();
        $this->seedAnimals($breeds);
    }

    private function seedUsers(): void
    {
        foreach ([
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@bbh.test',
                'role' => 'super_admin',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Satu',
                'email' => 'admin1@bbh.test',
                'role' => 'admin',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Dua',
                'email' => 'admin2@bbh.test',
                'role' => 'admin',
            ],
        ] as $row) {
            User::query()->create([
                'name' => trim($row['first_name'].' '.$row['last_name']),
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => $row['role'],
                'is_active' => true,
                'remember_token' => null,
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function seedBreeds(): array
    {
        $names = [
            'Saanen',
            'Alpine',
            'Sapera',
            'Toggenburg',
            'Peranakan Etawa',
        ];

        return collect($names)
            ->mapWithKeys(function (string $name): array {
                $breed = Breed::query()->create([
                    'breed_name' => $name,
                    'description' => null,
                    'is_active' => true,
                ]);

                return [$name => $breed->id];
            })
            ->all();
    }

    /**
     * @param  array<string, int>  $breeds
     */
    private function seedAnimals(array $breeds): void
    {
        $rows = [
            ['tag_number' => 'BBH-24-001-SPB', 'breed_id' => $breeds['Saanen'], 'sex' => 'male', 'male_role' => 'SPB', 'generation' => 'Pure Breed', 'birth_date' => '2024-01-12'],
            ['tag_number' => 'BBH-24-002-APB', 'breed_id' => $breeds['Alpine'], 'sex' => 'male', 'male_role' => 'APB', 'generation' => 'Pure Breed', 'birth_date' => '2024-02-20'],
            ['tag_number' => 'BBH-25-003', 'breed_id' => $breeds['Sapera'], 'sex' => 'male', 'male_role' => null, 'generation' => 'F2', 'birth_date' => '2025-06-18'],
            ['tag_number' => 'BBH-24-004', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-03-04'],
            ['tag_number' => 'BBH-24-005', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-04-09'],
            ['tag_number' => 'BBH-24-006', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F3', 'birth_date' => '2024-04-30'],
            ['tag_number' => 'BBH-24-007', 'breed_id' => $breeds['Peranakan Etawa'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-05-11'],
            ['tag_number' => 'BBH-24-008', 'breed_id' => $breeds['Toggenburg'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2024-06-22'],
            ['tag_number' => 'BBH-24-009', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2024-07-16'],
            ['tag_number' => 'BBH-24-010', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'generation' => 'F3', 'birth_date' => '2024-08-01'],
            ['tag_number' => 'BBH-25-011', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2025-08-12'],
            ['tag_number' => 'BBH-25-012', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F2', 'birth_date' => '2025-09-20'],
            ['tag_number' => 'BBH-25-013', 'breed_id' => $breeds['Peranakan Etawa'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2025-10-07'],
            ['tag_number' => 'BBH-25-014', 'breed_id' => $breeds['Toggenburg'], 'sex' => 'female', 'generation' => 'F1', 'birth_date' => '2025-11-02'],
            ['tag_number' => 'BBH-26-015-SPB', 'breed_id' => $breeds['Saanen'], 'sex' => 'female', 'male_role' => 'SPB', 'generation' => 'F3', 'birth_date' => '2026-01-15'],
            ['tag_number' => 'BBH-26-016-SPB', 'breed_id' => $breeds['Saanen'], 'sex' => 'male', 'male_role' => 'SPB', 'generation' => 'F3', 'birth_date' => '2026-01-15'],
            ['tag_number' => 'BBH-26-017-APB', 'breed_id' => $breeds['Alpine'], 'sex' => 'female', 'male_role' => 'APB', 'generation' => 'F3', 'birth_date' => '2026-02-18'],
            ['tag_number' => 'BBH-26-018-APB', 'breed_id' => $breeds['Alpine'], 'sex' => 'male', 'male_role' => 'APB', 'generation' => 'F3', 'birth_date' => '2026-02-18'],
            ['tag_number' => 'BBH-26-019', 'breed_id' => $breeds['Sapera'], 'sex' => 'female', 'generation' => 'F4', 'birth_date' => '2026-03-21'],
            ['tag_number' => 'BBH-26-020', 'breed_id' => $breeds['Sapera'], 'sex' => 'male', 'generation' => 'F4', 'birth_date' => '2026-03-21'],
        ];

        foreach ($rows as $row) {
            Animal::query()->create(array_merge([
                'male_role' => null,
                'birth_place' => 'BBH Farm Ajibarang',
                'current_pen_id' => null,
                'reproductive_status' => 'kosong',
                'status_date' => $row['birth_date'],
                'life_status' => 'alive',
                'exit_status' => null,
                'notes' => null,
                'is_impor' => false,
                'origin_type' => 'internal_birth',
                'origin_detail' => 'Data awal UAT.',
            ], $row));
        }
    }
}
