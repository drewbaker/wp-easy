<?php
/**
 * Class Live_Reload
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Live_Reload
 *
 * @package WpEasy
 */
class Live_Reload {

	/**
	 * Init function
	 */
	public function init() {
		if ( Utils::is_debug_mode() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		add_action( 'wp_ajax_wp-easy-live-reload', array( $this, 'ajax_reload_handler' ) );
		add_action( 'wp_ajax_nopriv_wp-easy-live-reload', array( $this, 'ajax_reload_handler' ) );
	}

	/**
	 * Enqueue live-reload.js
	 */
	public function enqueue_scripts() {
		$delay = apply_filters( 'wp_easy_live_reload_delay', 1000 );
		wp_enqueue_script( 'live-reload', wp_easy_get_plugin_instance()->dir_url . '/includes/libs/php-live-reload/live-reload.js' );
		wp_add_inline_script(
			'live-reload',
			sprintf( 'monitorChanges(%d, false, "%s")', $delay, admin_url( 'admin-ajax.php?action=wp-easy-live-reload' ) ),
			'after'
		);
	}

	public function ajax_reload_handler() {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$dist_dir = Utils::get_dist_directory();

		$extensions = array( 'php', 'js', 'css', 'scss' );
		$dataFile   = $dist_dir['base']['dir'] . 'live-reload.json';
		$watchDir   = get_theme_root();

		$exclude_files = array();
		$exclude_paths = array( '.git', 'images', 'vendor' );

		$excludeFilesFilter = function ( $f ) use ( $dataFile, $exclude_files ) {
			$exclude_files[] = $dataFile;
			return ( ! in_array( $f, $exclude_files ) );
		};

		$excludePathsFilter = function ( $path ) use ( $exclude_paths ) {
			foreach ( $exclude_paths as $exclude ) {
				if ( preg_match( $exclude, $path ) ) {
					return false;
				}
			}
			return true;
		};
		require_once wp_easy_get_plugin_instance()->dir_path . '/includes/libs/php-live-reload/live-reload.php';
		exit;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}
}
