<?php
/**
 * Temporary shortcode used during development.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GD6D_Reviews_Shortcode {
	private static ?GD6D_Reviews_Shortcode $instance = null;

	public static function instance(): GD6D_Reviews_Shortcode {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register(): void {
		add_shortcode( 'gd6d_reviews', array( $this, 'render' ) );
	}

	public function render(): string {
		wp_enqueue_style( 'gd6d-reviews' );
		$options = wp_parse_args( get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ), GD6D_Reviews_Settings::defaults() );

		ob_start();
		?>
		<div class="gd6d-reviews gd6d-reviews--placeholder">
			<strong><?php esc_html_e( 'Gd6d Reviews', 'gd6d-reviews' ); ?></strong>
			<p><?php esc_html_e( 'Le socle fonctionne. La récupération des avis Google arrive au sprint 2.', 'gd6d-reviews' ); ?></p>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<p class="gd6d-reviews__debug">
					<?php
					printf(
						esc_html__( 'Place ID configuré : %s', 'gd6d-reviews' ),
						$options['place_id'] ? esc_html( $options['place_id'] ) : esc_html__( 'non', 'gd6d-reviews' )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function __construct() {}
}
