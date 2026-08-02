<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends ApiRequest
{
    private const ROLE_OPTIONS = ['super_admin', 'admin'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('sys_users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in(self::ROLE_OPTIONS)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
