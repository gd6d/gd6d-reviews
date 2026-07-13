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

	public static function get_place_data( string $language = 'fr' ) {
		$language    = self::normalize_language( $language );
		$cached_data = GD6D_Reviews_Cache::get( $language );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			$cached_data['source'] = 'cache';
			return $cached_data;
		}

		$data = self::fetch_from_google( $language );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		GD6D_Reviews_Cache::set( $data, $language );
		$data['source'] = 'google';
		return $data;
	}

	public static function test_connection() {
		return self::get_place_data( 'fr' );
	}

	private static function fetch_from_google( string $language ) {
		$settings = wp_parse_args(
			get_option( GD6D_Reviews_Settings::OPTION_NAME, array() ),
			GD6D_Reviews_Settings::defaults()
		);

		$api_key = defined( 'GD6D_REVIEWS_API_KEY' ) ? GD6D_REVIEWS_API_KEY : $settings['api_key'];
		$place_id = $settings['place_id'];

		if ( empty( $api_key ) ) {
			return new WP_Error( 'gd6d_reviews_missing_api_key', __( 'La clé API Google est manquante.', 'gd6d-reviews' ) );
		}
		if ( empty( $place_id ) ) {
			return new WP_Error( 'gd6d_reviews_missing_place_id', __( 'Le Place ID est manquant.', 'gd6d-reviews' ) );
		}

		$url = add_query_arg(
			array( 'languageCode' => $language ),
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
			return new WP_Error( 'gd6d_reviews_http_error', sprintf( __( 'Google n’a pas pu être contacté : %s', 'gd6d-reviews' ), $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code ) {
			$message = isset( $body['error']['message'] ) ? sanitize_text_field( $body['error']['message'] ) : __( 'Réponse inattendue de Google.', 'gd6d-reviews' );
			return new WP_Error( 'gd6d_reviews_google_api_error', sprintf( __( 'Erreur Google (%1$d) : %2$s', 'gd6d-reviews' ), $status_code, $message ) );
		}

		if ( ! is_array( $body ) || empty( $body['displayName']['text'] ) ) {
			return new WP_Error( 'gd6d_reviews_invalid_response', __( 'La réponse de Google est incomplète.', 'gd6d-reviews' ) );
		}

		return array(
			'id'           => sanitize_text_field( $body['id'] ?? $place_id ),
			'name'         => sanitize_text_field( $body['displayName']['text'] ),
			'rating'       => isset( $body['rating'] ) ? (float) $body['rating'] : null,
			'review_count' => isset( $body['userRatingCount'] ) ? absint( $body['userRatingCount'] ) : 0,
			'maps_url'     => isset( $body['googleMapsUri'] ) ? esc_url_raw( $body['googleMapsUri'] ) : '',
			'reviews'      => isset( $body['reviews'] ) && is_array( $body['reviews'] ) ? $body['reviews'] : array(),
			'fetched_at'   => time(),
			'language'     => $language,
		);
	}

	private static function normalize_language( string $language ): string {
		return in_array( $language, array( 'fr', 'en' ), true ) ? $language : 'fr';
	}
}
