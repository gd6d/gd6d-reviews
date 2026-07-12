<?php
/**
 * Main plugin bootstrap.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GD6D_Reviews_Plugin {
	private static ?GD6D_Reviews_Plugin $instance = null;

	public static function instance(): GD6D_Reviews_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate(): void {
		$defaults = GD6D_Reviews_Settings::defaults();
		$current  = get_option( GD6D_Reviews_Settings::OPTION_NAME, array() );

		if ( ! is_array( $current ) ) {
			$current = array();
		}

		update_option(
			GD6D_Reviews_Settings::OPTION_NAME,
			wp_parse_args( $current, $defaults ),
			false
		);
	}

	public function run(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_assets' ) );

		GD6D_Reviews_Settings::instance()->register();
		GD6D_Reviews_Shortcode::instance()->register();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'gd6d-reviews',
			false,
			dirname( plugin_basename( GD6D_REVIEWS_FILE ) ) . '/languages'
		);
	}

	public function register_assets(): void {
		wp_register_style(
			'gd6d-reviews',
			GD6D_REVIEWS_URL . 'assets/css/gd6d-reviews.css',
			array(),
			GD6D_REVIEWS_VERSION
		);
	}

	private function __construct() {}
	private function __clone() {}
	public function __wakeup(): void {
		throw new Exception( 'Cannot unserialize singleton.' );
	}
}
