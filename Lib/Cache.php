<?php

namespace VisitMarche\ThemeTail\Lib;

use Symfony\Component\String\UnicodeString;

class Cache
{
    public const MENU_NAME = 'menu-top';
    public const ICONES_NAME = 'icones-home';
    public const EVENTS = 'events';
    public const OFFRES = 'offres';
    public const OFFRE = 'offre';
    public const SEE_ALSO_OFFRES = 'see_also_offre';

    public static function setItem(string $keyname, string|array|object $data, int $expiration = 86400): bool
    {
        return set_transient($keyname, $data, $expiration);
    }

    public static function getItem(string $keyname): string|array|object|null
    {
        return get_transient($keyname);
    }

    public static function removeCache(string $keyname): bool
    {
        return delete_transient($keyname);
    }

    public static function generateKey(string $cacheKey): string
    {
        $keyUnicode = new UnicodeString($cacheKey);

        return sanitize_title($keyUnicode->ascii()->toString());
    }
}