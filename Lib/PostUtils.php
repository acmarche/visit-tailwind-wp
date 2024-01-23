<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Label;
use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entities\Specification\SpecData;
use AcMarche\Pivot\Entities\Tag;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Spec\UrnTypeList;
use VisitMarche\ThemeTail\Entity\CommonItem;
use WP_Post;

class PostUtils
{
    /**
     * @param WP_Post[] $posts
     * @return CommonItem[]
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
     * @return CommonItem[]
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
                        $tmp = [$offre->descriptionsByLanguage()];//force fr
                    }
                    if ($tmp[0] instanceof SpecData) {
                        $description = $tmp[0]->value;
                    }
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
                    $item->isPeriod = $offre->firstDate()->isPeriod();

                    if (!$offre->firstImage()) {
                        $item->image = get_template_directory_uri().'/assets/tartine/bg_events.png';
                    }
                }

                return $item;

            },
            $offres
        );
    }

    /**
     * @param Offre $offre
     * @param string $language
     * @param string|null $urlCat
     * @return array|Tag[]
     */
    public function tagsOffre(Offre $offre, string $language, ?string $urlCat = null): array
    {
        $tags = [];
        foreach ($offre->tags as $tag) {

            $tag->name = $tag->labelByLanguage($language);
            if ($urlCat) {
                $tag->url = $urlCat.'?'.RouterPivot::PARAM_FILTRE.'='.$tag->urn;
            }
            $tags[] = $tag;
        }
        //pour ne pas ecraser la valeur initiale
        $offre->tagsFormatted = $tags;

        return $tags;
    }

    /**
     * @param WP_Post $post
     * @return array|Tag[]
     */
    public static function tagsPost(WP_Post $post): array
    {
        $tags = [];
        foreach (get_the_category($post) as $category) {
            $label = new Label();
            $label->lang = 'fr';
            $label->value = $category->name;
            $tag = new Tag('urn', [$label]);
            $tag->name = $category->name;
            $tag->url = get_category_link($category);
            $tags[] = $tag;
        }

        $post->tags = $tags;
        $post->tagsFormatted = $tags;

        return $tags;
    }

    /**
     * @param array $offres
     * @param string $language
     * @return CommonItem[]
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
     * @param CommonItem[] $offers
     * @return CommonItem[]
     */
    public static function removeDoublon(array $offers): array
    {
        $tmp = [];
        foreach ($offers as $offer) {
            $tmp[$offer->id] = $offer;
        }

        return array_values($tmp);
    }

    /**
     * @param TypeOffre[] $typesOffre
     * @return FilterStd[]
     */
    public static function convertTypeOffreToFilterStd(array $typesOffre): array
    {
        $filters = [];
        foreach ($typesOffre as $typeOffre) {
            $filters[] = FilterStd::createFromTypeOffre($typeOffre);
        }

        return $filters;

    }
}
