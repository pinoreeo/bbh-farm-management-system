<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthSessionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_shows_validation_error_when_api_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@farm.local',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_login_rotates_session_id_before_storing_api_token(): void
    {
        Http::fake(['*' => Http::response([
            'access_token' => 'new-token',
            'user' => ['id' => 1, 'role' => 'admin'],
        ])]);
        $this->withSession(['old_flag' => 'keep']);
        $oldId = session()->getId();

        $this->post('/login', ['email' => 'admin@farm.local', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('bbh_api_token', 'new-token')
            ->assertSessionHas('old_flag', 'keep');

        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_forgot_password_shows_validation_error_when_api_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->from('/lupa-kata-sandi')
            ->post('/lupa-kata-sandi', [
                'email' => 'admin@farm.local',
            ])
            ->assertRedirect('/lupa-kata-sandi')
            ->assertSessionHasErrors('email');
    }

    public function test_reset_password_shows_validation_error_when_api_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->from('/reset-kata-sandi/token?email=admin@farm.local')
            ->post('/reset-kata-sandi', [
                'email' => 'admin@farm.local',
                'token' => 'token',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/reset-kata-sandi/token?email=admin@farm.local')
            ->assertSessionHasErrors('email');
    }

    public function test_logout_clears_local_session_when_api_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->withSession([
            'bbh_api_token' => 'token',
            'bbh_admin_user' => ['id' => 1],
            'other_session_data' => 'discard',
        ]);
        $oldId = session()->getId();

        $this
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('bbh_api_token')
            ->assertSessionMissing('bbh_admin_user')
            ->assertSessionMissing('other_session_data');

        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_reset_delivery_failure_does_not_claim_email_is_unregistered(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

        $this->from('/lupa-kata-sandi')->post('/lupa-kata-sandi', ['email' => 'admin@farm.local'])
            ->assertSessionHasErrors(['email' => 'Gagal: Tautan reset password belum dapat dikirim. Silakan coba lagi.']);
    }

    public function test_reset_request_keeps_the_conditional_success_message(): void
    {
        Http::fake(['*' => Http::response(['message' => 'OK'], 200)]);

        $this->from('/lupa-kata-sandi')->post('/lupa-kata-sandi', ['email' => 'admin@farm.local'])
            ->assertSessionHas('status', 'Info: Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.');
    }

    public function test_login_server_error_does_not_expose_diagnostics(): void
    {
        Http::fake(['*' => Http::response(['message' => 'SQLSTATE exception at port 8000'], 500)]);

        $this->from('/login')->post('/login', ['email' => 'admin@farm.local', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.']);
    }

    public function test_reset_password_validation_states_the_minimum_length(): void
    {
        Http::fake();

        $this->from('/reset-kata-sandi/token')->post('/reset-kata-sandi', [
            'email' => 'admin@farm.local',
            'token' => 'token',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['password' => 'Peringatan: Password minimal 8 karakter.']);

        Http::assertNothingSent();
    }
}
