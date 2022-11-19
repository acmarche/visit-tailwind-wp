<?php

namespace VisitMarche\ThemeTail\Lib;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;

class Cache
{
    public const MENU_NAME = 'menu-top';
    public const ICONES_NAME = 'icones-home';
    public const EVENTS = 'events';
    public const OFFRES = 'offres';
    public const OFFRE = 'offre';
    public const SEE_ALSO_OFFRES = 'see_also_offre';
    public const FETCH_OFFRES = 'fetch_offres';
    public static ?CacheInterface $instanceObject = null;

    public static function getPathCache(): string
    {
        return ABSPATH.'../var/cache';
    }

    public static function instance(): CacheInterface
    {
        if (null !== self::$instanceObject) {
            return self::$instanceObject;
        }

        self::$instanceObject =
            new FilesystemAdapter(
                '_visit',
                43200,
                $_ENV['APP_CACHE_DIR'] ?? ABSPATH.self::getPathCache()
            );

        return self::$instanceObject;
    }

    public static function generateKey(string $cacheKey): string
    {
        $keyUnicode = new UnicodeString($cacheKey);

        return sanitize_title($keyUnicode->ascii()->toString());
    }
}