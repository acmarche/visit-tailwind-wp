<?php

namespace VisitMarche\ThemeTail\Inc;

class ShortCodes
{
    public function __construct()
    {
        // add_action('init', fn() => $this->registerShortcodes());
    }

    public function registerShortcodes(): void
    {
        // add_shortcode('gpx_viewer', fn($args): string => (new self())->doSomething($args));
    }

    public function doSomething($args)
    {

    }

}
