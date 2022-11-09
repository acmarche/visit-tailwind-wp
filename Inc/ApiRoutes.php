<?php

namespace VisitMarche\ThemeTail\Inc;

use VisitMarche\ThemeTail\Lib\ApiData;
use WP_Error;

/**
 * Enregistrement des routes pour les api pour les composants react
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
        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'filtres_category/(?P<categoryId>[\w]+)/(?P<flatWithChildren>[\w]+)/(?P<filterCount>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::pivotFiltresByCategory($args),
                    ]
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'filtres_parent/(?P<parentId>[\w]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::pivotFiltresByParent($args),
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
                    ]
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'pivot',
                    'offres/(?P<category>[\d]+)/(?P<filtre>[\d]+)',
                    [
                        'methods' => 'GET',
                        'callback' => fn($args) => ApiData::pivotOffres($args),
                    ],
                    true
                );
            }
        );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'visit',
                    'all',
                    [
                        'methods' => 'GET',
                        'callback' => fn() => ApiData::getAll(),
                    ],
                    true
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
