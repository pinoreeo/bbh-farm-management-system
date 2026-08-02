<?php

namespace App\Http\Requests\Api\V1\RsaKey;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateRsaKeyRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rsaKeyId = $this->route('rsaKey')?->id;

        return [
            'key_identifier' => ['sometimes', 'string', 'max:150', Rule::unique('cert_rsa_keys', 'key_identifier')->ignore($rsaKeyId)],
            'is_active' => ['sometimes', 'boolean'],
            'status_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
