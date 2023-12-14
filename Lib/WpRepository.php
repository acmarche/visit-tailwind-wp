<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Api\QueryDetailEnum;
use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Entity\UrnDefinitionEntity;
use AcMarche\Pivot\Spec\UrnList;
use AcSort;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Elasticsearch\Searcher;
use WP_Post;
use WP_Query;
use WP_Term;

class WpRepository
{
    private CacheInterface $cache;
    public const PIVOT_REFRUBRIQUE = 'pivot_refrubrique';
    public const PIVOT_REFOFFERS = 'pivot_ref_offers';

    public function __construct()
    {
        $this->cache = Cache::instance('wprepo');
    }

    public function getCategoryBySlug(string $slug)
    {
        return get_category_by_slug($slug);
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
            $children
        );

        return $children;
    }

    public function getSamePosts(int $postId): array
    {
        $categories = get_the_category($postId);
        $args = [
            'category__in' => array_map(
                fn($category) => $category->cat_ID,
                $categories
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
     * @param int $categoryWpId
     * @param bool $flatWithChildren pour admin ne pas etendre enfants
     * @param bool $removeFilterEmpty
     * @param bool $unsetParent pour ajax
     * @return TypeOffre[]
     * @throws NonUniqueResultException
     */
    public static function getCategoryFilters(
        int $categoryWpId,
        bool $flatWithChildren = false,
        bool $removeFilterEmpty = true,
        bool $unsetParent = false
    ): array {
        if (in_array($categoryWpId, Theme::CATEGORIES_HEBERGEMENT)) {
            return WpRepository::getChildrenHebergements($removeFilterEmpty);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_AGENDA)) {
            return WpRepository::getAllFiltersEvent($removeFilterEmpty);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_RESTAURATION)) {
            return WpRepository::getChildrenRestauration($removeFilterEmpty);
        }

        $categoryUrns = WpRepository::getMetaPivotTypesOffre($categoryWpId);
        $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $allFiltres = [];

        foreach ($categoryUrns as $categoryUrn) {

            if (!isset($categoryUrn['urn'])) {
                continue;
            }

            $typeOffre = $typeOffreRepository->findOneByUrn($categoryUrn['urn']);
            if (!$typeOffre) {
                continue;
            }

            //bug parent is a proxy
            if ($unsetParent) {
                if ($typeOffre->parent) {
                    $typeOffre->parent = $typeOffreRepository->find($typeOffre->parent->id);
                }
            }

            $typeOffre->withChildren = $categoryUrn['withChildren'];
            $allFiltres[] = $typeOffre;

            /**
             * Force a pas prendre enfant
             */
            if ($flatWithChildren) {
                continue;
            }

            if ($categoryUrn['withChildren']) {
                $children = $typeOffreRepository->findByParent($typeOffre->id, $removeFilterEmpty);
                foreach ($children as $typeOffreChild) {
                    //bug parent is a proxy
                    if ($typeOffreChild->parent) {
                        $typeOffreChild->parent = $typeOffreRepository->find($typeOffreChild->parent->id);
                    }
                    $allFiltres[] = $typeOffreChild;
                }
            }
        }

        return $allFiltres;
    }

    /**
     * Retourne les posts, les offres
     * @param int $categoryId
     * @param int $filtreSelected
     * @return array
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function findAllArticlesForCategory(int $categoryId, int $filtreSelected): array
    {
        $offres = $filtres = [];
        $language = LocaleHelper::getSelectedLanguage();
        $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        $postUtils = new PostUtils();

        $typeOffreSelected = null;
        if ($filtreSelected == 0) {
            $filtres = $this->getCategoryFilters($categoryId);
            $filtres = RouterPivot::setRoutesToFilters($filtres, $categoryId);
        } else {
            if ($typeOffreSelected = $typeOffreRepository->find($filtreSelected)) {
                $filtres[] = $typeOffreSelected;
            }
        }

        if ([] !== $filtres) {
            if (in_array($categoryId, Theme::CATEGORIES_AGENDA)) {
                $offres = $this->getEvents($typeOffreSelected);
            } else {
                $offres = $this->getOffresByTypes($filtres);
            }
        }

        $offers = [];
        foreach ($this->findOffersShortsByCategory($categoryId) as $offerShort) {
            if ($offer = $pivotRepository->fetchOffreByCgtAndParse($offerShort->codeCgt)) {
                $offers[] = $offer;
            }
        }

        $offres = [...$offres, ...$offers];
        PostUtils::setLinkOnOffres($offres, $categoryId, $language);
        $posts = $this->getPostsByCatId($categoryId);

        $category_order = get_term_meta($categoryId, CategoryMetaBox::KEY_NAME_ORDER, true);
        if ('manual' === $category_order) {
            $posts = AcSort::getSortedItems($categoryId, $posts);
        }

        //fusion offres et articles
        $posts = $postUtils->convertPostsToArray($posts);
        $offres = $postUtils->convertOffresToArray($offres, $categoryId, $language);

        return PostUtils::removeDoublon([...$posts, ...$offres]);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException|\Exception
     */
    public static function getAllFiltersEvent(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $parents = $filtreRepository->findByUrn(UrnList::EVENTS->value);

        return $filtreRepository->findByParent($parents[0]->parent->id, $removeFilterEmpty);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException|\Exception
     */
    public static function getChildrenRestauration(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $barVin = $filtreRepository->findOneByUrn(UrnList::BAR_VIN->value);

        return $filtreRepository->findByParent($barVin->parent->id, $removeFilterEmpty);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException
     */
    public static function getChildrenHebergements(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filtre = $filtreRepository->findOneByUrn(UrnList::HERGEMENT->value);

        return $filtreRepository->findByParent($filtre->id, $removeFilterEmpty);
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
        $recommandations = $this->getSamePosts($post->ID);
        if (0 === \count($recommandations)) {
            $searcher = new Searcher();
            global $wp_query;
            $recommandations = $searcher->searchRecommandations($wp_query);
        }

        return $recommandations;
    }

    public function recommandationsByOffre(Offre $offerRefer, WP_Term $category, string $language): array
    {
        $key = Cache::SEE_ALSO_OFFRES.'-'.$offerRefer->codeCgt.'-'.$category->term_id;
        $cacheKey = Cache::generateKey($key);

        return $this->cache->get($cacheKey, function () use ($offerRefer, $category, $language) {
            if (count($offerRefer->see_also)) {
                $offres = $offerRefer->see_also;
            } else {
                $pivotRepository = PivotContainer::getPivotRepository();
                $offres = $pivotRepository->fetchSameOffres($offerRefer, 10);
            }
            PostUtils::setLinkOnOffres($offres, $category->term_id, $language);
            $recommandations = PostUtils::convertRecommandationsToArray($offres, $language);
            $count = count($recommandations);
            $data = [];

            if ($count === 0) {
                return $data;
            }

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
    }

    /**
     * @return Offre[]
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     */
    public function getEvents(TypeOffre $typeOffre = null): array
    {
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        if ($typeOffre) {
            $filtres = [$typeOffre];
        } else {
            $filtres = $this->getAllFiltersEvent(true);
        }

        $events = $pivotRepository->fetchEvents($filtres);
        $data = [];
        foreach ($events as $event) {
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

        return $data;
    }

    /**
     * @param TypeOffre[] $typesOffre
     * @return Offre[]
     * @throws InvalidArgumentException
     */
    public function getOffresByTypes(array $typesOffre): array
    {
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

        return $pivotRepository->fetchOffres($typesOffre);
    }

    /**
     * @param int $categoryId
     * @return \stdClass[]
     */
    public function findOffersShortsByCategory(int $categoryId): array
    {
        $wpRepository = new WpRepository();
        $codesCgt = WpRepository::getMetaPivotOffres($categoryId);
        $offers = [];
        try {
            $offersShort = $wpRepository->getAllOffresShorts();
            foreach ($codesCgt as $codeCgt) {
                foreach ($offersShort as $offerShort) {
                    if ($offerShort->codeCgt === $codeCgt) {
                        $offers[] = $offerShort;
                        break;
                    }
                }
            }

        } catch (InvalidArgumentException $e) {
        }

        return $offers;
    }


    /**
     * @return \stdClass{codeCgt: string, name: string, type: string}[]
     * @throws InvalidArgumentException
     */
    public function getAllOffresShorts(): array
    {
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

        return $offres;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getOffreByCgtAndParse(string $codeCgt): ?Offre
    {
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

        return $pivotRepository->fetchOffreByCgtAndParse($codeCgt);
    }

    public function getUrnDefinition(string $urnName): ?UrnDefinitionEntity
    {
        $pivotRepository = PivotContainer::getUrnDefinitionRepository(WP_DEBUG);

        return $pivotRepository->findByUrn($urnName);
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

    public static function getMetaPivotTypesOffre(int $wpCategoryId): array
    {
        $filtres = get_term_meta($wpCategoryId, self::PIVOT_REFRUBRIQUE, true);
        if (!is_array($filtres)) {
            return [];
        }

        return $filtres;
    }

    public static function getMetaPivotOffres(int $wpCategoryId): array
    {
        $offers = get_term_meta($wpCategoryId, self::PIVOT_REFOFFERS, true);
        if (!is_array($offers)) {
            return [];
        }

        return $offers;
    }
}