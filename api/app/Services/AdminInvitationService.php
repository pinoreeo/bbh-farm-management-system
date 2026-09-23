<?php

namespace App\Services;

use App\Jobs\SendAdminInvitationMail;
use App\Models\AdminInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminInvitationService
{
    public function send(User $user): void
    {
        $token = Str::random(64);
        AdminInvitation::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'token_hash' => Hash::make($token),
                'expires_at' => now()->addMinutes(60),
            ]
        );

        $baseUrl = config('bbh.public_web_url') ?: config('app.url');
        $inviteUrl = rtrim(is_string($baseUrl) ? $baseUrl : '', '/')
            .'/aktifkan-akun/'.rawurlencode($token)
            .'?email='.urlencode($user->email);

        SendAdminInvitationMail::dispatch($user->email, $inviteUrl)->afterCommit();
    }

    public function accept(string $email, string $token, string $password): void
    {
        DB::transaction(function () use ($email, $token, $password) {
            $user = User::query()->where('email', $email)->where('role', 'admin')->lockForUpdate()->first();
            $invitation = $user instanceof User
                ? AdminInvitation::query()->where('user_id', $user->id)->lockForUpdate()->first()
                : null;

            if (! $user instanceof User || ! $invitation || $invitation->expires_at->isPast() || ! Hash::check($token, $invitation->token_hash)) {
                throw ValidationException::withMessages([
                    'token' => ['Peringatan: Tautan undangan tidak valid atau telah kedaluwarsa.'],
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
            $invitation->delete();
        }, 3);
    }
}
