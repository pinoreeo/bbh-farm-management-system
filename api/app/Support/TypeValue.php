<?php

namespace App\Support;

final class TypeValue
{
    public static function int(mixed $value): int
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

    public static function string(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('Expected string-compatible value.');
    }

    public static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    public static function number(mixed $value): int|float|string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return $value;
        }

        throw new \InvalidArgumentException('Expected numeric-compatible value.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function stringKeyArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
