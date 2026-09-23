<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read BreedingPeriod|null $breedingPeriod
 * @property-read BreedingFemale|null $breedingFemale
 * @property-read Animal|null $femaleAnimal
 */
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

    /** @return BelongsTo<BreedingPeriod, $this> */
    public function breedingPeriod(): BelongsTo
    {
        return $this->belongsTo(BreedingPeriod::class, 'breeding_period_id');
    }

    /** @return BelongsTo<BreedingFemale, $this> */
    public function breedingFemale(): BelongsTo
    {
        return $this->belongsTo(BreedingFemale::class, 'breeding_female_id');
    }

    /** @return BelongsTo<Animal, $this> */
    public function femaleAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_animal_id');
    }
}
