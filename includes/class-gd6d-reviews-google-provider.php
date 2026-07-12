<?php
/**
 * Google Places API provider.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GD6D_Reviews_Google_Provider {

	private const API_BASE_URL = 'https://places.googleapis.com/v1/places/';

	/**
	 * Retourne les données Google depuis le cache ou depuis l’API.
	 *
	 * @return array|WP_Error
	 */
	public static function get_place_data() {
		$cached_data = GD6D_Reviews_Cache::get();

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			$cached_data['source'] = 'cache';

			return $cached_data;
		}

		$data = self::fetch_from_google();

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		GD6D_Reviews_Cache::set( $data );

		$data['source'] = 'google';

		return $data;
	}

	/**
	 * Teste la connexion Google.
	 *
	 * Pour le moment, le test utilise aussi le cache afin de vérifier
	 * le fonctionnement complet du circuit.
	 *
	 * @return array|WP_Error
	 */
	public static function test_connection() {
		return self::get_place_data();
	}

	/**
	 * Récupère les données directement depuis Google Places.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_from_google() {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);

		$api_key = defined( 'GD6D_REVIEWS_API_KEY' )
			? GD6D_REVIEWS_API_KEY
			: $settings['api_key'];

		$place_id = $settings['place_id'];

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'gd6d_reviews_missing_api_key',
				__( 'La clé API Google est manquante.', 'gd6d-reviews' )
			);
		}

		if ( empty( $place_id ) ) {
			return new WP_Error(
				'gd6d_reviews_missing_place_id',
				__( 'Le Place ID est manquant.', 'gd6d-reviews' )
			);
		}

		$url = add_query_arg(
	array(
		'languageCode' => 'fr',
	),
	self::API_BASE_URL . rawurlencode( $place_id )
);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-Goog-Api-Key'   => $api_key,
					'X-Goog-FieldMask' => 'id,displayName,rating,userRatingCount,googleMapsUri,reviews',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'gd6d_reviews_http_error',
				sprintf(
					/* translators: %s: HTTP error message. */
					__( 'Google n’a pas pu être contacté : %s', 'gd6d-reviews' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code ) {
			$message = isset( $body['error']['message'] )
				? sanitize_text_field( $body['error']['message'] )
				: __( 'Réponse inattendue de Google.', 'gd6d-reviews' );

			return new WP_Error(
				'gd6d_reviews_google_api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: Google error message. */
					__( 'Erreur Google (%1$d) : %2$s', 'gd6d-reviews' ),
					$status_code,
					$message
				)
			);
		}

		if ( ! is_array( $body ) || empty( $body['displayName']['text'] ) ) {
			return new WP_Error(
				'gd6d_reviews_invalid_response',
				__( 'La réponse de Google est incomplète.', 'gd6d-reviews' )
			);
		}

return array(
    'id'           => sanitize_text_field( $body['id'] ?? $place_id ),
    'name'         => sanitize_text_field( $body['displayName']['text'] ),
    'rating'       => isset( $body['rating'] ) ? (float) $body['rating'] : null,
    'review_count' => isset( $body['userRatingCount'] ) ? absint( $body['userRatingCount'] ) : 0,
    'maps_url'     => isset( $body['googleMapsUri'] ) ? esc_url_raw( $body['googleMapsUri'] ) : '',
    'reviews'      => $body['reviews'] ?? array(),
    'fetched_at'   => time(),
);
	}
}