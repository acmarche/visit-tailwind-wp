<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use VisitMarche\ThemeTail\Lib\WpFilterRepository;
use VisitMarche\ThemeTail\Lib\WpRepository;

class Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_action_delete_filtre', fn() => $this::actionDeleteFiltre());
        add_action('wp_ajax_action_add_filtre', fn() => $this::actionAddFiltre());
        add_action('wp_ajax_action_add_offer', fn() => $this::actionAddOffer());
        add_action('wp_ajax_action_delete_offer', fn() => $this::actionDeleteOffer());
    }

    function actionDeleteFiltre(): void
    {
        $categoryWpId = (int)$_POST['categoryId'];
        $id = (int)$_POST['id'];
        $categoryFiltres = [];
        if ($categoryWpId && $id) {
            $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
            if ($filtre = $filtreRepository->find($id)) {
                $urn = $filtre->urn;
                $categoryFiltres = WpFilterRepository::getMetaPivotTypesOffre($categoryWpId);
                foreach ($categoryFiltres as $key => $data) {
                    if ($urn == $data['urn']) {
                        unset($categoryFiltres[$key]);
                        update_term_meta($categoryWpId, WpRepository::PIVOT_REFRUBRIQUE, $categoryFiltres);
                    }
                }
            }
        }
        echo json_encode($categoryFiltres);
        wp_die();
    }

    function actionAddFiltre(): void
    {
        $categoryFiltres = [];
        $categoryId = (int)$_POST['categoryId'];
        $typeOffreId = (int)$_POST['typeOffreId'];
        $withChildren = filter_var($_POST['withChildren'], FILTER_VALIDATE_BOOLEAN);
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);

        if ($categoryId > 0 && $typeOffreId > 0) {
            $categoryFiltres = WpFilterRepository::getMetaPivotTypesOffre($categoryId);
            $filtre = $filtreRepository->find($typeOffreId);
            if ($filtre) {
                $meta = ['urn' => $filtre->urn, 'withChildren' => $withChildren];
                $categoryFiltres[] = $meta;
                update_term_meta($categoryId, WpRepository::PIVOT_REFRUBRIQUE, $categoryFiltres);
            }
        }
        echo json_encode($categoryFiltres);
        wp_die();
    }

    private static function actionAddOffer(): void
    {
        $categoryId = (int)$_POST['categoryId'];
        $codeCgt = (string)$_POST['codeCgt'];
        $codesCgt = [];
        if ($categoryId > 0 && $codeCgt) {
            $codesCgt = WpFilterRepository::getMetaPivotCodesCgtOffres($categoryId);
            if (!in_array($codeCgt, $codesCgt)) {
                $codesCgt[] = $codeCgt;
                update_term_meta($categoryId, WpRepository::PIVOT_REFOFFERS, $codesCgt);
            }
        }

        echo json_encode($codesCgt);
        wp_die();
    }

    private static function actionDeleteOffer(): void
    {
        $categoryId = (int)$_POST['categoryId'];
        $codeCgt = (string)$_POST['codeCgt'];
        $codesCgt = [];

        if ($categoryId > 0 && $codeCgt) {
            $codesCgt = WpFilterRepository::getMetaPivotCodesCgtOffres($categoryId);
            $key = array_search($codeCgt, $codesCgt);
            if ($key !== false) {
                unset($codesCgt[$key]);
                update_term_meta($categoryId, WpRepository::PIVOT_REFOFFERS, $codesCgt);
            }
        }
        echo json_encode($codesCgt);
        wp_die();
    }
}