<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPhoneVerification extends Model
{
    protected $table = 'sys_admin_phone_verifications';

    protected $fillable = [
        'user_id',
        'phone',
        'code_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
