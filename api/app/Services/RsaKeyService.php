<?php

namespace App\Services;

use App\Models\RsaKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RsaKeyService
{
    public const KEY_LENGTH = 2048;

    public function queryFor(Request $request): Builder
    {
        $query = RsaKey::query()->with(['user:id,name,first_name,last_name,email,role']);
        $user = $request->user();

        if (($user->role ?? null) !== 'super_admin') {
            $query->where('user_id', $user?->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (int) $request->query('is_active') ? 1 : 0);
        } elseif (! (int) $request->query('include_inactive', 0)) {
            $query->where('is_active', 1);
        }

        if ($request->filled('key_status')) {
            $query->where('key_status', (string) $request->query('key_status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($query) use ($search, $user) {
                $query->where('key_identifier', 'like', "%{$search}%")
                    ->orWhere('fingerprint_sha256', 'like', "%{$search}%");

                if (($user->role ?? null) === 'super_admin') {
                    $query->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        return $query->orderByDesc('is_active')->orderByDesc('id');
    }

    public function storePublicKey(array $data, User $user): array
    {
        $normalizedPem = trim($data['public_key_pem']);
        $publicKey = openssl_pkey_get_public($normalizedPem);

        if ($publicKey === false) {
            return $this->error('Peringatan: Format public key RSA tidak valid.');
        }

        $details = openssl_pkey_get_details($publicKey);
        $canonicalPublicKeyPem = is_array($details) && ! empty($details['key'])
            ? trim((string) $details['key'])
            : $normalizedPem;
        $fingerprint = $this->fingerprintPublicKey($canonicalPublicKeyPem);
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : false;

        if ($isActive) {
            return $this->error('Peringatan: RSA Key tanpa private key tidak dapat dijadikan key aktif.');
        }

        if ($this->fingerprintExists($canonicalPublicKeyPem)) {
            return $this->error('Peringatan: RSA Key dengan fingerprint tersebut sudah terdaftar.');
        }

        $row = DB::transaction(fn () => RsaKey::query()->create([
            'user_id' => $user->id,
            'key_identifier' => $data['key_identifier'],
            'public_key_pem' => $canonicalPublicKeyPem,
            'algorithm' => 'RSA',
            'key_length' => $data['key_length'],
            'fingerprint_sha256' => $fingerprint,
            'is_active' => 0,
            'key_status' => 'retired',
            'retired_at' => now(),
            'status_reason' => 'Public key disimpan untuk arsip/verifikasi.',
        ])->fresh());

        return $this->success('Sukses: RSA Key berhasil disimpan.', $row, 201);
    }

    public function generate(array $data, User $user): array
    {
        $keyIdentifier = $data['key_identifier']
            ?? 'BBH-RSA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        $keyResource = openssl_pkey_new([
            'private_key_bits' => self::KEY_LENGTH,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ] + $this->opensslConfigArgs());

        if ($keyResource === false) {
            return $this->error('Gagal: Gagal membuat kunci digital. Pastikan OpenSSL tersedia pada server.');
        }

        $privateKeyPem = '';
        $passphrase = config('bbh_signing.private_key_passphrase');
        $exported = openssl_pkey_export(
            $keyResource,
            $privateKeyPem,
            is_string($passphrase) && $passphrase !== '' ? $passphrase : null,
            $this->opensslConfigArgs()
        );

        if ($exported !== true || trim($privateKeyPem) === '') {
            return $this->error('Gagal: Private key RSA gagal diekspor.');
        }

        $keyDetails = openssl_pkey_get_details($keyResource);
        if (! is_array($keyDetails) || empty($keyDetails['key'])) {
            return $this->error('Gagal: Public key RSA hasil generate gagal dibaca.');
        }

        $publicKeyPem = trim($keyDetails['key']);
        if ($this->fingerprintExists($publicKeyPem)) {
            return $this->error('Peringatan: RSA Key dengan fingerprint tersebut sudah terdaftar.');
        }

        $privateKeyPath = $this->privateKeyPath($keyIdentifier);
        if (is_file($privateKeyPath)) {
            return $this->error('Peringatan: File private key untuk key identifier tersebut sudah ada.');
        }

        if (File::put($privateKeyPath, Crypt::encryptString($privateKeyPem), true) === false) {
            return $this->error('Gagal: File private key RSA gagal disimpan.');
        }

        @chmod($privateKeyPath, 0600);
        $fingerprint = $this->fingerprintPublicKey($publicKeyPem);

        $row = DB::transaction(function () use ($keyIdentifier, $publicKeyPem, $privateKeyPath, $fingerprint, $user) {
            $row = RsaKey::query()->create([
                'user_id' => $user->id,
                'key_identifier' => $keyIdentifier,
                'public_key_pem' => $publicKeyPem,
                'private_key_path' => $privateKeyPath,
                'algorithm' => 'RSA',
                'key_length' => self::KEY_LENGTH,
                'fingerprint_sha256' => $fingerprint,
                'is_active' => 1,
                'key_status' => 'active',
                'retired_at' => null,
                'compromised_at' => null,
                'status_reason' => 'RSA Key aktif hasil generate.',
            ]);

            $this->deactivateOtherKeys((int) $user->id, (int) $row->id);

            return $row->fresh();
        });

        return $this->success('Sukses: RSA Key berhasil dibuat dan diaktifkan.', $row, 201);
    }

    public function update(RsaKey $rsaKey, array $data): array
    {
        return DB::transaction(function () use ($data, $rsaKey) {
            $data['algorithm'] = 'RSA';

            if (array_key_exists('is_active', $data)) {
                $activeResult = $this->applyActiveState($rsaKey, (bool) $data['is_active'], $data);
                if ($activeResult['ok'] === false) {
                    return $activeResult;
                }
                $data = $activeResult['data'];
            }

            $rsaKey->fill($data)->save();

            if ((int) $rsaKey->is_active === 1) {
                $this->deactivateOtherKeys((int) $rsaKey->user_id, (int) $rsaKey->id);
            }

            return $this->success('Sukses: RSA Key berhasil diperbarui.', $rsaKey->fresh());
        });
    }

    public function activate(RsaKey $rsaKey): array
    {
        return DB::transaction(function () use ($rsaKey) {
            $guard = $this->canActivate($rsaKey);
            if ($guard !== null) {
                return $guard;
            }

            $rsaKey->update([
                'is_active' => 1,
                'key_status' => 'active',
                'retired_at' => null,
                'compromised_at' => null,
                'status_reason' => 'Diaktifkan kembali melalui dashboard.',
            ]);

            $this->deactivateOtherKeys((int) $rsaKey->user_id, (int) $rsaKey->id);

            return $this->success('Sukses: RSA Key berhasil diaktifkan.', $rsaKey->fresh());
        });
    }

    public function deactivate(RsaKey $rsaKey): array
    {
        if ((int) $rsaKey->is_active === 0) {
            return $this->error('Peringatan: RSA Key ini sudah nonaktif.');
        }

        if ($this->activeOtherCount($rsaKey) === 0) {
            return $this->error('Peringatan: Minimal harus ada satu RSA Key yang aktif.');
        }

        $rsaKey->update([
            'is_active' => 0,
            'key_status' => 'retired',
            'retired_at' => now(),
            'status_reason' => 'Dinonaktifkan melalui dashboard.',
        ]);

        return $this->success('Sukses: RSA Key berhasil dinonaktifkan.', $rsaKey->fresh());
    }

    public function markCompromised(RsaKey $rsaKey, ?string $reason = null): array
    {
        if ($rsaKey->key_status === 'compromised') {
            return $this->error('Peringatan: RSA Key ini sudah dinonaktifkan.');
        }

        $rsaKey->update([
            'is_active' => 0,
            'key_status' => 'compromised',
            'compromised_at' => now(),
            'status_reason' => $reason ?? 'Key dicurigai bocor atau tidak lagi aman digunakan.',
        ]);

        return $this->success('Sukses: RSA Key berhasil dinonaktifkan dan tidak dapat digunakan untuk tanda tangan baru.', $rsaKey->fresh());
    }

    public function authorizeKeyAccess(RsaKey $rsaKey, ?User $user): void
    {
        if (($user->role ?? null) === 'super_admin') {
            return;
        }

        if ($rsaKey->user_id !== $user?->id) {
            abort(404);
        }
    }

    private function applyActiveState(RsaKey $rsaKey, bool $newActive, array $data): array
    {
        if ($newActive) {
            $guard = $this->canActivate($rsaKey);
            if ($guard !== null) {
                return $guard;
            }

            $data['is_active'] = 1;
            $data['key_status'] = 'active';
            $data['retired_at'] = null;
            $data['compromised_at'] = null;

            return $this->rawSuccess($data);
        }

        if ($this->activeOtherCount($rsaKey) === 0 && (int) $rsaKey->is_active === 1) {
            return $this->error('Peringatan: Minimal harus ada satu RSA Key yang aktif.');
        }

        $data['is_active'] = 0;
        $data['key_status'] = 'retired';
        $data['retired_at'] = now();
        $data['status_reason'] = $data['status_reason'] ?? 'Dinonaktifkan melalui pembaruan RSA Key.';

        return $this->rawSuccess($data);
    }

    private function canActivate(RsaKey $rsaKey): ?array
    {
        if (! $rsaKey->has_private_key) {
            return $this->error('Peringatan: RSA Key tanpa private key tidak dapat dijadikan key aktif.');
        }

        if ($rsaKey->key_status === 'compromised') {
            return $this->error('Peringatan: RSA Key tidak dapat diaktifkan kembali karena sudah dinonaktifkan.');
        }

        return null;
    }

    private function deactivateOtherKeys(int $userId, int $activeKeyId): void
    {
        RsaKey::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $activeKeyId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'key_status' => 'retired',
                'retired_at' => now(),
                'status_reason' => 'Retired otomatis karena rotasi RSA Key.',
            ]);
    }

    private function activeOtherCount(RsaKey $rsaKey): int
    {
        return RsaKey::query()
            ->where('user_id', $rsaKey->user_id)
            ->where('id', '!=', $rsaKey->id)
            ->where('is_active', 1)
            ->count();
    }

    private function privateKeyPath(string $keyIdentifier): string
    {
        $directory = storage_path('app/keys/rsa');
        File::ensureDirectoryExists($directory, 0700, true);

        return $directory.DIRECTORY_SEPARATOR.$this->safeKeyFileName($keyIdentifier).'_private.pem.enc';
    }

    private function safeKeyFileName(string $keyIdentifier): string
    {
        $slug = Str::slug($keyIdentifier, '_');

        return $slug !== '' ? $slug : 'rsa_key_'.now()->format('YmdHis');
    }

    private function fingerprintExists(string $pem): bool
    {
        return RsaKey::query()->whereIn('fingerprint_sha256', $this->fingerprintsForPublicKey($pem))->exists();
    }

    private function normalizePem(string $pem): string
    {
        return preg_replace('/\s+/', '', trim($pem)) ?? '';
    }

    private function fingerprintPublicKey(string $pem): string
    {
        return hash('sha256', $this->normalizePem($pem));
    }

    private function fingerprintsForPublicKey(string $pem): array
    {
        return array_values(array_unique([
            $this->fingerprintPublicKey($pem),
            hash('sha256', trim($pem)),
        ]));
    }

    private function opensslConfigArgs(): array
    {
        $configPath = config('bbh.openssl_conf');

        if (is_string($configPath) && $configPath !== '' && is_file($configPath)) {
            return ['config' => $configPath];
        }

        return [];
    }

    private function success(string $message, mixed $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'data' => $data];
    }

    private function rawSuccess(mixed $data): array
    {
        return ['ok' => true, 'data' => $data];
    }

    private function error(string $message, int $status = 422): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message];
    }
}
