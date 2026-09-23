<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\ApiRequest;

class UpdateUserRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['prohibited'],
            'phone' => ['nullable', 'string', 'max:100'],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'role' => ['prohibited'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
