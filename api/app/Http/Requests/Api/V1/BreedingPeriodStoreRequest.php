<?php

namespace App\Http\Requests\Api\V1;

class BreedingPeriodStoreRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'colony_pen_id' => ['required', 'integer', 'exists:animal_pens,id'],
            'period_code' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'male_animal_id' => ['required', 'integer', 'exists:animals,id'],
            'status' => ['nullable', 'in:active,closed'],
            'inbreeding_policy' => ['nullable', 'in:block_high_risk,warning_only'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
