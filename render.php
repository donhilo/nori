<?php get_header();?>

<div id="norimake_renderbox">
<h1>Artículos seleccionados</h1>
<?php echo $_SESSION['articlesel'];?>

<?php nori_centralOps();?>

<div id="noriresult">
</div>

</div>
<?php get_footer();?>