<?php
/**
 * Plugin Name: Wordpress External Media Importer
 * Description: Scans posts for external media and imports them into the media library.
 * Version: 1.0
 * Author: Roman Rozenberger & Cline
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EID_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Include the admin class.
require_once EID_PLUGIN_PATH . 'admin/class-eid-admin.php';

// Initialize the admin class.
new EID_Admin();
