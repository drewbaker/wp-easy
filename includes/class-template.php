<?php

/**
 * Class Template file
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * Class Utils
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

		add_action( 'wp_footer', array( $this, 'print_component_scripts' ) );

		add_action( 'the_post', array( $this, 'expand_post' ) );
		add_action( 'the_posts', array( $this, 'expand_posts' ) );
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
	 * Print component inline script
	 */
	public function print_component_scripts() {
		?>
		<script type="text/javascript">
			<?php echo join( PHP_EOL, Utils::$component_scripts ); ?>
		</script>
		<?php
	}

	/**
	 * Filter an array of posts to add some default values to each post object.
	 *
	 * @param \WP_Post[] $posts Posts array.
	 */
	public function expand_posts( $posts ) {
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
	public function expand_post( $post ) {
		$post = Utils::expand_post_object( $post );
	}
}
