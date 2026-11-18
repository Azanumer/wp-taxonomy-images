<?php
/**
 * Uninstall WP Taxonomy Images.
 *
 * Removes the plugin's settings and every term's image assignment.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wti_settings' );

global $wpdb;
$wpdb->delete(
	$wpdb->termmeta,
	array( 'meta_key' => 'wti_image_id' ),
	array( '%s' )
);
