<?php
/*
 * Define the templates to use, based on the valid WordPress routes.
 *
 * Syntax is similar to Express paths in Node
 * The key is the route name, and the value is an array of [path, template]
 * Values can also be strings, in which case the path is the string and the template is the key.
 * If no template set, the key is used as the template name.
 * If
 *
 * SEE https://github.com/drewbaker/wp-easy/blob/main/README.md
 */

$routes = [
	'home'        => '/',
	'work'        => '/work/',
	'work-detail' => [
		'path'     => '/work/:spot/',
		'template' => 'work-detail',
		'layout'   => 'layout1',
	],
	'reel'        => '/reel/',
];

return $routes;
