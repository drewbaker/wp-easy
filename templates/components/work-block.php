<?php
// Set default args for the component
$args = set_defaults(
	$args,
	[
		'title' => 'Title default here',
	]
);
?>

<div class="work-block">
	<h2 class="title">
		<?php echo $args['title']; ?>
	</h2>
</div>

<style>
	body {
		.work-block {
			border: 1px solid #000;
		}
	}
</style>
<style>
.work-block {
	h2 {
		&.title {
			font-weight: 700;
			color: red;
		}
	}
}
</style>
<script>
console.log(1);
</script>
<script>console.log(2);</script>
