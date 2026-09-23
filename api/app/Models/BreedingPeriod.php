<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read ColonyPen|null $colonyPen
 * @property-read Animal|null $maleAnimal
 * @property-read Collection<int, BreedingFemale> $females
 * @property-read Collection<int, PregnancyCheck> $pregnancyChecks
 */
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
        'closed_by_male_death' => 'boolean',
        'status' => 'string',
    ];

    /**
     * @param  Builder<BreedingPeriod>  $query
     * @return Builder<BreedingPeriod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** @return BelongsTo<ColonyPen, $this> */
    public function colonyPen(): BelongsTo
    {
        return $this->belongsTo(ColonyPen::class);
    }

    /** @return BelongsTo<Animal, $this> */
    public function maleAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return HasMany<BreedingFemale, $this> */
    public function females(): HasMany
    {
        return $this->hasMany(BreedingFemale::class);
    }

    /** @return HasMany<PregnancyCheck, $this> */
    public function pregnancyChecks(): HasMany
    {
        return $this->hasMany(PregnancyCheck::class);
    }
}
