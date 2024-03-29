<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entity\TypeOffre;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpFilterRepository;
use VisitMarche\ThemeTail\Lib\WpRepository;
use WP_Term;

get_header();

$cat_ID = get_queried_object_id();
$category = get_category($cat_ID);

$language = LocaleHelper::getSelectedLanguage();
$translator = LocaleHelper::iniTranslator();

$wpRepository = new WpRepository();
$image = null;
if ($category instanceof WP_Term) {
    $image = $wpRepository->categoryImage($category);
}
$filterSelected = $_GET[RouterPivot::PARAM_FILTRE] ?? null;
$nameBack = $translator->trans('menu.home');
$categorName = $category->name;
$filtre = null;
if ($filterSelected) {
    $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
    try {
        $filtre = $typeOffreRepository->findOneByUrn($filterSelected);
        if ($filtre instanceof TypeOffre) {
            $nameBack = $translator->trans('agenda.title');
            $categorName = $category->name.' - '.$filtre->labelByLanguage($language);
        }
    } catch (NonUniqueResultException $e) {

    }
}
try {
    $events = $wpRepository->getEvents($filtre?->id);

    array_map(
        function ($event) use ($cat_ID, $language) {
            $event->url = RouterPivot::getUrlOffre($cat_ID, $event->codeCgt);
        },
        $events
    );
} catch (\Exception|InvalidArgumentException $e) {
    Twig::rend500Page($e->getMessage());
    get_footer();

    return;
}
$wpFilterRepository = new WpFilterRepository();
$filters = $wpFilterRepository->getCategoryFilters($cat_ID, true, true);
RouterPivot::setRoutesToFilters($filters, $cat_ID);

if (count($filters) > 1) {
    $labelAll = $translator->trans('filter.all');
    $filtreTout = new TypeOffre($labelAll, 0, 0, "ALL", "", "Type", null);
    $filtreTout->id = 0;
    $filtres = [$filtreTout, ...$filters];
}
Twig::rendPage(
    '@VisitTail/agenda.html.twig',
    [
        'events' => $events,
        'category' => $category,
        'name' => $category->name,
        'nameBack' => $nameBack,
        'categoryName' => $categorName,
        'image' => $image,
        'filters' => $filtres,
        'icone' => null,
    ]
);

get_footer();
