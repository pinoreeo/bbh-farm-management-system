<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffspringBirth extends Model
{
    protected $table = 'breed_offsprings';

    protected $fillable = [
        'birth_event_id',
        'offspring_animal_id',
        'birth_weight_kg',
        'offspring_grade',
        'birth_status',
        'notes',
    ];

    protected $casts = [
        'birth_weight_kg' => 'decimal:2',
        'birth_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function birthEvent(): BelongsTo
    {
        return $this->belongsTo(BirthEvent::class, 'birth_event_id');
    }

    public function offspringAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'offspring_animal_id');
    }
}
