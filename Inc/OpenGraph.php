<?php

namespace VisitMarche\ThemeTail\Inc;

//@see https://www.whitehouse.gov/
//@see https://fr.wordpress.org/plugins/opengraph/
class OpenGraph
{
    public function __construct()
    {
        add_filter('language_attributes', 'opengraph_add_prefix');
        add_filter('wp', 'opengraph_default_metadata');

        add_action('wp_head', 'meta_description');
        add_action('wp_head', 'opengraph_meta_tags');
    }

    /**
     * Helper function to trim text using the same default values for length and
     * 'more' text as wp_trim_excerpt.
     */
    public function __opengraph_trim_text($text)
    {
        $excerpt_length = apply_filters('excerpt_length', 55);
        $excerpt_more = apply_filters('excerpt_more', ' [...]');

        return wp_trim_words($text, $excerpt_length, $excerpt_more);
    }

    /**
     * Add Open Graph XML prefix to <html> element.
     *
     * @uses apply_filters calls 'opengraph_prefixes' filter on RDFa prefix array
     */
    public function opengraph_add_prefix($output)
    {
        $prefixes = [
            'og' => 'http://ogp.me/ns#',
        ];
        $prefixes = apply_filters('opengraph_prefixes', $prefixes);

        $prefix_str = '';
        foreach ($prefixes as $k => $v) {
            $prefix_str .= $k.': '.$v.' ';
        }
        $prefix_str = trim($prefix_str);

        if (preg_match('/(prefix\s*=\s*[\"|\'])/i', $output)) {
            $output = preg_replace('/(prefix\s*=\s*[\"|\'])/i', '${1}'.$prefix_str, $output);
        } else {
            $output .= ' prefix="'.$prefix_str.'"';
        }

        return $output;
    }

    /**
     * Add additional prefix namespaces that are supported by the opengraph plugin.
     */
    public function opengraph_additional_prefixes($prefixes)
    {
        if (is_author()) {
            $prefixes['profile'] = 'http://ogp.me/ns/profile#';
        }

        return $prefixes;
    }

    /**
     * Get the Open Graph metadata for the current page.
     *
     * @uses apply_filters() Calls 'opengraph_{$name}' for each property name
     * @uses apply_filters() Calls 'opengraph_metadata' before returning metadata array
     */
    public function opengraph_metadata()
    {
        $metadata = [];

        // defualt properties defined at http://ogp.me/
        $properties = [
            // required
            'title',
            'type',
            'image',
            'url',
            // optional
            'audio',
            'description',
            'determiner',
            'locale',
            'site_name',
            'video',
        ];

        foreach ($properties as $property) {
            $filter = 'opengraph_'.$property;
            $metadata["og:${property}"] = apply_filters($filter, '');
        }

        return apply_filters('opengraph_metadata', $metadata);
    }

    /**
     * Register filters for default Open Graph metadata.
     */
    public function opengraph_default_metadata(): void
    {
        // core metadata attributes
        add_filter('opengraph_title', 'opengraph_default_title', 5);
        add_filter('opengraph_type', 'opengraph_default_type', 5);
        add_filter('opengraph_image', 'opengraph_default_image', 5);
        add_filter('opengraph_url', 'opengraph_default_url', 5);

        add_filter('opengraph_description', 'opengraph_default_description', 5);
        add_filter('opengraph_locale', 'opengraph_default_locale', 5);
        add_filter('opengraph_site_name', 'opengraph_default_sitename', 5);

        // additional prefixes
        add_filter('opengraph_prefixes', 'opengraph_additional_prefixes');

        // additional profile metadata
        add_filter('opengraph_metadata', 'opengraph_profile_metadata');
    }

    /**
     * Default title property, using the page title.
     */
    public function opengraph_default_title($title)
    {
        if (empty($title)) {
            if (is_singular()) {
                $post = get_queried_object();
                if (property_exists($post, 'post_title') && null !== $post->post_title) {
                    $title = $post->post_title;
                }
            } elseif (is_author()) {
                $author = get_queried_object();
                $title = $author->display_name;
            }
        }

        return $title;
    }

