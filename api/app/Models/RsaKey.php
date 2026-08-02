<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

class RsaKey extends Model
{
    protected $table = 'cert_rsa_keys';

    protected $fillable = [
        'user_id',
        'key_identifier',
        'public_key_pem',
        'private_key_path',
        'algorithm',
        'key_length',
        'fingerprint_sha256',
        'is_active',
        'key_status',
        'retired_at',
        'compromised_at',
        'last_used_at',
        'status_reason',
    ];

    protected $hidden = [
        'private_key_path',
    ];

    protected $appends = [
        'has_private_key',
    ];

    protected $casts = [
        'key_length' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'boolean',
        'retired_at' => 'datetime',
        'compromised_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function signatures(): HasMany
    {
        return $this->hasMany(CertificateSignature::class, 'rsa_key_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function validateKey(array $data)
    {
        return Validator::make($data, [
            'key_identifier' => 'required|string|max:255|unique:cert_rsa_keys,key_identifier',
            'user_id' => 'required|integer',
            'public_key_pem' => 'required|string',
            'algorithm' => 'required|string|in:RSA',
            'key_length' => 'required|integer|in:2048',
            'fingerprint_sha256' => 'required|string',
            'is_active' => 'required|boolean',
            'key_status' => 'nullable|string|in:active,retired,compromised',
        ]);
    }

    public function generateFingerprint(): string
    {
        return hash('sha256', preg_replace('/\s+/', '', trim((string) $this->public_key_pem)) ?? '');
    }

    public function getHasPrivateKeyAttribute(): bool
    {
        if (! is_string($this->private_key_path) || $this->private_key_path === '') {
            return false;
        }

        if (is_file($this->private_key_path)) {
            return true;
        }

        return is_file(storage_path('app/keys/rsa/'.basename($this->private_key_path)));
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'key_status' => 'retired',
            'retired_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'key_status' => 'active',
            'retired_at' => null,
            'compromised_at' => null,
        ]);
    }

    public function getPublicKeyPem(): string
    {
        return $this->public_key_pem;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
