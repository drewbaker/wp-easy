<?php

/**
 * Class Override default WP behavior.
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Override
 *
 * @package WpEasy
 */
class Override {

	/**
	 * Init hook
	 */
	public function init() {
		add_action( 'init', array( $this, 'disable_wp_emojicons' ) );
		add_action( 'wp_head', array( $this, 'print_head_meta' ) );

		// Enable SVG uploads
		add_filter( 'upload_mimes', array( $this, 'add_mime_types' ) );

		// Make jQuery $ work globally & remove old jQuery migrate plugin
		add_action( 'wp_head', array( $this, 'enable_jquery_dollar' ) );

		// Custom Login Page and Admin styles.
		add_filter( 'login_headerurl', array( $this, 'custom_loginpage_logo_link' ) );
		add_filter( 'login_headertext', array( $this, 'custom_loginpage_logo_title' ) );
		add_action( 'login_head', array( $this, 'custom_loginpage_styles' ) );
		add_action( 'admin_print_styles', array( $this, 'custom_admin_styles' ) );

		// Add Favicon to Admin and Login pages.
		add_action( 'admin_head', array( $this, 'custom_site_favicon' ) );
		add_action( 'login_head', array( $this, 'custom_site_favicon' ) );

		// Open Graph Tags.
		add_action( 'wp_head', array( $this, 'og_tags' ) );
		add_action( 'wp_body_open', array( $this, 'body_open' ) );

		// Disable comments with the NoCommentsPlease lib.
		require_once Utils::get_plugin_dir( 'includes/libs/no-comments-please.php' );
	}

	/**
	 * Just a hack to allow jQuery to work globally
	 * This way cause conflicts with other JS libraries that us $ as a global variable.
	 */
	public function enable_jquery_dollar() {
		?>
		<!-- A hacky way to allow jQuery to work globally. -->
		<!-- This might cause conflicts with other JS libraries that us $ as a global variable. -->
		<script type="text/javascript">
			window.$ = jQuery;
		</script>
		<?php
	}

	/**
	 * Disable the default WordPress emoji scripts
	 */
	public function disable_wp_emojicons() {
		// all actions related to emojis
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// filter to remove TinyMCE emojis
		add_filter(
			'tiny_mce_plugins',
			function ( $plugins ) {
				if ( is_array( $plugins ) ) {
					return array_diff( $plugins, array( 'wpemoji' ) );
				} else {
					return array();
				}
			}
		);
	}

	/**
	 * Adding generic meta tags to the head
	 * Added here to keep the header.php clean
	 */
	public function print_head_meta() {
		?>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="shortcut icon" href="<?php echo Utils::get_favicon_url(); ?>" />
		<?php
	}

	/**
	 * Allow SVG uploads.
	 * Off be default, only enable on sites that need SVG uploaded.
	 */
	public function add_mime_types( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Set the login page url it links too.
	 *
	 * @param string $url Logo URL
	 * @return string
	 */
	public function custom_loginpage_logo_link( $url ) {
		return get_bloginfo( 'url' );
	}

	/**
	 * Custom login header text for the logo to replace 'WordPress'
	 *
	 * @param string $message Default message text.
	 * @return string
	 */
	public function custom_loginpage_logo_title( $message ) {
		return get_bloginfo( 'name' );
	}

	/**
	 * Enqueue custom login CSS.
	 */
	public function custom_loginpage_styles() {
		wp_enqueue_style(
			'wp-easy-login',
			get_theme_file_uri() . '/styles/login.css',
			null,
			true
		);
	}

	/**
	 * Enqueue custom Admin CSS.
	 */
	public function custom_admin_styles() {
		wp_enqueue_style(
			'wp-easy-admin',
			get_theme_file_uri() . '/styles/admin.css',
			null,
			true
		);
		$custom_css = "
			#wpadminbar {
				--favicon-url: url('" . Utils::get_favicon_url() . "');
			}
		";
		wp_add_inline_style( 'wp-easy-admin', $custom_css );
	}

	/**
	 * Add custom favicon to Admin and Login pages.
	 */
	public function custom_site_favicon() {
		?>
		<link rel="shortcut icon" href="<?php echo Utils::get_favicon_url(); ?>" />
		<?php
	}

	/**
	 * Adding generic open-graph meta tags to the head
	 * Added here to keep the header.php clean
	 */
	public function og_tags() {
		global $post;

		// Defaults to site generic info
		$shared_image = get_theme_file_uri() . '/screenshot.png';
		$summary      = $this->get_summary();
		$url          = get_bloginfo( 'url' );
		$title        = $this->get_title();
		$type         = 'website';
		$site_name    = get_bloginfo( 'name' );

		switch ( true ) {
			case is_home():
			case is_front_page():
			case empty( $post ):
				break;

			case ! empty( $post->video_url ):
				$type = 'video';

			case is_singular( 'post' ):
				$type = 'article';

			case is_single() or is_page():
				$url = get_permalink( $post->ID );

				// Set image to post thumbnail
				$image_id = get_post_thumbnail_id();
				if ( ! empty( $image_id ) ) {
					$image_url    = wp_get_attachment_image_src( $image_id, 'social-preview' );
					$shared_image = $image_url[0];
				}

				break;
		}
		?>
		<meta property="og:title" content="<?php echo $title; ?>" />
		<meta property="og:type" content="<?php echo $type; ?>" />
		<meta property="og:url" content="<?php echo $url; ?>" />
		<meta property="og:image" content="<?php echo $shared_image; ?>" />
		<meta property="og:description" content="<?php echo $summary; ?>" />
		<meta property="og:site_name" content="<?php echo $site_name; ?>" />
		<?php
	}

	/**
	 * Adding some generic site data to start of page for SEO
	 * Added here to keep the header.php clean
	 */
	public function body_open() {
		?>
		<div class="wp-seo">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
			<p><?php echo esc_html( $this->get_summary() ); ?></p>
		</div>
		<?php
	}

	/**
	 * Helper function to get a summary for the current page
	 *
	 * @return string
	 */
	private function get_summary() {
		global $post;

		$summary = get_bloginfo( 'description' );

		if ( is_single() or is_page() ) {
			// Generate an excerpt
			$summary = get_the_excerpt() ?: wp_trim_excerpt( strip_shortcodes( $post->post_content ) );
		}

		// Remove any links, tags or line breaks from summary
		$summary = $summary ?: get_bloginfo( 'description' );
		$summary = strip_tags( $summary );
		$summary = esc_attr( $summary );
		$summary = preg_replace( '!\s+!', ' ', $summary );

		return $summary;
	}

	/**
	 * Helper function to get a title for the current page
	 *
	 * @return string
	 */
	private function get_title() {
		$title = trim( wp_title( '', false ) );

		if ( is_home() or is_front_page() ) {
			$title = get_bloginfo( 'name' );
		}

		return $title;
	}
}
