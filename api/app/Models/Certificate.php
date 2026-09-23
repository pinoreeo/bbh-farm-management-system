<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property-read Animal|null $animal
 * @property-read CertificateType|null $certificateType
 * @property-read BirthEvent|null $birthEvent
 * @property-read CertificateSignature|null $signature
 * @property-read RsaKey|null $officialPdfRsaKey
 * @property-read CertificateRevocation|null $revocation
 * @property-read Collection<int, CertificateVerificationLog> $verificationLogs
 */
class Certificate extends Model
{
    protected $table = 'certs';

    protected $fillable = [
        'animal_id',
        'certificate_type_id',
        'replaces_certificate_id',
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
        'replaces_certificate_id' => 'integer',
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

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    /** @return BelongsTo<CertificateType, $this> */
    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    /** @return BelongsTo<Certificate, $this> */
    public function replacedCertificate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_certificate_id');
    }

    /** @return HasOne<Certificate, $this> */
    public function replacementCertificate(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_certificate_id');
    }

    /** @return BelongsTo<BirthEvent, $this> */
    public function birthEvent(): BelongsTo
    {
        return $this->belongsTo(BirthEvent::class, 'birth_event_id');
    }

    /** @return HasOne<CertificateSignature, $this> */
    public function signature(): HasOne
    {
        return $this->hasOne(CertificateSignature::class, 'certificate_id')
            ->where('status', 'active')
            ->latestOfMany();
    }

    /** @return BelongsTo<RsaKey, $this> */
    public function officialPdfRsaKey(): BelongsTo
    {
        return $this->belongsTo(RsaKey::class, 'official_pdf_rsa_key_id');
    }

    /** @return HasOne<CertificateRevocation, $this> */
    public function revocation(): HasOne
    {
        return $this->hasOne(CertificateRevocation::class, 'certificate_id');
    }

    /** @return HasMany<CertificateVerificationLog, $this> */
    public function verificationLogs(): HasMany
    {
        return $this->hasMany(CertificateVerificationLog::class, 'certificate_id');
    }

    public function getPublicVerificationUrlAttribute(): ?string
    {
        if (! $this->verification_token) {
            return null;
        }

        $baseUrlConfig = config('bbh.public_web_url') ?: config('app.url');
        $localeConfig = config('bbh.public_default_locale', 'id-id');
        $baseUrl = rtrim(is_string($baseUrlConfig) ? $baseUrlConfig : '', '/');
        $locale = trim(is_string($localeConfig) ? $localeConfig : 'id-id', '/');

        return "{$baseUrl}/{$locale}/verifikasi/".rawurlencode((string) $this->verification_token);
    }
}
