<?php

namespace VisitMarche\ThemeTail\Lib;

class SyncPivot
{
    public function syncUrns()
    {
        $languages = ['en', 'nl'];
        $wpRepository = new WpRepository();
        foreach ($wpRepository->getCategoriesFromWp() as $category) {
            $filtresFr = WpFilterRepository::getMetaPivotTypesOffre($category->term_id);
            $codesCgtFr = WpFilterRepository::getMetaPivotCodesCgtOffres($category->term_id);
            foreach ($languages as $language) {
                $categoryId = apply_filters('wpml_object_id', $category->term_id, 'category', true, $language);
                if ($categoryId) {
                    $categoryLng = get_category($categoryId);
                    if (count($filtresFr) > 0) {
                        $filtresLng = WpFilterRepository::getMetaPivotTypesOffre($categoryLng->term_id);
                        $diff = array_diff(array_column($filtresFr, 'urn'), (array_column($filtresLng, 'urn')));
                        if (count($diff) > 0) {
                            update_term_meta($categoryLng->term_id, WpRepository::PIVOT_REFRUBRIQUE, $filtresFr);
                        }
                    }
                    if (count($codesCgtFr) > 0) {
                        $codesCgtLng = WpFilterRepository::getMetaPivotCodesCgtOffres($categoryLng->term_id);
                        $diff = array_diff($codesCgtFr, $codesCgtLng);
                        if (count($diff) > 0) {
                            update_term_meta($categoryLng->term_id, WpRepository::PIVOT_REFOFFERS, $filtresFr);
                        }
                    }
                }
            }
        }
    }

    public function cleanFiltres()
    {
        $wpRepository = new WpRepository();
        foreach ($wpRepository->getCategoriesFromWp() as $category) {
            $update = false;
            $filtres = WpFilterRepository::getMetaPivotTypesOffre($category->term_id);
            foreach ($filtres as $key => $filtre) {
                if (!isset($filtre['urn'])) {
                    $update = true;
                    unset($filtres[$key]);
                }
            }
            if ($update) {
                update_term_meta($category->term_id, WpRepository::PIVOT_REFRUBRIQUE, $filtres);
            }
        }
    }
}