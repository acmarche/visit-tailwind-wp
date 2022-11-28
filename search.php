<?php

use VisitMarche\ThemeTail\Lib\Elasticsearch\Searcher;
use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();
$searcher = new Searcher();
$keyword = get_search_query();
$hits = [];
if ($keyword) {
    try {

        $results = $searcher->searchFromWww($keyword);
        dump($results);
        $hits = json_decode($results, null, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $exception) {
        Mailer::sendError('visit error search', $exception->getMessage());
    }
}
if (isset($hits['error'])) {
    Twig::rend500Page();
    Mailer::sendError('visit error search', $hits['error']);
    get_footer();

    return;
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
        'count' => is_countable($hits) ? \count($hits) : 0,
    ]
);
get_footer();
