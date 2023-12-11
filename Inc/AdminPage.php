<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Lib\PivotCategoriesTable;
use VisitMarche\ThemeTail\Lib\SyncPivot;
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
        add_submenu_page(
            'pivot_home',
            'Toutes les offres',
            'Toutes les offres',
            'edit_posts',
            'pivot_offres',
            fn() => $this::allOffersRender(),
        );
    }

    private static function homepageRender(): void
    {
        Twig::rendPage(
            '@VisitTail/admin/home.html.twig',
            [

            ]
        );

    }

    private static function filtresRender(): void
    {
        $pivotRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filters = $pivotRepository->findWithChildren(true);

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

    private static function categoriesFiltresRender(): void
    {
        $syncPivot = new SyncPivot();
        $syncPivot->cleanFiltres();
        $syncPivot->syncUrns();
        $categories = $syncPivot->categoriesWithFiltre();
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

    private static function allOffersRender(): void
    {
        $wpRepository = new WpRepository();
        try {
            $offres = $wpRepository->getAllOffresShorts();
        } catch (InvalidArgumentException $e) {
            $offres = [];
        }
        Twig::rendPage(
            '@VisitTail/admin/offers_list.html.twig',
            [
                'offers' => $offres,
            ]
        );
    }
}
