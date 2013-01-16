<?php get_header();?>

<div id="nori_make_renderbox" class="nori-css">

<h1> <?php echo NORIMSG_SYSTEMTITLE;?> </h1>

<div class="introstuff">
<p> <?php echo NORIMSG_RENDERINTRO; ?> </p>
<p> <?php echo NORIMSG_TIMEWARNING; ?> </p>

<!--<p id="generar-url"> <?php echo NORIMSG_STOREINTRO;?>: <span class="btn nori-btn btn-mini storesel">Guardar selección</span></p>-->

<p> <?php echo NORIMSG_LISTTITLE;?> </p>





</div>

<?php nori_centralOps();?>

<div id="nori_printform">
	<form id="nori_payform" method="POST" class="form-horizontal">
		<fieldset>
		<legend>Datos de envío</legend>
		<div class="control-group">
			<label class="control-label" for="clientname">Nombre</label>
			<div class="controls">
			<input name="clientname" id="clientname" type="text" placeholder="Nombre"/>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="clientemail">Correo</label>
			<div class="controls">
			<input name="clientemail" id="clientemail" type="text" placeholder="Correo"/>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="clientaddress">Dirección</label>
			<div class="controls">
			<input name="clientaddress" id="clientaddress" type="text" placeholder="Dirección"/>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="clientphone">Teléfono</label>
			<div class="controls">
			<input name="clientphone" id="clientphone" type="text" placeholder="Teléfono"/>
			</div>
		</div>

		<button type="submit" class="btn" id="payandprint">Pagar y enviar</button>
		
		</fieldset>
		
	
	</form>

</div>

<div class="legend">
<p> <i class="icon icon-move"></i> <?php echo NORIMSG_REORDERINTRO; ?> </p>
<p> <i class="icon icon-trash"></i> <?php echo NORIMSG_TRASHINTRO; ?> </p>
</div>

<div id="nori_result">
</div>

</div>
<?php get_footer();?>