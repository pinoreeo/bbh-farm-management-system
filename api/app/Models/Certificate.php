<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Certificate extends Model
{
    protected $table = 'certs';

    protected $fillable = [
        'animal_id',
        'certificate_type_id',
        'certificate_number',
        'verification_token',
        'issue_date',
        'issue_place',
        'birth_event_id',
        'valid_from',
        'valid_until',
        'death_date',
        'death_time',
        'cause_of_death',
        'barcode_value',
        'barcode_format',
        'payload_snapshot',
        'canonical_method',
        'hash_sha256',
        'official_pdf_path',
        'official_pdf_hash_sha256',
        'official_pdf_signature_base64',
        'official_pdf_signature_scheme',
        'official_pdf_rsa_key_id',
        'official_pdf_signed_at',
        'official_pdf_generated_at',
        'status',
    ];

    protected $casts = [
        'animal_id' => 'integer',
        'certificate_type_id' => 'integer',
        'birth_event_id' => 'integer',
        'issue_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'death_date' => 'date',
        'death_time' => 'string',
        'status' => 'string',
        'official_pdf_rsa_key_id' => 'integer',
        'official_pdf_signed_at' => 'datetime',
        'official_pdf_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    public function birthEvent(): BelongsTo
    {
        return $this->belongsTo(BirthEvent::class, 'birth_event_id');
    }

    public function signature(): HasOne
    {
        return $this->hasOne(CertificateSignature::class, 'certificate_id')
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function officialPdfRsaKey(): BelongsTo
    {
        return $this->belongsTo(RsaKey::class, 'official_pdf_rsa_key_id');
    }

    public function revocation(): HasOne
    {
        return $this->hasOne(CertificateRevocation::class, 'certificate_id');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(CertificateVerificationLog::class, 'certificate_id');
    }

    public function getPublicVerificationUrlAttribute(): ?string
    {
        if (! $this->verification_token) {
            return null;
        }

        $baseUrl = rtrim((string) (config('bbh.public_web_url') ?: config('app.url')), '/');
        $locale = trim((string) config('bbh.public_default_locale', 'id-id'), '/');

        return "{$baseUrl}/{$locale}/verifikasi/".rawurlencode((string) $this->verification_token);
    }
}
