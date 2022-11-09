<?php

namespace VisitMarche\ThemeTail\Inc;

//@see https://www.whitehouse.gov/
//@see https://fr.wordpress.org/plugins/opengraph/
use VisitMarche\ThemeTail\Lib\Twig;

class OpenGraph
{
    public function __construct()
    {
        // add_filter('language_attributes', 'opengraph_add_prefix');
        //  add_filter('wp', 'opengraph_default_metadata');
        add_action('wp_head', function (): void {
            $this::ogMetaInfo();
        });
    }

    private static function ogMetaInfo()
    {
        $metas = Seo::assignMetaInfo(false);
        Twig::rendPage('@VisitTail/pub/_og.html.twig', [
            'title' => $metas['title'],
            'image' => $metas['image'],
            'content' => $metas['description'],
        ]);
    }

}
