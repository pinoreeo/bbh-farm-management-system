<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends ApiRequest
{
    private const ROLE_OPTIONS = ['super_admin', 'admin'];

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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_OPTIONS)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
