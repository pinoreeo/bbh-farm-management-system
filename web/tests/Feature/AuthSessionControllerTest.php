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
        ])
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('bbh_api_token')
            ->assertSessionMissing('bbh_admin_user');
    }
}
