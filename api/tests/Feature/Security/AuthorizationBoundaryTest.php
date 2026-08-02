<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\ApiTestCase;

class AuthorizationBoundaryTest extends ApiTestCase
{
    public function test_regular_admin_cannot_access_super_admin_audit_surfaces(): void
    {
        $regularAdmin = User::query()->create([
            'name' => 'Regular Admin',
            'email' => 'regular-admin@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($regularAdmin);

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->getJson('/api/v1/admin-activity-logs')->assertForbidden();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users')->assertOk();
        $this->getJson('/api/v1/admin-activity-logs')->assertOk();
    }

    public function test_malformed_public_qr_token_is_rejected_before_lookup(): void
    {
        $this->getJson('/api/v1/public/certificates/verify/not-a-valid-token')
            ->assertNotFound()
            ->assertJsonPath('is_valid', false);
    }
}
