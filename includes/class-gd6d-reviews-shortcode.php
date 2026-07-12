<?php
/**
 * Google reviews shortcode.
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
		$data = GD6D_Reviews_Google_Provider::get_place_data();

		if ( is_wp_error( $data ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				return sprintf(
					'<p class="gd6d-reviews__error">%s</p>',
					esc_html( $data->get_error_message() )
				);
			}

			return '';
		}

		$reviews = isset( $data['reviews'] ) && is_array( $data['reviews'] )
			? $data['reviews']
			: array();

		ob_start();
		?>
		<section class="gd6d-reviews" aria-label="<?php esc_attr_e( 'Avis Google', 'gd6d-reviews' ); ?>">

			<header class="gd6d-reviews__header">
				<h2 class="gd6d-reviews__title">
					<?php echo esc_html( $data['name'] ?? '' ); ?>
				</h2>

				<p class="gd6d-reviews__summary">
					<span aria-hidden="true">★★★★★</span>
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: %s: rating out of five. */
							esc_html__( 'Note de %s sur 5.', 'gd6d-reviews' ),
							esc_html( number_format_i18n( $data['rating'] ?? 0, 1 ) )
						);
						?>
					</span>

					<strong>
						<?php echo esc_html( number_format_i18n( $data['rating'] ?? 0, 1 ) ); ?>/5
					</strong>

					<span>
						<?php
						printf(
							/* translators: %s: number of reviews. */
							esc_html( _n( '%s avis Google', '%s avis Google', $data['review_count'] ?? 0, 'gd6d-reviews' ) ),
							esc_html( number_format_i18n( $data['review_count'] ?? 0 ) )
						);
						?>
					</span>
				</p>

				<?php if ( ! empty( $data['maps_url'] ) ) : ?>
					<p>
						<a href="<?php echo esc_url( $data['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Voir tous les avis sur Google', 'gd6d-reviews' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $reviews ) ) : ?>
				<ul class="gd6d-reviews__list">
					<?php foreach ( $reviews as $review ) : ?>
						<?php
						$author = $review['authorAttribution']['displayName'] ?? __( 'Client Google', 'gd6d-reviews' );
						$text = $review['originalText']['text']
						?? $review['text']['text']
						?? '';
						$rating = isset( $review['rating'] ) ? (int) $review['rating'] : 0;
						$date   = $review['relativePublishTimeDescription'] ?? '';
						?>
						<li class="gd6d-reviews__item">
							<article class="gd6d-review">
								<header class="gd6d-review__header">
									<h3 class="gd6d-review__author">
										<?php echo esc_html( $author ); ?>
									</h3>

									<?php if ( $date ) : ?>
										<p class="gd6d-review__date">
											<?php echo esc_html( $date ); ?>
										</p>
									<?php endif; ?>
								</header>

								<p class="gd6d-review__rating">
									<span aria-hidden="true">
										<?php echo esc_html( str_repeat( '★', $rating ) ); ?>
									</span>
									<span class="screen-reader-text">
										<?php
										printf(
											/* translators: %d: rating out of five. */
											esc_html__( 'Note de %d sur 5.', 'gd6d-reviews' ),
											$rating
										);
										?>
									</span>
								</p>

								<?php if ( $text ) : ?>
									<p class="gd6d-review__text">
										<?php echo esc_html( $text ); ?>
									</p>
								<?php endif; ?>
							</article>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</section>
		<?php

		return (string) ob_get_clean();
	}

	private function __construct() {}
}