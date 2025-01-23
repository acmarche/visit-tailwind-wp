<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Utils\CacheUtils;
use Psr\Cache\InvalidArgumentException;
use Redis;
use RedisException;
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

    public static function generateKey(string $cacheKey): string
    {
        $keyUnicode = new UnicodeString($cacheKey);

        return sanitize_title($keyUnicode->ascii()->toString());
    }

    public static function purgeCache(): void
    {
        try {
            $cacheUtils = new CacheUtils();
            $cache = $cacheUtils->instance();
            $cache->invalidateTags(CacheUtils::TAG);
        } catch (InvalidArgumentException|\Exception $e) {
        }
    }

    public static function purgeCacheHard(): void
    {
        $redis = new Redis();
        try {
            $redis->connect('127.0.0.1', 6379);

            // Authenticate if needed
            // $redis->auth('yourpassword');
            // Option 1: Flush the entire Redis server (all databases)
            $redis->flushAll();
            // Option 2: Flush only the current database
            // $redis->flushDB();

        } catch (RedisException $e) {
            dump("Failed to connect to Redis: ".$e->getMessage());
        }
    }

    public static function instance(string $folder): CacheInterface
    {
        $cacheUtils = new CacheUtils();

        if (null !== self::$instanceObject) {
            return self::$instanceObject;
        }

        self::$instanceObject = $cacheUtils->instance();

        return self::$instanceObject;
    }

    public static function getPathCache(string $folder): string
    {
        return ABSPATH.'../var/cache/'.$folder;
    }
}