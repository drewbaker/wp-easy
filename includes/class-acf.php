<?php
/**
 * ACF custom location rules for Custom Post Types.
 *
 * @package WpEasy
 */

namespace WpEasy;

/**
 * ACF rules for custom post type
 */
class Acf {

	private const PREFIX         = 'wpeasy'; // Custom location rule prefix.
	private const SURFIX_PARENT  = 'parent'; // Custom location rule "CPT Parent" surfix.
	private const SURFIX_TREE    = 'tree'; // Custom location rule "CPT belongs to tree" surfix.
	private const POST_IS_TREE   = 'post-is-tree'; // Location rule name for post is tree.
	private const PAGE_IS_TREE   = 'page-is-tree'; // Location rule name for page is tree.
	private const POST_HAS_CHILD = 'post-has-child'; // Location rule name for post has child.
	private const PAGE_HAS_CHILD = 'page-has-child'; // Location rule name for page has child.

	/**
	 * Init.
	 */
	public function init() {
		add_filter( 'acf/location/rule_types', array( $this, 'location_rule_types' ) );

		add_filter( 'acf/location/rule_values', array( $this, 'rule_values' ), 10, 2 );
		add_filter( 'acf/location/rule_match', array( $this, 'rule_match' ), 10, 2 );
	}

	/**
	 * Custom ACF filter rules.
	 * This adds the label to the first <select> in the Field Group screen.
	 *
	 * @param array $types Rule types.
	 *
	 * @return array
	 */
	public function location_rule_types( $types ) {
		// Adds Parent and Tree rule types.
		foreach ( $this->custom_post_types() as $cpt ) {
			$types[ $cpt['label'] ] = array(
				$this->key_parent( $cpt['name'] ) => $cpt['label'] . ' Parent',
				$this->key_tree( $cpt['name'] )   => $cpt['label'] . ' belongs to tree',
			);
		}

		// Adds Tree rule types to Post and Page.
		$types['Post'][ self::POST_IS_TREE ] = 'Post belongs to tree';
		$types['Page'][ self::PAGE_IS_TREE ] = 'Page belongs to tree';

		// Adds Tree rule types to Post and Page.
		$types['Post'][ self::POST_HAS_CHILD ] = 'Post has children';
		$types['Page'][ self::PAGE_HAS_CHILD ] = 'Page has children';

		return $types;
	}

	/**
	 * Custom ACF filter values.
	 *
	 * @param array $values Rule values.
	 * @param array $rule   Rule.
	 *
	 * @return array
	 */
	public function rule_values( $values, $rule ) {
		switch ( $rule['param'] ) {
			case self::POST_IS_TREE:
			case self::PAGE_IS_TREE:
				return $this->rules_values_tree_location( $values, $rule );
			case self::POST_HAS_CHILD:
			case self::PAGE_HAS_CHILD:
				return $this->rules_values_has_children( $values, $rule );
			case 'page_parent':
				return $this->rule_values_no_parent( $values );
			default:
				return $this->rule_values_cpt( $values, $rule );
		}
	}

	/**
	 * This adds the options on the right <select>.
	 * You can add more options for top level pages to test agaisnt here.
	 *
	 * @param array $values Rule values.
	 * @param array $rule   Rule.
	 *
	 * @return array
	 */
	private function rules_values_tree_location( $values, $rule ) {
		// Get all top level pages/CPTs.
		$pages = get_posts(
			array(
				'post_parent'    => 0,
				'post_type'      => self::PAGE_IS_TREE === $rule['param'] ? array( 'page' ) : array_column( $this->custom_post_types(), 'name' ),
				'posts_per_page' => 1000, // Limit this just in case.
				'orderby'        => 'type name',
				'order'          => 'ASC',
			)
		);

		// Build menu for ACF filter rule.
		foreach ( $pages as $page ) {
			$values[ 'post_id_' . $page->ID ] = $page->post_type . ': ' . $page->post_title;
		}

		return $values;
	}

	/**
	 * This adds the options on the right <select>.
	 * You can add more options for top level pages to test agaisnt here.
	 *
	 * @param array $values Rule values.
	 * @param array $rule   Rule.
	 *
	 * @return array
	 */
	private function rules_values_has_children( $values, $rule ) {
		return 'Has Children';
	}

	/**
	 * Add no parent option to page location rule.
	 *
	 * @param array $values Rule values.
	 *
	 * @return array
	 */
	private function rule_values_no_parent( $values ) {
		$values[ PHP_INT_MAX ] = 'No Parent';
		return $values;
	}

