<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return ValidationMessages::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ValidationMessages::attributes();
    }
}
