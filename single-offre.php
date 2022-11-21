<?php

namespace VisitMarche\ThemeTail;

use AcMarche\Pivot\Entities\Offre\Offre;
use Exception;
use VisitMarche\ThemeTail\Inc\AssetsLoad;
use VisitMarche\ThemeTail\Lib\GpxViewer;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
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
    $offre = $wpRepository->getOffreByCgtAndParse($codeCgt, Offre::class);
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
$tags = [];
foreach ($offre->tags as $tag) {
    $tags[] = [
        'name' => $tag->urnDefinition->labelByLanguage($language),
        'url' => $urlcurrentCategory.'?'.RouterPivot::PARAM_FILTRE.'='.$tag->data->urn,
    ];
}

//todo heberg pas de categories
//$offre->categories;
$recommandations = $wpRepository->recommandationsByOffre($offre, $currentCategory, $language);

foreach ($offre->pois as $poi) {
    $poi->url = RouterPivot::getUrlOffre($poi, $currentCategory->cat_ID);
    $poi->title = $poi->name;
    $poi->image = $poi->firstImage();
}

$gpxMap = null;
if (count($offre->gpxs) > 0) {
    $gpxViewer = new GpxViewer();
    $gpxMap = $gpxViewer->renderWithPlugin($offre->codeCgt, $offre->gpxs[0]->url);
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
        'tags' => $tags,
        'image' => $offre->firstImage(),
        'icone' => null,
        'recommandations' => $recommandations,
        'urlBack' => $urlcurrentCategory,
        'categoryName' => $currentCategory->name,
        'nameBack' => $currentCategory->name,
        'specs' => $specs,
        'gpxMap' => $gpxMap,
    ]
);
get_footer();