<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\Twig;

$locale = LocaleHelper::getSelectedLanguage();
$localeSf = LocaleHelper::getCurrentLanguageSf();
?>
    <!doctype html>
<html lang="<?php echo $locale; ?>">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="https://gmpg.org/xfn/11">
        <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri() ?>/assets/images/favicon.png"/>
        <?php wp_head();
        Twig::rendPage(
            '@VisitTail/pub/_pubs.html.twig',
            [

            ]
        );
        ?>
        <!--
        <script type="module">
            import 'https://flackr.github.io/scroll-timeline/dist/scroll-timeline.js';

            // if (typeof ScrollTimeline !== 'undefined') {
            const $header = document.querySelector("#sticky-parallax-header");
            const main = document.querySelector('main')
            if ($header) {
                // Fixate the header
                // @note: We use `position: fixed` instead of `position: sticky` here (see infobox why)
                $header.style.position = 'fixed';
                $header.style.top = 0;
                // Offset content
                main.style.paddingTop = '64vh';
                $header.animate({
                        backgroundPosition: ["50% 0", "50% 100%"],
                        //    backgroundColor: ['transparent', '#0b1584'],
                        height: ['64vh', '15vh'],
                        //      height: ['100rem', '10rem'],
                        //     fontSize: ['calc(4vw + 1em)', 'calc(1vw + 1em)'],
                    },
                    {
                        fill: "both",
                        timeline: new ScrollTimeline({
                            source: document.documentElement,
                        }),
                        rangeStart: '0',
                        rangeEnd: '58vh',
                    });
            }
            //}
        </script>
        -->
    </head>

<body <?php body_class(); ?> id="app" data-langwp="<?= $locale ?>" data-langsf="<?= $localeSf ?>">
    <?php
wp_body_open();
$menu = new Menu();
$items = $menu->getMenuTop();
$icons = $menu->getIcones();

Twig::rendPage(
    '@VisitTail/header/_header.html.twig',
    [
        'items' => $items,
        'icones' => $icons,
        'locale' => $locale,
    ]
);
