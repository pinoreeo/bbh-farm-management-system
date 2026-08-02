<?php

namespace Tests\Feature\BranchCoverage;

use App\Models\RsaKey;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\ApiTestCase;

class RsaKeyManagementBranchCoverageTest extends ApiTestCase
{
    public function test_rsa_key_management_covers_store_generate_update_activation_and_guard_branches(): void
    {
        $this->actingAsAdmin();
        RsaKey::query()->delete();

        $this->getJson('/api/v1/rsa-keys?is_active=1&search=BBH')
            ->assertOk();

        $this->postJson('/api/v1/rsa-keys', [
            'key_identifier' => 'INVALID-LENGTH',
            'public_key_pem' => $this->publicKeyPem(),
            'key_length' => 4096,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('key_length');

        $this->postJson('/api/v1/rsa-keys', [
            'key_identifier' => 'INVALID-PEM',
            'public_key_pem' => 'not-a-public-key',
            'key_length' => 2048,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Format public key RSA tidak valid.');

        $publicKeyPem = $this->publicKeyPem();

        $inactive = $this->postJson('/api/v1/rsa-keys', [
            'key_identifier' => 'VALID-INACTIVE-RSA',
            'public_key_pem' => $publicKeyPem,
            'key_length' => 2048,
            'is_active' => false,
        ])->assertCreated()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.key_status', 'retired');

        $this->getJson('/api/v1/rsa-keys?include_inactive=1&search=VALID-INACTIVE')
            ->assertOk()
            ->assertJsonFragment(['key_identifier' => 'VALID-INACTIVE-RSA']);

        $generatedId = (int) $inactive->json('data.id');

        $this->putJson('/api/v1/rsa-keys/'.$generatedId, [
            'key_identifier' => 'VALID-ACTIVE-RSA',
            'is_active' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key tanpa private key tidak dapat dijadikan key aktif.');

        $activeIdentifier = 'VALID-ACTIVE-RSA-'.uniqid();
        $generated = $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => $activeIdentifier,
            'key_length' => 2048,
        ])->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $activeId = (int) $generated->json('data.id');

        $this->postJson('/api/v1/rsa-keys/'.$activeId.'/deactivate')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Minimal harus ada satu RSA Key yang aktif.');

        $rotated = $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => 'VALID-ROTATED-RSA-'.uniqid(),
            'key_length' => 2048,
        ])->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $this->assertFalse((bool) RsaKey::query()->findOrFail($activeId)->is_active);
        $this->assertSame('retired', RsaKey::query()->findOrFail($activeId)->key_status);
        $this->assertTrue((bool) RsaKey::query()->findOrFail((int) $rotated->json('data.id'))->is_active);

        $this->postJson('/api/v1/rsa-keys/'.$activeId.'/deactivate')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key ini sudah nonaktif.');

        $this->postJson('/api/v1/rsa-keys/'.$activeId.'/compromise', [
            'status_reason' => 'Unit test compromised branch.',
        ])->assertOk()
            ->assertJsonPath('data.key_status', 'compromised')
            ->assertJsonPath('data.is_active', false);

        $this->postJson('/api/v1/rsa-keys/'.$activeId.'/activate')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key tidak dapat diaktifkan kembali karena sudah dinonaktifkan.');

        $this->postJson('/api/v1/rsa-keys/'.$generatedId.'/activate')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key tanpa private key tidak dapat dijadikan key aktif.');

        $this->postJson('/api/v1/rsa-keys', [
            'key_identifier' => 'DUPLICATE-FINGERPRINT-RSA',
            'public_key_pem' => $publicKeyPem,
            'key_length' => 2048,
            'is_active' => false,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key dengan fingerprint tersebut sudah terdaftar.');

        $this->getJson('/api/v1/rsa-keys/'.$inactive->json('data.id'))
            ->assertOk()
            ->assertJsonPath('key_identifier', 'VALID-INACTIVE-RSA');

        $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => 'INVALID-GENERATE-LENGTH',
            'key_length' => 4096,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('key_length');

        $this->putJson('/api/v1/rsa-keys/'.$activeId, [
            'public_key_pem' => 'invalid-public-key',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Public key dan panjang RSA key tidak dapat diubah setelah dibuat. Buat key baru jika diperlukan.');

        $this->putJson('/api/v1/rsa-keys/'.$activeId, [
            'key_length' => 4096,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Public key dan panjang RSA key tidak dapat diubah setelah dibuat. Buat key baru jika diperlukan.');

        $regularAdmin = $this->otherAdmin('regular-admin');
        Sanctum::actingAs($regularAdmin);

        $this->getJson('/api/v1/rsa-keys')
            ->assertOk();

        $this->getJson('/api/v1/rsa-keys/'.$inactive->json('data.id'))
            ->assertNotFound();

        $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => 'REGULAR-ADMIN-RSA-'.uniqid(),
            'key_length' => 2048,
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $regularAdmin->id)
            ->assertJsonPath('data.is_active', true);
    }

    public function test_rsa_key_generation_and_duplicate_fingerprint_branches_are_covered(): void
    {
        $this->actingAsAdmin();

        RsaKey::query()->delete();

        $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => 'INVALID-GENERATE-LENGTH',
            'key_length' => 4096,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('key_length');

        $generatedIdentifier = 'GENERATED-BRANCH-RSA-'.uniqid();

        $generated = $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => $generatedIdentifier,
            'key_length' => 2048,
        ])->assertCreated()
            ->assertJsonPath('data.key_identifier', $generatedIdentifier)
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue(RsaKey::query()->findOrFail($generated->json('data.id'))->has_private_key);
        $generatedKey = RsaKey::query()->findOrFail($generated->json('data.id'));
        $this->assertStringEndsWith('.pem.enc', (string) $generatedKey->private_key_path);
        $this->assertStringNotContainsString('-----BEGIN', (string) file_get_contents((string) $generatedKey->private_key_path));

        $rotatedIdentifier = 'GENERATED-ROTATED-RSA-'.uniqid();
        $rotated = $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => $rotatedIdentifier,
            'key_length' => 2048,
        ])->assertCreated()
            ->assertJsonPath('data.key_identifier', $rotatedIdentifier)
            ->assertJsonPath('data.is_active', true);

        $this->assertFalse((bool) RsaKey::query()->findOrFail($generated->json('data.id'))->is_active);
        $this->assertSame('retired', RsaKey::query()->findOrFail($generated->json('data.id'))->key_status);
        $this->assertTrue((bool) RsaKey::query()->findOrFail($rotated->json('data.id'))->is_active);

        RsaKey::query()->delete();

        $duplicatePem = $this->publicKeyPem();

        RsaKey::query()->create([
            'user_id' => $this->otherAdmin('duplicate-fingerprint')->id,
            'key_identifier' => 'EXISTING-DUPLICATE-FINGERPRINT-RSA',
            'public_key_pem' => $duplicatePem,
            'algorithm' => 'RSA',
            'key_length' => 2048,
            'fingerprint_sha256' => hash('sha256', $duplicatePem),
            'is_active' => 1,
        ]);

        $this->postJson('/api/v1/rsa-keys', [
            'key_identifier' => 'NEW-DUPLICATE-FINGERPRINT-RSA',
            'public_key_pem' => $duplicatePem,
            'key_length' => 2048,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: RSA Key dengan fingerprint tersebut sudah terdaftar.');
    }

    private function publicKeyPem(): string
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ] + $this->opensslConfigArgs());

        $details = openssl_pkey_get_details($resource);

        return trim((string) $details['key']);
    }

    private function otherAdmin(string $prefix): User
    {
        return User::query()->create([
            'name' => 'Other '.$prefix,
            'email' => $prefix.'@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function opensslConfigArgs(): array
    {
        $configPath = config('bbh.openssl_conf');

        if (is_string($configPath) && $configPath !== '' && is_file($configPath)) {
            return ['config' => $configPath];
        }

        return [];
    }
}
