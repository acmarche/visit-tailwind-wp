<?php

namespace VisitMarche\ThemeTail\Inc;

use VisitMarche\ThemeTail\Lib\IconeEnum;
use VisitMarche\ThemeTail\Lib\LocaleHelper;

class Menu
{
    public const MENU_NAME = 'menu-top';
    public const ICONES_NAME = 'icones-home22';
    public const EXPIRATION = 86400;

    /**
     * @return \WP_Term[]
     */
    public function getIcones(): array
    {
        $language = LocaleHelper::getSelectedLanguage();
        if ($items = get_transient(self::ICONES_NAME.$language)) {
            return $items;
        }
        $icones = [
            'arts' => get_category_by_slug('arts'),
            'balades' => get_category_by_slug('balades'),
            'fetes' => get_category_by_slug('fetes'),
            'gourmandises' => get_category_by_slug('gourmandises'),
            'patrimoine' => get_category_by_slug('patrimoine'),
        ];

        array_map(
            function ($icone) {
                if ($icone) {
                    $icone->url = get_category_link($icone);
                    $icone->imageWhite = IconeEnum::iconeWhite($icone->slug);
                }

                return $icone;
            },
            $icones
        );

        set_transient(self::ICONES_NAME.$language, $icones, self::EXPIRATION);

        return $icones;
    }

    public function getMenuTop(): array
    {
        $language = LocaleHelper::getSelectedLanguage();
        if ($items = get_transient(self::MENU_NAME.$language)) {
            return $items;
        }

        $menu = [
            'sorganiser' => get_category_by_slug('sorganiser'),
            'sejourner' => get_category_by_slug('sejourner'),
            'savourer' => get_category_by_slug('savourer'),
            'bouger' => get_category_by_slug('bouger'),
            'mice' => get_category_by_slug('mice'),
            'inspirations' => get_category_by_slug('inspirations'),
            'pratique' => get_category_by_slug('pratique'),
            'agenda' => get_category_by_slug('agenda'),
        ];
        $menu = array_map(
            function ($item) {
                $item->url = get_category_link($item);

                return $item;
            },
            $menu
        );

        $idDecouvrir = apply_filters('wpml_object_id', Theme::PAGE_DECOUVRIR, 'post', true);

        $decouvrir = get_post($idDecouvrir);
        $decouvrir->name = $decouvrir->post_title;
        $decouvrir->url = get_permalink($decouvrir);
        $menu['decouvrir'] = $decouvrir;

        set_transient(self::MENU_NAME.$language, $menu, self::EXPIRATION);

        return $menu;
    }
}
