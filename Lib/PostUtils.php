<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
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

    public function tagsOffre(Offre $offre, string $language)
    {
        $tags = [];
        foreach ($offre->categories as $category) {
            $tag = new \stdClass();
            $tag->nom = $category->labelByLanguage($language);
            $tag->key = $category->urn;
            $tags[] = $tag;
        }
        $offre->tags = $tags;
    }

    public function tagsPost(WP_Post $post)
    {
        $tags = $this->wpRepository->getTags($post->ID);
        $post->tags = array_map(
            fn($category) => $category['name'],
            $tags
        );
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
                $nom = $offre->nomByLanguage($language);
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

                return [
                    'id' => $offre->codeCgt,
                    'url' => $url,
                    'nom' => $nom,
                    'description' => $description,
                    'tags' => $offre->tags,
                    'image' => $image,
                ];

            },
            $offres
        );
    }
}
