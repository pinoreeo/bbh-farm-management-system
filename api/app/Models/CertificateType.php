<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateType extends Model
{
    protected $table = 'cert_types';

    protected $fillable = [
        'type_code',
        'type_name',
        'description',
        'template_version',
        'is_active',
    ];

    protected $casts = [
        'type_code' => 'string',
        'type_name' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'certificate_type_id');
    }
}
