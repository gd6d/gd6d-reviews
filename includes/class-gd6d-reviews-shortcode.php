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

		wp_enqueue_style( 'gd6d-reviews' );

		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);

		$reviews = isset( $data['reviews'] ) && is_array( $data['reviews'] )
			? array_slice( $data['reviews'], 0, (int) $settings['review_count'] )
			: array();

		$rating       = isset( $data['rating'] ) ? (float) $data['rating'] : 0.0;
		$review_count = isset( $data['review_count'] ) ? (int) $data['review_count'] : 0;

		ob_start();
		?>
		<section class="gd6d-reviews" aria-label="<?php esc_attr_e( 'Avis Google', 'gd6d-reviews' ); ?>">
			<header class="gd6d-reviews__header">
				<div class="gd6d-reviews__summary">
					<p class="gd6d-reviews__eyebrow"><?php esc_html_e( 'Avis Google', 'gd6d-reviews' ); ?></p>
					<div class="gd6d-reviews__score">
						<strong class="gd6d-reviews__rating"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong>
						<span class="gd6d-reviews__stars" aria-hidden="true">★★★★★</span>
						<span class="gd6d-reviews__sr-only">
							<?php
							printf(
								/* translators: %s: rating out of five. */
								esc_html__( 'Note de %s sur 5.', 'gd6d-reviews' ),
								esc_html( number_format_i18n( $rating, 1 ) )
							);
							?>
						</span>
					</div>
					<p class="gd6d-reviews__count">
						<?php
						printf(
							/* translators: %s: number of Google reviews. */
							esc_html( _n( '%s avis Google', '%s avis Google', $review_count, 'gd6d-reviews' ) ),
							esc_html( number_format_i18n( $review_count ) )
						);
						?>
					</p>
				</div>

				<?php if ( ! empty( $data['maps_url'] ) ) : ?>
					<a class="gd6d-reviews__button" href="<?php echo esc_url( $data['maps_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Voir tous les avis', 'gd6d-reviews' ); ?>
					</a>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $reviews ) ) : ?>
				<ul class="gd6d-reviews__list">
					<?php foreach ( $reviews as $review ) : ?>
						<?php
						$author      = $review['authorAttribution']['displayName'] ?? __( 'Client Google', 'gd6d-reviews' );
						$author_url  = $review['authorAttribution']['uri'] ?? '';
						$author_photo = $review['authorAttribution']['photoUri'] ?? '';
						$text        = $review['originalText']['text'] ?? $review['text']['text'] ?? '';
						$item_rating = isset( $review['rating'] ) ? max( 0, min( 5, (int) $review['rating'] ) ) : 0;
						$date        = $this->format_relative_date( $review['publishTime'] ?? '' );
						?>
						<li class="gd6d-reviews__item">
							<article class="gd6d-review">
								<header class="gd6d-review__header">
									<?php if ( $author_photo ) : ?>
										<img class="gd6d-review__avatar" src="<?php echo esc_url( $author_photo ); ?>" alt="" width="48" height="48" loading="lazy" decoding="async">
									<?php else : ?>
										<span class="gd6d-review__avatar gd6d-review__avatar--fallback" aria-hidden="true"><?php echo esc_html( $this->get_initial( $author ) ); ?></span>
									<?php endif; ?>

									<div class="gd6d-review__identity">
										<h3 class="gd6d-review__author">
											<?php if ( $author_url ) : ?>
												<a href="<?php echo esc_url( $author_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $author ); ?></a>
											<?php else : ?>
												<?php echo esc_html( $author ); ?>
											<?php endif; ?>
										</h3>

										<?php if ( $date ) : ?>
											<p class="gd6d-review__date"><?php echo esc_html( $date ); ?></p>
										<?php endif; ?>
									</div>
								</header>

								<p class="gd6d-review__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Note de %d sur 5', 'gd6d-reviews' ), $item_rating ) ); ?>">
									<span aria-hidden="true"><?php echo esc_html( str_repeat( '★', $item_rating ) ); ?></span>
								</p>

								<?php if ( $text ) : ?>
									<p class="gd6d-review__text"><?php echo esc_html( $text ); ?></p>
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

	private function format_relative_date( string $publish_time ): string {
		if ( '' === $publish_time ) {
			return '';
		}

		$published_timestamp = strtotime( $publish_time );

		if ( false === $published_timestamp ) {
			return '';
		}

		$seconds = max( 0, current_time( 'timestamp' ) - $published_timestamp );

		if ( $seconds >= YEAR_IN_SECONDS ) {
			$value = (int) floor( $seconds / YEAR_IN_SECONDS );
			return sprintf( _n( 'Il y a %d an', 'Il y a %d ans', $value, 'gd6d-reviews' ), $value );
		}

		if ( $seconds >= MONTH_IN_SECONDS ) {
			$value = (int) floor( $seconds / MONTH_IN_SECONDS );
			return sprintf( _n( 'Il y a %d mois', 'Il y a %d mois', $value, 'gd6d-reviews' ), $value );
		}

		if ( $seconds >= WEEK_IN_SECONDS ) {
			$value = (int) floor( $seconds / WEEK_IN_SECONDS );
			return sprintf( _n( 'Il y a %d semaine', 'Il y a %d semaines', $value, 'gd6d-reviews' ), $value );
		}

		if ( $seconds >= DAY_IN_SECONDS ) {
			$value = (int) floor( $seconds / DAY_IN_SECONDS );
			return sprintf( _n( 'Il y a %d jour', 'Il y a %d jours', $value, 'gd6d-reviews' ), $value );
		}

		return __( 'Aujourd’hui', 'gd6d-reviews' );
	}

	private function get_initial( string $author ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_strtoupper( mb_substr( trim( $author ), 0, 1 ) );
		}

		return strtoupper( substr( trim( $author ), 0, 1 ) );
	}

	private function __construct() {}
}
