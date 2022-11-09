<?php

namespace VisitMarche\ThemeTail\Lib\Elasticsearch;

use Elastica\Exception\InvalidException;
use Elastica\ResultSet;
use WP_Query;

/**
 * https://github.com/ruflin/Elastica/tree/master/tests
 * Class Searcher.
 */
class Searcher
{
    public function searchFromWww(string $keyword): bool|string
    {
        return file_get_contents(
            'https://www.marche.be/visit-elasticsearch/search.php?keyword='.urlencode($keyword)
        );
    }

    /**
     * @param string $wp_query
     *
     * @return ResultSet
     *
     * @throws InvalidException
     */
    public function searchRecommandations(WP_Query $wp_query): array
    {
        $hits = [];

        $queries = $wp_query->query;
        $queryString = implode(' ', $queries);
        $queryString = preg_replace('#-#', ' ', $queryString);
        $queryString = preg_replace('#/#', ' ', $queryString);
        $queryString = strip_tags($queryString);
        if ('' !== $queryString) {
            $results = $this->searchFromWww($queryString);
            $hits = json_decode($results, null, 512, JSON_THROW_ON_ERROR);
        }

        return array_map(
            function ($hit) {
                $hit->title = $hit->name;
                $hit->tags = [];

                return $hit;
            },
            $hits
        );
    }
}
