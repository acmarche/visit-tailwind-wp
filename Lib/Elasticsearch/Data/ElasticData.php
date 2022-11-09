<?php

namespace VisitMarche\ThemeTail\Lib\Elasticsearch\Data;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use DateTime;
use Exception;
use stdClass;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\PostUtils;
use VisitMarche\ThemeTail\Lib\WpRepository;
use WP_Post;
use WP_Term;

class ElasticData
{
    private WpRepository $wpRepository;
    private string $url;

    public function __construct()
    {
        $this->wpRepository = new WpRepository();
        $this->url = 'https://www.visitmarche.be/wp-json/visit/all';
    }

    public function getAllData(): stdClass
    {
        $t = json_decode(file_get_contents($this->url), null, 512, JSON_THROW_ON_ERROR); //2 times error ssl
        $t = json_decode(file_get_contents($this->url), null, 512, JSON_THROW_ON_ERROR); //2 times error ssl
        $t = json_decode(file_get_contents($this->url), null, 512, JSON_THROW_ON_ERROR);

        return $t;
    }

    /**
     * @param int $language
     *
     * @return DocumentElastic[]
     */
    public function getCategories(string $language = 'fr'): array
    {
        $datas = [];
        $today = new DateTime();

        foreach ($this->wpRepository->getCategoriesFromWp() as $category) {
            $description = '';
            if ($category->description) {
                $description = Cleaner::cleandata($category->description);
            }

            $date = $today->format('Y-m-d');
            $content = $description;

            foreach ($this->getPosts($category->cat_ID) as $documentElastic) {
                $content .= $documentElastic->name;
                $content .= $documentElastic->excerpt;
                $content .= $documentElastic->content;
            }

            $content .= $this->getContentHades($category, $language);

            $children = $this->wpRepository->getChildrenOfCategory($category->cat_ID);
            $tags = [];
            foreach ($children as $child) {
                $tags[] = $child->name;
            }
            $parent = $this->wpRepository->getParentCategory($category->cat_ID);
            if ($parent) {
                $tags[] = $parent->name;
            }

            $document = new DocumentElastic();
            $document->id = $category->cat_ID;
            $document->name = Cleaner::cleandata($category->name);
            $document->excerpt = $description;
            $document->content = $content;
            $document->tags = $tags;
            $document->date = $date;
            $document->url = get_category_link($category->cat_ID);
            $document->image = null;

            $datas[] = $document;
        }

        return $datas;
    }

    /**
     * @return DocumentElastic[]
     */
    public function getPosts(int $categoryId = null): array
    {
        $args = [
            'numberposts' => 5000,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ];

        if ($categoryId) {
            $args['category'] = $categoryId;
        }

        $posts = get_posts($args);
        $datas = [];

        foreach ($posts as $post) {
            if (($document = $this->postToDocumentElastic($post)) !== null) {
                $datas[] = $document;
            } else {
                Mailer::sendError(
                    'update elastic error ',
                    'create document '.$post->post_title
                );
                //  var_dump($post);
            }
        }

        return $datas;
    }

    /**
     * @return DocumentElastic[]
     */
    public function getOffres(string $language = 'fr'): array
    {
        $datas = [];

        foreach ($this->wpRepository->getCategoriesFromWp() as $category) {
            $filtres = $this->wpRepository->getCategoryFilters($category->cat_ID);

            if ([] !== $filtres) {
                $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
                $offres = $pivotRepository->getOffres($filtres);
                array_map(
                    function ($offre) use ($category, $language) {
                        $offre->url = RouterPivot::getUrlOffre($offre, $category->cat_ID);
                        $offre->titre = $offre->nomByLanguage($language);
                    },
                    $offres
                );
            }

            foreach ($offres as $offre) {
                $datas[] = $this->createDocumentElasticFromOffre($offre, $language);
            }
        }

        return $datas;
    }

    public function postToDocumentElastic(WP_Post $post): ?DocumentElastic
    {
        try {
            return $this->createDocumentElasticFromWpPost($post);
        } catch (Exception $exception) {
            Mailer::sendError('update elastic', 'create document '.$post->post_title.' => '.$exception->getMessage());
        }

        return null;
    }

    public function createDocumentElasticFromX(stdClass $post): DocumentElastic
    {
        $document = new DocumentElastic();
        $document->id = $post->id;
        $document->name = $post->name;
        $document->excerpt = $post->excerpt;
        $document->content = $post->content;
        $document->tags = $post->tags;
        $document->date = $post->date;
        $document->url = $post->url;

        return $document;
    }

    private function createDocumentElasticFromWpPost(WP_Post $post): DocumentElastic
    {
        [$date, $time] = explode(' ', $post->post_date);
        $categories = [];
        foreach (get_the_category($post->ID) as $category) {
            $categories[] = $category->cat_name;
        }

        $content = get_the_content(null, null, $post);
        $content = apply_filters('the_content', $content);

        $document = new DocumentElastic();
        $document->id = $post->ID;
        $document->name = Cleaner::cleandata($post->post_title);
        $document->excerpt = Cleaner::cleandata($post->post_excerpt);
        $document->content = Cleaner::cleandata($content);
        $document->tags = $categories;
        $document->date = $date;
        $document->url = get_permalink($post->ID);
        $document->image = PostUtils::getImage($post);

        return $document;
    }

    private function createDocumentElasticFromOffre(Offre $offre, string $language): DocumentElastic
    {
        $categories = [];
        foreach ($offre->categories as $category) {
            $categories[] .= ' '.$category->labelByLanguage($language);
        }

        $content = '';
        $offre->description = '';
        $descriptions = $offre->descriptionsByLanguage($language);
        if ([] !== $descriptions) {
            $offre->description = $offre->descriptions[0]->value;
            foreach ($descriptions as $description) {
                $content .= ' '.$description->value;
            }
        }

        $today = new DateTime();
        $document = new DocumentElastic();
        $document->id = $offre->codeCgt;
        $document->name = Cleaner::cleandata($offre->nomByLanguage($language));
        $document->excerpt = Cleaner::cleandata($offre->description);
        $document->content = Cleaner::cleandata($content);
        $document->tags = $categories;
        $document->date = $today->format('Y-m-d');
        $document->url = $offre->url;
        $document->image = $offre->firstImage();

        return $document;
    }

    private function getContentHades(WP_Term $category, string $language): string
    {
        $content = '';
        $categoryUtils = new WpRepository();
        $filtres = $categoryUtils->getCategoryFilters($category->cat_ID);

        if ([] !== $filtres) {
            $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
            $offres = $pivotRepository->getOffres($filtres);
            array_map(
                function ($offre) use ($category, $language) {
                    $offre->url = RouterPivot::getUrlOffre($offre, $category->cat_ID);
                    $offre->titre = $offre->nomByLanguage($language);
                },
                $offres
            );
            foreach ($offres as $offre) {
                $content .= $offre->nomByLanguage($language);
                $descriptions = $offre->descriptionsByLanguage($language);
                if ([] !== $descriptions) {
                    foreach ($descriptions as $description) {
                        $content .= ' '.$description->value;
                    }
                }
                foreach ($offre->categories as $category) {
                    $content .= ' '.$category->labelByLanguage($language);
                }
            }
        }

        return $content;
    }
}
