<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entity\TypeOffre;
use AcSort;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

get_header();

$cat_ID = get_queried_object_id();
$category = get_category($cat_ID);
$categoryName = single_cat_title('', false);

$wpRepository = new WpRepository();
$translator = LocaleHelper::iniTranslator();
$language = LocaleHelper::getSelectedLanguage();

$urlBack = '/'.$language;
$nameBack = $translator->trans('menu.home');

$posts = $wpRepository->getPostsByCatId($cat_ID);

$category_order = get_term_meta($cat_ID, CategoryMetaBox::KEY_NAME_ORDER, true);
$postUtils = new PostUtils();
$posts = $postUtils->convertPostsToArray($posts);
if ('manual' === $category_order) {
    $posts = AcSort::getSortedItems($cat_ID, $posts);
}
$icone = $wpRepository->categoryIcone($category);
$bgcat = $wpRepository->categoryBgColor($category);
$image = $wpRepository->categoryImage($category);

$children = $wpRepository->getChildrenOfCategory($category->cat_ID);


Twig::rendPage(
    '@VisitTail/pratique.html.twig',
    [
        'name' => $categoryName,
        'excerpt' => $category->description,
        'image' => $image,
        'bgCat' => $bgcat,
        'icone' => $icone,
        'category' => $category,
        'urlBack' => $urlBack,
        'children' => $children,
        'filtres' => [],
        'filterSelected' => null,
        'nameBack' => $nameBack,
        'categoryName' => $categoryName,
        'offres' => $posts,
        'bgcat' => $bgcat,
        'countArticles' => count($posts),
    ]
);
get_footer();