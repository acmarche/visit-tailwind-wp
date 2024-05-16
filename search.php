<?php

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();
$searcher = PivotContainer::getSearchMeili(WP_DEBUG);
$keyword = get_search_query();
$hits = [];

if ($keyword) {
    try {
        $results = $searcher->search($keyword);
        $hits = $results->getHits();
    } catch (Exception $exception) {
        Twig::rend500Page($exception->getMessage());
        Mailer::sendError('visit error search', $exception->getMessage());
        get_footer();

        return;
    }
}
Twig::rendPage(
    '@VisitTail/search.html.twig',
    [
        'name' => 'Search',
        'urlBack' => '/',
        'nameBack' => 'Home',
        'categoryName' => 'Search',
        'image' => get_template_directory_uri().'/assets/tartine/bg_search.png',
        'keyword' => $keyword,
        'results' => $hits,
        'count' => $results?->count() ?? 0,
    ]
);
get_footer();
