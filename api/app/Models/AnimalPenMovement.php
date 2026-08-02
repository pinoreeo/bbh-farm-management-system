<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalPenMovement extends Model
{
    protected $fillable = [
        'animal_id',
        'from_pen_id',
        'to_pen_id',
        'movement_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function fromPen(): BelongsTo
    {
        return $this->belongsTo(ColonyPen::class, 'from_pen_id');
    }

    public function toPen(): BelongsTo
    {
        return $this->belongsTo(ColonyPen::class, 'to_pen_id');
    }
}
