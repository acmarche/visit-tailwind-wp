<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

global $post;

$wpRepository = new WpRepository();

$slugs = explode('/', get_query_var('category_name'));
$image = PostUtils::getImage($post);
$currentCategory = get_category_by_slug($slugs[array_key_last($slugs)]);
$urlBack = get_category_link($currentCategory);

$bgcat = $wpRepository->categoryBgColor($currentCategory);
$tags = PostUtils::tagsPost($post);

$recommandations = $wpRepository->recommandationsByPost($post);
$recommandations = array_slice($recommandations, 0, 3);

$content = get_the_content(null, null, $post);
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

$nameBack = $currentCategory->name;

if (preg_match('#gpx_viewer#', $post->post_content)) {
   AssetsLoad::enqueueLeaflet();
   AssetsLoad::enqueueElevation();
}

get_header();
Twig::rendPage(
    '@VisitTail/article.html.twig',
    [
        'name' => $post->post_title,
        'post' => $post,
        'excerpt' => $post->post_excerpt,
        'tags' => $tags,
        'image' => $image,
        'icone' => null,
        'recommandations' => $recommandations,
        'bgCat' => $bgcat,
        'urlBack' => $urlBack,
        'categoryName' => $currentCategory->name,
        'nameBack' => $nameBack,
        'content' => $content,
    ]
);
get_footer();