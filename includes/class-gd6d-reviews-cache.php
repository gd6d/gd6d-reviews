<?php
/**
 * Google reviews cache.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GD6D_Reviews_Cache {
	private const LEGACY_KEY = 'gd6d_reviews_google';
	private const KEY_PREFIX = 'gd6d_reviews_google_';

	/**
	 * Retourne les données en cache pour le Place ID courant.
	 *
	 * @return array|false
	 */
	public static function get() {
		return get_transient( self::get_key() );
	}

	/**
	 * Enregistre les données pour la durée configurée.
	 *
	 * @param array $data Données Google.
	 * @return bool
	 */
	public static function set( array $data ): bool {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);
		$days = min( 30, max( 1, absint( $settings['cache_days'] ?? 7 ) ) );

		return set_transient(
			self::get_key(),
			$data,
			$days * DAY_IN_SECONDS
		);
	}

	/**
	 * Supprime le cache courant et l’ancienne clé utilisée avant la v0.6.0.
	 *
	 * @return bool
	 */
	public static function delete(): bool {
		$deleted_current = delete_transient( self::get_key() );
		$deleted_legacy  = delete_transient( self::LEGACY_KEY );

		return $deleted_current || $deleted_legacy;
	}

	/**
	 * Construit une clé distincte pour chaque établissement.
	 */
	private static function get_key(): string {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);
		$place_id = sanitize_text_field( $settings['place_id'] ?? '' );

		return self::KEY_PREFIX . md5( $place_id );
	}
}
