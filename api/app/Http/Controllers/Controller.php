<?php

namespace App\Http\Controllers;

use App\Support\TypeValue;
use App\Support\ValidationMessages;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min($this->intValue($request->query('per_page', $default)), $max));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validated(Request $request, array $rules): array
    {
        $validated = $request->validate($rules, ValidationMessages::messages(), $this->validationAttributes());

        return TypeValue::stringKeyArray($validated);
    }

    protected function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException('Expected integer-compatible value.');
    }

    protected function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('Expected string-compatible value.');
    }

    protected function nullableStringValue(mixed $value): ?string
    {
        return $value === null ? null : $this->stringValue($value);
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ValidationMessages::attributes();
    }
}
