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
        $filtres = $wpFilterRepository->getCategoryFilters($categoryWpId);

        return rest_ensure_response($filtres);
    }

    public static function getAllWalks(WP_REST_Request $request
    ): WP_Error|WP_REST_Response|WP_HTTP_Response {
        $categoryWpId = (int)$request->get_param('categoryId');
        $args = $request->get_param('args');
        $localite = $args['localite'];
        $type = $args['type'];
        $coordinates = $args['coordinates'];
        $cache = Cache::instance('walks');

        $data = $cache->get('walks5-'.$categoryWpId, function () use ($categoryWpId) {

            $wpRepository = new WpRepository();

            try {
                $offres = $wpRepository->findOffersByCategory($categoryWpId);
            } catch (NonUniqueResultException|InvalidArgumentException $e) {
                $offres = [];
            }

            $gpxViewer = new GpxViewer();
            $offers = [];
            foreach ($offres as $offre) {
                $locations = [];
                try {
                    if (count($offre->gpxs) > 0) {
                        $gpx = $offre->gpxs[0];
                        $gpxViewer->renderWithPlugin($gpx);
                        if ($gpx && isset($gpx->data['locations'])) {
                            foreach ($gpx->data['locations'] as $location) {
                                $locations[] = [$location['latitude'], $location['longitude']];
                            }
                        }
                    }

                    $offers[] = [
                        'codeCgt' => $offre->codeCgt,
                        'nom' => $offre->nom,
                        'url' => RouterPivot::getUrlOffre($categoryWpId, $offre->codeCgt),
                        'images' => $offre->images,
                        'address' => $offre->adresse1,
                        'locations' => $locations,
                    ];
                } catch (\Exception|InvalidArgumentException $e) {

                }
            }
            if (count($offers) > 0) {
                return $offers;
            }

            return null;
        });

        return rest_ensure_response($data);
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
