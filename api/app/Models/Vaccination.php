<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    protected $table = 'med_vaccinations';

    protected $fillable = [
        'animal_id',
        'category_name',
        'vaccination_date',
        'product_name',
        'dosage',
        'administration_route',
        'notes',
    ];

    protected $casts = [
        'vaccination_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}
