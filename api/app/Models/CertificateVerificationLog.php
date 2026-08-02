<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateVerificationLog extends Model
{
    protected $table = 'cert_log';

    const UPDATED_AT = null;

    protected $fillable = [
        'certificate_id',
        'verification_method',
        'verification_time',
        'is_valid',
        'certificate_status_at_verification',
        'failure_reason',
        'used_key_fingerprint',
        'used_barcode_value',
        'created_at',
    ];

    protected $casts = [
        'certificate_id' => 'integer',
        'verification_time' => 'datetime',
        'is_valid' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
