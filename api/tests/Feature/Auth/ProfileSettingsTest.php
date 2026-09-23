<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\ApiTestCase;

class ProfileSettingsTest extends ApiTestCase
{
    public function test_admin_can_update_user_and_farm_profiles(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Admin Penelitian',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Admin Penelitian');

        $this->assertDatabaseHas('sys_users', [
            'id' => $this->admin->id,
            'name' => 'Admin Penelitian',
        ]);

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Admin Penelitian',
            'email' => 'alamat-baru@bbhfarm.test',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('sys_users', [
            'id' => $this->admin->id,
            'email' => $this->admin->email,
        ]);

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Admin Penelitian',
            'phone' => '081234567890',
        ])->assertOk()
            ->assertJsonPath('user.phone', '081234567890');

        $this->assertDatabaseHas('sys_users', [
            'id' => $this->admin->id,
            'phone' => '081234567890',
        ]);

        $this->putJson('/api/v1/farm', [
            'farm_name' => 'BBH Farm Penelitian',
            'address' => 'Ajibarang, Banyumas',
            'phone' => '081234567890',
            'email' => 'farm@bbhfarm.test',
        ])->assertOk()
            ->assertJsonPath('data.farm_name', 'BBH Farm Penelitian');

        $this->getJson('/api/v1/farm')->assertOk()
            ->assertJsonPath('farm_name', 'BBH Farm Penelitian')
            ->assertJsonPath('email', 'farm@bbhfarm.test');

        $this->assertDatabaseHas('sys_farm_profiles', [
            'farm_name' => 'BBH Farm Penelitian',
            'email' => 'farm@bbhfarm.test',
        ]);
    }

    public function test_regular_admin_cannot_access_farm_profile(): void
    {
        $regularAdmin = User::query()->create([
            'name' => 'Admin Operasional',
            'email' => 'admin-operasional@bbhfarm.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($regularAdmin);

        $this->getJson('/api/v1/farm')->assertForbidden();
        $this->putJson('/api/v1/farm', [
            'farm_name' => 'Tidak Diizinkan',
        ])->assertForbidden();
    }
}
