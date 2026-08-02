<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default admin account.
     */
    public function run(): void
    {
        $adminEmail = $this->stringConfig('bbh.admin.email', 'admin@example.com');
        $adminPassword = $this->stringConfig('bbh.admin.password', 'password');
        $adminName = $this->stringConfig('bbh.admin.name', 'John Doe');
        $nameParts = preg_split('/\s+/', trim($adminName), 2) ?: [];

        if ($adminPassword === '' || (! app()->environment('local') && $adminPassword === 'password')) {
            throw new \RuntimeException('BBH_ADMIN_PASSWORD must be configured before seeding the default admin.');
        }

        DB::table('sys_users')->updateOrInsert(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'first_name' => $nameParts[0] ?? $adminName,
                'last_name' => $nameParts[1] ?? null,
                'email_verified_at' => now(),
                'password' => Hash::make($adminPassword),
                'role' => 'super_admin',
                'is_active' => true,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
