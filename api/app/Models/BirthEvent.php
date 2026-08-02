<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BirthEvent extends Model
{
    protected $table = 'breed_births';

    protected $fillable = [
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
        'birth_date' => 'date',
        'birth_time' => 'string',
        'offspring_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function offspringBirths(): HasMany
    {
        return $this->hasMany(OffspringBirth::class, 'birth_event_id');
    }

    public function postnatalCareRecords(): HasMany
    {
        return $this->hasMany(PostnatalCareRecord::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
