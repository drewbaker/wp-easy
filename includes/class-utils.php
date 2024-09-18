<?php

/**
 * Class Utils file
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Utils
 *
 * @package WpEasy
 */
class Utils {

	/**
	 * Styles cache to print.
	 *
	 * @var array
	 */
	public static $printed_styles = array();

	/**
	 * Styles cache to print.
	 *
	 * @var array
	 */
	public static $scripts_to_print = array();

	/**
	 * Get route name.
	 *
	 * @return string
	 */
	public static function get_route_name() {
		return get_query_var( 'template' ) ?? 'default';
	}

	/**
	 * Use Outlet template function.
	 */
	public static function use_outlet() {
		$template_file = get_query_var( 'template_file' );
		if ( ! empty( $template_file ) ) {
			include $template_file;
		}
	}

	/**
	 * Use a component, supporting args and loading styles and scripts
	 *
	 * @param string $name  Component Name.
	 * @param array  $props Props to pass to component template.
	 */
	public static function use_component( $name, $props = null ) {
		ob_start();
		Utils::locate_template( $name, 'components', true, false, $props );
		$content = ob_get_clean();

		// Match styles
		preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/si', $content, $styles );

		// Match scripts
		preg_match_all( '/<script\b[^>]*>(.*?)<\/script>/si', $content, $scripts );

		if ( ! empty( $styles[0] ) ) {
			self::enqueue_component_styles( $styles[1] );
			$content = str_replace( $styles[0], '', $content );
		}

		if ( ! empty( $scripts[0] ) ) {
			self::enqueue_component_scripts( $scripts[1] );
			$content = str_replace( $scripts[0], '', $content );
		}

		echo $content;

		wp_reset_postdata();
	}

	/**
	 * Function that works like get_posts, but for children of the current post
	 * Also adds some default values to the post object
	 */
	public static function use_children( $args = [] ) {
		global $post;

		$defaults = [
			'post_type'      => 'any',
			'post_parent'    => $post->ID,
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
		];
		$args     = wp_parse_args( $args, $defaults );

		$posts = new \WP_Query( $args );

		return $posts->posts ?? [];
	}

	/**
	 * Enqueue component inline styles.
	 *
	 * @param array $styles Style array to register.
	 */
	public static function enqueue_component_styles( $styles ) {
		$diff = array_diff( $styles, self::$printed_styles );
		if ( ! empty( $diff ) ) {
			$style_str = join( PHP_EOL, $diff );
			$style_str = self::compile_scss( $style_str );
			printf( '<style>%s</style>', $style_str );

			self::$printed_styles = array_unique( array_merge( self::$printed_styles, $styles ) );
		}
	}

	/**
	 * Return compiled string for SCSS style.
	 *
	 * @param string $style_str Style string
	 *
	 * @return string
	 */
	public static function compile_scss( $style_str ) {
		static $cache = null;

		// Init cache.
		if ( $cache === null ) {
			$cache = get_transient( 'wp_easy_cached_styles' );

			if ( ! is_array( $cache ) ) {
				$cache = array();
			}
		}

		// Check cache first.
		$key = md5( $style_str );
		if ( array_key_exists( $key, $cache ) ) {
			return $cache[ $key ];
		}

		// Compile if not in cache.
		if ( class_exists( 'ScssPhp\ScssPhp\Compiler' ) ) {
			try {
				$compiler  = new \ScssPhp\ScssPhp\Compiler();
				$style_str = $compiler->compileString( $style_str )->getCss();
			} catch ( \Exception $e ) {
				//
			}
		}

		// Store into DB.
		$cache[ $key ] = $style_str;
		set_transient( 'wp_easy_cached_styles', $cache, DAY_IN_SECONDS );

		return $style_str;
	}

	/**
	 * Register inline scripts.
	 *
	 * @param array $scripts Style array to register.
	 */
	public static function enqueue_component_scripts( $scripts ) {
		self::$scripts_to_print = array_unique( array_merge( self::$scripts_to_print, $scripts ) );
	}

	/**
	 * Helper function to return the favicon URL.
	 *
	 * @return string
	 */
	public static function get_favicon_url() {
		if ( has_site_icon() ) {
			$favicon_url = get_site_icon_url();
		} else {
			$favicon_url = get_theme_file_uri() . '/images/favicon.png';
		}
		return $favicon_url;
	}

	/*
	* Get the next or previous sibling page (or any post type)
	*/
	public static function get_adjacent_sibling( $post_id, $direction = 'next', $args = [
		'post_type' => 'page',
		'orderby'   => 'menu_order',
	] ) {
		$post    = get_post( $post_id );
		$is_next = $direction == 'next';
		$is_prev = $direction == 'prev' || $direction == 'previous';

		// Get all siblings, respect supplied args
		$defaults = [
			'post_type'      => get_post_type( $post ),
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $post->post_parent,
			'fields'         => 'ids',
		];
		$args     = wp_parse_args( $args, $defaults );
		$siblings = get_posts( $args );

		// Find where current post is in the array
		$current = array_search( $post->ID, $siblings );

		// Get the adjacent post
		if ( $is_next ) {
			$adjacent_post_id = $siblings[ $current + 1 ] ?? null;
		} else {
			$adjacent_post_id = $siblings[ $current - 1 ] ?? null;
		}

		// Loop around if at the end
		$found = count( $siblings );
		if ( $current == 0 and $is_prev ) {
			$adjacent_post_id = $siblings[ $found - 1 ];
		} elseif ( $current == $found - 1 and $is_next ) {
			$adjacent_post_id = $siblings[0];
		}

		return self::expand_post_object( get_post( $adjacent_post_id ) );
	}

