<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GD6D_Reviews_Cache {

	const KEY = 'gd6d_reviews_google';
	const DURATION = WEEK_IN_SECONDS;

	/**
	 * Retourne les données en cache.
	 *
	 * @return array|false
	 */
	public static function get() {
		return get_transient( self::KEY );
	}

	/**
	 * Enregistre les données.
	 *
	 * @param array $data Données Google.
	 * @return bool
	 */
	public static function set( array $data ) {
		return set_transient(
			self::KEY,
			$data,
			self::DURATION
		);
	}

	/**
	 * Supprime le cache.
	 *
	 * @return bool
	 */
	public static function delete() {
		return delete_transient( self::KEY );
	}
}