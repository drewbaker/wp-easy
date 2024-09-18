<?php
/**
 * Class Plugin
 *
 * @package WpEasy
 */

namespace WpEasy;

use \WpEasy\Plugin_Base;
/**
 * Class Plugin
 *
 * @package WpEasy
 */
class Plugin extends Plugin_Base {
	public function init() {
		// Load modules.
		( new Router() )->init();
		( new Template() )->init();
	}
}
