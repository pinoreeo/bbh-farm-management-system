<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiConnectionFailureTest extends TestCase
{
    public function test_profile_update_returns_to_form_when_api_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->withSession([
            'bbh_api_token' => 'test-token',
            'bbh_admin_user' => ['role' => 'admin'],
        ])->from('/admin/profile')->put('/admin/profile/user', [
            'user_name' => 'Audit Admin',
        ])->assertRedirect('/admin/profile')
            ->assertSessionHas('adminApiStatus', 'Layanan data belum dapat diakses. Silakan coba lagi.');
    }

    public function test_uncaught_read_request_returns_service_unavailable_without_debug_page(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->withSession([
            'bbh_api_token' => 'test-token',
            'bbh_admin_user' => ['role' => 'super_admin'],
        ])->get('/admin/farm-profile')
            ->assertStatus(503)
            ->assertSeeText('Layanan data belum dapat diakses.')
            ->assertDontSee('Connection failed.');
    }

    public function test_failed_profile_update_message_is_not_overwritten_on_redirect(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection failed.'));

        $this->withSession([
            'bbh_api_token' => 'test-token',
            'bbh_admin_user' => ['role' => 'admin'],
        ])->followingRedirects()->from('/admin/profile')->put('/admin/profile/user', [
            'user_name' => 'Audit Admin',
        ])->assertOk()
            ->assertSeeText('Layanan data belum dapat diakses. Silakan coba lagi.');
    }
}
