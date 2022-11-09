<?php

namespace VisitMarche\ThemeTail\Lib;

use Symfony\Component\String\UnicodeString;

class Cache
{
    public const MENU_NAME = 'menu-top';
    public const ICONES_NAME = 'icones-home';
    public const EVENTS = 'events';

    public static function setItem(string $keyname, string|array $data, int $expiration = 86400): bool
    {
        return set_transient($keyname, $data, $expiration);
    }

    public static function getItem(string $keyname): string|array|null
    {
        return get_transient($keyname);
    }

    public static function removeCache(string $keyname): bool
    {
        return delete_transient($keyname);
    }

    public static function generateKey(string $cacheKey):string
    {
        $keyUnicode = new UnicodeString($cacheKey);
        return sanitize_title($keyUnicode->ascii()->toString());
    }
}