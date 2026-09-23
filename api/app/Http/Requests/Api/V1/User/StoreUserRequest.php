<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\ApiRequest;

class StoreUserRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255', 'required_without:first_name'],
            'first_name' => ['nullable', 'string', 'max:120', 'required_without:name'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:sys_users,email'],
            'phone' => ['nullable', 'string', 'max:100'],
            'password' => ['prohibited'],
            'role' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }
}
