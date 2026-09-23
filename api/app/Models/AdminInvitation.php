<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminInvitation extends Model
{
    protected $table = 'sys_admin_invitations';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
