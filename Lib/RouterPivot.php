<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entity\TypeOffre;
use VisitMarche\ThemeTail\Entity\CommonItem;

/**
 * Ajouts des routes pour les offres
 * https://roots.io/routing-wp-requests/
 * https://developer.wordpress.org/reference/functions/add_rewrite_rule/#user-contributed-notes
 * Class Router.
 */
class RouterPivot
{
    public const PARAM_OFFRE = 'codeoffre';
    public const OFFRE_URL = 'offre';
    public const PARAM_FILTRE = 'filtre';
    public const PARAM_FILTRE_TYPE = 'filtretype';

    public function __construct()
    {
        $this->addRouteOffre();
    }

    public static function getUrlSite(): string
    {
        global $wp;

        return home_url();
    }

    public static function getCurrentUrl(): string
    {
        global $wp;

        return home_url($wp->request);
    }

    /**
     * @param CommonItem[] $items
     * @param int $categoryId
     * @param string $language
     * @return CommonItem[]
     */
    public static function setLinkOnCommonItems(array $items, int $categoryId, string $language): void
    {
        $urlBase = get_category_link(get_category($categoryId)).self::OFFRE_URL.'/';

        array_map(
            function ($item) use ($categoryId, $language, $urlBase) {
                if ($item->type == 'post') {
                    $item->url = get_permalink($item->id);
                } else {
                    $item->url = $urlBase.$item->id;
                }
            },
            $items
        );

    }

    public static function getUrlOffre(int $categoryId, string $codeCgt): string
    {
        return get_category_link(get_category($categoryId)).self::OFFRE_URL.'/'.$codeCgt;
    }

    /**
     * @param FilterStd[] $filtres
     * @return TypeOffre[]
     */
    public static function setRoutesToFilters(array $filtres, int $categoryId): array
    {
        $urlBase = get_category_link(get_category($categoryId));
        foreach ($filtres as $filtre) {
            if ($filtre->type == FilterStd::TYPE_PIVOT) {
                $filtre->url = $urlBase.'?filtre='.$filtre->urn;
            } else {
                $filtre->url = get_category_link(get_category($filtre->id));
            }
        }

        return $filtres;
    }

    public static function getRouteOfferToPivotSite(string $codeCgt): string
    {
        return 'https://pivotgest.tourismewallonie.be/PivotGest-4.0.0/detail.xhtml?codeCgt='.$codeCgt;
    }

    public function addRouteOffre(): void
    {
        //Setup a rule
        add_action(
            'init',
            function () {
                $taxonomy = get_taxonomy('category');
                $categoryBase = $taxonomy->rewrite['slug'];
                //^= depart, $ fin string, + one or more, * zero or more, ? zero or one, () capture
                // [^/]* => veut dire tout sauf /
                //https://regex101.com/r/pnR7x3/1
                //https://stackoverflow.com/questions/67060063/im-trying-to-capture-data-in-a-web-url-with-regex
                add_rewrite_rule(
                    '^'.$categoryBase.'/(?:([a-zA-Z0-9_-]+)/){1,3}offre/([a-zA-Z0-9-]+)[/]?$',
                    //'^'.$categoryBase.'/(?:([a-zA-Z0-9_-]+)/){1,3}offre/(\d+)/?$',
                    'index.php?category_name=$matches[1]&'.self::PARAM_OFFRE.'=$matches[2]',
                    'top'
                );
            }
        );
        //Whitelist the query param
        add_filter(
            'query_vars',
            function ($query_vars) {
                $query_vars[] = self::PARAM_OFFRE;

                return $query_vars;
            }
        );
        //Add a handler to send it off to a template file
        add_action(
            'template_include',
            function ($template) {
                global $wp_query;
                if (is_admin() || !$wp_query->is_main_query()) {
                    return $template;
                }
                if (false === get_query_var(self::PARAM_OFFRE) ||
                    '' === get_query_var(self::PARAM_OFFRE)) {
                    return $template;
                }

                return get_template_directory().'/single-offre.php';
            }
        );
    }

    public function custom_rewrite_tag(): void
    {
        add_rewrite_tag('%offre%', '([^&]+)'); //utilite?
    }
}
