<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

get_header();

global $post;

$wpRepository = new WpRepository();

$image = PostUtils::getImage($post);
$tags = $wpRepository->getTags($post->ID);

$recommandations = $wpRepository->recommandationsByPost($post);
$recommandations = array_slice($recommandations, 0, 3);

$content = get_the_content(null, null, $post);
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

$translator = LocaleHelper::iniTranslator();
$nameBack = $translator->trans('menu.home');

Twig::rendPage(
    '@VisitTail/page_select_language.html.twig',
    [
        'title' => $post->post_title,
        'post' => $post,
        'excerpt' => $post->post_excerpt,
        'tags' => $tags,
        'image' => $image,
        'icone' => null,
        'recommandations' => $recommandations,
        'bgCat' => '',
        'urlBack' => '/',
        'categoryName' => '',
        'nameBack' => 'Home',
        'content' => $content,
    ]
);
get_footer();