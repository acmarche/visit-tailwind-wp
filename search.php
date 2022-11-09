<?php

use VisitMarche\ThemeTail\Lib\Elasticsearch\Searcher;
use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();
$searcher = new Searcher();
$keyword = get_search_query();
$results = $searcher->searchFromWww($keyword);
$hits = json_decode($results, null, 512, JSON_THROW_ON_ERROR);

if (isset($hits['error'])) {
    Mailer::sendError('wp error search', $hits['error']);
    Twig::rendPage(
        '@VisitTail/errors/500.html.twig',
        [
            'message' => $hits['error'],
            'title' => 'Erreur lors de la recherche',
            'tags' => [],
            'relations' => [],
        ]
    );
    get_footer();

    return;
}

Twig::rendPage(
    '@VisitTail/search.html.twig',
    [
        'title' => 'Search',
        'urlBack' => '/',
        'nameBack' => 'Home',
        'categoryName' => 'Search',
        'keyword' => $keyword,
        'results' => $hits,
        'count' => is_countable($hits) ? \count($hits) : 0,
    ]
);
get_footer();
