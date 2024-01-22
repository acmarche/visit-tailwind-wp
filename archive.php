<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entity\TypeOffre;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
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

$icone = $wpRepository->categoryIcone($category);
$bgcat = $wpRepository->categoryBgColor($category);
$image = $wpRepository->categoryImage($category);
$video = $wpRepository->categoryVideo($category);

$children = $wpRepository->getChildrenOfCategory($category->cat_ID);

$request = Request::createFromGlobals();
$filterSelected = $request->get(RouterPivot::PARAM_FILTRE, 0);

if ($filterSelected) {
    $filterSelected = htmlentities($filterSelected);
    $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
    $filtres = $typeOffreRepository->findByUrn($filterSelected);
    if ([] !== $filtres) {
        $filtres = [$filtres[0]];
        $categoryName = $filtres[0]->name;
    }
    $filterSelected = $filtres[0]->id;
} else {
    $filtres = $wpRepository->getCategoryFilters($cat_ID);
}
//add all button
if (count($filtres) > 1) {
    $labelAll = $translator->trans('filter.all');
    $filtreTout = new TypeOffre($labelAll, 0, 0, "ALL", "", "Type", null);
    $filtreTout->id = 0;
    $filtres = [$filtreTout, ...$filtres];
}
if (!$filterSelected) {
    $filterSelected = 0;
}
try {
    $offres = $wpRepository->findAllArticlesForCategory($category->cat_ID, $filterSelected);
} catch (NonUniqueResultException|InvalidArgumentException $e) {
    $offres = [];
}

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