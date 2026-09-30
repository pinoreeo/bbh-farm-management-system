<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_seeder_creates_only_the_three_requested_accounts(): void
    {
        config([
            'bbh.admin.name' => 'Super Admin',
            'bbh.admin.email' => 'superadmin@bbhfarm.com',
            'bbh.admin.password' => 'ExamplePassword123!',
        ]);

        $this->seed(AccountSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertDatabaseHas('sys_users', ['email' => 'superadmin@bbhfarm.com', 'role' => 'super_admin', 'is_active' => true]);
        $this->assertDatabaseHas('sys_users', ['email' => 'admin1@bbhfarm.com', 'role' => 'admin', 'is_active' => true]);
        $this->assertDatabaseHas('sys_users', ['email' => 'admin2@bbhfarm.com', 'role' => 'admin', 'is_active' => true]);

        $passwordHashes = User::query()->where('role', 'admin')->pluck('password', 'email')->all();
        $this->assertNotSame($passwordHashes['admin1@bbhfarm.com'], $passwordHashes['admin2@bbhfarm.com']);

        $this->seed(AccountSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertSame($passwordHashes, User::query()->where('role', 'admin')->pluck('password', 'email')->all());
    }
}
