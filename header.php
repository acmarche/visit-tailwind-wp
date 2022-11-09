<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\Twig;

$locale = LocaleHelper::getSelectedLanguage();
?>
    <!doctype html>
<html lang="<?php echo $locale; ?>">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="https://gmpg.org/xfn/11">
        <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri() ?>/assets/images/favicon.png"/>
        <?php wp_head(); ?>
    </head>

<body <?php body_class(); ?>>
    <?php wp_body_open();
$menu = new Menu();
$items = $menu->getMenuTop();

Twig::rendPage(
    '@VisitTail/header/_header.html.twig',
    [
        'items' => $items,
    ]
);
