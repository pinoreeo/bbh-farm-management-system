<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

class PublicSiteCopy
{
    /**
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        $copy = trans('public');

        return is_array($copy) ? $copy : [];
    }

    /**
     * @return array<string, string>
     */
    public static function languages(): array
    {
        return collect(config('public.locales', []))
            ->mapWithKeys(fn (array $locale, string $segment) => [$segment => (string) ($locale['label'] ?? $segment)])
            ->all();
    }

    public static function locale(): string
    {
        $routeLocale = (string) request()->route('locale');

        if (array_key_exists($routeLocale, self::languages())) {
            return $routeLocale;
        }

        foreach (config('public.locales', []) as $segment => $locale) {
            if (($locale['app_locale'] ?? null) === App::currentLocale()) {
                return (string) $segment;
            }
        }

        return self::defaultLocale();
    }

    public static function defaultLocale(): string
    {
        return (string) config('public.default_locale', 'id-id');
    }

    public static function switchUrl(string $targetLocale): string
    {
        if (! array_key_exists($targetLocale, self::languages())) {
            $targetLocale = self::defaultLocale();
        }

        $segments = explode('/', trim(request()->getPathInfo(), '/'));

        if (isset($segments[0]) && array_key_exists($segments[0], self::languages())) {
            $segments[0] = $targetLocale;
        } else {
            array_unshift($segments, $targetLocale);
        }

        $path = '/'.implode('/', array_filter($segments, fn (string $segment) => $segment !== ''));
        $query = request()->getQueryString();

        return url($path).($query ? '?'.$query : '');
    }
}
