<?php use_component( 'header' ); ?>

<main class="template-work main">

	Work template example

	<?php var_dump( get_route_name() ); ?>

	<?php use_component( 'work-block', [ 'title' => 'test of title argument' ] ); ?>

	<?php use_component( 'work-block', [ 'title' => '2nd work block' ] ); ?>

	<?php use_component( 'work-block', [ 'title' => '3rd work block' ] ); ?>

	<?php use_component( 'work-block' ); ?>

</main>

<?php use_component( 'footer' ); ?>
