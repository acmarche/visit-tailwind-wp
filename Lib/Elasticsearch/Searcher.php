<?php

namespace VisitMarche\ThemeTail\Lib\Elasticsearch;

use Elastica\Exception\InvalidException;
use Elastica\ResultSet;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use VisitMarche\ThemeTail\Lib\Mailer;
use WP_Query;

/**
 * https://github.com/ruflin/Elastica/tree/master/tests
 * Class Searcher.
 */
class Searcher
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();

    }

    /**
     * @throws \Exception
     */
    public function searchFromWww(string $keyword): bool|string
    {
        $url = 'https://www.marche.be/visit-elasticsearch/search.php?keyword='.urlencode($keyword);

        try {
            $response = $this->httpClient->request(
                'GET',
                $url, [
                    'timeout' => 2.5,
                ]
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('search', 'erreur '.$exception->getMessage());
            throw  new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @throws \Exception
     */
    public function dump(): bool|string
    {
        $url = 'https://www.hotton.be/';

        try {
            $response = $this->httpClient->request(
                'GET',
                $url, [
                    'timeout' => 2.5,
                ]
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('search', 'erreur '.$exception->getMessage());
            throw  new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
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
        $queries = $wp_query->query;
        $queryString = implode(' ', $queries);
        $queryString = preg_replace('#-#', ' ', $queryString);
        $queryString = preg_replace('#/#', ' ', $queryString);
        $queryString = strip_tags($queryString);
        if ('' !== $queryString) {
            try {
                $results = $this->searchFromWww($queryString);
                $hits = json_decode($results, null, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                return [];
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

        return [];

    }
}