	/**
	 * Adds some useful default values to a post object.
	 *
	 * @param \WP_Post $post_object Post object.
	 *
	 * @return \WP_Post
	 */
	public static function expand_post_object( $post_object ) {
		if ( ! isset( $post_object->id ) and ! is_admin() ) {
			$post_object->id           = $post_object->ID;
			$post_object->url          = get_permalink( $post_object->ID );
			$post_object->thumbnail_id = get_post_thumbnail_id( $post_object->ID );
			$post_object->title        = get_the_title( $post_object->ID );
		}
		return $post_object;
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
	 * inherit from a parent theme can just overload one file. If the template is
	 * not found in either of those, it looks in the theme-compat folder last.
	 *
	 * @param string|array $template_names Template file(s) to search for, in order.
	 * @param string       $directory_name Sub Directory name to look. It can be layouts, templates, or components.
	 * @param bool         $load           If true the template file will be loaded if it is found.
	 * @param bool         $require_once   Whether to require_once or require. Default true.
	 * @param array        $args           Additional arguments passed to the template.
	 *                            Has no effect if $load is false.
	 * @return string The template filename if one is located.
	 */
	public static function locate_template( $template_names, $directory_name = 'templates', $load = false, $require_once = true, $args = array() ) {
		global $wp_stylesheet_path, $wp_template_path;

		if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
			wp_set_template_globals();
		}

		$is_child_theme = is_child_theme();

		$located = '';

		// if $template_names is string convert it to array.
		if ( ! is_array( $template_names ) ) {
			$template_names = array(
				$template_names,
			);
		}

		$sub_directory = '/' . $directory_name . '/';

		// Try to find a template file
		foreach ( (array) $template_names as $template_name ) {

			if ( ! $template_name ) {
				continue;
			}

			// Trim off any slashes from the template name
			$template_name = ltrim( $template_name, '/' );

			if ( file_exists( $wp_stylesheet_path . $sub_directory . $template_name ) ) {
				$located = $wp_stylesheet_path . $sub_directory . $template_name;
				break;
			} elseif ( $is_child_theme && file_exists( $wp_template_path . $sub_directory . $template_name ) ) {
				$located = $wp_template_path . $sub_directory . $template_name;
				break;
			}
		}

		if ( $load && '' !== $located ) {
			load_template( $located, $require_once, $args );
		}

		return $located;
	}

	/**
	 * Get plugin directory path.
	 *
	 * @param string $path_relative Relative path string.
	 *
	 * @return string
	 */
	public static function get_plugin_dir( $path_relative = '' ) {
		return self::get_plugin_instance()->path_to( $path_relative );
	}

	/**
	 * Get plugin directory URL.
	 *
	 * @param string $path_relative Relative path string.
	 *
	 * @return string
	 */
	public static function get_plugin_url( $path_relative = '' ) {
		return self::get_plugin_instance()->url_to( $path_relative );
	}

	/**
	 * Get template directory path.
	 *
	 * @param string $path_relative Relative path string.
	 *
	 * @return string
	 */
	public static function get_template_dir( $path_relative = '' ) {
		return get_template_directory() . '/templates/' . ltrim( $path_relative, '/\\' );
	}

	/**
	 * Get plugin instance.
	 *
	 * @return \WpEasy\Plugin
	 */
	public static function get_plugin_instance() {
		return \wp_easy_get_plugin_instance();
	}

	/**
	 * Use a component, supporting args and loading styles and scripts
	 *
	 * @param string $name  SVG filename, without extension.
	 * @param array  $props HTML attributes to pass to the SVG
	 */
	public static function use_svg( $name, $attrs = null ) {
		$svg = file_get_contents( get_template_directory() . '/images/' . $name . '.svg' );

		// Add any props as HTML attributes to the SVG
		if ( $attrs ) {
			$attrs_output = '';

			foreach ( $attrs as $key => $value ) {
				$attrs_output .= $key . '="' . $value . '" ';
			}

			$svg = str_replace( '<svg ', '<svg ' . $attrs_output, $svg );
		}

		// SEE https://clicknathan.com/web-design/strip-xml-version-from-svg-file-with-php/
		$allowed = [ 'svg', 'g', 'path', 'a', 'animate', 'a', 'animate', 'animateMotion', 'animateTransform', 'circle', 'clipPath', 'defs', 'desc', 'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'filter', 'foreignObject', 'image', 'line', 'linearGradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialGradient', 'rect', 'script', 'set', 'stop', 'style', 'svg', 'switch', 'symbol', 'text', 'textPath', 'title', 'tspan', 'use', 'view' ];
		echo strip_tags( $svg, $allowed );
	}
}
