<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColonyPen extends Model
{
    protected $table = 'animal_pens';

    protected $fillable = [
        'pen_code',
        'colony_code',
        'colony_name',
        'colony_type',
        'colony_phase',
        'location',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function breedingPeriods(): HasMany
    {
        return $this->hasMany(BreedingPeriod::class, 'colony_pen_id');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'current_pen_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(AnimalPenMovement::class, 'to_pen_id');
    }
}
