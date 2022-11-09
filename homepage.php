<?php

namespace VisitMarche\ThemeTail;

use AcSort;
use Exception;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

get_header();

$wpRepository = new WpRepository();

$intro = $wpRepository->getIntro();
$inspirationCat = $wpRepository->getCategoryBySlug('inspirations');
$inspirations = $wpRepository->getPostsByCatId($inspirationCat->cat_ID);
$category_order = get_term_meta($inspirationCat->cat_ID, CategoryMetaBox::KEY_NAME_ORDER, true);
if ('manual' === $category_order) {
    $inspirations = AcSort::getSortedItems($inspirationCat->cat_ID, $inspirations);
}
$categoryAgenda = get_category_by_slug('agenda');
$urlAgenda = '/';
$urlInspiration = get_category_link($inspirationCat);
try {
    $events = $wpRepository->getEvents(true);
    if ($categoryAgenda) {
        $urlAgenda = get_category_link($categoryAgenda);
        array_map(
            function ($event) use ($categoryAgenda) {
                $event->url = RouterPivot::getUrlOffre($event, $categoryAgenda->cat_ID);
            },
            $events
        );
    }
} catch (Exception) {
    $events = [];
}

$inspirations = array_slice($inspirations, 0, 4);
$events = array_slice($events, 0, 4);
$menu = new Menu();
$icones = $menu->getIcones();

Twig::rendPage(
    '@VisitTail/homepage.html.twig',
    [
        'events' => $events,
        'inspirations' => $inspirations,
        'urlAgenda' => $urlAgenda,
        'urlInspiration' => $urlInspiration,
        'intro' => $intro,
        'icones' => $icones,
    ]
);
get_footer();