    /**
     * Default type property.
     */
    public function opengraph_default_type($type)
    {
        if (empty($type)) {
            if (is_singular(['post', 'page', 'aside', 'status'])) {
                $type = 'article';
            } elseif (is_author()) {
                $type = 'profile';
            } else {
                $type = 'blog';
            }
        }

        return $type;
    }

    /**
     * Default image property, using the post-thumbnail and any attached images.
     */
    public function opengraph_default_image($image)
    {
        if (empty($image) && is_singular()) {
            $id = get_queried_object_id();
            $image_ids = [];

            // list post thumbnail first if this post has one
            if (\function_exists('has_post_thumbnail') && (is_singular() && has_post_thumbnail($id))) {
                $image_ids[] = get_post_thumbnail_id($id);
            }

            // then list any image attachments
            $attachments = get_children(
                [
                    'post_parent' => $id,
                    'post_status' => 'inherit',
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image',
                    'order' => 'ASC',
                    'orderby' => 'menu_order ID',
                ]
            );
            foreach ($attachments as $attachment) {
                if (! \in_array($attachment->ID, $image_ids, true)) {
                    $image_ids[] = $attachment->ID;
                }
            }

            // get URLs for each image
            $image = [];
            foreach ($image_ids as $id) {
                $thumbnail = wp_get_attachment_image_src($id, 'normal');
                if ($thumbnail) {
                    $image[] = $thumbnail[0];
                }
            }
        }

        return $image;
    }

    /**
     * Default url property, using the permalink for the page.
     */
    public function opengraph_default_url($url)
    {
        if (empty($url)) {
            if (is_singular()) {
                $url = get_permalink();
            } elseif (is_author()) {
                $url = get_author_posts_url(get_queried_object_id());
            }
        }

        return $url;
    }

    /**
     * Default site_name property, using the bloginfo name.
     */
    public function opengraph_default_sitename($name)
    {
        if (empty($name)) {
            $name = get_bloginfo('name');
        }

        return $name;
    }

    /**
     * Default description property, using the excerpt or content for posts, or the
     * bloginfo description.
     */
    public function opengraph_default_description($description)
    {
        if (empty($description)) {
            if (is_singular()) {
                $post = get_queried_object();
                if (! empty($post->post_excerpt)) {
                    $description = strip_tags($post->post_excerpt);
                } else {
                    // fallback to first 55 words of post content.
                    $description = strip_tags(strip_shortcodes($post->post_content));
                    $description = __opengraph_trim_text($description);
                }
            } elseif (is_author()) {
                $id = get_queried_object_id();
                $description = get_user_meta($id, 'description', true);
                $description = __opengraph_trim_text($description);
            } elseif (is_category() && category_description()) {
                $description = category_description();
                $description = __opengraph_trim_text($description);
            } elseif (is_tag() && tag_description()) {
                $description = tag_description();
                $description = __opengraph_trim_text($description);
            } else {
                $description = get_bloginfo('description');
            }
        }

        return $description;
    }

    /**
     * Default locale property, using the WordPress locale.
     */
    public function opengraph_default_locale($locale)
    {
        if (empty($locale)) {
            $locale = get_locale();
        }

        return $locale;
    }

    /**
     * Output Open Graph <meta> tags in the page header.
     */
    public function opengraph_meta_tags(): void
    {
        $metadata = opengraph_metadata();
        foreach ($metadata as $key => $value) {
            if (empty($key) || empty($value)) {
                continue;
            }
            $value = (array) $value;
            foreach ($value as $v) {
                echo '<meta property="'.esc_attr($key).'" content="'.esc_attr($v).'" />'."\n";
            }
        }
    }

    /**
     * Include profile metadata for author pages.
     */
    public function opengraph_profile_metadata($metadata)
    {
        if (is_author()) {
            $id = get_queried_object_id();
            $metadata['profile:first_name'] = get_the_author_meta('first_name', $id);
            $metadata['profile:last_name'] = get_the_author_meta('last_name', $id);
            $metadata['profile:username'] = get_the_author_meta('nicename', $id);
        }

        return $metadata;
    }

    public function meta_description(): void
    {
        $description = opengraph_default_description('');
        if ($description) {
            $description = preg_replace('#"#', '', $description); ?>
            <meta name="description" content="<?php echo $description; ?>"/>
            <?php
        }
    }
}
