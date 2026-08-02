<?php

namespace App\Http\Requests\Api\V1;

class BreedingPeriodUpdateRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'colony_pen_id' => ['sometimes', 'integer', 'exists:animal_pens,id'],
            'period_code' => ['sometimes', 'string', 'max:100'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'male_animal_id' => ['sometimes', 'integer', 'exists:animals,id'],
            'status' => ['sometimes', 'in:active,closed'],
            'inbreeding_policy' => ['nullable', 'in:block_high_risk,warning_only'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
