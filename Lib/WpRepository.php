<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Api\QueryDetailEnum;
use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Communication\Adresse;
use AcMarche\Pivot\Entities\Label;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entities\Specification\Gpx;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Event\EventEnum;
use AcMarche\Pivot\Utils\CacheUtils;
use AcSort;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use VisitMarche\ThemeTail\Entity\CommonItem;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Inc\Theme;
use WP_Post;
use WP_Query;
use WP_Term;

class WpRepository
{
    public const PIVOT_REFRUBRIQUE = 'pivot_refrubrique';
    public const PIVOT_REFOFFERS = 'pivot_ref_offers';

    public function getParentCategory(int $cat_ID): array|WP_Term|\WP_Error|null
    {
        $category = get_category($cat_ID);

        if ($category) {
            if ($category->parent < 1) {
                return null;
            }

            return get_category($category->parent);
        }

        return null;
    }

    public function getIntro(): array|string
    {
        $intro = '<p>Intro vide</p>';
        $introId = apply_filters('wpml_object_id', Theme::PAGE_INTRO, 'page', true);
        $pageIntro = get_post($introId);

        if ($pageIntro) {
            $intro = get_the_content(null, null, $pageIntro);
            $intro = apply_filters('the_content', $intro);
            $intro = str_replace(']]>', ']]&gt;', $intro);
            $intro = str_replace('<p>', '', $intro);
            $intro = str_replace('</p>', '', $intro);
        }

        return $intro;
    }

    public function getIdeas(): array
    {
        $ideas = [];
        if ($term = get_category_by_slug('en-famille')) {
            $ideas[] = $this->addIdea($term, 'Famille.jpg');
        }
        if ($term = get_category_by_slug('en-solo-ou-duo')) {
            $ideas[] = $this->addIdea($term, 'Duo-WBT.jpg');
        }
        if ($term = get_category_by_slug('entre-amis')) {
            $ideas[] = $this->addIdea($term, 'Friends.jpg');
        }
        if ($term = get_category_by_slug('en-groupe')) {
            $ideas[] = $this->addIdea($term, 'Groupe.jpg');
        }
        if ($term = get_category_by_slug('personnes-porteuses-dun-handicap')) {
            $ideas[] = $this->addIdea($term, 'PMR.jpg');
        }
        if ($term = get_category_by_slug('tourisme-participatif-2')) {
            $ideas[] = $this->addIdea($term, 'Tourismeparticipatif.jpg');
        }

        return $ideas;
    }

    private function addIdea(WP_Term $term, string $imageName): array
    {
        return [
            'img' => $imageName,
            'description' => $term->name,
            'url' => get_category_link($term),
        ];
    }

    /**
     * @return array|\WP_Term[]
     */
    public function getCategoriesFromWp(): array
    {
        $args = [
            'type' => 'post',
            'child_of' => 0,
            'parent' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'number' => '',
            'taxonomy' => 'category',
            'pad_counts' => true,
        ];

        return get_categories($args);
    }

