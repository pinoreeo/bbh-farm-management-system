<?php

namespace App\Http\Requests\Api\V1\RsaKey;

use App\Http\Requests\Api\V1\ApiRequest;

class CompromiseRsaKeyRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
