<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PregnancyCheck extends Model
{
    protected $table = 'breed_pregnancies';

    protected $fillable = [
        'breeding_female_id',
        'breeding_period_id',
        'female_animal_id',
        'check_date',
        'is_pregnant',
        'outcome_status',
        'method',
        'estimated_gestation_days',
        'notes',
    ];

    protected $casts = [
        'check_date' => 'date',
        'breeding_female_id' => 'integer',
        'breeding_period_id' => 'integer',
        'female_animal_id' => 'integer',
        'is_pregnant' => 'boolean',
        'outcome_status' => 'string',
        'estimated_gestation_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function breedingPeriod(): BelongsTo
    {
        return $this->belongsTo(BreedingPeriod::class, 'breeding_period_id');
    }

    public function breedingFemale(): BelongsTo
    {
        return $this->belongsTo(BreedingFemale::class, 'breeding_female_id');
    }

    public function femaleAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_animal_id');
    }
}
