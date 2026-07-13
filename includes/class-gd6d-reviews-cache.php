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

	public static function get( string $language = 'fr' ) {
		return get_transient( self::get_key( $language ) );
	}

	public static function set( array $data, string $language = 'fr' ): bool {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);
		$days = min( 30, max( 1, absint( $settings['cache_days'] ?? 7 ) ) );

		return set_transient( self::get_key( $language ), $data, $days * DAY_IN_SECONDS );
	}

	public static function delete(): bool {
		$deleted = delete_transient( self::LEGACY_KEY );

		foreach ( array( 'fr', 'en' ) as $language ) {
			$deleted = delete_transient( self::get_key( $language ) ) || $deleted;
		}

		return $deleted;
	}

	private static function get_key( string $language ): string {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);
		$place_id = sanitize_text_field( $settings['place_id'] ?? '' );
		$language = in_array( $language, array( 'fr', 'en' ), true ) ? $language : 'fr';

		return self::KEY_PREFIX . md5( $place_id . '|' . $language );
	}
}
