<?php
// Set default args for the component
$args = set_defaults(
	$args,
	[
		'class'    => '',
		'id'       => '',
		'image_id' => 0,
		'sizes'    => '',
		'mode'     => 'default',
	]
);

$sizes = $args['sizes'];

// Handle some custom size shortcuts
switch ( $sizes ) {
	case 'full':
	case 'full-screen':
	case 'fullscreen':
		$sizes = '(max-width: 850px) 1920px, 100vw';
		break;

	case 'half':
	case 'half-screen':
		$sizes = '(max-width: 850px) 100vw, 50vw';
		break;

	case 'third':
	case 'third-screen':
		$sizes = '(max-width: 850px) 100vw, 33.33vw';
		break;

	case 'quarter':
	case 'quarter-screen':
		$sizes = '(max-width: 850px) 100vw, 25vw';
		break;
}

// Get the image HTML
$image = wp_get_attachment_image(
	$args['image_id'],
	'fullscreen-xlarge',
	false,
	[
		'class' => 'media media-image',
		'sizes' => $sizes,
	]
);

// Set the image aspect ratio as CSS vars
$img_meta = wp_get_attachment_metadata( $args['image_id'] );
if ( $img_meta ) {
	$ratio = $img_meta['width'] . ' / ' . $img_meta['height'];
}

// Check for Video
if ( function_exists( 'get_field' ) ) {
	$video_url = get_field( 'video_url', $args['image_id'] );
} else {
	$video_url = false;
}

// Build out all the CSS classes
$classes = [ 'wp-image', $args['class'], 'mode-' . $args['mode'] ];

?>

<?php
if ( ! $image ) {
	return;}
?>

<figure
	id="<?php echo $args['id']; ?>"
	class="<?php echo implode( ' ', $classes ); ?>"
	style="--aspect-ratio: <?php echo $ratio; ?>">

	<?php echo $image; ?>

	<?php if ( $video_url ) : ?>
		<video class="media media-video" playsinline autoplay muted loading="lazy" src="<?php echo $video_url; ?>" loop></video>
	<?php endif; ?>
</figure>
