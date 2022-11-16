<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\GpxViewer;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();

$language = LocaleHelper::getSelectedLanguage();
//dump($language);
$menu = new Menu();
$items = $menu->getMenuTop();
//dump(ICL_LANGUAGE_CODE);
//dump($items);

$filepath = '/wp-content/uploads/gpx/non-classifiee/Cirkwi-Marche-en-Famenne_-_Circuit_VTT_Vert.gpx';

$pgwViewer = new GpxViewer();
$pgwViewer->renderWithPlugin($filepath);

$gpx = gpx_view(array(
        'src' => $filepath,
        'title' => 'Cirkwi-Marche-en-Famenne_-_Circuit_VTT_Vert.gpx',
        'color' => '#FF0000',
        'width' => '450px',
        'distance_unit'=>'km',
        'download_button' => true,
    )
);

echo $gpx;

Twig::rendPage(
    '@VisitTail/errors/404.html.twig',
    [
        'title' => 'post_title',
        'message' => 'post_title',
        'url' => '/',
        'latitude' => '5.342961',
        'longitude' => '50.226484',
        'gpx'=>$gpx
    ]
);
get_footer();