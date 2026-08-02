<?php

namespace App\Http\Requests\Api\V1\BreedingFemale;

use App\Http\Requests\Api\V1\ApiRequest;

class StoreBreedingFemaleRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'breeding_period_id' => ['required', 'integer', 'exists:breed_periods,id'],
            'female_animal_id' => ['required_without:female_animal_ids', 'integer', 'exists:animals,id'],
            'female_animal_ids' => ['nullable', 'array'],
            'female_animal_ids.*' => ['integer', 'exists:animals,id'],
            'entry_date' => ['required', 'date'],
            'mating_date' => ['nullable', 'date'],
            'cycle_stage' => ['nullable', 'in:kawin,bunting,kering,laktasi_kosong,melahirkan'],
        ];
    }
}
