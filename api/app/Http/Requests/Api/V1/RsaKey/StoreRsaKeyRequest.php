<?php

namespace App\Http\Requests\Api\V1\RsaKey;

use App\Http\Requests\Api\V1\ApiRequest;
use App\Services\RsaKeyService;
use Illuminate\Validation\Rule;

class StoreRsaKeyRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key_identifier' => ['required', 'string', 'max:150', 'unique:cert_rsa_keys,key_identifier'],
            'public_key_pem' => ['required', 'string'],
            'key_length' => ['required', 'integer', Rule::in([RsaKeyService::KEY_LENGTH])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
