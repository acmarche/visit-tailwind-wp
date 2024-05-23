<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\Utils\CacheUtils;
use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpFilterRepository;
use VisitMarche\ThemeTail\Lib\WpRepository;

global $post;
$cacheUtils = new CacheUtils();
$cache = $cacheUtils->instance();

$dataString = $cache->get('list_walks', function () use ($post) {
    $wpRepository = new WpRepository();

    return json_encode($wpRepository->getAllWalks());
});

$filters = $cache->get('filters_walk', function () use ($post) {
    $wpFilterRepository = new WpFilterRepository();

    return [
        'type' => $wpFilterRepository->getCategoryFilters(Theme::CATEGORY_BALADES),
        'localite' => WpFilterRepository::getLocalites(),
    ];
});

AssetsLoad::enqueueLeaflet();
AssetsLoad::enqueueMarkercluster();
add_action('wp_head', fn() => my_custom_styles(), 100);

function my_custom_styles()
{
    echo "<style>#mainheader {background-color: black;}</style>";
}

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