    /**
     * Retourne les posts, les offres
     * @param int $currentCategoryId
     * @param int $filtreSelected
     * @param ?string $filtreType
     * @return CommonItem[]
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function findAllArticlesForCategory(int $currentCategoryId, int $filtreSelected, ?string $filtreType): array
    {
        $cache = Cache::instance('visit-wp');
        $key = 'allArticle-'.$currentCategoryId.'-'.$filtreSelected;
        if ($filtreType) {
            $key .= $filtreType;
        }
        $cacheKey = Cache::generateKey($key);

        return $cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($currentCategoryId, $filtreSelected, $filtreType) {
                $item->expiresAfter(CacheUtils::DURATION);
                $item->tag(CacheUtils::TAG);
                if ($filtreSelected) {
                    if ($filtreType == FilterStd::TYPE_WP) {
                        if ($category = get_category($filtreSelected)) {
                            $offers = $this->findOffersByCategory(
                                $category->term_id,
                            );

                            $posts = $this->getPostsByCatId($filtreSelected);

                            return $this->treatment($currentCategoryId, $offers, $posts);
                        }
                    } else {
                        $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
                        if ($typeOffre = $typeOffreRepository->find($filtreSelected)) {
                            $offers = $this->findOffresByTypesOffre([$typeOffre]);
                            $posts = [];

                            return $this->treatment($currentCategoryId, $offers, $posts);
                        }
                    }
                }

                $offres = $this->findOffersByCategory($currentCategoryId);
                $posts = $this->getPostsByCatId($currentCategoryId);

                return $this->treatment($currentCategoryId, $offres, $posts);
            },
        );
    }

    /**
     * @param int $categoryIdSelected
     * @return Offre[]
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function findOffersByCategory(int $categoryIdSelected): array
    {
        $wpFilterRepository = new WpFilterRepository();
        $typesOffre = $wpFilterRepository->getTypesOffreByCategoryId($categoryIdSelected);
        $codesCgt = $wpFilterRepository->getCodesCgtByCategoryId($categoryIdSelected);

        foreach ($this->getChildrenOfCategory($categoryIdSelected) as $child) {
            $tmp = $wpFilterRepository->getTypesOffreByCategoryId($child->term_id);
            foreach ($tmp as $t) {
                if ($t) {
                    $typesOffre[$t->id] = $t;
                }
            }
            $tmp = $wpFilterRepository->getCodesCgtByCategoryId($child->term_id);
            foreach ($tmp as $t) {
                if ($t) {
                    $codesCgt[$t] = $t;
                }
            }
        }

        $offres = $this->findOffresByTypesOffre($typesOffre);
        $offersShort = $this->findOffersShortByCodesCgt($codesCgt);

        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        $offers = [];
        foreach ($offersShort as $offerShort) {
            if ($offer = $pivotRepository->fetchOffreByCgtAndParse($offerShort->codeCgt)) {
                $offers[] = $offer;
            }
        }

        return [...$offres, ...$offers];
    }

    /**
     * @param int $cat_ID
     * @return WP_Term[]
     */
    public function getChildrenOfCategory(int $cat_ID): array
    {
        $args = [
            'parent' => $cat_ID,
            'hide_empty' => false,
        ];
        $children = get_categories($args);
        array_map(
            function ($category) {
                $category->url = get_category_link($category->term_id);
                $category->id = $category->term_id;
            },
            $children,
        );

        return $children;
    }

    /**
     * @param TypeOffre[] $filters
     * @return Offre[]
     * @throws InvalidArgumentException
     */
    public function findOffresByTypesOffre(array $filters): array
    {
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

        return $pivotRepository->fetchOffres($filters);
    }

