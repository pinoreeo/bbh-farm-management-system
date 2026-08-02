<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedingFemale extends Model
{
    protected $table = 'breed_females';

    protected $fillable = [
        'breeding_period_id',
        'female_animal_id',
        'entry_date',
        'mating_date',
        'expected_birth_date',
        'cycle_stage',
        'inbreeding_status',
        'inbreeding_note',
        'exit_date',
        'exit_reason',
        'exit_reason_code',
        'exit_notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'mating_date' => 'date',
        'expected_birth_date' => 'date',
        'exit_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('exit_date');
    }

    public function breedingPeriod(): BelongsTo
    {
        return $this->belongsTo(BreedingPeriod::class, 'breeding_period_id');
    }

    public function femaleAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_animal_id');
    }
}
