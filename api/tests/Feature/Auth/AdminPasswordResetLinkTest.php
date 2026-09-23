<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\ApiTestCase;

class AdminPasswordResetLinkTest extends ApiTestCase
{
    public function test_super_admin_can_send_a_reset_link_to_an_active_admin_only(): void
    {
        Mail::fake();
        $this->actingAsAdmin();

        $admin = User::query()->create([
            'name' => 'Admin Lapangan',
            'email' => 'lapangan@bbhfarm.test',
            'password' => Hash::make('password-admin'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->postJson("/api/v1/users/{$admin->id}/send-password-reset")
            ->assertOk()
            ->assertJsonPath('message', 'Sukses: Tautan reset password telah dikirim ke email admin.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $admin->email,
        ]);

        $this->putJson("/api/v1/users/{$admin->id}", [
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'password_confirmation']);

        $admin->update(['is_active' => false]);
        DB::table('password_reset_tokens')->where('email', $admin->email)->delete();

        $this->postJson("/api/v1/users/{$admin->id}/send-password-reset")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Aktifkan akun admin terlebih dahulu sebelum mengirim tautan reset password.');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $admin->email,
        ]);
    }
}
