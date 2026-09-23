<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Breed|null $breed
 * @property-read ColonyPen|null $currentPen
 * @property-read Collection<int, OffspringBirth> $offspringBirths
 * @property-read Collection<int, PostnatalCareRecord> $postnatalCareRecords
 */
class Animal extends Model
{
    protected $table = 'animals';

    protected $fillable = [
        'tag_number',
        'photo_path',
        'breed_id',
        'sex',
        'male_role',
        'generation',
        'birth_date',
        'birth_place',
        'current_pen_id',
        'reproductive_status',
        'status_date',
        'life_status',
        'exit_status',
        'notes',
        'is_impor',
        'origin_type',
        'origin_detail',
    ];

    protected $casts = [
        'sex' => 'string',
        'male_role' => 'string',
        'generation' => 'string',
        'birth_date' => 'date',
        'status_date' => 'date',
        'life_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_impor' => 'boolean',
        'origin_type' => 'string',
    ];

    protected $appends = ['umur', 'kategori_umur', 'photo_url'];

    /** @return BelongsTo<Breed, $this> */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /** @return BelongsTo<ColonyPen, $this> */
    public function currentPen(): BelongsTo
    {
        return $this->belongsTo(ColonyPen::class, 'current_pen_id');
    }

    /** @return HasMany<BirthEvent, $this> */
    public function birthEventsAsDam(): HasMany
    {
        return $this->hasMany(BirthEvent::class, 'dam_id');
    }

    /** @return HasMany<BirthEvent, $this> */
    public function birthEventsAsSire(): HasMany
    {
        return $this->hasMany(BirthEvent::class, 'sire_id');
    }

    /** @return HasMany<OffspringBirth, $this> */
    public function offspringBirths(): HasMany
    {
        return $this->hasMany(OffspringBirth::class, 'offspring_animal_id');
    }

    /** @return HasMany<WeightRecord, $this> */
    public function weightRecords(): HasMany
    {
        return $this->hasMany(WeightRecord::class);
    }

    /** @return HasMany<HealthTreatment, $this> */
    public function healthTreatments(): HasMany
    {
        return $this->hasMany(HealthTreatment::class);
    }

    /** @return HasMany<Vaccination, $this> */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    /** @return HasMany<PostnatalCareRecord, $this> */
    public function postnatalCareRecords(): HasMany
    {
        return $this->hasMany(PostnatalCareRecord::class, 'target_animal_id');
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** @return HasMany<BreedingPeriod, $this> */
    public function breedingPeriodsAsMale(): HasMany
    {
        return $this->hasMany(BreedingPeriod::class, 'male_animal_id');
    }

    /** @return HasMany<BreedingFemale, $this> */
    public function breedingFemales(): HasMany
    {
        return $this->hasMany(BreedingFemale::class, 'female_animal_id');
    }

    /** @return HasMany<AnimalPenMovement, $this> */
    public function penMovements(): HasMany
    {
        return $this->hasMany(AnimalPenMovement::class);
    }

    /** @return HasMany<PregnancyCheck, $this> */
    public function pregnancyChecks(): HasMany
    {
        return $this->hasMany(PregnancyCheck::class, 'female_animal_id');
    }

    /** @return HasMany<OffspringBirth, $this> */
    public function parentBirthRecord(): HasMany
    {
        return $this->hasMany(OffspringBirth::class, 'offspring_animal_id');
    }

    public function getUmurAttribute(): ?string
    {
        if (! $this->birth_date) {
            return null;
        }

        $diff = $this->birth_date->diff(now());
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y.' Tahun';
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m.' Bulan';
        }

        if ($diff->d > 0 || $parts === []) {
            $parts[] = $diff->d.' Hari';
        }

        return implode(' ', $parts);
    }

    public function getKategoriUmurAttribute(): string
    {
        if (! $this->birth_date) {
            return 'unknown';
        }

        $umurBulan = $this->birth_date->diffInMonths(now());

        if ($umurBulan <= 6) {
            return 'cempe';
        }

        if ($this->sex === 'female') {
            if ($umurBulan <= 12) {
                return 'dere';
            }

            return 'betina dewasa';
        }

        if ($this->sex === 'male') {
            if ($umurBulan <= 12) {
                return 'pejantan muda';
            }

            return 'pejantan dewasa';
        }

        return 'unknown';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
