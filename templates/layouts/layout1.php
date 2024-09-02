<!DOCTYPE html>
<html <?php language_attributes(); ?> prefix="og: http://ogp.me/ns#" <?php body_class(); ?>>

<head>
	<?php wp_head(); ?>
</head>

<body>
	<?php wp_body_open(); ?>

	<h1>Layout1</h1>
	<?php use_outlet(); ?>

	<?php wp_footer(); ?>
</body>

</html>
