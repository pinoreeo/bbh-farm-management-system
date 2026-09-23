<?php

namespace App\Services;

use App\Models\User;
use App\Support\TypeValue;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const LOGIN_ROLES = ['super_admin', 'admin'];

    public function __construct(private readonly AdminActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function login(Request $request, array $data): array
    {
        $email = TypeValue::string($data['email'] ?? '');
        $password = TypeValue::string($data['password'] ?? '');
        $throttleKey = $this->loginThrottleKey($email, $request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => ["Gagal Masuk: Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik."],
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password) || ! $this->canLogin($user)) {
            RateLimiter::hit($throttleKey, 300);
            $this->logFailedLogin($request, $email);

            throw ValidationException::withMessages(['email' => ['Gagal: Email atau password tidak sesuai.']]);
        }

        RateLimiter::clear($throttleKey);

        if ((bool) ($data['revoke_existing_tokens'] ?? false)) {
            $user->tokens()->delete();
        }

        $tokenName = TypeValue::nullableString($data['device_name'] ?? null) ?? 'api-token';
        $token = $user->createToken($tokenName)->plainTextToken;
        $user->forceFill(['last_login_at' => now()])->save();

        $request->setUserResolver(fn () => $user);
        $this->activityLogger->log($request, 200, 'login', 'auth', [
            'device_name' => $tokenName,
            'revoke_existing_tokens' => (bool) ($data['revoke_existing_tokens'] ?? false),
        ]);

        return [
            'message' => 'Sukses: Anda berhasil login.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $this->userPayload($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forgotPassword(array $data): array
    {
        $email = TypeValue::string($data['email'] ?? '');
        $user = User::query()
            ->where('email', $email)
            ->whereIn('role', self::LOGIN_ROLES)
            ->where('is_active', true)
            ->first();

        if ($user) {
            $token = Str::random(64);
            $resetUrl = $this->resetUrl($token);
            $separator = str_contains($resetUrl, '?') ? '&' : '?';
            $resetUrl .= $separator.'email='.urlencode($email);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            Mail::raw(
                "Gunakan tautan berikut untuk reset password akun BBH Farm:\n\n{$resetUrl}\n\nTautan berlaku selama 60 menit.",
                fn (Message $message): Message => $message
                    ->to($email)
                    ->subject('Reset Kata Sandi Admin BBH Farm')
            );
        }

        return ['message' => 'Info: Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function resetPassword(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $email = TypeValue::string($data['email'] ?? '');
            $user = User::query()->where('email', $email)
                ->whereIn('role', self::LOGIN_ROLES)->where('is_active', true)
                ->lockForUpdate()->first();
            $row = DB::table('password_reset_tokens')->where('email', $email)->lockForUpdate()->first();

            if (! $user || ! $row
                || ! Hash::check(TypeValue::string($data['token'] ?? ''), TypeValue::string(data_get($row, 'token')))
                || Carbon::parse(TypeValue::string(data_get($row, 'created_at')))->lt(now()->subMinutes(60))) {
                $this->throwExpiredResetLink();
            }

            $user->forceFill([
                'password' => Hash::make(TypeValue::string($data['password'] ?? '')),
            ])->save();
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return ['message' => 'Sukses: Password berhasil diperbarui. Silakan login dengan password baru.'];
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function changePassword(User $user, array $data): array
    {
        if (! Hash::check(TypeValue::string($data['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Peringatan: Password saat ini tidak sesuai.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make(TypeValue::string($data['password'] ?? '')),
        ])->save();

        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();

        return ['message' => 'Sukses: Password berhasil diperbarui.'];
    }

    public function logout(Request $request): void
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return;
        }

        $this->activityLogger->log($request, 200, 'logout', 'auth');
        $user->currentAccessToken()?->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function canLogin(User $user): bool
    {
        return in_array((string) ($user->role ?? ''), self::LOGIN_ROLES, true)
            && (bool) ($user->is_active ?? true);
    }

    private function loginThrottleKey(string $email, Request $request): string
    {
        return 'login-failed:'.Str::lower($email).'|'.$request->ip();
    }

    private function logFailedLogin(Request $request, string $email): void
    {
        $this->activityLogger->log($request, 401, 'login_failed', 'auth', [
            'email' => Str::lower($email),
        ]);
    }

    private function throwExpiredResetLink(): never
    {
        throw ValidationException::withMessages([
            'email' => ['Peringatan: Tautan reset password tidak valid atau sudah kedaluwarsa. Ajukan reset password kembali.'],
        ]);
    }

    private function resetUrl(string $token): string
    {
        $webUrlConfig = config('bbh.public_web_url') ?: config('app.url');
        $webUrl = rtrim(is_string($webUrlConfig) ? $webUrlConfig : '', '/');

        return $webUrl.'/reset-kata-sandi/'.rawurlencode($token);
    }
}
