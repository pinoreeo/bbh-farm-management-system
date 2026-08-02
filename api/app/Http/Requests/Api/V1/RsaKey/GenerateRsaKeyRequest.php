<?php

namespace App\Http\Requests\Api\V1\RsaKey;

use App\Http\Requests\Api\V1\ApiRequest;
use App\Services\RsaKeyService;
use Illuminate\Validation\Rule;

class GenerateRsaKeyRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key_identifier' => ['nullable', 'string', 'max:150', 'unique:cert_rsa_keys,key_identifier'],
            'key_length' => ['nullable', 'integer', Rule::in([RsaKeyService::KEY_LENGTH])],
        ];
    }
}
