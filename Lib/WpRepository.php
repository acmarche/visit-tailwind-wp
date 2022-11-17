<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Entity\UrnDefinitionEntity;
use AcMarche\Pivot\Spec\UrnList;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Contracts\Cache\CacheInterface;
use VisitMarche\ThemeTail\Inc\PivotMetaBox;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Elasticsearch\Searcher;
use WP_Post;
use WP_Query;
use WP_Term;

class WpRepository
{
    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = Cache::instance();
    }

    public function getCategoryBySlug(string $slug)
    {
        return get_category_by_slug($slug);
    }

    public function getTags(int $postId): array
    {
        $tags = [];
        foreach (get_the_category($postId) as $category) {
            $tags[] = [
                'name' => $category->name,
                'url' => get_category_link($category),
            ];
        }

        return $tags;
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

    /**
     * @return array|WP_Term|object|\WP_Error|null
     */
    public function getParentCategory(int $cat_ID)
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

    private function getSamePosts(int $postId): array
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
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'url' => get_permalink($post->ID),
                'image' => $image,
                'tags' => self::getTags($post->ID),
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
     * @param bool $filterCount
     * @return TypeOffre[]|array
     * @throws NonUniqueResultException
     */
    public static function getCategoryFilters(
        int $categoryWpId,
        bool $flatWithChildren = false,
        bool $filterCount = true
    ): array {
        if (in_array($categoryWpId, Theme::CATEGORIES_HEBERGEMENT)) {
            return WpRepository::getChildrenHebergements($filterCount);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_AGENDA)) {
            return WpRepository::getChildrenEvents($filterCount);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_RESTAURATION)) {
            return WpRepository::getChildrenRestauration($filterCount);
        }

        $categoryUrns = PivotMetaBox::getMetaPivotTypesOffre($categoryWpId);
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
            unset($typeOffre->parent);

            $typeOffre->withChildren = $categoryUrn['withChildren'];
            $allFiltres[] = $typeOffre;

            /**
             * Force a pas prendre enfant
             */
            if ($flatWithChildren) {
                continue;
            }

            if ($categoryUrn['withChildren']) {
                $children = $typeOffreRepository->findByParent($typeOffre->id, $filterCount);
                foreach ($children as $typeOffreChild) {
                    //bug parent is a proxy
                    unset($typeOffreChild->parent);
                    $allFiltres[] = $typeOffreChild;
                }
            }
        }

        return $allFiltres;
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException|\Exception
     */
    public static function getChildrenEvents(bool $filterCount): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $parents = $filtreRepository->findByUrn(UrnList::EVENT_CINEMA->value);

        return $filtreRepository->findByParent($parents[0]->parent->id, $filterCount);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException|\Exception
     */
    public static function getChildrenRestauration(bool $filterCount): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $barVin = $filtreRepository->findOneByUrn(UrnList::BAR_VIN->value);

        return $filtreRepository->findByParent($barVin->parent->id, $filterCount);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException
     */
    public static function getChildrenHebergements(bool $filterCount): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filtre = $filtreRepository->findOneByUrn(UrnList::HERGEMENT->value);

        return $filtreRepository->findByParent($filtre->id, $filterCount);
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
        $cacheKey = Cache::generateKey(Cache::SEE_ALSO_OFFRES.'-'.$offerRefer->codeCgt.'-'.$category->term_id);

        return $this->cache->get($cacheKey, function () use ($offerRefer, $category, $language) {
            $recommandations = [];

            if (count($offerRefer->see_also)) {
                $offres = $offerRefer->see_also;
            } else {
                $pivotRepository = PivotContainer::getPivotRepository();
                $offres = $pivotRepository->fetchSameOffres($offerRefer);
            }
            $urlCat = get_category_link($category);
            foreach ($offres as $offre) {
                $url = RouterPivot::getUrlOffre($offre, $category->cat_ID);
                $tags[] = [];
                foreach ($offre->categories as $categoryItem) {
                    $tags[] = [
                        'name' => $categoryItem->labelByLanguage($language),
                        'url' => $urlCat.'?'.RouterPivot::PARAM_FILTRE.'='.$category->urn,
                    ];
                }

                $recommandations[] = [
                    'title' => $offre->nomByLanguage($language),
                    'url' => $url,
                    'excerpt' => '',
                    'image' => $offre->firstImage(),
                    'tags' => $tags,
                ];
            }

            return $recommandations;
        });
    }

    public function getEvents(bool $removeObsolete = false, TypeOffre $typeOffre = null): array
    {
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        if ($typeOffre) {
            $filtres = [$typeOffre];
        } else {
            $filtres = $this->getChildrenEvents(true);
        }

        $events = $pivotRepository->fetchEvents($removeObsolete, $filtres);

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
        }

        return $events;
    }

    /**
     * @param TypeOffre[] $typesOffre
     * @param bool $parse
     * @return Offre[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getOffres(array $typesOffre): array
    {
        $keyName = '';
        foreach ($typesOffre as $typeOffre) {
            $keyName .= $typeOffre->urn.'-';
        }
        $cacheKey = Cache::generateKey(Cache::OFFRES.'-'.$keyName);

        return $this->cache->get($cacheKey, function () use ($typesOffre) {
            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

            return $pivotRepository->fetchOffres($typesOffre);
        });
    }

    public function getOffreByCgtAndParse(string $codeCgt, string $class, ?string $cacheKeyPlus = null): ?Offre
    {
        $cacheKey = Cache::generateKey(Cache::OFFRE.'-'.$codeCgt.'-'.$class);

        return $this->cache->get($cacheKey, function () use ($codeCgt, $class, $cacheKeyPlus) {

            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);

            return $pivotRepository->fetchOffreByCgtAndParse($codeCgt, $class, $cacheKeyPlus);
        });
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
}