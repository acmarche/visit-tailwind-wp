<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Api\QueryDetailEnum;
use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Event\EventEnum;
use AcSort;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use VisitMarche\ThemeTail\Entity\CommonItem;
use VisitMarche\ThemeTail\Inc\CategoryMetaBox;
use VisitMarche\ThemeTail\Inc\Theme;
use VisitMarche\ThemeTail\Lib\Elasticsearch\Searcher;
use WP_Post;
use WP_Query;
use WP_Term;

class WpRepository
{
    public const PIVOT_REFRUBRIQUE = 'pivot_refrubrique';
    public const PIVOT_REFOFFERS = 'pivot_ref_offers';

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

    public function getIdeas(): array
    {
        $ideas = [];
        if ($term = get_category_by_slug('en-famille')) {
            $ideas[] = $this->addIdea($term, 'Famille.jpg');
        }
        if ($term = get_category_by_slug('en-solo-ou-duo')) {
            $ideas[] = $this->addIdea($term, 'Duo-WBT.jpg');
        }
        if ($term = get_category_by_slug('enterrement-de-vie-de-celibataire')) {
            $ideas[] = $this->addIdea($term, 'EVC.png');
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
        if ('manual' === $category_order) {
            $posts = AcSort::getSortedItems($currentCategoryId, $posts);
        }

        //fusion offres et articles
        $postUtils = new PostUtils();
        $posts = $postUtils->convertPostsToArray($posts);
        $offres = $postUtils->convertOffresToArray($offers, $currentCategoryId, $language);

        $data = PostUtils::removeDoublon([...$posts, ...$offres]);
        RouterPivot::setLinkOnCommonItems($data, $currentCategoryId, $language);

        return PostUtils::sortOffresByName($data);
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
        $cache = Cache::instance('wprepo');

        try {
            return $cache->get($cacheKey, function () use ($offerRefer, $category, $language) {
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
    public function findOffersShortByCodesCgt(array $codesCgt): array
    {
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