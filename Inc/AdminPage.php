<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\PivotCategoriesTable;
use VisitMarche\ThemeTail\Lib\PivotOffresTable;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

class AdminPage
{
    public function __construct()
    {
        add_action('admin_menu', fn($args) => $this::my_add_menu_items());
    }

    function my_add_menu_items()
    {
        add_menu_page(
            'pivot_home',
            'Pivot',
            'edit_posts',
            'pivot_home',
            fn() => $this::homepageRender(),
            get_template_directory_uri().'/assets/tartine/Icone_Pivot_Small.png'
        );
        add_submenu_page(
            'pivot_home',
            'Pivot filtres',
            'Filtres',
            'edit_posts',
            'pivot_filtres',
            fn() => $this::filtresRender(),
        );
        add_submenu_page(
            'pivot_home',
            'Catégories avec filtres',
            'Catégories avec filtres',
            'edit_posts',
            'pivot_categories_filtre',
            fn() => $this::categoriesFiltresRender(),
        );
    }

    private static function homepageRender()
    {
        Twig::rendPage(
            '@VisitTail/admin/home.html.twig',
            [

            ]
        );

    }

    private static function filtresRender()
    {
        $pivotRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filters = $pivotRepository->findWithChildren();

        $category = get_category_by_slug('offres');
        $categoryUrl = get_category_link($category);
        $urlAdmin = admin_url('admin.php?page=pivot_offres&filtreId=');

        Twig::rendPage(
            '@VisitTail/admin/filtres_list.html.twig',
            [
                'filters' => $filters,
                'urlAdmin' => $urlAdmin,
                'categoryUrl' => $categoryUrl,
            ]
        );
    }

    private static function offresRender()
    {
        $filtre = $_GET['filtreId'] ?? null;
        if (!$filtre) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Choisissez un filtre dans le menu',
                ]
            );

            return;
        }
        $language = LocaleHelper::getSelectedLanguage();
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filtres = $filtreRepository->findByIdsOrUrns([$filtre]);
        if (count($filtres) == 0) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Le filtre n\'a pas été trouvé dans la base de donnée',
                ]
            );

            return;
        }
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        $offres = $pivotRepository->getOffres($filtres);
        $pivotOffresTable = new PivotOffresTable();
        $pivotOffresTable->data = $offres;
        $pivotOffresTable->categoryId = 14;
        ?>
        <div class="wrap">
            <h2>Les offres pour <?php echo $filtres[0]->nom; ?></h2>
            <?php $pivotOffresTable->prepare_items();
            $pivotOffresTable->display();
            ?>
        </div>
        <?php
    }

    private static function offreRender()
    {
        $codeCgt = $_GET['codeCgt'] ?? null;
        if (!$codeCgt) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Choisissez une offre dans la liste par filtre',
                ]
            );

            return;
        }
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        $offre = $pivotRepository->getOffreByCgtAndParse($codeCgt, Offre::class);
        if (!$offre) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Offre non trouvée',
                ]
            );

            return;
        }
        Twig::rendPage(
            '@VisitTail/admin/offre.html.twig',
            [
                'offre' => $offre,
            ]
        );
    }

    private static function categoriesFiltresRender()
    {
        $categories = [];
        $wpRepository = new WpRepository();
        foreach ($wpRepository->getCategoriesFromWp() as $category) {
            $filtres = $wpRepository->getCategoryFilters($category->term_id, false, false);
            if (count($filtres) > 0) {
                $categories[] = $category;
            } else {
                $categoryFiltres  = PivotMetaBox::getMetaPivotTypesOffre($category->term_id);
                foreach ($categoryFiltres as $data) {

                }
                $categories[] = $category;
            }
        }
        $pivotOffresTable = new PivotCategoriesTable();
        $pivotOffresTable->data = $categories;
        ?>
        <div class="wrap">
            <h2>Les catégories wordpress avec des références Pivot</h2>
            <?php $pivotOffresTable->prepare_items();
            $pivotOffresTable->display();
            ?>
        </div>
        <?php
    }
}
