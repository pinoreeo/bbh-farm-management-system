<?php

namespace App\Http\Requests\Api\V1\BreedingFemale;

use App\Http\Requests\Api\V1\ApiRequest;
use App\Services\BreedingFemaleService;

class ExitBreedingFemaleRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date'],
            'exit_reason_code' => ['required', 'string', 'in:'.implode(',', array_keys(BreedingFemaleService::EXIT_REASONS))],
            'exit_reason' => ['required_if:exit_reason_code,lainnya', 'nullable', 'string', 'max:255'],
            'to_pen_id' => ['nullable', 'integer', 'exists:animal_pens,id'],
            'exit_notes' => ['nullable', 'string'],
        ];
    }
}
