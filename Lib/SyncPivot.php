<?php

namespace VisitMarche\ThemeTail\Lib;

use VisitMarche\ThemeTail\Inc\PivotMetaBox;

class SyncPivot
{
    /**
     * @return array|\WP_Term[]
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function categoriesWithFiltre(): array
    {
        $categories = [];
        $wpRepository = new WpRepository();
        foreach ($wpRepository->getCategoriesFromWp() as $category) {
            $filtres = PivotMetaBox::getMetaPivotTypesOffre($category->term_id);
            if (count($filtres) > 0) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public function syncUrns()
    {
        $languages = ['en','nl'];
        foreach ($this->categoriesWithFiltre() as $category) {
            $filtresFr = PivotMetaBox::getMetaPivotTypesOffre($category->term_id);
            foreach ($languages as $language) {
                $categoryId = apply_filters('wpml_object_id', $category->term_id, 'category', true, $language);
                if ($categoryId) {
                    $categoryLng = get_category($categoryId);
                    $filtresEn = PivotMetaBox::getMetaPivotTypesOffre($categoryLng->term_id);
                    $diff = array_diff(array_column($filtresFr, 'urn'), (array_column($filtresEn, 'urn')));
                    if (count($diff) > 0) {
                        update_term_meta($categoryLng->term_id, PivotMetaBox::PIVOT_REFRUBRIQUE, $filtresFr);
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
            $filtres = PivotMetaBox::getMetaPivotTypesOffre($category->term_id);
            foreach ($filtres as $key => $filtre) {
                if (!isset($filtre['urn'])) {
                    $update = true;
                    unset($filtres[$key]);
                }
            }
            if ($update) {
                update_term_meta($category->term_id, PivotMetaBox::PIVOT_REFRUBRIQUE, $filtres);
            }
        }
    }
}