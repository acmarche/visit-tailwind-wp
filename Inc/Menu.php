<?php

namespace VisitMarche\ThemeTail\Inc;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use VisitMarche\ThemeTail\Lib\Cache;
use VisitMarche\ThemeTail\Lib\IconeEnum;
use VisitMarche\ThemeTail\Lib\LocaleHelper;

class Menu
{
    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = Cache::instance('menu');
    }

    /**
     * @return \WP_Term[]
     */
    public function getIcones(): array
    {
        $language = LocaleHelper::getSelectedLanguage();

        return $this->cache->get(Cache::ICONES_NAME.$language, function ($item) {
            $icones = [
                'arts' => get_category_by_slug('arts'),
                'balades' => get_category_by_slug('balades'),
                'fetes' => get_category_by_slug('fetes'),
                'gourmandises' => get_category_by_slug('gourmandises'),
                'patrimoine' => get_category_by_slug('patrimoine'),
            ];

            foreach ($icones as $key => $icone) {
                $icone->url = get_category_link($icone);
                $icone->colorOver = $this->hoverColor($key);
                $icone->imageWhite = IconeEnum::iconeWhite($icone->slug);
            }

            return $icones;
        });
    }

    private function hoverColor(string $key): string
    {
        return match ($key) {
            'arts' => 'hover:bg-art',
            'balades' => 'hover:bg-walk',
            'fetes' => 'hover:bg-party',
            'gourmandises' => 'hover:bg-delicacy',
            default => 'hover:bg-patrimony',
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getMenuTop()
    {
        $language = LocaleHelper::getSelectedLanguage();

        return $this->cache->get(Cache::MENU_NAME.$language, function ($item) {
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

            return $menu;
        });
    }
}
