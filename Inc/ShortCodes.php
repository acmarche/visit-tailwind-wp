<?php

namespace VisitMarche\ThemeTail\Inc;

use VisitMarche\ThemeTail\Lib\GpxViewer;

class ShortCodes
{
    public function __construct()
    {
        add_action('init', fn() => $this->registerShortcodes());
    }

    public function registerShortcodes(): void
    {
        add_shortcode('gpx_viewer', fn($args): string => (new self())->gpxViewer($args));
    }

    public function gpxViewer($args): string
    {
        $gpxViewer = new GpxViewer();

        return $gpxViewer->render($args);
    }

}
