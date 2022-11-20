<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();

$language = LocaleHelper::getSelectedLanguage();
//dump($language);
$menu = new Menu();
$items = $menu->getMenuTop();
//dump(ICL_LANGUAGE_CODE);
//dump($items);
Twig::rend404Page();
get_footer();