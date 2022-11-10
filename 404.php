<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();

$language = LocaleHelper::getSelectedLanguage();
dump(111);
dump($language);
dump(111);
$menu = new Menu();
$items = $menu->getMenuTop();
dump($items);
Twig::rendPage(
    '@VisitTail/errors/404.html.twig',
    [
        'title' => 'post_title',
        'message' => 'post_title',
        'url' => '/',
        'latitude' => '5.342961',
        'longitude' => '50.226484',
    ]
);
get_footer();