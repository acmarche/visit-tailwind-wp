<?php

namespace VisitMarche\ThemeTail\Inc;

use Symfony\Component\HttpFoundation\Request;

class Theme
{
    public const PAGE_INTRO = 115;
    public const PAGE_DECOUVRIR = 828;
    public const CATEGORY_ARTS = 10;
    public const CATEGORY_BALADES = 11;
    public const CATEGORY_FETES = 12;
    public const CATEGORY_GOURMANDISES = 13;
    public const CATEGORY_PATRIMOINES = 9;
    public const CATEGORIES_AGENDA = [8,33,34];
    public const CATEGORIES_HEBERGEMENT = [6,67,68];
    public const CATEGORIES_RESTAURATION = [5,44,66];

    public static function isHomePage(): bool
    {
        $request = Request::createFromGlobals();
        $uri = $request->getPathInfo();

        return '/' === $uri || '/fr/' === $uri || '/fr' === $uri || '/nl/' === $uri || '/nl' === $uri || '/en/' === $uri || '/en' === $uri;
    }
}
