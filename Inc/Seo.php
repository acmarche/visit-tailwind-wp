<?php

namespace VisitMarche\ThemeTail\Inc;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entities\Offre\Offre;
use VisitMarche\ThemeTail\Lib\LocaleHelper;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use WP_Post;

class Seo
{
    private static array $metas = [
        'title' => '',
        'keywords' => '',
        'description' => '',
    ];

    public function __construct()
    {
        add_action('wp_head', function (): void {
            $this::assignMetaInfo();
        });
    }

    public static function assignMetaInfo(): void
    {
        if (Theme::isHomePage()) {
            self::metaHomePage();
            self::renderMetas();

            return;
        }

        global $post;
        if ($post) {
            self::metaPost($post);
            self::renderMetas();

            return;
        }

        $codeCgt = get_query_var(RouterPivot::PARAM_OFFRE);
        if ($codeCgt) {
            self::metaPivotOffre($codeCgt);
            self::renderMetas();
        }

        $cat_id = get_query_var('cat');
        if ($cat_id) {
            self::metaCategory($cat_id);
            self::renderMetas();

            return;
        }

        self::renderMetas();
    }

    public function isGoole(): void
    {
        global $is_lynx;
    }

    public static function baseTitle(string $begin): string
    {
        $base = wp_title('|', false, 'right');

        $nameSousSite = get_bloginfo('name', 'display');

        $tourisme = self::translate('page.tourisme');

        return $begin.' '.$tourisme.' '.$base.' '.$nameSousSite;
    }

    private static function renderMetas(): void
    {
        self::$metas['title'] = self::cleanString(self::$metas['title']);
        echo '<title>'.self::$metas['title'].'</title>';

        if ('' !== self::$metas['description']) {
            self::$metas['description'] = self::cleanString(self::$metas['description']);
            echo '<meta name="description" content="'.self::$metas['description'].'" />';
        }

        if ('' !== self::$metas['keywords']) {
            echo '<meta name="keywords" content="'.self::$metas['keywords'].'" />';
        }
    }

    private static function metaPivotOffre(string $codeCgt): void
    {
        $language = LocaleHelper::getSelectedLanguage();
        $pivotRepository = PivotContainer::getPivotRepository(WP_DEBUG);
        $offre = $pivotRepository->getOffreByCgtAndParse($codeCgt, Offre::class);

        if (null !== $offre) {
            $base = self::baseTitle('');
            self::$metas['title'] = $offre->nomByLanguage($language).$base;
            self::$metas['description'] = implode(
                ',',
                array_map(
                    fn($description) => $description->value,
                    $offre->descriptionsByLanguage($language)
                )
            );
            $keywords = array_map(
                fn($category) => $category->labelByLanguage($language),
                $offre->categories
            );
            $keywords = array_merge(
                $keywords,
                array_map(
                    fn($tag) => $tag,
                    $offre->tags
                )
            );
            self::$metas['keywords'] = implode(',', $keywords);
        }
    }

    private static function metaHomePage(): void
    {
        $home = self::translate('homepage.title');
        self::$metas['title'] = self::baseTitle($home);
        self::$metas['description'] = get_bloginfo('description', 'display');
        self::$metas['keywords'] = 'Commune, Ville, Marche, Marche-en-Famenne, Famenne, Tourisme, Horeca';
    }

    private static function metaCategory(int $cat_id): void
    {
        $category = get_category($cat_id);
        self::$metas['title'] = self::baseTitle('');
        self::$metas['description'] = self::cleanString($category->description);
        self::$metas['keywords'] = '';
    }

    private static function metaPost(WP_Post $post): void
    {
        self::$metas['title'] = self::baseTitle('');
        self::$metas['description'] = $post->post_excerpt;
        $tags = get_the_category($post->ID);
        self::$metas['keywords'] = implode(
            ',',
            array_map(
                fn($tag) => $tag->name,
                $tags
            )
        );
    }

    private static function metaCartographie(): void
    {
        //todo
    }

    private static function cleanString(string $description): ?string
    {
        $description = trim(strip_tags($description));

        return preg_replace('#"#', '', $description);
    }

    private static function translate(string $text): string
    {
        return LocaleHelper::translate($text);
    }
}
