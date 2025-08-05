<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// We need these files for media_sideload_image()
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

class EID_Importer {

    /**
     * Imports a single media file from a URL.
     *
     * @param string $url The URL of the file to import.
     * @param int    $post_id The post ID to attach the media to.
     * @return int|WP_Error The attachment ID on success, or a WP_Error object on failure.
     */
    public static function import_media( $url, $post_id = 0 ) {
        $image_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];
        $file_extension = pathinfo( $url, PATHINFO_EXTENSION );

        // Use media_sideload_image for known image types
        if ( in_array( strtolower( $file_extension ), $image_extensions ) ) {
            $attachment_id = media_sideload_image( $url, $post_id, null, 'id' );
            return $attachment_id;
        }

        // For other file types, use a more manual approach
        $file_name = basename( parse_url( $url, PHP_URL_PATH ) );
        
        // Download file to temp folder
        $tmp_file = download_url( $url, 30 ); // 30 second timeout
        if ( is_wp_error( $tmp_file ) ) {
            return $tmp_file;
        }

        $file = [
            'name'     => $file_name,
            'tmp_name' => $tmp_file,
        ];

        // Upload the file to the media library
        $attachment_id = media_handle_sideload( $file, $post_id );

        // Clean up temp file
        @unlink( $tmp_file );

        return $attachment_id;
    }

    /**
     * Replaces an old URL with a new one in the post content.
     *
     * @param string $old_url The original external URL.
     * @param string $new_url The new internal URL.
     * @param int    $post_id The ID of the post to update.
     * @return bool True on success, false on failure.
     */
    public static function replace_url_in_content( $old_url, $new_url, $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        $content = $post->post_content;
        $new_content = str_replace( $old_url, $new_url, $content );

        if ( $content === $new_content ) {
            return false; // No replacement was made.
        }

        $update_post_args = [
            'ID'           => $post_id,
            'post_content' => $new_content,
        ];

        $result = wp_update_post( $update_post_args, true );

        return ! is_wp_error( $result );
    }
}
