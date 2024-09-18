<?php

/**
 * Class Router file
 *
 * @package WpEasy
 */

namespace WpEasy;

use \WpEasy\Libs\Path_To_Regexp;

/**
 * Class Router
 *
 * @package WpEasy
 */
class Router
{
	public function init()
	{
		add_action('init', array($this, 'load_router'));
	}

	// Load router.
	public function load_router()
	{
		$routes = get_template_directory() . '/router.php';
		if (! file_exists($routes)) {
			return;
		}

		include $routes;

		$keys          = [];
		$template_name = '';
		$layout_name   = 'default';

		foreach ($routes as $name => $params) {
			$path    = $params['path'] ?? $params;
			$re      = Path_To_Regexp::convert($path, $keys);
			$matches = [];
			$match   = preg_match($re, $_SERVER['REQUEST_URI'], $matches);

			if ($match) {
				$template_name = $params['template'] ?? $name;
				$layout_name   = $params['layout'] ?? 'default';
				break;
			}
		}

		// If no template found, then will fallback to default WP template hierarchy.
		if (!$template_name) {
			return;
		}

		$template = Utils::locate_template(['templates/' . $template_name . '.php']);
		if (! $template) {
			$error = new \WP_Error(
				'missing_template',
				sprintf(__('The file for the template %s does not exist', 'wp-easy-router'), '<b>' . $template_name . '</b>')
			);
			echo $error->get_error_message();
		}

		$layout = Utils::locate_template(['layouts/' . $layout_name . '.php']);
		if (! $layout) {
			$error = new \WP_Error(
				'missing_template',
				sprintf(__('The file for the layout %s does not exist', 'wp-easy-router'), '<b>' . $layout_name . '</b>')
			);
			echo $error->get_error_message();
		}

		// Now replace the template
		add_filter(
			'template_include',
			function ($old_template) use ($template, $template_name, $layout) {
				// Set our custom query var
				set_query_var('template', $template_name);
				set_query_var('template_file', $template); // Caching it to avoid duplicate locate_template() call in use_outlet().

				return $layout;
			},
			1
		);
	}
}
