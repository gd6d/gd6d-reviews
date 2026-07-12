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
	private const TEST_ACTION = 'gd6d_reviews_test_connection';
	private const TEST_RESULT_TRANSIENT = 'gd6d_reviews_test_result_';

	private static ?GD6D_Reviews_Settings $instance = null;

	public static function instance(): GD6D_Reviews_Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults(): array {
		return array(
			'api_key'      => '',
			'place_id'     => '',
			'cache_days'   => 7,
			'review_count' => 3,
		);
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handle_connection_test' ) );
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
		echo '<p>' . esc_html__( 'Renseignez la clé API et le Place ID, enregistrez les réglages, puis testez la connexion.', 'gd6d-reviews' ) . '</p>';
	}

	public function render_field( array $args ): void {
		$options = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );
		$key     = $args['key'];
		$type    = $args['type'];
		$value   = $options[ $key ] ?? '';
		$name    = self::OPTION_NAME . '[' . $key . ']';

		if ( 'number' === $type ) {
			$max = 'cache_days' === $key ? 30 : 5;
			printf(
				'<input class="small-text" type="number" min="1" max="%1$d" name="%2$s" value="%3$d">',
				$max,
				esc_attr( $name ),
				(int) $value
			);
			if ( 'cache_days' === $key ) {
				echo '<p class="description">' . esc_html__( '7 jours par défaut.', 'gd6d-reviews' ) . '</p>';
			}
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
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’effectuer cette action.', 'gd6d-reviews' ) );
		}

		check_admin_referer( self::TEST_ACTION );

		$result = GD6D_Reviews_Google_Provider::test_connection();
		$key    = self::TEST_RESULT_TRANSIENT . get_current_user_id();

		if ( is_wp_error( $result ) ) {
			set_transient(
				$key,
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				MINUTE_IN_SECONDS
			);
		} else {
			set_transient(
				$key,
				array(
					'success' => true,
					'data'    => $result,
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	private function render_test_result(): void {
		$key    = self::TEST_RESULT_TRANSIENT . get_current_user_id();
		$result = get_transient( $key );

		if ( ! is_array( $result ) ) {
			return;
		}

		delete_transient( $key );

		if ( empty( $result['success'] ) ) {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'Échec de la connexion.', 'gd6d-reviews' ),
				esc_html( $result['message'] ?? '' )
			);
			return;
		}

		$data = $result['data'];
		?>
		<div class="notice notice-success">
			<p><strong><?php esc_html_e( 'Connexion Google réussie.', 'gd6d-reviews' ); ?></strong></p>
			<ul>
				<li><strong><?php esc_html_e( 'Établissement :', 'gd6d-reviews' ); ?></strong> <?php echo esc_html( $data['name'] ); ?></li>
				<li><strong><?php esc_html_e( 'Note :', 'gd6d-reviews' ); ?></strong> <?php echo null !== $data['rating'] ? esc_html( number_format_i18n( $data['rating'], 1 ) ) . '/5' : esc_html__( 'non disponible', 'gd6d-reviews' ); ?></li>
				<li><strong><?php esc_html_e( 'Nombre d’avis :', 'gd6d-reviews' ); ?></strong> <?php echo esc_html( number_format_i18n( $data['review_count'] ) ); ?></li>
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
			<p><?php esc_html_e( 'Version 0.2.0 : test de connexion à Google Places.', 'gd6d-reviews' ); ?></p>
			<?php $this->render_test_result(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'gd6d_reviews_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Test de connexion', 'gd6d-reviews' ); ?></h2>
			<p><?php esc_html_e( 'Enregistrez d’abord les réglages, puis lancez le test.', 'gd6d-reviews' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
				<?php wp_nonce_field( self::TEST_ACTION ); ?>
				<?php submit_button( __( 'Tester la connexion Google', 'gd6d-reviews' ), 'secondary', 'submit', false ); ?>
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
