<?php

namespace VisitMarche\ThemeTail\Inc;

use WP_Error;

class SecurityConfig
{
    public function __construct()
    {
        // remove junk from head
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'start_post_rel_link', 10);
        remove_action('wp_head', 'parent_post_rel_link', 10);
        remove_action('wp_head', 'adjacent_posts_rel_link', 10);
        // REMOVE WP EMOJI
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        // Disable REST API link tag
        remove_action('wp_head', 'rest_output_link_wp_head', 10);

        // Disable oEmbed Discovery Links
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

        // Disable REST API link in HTTP headers
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // $this->secureApi();//todo activate en prod!
    }

    public function secureApi(): void
    {
        add_filter(
            'rest_authentication_errors',
            function ($result) {
                // If a previous authentication check was applied,
                // pass that result along without modification.
                if (true === $result || is_wp_error($result)) {
                    return $result;
                }

                // No authentication has been performed yet.
                // Return an error if user is not logged in.
                if (! is_user_logged_in()) {
                    return new WP_Error(
                        'rest_not_logged_in',
                        __('You are not currently logged in.'),
                        [
                            'status' => 401,
                        ]
                    );
                }

                // Our custom authentication check should have no effect
                // on logged-in requests
                return $result;
            }
        );
    }
}
