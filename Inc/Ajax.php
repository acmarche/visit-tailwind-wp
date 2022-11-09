<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;

class Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_action_delete_filtre', fn() => $this::actionDeleteFiltre());
        add_action('wp_ajax_action_add_filtre', fn() => $this::actionAddFiltre());
    }

    function actionDeleteFiltre()
    {
        $categoryWpId = (int)$_POST['categoryId'];
        $id = (int)$_POST['id'];
        $categoryFiltres = [];
        if ($categoryWpId && $id) {
            $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
            if ($filtre = $filtreRepository->find($id)) {
                $urn = $filtre->urn;
                $categoryFiltres = PivotMetaBox::getMetaPivotTypesOffre($categoryWpId);
                foreach ($categoryFiltres as $key => $data) {
                    if ($urn == $data['urn']) {
                        unset($categoryFiltres[$key]);
                        update_term_meta($categoryWpId, PivotMetaBox::PIVOT_REFRUBRIQUE, $categoryFiltres);
                    }
                }
            }
        }
        echo json_encode($categoryFiltres);
        wp_die();
    }

    function actionAddFiltre()
    {
        $categoryFiltres = [];
        $categoryId = (int)$_POST['categoryId'];
        $typeOffreId = (int)$_POST['typeOffreId'];
        $withChildren = filter_var($_POST['withChildren'], FILTER_VALIDATE_BOOLEAN);
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);

        if ($categoryId > 0 && $typeOffreId > 0) {
            $categoryFiltres = PivotMetaBox::getMetaPivotTypesOffre($categoryId);
            $filtre = $filtreRepository->find($typeOffreId);
            if ($filtre) {
                $meta = ['urn' => $filtre->urn, 'withChildren' => $withChildren];
                $categoryFiltres[] = $meta;
                update_term_meta($categoryId, PivotMetaBox::PIVOT_REFRUBRIQUE, $categoryFiltres);
            }
        }
        echo json_encode($categoryFiltres);
        wp_die();
    }
}