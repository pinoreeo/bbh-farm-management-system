<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmProfile extends Model
{
    protected $table = 'sys_farm_profiles';

    protected $fillable = [
        'singleton_key',
        'farm_name',
        'address',
        'phone',
        'email',
    ];
}
