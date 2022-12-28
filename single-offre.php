<?php

namespace VisitMarche\ThemeTail;

use Exception;
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
} catch (Exception $e) {
    get_header();
    Twig::rend500Page();
    get_footer();

    return;
}

$latitude = $offre->getAdresse()->latitude ?? null;
$longitude = $offre->getAdresse()->longitude ?? null;
if ($latitude && $longitude) {
    AssetsLoad::enqueueLeaflet();
}
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
    $poi->url = RouterPivot::getUrlOffre($poi, $currentCategory->cat_ID);
    $poi->image = $poi->firstImage();
    $postUtils->tagsOffre($poi, $language, $urlcurrentCategory);
}
$gpxMap = $gpx = null;
$locations = [];
$gpxViewer = new GpxViewer();
if (count($offre->gpxs) > 0) {
    $gpx = $offre->gpxs[0];
    $gpxViewer->renderWithPlugin($offre, $gpx);
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
        'gpxMap' => $gpxMap,
        'gpx' => $gpx,
        'locations' => $locations,
    ]
);
get_footer();