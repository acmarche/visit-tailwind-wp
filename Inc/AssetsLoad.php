<?php

namespace VisitMarche\ThemeTail\Inc;

class AssetsLoad
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', fn() => $this->visitmarcheAssets());
        if (!is_category() && !is_search() && !is_front_page()) {
            add_action('wp_enqueue_scripts', fn() => $this->visitmarcheLeaft());
        }
        add_filter('script_loader_tag', fn($tag, $handle, $src) => $this->addAsModule($tag, $handle, $src), 10, 3);
        add_filter('script_loader_tag', fn($tag, $handle, $src) => $this->addDefer($tag, $handle, $src), 10, 3);
    }

    public function visitmarcheAssets(): void
    {
        wp_enqueue_style(
            'visitmarche-css',
            get_template_directory_uri().'/assets/visit.css',
        );

        wp_enqueue_script(
            'menuMobile-js',
            get_template_directory_uri().'/assets/js/alpine/menuMobile.js',
            [],
            false,
            false
        );

        wp_enqueue_script(
            'searchXl-js',
            get_template_directory_uri().'/assets/js/alpine/searchXl.js',
            [],
            false,
            false
        );

        wp_enqueue_script(
            'refreshOffres-js',
            get_template_directory_uri().'/assets/js/alpine/refreshOffres.js',
            [],
            false,
            false
        );

        wp_enqueue_script(
            'share-js',
            get_template_directory_uri().'/assets/js/alpine/share.js',
            [],
            false,
            false
        );

        wp_enqueue_script(
            'alpine-js',
            '//unpkg.com/alpinejs',
            [],
            false,
            false
        );
        /*   wp_enqueue_script(
               'oljf-js',
               get_template_directory_uri().'/assets/js/dist/js/oljf.js',
               [],
               false,
               false
           );
           wp_enqueue_script(
               'titi-js',
               get_template_directory_uri().'/assets/js/titi.js',
               [],
               false,
               false
           );

        /*   wp_enqueue_style(
               'visitmarche-jf-style',
               get_template_directory_uri().'/assets/js/dist/css/oljf.css',
               [],
               wp_get_theme()->get('Version')
           );*/

    }

    public function visitmarcheLeaft(): void
    {
        wp_register_style(
            'visitmarche-leaflet-css',
            'https://unpkg.com/leaflet@latest/dist/leaflet.css',
            [],
            null
        );
        wp_register_script(
            'visitmarche-leaflet-js',
            'https://unpkg.com/leaflet@latest/dist/leaflet.js',
            [],
            null
        );
    }

    /**
     * Pour vue
     * @param $tag
     * @param $handle
     * @param $src
     * @return mixed|string
     */
    function addAsModule($tag, $handle, $src)
    {
        if (!in_array($handle, ['oljf-js', 'titi-js'])) {
            return $tag;
        }

        return '<script type="module" src="'.esc_url($src).'"></script>';
    }

    function addDefer($tag, $handle, $src)
    {
        if (!in_array($handle, ['alpine-js', 'menuMobile-js', 'searchXl-js', 'refreshOffres-js', 'share-js'])) {
            return $tag;
        }

        return '<script src="'.esc_url($src).'" defer></script>';
    }
}
