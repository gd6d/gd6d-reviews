<?php
/**
 * Plugin settings.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GD6D_Reviews_Settings {
	public const OPTION_NAME = 'gd6d_reviews_settings';
	private const PAGE_SLUG  = 'gd6d-reviews';

	private static ?GD6D_Reviews_Settings $instance = null;

	public static function instance(): GD6D_Reviews_Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults(): array {
		return array(
			'api_key'     => '',
			'place_id'    => '',
			'cache_days'  => 7,
			'review_count'=> 3,
		);
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( GD6D_REVIEWS_FILE ), array( $this, 'add_action_link' ) );
	}

	public function add_settings_page(): void {
		add_options_page(
			__( 'Gd6d Reviews', 'gd6d-reviews' ),
			__( 'Avis Google', 'gd6d-reviews' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'gd6d_reviews_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'gd6d_reviews_google',
			__( 'Connexion Google Places', 'gd6d-reviews' ),
			array( $this, 'render_section_intro' ),
			self::PAGE_SLUG
		);

		$this->add_field( 'api_key', __( 'Clé API', 'gd6d-reviews' ), 'password' );
		$this->add_field( 'place_id', __( 'Place ID', 'gd6d-reviews' ), 'text' );
		$this->add_field( 'cache_days', __( 'Durée du cache', 'gd6d-reviews' ), 'number' );
		$this->add_field( 'review_count', __( 'Nombre d’avis affichés', 'gd6d-reviews' ), 'number' );
	}

	private function add_field( string $key, string $label, string $type ): void {
		add_settings_field(
			'gd6d_reviews_' . $key,
			$label,
			array( $this, 'render_field' ),
			self::PAGE_SLUG,
			'gd6d_reviews_google',
			array(
				'key'  => $key,
				'type' => $type,
			)
		);
	}

	public function sanitize( $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'api_key'      => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
			'place_id'     => isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : '',
			'cache_days'   => isset( $input['cache_days'] ) ? min( 30, max( 1, absint( $input['cache_days'] ) ) ) : $defaults['cache_days'],
			'review_count' => isset( $input['review_count'] ) ? min( 5, max( 1, absint( $input['review_count'] ) ) ) : $defaults['review_count'],
		);
	}

	public function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Renseignez les identifiants Google. La connexion API et le cache seront ajoutés au sprint suivant.', 'gd6d-reviews' ) . '</p>';
	}

	public function render_field( array $args ): void {
		$options = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );
		$key     = $args['key'];
		$type    = $args['type'];
		$value   = $options[ $key ] ?? '';
		$name    = self::OPTION_NAME . '[' . $key . ']';

		if ( 'number' === $type ) {
			$min = 'cache_days' === $key ? 1 : 1;
			$max = 'cache_days' === $key ? 30 : 5;
			printf(
				'<input class="small-text" type="number" min="%1$d" max="%2$d" name="%3$s" value="%4$d">',
				$min,
				$max,
				esc_attr( $name ),
				(int) $value
			);
			if ( 'cache_days' === $key ) {
				echo '<p class="description">' . esc_html__( '7 jours par défaut.', 'gd6d-reviews' ) . '</p>';
			}
			return;
		}

		printf(
			'<input class="regular-text" type="%1$s" autocomplete="off" name="%2$s" value="%3$s">',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $value )
		);

		if ( 'api_key' === $key && defined( 'GD6D_REVIEWS_API_KEY' ) ) {
			echo '<p class="description">' . esc_html__( 'Une clé définie dans wp-config.php sera prioritaire à partir du sprint API.', 'gd6d-reviews' ) . '</p>';
		}
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gd6d Reviews', 'gd6d-reviews' ); ?></h1>
			<p><?php esc_html_e( 'Version 0.1.0 : socle de l’extension et réglages.', 'gd6d-reviews' ); ?></p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'gd6d_reviews_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Shortcode de test', 'gd6d-reviews' ); ?></h2>
			<code>[gd6d_reviews]</code>
		</div>
		<?php
	}

	public function add_action_link( array $links ): array {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Réglages', 'gd6d-reviews' ) . '</a>' );
		return $links;
	}

	private function __construct() {}
}
