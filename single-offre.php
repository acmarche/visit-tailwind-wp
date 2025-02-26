<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\Event\EventUtils;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Lib\GpxViewer;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;
use VisitMarche\ThemeTail\Lib\WpRepository;

$codeCgt = get_query_var(RouterPivot::PARAM_OFFRE);

$wpRepository = new WpRepository();

if (!str_contains($codeCgt, "-")) {
    get_header();
    Twig::rend404Page();
    get_footer();

    return;
}

try {
    $offre = $wpRepository->getOffreByCgtAndParse($codeCgt);
} catch (\Exception|InvalidArgumentException $e) {
    get_header();
    Twig::rend500Page($e->getMessage());
    get_footer();

    return;
}
if (!$offre) {
    get_header();
    Twig::rend404Page();
    get_footer();

    return;
}
if (count($offre->datesEvent) > 0) {
    if ($eventOk = EventUtils::isEventObsolete($offre)) {
        $offre = $eventOk;
    }
}

$latitude = $offre->getAdresse()->latitude ?? null;
$longitude = $offre->getAdresse()->longitude ?? null;
if ($latitude && $longitude) {
    AssetsLoad::enqueueLeaflet();
}
AssetsLoad::enqueueSlider();
get_header();
if (!$currentCategory = get_category_by_slug(get_query_var('category_name'))) {
    $currentCategory = get_category_by_slug('non-classifiee');
}
$urlcurrentCategory = get_category_link($currentCategory);
$language = LocaleHelper::getSelectedLanguage();

$postUtils = new PostUtils();
$postUtils->tagsOffre($offre, $language, $urlcurrentCategory);

//todo heberg pas de categories
//$offre->categories;
$recommandations = $wpRepository->recommandationsByOffre($offre, $currentCategory, $language);

foreach ($offre->pois as $poi) {
    $poi->url = RouterPivot::getUrlOffre($currentCategory->cat_ID, $poi->codeCgt);
    $poi->image = $poi->firstImage();
    $postUtils->tagsOffre($poi, $language, $urlcurrentCategory);
}
$gpx = null;
$locations = [];

if (count($offre->gpxs) > 0) {
    $gpx = $offre->gpxs[0];
    $gpxViewer = new GpxViewer();
    $gpxViewer->renderWithPlugin($gpx);
    if ($gpx && isset($gpx->data['locations'])) {
        foreach ($gpx->data['locations'] as $location) {
            $locations[] = [$location['latitude'], $location['longitude']];
        }
    }
}

$specs = $wpRepository->groupSpecifications($offre);
Twig::rendPage(
    '@VisitTail/offre.html.twig',
    [
        'offre' => $offre,
        'name' => $offre->nameByLanguage($language),
        'latitude' => $latitude,
        'longitude' => $longitude,
        'excerpt' => null,
        'tags' => $offre->tagsFormatted,
        'image' => $offre->firstImage(),
        'icone' => null,
        'recommandations' => $recommandations,
        'urlBack' => $urlcurrentCategory,
        'categoryName' => $currentCategory->name,
        'nameBack' => $currentCategory->name,
        'specs' => $specs,
        'gpx' => $gpx,
        'locations' => $locations,
    ]
);
get_footer();