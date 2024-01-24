<?php

namespace VisitMarche\ThemeTail\Inc;

use SortLink;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use WP_Admin_Bar;

class AdminBar
{
    public function __construct()
    {
        add_action('admin_bar_menu', fn($wp_admin_bar) => $this->customize_my_wp_admin_bar($wp_admin_bar), 100);
    }

    public function customize_my_wp_admin_bar(WP_Admin_Bar $wp_admin_bar): void
    {
        $codeCgt = get_query_var(RouterPivot::PARAM_OFFRE);

        if ($codeCgt) {
            $wp_admin_bar->add_menu(
                [
                    'id' => 'edit',
                    'title' => 'Modifier l\'offre',
                    'href' => RouterPivot::getRouteOfferToPivotSite($codeCgt),
                ]
            );
        }
        if (is_category()) {
            $cat_ID = get_queried_object_id();
            $sortLink = SortLink::linkSortArticles($cat_ID);
            if ($sortLink) {
                $wp_admin_bar->add_menu(
                    [
                        'id' => 'pivot_sort',
                        'title' => 'Trier les articles',
                        'href' => $sortLink,
                    ]
                );
            }
            $wp_admin_bar->add_menu(
                [
                    'id' => 'pivot_category_filters',
                    'title' => 'Filtres Pivot',
                    'href' => '/wp-admin/admin.php?page=category_filters&catID='.$cat_ID,
                ]
            );
            $wp_admin_bar->add_menu(
                [
                    'id' => 'pivot_category_offers',
                    'title' => 'Offres Pivot',
                    'href' => '/wp-admin/admin.php?page=category_offers&catID='.$cat_ID,
                ]
            );
        }
    }
}
