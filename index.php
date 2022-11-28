<?php
namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Twig;

get_header();

Twig::rendPage(
    '@VisitTail/empty.html.twig',
    [
        'name' => 'Page index',
        'post' => null,
        'excerpt' => '',
        'tags' => [],
        'image' => null,
        'icone' => null,
        'recommandations' => [],
        'bgCat' => '',
        'urlBack' => '',
        'categoryName' => 'name',
        'nameBack' => '',
        'content' => '',
    ]
);

get_footer();