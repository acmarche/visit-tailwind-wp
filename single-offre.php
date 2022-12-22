<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\Entities\Offre\Offre;
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
    if (count($offre->gpxs) === 0) {
        AssetsLoad::enqueueLeaflet();
    }
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
if (count($offre->gpxs) > 0) {
    $gpxViewer = new GpxViewer();
    $gpx = $offre->gpxs[0];
    $gpxMap = $gpxViewer->renderWithPlugin($offre, $gpx);
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
    ]
);
get_footer();