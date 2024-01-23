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
     * @param CommonItem[] $offres
     * @param int $categoryId
     * @param string $language
     * @return void
     */
    public static function setLinkOnOffres(array $offres, int $categoryId, string $language): void
    {
        array_map(
            function ($offre) use ($categoryId, $language) {
                $offre->url = RouterPivot::getUrlOffre($offre, $categoryId);
            },
            $offres
        );
    }

    public static function getUrlOffre(CommonItem $offre, int $categoryId): string
    {
        return get_category_link($categoryId).self::OFFRE_URL.'/'.$offre->id;
    }

    /**
     * @param FilterStd[] $filtres
     * @return TypeOffre[]
     */
    public static function setRoutesToFilters(array $filtres, int $categoryId): array
    {
        $urlBase = get_category_link(get_category($categoryId)).'?filtre=';
        foreach ($filtres as $filtre) {
            if ($filtre->type == FilterStd::TYPE_PIVOT) {
                $filtre->url = $urlBase.$filtre->urn;
            } else {
                $filtre->url = $urlBase.$filtre->id;
            }
        }

        return $filtres;
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
