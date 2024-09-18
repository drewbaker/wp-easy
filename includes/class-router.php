<?php
/**
 * Class Router file
 *
 * @package WpEasy
 */

namespace WpEasy;

use \WpEasy\Libs\Path_To_Regexp;

/**
 * Class Router
 *
 * @package WpEasy
 */
class Router {
	public function init() {
		add_action( 'init', array( $this, 'load_router' ) );
	}

	// Load router.
	public function load_router() {
		// Load global variables for theme path.
		global $wp_stylesheet_path, $wp_template_path;

		if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
			wp_set_template_globals();
		}

		$routes = array();

		// Check child theme and theme root directory for router.php
		if ( file_exists( $wp_stylesheet_path . '/router.php' ) ) {
			$routes = include $wp_stylesheet_path . '/router.php';
		} elseif ( file_exists( $wp_template_path . '/router.php' ) ) {
			$routes = include $wp_template_path . '/router.php';
		}

		// Apply filter wp_easy_routes.
		$routes = apply_filters( 'wp_easy_routes', $routes );

		// Routes validation.
		if ( empty( $routes ) || ! is_array( $routes ) ) {
			return;
		}

		$keys          = [];
		$template_name = '';
		$layout_name   = 'default';

		foreach ( $routes as $name => $params ) {
			$path    = $params['path'] ?? $params;
			$re      = Path_To_Regexp::convert( $path, $keys );
			$matches = [];
			$match   = preg_match( $re, $_SERVER['REQUEST_URI'], $matches );

			if ( $match ) {
				$template_name = $params['template'] ?? $name;
				$layout_name   = $params['layout'] ?? 'default';
				break;
			}
		}

		// If non matching template, dismiss it.
		if ( empty( $template_name ) ) {
			return;
		}

		$template = Utils::locate_template( $template_name . '.php', 'templates' );
		if ( ! $template ) {
			$error = new \WP_Error(
				'missing_template',
				sprintf( __( 'The file for the template %s does not exist', 'wp-easy-router' ), '<b>' . $template_name . '</b>' )
			);
			echo $error->get_error_message();

			return;
		}

		$layout = Utils::locate_template( $layout_name, 'layouts' );
		if ( ! $layout ) {
			$error = new \WP_Error(
				'missing_template',
				sprintf( __( 'The file for the layout %s does not exist', 'wp-easy-router' ), '<b>' . $layout_name . '</b>' )
			);
			echo $error->get_error_message();

			return;
		}

		// Now replace the template
		add_filter(
			'template_include',
			function ( $old_template ) use ( $template, $template_name, $layout ) {
				// Set our custom query var
				set_query_var( 'template', $template_name );
				set_query_var( 'template_file', $template ); // Caching it to avoid duplicate locate_template() call in use_outlet().

				return $layout;
			},
			1
		);
	}
}
