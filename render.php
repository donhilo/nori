<?php get_header();?>

<?php if(is_user_logged_in()):?>

<div id="nori_make_renderbox" class="nori-css">

<div>

<h2>Generador de compilados de edición</h2>

<div class="nori_wrapper">

<?php 
	$args = array(
		'post_type' => 'ayc_edicion',
		'numberposts' => -1
		);
	$ediciones = get_posts($args);
	foreach($ediciones as $edicion){
		echo '<button class="btn btn-info make-edition" data-edition-id="'. $edicion->ID .'">Crear edición: ' . $edicion->post_title .'</button><br/>';		
	}
?>
</div>

<div id="nori_result">
</div>

</div>

</div>

<?php endif;?>
<?php get_footer();?>