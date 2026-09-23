<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\ApiTestCase;

class AuthPasswordResetTest extends ApiTestCase
{
    public function test_failure_deleting_reset_link_rolls_back_password_and_session_revocation(): void
    {
        $oldPassword = $this->admin->password;
        $this->admin->createToken('session-to-preserve-on-failure');
        DB::table('password_reset_tokens')->insert([
            'email' => $this->admin->email, 'token' => Hash::make('reset-token'), 'created_at' => now(),
        ]);
        DB::unprepared("CREATE TRIGGER fail_reset_delete BEFORE DELETE ON password_reset_tokens BEGIN SELECT RAISE(ABORT, 'simulated reset failure'); END");

        try {
            app(AuthService::class)->resetPassword([
                'email' => $this->admin->email, 'token' => 'reset-token', 'password' => 'NewPassword123',
            ]);
            $this->fail('Reset should fail when the last database operation fails.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('simulated reset failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER fail_reset_delete');
        }

        $this->assertSame($oldPassword, $this->admin->fresh()->password);
        $this->assertSame(1, $this->admin->tokens()->count());
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $this->admin->email]);
    }

    public function test_forgot_password_covers_validation_hidden_user_lookup_and_admin_mail_branches(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingValidationErrors(['reset_url_template']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'unknown@example.test',
        ])->assertOk()
            ->assertJsonPath('message', 'Info: Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'unknown@example.test',
        ]);
        Mail::assertSentCount(0);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $this->admin->email,
            'reset_url_template' => 'https://attacker.test/reset/{token}',
        ])->assertOk()
            ->assertJsonPath('message', 'Info: Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.');

        $row = DB::table('password_reset_tokens')->where('email', $this->admin->email)->first();

        $this->assertNotNull($row);
        $this->assertTrue(Hash::needsRehash((string) $row->token) === false);
    }

    public function test_reset_password_covers_confirmation_token_expiry_role_and_success_branches(): void
    {
        $token = 'valid-reset-token';

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->admin->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->admin->email,
            'token' => $token,
            'password' => 'Admin123',
            'password_confirmation' => 'Different123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->admin->email,
            'token' => 'wrong-token',
            'password' => 'Admin123',
            'password_confirmation' => 'Admin123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Tautan reset password tidak valid atau sudah kedaluwarsa. Ajukan reset password kembali.');

        DB::table('password_reset_tokens')->where('email', $this->admin->email)->update([
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(61),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->admin->email,
            'token' => $token,
            'password' => 'Admin123',
            'password_confirmation' => 'Admin123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Tautan reset password tidak valid atau sudah kedaluwarsa. Ajukan reset password kembali.');

        $operator = User::query()->create([
            'name' => 'Operator Reset',
            'email' => 'operator-reset@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'operator',
        ]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $operator->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $operator->email,
            'token' => $token,
            'password' => 'Admin123',
            'password_confirmation' => 'Admin123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Tautan reset password tidak valid atau sudah kedaluwarsa. Ajukan reset password kembali.');

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->admin->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $this->admin->createToken('existing-session');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->admin->email,
            'token' => $token,
            'password' => 'Admin123',
            'password_confirmation' => 'Admin123',
        ])->assertOk()
            ->assertJsonPath('message', 'Sukses: Password berhasil diperbarui. Silakan login dengan password baru.');

        $this->admin->refresh();

        $this->assertTrue(Hash::check('Admin123', $this->admin->password));
        $this->assertSame(0, $this->admin->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->admin->email,
        ]);

        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->admin->email, 'token' => $token,
            'password' => 'AnotherPassword123', 'password_confirmation' => 'AnotherPassword123',
        ])->assertUnprocessable();
        $this->assertTrue(Hash::check('Admin123', $this->admin->fresh()->password));
    }
}
