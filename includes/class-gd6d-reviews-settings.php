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

	private const PAGE_SLUG             = 'gd6d-reviews';
	private const TEST_ACTION           = 'gd6d_reviews_test_connection';
	private const CLEAR_CACHE_ACTION    = 'gd6d_reviews_clear_cache';
	private const RESULT_TRANSIENT      = 'gd6d_reviews_admin_result_';

	private static ?GD6D_Reviews_Settings $instance = null;

	public static function instance(): GD6D_Reviews_Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function defaults(): array {
		return array(
			'api_key'    => '',
			'place_id'   => '',
			'cache_days' => 7,
		);
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handle_connection_test' ) );
		add_action( 'admin_post_' . self::CLEAR_CACHE_ACTION, array( $this, 'handle_clear_cache' ) );
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
			'api_key'    => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
			'place_id'   => isset( $input['place_id'] ) ? sanitize_text_field( $input['place_id'] ) : '',
			'cache_days' => isset( $input['cache_days'] )
				? min( 30, max( 1, absint( $input['cache_days'] ) ) )
				: $defaults['cache_days'],
		);
	}

	public function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Renseignez la clé API et le Place ID, enregistrez les réglages, puis testez la connexion.', 'gd6d-reviews' ) . '</p>';
	}

	public function render_field( array $args ): void {
		$options = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );
		$key     = $args['key'];
		$type    = $args['type'];
		$value   = $options[ $key ] ?? '';
		$name    = self::OPTION_NAME . '[' . $key . ']';

		if ( 'number' === $type ) {
			printf(
				'<input class="small-text" type="number" min="1" max="30" name="%1$s" value="%2$d">',
				esc_attr( $name ),
				(int) $value
			);
			echo '<p class="description">' . esc_html__( '7 jours par défaut.', 'gd6d-reviews' ) . '</p>';
			return;
		}

		$disabled = 'api_key' === $key && defined( 'GD6D_REVIEWS_API_KEY' );

		printf(
			'<input class="regular-text" type="%1$s" autocomplete="off" name="%2$s" value="%3$s" %4$s>',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $disabled ? '' : $value ),
			disabled( $disabled, true, false )
		);

		if ( $disabled ) {
			echo '<p class="description">' . esc_html__( 'La clé définie dans wp-config.php est utilisée.', 'gd6d-reviews' ) . '</p>';
		}
	}

	public function handle_connection_test(): void {
		$this->authorize_action( self::TEST_ACTION );

		$result = GD6D_Reviews_Google_Provider::test_connection();

		if ( is_wp_error( $result ) ) {
			$this->store_admin_result(
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				)
			);
		} else {
			$this->store_admin_result(
				array(
					'type' => 'success',
					'data' => $result,
				)
			);
		}

		$this->redirect_to_settings();
	}

	public function handle_clear_cache(): void {
		$this->authorize_action( self::CLEAR_CACHE_ACTION );

		GD6D_Reviews_Cache::delete();

		$this->store_admin_result(
			array(
				'type'    => 'cache-cleared',
				'message' => __( 'Le cache des avis Google a été vidé.', 'gd6d-reviews' ),
			)
		);

		$this->redirect_to_settings();
	}

	private function authorize_action( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’effectuer cette action.', 'gd6d-reviews' ) );
		}

		check_admin_referer( $action );
	}

	private function store_admin_result( array $result ): void {
		set_transient(
			self::RESULT_TRANSIENT . get_current_user_id(),
			$result,
			MINUTE_IN_SECONDS
		);
	}

	private function redirect_to_settings(): void {
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	private function render_admin_result(): void {
		$key    = self::RESULT_TRANSIENT . get_current_user_id();
		$result = get_transient( $key );

		if ( ! is_array( $result ) ) {
			return;
		}

		delete_transient( $key );

		if ( 'error' === ( $result['type'] ?? '' ) ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'Échec de la connexion.', 'gd6d-reviews' ),
				esc_html( $result['message'] ?? '' )
			);
			return;
		}

		if ( 'cache-cleared' === ( $result['type'] ?? '' ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $result['message'] ?? '' )
			);
			return;
		}

		$data = $result['data'] ?? array();
		?>
		<div class="notice notice-success">
			<p><strong><?php esc_html_e( 'Connexion Google réussie.', 'gd6d-reviews' ); ?></strong></p>
			<ul>
				<li>
					<strong><?php esc_html_e( 'Établissement :', 'gd6d-reviews' ); ?></strong>
					<?php echo esc_html( $data['name'] ?? '' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Note :', 'gd6d-reviews' ); ?></strong>
					<?php
					echo isset( $data['rating'] ) && null !== $data['rating']
						? esc_html( number_format_i18n( $data['rating'], 1 ) ) . '/5'
						: esc_html__( 'non disponible', 'gd6d-reviews' );
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Nombre d’avis :', 'gd6d-reviews' ); ?></strong>
					<?php echo esc_html( number_format_i18n( $data['review_count'] ?? 0 ) ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Source :', 'gd6d-reviews' ); ?></strong>
					<?php
					echo esc_html(
						'cache' === ( $data['source'] ?? '' )
							? __( 'Cache WordPress', 'gd6d-reviews' )
							: __( 'Google Places API', 'gd6d-reviews' )
					);
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Dernière synchronisation :', 'gd6d-reviews' ); ?></strong>
					<?php
					echo ! empty( $data['fetched_at'] )
						? esc_html(
							wp_date(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								(int) $data['fetched_at']
							)
						)
						: '—';
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Avis récupérés :', 'gd6d-reviews' ); ?></strong>
					<?php echo esc_html( count( $data['reviews'] ?? array() ) ); ?>
				</li>
			</ul>
		</div>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gd6d Reviews', 'gd6d-reviews' ); ?></h1>
			<?php $this->render_admin_result(); ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'gd6d_reviews_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Outils', 'gd6d-reviews' ); ?></h2>
			<p><?php esc_html_e( 'Testez la connexion ou videz le cache après avoir modifié le Place ID.', 'gd6d-reviews' ); ?></p>

			<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
					<?php wp_nonce_field( self::TEST_ACTION ); ?>
					<?php submit_button( __( 'Tester la connexion Google', 'gd6d-reviews' ), 'secondary', 'submit', false ); ?>
				</form>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::CLEAR_CACHE_ACTION ); ?>">
					<?php wp_nonce_field( self::CLEAR_CACHE_ACTION ); ?>
					<?php submit_button( __( 'Vider le cache', 'gd6d-reviews' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<hr>
			<h2><?php esc_html_e( 'Shortcode', 'gd6d-reviews' ); ?></h2>
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
