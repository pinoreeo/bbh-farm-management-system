<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthTreatment extends Model
{
    protected $table = 'med_treatments';

    protected $fillable = [
        'animal_id',
        'treatment_group',
        'product_name',
        'treatment_date',
        'symptoms',
        'diagnosis',
        'dosage',
        'administration_route',
        'action_category',
        'handled_by',
        'next_control_date',
        'notes',
    ];

    protected $casts = [
        'treatment_date' => 'date',
        'next_control_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
