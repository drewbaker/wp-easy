<?php

/**
 * Class Template file
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Template
 *
 * @package WpEasy
 */
class Template {

	/**
	 * Init function
	 */
	public function init() {
		add_filter( 'body_class', array( $this, 'body_class' ) );

		// Register our custom query var
		add_filter( 'query_vars', array( $this, 'query_vars' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		add_action( 'the_post', array( $this, 'filter_post' ) );
		add_action( 'the_posts', array( $this, 'filter_posts' ) );
	}

	/**
	 * Add custom body classes to the front-end of our application so we can style accordingly.
	 *
	 * @param array $classes.
	 *
	 * @return array
	 */
	public function body_class( $classes ) {
		$classes[] = 'route-' . Utils::get_route_name();
		return $classes;
	}

	/**
	 * Register our custom query var.
	 *
	 * @param array
	 *
	 * @return array
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'template';
		return $query_vars;
	}

	/**
	 * Enqueue Custom Styles
	 */
	public function enqueue_styles() {

		// Build site SCSS file.
		$compiled_site_style = Utils::compile_site_styles();
		wp_enqueue_style( 'wp-easy-scss-compiled', $compiled_site_style['url'], [], $compiled_site_style['version'] );

		// Enqueue all CSS files in styles directory.
		$css_files = glob( get_template_directory() . '/styles/' . '*.css' );
		sort( $css_files, SORT_STRING | SORT_FLAG_CASE );

		foreach ( $css_files as $css_file ) {
			$handle = 'wp-easy-' . basename( $css_file );
			$handle = str_replace( [ '.' ], '-', $handle );

			wp_enqueue_style( $handle, get_theme_file_uri() . '/styles/' . basename( $css_file ), [], null );
		}
	}

	/**
	 * Enqueue Custom Scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );

		// Enqueue all JS files in /js/libs
		$this->auto_enqueue_libs();

		// Register all modules.
		$directories = [
			''       => '/scripts',
			'utils/' => '/scripts/utils',
		];

		$handles = array();
		foreach ( $directories as $namespace => $path ) {
			$files = glob( get_template_directory() . $path . '/*.js' );
			foreach ( $files as $file ) {
				$handle    = $namespace . basename( $file, '.js' );
				$handles[] = $handle;
				wp_register_script_module( $handle, get_theme_file_uri() . $path . '/' . basename( $file ) );
			}
		}

		// deregister first because auto registred doesn't have dependency.
		wp_deregister_script_module( 'main' );
		$handles = array_diff( $handles, [ 'main' ] );

		// Enqueue wp-easy scripts.
		wp_enqueue_script_module( 'main', get_theme_file_uri() . '/scripts/main.js', $handles );
		wp_enqueue_script_module( 'fonts' );

		// Setup JS variables in scripts
		wp_localize_script(
			'jquery',
			'serverVars',
			array(
				'themeURL' => get_theme_file_uri(),
				'homeURL'  => home_url(),
			)
		);
	}

	/**
	 * Helper function to enqueue all JS files in /js/libs
	 */
	private function auto_enqueue_libs() {
		$libs_dir = get_template_directory() . '/scripts/libs/';
		$libs     = glob( $libs_dir . '*.js' );
		foreach ( $libs as $lib ) {
			// Remove file extension and version numbers for the handle name of the script
			$handle = basename( $lib, '.js' );
			$handle = str_replace( [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'js', '..' ], '', $handle );
			$handle = rtrim( $handle, '.' );
			wp_enqueue_script( $handle, get_theme_file_uri() . '/scripts/libs/' . basename( $lib ), [], null, [] );
		}
	}

	/**
	 * Filter an array of posts to add some default values to each post object.
	 *
	 * @param \WP_Post[] $posts Posts array.
	 */
	public function filter_posts( $posts ) {
		foreach ( $posts as $post ) {
			$post = Utils::expand_post_object( $post );
		}
		return $posts;
	}

	/**
	 * Filter a single post to add some default values to the post object.
	 *
	 * @param \WP_Post $post
	 */
	public function filter_post( $post ) {
		$post = Utils::expand_post_object( $post );
	}
}
