<?php

namespace VisitMarche\ThemeTail\Inc;

use VisitMarche\ThemeTail\Lib\ApiData;
use WP_Error;

/**
 * Enregistrement des routes pour les api pour les composants vuejs
 * https://visit.marche.be/wp-json/pivot/category_offers/120
 * https://visit.marche.be/wp-json/pivot/offres/120/0/wp
 * https://visit.marche.be/wp-json/pivot/offres/8/10/pivot
 * https://visit.marche.be/wp-json/pivot/category_filters/120/0/1
 */
class ApiRoutes
{
    public function __construct()
    {
        if (!is_admin()) {
            $this->registerPivot();
        }
    }

    public function registerPivot(): void
    {
        /**
         * return category's filters
         */
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'category_filters/(?P<categoryId>[\w]+)/(?P<flatWithChildren>[\w]+)/(?P<filterCount>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::getFiltersByCategory($args),
                        'permission_callback' => fn() => true,
                    ]
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'filtres_name/(?P<name>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::pivotFiltresByName($args),
                        'permission_callback' => fn() => true,
                    ]
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'offres/(?P<category>[\d]+)/(?P<filtre>[\d]+)/(?P<filtreType>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::pivotOffres($args),
                        'permission_callback' => fn() => true,
                    ],
                );
            }
        );

        /**
         * return offer's by codesCgt
         */
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'category_offers/(?P<categoryId>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::getOffersShortByCodesCgt($args),
                        'permission_callback' => fn() => true,
                    ]
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'find-offers-by-name/(?P<name>[\w\s%20]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::findShortsByNameOrCode($args),
                        'permission_callback' => fn() => true,
                    ],
                );
            }
        );

        /**
         * filter walks
         */
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'walk_filters/(?P<categoryId>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::getAllWalkFilters($args),
                        'permission_callback' => fn() => true,
                    ]
                );
            }
        );

        /**
         * all walks
         */
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'walks_list/(?P<categoryId>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::getAllWalks($args),
                        'permission_callback' => fn() => true,
                    ]
                );
            }
        );

        /**
         * One offer by cgt
         */
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'offer/(?P<codeCgt>[\w-]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::offerByCodeCgt($args),
                        'permission_callback' => fn() => true,
                    ],
                );
            }
        );
    }

    /**
     * Todo pour list/users !!
     */
    public function secureApi(): void
    {
        add_filter(
            'rest_authentication_errors',
            function ($result) {
                // If a previous authentication check was applied,
                // pass that result along without modification.
                if (true === $result || is_wp_error($result)) {
                    return $result;
                }

                // No authentication has been performed yet.
                // Return an error if user is not logged in.
                if (!is_user_logged_in()) {
                    return new WP_Error(
                        'rest_not_logged_in',
                        __('You are not currently logged in.'),
                        [
                            'status' => 401,
                        ]
                    );
                }

                // Our custom authentication check should have no effect
                // on logged-in requests
                return $result;
            }
        );
    }
}
