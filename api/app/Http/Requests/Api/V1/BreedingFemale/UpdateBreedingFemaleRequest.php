<?php

namespace App\Http\Requests\Api\V1\BreedingFemale;

use App\Http\Requests\Api\V1\ApiRequest;

class UpdateBreedingFemaleRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entry_date' => ['sometimes', 'date'],
            'cycle_stage' => ['nullable', 'in:kawin,bunting,kering,laktasi_kosong,melahirkan'],
        ];
    }
}
