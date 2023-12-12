<?php

namespace VisitMarche\ThemeTail\Inc;

class AssetsLoad
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', fn() => $this->mainAssets());
        add_action('wp_enqueue_scripts', fn() => $this->leaflet());
        add_action('wp_enqueue_scripts', fn() => $this->leafletElevation());
        add_action('wp_enqueue_scripts', fn() => $this->slider());
        add_filter(
            'script_loader_tag',
            fn($tag, $handle, $src) => $this->addScriptAsModule($tag, $handle, $src),
            10,
            3
        );
        add_filter(
            'script_loader_tag',
            fn($tag, $handle, $src) => $this->addScriptAsModulePreload($tag, $handle, $src),
            10,
            3
        );
        add_filter(
            'style_loader_tag',
            fn($tag, $handle, $src) => $this->addLinkAsModulePreload($tag, $handle, $src),
            10,
            3
        );
        add_filter(
            'script_loader_tag',
            fn($tag, $handle, $src) => $this->addScriptDefer($tag, $handle, $src),
            10,
            3
        );
    }

    public function mainAssets(): void
    {
        wp_enqueue_style(
            'visitmarche-css',
            get_template_directory_uri().'/assets/visit.css',
        );

        wp_enqueue_style(
            'visitmarche-perso-css',
            get_template_directory_uri().'/assets/css/perso.css',
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
        wp_register_script(
            'visitmarche-chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.1.1/chart.min.js',
            [],
            null
        );

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

    public function slider(): void
    {
        wp_register_style(
            'visitmarche-slider-css',
            'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css',
            [],
            null
        );
        wp_register_script(
            'visitmarche-slider-js',
            'https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js',
            [],
            null
        );
    }

    function addScriptAsModule(string $tag, string $handle, string $src): string
    {
        if (!in_array($handle, ['vue-admin-filters-js'])) {
            return $tag;
        }

        return '<script type="module" crossorigin src="'.esc_url($src).'"></script>';
    }

    function addScriptAsModulePreload(string $tag, string $handle, string $src): string
    {
        if (!in_array($handle, ['vue-admin-filters2-js'])) {
            return $tag;
        }

        return '<script rel="modulepreload" crossorigin src="'.esc_url($src).'"></script>';
    }

    function addLinkAsModulePreload(string $tag, string $handle, string $src): string
    {
        if (!in_array($handle, ['vue-admin-filters2-js'])) {
            return $tag;
        }

        return '<link rel="modulepreload" crossorigin href="'.esc_url($src).'"></script>';
    }

    function addScriptDefer(string $tag, string $handle, string $src): string
    {
        if (!in_array($handle, ['alpine-js', 'menuMobile-js', 'searchXl-js', 'refreshOffres-js', 'share-js'])) {
            return $tag;
        }

        return '<script src="'.esc_url($src).'" defer></script>';
    }

    public static function enqueueLeaflet(): void
    {
        //todo test this
        //wp_add_inline_script();
        wp_enqueue_style('visitmarche-leaflet-css');
        wp_enqueue_script('visitmarche-leaflet-js');
    }

    public static function enqueueElevation(): void
    {
        wp_enqueue_style('visitmarche-leaflet-elevation-css');
        wp_enqueue_script('visitmarche-leaflet-ui-js');
        wp_enqueue_script('visitmarche-leaflet-elevation-js');
    }

    public static function enqueueSlider(): void
    {
        wp_enqueue_style('visitmarche-slider-css');
        wp_enqueue_script('visitmarche-slider-js');
    }
}
