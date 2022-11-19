<?php

namespace VisitMarche\ThemeTail\Inc;

use SortLink;
use VisitMarche\ThemeTail\Lib\RouterPivot;

class AdminBar
{
    public function __construct()
    {
        add_action('admin_bar_menu', fn($wp_admin_bar) => $this->customize_my_wp_admin_bar($wp_admin_bar), 100);
    }

    public function customize_my_wp_admin_bar($wp_admin_bar): void
    {
        $codeCgt = get_query_var(RouterPivot::PARAM_OFFRE);
        if ($codeCgt) {
            $wp_admin_bar->add_menu(
                [
                    'id' => 'edit',
                    'title' => 'Modifier l\'offre',
                    'href' => 'https://pivotgest.tourismewallonie.be/PivotGest-4.0.0/detail.xhtml?codeCgt='.$codeCgt,
                ]
            );
        }
        if (is_category()) {
            $cat_ID = get_queried_object_id();
            $sortLink = SortLink::linkSortArticles($cat_ID);
            if ($sortLink) {
                $wp_admin_bar->add_menu(
                    [
                        'id' => 'sort',
                        'title' => 'Trier les articles',
                        'href' => $sortLink,
                    ]
                );
            }
        }
    }
}
