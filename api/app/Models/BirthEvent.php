<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read Animal|null $dam
 * @property-read Animal|null $sire
 * @property-read Collection<int, OffspringBirth> $offspringBirths
 * @property-read Collection<int, PostnatalCareRecord> $postnatalCareRecords
 */
class BirthEvent extends Model
{
    protected $table = 'breed_births';

    protected $fillable = [
        'breeding_female_id',
        'dam_id',
        'sire_id',
        'birth_date',
        'birth_time',
        'offspring_count',
        'birth_process',
        'dam_grade',
        'birth_place',
        'notes',
    ];

    protected $casts = [
        'breeding_female_id' => 'integer',
        'birth_date' => 'date',
        'birth_time' => 'string',
        'offspring_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return BelongsTo<Animal, $this> */
    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    /** @return BelongsTo<BreedingFemale, $this> */
    public function breedingFemale(): BelongsTo
    {
        return $this->belongsTo(BreedingFemale::class);
    }

    /** @return BelongsTo<Animal, $this> */
    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    /** @return HasMany<OffspringBirth, $this> */
    public function offspringBirths(): HasMany
    {
        return $this->hasMany(OffspringBirth::class, 'birth_event_id');
    }

    /** @return HasMany<PostnatalCareRecord, $this> */
    public function postnatalCareRecords(): HasMany
    {
        return $this->hasMany(PostnatalCareRecord::class);
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
