<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Spec\UrnTypeList;
use WP_Post;

class PostUtils
{
    private WpRepository $wpRepository;

    public function __construct()
    {
        $this->wpRepository = new WpRepository();
    }

    /**
     * @param WP_Post[] $posts
     * @return array
     */
    public function convertPostsToArray(array $posts): array
    {
        return array_map(
            fn($post) => $this->postToArray($post),
            $posts
        );
    }

    public function postToArray(WP_Post $post): array
    {
        $this->tagsPost($post);

        return [
            'id' => $post->ID,
            'url' => $post->permalink,
            'nom' => $post->post_title,
            'description' => $post->post_excerpt,
            'tags' => $post->tags,
            'image' => $post->thumbnail_url,
        ];
    }

    public static function getImage(WP_Post $post): ?string
    {
        if (has_post_thumbnail($post)) {
            $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'original');
            if ($images) {
                return $images[0];
            }
        }

        return null;
    }

    public function tagsOffre(Offre $offre, string $language, ?string $urlCat = null)
    {
        $tags = [];
        foreach ($offre->tags as $tag) {
            $tag->name = $tag->labelByLanguage($language);
            if ($urlCat) {
                $tag->url = $urlCat.'?'.RouterPivot::PARAM_FILTRE.'='.$tag->urn;
            }
            $tags[] = $tag;
        }
        $offre->tagsFormatted = $tags;
    }

    public function tagsPost(WP_Post $post)
    {
        $tags = $this->wpRepository->tagsOfPost($post->ID);
        $post->tags = array_map(
            fn($category) => $category['name'],
            $tags
        );
    }

    public static function convertRecommandationsToArray(array $offres): array
    {
        $recommandations = [];
        foreach ($offres as $offre) {
            $recommandations[] = [
                'name' => $offre->name(),
                'url' => $offre->url,
                'excerpt' => '',
                'image' => $offre->firstImage(),
                'tags' => $offre->tags,
            ];
        }

        return $recommandations;
    }

    /**
     * @param Offre[] $offres
     * @param int $categoryId
     * @param string $language
     * @return array
     */
    public function convertOffresToArray(array $offres, int $categoryId, string $language): array
    {
        return array_map(
            function ($offre) use ($categoryId, $language) {
                $url = RouterPivot::getUrlOffre($offre, $categoryId);
                $name = $offre->nameByLanguage($language);
                $description = null;
                if ((is_countable($offre->descriptions) ? \count($offre->descriptions) : 0) > 0) {
                    $tmp = $offre->descriptionsByLanguage($language);
                    if (count($tmp) == 0) {
                        $tmp = $offre->descriptions;
                    }
                    $description = $tmp[0]->value;
                }
                $this->tagsOffre($offre, $language);
                $image = $offre->firstImage();

                $data = [
                    'id' => $offre->codeCgt,
                    'url' => $url,
                    'name' => $name,
                    'locality' => $offre->adresse1->localiteByLanguage('fr'),
                    'dateEvent' => $offre->dateEvent,
                    'description' => $description,
                    'tags' => $offre->tagsFormatted,
                    'image' => $image,
                ];

                if ($offre->typeOffre->idTypeOffre == UrnTypeList::evenement()->typeId) {
                    $data['dateEvent'] = $offre->dateEvent;
                    if (!$offre->image) {
                        $offre->image = get_template_directory_uri().'/assets/tartine/bg_home.jpg';
                    }
                }

                return $data;

            },
            $offres
        );
    }

    /**
     * @param array|Offre[] $offres
     * @param int $categoryId
     * @param string $language
     * @return void
     */
    public static function setLinkOnOffres(array $offres, int $categoryId, string $language)
    {
        array_map(
            function ($offre) use ($categoryId, $language) {
                $urlCat = get_category_link($categoryId);
                $offre->url = RouterPivot::getUrlOffre($offre, $categoryId);
                if (count($offre->images) == 0) {
                    $offre->images = [get_template_directory_uri().'/assets/tartine/bg_home.jpg'];
                }
                $postUtils = new PostUtils();
                $postUtils->tagsOffre($offre, $language);
            },
            $offres
        );
    }
}