	/**
	 * Custom ACF filter values.
	 * This adds the options on the right <select>.
	 *
	 * @param array $values Rule values.
	 * @param array $rule   Rule.
	 *
	 * @return array
	 */
	private function rule_values_cpt( $values, $rule ) {
		$rule_arr = $this->parse_key( $rule['param'] );
		if ( $rule_arr && in_array( $rule_arr['cpt_name'], array_column( $this->custom_post_types(), 'name' ) ) ) {
			$choices = array();

			if ( $rule_arr['surfix'] === self::SURFIX_PARENT ) {
				$choices[0] = 'No Parent';
			}

			// Get grouped posts.
			$groups = acf_get_grouped_posts(
				array(
					'post_type' => array( $rule_arr['cpt_name'] ),
				)
			);

			// Get first group.
			$posts = reset( $groups );

			// Append to choices.
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$choices[ $post->ID ] = acf_get_post_title( $post );
				}
			}
			return $choices;
		}

		return $values;
	}

	/**
	 * Custom ACF rule match.
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 *
	 * @return array
	 */
	public function rule_match( $result, $rule ) {

		// Abort if no post ID.
		$post_id = $this->get_current_post_id();
		if ( is_null( $post_id ) ) {
			return $result;
		}

		switch ( $rule['param'] ) {
			case self::POST_IS_TREE:
			case self::PAGE_IS_TREE:
				return $this->rule_match_tree_location( $result, $rule );
			case self::POST_HAS_CHILD:
			case self::PAGE_HAS_CHILD:
				return $this->rule_match_has_children( $result, $rule );
			case 'page_parent':
				return $this->rule_match_no_parent( $result, $rule );
			default:
				return $this->rule_match_cpt( $result, $rule );
		}
	}

	/**
	 * Custom ACF rule match.
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 *
	 * @return array
	 */
	private function rule_match_tree_location( $result, $rule ) {

		// Abort if no post ID.
		$post_id = $this->get_current_post_id();

		// Current and selected vars.
		$current_post = get_post( $post_id );
		$tree_id      = (int) str_replace( 'post_id_', '', $rule['value'] );

		// Is current post in the selected tree?
		$ancestors = get_ancestors( $current_post->ID, $current_post->post_type );
		$in_tree   = ( $current_post->ID === $tree_id ) || in_array( $tree_id, $ancestors );

		switch ( $rule['operator'] ) {
			case '==':
				return $in_tree;
			case '!=':
				return ! $in_tree;
		}

		return $result;
	}

	/**
	 * Custom ACF rule match.
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 *
	 * @return array
	 */
	private function rule_match_has_children( $result, $rule ) {

		$post_id = $this->get_current_post_id();

		$post_type = get_post_type( $post_id );

		// Current and selected vars.
		$children = get_children(
			array(
				'post_parent' => $post_id,
				'post_type'   => $post_type,
			)
		);

		switch ( $rule['operator'] ) {
			case '==':
				return ! empty( $children );
			case '!=':
				return empty( $children );
		}

		return $result;
	}

	/**
	 * No-parent page location rule match.
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 * @param array $screen The screen args.
	 *
	 * @return array
	 */
	private function rule_match_no_parent( $result, $rule ) {
		if ( $rule['value'] == PHP_INT_MAX ) { // phpcs:ignore
			return empty( get_post_parent( $this->get_current_post_id() ) );
		}

		return $result;
	}

	/**
	 * Custom ACF rule match.
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 *
	 * @return array
	 */
	private function rule_match_cpt( $result, $rule ) {
		$post_id = $this->get_current_post_id();

		$post_type = get_post_type( $post_id );
		$rule_arr  = $this->parse_key( $rule['param'] );
		if ( $rule_arr && $rule_arr['cpt_name'] === $post_type ) {
			switch ( $rule_arr['surfix'] ) {
				case self::SURFIX_PARENT:
					$parent = get_post_parent( $post_id );
					return $parent ? $parent->ID == $rule['value'] : empty( $rule['value'] ); // phpcs:ignore
				case self::SURFIX_TREE:
					$ancestors   = get_ancestors( $post_id, $post_type, 'post_type' );
					$ancestor_id = $rule['value'];
					$in_tree     = ( $ancestor_id == $post_id ) || in_array( $ancestor_id, $ancestors ); // phpcs:ignore

					switch ( $rule['operator'] ) {
						case '==':
							return $in_tree;
						case '!=':
							return ! $in_tree;
					}
					return false;
			}
		}

		return $result;
	}

	/**
	 * Get all custom post types excluding registered by ACF ones.
	 *
	 * @return array Custom post types array.
	 *               array( 'name' => String, 'label' => String )
	 */
	private function custom_post_types() {
		static $cpts;

		// Caching the query for a better performance.
		if ( ! is_null( $cpts ) ) {
			return $cpts;
		}

		$cpts = array_map(
			function ( $cpt ) {
				return array(
					'name'  => $cpt->name,
					'label' => $cpt->label,
				);
			},
			get_post_types(
				array(
					'_builtin'     => false, // Custom post types only.
					'hierarchical' => true, // Hierarchical ones only.
					'public'       => true, // Exclude CPTs by ACF because those are private ones.
				),
				'objects'
			)
		);

		return $cpts;
	}

	/**
	 * Get CPT Parent rule key name.
	 *
	 * @param string $cpt_name Custom post type name.
	 *
	 * @return string
	 */
	private function key_parent( $cpt_name ) {
		return self::PREFIX . ':' . $cpt_name . ':' . self::SURFIX_PARENT;
	}

	/**
	 * Get CPT Is Tree rule key name.
	 *
	 * @param string $cpt_name Custom post type name.
	 *
	 * @return string
	 */
	private function key_tree( $cpt_name ) {
		return self::PREFIX . ':' . $cpt_name . ':' . self::SURFIX_TREE;
	}

	/**
	 * Check if key is for custom location rule by comparing key.
	 *
	 * @param string $key String to check if custom key.
	 *
	 * @return bool false|array [cpt_name, surfix]
	 */
	private function parse_key( $key ) {
		$arr = explode( ':', $key );

		// Validate key value.
		if ( count( $arr ) < 3 || $arr[0] !== self::PREFIX ) {
			return false;
		}

		// remove prefix.
		array_shift( $arr );

		$surfix   = array_pop( $arr );
		$cpt_name = join( ':', $arr );
		return array(
			'cpt_name' => $cpt_name,
			'surfix'   => $surfix,
		);
	}

	/**
	 * Get current post id in admin panel. Returns null if it's not a post page.
	 *
	 * @return int|null
	 */
	private function get_current_post_id() {
		global $pagenow;
		if ( $pagenow !== 'post.php' ) {
			return null;
		}

		global $post;
		return $post->ID;
	}
}
