<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\Utils\CacheUtils;
use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Lib\Twig;

global $post;
$cacheUtils = new CacheUtils();
$cache = $cacheUtils->instance();

$dataString = $cache->get('list_walks2', function () use ($post) {
    return file_get_contents('https://www.visitmarche.be/wp-json/pivot/walks_list/11');
});

$filters = $cache->get('filters_walk3', function () use ($post) {
    $object = json_decode(file_get_contents('https://www.visitmarche.be/wp-json/pivot/walk_filters/11'));

    return ['type' => $object->type, 'localites' => $object->localite];
});
AssetsLoad::enqueueLeaflet();
AssetsLoad::enqueueMarkercluster();
get_header();
Twig::rendPage(
    '@VisitTail/balade/index.html.twig',
    [
        'name' => $post->post_title,
        'post' => $post,
        'excerpt' => $post->post_excerpt,
        'tags' => [],
        'image' => '',
        'icone' => null,
        'recommandations' => [],
        'bgCat' => '',
        'urlBack' => '',
        'categoryName' => '',
        'nameBack' => '',
        'content' => '',
        'dataString' => $dataString,
        'filters' => $filters,
    ]
);
get_footer();