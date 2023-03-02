<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entity\TypeOffre;
use AcSort;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
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

$parent = $wpRepository->getParentCategory($cat_ID);

$urlBack = '/'.$language;
$nameBack = $translator->trans('menu.home');

if ($parent) {
    $urlBack = get_category_link($parent->term_id);
    $nameBack = $parent->name;
}

$posts = $wpRepository->getPostsByCatId($cat_ID);

$category_order = get_term_meta($cat_ID, CategoryMetaBox::KEY_NAME_ORDER, true);
if ('manual' === $category_order) {
    $posts = AcSort::getSortedItems($cat_ID, $posts);
}
$icone = $wpRepository->categoryIcone($category);
$bgcat = $wpRepository->categoryBgColor($category);
$image = $wpRepository->categoryImage($category);
$video = $wpRepository->categoryVideo($category);
dump($video);
$children = $wpRepository->getChildrenOfCategory($category->cat_ID);
$offres = [];

$request = Request::createFromGlobals();
$filterSelected = $request->get(RouterPivot::PARAM_FILTRE, null);

if ($filterSelected) {
    $filterSelected = htmlentities($filterSelected);
    $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
    $filtres = $typeOffreRepository->findByUrn($filterSelected);
    if ([] !== $filtres) {
        $filtres = [$filtres[0]];
        $categoryName = $filtres[0]->name;
    }
} else {
    $filtres = $wpRepository->getCategoryFilters($cat_ID);
}

if ([] !== $filtres) {
    $filtres = RouterPivot::setRoutesToFilters($filtres, $cat_ID);

    try {
        $offres = $wpRepository->getOffres($filtres);
    } catch (InvalidArgumentException|\Exception $e) {
        dump($e->getMessage());
    }
    if (count($filtres) > 1) {
        $labelAll = $translator->trans('filter.all');
        $filtreTout = new TypeOffre($labelAll, 0, 0, "ALL", "", "Type", null);
        $filtreTout->id = 0;
        $filtres = [$filtreTout, ...$filtres];
    }
    PostUtils::setLinkOnOffres($offres, $cat_ID, $language);
}
//fusion offres et articles
$postUtils = new PostUtils();
$posts = $postUtils->convertPostsToArray($posts);

$offres = $postUtils->convertOffresToArray($offres, $cat_ID, $language);
$offres = [...$posts, ...$offres];

Twig::rendPage(
    '@VisitTail/category.html.twig',
    [
        'name' => $categoryName,
        'excerpt' => $category->description,
        'image' => $image,
        'video' => $video,
        'bgCat' => $bgcat,
        'icone' => $icone,
        'category' => $category,
        'urlBack' => $urlBack,
        'children' => $children,
        'filtres' => $filtres,
        'filterSelected' => $filterSelected,
        'nameBack' => $nameBack,
        'categoryName' => $categoryName,
        'offres' => $offres,
        'bgcat' => $bgcat,
        'countArticles' => count($offres),
    ]
);
get_footer();