<?php

/**
 * No Comments, Please!
 *
 * @package     No_Comments_Please
 * @version     1.0
 * @author      Marco Andrei Kichalowsky <marco.andrei@marcoandrei.com>
 *
 * @wordpress-plugin
 * Plugin Name:         No Comments, Please!
 * Plugin URI:          https://wordpress.org/plugins/no-comments-please/
 * Description:         Deactivate and hide all Comments interface parts and all Comments features in WordPress.
 * Version:             1.0
 * Author:              Marco Andrei Kichalowsky
 * Author URI:          http://marcoandrei.com/
 * License:             GPLv2
 * Text Domain:         no-comments-please
 * Domain Path:         /languages
 * Requires PHP:        7.0
 * Requires at least:   5.0
 *
 *
 * This plugin brings together several snippets to disable comments in WordPress that I found on the web. It is a form of recognition to all the developers who helped build this software.
 *
 * It is recommended to install this plugin in the mu-plugins folder ("must-use plug-ins") in order to always run it before the common plug-ins.
 *
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action(
	'admin_init',
	function () {
		// Redirect any user trying to access comments page
		global $pagenow;

		if ( $pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php' ) {
			wp_redirect( admin_url() );
			exit;
		}

		// Remove comments metabox from dashboard
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

		// Disable support for comments and trackbacks in post types
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}
);

// Close comments on the front-end
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );

// Hide existing comments
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

// Remove comments page and option page in menu
add_action(
	'admin_menu',
	function () {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}
);

// Remove comments links from admin bar
add_action(
	'init',
	function () {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}
);

// Remove menu from admin bar
add_action(
	'wp_before_admin_bar_render',
	function () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'comments' );
	}
);
