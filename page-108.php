<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\Twig;

get_header();

global $post;
if(!$post){
    return '';
}
$image = PostUtils::getImage($post);
$tags = PostUtils::tagsPost($post);

$content = get_the_content(null, null, $post);
$content = apply_filters('the_content', $content);
$content = str_replace(']]>', ']]&gt;', $content);

Twig::rendPage(
    '@VisitTail/page_select_language.html.twig',
    [
        'name' => $post->post_title,
        'post' => $post,
        'excerpt' => $post->post_excerpt,
        'tags' => $tags,
        'image' => $image,
        'icone' => null,
        'recommandations' => [],
        'bgCat' => '',
        'urlBack' => '/',
        'categoryName' => '',
        'nameBack' => 'Home',
        'content' => $content,
    ]
);
get_footer();