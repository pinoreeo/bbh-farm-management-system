<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffAdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'admin1@bbhfarm.com' => '1',
            'admin2@bbhfarm.com' => '2',
        ] as $email => $number) {
            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin '.$number,
                    'first_name' => 'Admin',
                    'last_name' => $number,
                    'password' => Hash::make(Str::random(48)),
                    'role' => 'admin',
                    'is_active' => true,
                ]
            );
        }
    }
}
