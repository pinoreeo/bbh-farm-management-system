<?php

namespace Tests\Feature\Auth;

use App\Models\AdminInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\ApiTestCase;

class AdminAccountActivationTest extends ApiTestCase
{
    public function test_super_admin_creates_an_admin_that_activates_after_accepting_email_invitation(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', [
            'name' => 'Admin Lapangan',
            'email' => 'lapangan@bbhfarm.test',
            'phone' => '081234567890',
        ])->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.role', 'admin');

        $user = User::query()->where('email', 'lapangan@bbhfarm.test')->firstOrFail();
        $this->assertFalse($user->is_active);

        $invitation = AdminInvitation::query()->where('user_id', $user->id)->firstOrFail();
        $invitation->update(['token_hash' => Hash::make('undangan-rahasia')]);

        $this->postJson('/api/v1/public/admin-invitations/accept', [
            'email' => $user->email,
            'token' => 'undangan-rahasia',
            'password' => 'password-admin',
            'password_confirmation' => 'password-admin',
        ])->assertOk();

        $this->assertDatabaseHas('sys_users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
        $this->assertTrue(Hash::check('password-admin', $user->fresh()->password));
        $this->assertDatabaseMissing('sys_admin_invitations', ['user_id' => $user->id]);

        $this->postJson('/api/v1/public/admin-invitations/accept', [
            'email' => $user->email,
            'token' => 'undangan-rahasia',
            'password' => 'password-lain',
            'password_confirmation' => 'password-lain',
        ])->assertUnprocessable();
    }
}
