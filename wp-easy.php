<?php
/**
 * Plugin Name: WP Easy Plugin
 * Plugin URI: https://github.com/drewbaker/wp-easy-plugin
 * Description: A framework for a modern WordPress template, but make it easy.
 * Author: Drew Baker
 * Author URI: https://github.com/drewbaker
 * Text Domain: wp-easy
 * Version: 0.1.0
 * Year: 2024
 */

require_once __DIR__ . '/includes/class-plugin-base.php';
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/helpers.php';

/**
 * Speedo Core Plugin Instance
 *
 * @return \WpEasy\Plugin
 */
function wp_easy_get_plugin_instance() {
	static $wp_easy_plugin;

	if ( is_null( $wp_easy_plugin ) ) {
		$wp_easy_plugin = new \WpEasy\Plugin( __FILE__ );

		if ( function_exists( 'wp_get_environment_type' ) ) {
			$wp_easy_plugin->set_site_environment_type( wp_get_environment_type() );
		}

		$wp_easy_plugin->init();
	}

	return $wp_easy_plugin;
}

wp_easy_get_plugin_instance();
