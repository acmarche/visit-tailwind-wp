<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Spec\UrnTypeList;
use VisitMarche\ThemeTail\Entity\CommonItem;
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
     * @return array|CommonItem[]
     */
    public function convertPostsToArray(array $posts): array
    {
        return array_map(
            function ($post) {
                $this->tagsPost($post);

                return new CommonItem(
                    $post->ID,
                    $post->post_title,
                    $post->post_excerpt,
                    $post->thumbnail_url,
                    $post->permalink,
                    $post->tags
                );
            },
            $posts
        );
    }

    /**
     * @param Offre[] $offres
     * @param int $categoryId
     * @param string $language
     * @return array|CommonItem[]
     */
    public function convertOffresToArray(array $offres, int $categoryId, string $language): array
    {
        return array_map(
            function ($offre) use ($categoryId, $language) {
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
                if (!$image) {
                    $image = get_template_directory_uri().'/assets/tartine/bg_home.jpg';
                }

                $item = new CommonItem(
                    $offre->codeCgt, $name, $description, $image, $offre->url, $offre->tagsFormatted
                );

                $item->locality = $offre->adresse1->localiteByLanguage('fr');//ajax

                if ($offre->typeOffre->idTypeOffre == UrnTypeList::evenement()->typeId) {
                    $item->dateEvent = $offre->dateEvent;//ajax
                    if (!$offre->firstImage()) {
                        $item->image = get_template_directory_uri().'/assets/tartine/bg_events.png';
                    }
                }

                return $item;

            },
            $offres
        );
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
        $post->tags = $this->wpRepository->tagsOfPost($post->ID);
    }

    /**
     * @param array $offres
     * @param string $language
     * @return array|CommonItem[]
     */
    public static function convertRecommandationsToArray(array $offres, string $language): array
    {
        $recommandations = [];
        foreach ($offres as $offre) {
            (new PostUtils)->tagsOffre($offre, $language);
            $image = $offre->firstImage();
            if (!$image) {
                $image = get_template_directory_uri().'/assets/tartine/bg_home.jpg';
            }
            $item = new CommonItem(
                $offre->codeCgt, $offre->name(), '', $image, $offre->url, $offre->tags
            );
            $recommandations[] = $item;
        }

        return $recommandations;
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
                $offre->url = RouterPivot::getUrlOffre($offre, $categoryId);
            },
            $offres
        );
    }
}
