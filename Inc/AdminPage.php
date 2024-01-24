<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Lib\PivotCategoriesTable;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\SyncPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

class AdminPage
{
    public function __construct()
    {
        add_action('admin_menu', fn($args) => $this::my_add_menu_items());
    }

    function my_add_menu_items(): void
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
            'Tous les filtres',
            'Tous les filtres',
            'edit_posts',
            'pivot_filtres',
            fn() => $this::filtresRender(),
        );
        add_submenu_page(
            'pivot_home',
            'Le catalogue',
            'Le catalogue',
            'edit_posts',
            'pivot_offres',
            fn() => $this::allOffersRender(),
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
            'Filtres sur une catégorie',
            'Filtres sur une catégorie',
            'edit_posts',
            'category_filters',
            fn() => $this::categoryFiltersRender(),
        );
        add_submenu_page(
            'pivot_home',
            'Offres sur une catégorie',
            'Offres sur une catégorie',
            'edit_posts',
            'category_offers',
            fn() => $this::categoryOffersRender(),
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
        $error = null;
        try {
            $offres = $wpRepository->getAllOffresShorts();
        } catch (InvalidArgumentException $e) {
            $offres = [];
            $error = $e->getMessage();
        }
        $offres = PostUtils::sortOffresByName($offres);
        array_map(fn($offer) => $offer->urlPivot = RouterPivot::getRouteOfferToPivotSite($offer->codeCgt), $offres);
        Twig::rendPage(
            '@VisitTail/admin/offers_list.html.twig',
            [
                'offers' => $offres,
                'error' => $error,
            ]
        );
    }

    private static function categoryFiltersRender(): void
    {
        if (!$catID = (int)$_GET['catID']) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Vous devez passer par une catégorie pour accéder à cette page',
                ]
            );

            return;
        }
        $category = get_category($catID);
        $categoryUrl = get_category_link($category);
        wp_enqueue_script(
            'vue-admin-filters-js',
            get_template_directory_uri().'/assets/js/dist/js/categoryFilters.js',
            [],
            wp_get_theme()->get('Version'),
        );

        wp_enqueue_style(
            'vue-admin-css',
            get_template_directory_uri().'/assets/js/dist/css/AxiosInstance.css',
            [],
            wp_get_theme()->get('Version'),
        );

        $url = admin_url('admin.php?page=pivot_filtres');

        Twig::rendPage(
            '@VisitTail/admin/category_filters.html.twig',
            [
                'urlAdmin' => $url,
                'categoryUrl' => $categoryUrl,
                'category' => $category,
                'catId' => $catID,
            ]
        );
    }

    private static function categoryOffersRender(): void
    {
        if (!$catID = (int)$_GET['catID']) {
            Twig::rendPage(
                '@VisitTail/admin/error.html.twig',
                [
                    'message' => 'Vous devez passer par une catégorie pour accéder à cette page',
                ]
            );

            return;
        }
        $category = get_category($catID);
        $categoryUrl = get_category_link($category);

        wp_enqueue_script(
            'vue-admin-offers-js',
            get_template_directory_uri().'/assets/js/dist/js/categoryOffers.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
        wp_enqueue_style(
            'vue-admin-css',
            get_template_directory_uri().'/assets/js/dist/css/AxiosInstance.css',
            [],
            wp_get_theme()->get('Version'),
        );

        Twig::rendPage(
            '@VisitTail/admin/category_offers.html.twig',
            [
                'category' => $category,
                'categoryUrl' => $categoryUrl,
                'catId' => $catID,
            ]
        );
    }
}
