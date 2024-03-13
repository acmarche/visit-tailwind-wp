<?php

namespace VisitMarche\ThemeTail;

use AcSort;
use Exception;
use Psr\Cache\InvalidArgumentException;
use SortLink;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

get_header();

$wpRepository = new WpRepository();

$intro = $wpRepository->getIntro();
try {
    $ideas = $wpRepository->getIdeas();
} catch (Exception $exception) {
    $ideas = [];
}
$inspirationCat = get_category_by_slug('inspirations');
$inspirations = $wpRepository->getPostsByCatId($inspirationCat->cat_ID);
$category_order = get_term_meta($inspirationCat->cat_ID, CategoryMetaBox::KEY_NAME_ORDER, true);
if ('manual' === $category_order) {
    $inspirations = AcSort::getSortedItems($inspirationCat->cat_ID, $inspirations);
}
$categoryAgenda = get_category_by_slug('agenda');
$urlAgenda = '/';
$urlInspiration = get_category_link($inspirationCat);
try {
    $events = $wpRepository->getEvents();
    if ($categoryAgenda) {
        $urlAgenda = get_category_link($categoryAgenda);
        array_map(
            function ($event) use ($categoryAgenda) {
                $event->url = RouterPivot::getUrlOffre($categoryAgenda->cat_ID, $event->codeCgt);
            },
            $events
        );
    }
} catch (Exception|InvalidArgumentException $exception) {
    $events = [];
}
$sortLink = false;
if (current_user_can('edit_post', 2)) {
    $sortLink = SortLink::linkSortArticles(2);
}
$inspirations = array_slice($inspirations, 0, 4);
$events = array_slice($events, 0, 4);
$menu = new Menu();
$icones = $menu->getIcones();
$imgs = [
    'home_ville2.jpg',
    '201114DJI_0750.jpg',
    '210911Chateau Hargimont HDR.jpg',
    '210911Paysage matinal HDR.jpg',
    '220213220213DJI_0057.jpg',
    '221125221112DJI_0037.jpg',
    '201114DJI_affinity0741.jpg',
    '210911DJI_0563.jpg',
    '220115DJI_0385 (2).jpg',
    '220305DJI_0135-HDR.jpg',
];
$img = array_rand($imgs);
$bgImg = $imgs[$img];

Twig::rendPage(
    '@VisitTail/homepage.html.twig',
    [
        'events' => $events,
        'inspirations' => $inspirations,
        'urlAgenda' => $urlAgenda,
        'urlInspiration' => $urlInspiration,
        'intro' => $intro,
        'icones' => $icones,
        'ideas' => $ideas,
        'bgimg' => $bgImg,
        'sortLink' => $sortLink,
    ]
);
get_footer();