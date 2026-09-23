<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingAdminRegistration extends Model
{
    protected $table = 'sys_pending_admin_registrations';

    protected $fillable = [
        'created_by_id',
        'name',
        'email',
        'phone',
        'password_hash',
        'code_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
