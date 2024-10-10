<?php

/**
 * Class Settings file
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Settings
 *
 * @package WpEasy
 */
class Settings {

	/**
	 * Init function
	 */
	public function init() {
		add_action( 'admin_bar_menu', array( $this, 'add_purge_link' ), 1000 );
		add_action( 'init', array( $this, 'purge_cache' ) );
	}

	/**
	 * Adds purge link to adminbar menu.
	 */
	public function add_purge_link( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return; // security check.
		}

		$current_url = ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// Parse the URL into components
		$url_parts = parse_url( $current_url );

		// Parse existing query parameters
		parse_str( $url_parts['query'] ?? '', $query_params );

		// Add/modify the parameter
		$query_params['purge-wp-easy'] = '1';

		$new_query = http_build_query( $query_params );
		$new_url   = $url_parts['path'] . ( empty( $new_query ) ? '' : '?' . $new_query );

		$admin_bar->add_menu(
			array(
				'id'    => 'wp-easy-purge-component-styles',
				'title' => 'Purge WP Easy',
				'href'  => $new_url,
			)
		);
	}

	/**
	 * Purge cache.
	 */
	public function purge_cache() {
		if ( isset( $_GET['purge-wp-easy'] ) ) {
			Utils::purge_component_styles();
		}
	}
}
