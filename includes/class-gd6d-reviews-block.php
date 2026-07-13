<?php
/**
 * Gutenberg block registration.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GD6D_Reviews_Block {
	private static ?GD6D_Reviews_Block $instance = null;
	public static function instance(): GD6D_Reviews_Block { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register(): void { add_action( 'init', array( $this, 'register_block' ) ); }
	public function register_block(): void { register_block_type( GD6D_REVIEWS_DIR . 'blocks/reviews', array( 'render_callback' => array( $this, 'render' ) ) ); }
	public function render( array $attributes = array() ): string {
		return GD6D_Reviews_Renderer::render( $attributes );
	}
	private function __construct() {}
}
