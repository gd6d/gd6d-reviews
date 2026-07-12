<?php
/**
 * Uninstall routine.
 *
 * The option is intentionally kept by default to avoid accidental data loss.
 * Define GD6D_REVIEWS_DELETE_DATA as true before uninstalling to remove it.
 *
 * @package Gd6dReviews
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( defined( 'GD6D_REVIEWS_DELETE_DATA' ) && true === GD6D_REVIEWS_DELETE_DATA ) {
	delete_option( 'gd6d_reviews_settings' );
}
