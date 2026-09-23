<?php

namespace App\Support;

use App\Models\Animal;
use Illuminate\Database\QueryException;

class AnimalEartag
{
    public function next(?string $birthDate = null, ?string $jantanPemacek = null, int $offset = 0): string
    {
        $timestamp = $birthDate !== null && $birthDate !== '' ? strtotime($birthDate) : false;
        $year = $timestamp !== false
            ? date('y', $timestamp)
            : now()->format('y');
        $next = $this->nextSequence($year) + $offset;

        do {
            $tag = $this->format($year, $next, $jantanPemacek);
            $next++;
        } while (Animal::query()->where('tag_number', $tag)->exists());

        return $tag;
    }

    public function isDuplicateTag(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'uq_animals_tag')
            || str_contains($exception->getMessage(), 'animals.tag_number');
    }

    private function nextSequence(string $year): int
    {
        $max = 0;
        $patterns = [
            '/^'.preg_quote($year, '/').'-(\d{3})(?:-[A-Z]{2,10})?$/',
            '/^BBH-'.preg_quote($year, '/').'-(\d{3})(?:-[A-Z]{2,10})?$/',
        ];

        Animal::query()
            ->where('tag_number', 'like', $year.'-%')
            ->orWhere('tag_number', 'like', 'BBH-'.$year.'-%')
            ->pluck('tag_number')
            ->filter(fn (mixed $tag): bool => is_string($tag))
            ->each(function (mixed $tag) use (&$max, $patterns): void {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $tag, $matches)) {
                        $max = max($max, (int) $matches[1]);
                    }
                }
            });

        return $max + 1;
    }

    private function format(string $year, int $sequence, ?string $jantanPemacek): string
    {
        $base = $year.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $marker = in_array($jantanPemacek, ['SPB', 'APB'], true) ? $jantanPemacek : null;

        return $marker ? $base.'-'.$marker : $base;
    }
}
