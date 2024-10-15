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
	 * Is debug mode enabled.
	 *
	 * @return bool
	 */
	public static function is_debug_mode() {
		return defined( 'WP_DEBUG' ) && true === WP_DEBUG;
	}

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

			self::parse_template( $content, $template_file );
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

		self::parse_template( $content, $path );
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
	 * Parse template file.
	 *
	 * @param string $content   Content string.
	 * @param string $file_path File path.
	 *
	 * @return void
	 */
	private static function parse_template( $content, $file_path ) {
		preg_match_all( '/<template\b[^>]*>(.*?)<\/template>/si', $content, $templates );

		$content_no_template = $content;

		if ( ! empty( $templates[0] ) ) {
			$content_no_template = str_replace( $templates[0], '', $content ); // to avoid double parsing for component styles in template.
			$content             = str_replace( $templates[0], $templates[1], $content );
		}

		preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/si', $content_no_template, $styles );
		if ( ! empty( $styles[0] ) ) {
			self::enqueue_component_styles( $styles[1], $file_path );
			$content = str_replace( $styles[0], '', $content );
		}

		// Match scripts
		preg_match_all( '/<script\b[^>]*>(.*?)<\/script>/si', $content_no_template, $scripts );
		if ( ! empty( $scripts[0] ) ) {
			self::enqueue_component_scripts( $scripts[1], $file_path );
			$content = str_replace( $scripts[0], '', $content );
		}

		echo $content;
	}

	/**
	 * Enqueue component inline styles.
	 *
	 * @param array  $styles    Style array to register.
	 * @param string $file_path Component file path.
	 */
	public static function enqueue_component_styles( $styles, $file_path ) {
		if ( ! in_array( $file_path, self::$printed_styles ) ) {
			$style_str = join( PHP_EOL, $styles );
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
		$out_file_time = filemtime( $out_file_path );

		// Check if generated file is up to date.
		if ( $src_file_time <= $out_file_time ) {
			$global_scss = file_get_contents( $out_file_path );
			return $global_scss;
		}

		// Generate global css.
		$global_scss = '';
		foreach ( $src_files as $scss_file ) {
			$global_scss .= file_get_contents( $scss_file ) . PHP_EOL;
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
	 * @param bool $dev_mode Is dev mode.
	 *
	 * @return array ['url' => '', 'version' => ''].
	 */
	public static function compile_site_styles( $dev_mode = false ) {

		// Early do it to regenerate global scss file.
		$scss_content = self::get_global_scss();

		$src_dir       = get_template_directory() . '/styles/';
		$src_files     = glob( $src_dir . '*.scss' );
		$src_file_time = max( array_map( 'filemtime', $src_files ) ); // current latest update time.
		$src_file_time = max( $src_file_time, filemtime( self::get_global_scss_file_path() ) ); // current latest update time.

		$dist_dir      = self::get_dist_directory();
		$out_file_name = apply_filters( 'wp_easy_global_style_name', 'general-compiled.css' );
		$out_file_path = $dist_dir['css']['dir'] . $out_file_name;
		$out_file_url  = $dist_dir['css']['url'] . $out_file_name;
		$out_file_time = filemtime( $out_file_path );

		// Don't compile if files are not updated.
		if ( file_exists( $out_file_path ) && $src_file_time <= $out_file_time ) {
			return array(
				'url'     => $out_file_url,
				'version' => $out_file_time,
			);
		}

		foreach ( $src_files as $src_file ) {
			$scss_content .= file_get_contents( $src_file ) . PHP_EOL;
		}

		try {
			self::load_scss_compiler();
			$compiler = new \ScssPhp\ScssPhp\Compiler();
			$compiler->addImportPath( self::get_theme_file( 'global/', 'styles' ) );
			$compiler->setOutputStyle( $dev_mode ? 'expanded' : 'compressed' );

			// Configuration to create the debugging .map file.
			if ( $dev_mode ) {
				$srcmap_data = [
					'sourceMapWriteTo'  => $out_file_path . '.map', // Absolute path to the map file.
					'sourceMapURL'      => $out_file_url . '.map', // URL to the map file.
					'sourceMapBasepath' => rtrim( ABSPATH, '/' ), // Partial route to use a root.
					'sourceRoot'        => dirname( content_url() ), // Where to redirect external files.
				];
				$compiler->setSourceMap( \ScssPhp\ScssPhp\Compiler::SOURCE_MAP_FILE );
				$compiler->setSourceMapOptions( $srcmap_data );
			}

			$result = $compiler->compileString( $scss_content );

			// Save the compiled file.
			file_put_contents( $out_file_path, $result->getCss() );
			$out_file_time = filemtime( $out_file_path );

			// Save map if a source map has been created
			if ( $map = $result->getSourceMap() ) {
				file_put_contents( $out_file_path . '.map', $map );
			} elseif ( file_exists( $out_file_path . '.map' ) ) { // Delete if file exists
				unlink( $out_file_path . '.map' );
			}
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
		$checksum = md5( $style_str );

		// Init cache.
		if ( $cache === null ) {
			$cache = get_transient( 'wp_easy_component_styles' );

			if ( ! is_array( $cache ) ) {
				$cache = array();
			}
		}

		// Check cache first.
		if ( array_key_exists( $key, $cache ) && $cache[ $key ]['checksum'] == $checksum ) {
			return $cache[ $key ]['content'];
		}

		// Compile if not in cache.
		try {
			self::load_scss_compiler();
			$compiler = new \ScssPhp\ScssPhp\Compiler();
			$compiler->addImportPath( self::get_theme_file( 'global/', 'styles' ) );

			$style_str = $compiler->compileString( $style_str )->getCss();
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

	private static function load_scss_compiler() {
		if ( ! class_exists( '\ScssPhp\ScssPhp\Compiler' ) ) {
			require_once __DIR__ . '/../vendor/autoload.php';
		}
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
		$out_filemtime = (int) filemtime( $out_file_path );

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
			$post_object->title        = apply_filters( 'the_title', $post_object->post_title, $post_object->ID );
			$post_object->content      = apply_filters( 'the_content', $post_object->post_content );
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

	/**
	 * Get dist directory info.
	 *
	 * @return array ['js'=>['dir'=>'', 'url'=>''], 'css'=>['dir'=>'', 'url'=>'']]
	 */
	public static function get_dist_directory() {
		$upload_dir = wp_get_upload_dir();

		$base_dir = $upload_dir['basedir'];
		$base_url = $upload_dir['baseurl'];

		$css_sub_dir = '/wp-easy-dist/css';
		$js_sub_dir  = '/wp-easy-dist/js';

		$full_css_path = $base_dir . $css_sub_dir;
		$full_js_path  = $base_dir . $js_sub_dir;

		if ( ! is_dir( $full_css_path ) ) {
			wp_mkdir_p( $full_css_path );
		}

		if ( ! is_dir( $full_js_path ) ) {
			wp_mkdir_p( $full_js_path );
		}

		return array(
			'css' => array(
				'dir' => $full_css_path . '/',
				'url' => $base_url . $css_sub_dir . '/',
			),
			'js'  => array(
				'dir' => $full_js_path . '/',
				'url' => $base_url . $js_sub_dir . '/',
			),
		);
	}
}
