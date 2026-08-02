<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateRevocation extends Model
{
    protected $table = 'cert_revocations';

    protected $fillable = [
        'certificate_id',
        'revoked_at',
        'reason',
    ];

    protected $casts = [
        'certificate_id' => 'integer',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
