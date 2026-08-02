<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BreedingPeriod extends Model
{
    protected $table = 'breed_periods';

    protected $fillable = [
        'colony_pen_id',
        'period_code',
        'start_date',
        'end_date',
        'male_animal_id',
        'status',
        'inbreeding_policy',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function colonyPen(): BelongsTo
    {
        return $this->belongsTo(ColonyPen::class);
    }

    public function maleAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function females(): HasMany
    {
        return $this->hasMany(BreedingFemale::class);
    }

    public function pregnancyChecks(): HasMany
    {
        return $this->hasMany(PregnancyCheck::class);
    }
}
