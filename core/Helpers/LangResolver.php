<?php

namespace Core\Helpers;

class LangResolver
{
    private static string $lang = 'nl';
    private static array $supportedLangs = ['nl', 'fr'];

    /**
     * Detect language from URL prefix and strip it from REQUEST_URI.
     * /fr/about -> lang=fr, REQUEST_URI=/about
     * /about -> lang=nl, REQUEST_URI=/about (unchanged)
     */
    public static function resolve(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Check for language prefix (e.g. /fr/ or /fr)
        if (preg_match('#^/(fr)(/.*)?$#', $path, $matches)) {
            self::$lang = $matches[1];
            $newPath = $matches[2] ?? '/';
            if ($newPath === '') $newPath = '/';

            // Rewrite REQUEST_URI so the router sees the clean path
            $query = parse_url($uri, PHP_URL_QUERY);
            $_SERVER['REQUEST_URI'] = $newPath . ($query ? '?' . $query : '');
        }

        return self::$lang;
    }

    public static function getLang(): string
    {
        return self::$lang;
    }

    public static function getSupportedLangs(): array
    {
        return self::$supportedLangs;
    }

    public static function isDefault(): bool
    {
        return self::$lang === 'nl';
    }
}
