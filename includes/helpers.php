<?php
/**
 * Wrapper function for Utils::get_route_name().
 *
 * @return string
 */
function get_route_name() {
	return \WpEasy\Utils::get_route_name();
}

/**
 * Wrapper function for Utils::use_component()
 */
function use_component( $name, $props = null ) {
	return \WpEasy\Utils::use_component( $name, $props );
}

/**
 * Wrapper function for Utils::use_outlet()
 */
function use_outlet() {
	return \WpEasy\Utils::use_outlet();
}

/**
 * Wrapper function for Utils::use_children()
 */
function use_children( $args = [] ) {
	return \WpEasy\Utils::use_children( $args );
}

/**
 * Helper function to set default values component args
 */
function set_defaults( $args, $defaults ) {
	return wp_parse_args( $args, $defaults );
}
