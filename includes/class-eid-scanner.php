<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EID_Scanner {

    public static function find_external_media( $post_types, $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], $post_statuses = ['publish'], &$debug_info = [] ) {
        $found_media = [];
        $debug_info['scanner_log'] = [];

        if ( empty($extensions) ) {
            return $found_media;
        }

        // Sanitize extensions for regex
        $sanitized_extensions = array_map( 'sanitize_text_field', $extensions );
        $regex_extensions = implode( '|', $sanitized_extensions );

        $args = [
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'post_status'    => $post_statuses,
        ];

        $query = new WP_Query( $args );

        $debug_info['wp_query_args'] = $args;
        $debug_info['post_count'] = $query->post_count;
        $debug_info['scanner_log'][] = "Query found " . $query->post_count . " posts.";

        if ( $query->have_posts() ) {
            $processed_posts = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                // Use raw post_content to get unfiltered content, including Gutenberg blocks
                $content = $query->post->post_content;
                
                // Limit debug content length
                if ($processed_posts < 1) { // Only log content for the first post
                    $debug_info['scanner_log'][] = "Processing Post #" . get_the_ID() . ": " . get_the_title();
                    $debug_info['scanner_log'][] = "Raw Content (first 500 chars): " . substr($content, 0, 500);
                }

                // Regex to find any URL within quotes that ends with a specific extension, allowing for query strings
                $pattern = '/[\'"](https?:\/\/[^\'"]*?\.(?:' . $regex_extensions . '))[^\'"]*[\'"]/i';
                $debug_info['regex_pattern'] = $pattern;

                preg_match_all( $pattern, $content, $matches );
                
                if ($processed_posts < 1 && !empty($matches[1])) {
                     $debug_info['scanner_log'][] = "Regex found " . count($matches[1]) . " potential matches in first post.";
                     $debug_info['first_post_matches'] = $matches[1];
                }

                $all_matches = $matches[1];

                // Also check for a featured image
                if ( has_post_thumbnail() ) {
                    $featured_image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
                    if ( $featured_image_url ) {
                        $all_matches[] = $featured_image_url;
                    }
                }

                $processed_posts++;

                foreach ( $all_matches as $url ) {
                    // Check if the URL is external (doesn't contain the site URL)
                    if ( strpos( $url, home_url() ) === false ) {
                        $found_media[] = [
                            'url'        => esc_url_raw( $url ),
                            'post_id'    => get_the_ID(),
                            'post_title' => get_the_title(),
                        ];
                    }
                }
            }
            wp_reset_postdata();
        }

        // Remove duplicates
        $found_media = array_map("unserialize", array_unique(array_map("serialize", $found_media)));

        return $found_media;
    }
}
