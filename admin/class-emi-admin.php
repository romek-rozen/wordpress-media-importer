<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EMI_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_plugin_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // AJAX handler for scanning content
        add_action( 'wp_ajax_emi_scan_content', [ $this, 'ajax_scan_content' ] );

        // Include scanner class
        require_once EMI_PLUGIN_PATH . 'includes/class-emi-scanner.php';
        require_once EMI_PLUGIN_PATH . 'includes/class-emi-importer.php';

        // AJAX handler for importing a single media item
        add_action( 'wp_ajax_emi_import_media_item', [ $this, 'ajax_import_media_item' ] );
    }

    public function enqueue_scripts( $hook ) {
        // Only load on our plugin page
        if ( 'toplevel_page_emi-scanner' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'emi-admin-script',
            plugin_dir_url( __FILE__ ) . 'js/emi-admin-scripts.js',
            [ 'jquery' ],
            '1.0',
            true
        );

        wp_localize_script(
            'emi-admin-script',
            'emi_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'emi-ajax-nonce' ),
            ]
        );
    }

    public function add_plugin_page() {
        add_menu_page(
            'External Media Importer',
            'External Media',
            'manage_options',
            'emi-scanner',
            [ $this, 'create_admin_page' ],
            'dashicons-download',
            100
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>External Media Importer</h1>
            <p>Select the content types you want to scan for external media.</p>

            <form id="emi-scan-form">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="emi-file-extensions">File Extensions</label></th>
                            <td>
                                <input type="text" id="emi-file-extensions" name="file_extensions" value="jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx" class="regular-text">
                                <p class="description">Enter comma-separated file extensions to scan for.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Content Types</th>
                            <td>
                                <?php $this->render_post_types_checkboxes(); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Post Statuses</th>
                            <td>
                                <?php $this->render_post_status_checkboxes(); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary" id="emi-scan-button">Scan Now</button>
                </p>
            </form>

            <div id="emi-scan-results">
                <!-- Results will be loaded here via AJAX -->
            </div>
        </div>
        <?php
    }

    private function render_post_types_checkboxes() {
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        foreach ( $post_types as $post_type ) {
            if ( $post_type->name === 'attachment' ) {
                continue;
            }
            echo '<label style="margin-right: 15px;">';
            echo '<input type="checkbox" name="post_types[]" value="' . esc_attr( $post_type->name ) . '" checked="checked"> ';
            echo esc_html( $post_type->label );
            echo '</label><br>';
        }
    }

    public function ajax_scan_content() {
        check_ajax_referer( 'emi-ajax-nonce', 'nonce' );

        if ( ! isset( $_POST['form_data'] ) ) {
            wp_send_json_error( [ 'message' => 'Invalid form data.' ] );
        }
        
        $form_data_raw = wp_unslash( $_POST['form_data'] );
        parse_str( $form_data_raw, $form_data );

        if ( empty( $form_data['post_types'] ) ) {
            wp_send_json_error( [ 'message' => 'Please select at least one content type to scan.' ] );
        }

        $post_types = array_map( 'sanitize_text_field', $form_data['post_types'] );
        
        $extensions = 'jpg,jpeg,png,gif,webp,pdf'; // Default extensions
        if ( ! empty( $form_data['file_extensions'] ) ) {
            $extensions = sanitize_text_field( $form_data['file_extensions'] );
        }
        $extensions_array = array_map('trim', explode(',', $extensions));

        if ( empty( $form_data['post_statuses'] ) ) {
            wp_send_json_error( [ 'message' => 'Please select at least one post status to scan.' ] );
        }
        $post_statuses = array_map( 'sanitize_text_field', $form_data['post_statuses'] );

        $debug_info = [];
        $debug_info['post_types'] = $post_types;
        $debug_info['extensions'] = $extensions_array;
        $debug_info['post_statuses'] = $post_statuses;

        $found_media = EMI_Scanner::find_external_media( $post_types, $extensions_array, $post_statuses, $debug_info );

        if ( empty( $found_media ) ) {
            wp_send_json_success( [ 
                'html' => '<p>No external media found.</p>',
                'debug' => $debug_info
            ] );
        }

        // Build HTML response
        ob_start();
        ?>
        <h2>Scan Results</h2>
        <p>Found <?php echo count( $found_media ); ?> external media items.</p>
        <form id="emi-import-form">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="check-column"><input type="checkbox" id="emi-select-all"></th>
                        <th scope="col" style="width: 50px;">Thumbnail</th>
                        <th scope="col">Media URL</th>
                        <th scope="col">Found in Post</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $found_media as $index => $media ) : ?>
                        <tr id="emi-row-<?php echo esc_attr( $index ); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="media_items[]" value="<?php echo esc_attr( $media['url'] ); ?>" data-post-id="<?php echo esc_attr( $media['post_id'] ); ?>" data-row-id="<?php echo esc_attr( $index ); ?>">
                            </th>
                            <td>
                                <?php
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                $file_extension = pathinfo( $media['url'], PATHINFO_EXTENSION );
                                if ( in_array( strtolower( $file_extension ), $image_extensions ) ) {
                                    echo '<img src="' . esc_url( $media['url'] ) . '" style="width: 50px; height: 50px; object-fit: cover;">';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $media['url'] ); ?>" target="_blank"><?php echo esc_html( basename( $media['url'] ) ); ?></a>
                                <div class="row-actions">
                                    <span class="status"></span>
                                </div>
                            </td>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $media['post_id'] ) ); ?>" target="_blank"><?php echo esc_html( $media['post_title'] ); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary" id="emi-import-button">Import Selected Media</button>
                <button type="button" class="button" id="emi-import-all-button">Import All</button>
            </p>
        </form>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( [ 
            'html' => $html,
            'debug' => $debug_info
        ] );
    }

    public function ajax_import_media_item() {
        check_ajax_referer( 'emi-ajax-nonce', 'nonce' );

        if ( ! isset( $_POST['url'] ) || ! isset( $_POST['post_id'] ) ) {
            wp_send_json_error( [ 'message' => 'Missing required data.' ] );
        }

        $url = esc_url_raw( wp_unslash( $_POST['url'] ) );
        $post_id = absint( wp_unslash( $_POST['post_id'] ) );

        $attachment_id = EMI_Importer::import_media( $url, $post_id );

        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( [ 'message' => 'Failed to import ' . $url . ': ' . $attachment_id->get_error_message() ] );
        }

        $new_url = wp_get_attachment_url( $attachment_id );
        if ( ! $new_url ) {
            wp_send_json_error( [ 'message' => 'Failed to get new URL for attachment ' . $attachment_id ] );
        }

        $replaced = EMI_Importer::replace_url_in_content( $url, $new_url, $post_id );

        if ( ! $replaced ) {
             wp_send_json_success( [ 'message' => 'Imported ' . basename($url) . ', but the URL was not found in the post content to replace.' ] );
        }

        wp_send_json_success( [ 'message' => 'Successfully imported and replaced ' . basename($url) . '.' ] );
    }

    private function render_post_status_checkboxes() {
        $stati = get_post_stati( ['public' => true, 'show_in_admin_status_list' => true], 'objects' );
        $stati['draft'] = get_post_status_object('draft');
        $stati['future'] = get_post_status_object('future');
        $stati['pending'] = get_post_status_object('pending');

        foreach ( $stati as $status ) {
            echo '<label style="margin-right: 15px;">';
            echo '<input type="checkbox" name="post_statuses[]" value="' . esc_attr( $status->name ) . '" checked="checked"> ';
            echo esc_html( $status->label );
            echo '</label><br>';
        }
    }
}
