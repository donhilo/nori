<?php get_header();?>

<div id="nori_make_renderbox">
<h1>Artículos seleccionados <!--<?php echo $_SESSION['articlesel'];?>--></h1>


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

<div id="nori_result">
</div>

</div>
<?php get_footer();?>