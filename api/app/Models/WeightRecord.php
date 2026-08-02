<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightRecord extends Model
{
    protected $table = 'animal_weights';

    protected $fillable = [
        'animal_id',
        'record_date',
        'weight_kg',
        'notes',
    ];

    protected $casts = [
        'record_date' => 'date',
        'weight_kg' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
