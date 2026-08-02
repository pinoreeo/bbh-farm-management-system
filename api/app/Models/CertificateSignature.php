<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateSignature extends Model
{
    protected $table = 'cert_signatures';

    protected $fillable = [
        'certificate_id',
        'rsa_key_id',
        'signed_by_user_id',
        'signature_scheme',
        'signature_base64',
        'signed_at',
        'status',
    ];

    protected $casts = [
        'certificate_id' => 'integer',
        'rsa_key_id' => 'integer',
        'signed_by_user_id' => 'integer',
        'signed_at' => 'datetime',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    public function rsaKey(): BelongsTo
    {
        return $this->belongsTo(RsaKey::class, 'rsa_key_id');
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }
}
