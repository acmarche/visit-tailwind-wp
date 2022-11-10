<?php

namespace VisitMarche\ThemeTail\Inc;

class AssetsLoad
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', fn() => $this->mainAssets());
        add_action('wp_enqueue_scripts', fn() => $this->leaflet());
        add_action('wp_enqueue_scripts', fn() => $this->leafletElevation());
        add_filter('script_loader_tag', fn($tag, $handle, $src) => $this->addAsModule($tag, $handle, $src), 10, 3);
        add_filter('script_loader_tag', fn($tag, $handle, $src) => $this->addDefer($tag, $handle, $src), 10, 3);
    }

    public function mainAssets(): void
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

    public function leaflet(): void
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

    public function leafletElevation(): void
    {
        wp_register_style(
            'visitmarche-leaflet-elevation-css',
            'https://unpkg.com/@raruto/leaflet-elevation/dist/leaflet-elevation.min.css',
            [],
            null
        );

        wp_register_script(
            'visitmarche-leaflet-ui-js',
            'https://unpkg.com/leaflet-ui@0.5.9/dist/leaflet-ui.js',
            [],
            null
        );

        wp_register_script(
            'visitmarche-leaflet-elevation-js',
            'https://unpkg.com/@raruto/leaflet-elevation/dist/leaflet-elevation.min.js',
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

    public static function enqueueLeaflet()
    {
        wp_enqueue_style('visitmarche-leaflet-css');
        wp_enqueue_script('visitmarche-leaflet-js');
    }

    public static function enqueueElevation()
    {
        wp_enqueue_style('visitmarche-leaflet-elevation-css');
        wp_enqueue_script('visitmarche-leaflet-ui-js');
        wp_enqueue_script('visitmarche-leaflet-elevation-js');
    }
}
