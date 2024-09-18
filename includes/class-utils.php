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
	private static $component_styles = array();

	/**
	 * Styles cache to print.
	 *
	 * @var array
	 */
	public static $component_scripts = array();

	/**
	 * Get route name.
	 *
	 * @return string
	 */
	public static function get_route_name() {
		return get_query_var( 'template' ) ?? 'default';
	}

	/**
	 * Check and get theme file path if exists. Check child theme and parent theme.
	 *
	 * @param string $file    File name.
	 * @param string $sub_dir Directory name.
	 *
	 * @return string|false
	 */
	public static function get_theme_file( $file, $sub_dir = '' ) {
		// Load global variables for theme path.
		global $wp_stylesheet_path, $wp_template_path;

		if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
			wp_set_template_globals();
		}

		$relative_path = ltrim( $file, '/\\' );
		if ( $sub_dir ) {
			$relative_path = trailingslashit( ltrim( $sub_dir, '/\\' ) ) . $relative_path;
		}

		$path = trailingslashit( $wp_stylesheet_path ) . $relative_path;
		if ( file_exists( $path ) ) {
			return $path;
		}

		/**
		 * In child theme case check parent theme.
		 */
		if ( is_child_theme() ) {
			$path = trailingslashit( $wp_template_path ) . $relative_path;
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Use Outlet template function.
	 */
	public static function use_outlet() {
		$template_file = get_query_var( 'template_file' );
		if ( ! empty( $template_file ) ) {
			ob_start();
			include $template_file;
			$content = ob_get_clean();

			self::parse_template( $content );
		}
	}

	/**
	 * Use a component, supporting args and loading styles and scripts
	 *
	 * @param string $name  Component Name.
	 * @param array  $props Props to pass to component template.
	 */
	public static function use_component( $name, $props = null ) {
		$path = Utils::get_theme_file( $name . '.php', 'components' );

		if ( ! $path ) {
			return;
		}

		ob_start();
		load_template( $path, false, $props );
		$content = ob_get_clean();

		self::parse_template( $content );

		// wp_reset_postdata();
	}

	/**
	 * Parse template file.
	 *
	 * @param string $content Content string.
	 *
	 * @return void
	 */
	private static function parse_template( $content ) {
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
	 * Use a component, supporting args and loading styles and scripts
	 *
	 * @param string $name  SVG filename, without extension.
	 * @param array  $props HTML attributes to pass to the SVG
	 */
	public static function use_svg( $name, $attrs = null ) {
		$path = self::get_theme_file( $name . '.svg', 'images' );
		if ( empty( $path ) ) {
			return;
		}

		$svg = file_get_contents( $path );

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

	/**
	 * Enqueue component inline styles.
	 *
	 * @param array $styles Style array to register.
	 */
	public static function enqueue_component_styles( $styles ) {
		$diff = array_diff( $styles, self::$component_styles );
		if ( ! empty( $diff ) ) {
			$style_str = join( PHP_EOL, $diff );
			$style_str = self::compile_scss( $style_str );
			printf( '<style>%s</style>', $style_str );

			self::$component_styles = array_unique( array_merge( self::$component_styles, $styles ) );
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
				$style_str = self::get_scss_compiler()->compileString( $style_str )->getCss();
			} catch ( \Exception $e ) {
				//
			}
		}

		// Store into DB.
		$cache[ $key ] = $style_str;
		set_transient( 'wp_easy_cached_styles', $cache, DAY_IN_SECONDS );

		return $style_str;
	}

	public static function get_scss_compiler() {
		static $scss_compiler = null;
		if ( empty( $scss_compiler ) ) {
			$scss_compiler = new \ScssPhp\ScssPhp\Compiler();
			$scss_compiler->addImportPath( self::get_theme_file( 'base/', 'styles' ) );
		}
		return $scss_compiler;
	}

	/**
	 * Register inline scripts.
	 *
	 * @param array $scripts Style array to register.
	 */
	public static function enqueue_component_scripts( $scripts ) {
		self::$component_scripts = array_unique( array_merge( self::$component_scripts, $scripts ) );
	}

	/**
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
	 * Get plugin instance.
	 *
	 * @return \WpEasy\Plugin
	 */
	public static function get_plugin_instance() {
		return \wp_easy_get_plugin_instance();
	}

}