    /**
     * @param string[] $codesCgt
     * @return \stdClass[]
     */
    public function findOffersShortByCodesCgt(array $codesCgt, bool $refreshCache = false): array
    {
        $cacheKey = Cache::generateKey('OffersShortByCodesCgt-'.json_encode($codesCgt));
        $cache = Cache::instance('visit-wp');
        if ($refreshCache) {
            try {
                Cache::purgeCacheHard();
                $cache->delete($cacheKey);
            } catch (InvalidArgumentException $e) {
            }
        }

        return $cache->get($cacheKey, function (ItemInterface $item) use ($codesCgt) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);
            $offers = [];
            try {
                $offersShort = $this->getAllOffresShorts();
                foreach ($codesCgt as $codeCgt) {
                    foreach ($offersShort as $offerShort) {
                        if ($offerShort->codeCgt === $codeCgt) {
                            $offers[] = $offerShort;
                            break;
                        }
                    }
                }
            } catch (InvalidArgumentException $e) {
                return [];
            }

            return $offers;
        });
    }

    /**
     * @return \stdClass{codeCgt: string, name: string, type: string}[]
     * @throws InvalidArgumentException
     */
    public function getAllOffresShorts(): array
    {
        $cacheKey = Cache::generateKey('alloffershort-');
        $cache = Cache::instance('visit-wp');

        return $cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);

            $offres = [];
            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
            $responseJson = $pivotRepository->getAllDataFromRemote(true, QueryDetailEnum::QUERY_DETAIL_LVL_RESUME);

            $tmp = json_decode($responseJson)->offre;

            foreach ($tmp as $offre) {
                $std = new \stdClass();
                $std->codeCgt = $offre->codeCgt;
                $std->name = $offre->nom;
                $std->type = $offre->typeOffre->label[0]->value;
                $offres[] = $std;
            }

            return PostUtils::sortOffresByName($offres);
        });
    }

    /**
     * @param int $catId
     * @return WP_Post[]
     */
    public function getPostsByCatId(int $catId): array
    {
        $args = [
            'cat' => $catId,
            'numberposts' => 5000,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ];

        $querynews = new WP_Query($args);
        $posts = [];
        while ($querynews->have_posts()) {
            $post = $querynews->next_post();
            $post->excerpt = $post->post_excerpt;
            $post->permalink = get_permalink($post->ID);
            $post->thumbnail_url = $this->getPostThumbnail($post->ID);
            $posts[] = $post;
        }

        return $posts;
    }

    public function getPostThumbnail(int $id): string
    {
        if (has_post_thumbnail($id)) {
            $attachment_id = get_post_thumbnail_id($id);
            $images = wp_get_attachment_image_src($attachment_id, 'original');
            $post_thumbnail_url = $images[0];
        } else {
            $post_thumbnail_url = get_template_directory_uri().'/assets/images/404.jpg';
        }

        return $post_thumbnail_url;
    }

    /**
     * @param int $currentCategoryId
     * @param Offre[] $offers
     * @param WP_Post[] $posts
     * @return  CommonItem[]
     */
    private function treatment(int $currentCategoryId, array $offers, array $posts): array
    {
        $language = LocaleHelper::getSelectedLanguage();

        $category_order = get_term_meta($currentCategoryId, CategoryMetaBox::KEY_NAME_ORDER, true);

        //fusion offres et articles
        $postUtils = new PostUtils();
        $posts = $postUtils->convertPostsToArray($posts);
        $offres = $postUtils->convertOffresToArray($offers, $currentCategoryId, $language);

        $data = PostUtils::removeDoublon([...$posts, ...$offres]);
        RouterPivot::setLinkOnCommonItems($data, $currentCategoryId, $language);

        if ('manual' === $category_order) {
            return AcSort::getSortedItems($currentCategoryId, $data);
        }

        return PostUtils::sortOffresByName($data);
    }

    public function categoryImage(WP_Term $category): string
    {
        $image = null;
        if ($imageId = get_term_meta($category->term_id, 'image', true)) {
            $image = esc_url(wp_get_attachment_image_url(($imageId), 'full'));
        }

        if (!$image) {
            $image = get_template_directory_uri().'/assets/tartine/bg_inspirations.png';
        }

        return $image;
    }

    public function categoryVideo(WP_Term $category): ?string
    {
        $video = null;
        if ($imageId = get_term_meta($category->term_id, 'video', true)) {
            $video = esc_url(wp_get_attachment_url(($imageId)));
        }

        return $video;
    }

    public function categoryBgColor(WP_Term $category): string
    {
        return IconeEnum::bgColor($category->slug);
    }

    public function categoryIcone(WP_Term $category): string
    {
        $icon = IconeEnum::icone($category->slug);
        if ($icon) {
            $icon = get_template_directory_uri().'/assets/tartine/'.$icon;
        }

        return $icon;
    }

    /**
     * @param WP_Post $post
     * @return array
     */
    public function recommandationsByPost(WP_Post $post): array
    {
        $key = 'recompost-'.$post->ID;
        $cacheKey = Cache::generateKey($key);
        $cache = Cache::instance('wprepo');

        return $cache->get($cacheKey, function (ItemInterface $item) use ($post) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);
            $recommandations = $this->getSamePosts($post->ID);
            if (0 === \count($recommandations)) {
                $searcher = PivotContainer::getSearchMeili(WP_DEBUG);
                global $wp_query;
                $recommandations = $searcher->searchRecommandations($wp_query);
            }

            return $recommandations;
        });
    }

    public function getSamePosts(int $postId): array
    {
        $cacheKey = Cache::generateKey('sampepost'.$postId);
        $cache = Cache::instance('visit-wp');

        return $cache->get($cacheKey, function (ItemInterface $item) use ($postId) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);
            $categories = get_the_category($postId);
            $args = [
                'category__in' => array_map(
                    fn($category) => $category->cat_ID,
                    $categories,
                ),
                'post__not_in' => [$postId],
                'orderby' => 'title',
                'order' => 'ASC',
            ];
            $query = new \WP_Query($args);
            $recommandations = [];
            foreach ($query->posts as $post) {
                $image = null;
                if (has_post_thumbnail($post)) {
                    $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'original');
                    if ($images) {
                        $image = $images[0];
                    }
                }
                $recommandations[] = [
                    'name' => $post->post_title,
                    'excerpt' => $post->post_excerpt,
                    'url' => get_permalink($post->ID),
                    'image' => $image,
                    'tags' => PostUtils::tagsPost($post),
                ];
            }

            return $recommandations;
        });
    }

    public function recommandationsByOffre(Offre $offerRefer, WP_Term $category, string $language): array
    {
        $key = Cache::SEE_ALSO_OFFRES.'-'.$offerRefer->codeCgt.'-'.$category->term_id;
        $cacheKey = Cache::generateKey($key);
        $cache = Cache::instance('wprepo');

        try {
            return $cache->get($cacheKey, function (ItemInterface $item) use ($offerRefer, $category, $language) {
                $item->expiresAfter(CacheUtils::DURATION);
                $item->tag(CacheUtils::TAG);
                if (count($offerRefer->see_also)) {
                    $offres = $offerRefer->see_also;
                } else {
                    $pivotRepository = PivotContainer::getPivotRepository();
                    $offres = $pivotRepository->fetchSameOffres($offerRefer, 10);
                }

                $recommandations = PostUtils::convertRecommandationsToArray($offres, $language);
                $count = count($recommandations);
                if ($count === 0) {
                    return [];
                }

                RouterPivot::setLinkOnCommonItems($recommandations, $category->term_id, $language);
                $data = [];

                if ($count > 3) {
                    $count = 3;
                }

                $keys = array_rand($recommandations, $count);

                if (is_array($keys)) {
                    foreach ($keys as $key) {
                        $data[] = $recommandations[$key];
                    }
                }

                return $data;
            });
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Offre[]
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function getEvents(?int $filterSelected = null): array
    {
        $cacheKey = Cache::generateKey('events-');
        if ($filterSelected) {
            $cacheKey .= $filterSelected;
        }
        $cache = Cache::instance('visit-wp');

        return $cache->get($cacheKey, function (ItemInterface $item) use ($filterSelected) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);
            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
            $args = [];
            if ($filterSelected) {
                $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
                try {
                    $filtre = $typeOffreRepository->find($filterSelected);
                    if ($filtre instanceof TypeOffre) {
                        $args = [$filtre];
                    }
                } catch (NonUniqueResultException $e) {
                    return [];
                }
            }

            $events = $pivotRepository->fetchEvents($args);
            $data = [];
            foreach ($events as $event) {
                if ($event->visibiliteUrn->urn !== EventEnum::CONVENTION->value) {
                    $event->locality = $event->getAdresse()->localite[0]->get('fr');
                    $event->dateEvent = [
                        'year' => $event->dateEnd->format('Y'),
                        'month' => $event->dateEnd->format('m'),
                        'day' => $event->dateEnd->format('d'),
                    ];
                    if (count($event->images) == 0) {
                        $event->images = [get_template_directory_uri().'/assets/tartine/bg_events.png'];
                    }
                    $data[] = $event;
                }
            }

            return $data;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getOffreByCgtAndParse(string $codeCgt): ?Offre
    {
        $cacheKey = Cache::generateKey('offrecgt-'.$codeCgt);
        $cache = Cache::instance('visit-wp');

        return $cache->get($cacheKey, function (ItemInterface $item) use ($codeCgt) {
            $item->expiresAfter(CacheUtils::DURATION);
            $item->tag(CacheUtils::TAG);
            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

            return $pivotRepository->fetchOffreByCgtAndParse($codeCgt);
        });
    }

    public function groupSpecifications(Offre $offre): array
    {
        $categories = [];
        foreach ($offre->specifications as $specification) {
            $categories[$specification->data->urnCat]['category'] = $specification->urnCatDefinition;
            $item = ["data" => $specification->data, "definition" => $specification->urnDefinition];
            $categories[$specification->data->urnCat]['items'][] = $item;
        }

        return $categories;
    }

    public function getAllWalks(): array
    {
        $skip = ["LOD-01-0AVJ-77ZN"];
        try {
            $offres = $this->findOffersByCategory(Theme::CATEGORY_BALADES);
        } catch (NonUniqueResultException|InvalidArgumentException $e) {
            return [];
        }

        $gpxViewer = new GpxViewer();
        $offers = [];
        foreach ($offres as $offre) {
            if (in_array($offre->codeCgt, $skip)) {
                continue;
            }

            try {
                $locations = [];
                if (count($offre->gpxs) > 0) {
                    $gpx = $offre->gpxs[0];
                    foreach ($gpxViewer->getLocations($gpx) as $location) {
                        $locations[] = [$location['latitude'], $location['longitude']];
                    }
                }
                $offers[$offre->codeCgt] = [
                    'codeCgt' => $offre->codeCgt,
                    'nom' => $offre->nom,
                    'url' => RouterPivot::getUrlOffre(Theme::CATEGORY_BALADES, $offre->codeCgt),
                    'images' => $offre->images,
                    'image' => count($offre->images) > 0 ? $offre->images[0] : null,
                    'address' => $offre->adresse1,
                    'localite' => $offre->adresse1->localite[0]->value,
                    'type' => self::getTypeWalk($offre->codeCgt),
                    'locations' => $locations,
                    'gpx_duree' => $offre->gpx_duree,
                    'gpx_difficulte' => $offre->gpx_difficulte,
                    'gpx_distance' => $offre->gpx_distance,
                ];
            } catch (\Exception $e) {
                return [];
            }
        }

        $offers[] = $this->addFamenneWalk($gpxViewer);
        $offers[] = $this->addGrp151Walk($gpxViewer);
        $offers[] = $this->addMontArdenneWalk($gpxViewer);

        return array_values($offers);
    }

    private static function getTypeWalk(string $codeCgt): int
    {
        $wpFilterRepository = new WpFilterRepository();
        if (in_array($codeCgt, $wpFilterRepository->getCodesCgtByCategoryId(Theme::CATEGORY_BIKE))) {
            return Theme::CATEGORY_BIKE;
        }
        if (in_array($codeCgt, $wpFilterRepository->getCodesCgtByCategoryId(Theme::CATEGORY_HIKES))) {
            return Theme::CATEGORY_HIKES;
        }

        return Theme::CATEGORY_FOOT;
    }

    private function addFamenneWalk(GpxViewer $gpxViewer): array
    {
        $address = new Adresse();
        $label = new Label();
        $label->value = 'Marche-en-Famenne';
        $label->lang = 'fr';
        $address->localite = [$label];
        $address->rue = "Rue du Luxembourg";
        $address->latitude = 50.2223474;
        $address->longitude = 5.34569;
        $locations = [];
        $gpx = new Gpx();
        $gpx->data_raw = file_get_contents(get_template_directory().'/assets/gpx/TRANSFAMENNE.gpx');
        foreach ($gpxViewer->getLocations($gpx) as $location) {
            $locations[] = [$location['latitude'], $location['longitude']];
        }

        return [
            'codeCgt' => 'transfamenne',
            'nom' => 'Transfamenne',
            'url' => '/fr/balades/transfamenne/',
            'images' => ['/wp-content/uploads/2024/02/trees-3294681_960_720-1.jpg'],
            'image' => '/wp-content/uploads/2024/02/trees-3294681_960_720-1.jpg',
            'address' => $address,
            'localite' => 'Famenne',
            'type' => Theme::CATEGORY_FOOT,
            'locations' => $locations,
            'gpx_duree' => '',
            'gpx_difficulte' => 'Intermédiaire',
            'gpx_distance' => 126,
        ];
    }

    private function addGrp151Walk(GpxViewer $gpxViewer): array
    {
        $address = new Adresse();
        $label = new Label();
        $label->value = 'Marche-en-Famenne';
        $label->lang = 'fr';
        $address->localite = [$label];
        $address->rue = "Place de l'Eglise";
        $address->latitude = 50.2225223;
        $address->longitude = 5.3432502;
        $locations = [];
        $gpx = new Gpx();
        $gpx->data_raw = file_get_contents(get_template_directory().'/assets/gpx/GRP151.gpx');
        foreach ($gpxViewer->getLocations($gpx) as $location) {
            $locations[] = [$location['latitude'], $location['longitude']];
        }

        return [
            'codeCgt' => 'grp151',
            'nom' => 'GRP 151 - Sentiers de l\'Ardenne - Tour du Luxembourg belge',
            'url' => '/fr/balades/gr-151-sentiers-de-lardenne-tour-du-luxembourg-belge/',
            'images' => ['/wp-content/uploads/2024/02/forest-1868028_960_720.jpg'],
            'image' => '/wp-content/uploads/2024/02/forest-1868028_960_720.jpg',
            'address' => $address,
            'localite' => 'Marche-en-Famenne',
            'type' => Theme::CATEGORY_HIKES,
            'locations' => $locations,
            'gpx_duree' => '',
            'gpx_difficulte' => 'Intermédiaire',
            'gpx_distance' => 234,
        ];
    }

    private function addMontArdenneWalk(GpxViewer $gpxViewer): array
    {
        $address = new Adresse();
        $label = new Label();
        $label->value = 'Marche-en-Famenne';
        $label->lang = 'fr';
        $address->localite = [$label];
        $address->rue = "Rue du Luxembourg";
        $address->latitude = 50.2223474;
        $address->longitude = 5.34569;
        $locations = [];
        $gpx = new Gpx();
        $gpx->data_raw = file_get_contents(get_template_directory().'/assets/gpx/mont_ardenne.gpx');
        foreach ($gpxViewer->getLocations($gpx) as $location) {
            $locations[] = [$location['latitude'], $location['longitude']];
        }

        return [
            'codeCgt' => 'mont_ardenne',
            'nom' => 'Le sentier des Monts d\'Ardenne',
            'url' => '/fr/balades/le-sentier-des-monts-dardenne/',
            'images' => ['/wp-content/uploads/2024/06/high-fens-2532335_1280.jpg'],
            'image' => '/wp-content/uploads/2024/06/high-fens-2532335_1280.jpg',
            'address' => $address,
            'localite' => 'Marche-en-Famenne',
            'type' => Theme::CATEGORY_HIKES,
            'locations' => $locations,
            'gpx_duree' => '',
            'gpx_difficulte' => 'Difficile',
            'gpx_distance' => 225,
        ];
    }
}