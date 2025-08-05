<?php
/**
 * Plugin Name: External Media Importer
 * Description: Scans posts for external media and imports them into the media library.
 * Version: 1.0
 * Author: Roman Rozenberger & Cline
 * Author URI: https://rozenberger.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: external-media-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EMI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Include the admin class.
require_once EMI_PLUGIN_PATH . 'admin/class-emi-admin.php';

// Initialize the admin class.
new EMI_Admin();
