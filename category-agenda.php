<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

get_header();

$cat_ID = get_queried_object_id();
$category = get_category($cat_ID);

$language = LocaleHelper::getSelectedLanguage();
$pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

$wpRepository = new WpRepository();
$image = $wpRepository->categoryImage($category);
$filterSelected = $_GET[RouterPivot::PARAM_FILTRE] ?? null;
$nameBack = 'Home';
$categorName = $category->name;
if ($filterSelected) {
    $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
    $filtres = $typeOffreRepository->findByUrn($filterSelected);
    if ([] !== $filtres) {
        $nameBack = 'Agenda';
        $categorName = $category->name.' - '.$filtres[0]->labelByLanguage($language);
    }
}
try {
    $events = $pivotRepository->getEvents(true, [$filterSelected]);
    array_map(
        function ($event) use ($cat_ID, $language) {
            $event->url = RouterPivot::getUrlOffre($event, $cat_ID);
        },
        $events
    );
} catch (\Exception $e) {
    Twig::rendPage(
        '@VisitTail/errors/500.html.twig',
        [
            'title' => 'Page non chargée',
            'message' => 'Impossible de charger les évènements: '.$e->getMessage(),
        ]
    );
    get_footer();

    return;
}
foreach ($events as $event) {
    $event->locality = $event->getAdresse()->localite[0]->get('fr');
    $event->dateEvent = [
        'year' => $event->dateEnd->format('Y'),
        'month' => $event->dateEnd->format('m'),
        'day' => $event->dateEnd->format('d'),
    ];
}
Twig::rendPage(
    '@VisitTail/agenda.html.twig',
    [
        'events' => $events,
        'category' => $category,
        'title' => $category->name,
        'nameBack' => $nameBack,
        'categoryName' => $categorName,
        'image' => $image,
        'icone' => null,
    ]
);

get_footer();
