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
	private static $printed_styles = array();

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Get route name from query var.
	 *
	 * @return string
	 */
	public static function get_route_name() {
		$template_name = get_query_var( 'template_name' );
		return $template_name ? $template_name : 'default';
	}

	/**
	 * Get the theme file path if exists, checking child and parent themes.
	 *
	 * @param string $file    File name or path relative to theme directory.
	 * @param string $sub_dir Optional subdirectory name within the theme.
	 *
	 * @return string|false The absolute path to the file if found, false otherwise.
	 */
	public static function get_theme_file( $file, $sub_dir = '' ) {
		// Load global variables for theme path.
		global $wp_stylesheet_path, $wp_template_path;

		if ( ! isset( $wp_stylesheet_path ) || ! isset( $wp_template_path ) ) {
			wp_set_template_globals();
		}

		// Normalize the file path
		$relative_path = ltrim( $file, '/\\' );
		if ( $sub_dir ) {
			$relative_path = trailingslashit( ltrim( $sub_dir, '/\\' ) ) . $relative_path;
		}

		// First check in child theme
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

		// File not found in either theme
		return false;
	}

	/**
	 * Use layout template.
	 *
	 * Loads a layout template based on the 'layout' query variable.
	 *
	 * @return void
	 */
	public static function use_layout() {
		self::use_file( get_query_var( 'layout' ) );
	}

	/**
	 * Use Outlet template.
	 *
	 * Loads a template file based on the 'template_file' query variable.
	 *
	 * @return void
	 */
	public static function use_outlet() {
		self::use_file( get_query_var( 'template_file' ) );
	}

	/**
	 * Use a component, supporting args and loading styles and scripts
	 *
	 * @param string $name  Component Name.
	 * @param array  $props Props to pass to component template.
	 */
	public static function use_component( $name, $props = null ) {
		$file_path = self::get_theme_file( $name . '.php', 'components' );
		if ( $file_path ) {
			self::use_file( $file_path, $props );
		}
	}

	/**
	 * Use file with optional props.
	 *
	 * @param string $path  Path to file.
	 * @param array  $props Props to pass.
	 */
	private static function use_file( $path, $props = array() ) {
		if ( ! empty( $path ) && file_exists( $path ) ) {
			ob_start();
			load_template( $path, false, $props );
			$content = ob_get_clean();

			self::parse_template( $content, $path );
		}
	}

	/**
	 * Get child posts of the current post.
	 *
	 * Works like get_posts but specifically for children of the current post.
	 * Also adds some default values to the post object.
	 *
	 * @param array $args Optional. Query arguments to override defaults.
	 * @return array Array of post objects.
	 */
	public static function use_children( $args = array() ) {
		global $post;

		// Ensure we have a valid post object
		if ( ! $post || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return array();
		}

		$defaults = array(
			'post_type'      => 'any',
			'post_parent'    => $post->ID,
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
		);
		$args     = wp_parse_args( $args, $defaults );

		$posts = new \WP_Query( $args );

		return $posts->posts ?? array();
	}

	/**
	 * Parse template content, handle <head>, <template>, <style>, and <script> tags.
	 *
	 * @param string $content   Content string.
	 * @param string $file_path File path.
	 *
	 * @return void
	 */
	private static function parse_template( $content, $file_path ) {
		// Handle <head>.
		if ( preg_match( '/<head\b[^>]*>(.*?)<\/head>/si', $content, $matches ) ) {
			$head_content = $matches[1];
			$content      = str_replace( $matches[0], '', $content );
			add_filter( 'wp_easy_custom_head', fn ( $old_content ) => $old_content . $head_content );
		}

		// Handle <head>.
		if ( preg_match( '/<head\b[^>]*>(.*?)<\/head>/si', $content, $matches ) ) {
			$head_content = $matches[1];
			$content      = str_replace( $matches[0], '', $content );
			add_filter( 'wp_easy_custom_head', fn ( $old_content ) => $old_content . $head_content );
		}

		// Handle <template>.
		preg_match_all( '/<template\b[^>]*>(.*?)<\/template>/si', $content, $templates );

		$template_tag_exists = ! empty( $templates[0] );
		if ( $template_tag_exists ) {
			$content_no_template = str_replace( $templates[0], '', $content ); // to avoid double parsing for component styles in template.
			$content             = $templates[1][0];
		} else {
			$content_no_template = $content;
		}

		// Handle <style>.
		preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/si', $content_no_template, $styles );
		if ( ! empty( $styles[0] ) ) {
			self::enqueue_component_styles( $styles[1][0], $file_path );

			// Replace style tag from content string in case template tag is missing.
			if ( ! $template_tag_exists ) {
				$content = str_replace( $styles[0], '', $content );
			}
		}

		// Handle <script>.
		preg_match_all( '/<script\b[^>]*>(.*?)<\/script>/si', $content_no_template, $scripts );
		if ( ! empty( $scripts[0] ) ) {
			self::enqueue_component_scripts( $scripts[1], $file_path );

			// Replace script tag from content string in case template tag is missing.
			if ( ! $template_tag_exists ) {
				$content = str_replace( $scripts[0], '', $content );
			}
		}

		echo $content;
	}

	/**
	 * Get style tag line number.
	 *
	 * @param string $file_path File path string.
	 *
	 * @return int
	 */
	private static function get_style_line_no( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return 0;
		}
		$php_raw_content      = file_get_contents( $file_path );
		$style_position       = strpos( $php_raw_content, '<style' );
		$before_style_content = substr( $php_raw_content, 0, $style_position );

		return count( explode( PHP_EOL, $before_style_content ) ) - 1;
	}

	/**
	 * Enqueue component inline styles.
	 *
	 * @param string $style_str      Style string.
	 * @param string $file_path      Component file path.
	 */
	public static function enqueue_component_styles( $style_str, $file_path ) {
		if ( ! in_array( $file_path, self::$printed_styles ) ) {
			$style_str = self::compile_component_scss( $style_str, $file_path );
			printf( '<style>%s</style>', $style_str );

			self::$printed_styles[] = $file_path;
		}
	}

	/**
	 * Get global scss content.
	 *
	 * @return string
	 */
	public static function get_global_scss() {
		static $global_scss = null;

		// Use static version if exists.
		if ( $global_scss !== null ) {
			return $global_scss;
		}

		$src_files     = glob( get_template_directory() . '/styles/global/*.scss' );
		$src_file_time = max( array_map( 'filemtime', $src_files ) ); // Latest update time.

		$out_file_path = self::get_global_scss_file_path();
		$out_file_time = file_exists( $out_file_path ) ? filemtime( $out_file_path ) : 0;

		// Check if generated file is up to date.
		if ( $src_file_time <= $out_file_time ) {
			$global_scss = file_get_contents( $out_file_path );
			return $global_scss;
		}

		// Generate global css.
		$global_scss = '';
		foreach ( $src_files as $scss_file ) {
			$global_scss .= sprintf( '@import "global/%s";', basename( $scss_file ) );
		}

		// Save to file.
		file_put_contents( $out_file_path, $global_scss );

		self::purge_component_styles();

		return $global_scss;
	}

	/**
	 * Get global css dist directory path.
	 */
	public static function get_global_scss_file_path() {
		$dist_dir = self::get_dist_directory();
		return $dist_dir['css']['dir'] . 'global.scss';
	}

	/**
	 * Purge component styles.
	 */
	public static function purge_component_styles() {
		delete_transient( 'wp_easy_component_styles' );
	}

	/**
	 * Compile SCSS files. Returns generated generate css file url and time(version).
	 *
	 * @return array ['url' => '', 'version' => ''].
	 */
	public static function compile_site_styles() {

		// Early do it to regenerate global scss file.
		$scss_content = self::get_global_scss();

		$src_dir       = get_template_directory() . '/styles/';
		$src_files     = glob( $src_dir . '*.scss' );
		$src_file_time = max( array_map( 'filemtime', $src_files ) ); // current latest update time.

		$global_scss_file = self::get_global_scss_file_path();
		if ( file_exists( $global_scss_file ) ) {
			$src_file_time = max( $src_file_time, filemtime( $global_scss_file ) ); // current latest update time.
		}

		$dist_dir      = self::get_dist_directory();
		$out_file_name = self::is_debug_mode() ? 'general-compiled.css' : 'general-compiled.min.css';
		$out_file_path = $dist_dir['css']['dir'] . $out_file_name;
		$out_file_url  = $dist_dir['css']['url'] . $out_file_name;
		$out_file_time = file_exists( $out_file_path ) ? filemtime( $out_file_path ) : 0;

		// Don't compile if files are not updated.
		if ( $src_file_time <= $out_file_time ) {
			return array(
				'url'     => $out_file_url,
				'version' => $out_file_time,
			);
		}

		foreach ( $src_files as $src_file ) {
			$scss_content .= sprintf( '@import "%s";' . PHP_EOL, basename( $src_file ) );
		}

		try {
			$result = self::run_scss_compiler( $scss_content, $out_file_name . '.map' );
			// Save the compiled file.
			file_put_contents( $out_file_path, $result->getCss() );
			$out_file_time = filemtime( $out_file_path );

		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
			if ( self::is_debug_mode() ) {
				throw $e;
			}
		}

		return array(
			'url'     => $out_file_url,
			'version' => $out_file_time,
		);
	}

	/**
	 * Return compiled string for SCSS style.
	 *
	 * @param string $style_str     Style string
	 * @param bool   $src_file_path Component file path.
	 *
	 * @return string
	 */
	public static function compile_component_scss( $style_str, $src_file_path ) {
		static $cache = null;

		// Append global style to component style for compiling.
		$style_str = self::get_global_scss() . $style_str;

		$key      = md5( $src_file_path );
		$checksum = md5( $style_str ) . ( self::is_debug_mode() ? 'debug' : '' );

		// Init cache.
		if ( $cache === null ) {
			$cache = get_transient( 'wp_easy_component_styles' );

			if ( ! is_array( $cache ) ) {
				$cache = array();
			}
		}

		// Check cache first.
		if ( array_key_exists( $key, $cache ) && $cache[ $key ]['checksum'] === $checksum ) {
			return $cache[ $key ]['content'];
		}

		// Compile if not in cache.
		try {
			if ( self::is_debug_mode() ) {
				$style_str = str_repeat( PHP_EOL, self::get_style_line_no( $src_file_path ) ) . $style_str;
			}
			$style_str = self::run_scss_compiler( $style_str, $key . '.css.map', $src_file_path )->getCss();
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
			if ( self::is_debug_mode() ) {
				throw $e;
			}
		}

		// Store into DB.
		$cache[ $key ] = array(
			'checksum' => $checksum,
			'content'  => $style_str,
		);
		set_transient( 'wp_easy_component_styles', $cache, DAY_IN_SECONDS );

		return $style_str;
	}

	/**
	 * Get SCSS compiler.
	 *
	 * @param string $scss_content     SCSS content to compile.
	 * @param string $map_file_name    CSS map file name.
	 * @param string $source_file_path Source file path.
	 *
	 * @return \ScssPhp\ScssPhp\CompilationResult
	 */
	private static function run_scss_compiler( $scss_content, $map_file_name = '', $source_file_path = null ) {
		if ( ! class_exists( '\ScssPhp\ScssPhp\Compiler' ) ) {
			require_once __DIR__ . '/../vendor/autoload.php';
		}

		$dev_mode = self::is_debug_mode();

		$compiler = new \ScssPhp\ScssPhp\Compiler();
		$compiler->addImportPath( self::get_theme_file( 'styles' ) );
		$compiler->addImportPath( self::get_theme_file( 'global/', 'styles' ) );
		$compiler->setOutputStyle( $dev_mode ? 'expanded' : 'compressed' );

		$map_file_path = '';

		// Set map option if map file name is not empty.
		if ( $map_file_name ) {
			$dist_dir      = self::get_dist_directory();
			$map_file_path = $dist_dir['css']['dir'] . $map_file_name;
			$map_file_url  = $dist_dir['css']['url'] . $map_file_name;

			// Configuration to create the debugging .map file.
			if ( $dev_mode ) {
				$srcmap_data = array(
					'sourceMapWriteTo'  => $map_file_path, // Absolute path to the map file.
					'sourceMapURL'      => $map_file_url, // URL to the map file.
					'sourceMapBasepath' => rtrim( str_replace( '\\', '/', ABSPATH ), '/' ), // Partial route to use a root.
					'sourceRoot'        => dirname( content_url() ), // Where to redirect external files.
					'sourceMapRootpath' => '',
				);
				$compiler->setSourceMap( \ScssPhp\ScssPhp\Compiler::SOURCE_MAP_FILE );
				$compiler->setSourceMapOptions( $srcmap_data );
			}
		}

		$result = $compiler->compileString( $scss_content, $source_file_path );

		// Don't generate map file if file name is empty.
		if ( $map_file_name ) {
			// Save map if a source map has been created
			$map = $result->getSourceMap();
			if ( $map ) {
				file_put_contents( $map_file_path, $map );
			} elseif ( file_exists( $map_file_path ) ) { // Delete if file exists
				unlink( $map_file_path );
			}
		}

		return $result;
	}

	/**
	 * Register inline scripts.
	 *
	 * @param array  $scripts   Style array to register.
	 * @param string $file_path Component file path
	 */
	public static function enqueue_component_scripts( $scripts, $src_file_path ) {
		static $enqueued_files = array();

		// Only enqueue once.
		if ( isset( $enqueued_files[ $src_file_path ] ) ) {
			return;
		}

		$name          = pathinfo( $src_file_path, PATHINFO_FILENAME );
		$type          = basename( dirname( $src_file_path ) );
		$src_filemtime = (int) filemtime( $src_file_path );

		$dist_dir      = self::get_dist_directory();
		$out_name      = sprintf( '%s-%s', $type, $name );
		$out_file_name = $out_name . '.js';
		$out_file_path = $dist_dir['js']['dir'] . $out_file_name;
		$out_file_url  = $dist_dir['js']['url'] . $out_file_name;
		$out_filemtime = file_exists( $out_file_path ) ? (int) filemtime( $out_file_path ) : 0;

		if ( $out_filemtime < $src_filemtime ) {
			// Build component script.
			file_put_contents( $out_file_path, join( PHP_EOL, $scripts ) );
			$out_filemtime = (int) filemtime( $out_file_path );
		}

		// Mark as enqueued.
		$enqueued_files[ $src_file_path ] = true;

		wp_enqueue_script_module( $out_file_name, $out_file_url, array(), $out_filemtime );
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

	/**
	 * Get the next or previous sibling page (or any post type).
	 *
	 * Retrieves the adjacent sibling post based on the specified direction.
	 * Supports looping around to the first/last post when at the beginning/end.
	 *
	 * @param int    $post_id  The ID of the current post.
	 * @param string $direction The direction to look: 'next' or 'prev'/'previous'.
	 * @param array  $args     Optional. Query arguments to override defaults.
	 * @return \WP_Post|false The adjacent post object or false if not found.
	 */
	public static function get_adjacent_sibling( $post_id, $direction = 'next', $args = array(
		'post_type' => 'page',
		'orderby'   => 'menu_order',
	) ) {
		$post = get_post( $post_id );

		// Return false if post doesn't exist
		if ( ! $post ) {
			return false;
		}

		$is_next = $direction === 'next';
		$is_prev = $direction === 'prev' || $direction === 'previous';

		// Get all siblings, respect supplied args
		$defaults = array(
			'post_type'      => get_post_type( $post ),
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_parent'    => $post->post_parent,
			'fields'         => 'ids',
		);
		$args     = wp_parse_args( $args, $defaults );
		$siblings = get_posts( $args );

		// Return false if no siblings found
		if ( empty( $siblings ) ) {
			return false;
		}

		// Find where current post is in the array
		$current = array_search( $post->ID, $siblings );

		// Return false if current post not found in siblings
		if ( $current === false ) {
			return false;
		}

		// Get the adjacent post
		if ( $is_next ) {
			$adjacent_post_id = $siblings[ $current + 1 ] ?? null;
		} else {
			$adjacent_post_id = $siblings[ $current - 1 ] ?? null;
		}

		// Loop around if at the end
		$found = count( $siblings );
		if ( $current === 0 && $is_prev ) {
			$adjacent_post_id = $siblings[ $found - 1 ];
		} elseif ( $current === $found - 1 && $is_next ) {
			$adjacent_post_id = $siblings[0];
		}

		// Return false if no adjacent post found
		if ( ! $adjacent_post_id ) {
			return false;
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
			$post_object->title        = apply_filters( 'the_title', $post_object->post_title, $post_object->ID );
			$post_object->content      = apply_filters( 'the_content', $post_object->post_content );
			$post_object->excerpt      = get_the_excerpt( $post_object ) ?: wp_trim_excerpt( strip_shortcodes( $post_object->post_content ) );
		}
		return $post_object;
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
	 * Include and output an SVG file with optional attributes.
	 *
	 * Loads an SVG file from the theme's images directory and outputs it with
	 * optional HTML attributes. Strips XML declarations and other non-SVG tags
	 * for security and compatibility.
	 *
	 * @param string $name  SVG filename without extension.
	 * @param array  $attrs Optional. HTML attributes to add to the SVG element.
	 * @return void
	 */
	public static function use_svg( $name, $attrs = null ) {
		$svg_path = get_template_directory() . '/images/' . $name . '.svg';

		// Check if file exists
		if ( ! file_exists( $svg_path ) ) {
			error_log( sprintf( 'SVG file not found: %s', $svg_path ) );
			return;
		}

		$svg = file_get_contents( $svg_path );

		// Check if file content is valid
		if ( $svg === false ) {
			error_log( sprintf( 'Failed to read SVG file: %s', $svg_path ) );
			return;
		}

		// Add any props as HTML attributes to the SVG
		if ( $attrs && is_array( $attrs ) ) {
			$attrs_output = '';

			foreach ( $attrs as $key => $value ) {
				$attrs_output .= esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$svg = str_replace( '<svg ', '<svg ' . $attrs_output, $svg );
		}

		// SEE https://clicknathan.com/web-design/strip-xml-version-from-svg-file-with-php/
		$allowed = array( 'svg', 'g', 'path', 'a', 'animate', 'a', 'animate', 'animateMotion', 'animateTransform', 'circle', 'clipPath', 'defs', 'desc', 'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'filter', 'foreignObject', 'image', 'line', 'linearGradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialGradient', 'rect', 'script', 'set', 'stop', 'style', 'svg', 'switch', 'symbol', 'text', 'textPath', 'title', 'tspan', 'use', 'view' );
		echo strip_tags( $svg, $allowed );
	}

	/**
	 * Get dist directory info.
	 *
	 * @return array ['js'=>['dir'=>'', 'url'=>''], 'css'=>['dir'=>'', 'url'=>'']]
	 */
	public static function get_dist_directory() {
		$upload_dir = wp_get_upload_dir();

		$base_dir = $upload_dir['basedir'] . '/wp-easy-dist';
		$base_url = $upload_dir['baseurl'] . '/wp-easy-dist';

		$css_sub_dir = '/css';
		$js_sub_dir  = '/js';

		$full_css_path = $base_dir . $css_sub_dir;
		$full_js_path  = $base_dir . $js_sub_dir;

		if ( ! is_dir( $full_css_path ) ) {
			wp_mkdir_p( $full_css_path );
		}

		if ( ! is_dir( $full_js_path ) ) {
			wp_mkdir_p( $full_js_path );
		}

		return array(
			'css'  => array(
				'dir' => $full_css_path . '/',
				'url' => $base_url . $css_sub_dir . '/',
			),
			'js'   => array(
				'dir' => $full_js_path . '/',
				'url' => $base_url . $js_sub_dir . '/',
			),
			'base' => array(
				'dir' => $base_dir . '/',
				'url' => $base_url . '/',
			),
		);
	}
}
