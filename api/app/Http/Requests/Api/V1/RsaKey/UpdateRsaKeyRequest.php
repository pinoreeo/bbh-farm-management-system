<?php

namespace App\Http\Requests\Api\V1\RsaKey;

use App\Http\Requests\Api\V1\ApiRequest;
use App\Models\RsaKey;
use Illuminate\Validation\Rule;

class UpdateRsaKeyRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rsaKey = $this->route('rsaKey');
        $rsaKeyId = $rsaKey instanceof RsaKey ? $rsaKey->id : null;

        return [
            'key_identifier' => ['sometimes', 'string', 'max:150', Rule::unique('cert_rsa_keys', 'key_identifier')->ignore($rsaKeyId)],
            'is_active' => ['sometimes', 'boolean'],
            'status_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
