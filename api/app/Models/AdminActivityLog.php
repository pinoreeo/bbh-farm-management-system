<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    protected $table = 'sys_activity_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_email',
        'action',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'method',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'admin_id' => 'integer',
        'subject_id' => 'integer',
        'status_code' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
