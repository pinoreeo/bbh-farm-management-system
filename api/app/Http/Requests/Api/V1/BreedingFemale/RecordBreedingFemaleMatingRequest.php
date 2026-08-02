<?php

namespace App\Http\Requests\Api\V1\BreedingFemale;

use App\Http\Requests\Api\V1\ApiRequest;

class RecordBreedingFemaleMatingRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mating_date' => ['required', 'date'],
        ];
    }
}
