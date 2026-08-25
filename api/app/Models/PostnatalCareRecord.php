<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostnatalCareRecord extends Model
{
    protected $table = 'med_postnatal_cares';

    protected $fillable = [
        'offspring_birth_id',
        'birth_event_id',
        'target_animal_id',
        'care_date',
        'administration_method',
        'volume_ml',
        'navel_iodine_status',
        'vitamin_ade_ml',
        'vitamin_b_complex_ml',
        'intracin_ml',
        'notes',
    ];

    protected $casts = [
        'care_date' => 'date',
        'offspring_birth_id' => 'integer',
        'birth_event_id' => 'integer',
        'target_animal_id' => 'integer',
        'volume_ml' => 'decimal:2',
        'vitamin_ade_ml' => 'decimal:2',
        'vitamin_b_complex_ml' => 'decimal:2',
        'intracin_ml' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function birthEvent(): BelongsTo
    {
        return $this->belongsTo(BirthEvent::class, 'birth_event_id');
    }

    public function offspringBirth(): BelongsTo
    {
        return $this->belongsTo(OffspringBirth::class, 'offspring_birth_id');
    }

    public function targetAnimal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'target_animal_id');
    }
}
