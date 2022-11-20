<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Elasticsearch\Data\ElasticData;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Enregistrement des routes pour les api pour les composants vue
 */
class ApiData
{
    public static function pivotFiltresByName(WP_REST_Request $request)
    {
        $name = $request->get_param('name');

        $pivotRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);

        $filtres = $pivotRepository->findByNameOrUrn($name);

        return rest_ensure_response($filtres);
    }

    public static function pivotFiltresByCategory(WP_REST_Request $request)
    {
        $categoryWpId = (int)$request->get_param('categoryId');
        $flatWithChildren = (bool)$request->get_param('flatWithChildren');
        $filterCount = (bool)$request->get_param('filterCount');

        if ($categoryWpId < 1) {
            Mailer::sendError('error cat id filtres', 'missing param categoryId');

            return new WP_Error(500, 'missing param categoryId');
        }

        $filtres = WpRepository::getCategoryFilters($categoryWpId, $flatWithChildren, $filterCount, unsetParent: true);

        return rest_ensure_response($filtres);
    }

    public static function pivotOffres(WP_REST_Request $request)
    {
        $currentCategoryId = (int)$request->get_param('category');
        $filtreSelected = (int)$request->get_param('filtre');

        if (0 === $currentCategoryId) {
            Mailer::sendError('error hades offre', 'missing param keyword');

            return new WP_Error(500, 'missing param category');
        }

        $offres = self::getOffres($filtreSelected, $currentCategoryId);

        return rest_ensure_response($offres);
    }

    /**
     * Pour alimenter le moteur de recherche depuis l'exterieur.
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

    private static function getOffres(int $filtreSelected, int $currentCategoryId): array
    {
        $offres = $filtres = [];
        $language = LocaleHelper::getSelectedLanguage();
        $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $wpRepository = new WpRepository();
        $postUtils = new PostUtils();

        $typeOffreSelected = null;
        if ($filtreSelected == 0) {
            $filtres = $wpRepository->getCategoryFilters($currentCategoryId);
        } else {
            if ($typeOffreSelected = $typeOffreRepository->find($filtreSelected)) {
                $filtres[] = $typeOffreSelected;
            }
        }

        if ([] !== $filtres) {
            if (in_array($currentCategoryId, Theme::CATEGORIES_AGENDA)) {
                $offres = $wpRepository->getEvents(typeOffre:  $typeOffreSelected);
            } else {
                $offres = $wpRepository->getOffres($filtres);
            }
        }

        $offres = $postUtils->convertOffresToArray($offres, $currentCategoryId, $language);
        //$posts = $wpRepository->getPostsByCatId($currentCategoryId);
        //fusion offres et articles
        //$posts = $postUtils->convertPostsToArray($posts);

        return $offres;

        //return array_merge($posts, $offres);
    }
}
