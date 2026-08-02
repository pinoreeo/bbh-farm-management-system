<?php

namespace App\Support;

use App\Models\Animal;

class AnimalEartag
{
    public function next(?string $birthDate = null, ?string $jantanPemacek = null): string
    {
        $year = $birthDate !== null && $birthDate !== ''
            ? date('y', strtotime($birthDate))
            : now()->format('y');
        $next = $this->nextSequence($year);

        do {
            $tag = $this->format($year, $next, $jantanPemacek);
            $next++;
        } while (Animal::query()->where('tag_number', $tag)->exists());

        return $tag;
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
            ->each(function (string $tag) use (&$max, $patterns): void {
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
