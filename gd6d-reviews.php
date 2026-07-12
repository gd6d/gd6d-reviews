<?php
/**
 * Plugin Name:       Gd6d Reviews
 * Plugin URI:        https://gd6d.fr/
 * Description:       Affiche des avis clients depuis Google Places avec un cache WordPress.
 * Version:           0.4.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Gd6d
 * Author URI:        https://gd6d.fr/
 * Text Domain:       gd6d-reviews
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GD6D_REVIEWS_VERSION', '0.4.0' );
define( 'GD6D_REVIEWS_FILE', __FILE__ );
define( 'GD6D_REVIEWS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GD6D_REVIEWS_URL', plugin_dir_url( __FILE__ ) );

require_once GD6D_REVIEWS_DIR . 'includes/class-gd6d-reviews-plugin.php';
require_once GD6D_REVIEWS_DIR . 'includes/class-gd6d-reviews-settings.php';
require_once GD6D_REVIEWS_DIR . 'includes/class-gd6d-reviews-google-provider.php';
require_once GD6D_REVIEWS_DIR . 'includes/class-gd6d-reviews-shortcode.php';
require_once GD6D_REVIEWS_DIR . 'includes/class-gd6d-reviews-cache.php';

register_activation_hook( __FILE__, array( 'GD6D_Reviews_Plugin', 'activate' ) );

GD6D_Reviews_Plugin::instance()->run();
