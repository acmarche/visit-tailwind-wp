<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Elasticsearch\Data\ElasticData;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Enregistrement des routes pour les api pour les composants VueJs
 */
class ApiData
{
    public static function pivotFiltresByName(WP_REST_Request $request): WP_Error|WP_REST_Response|WP_HTTP_Response
    {
        $name = $request->get_param('name');

        $pivotRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);

        $filtres = $pivotRepository->findByNameOrUrn($name);

        return rest_ensure_response($filtres);
    }

    public static function getFiltersByCategory(WP_REST_Request $request): WP_Error|WP_REST_Response|WP_HTTP_Response
    {
        $categoryWpId = (int)$request->get_param('categoryId');
        $flatWithChildren = (bool)$request->get_param('flatWithChildren');
        $removeFilterEmpty = (bool)$request->get_param('filterCount');

        if ($categoryWpId < 1) {
            Mailer::sendError('error cat id filtres', 'missing param categoryId');

            return new WP_Error(500, 'missing param categoryId');
        }

        $wpFilterRepository = new WpFilterRepository();
        $filtres = $wpFilterRepository->getCategoryFilters(
            $categoryWpId,
            $flatWithChildren,
            $removeFilterEmpty,
            unsetParent: true,
            onlyPivot: true
        );

        return rest_ensure_response($filtres);
    }

    public static function pivotOffres(WP_REST_Request $request): WP_Error|WP_REST_Response|WP_HTTP_Response
    {
        $currentCategoryId = (int)$request->get_param('category');
        $filtreSelected = (int)$request->get_param('filtre');
        $filtreType = (string)$request->get_param('filtreType');

        if (0 === $currentCategoryId) {
            Mailer::sendError('error hades offre', 'missing param keyword');

            return new WP_Error(500, 'missing param category');
        }

        $wpRepository = new WpRepository();
        if (in_array($currentCategoryId, Theme::CATEGORIES_AGENDA)) {
            try {
                $events = $wpRepository->getEvents($filtreSelected);
                array_map(
                    function ($event) use ($currentCategoryId) {
                        $event->url = RouterPivot::getUrlOffre($currentCategoryId, $event->codeCgt);
                    },
                    $events
                );
                $language = LocaleHelper::getSelectedLanguage();
                $postUtils = new PostUtils();
                $offres = $postUtils->convertOffresToArray($events, $currentCategoryId, $language);
                RouterPivot::setLinkOnCommonItems($offres, $currentCategoryId, $language);

                return rest_ensure_response($offres);
            } catch (NonUniqueResultException|InvalidArgumentException $e) {
                return rest_ensure_response([]);
            }

        }

        try {
            $offres = $wpRepository->findAllArticlesForCategory($currentCategoryId, $filtreSelected, $filtreType);
        } catch (NonUniqueResultException|InvalidArgumentException $e) {
            return rest_ensure_response([$e->getMessage()]);
        }

        return rest_ensure_response($offres);
    }

    /**
     * Pour alimenter le moteur de recherche depuis l'extérieur.
     */
    public static function getAll(): \WP_Error|WP_HTTP_Response|WP_REST_Response
    {
        $data = [];
        $elasticData = new ElasticData();
        $data['posts'] = $elasticData->getPosts();
        $data['categories'] = $elasticData->getCategories();
        $data['offres'] = $elasticData->getOffres();

        return rest_ensure_response($data);
    }

    public static function findShortsByNameOrCode(WP_REST_Request $request): \WP_Error|WP_HTTP_Response|WP_REST_Response
    {
        $name = $request->get_param('name');
        $wpRepository = new WpRepository();
        try {
            $offres = array_filter($wpRepository->getAllOffresShorts(), function (\stdClass $offre) use ($name) {
                if (preg_match(strtoupper("#".$name."#"), strtoupper($offre->name)) ||
                    preg_match(strtoupper("#".$name."#"), $offre->codeCgt)) {
                    return true;
                }

                return false;
            });

            return rest_ensure_response(array_values($offres));
        } catch (InvalidArgumentException $e) {
            return rest_ensure_response(['error' => $e->getMessage()]);
        }
    }

    public static function getOffersShortByCodesCgt(WP_REST_Request $request
    ): WP_Error|WP_REST_Response|WP_HTTP_Response {
        $categoryWpId = (int)$request->get_param('categoryId');
        $wpRepository = new WpRepository();
        $wpFilterRepository = new WpFilterRepository();
        $codesCgt = $wpFilterRepository->getCodesCgtByCategoryId($categoryWpId);

        try {
            $offers = $wpRepository->findOffersShortByCodesCgt($codesCgt);
        } catch (\Exception $e) {
            $offers = [];
        }

        foreach ($offers as $offer) {
            $offer->urlPivot = RouterPivot::getRouteOfferToPivotSite($offer->codeCgt);
            $offer->urlSite = RouterPivot::getUrlOffre($categoryWpId, $offer->codeCgt);
        }

        $offers = PostUtils::sortOffresByName($offers);

        return rest_ensure_response($offers);
    }

    public static function getAllWalkFilters(WP_REST_Request $request
    ): WP_Error|WP_REST_Response|WP_HTTP_Response {

        $categoryWpId = (int)$request->get_param('categoryId');

        $wpFilterRepository = new WpFilterRepository();
        $types = $wpFilterRepository->getCategoryFilters($categoryWpId);

        return rest_ensure_response([
            'type' => $types,
            'localite' => self::localites(),
        ]);
    }

    private static function localites(): array
    {
        $categoryWpId = 11;
        $cache = Cache::instance('walks');
        $localites = $cache->get('localitesWalks-'.$categoryWpId, function () use ($categoryWpId) {
            $localites = [];
            $wpRepository = new WpRepository();
            try {
                $offres = $wpRepository->findOffersByCategory($categoryWpId);
                foreach ($offres as $offre) {
                    if ($offre->adresse1) {
                        if ($localite = $offre->adresse1->localite) {
                            $localites[$localite[0]->value] = [
                                'id' => $localite[0]->value,
                                'name' => $localite[0]->value,
                            ];
                        }
                    }
                }

                return PostUtils::sortArrayByName(array_values($localites));
            } catch (NonUniqueResultException|InvalidArgumentException $e) {
                return [];
            }
        });

        return $localites;
    }

    public static function getAllWalks(WP_REST_Request $request
    ): WP_Error|WP_REST_Response|WP_HTTP_Response {
        $categoryWpId = (int)$request->get_param('categoryId');
        $cache = Cache::instance('walks');

        return rest_ensure_response($cache->get('walks-'.$categoryWpId.time(), function () use ($categoryWpId) {

            $wpRepository = new WpRepository();

            try {
                $offres = $wpRepository->findOffersByCategory($categoryWpId);
            } catch (NonUniqueResultException|InvalidArgumentException $e) {
                $offres = [];
            }

            $gpxViewer = new GpxViewer();
            $offers = [];
            foreach ($offres as $offre) {
                try {
                    $locations = [];
                    if (count($offre->gpxs) > 0) {
                        $gpx = $offre->gpxs[0];
                        foreach ($gpxViewer->getLocations($gpx) as $location) {
                            $locations[] = [$location['latitude'], $location['longitude']];
                        }
                    }
                    $offers[] = [
                        'codeCgt' => $offre->codeCgt,
                        'nom' => $offre->nom,
                        'url' => RouterPivot::getUrlOffre($categoryWpId, $offre->codeCgt),
                        'images' => $offre->images,
                        'address' => $offre->adresse1,
                        'localite' => $offre->adresse1->localite[0]->value,
                        'type' => self::getTypeWalk($offre->codeCgt),
                        'locations' => $locations,
                        'gpx_duree' => $offre->gpx_duree,
                        'gpx_difficulte' => $offre->gpx_difficulte,
                        'gpx_distance' => $offre->gpx_distance,
                    ];
                } catch (\Exception|InvalidArgumentException $e) {

                }
            }

            return rest_ensure_response($offers);
        }));
    }

    private static function getTypeWalk(string $codeCgt): int
    {
        $wpFilterRepository = new WpFilterRepository();
        $id = 130;//bike
        if (in_array($codeCgt, $wpFilterRepository->getCodesCgtByCategoryId($id))) {
            return $id;
        }
        $id = 132;//horse
        if (in_array($codeCgt, $wpFilterRepository->getCodesCgtByCategoryId($id))) {
            return $id;
        }

        return 131;//foot
    }


    public static function offerByCodeCgt(WP_REST_Request $request
    ): WP_Error|WP_REST_Response|WP_HTTP_Response {
        $codeCgt = (string)$request->get_param('codeCgt');
        $wpRepository = new WpRepository();

        try {
            $offre = $wpRepository->getOffreByCgtAndParse($codeCgt);
        } catch (\Exception|InvalidArgumentException $e) {

            return rest_ensure_response(['error' => $e->getMessage()]);
        }

        $offre->url = RouterPivot::getUrlOffre(11, $offre->codeCgt);

        return rest_ensure_response($offre);
    }
}
