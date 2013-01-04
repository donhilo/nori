jQuery(document).ready(function($) {	
	var resultbox = $('.nori_wrapper');

	$('#nori_printform').hide();	

	$('#nori_make_renderbox .nori_articlelist').sortable({
		update: function(event, ui){
			var articlesel = new Array();
			
			$('.nori_articlelist li').each(function() {				
				articlesel.push($(this).data('id'));
			});			

			var artjoin = articlesel.join()
			console.log(artjoin);
			$.ajax({
				type: 'POST',
				url: noriAJAX.ajaxurl,
				data: {
					action: 'ajaxSessionNori',
					command: 'update',
					orderdata: artjoin
				},
				success: function(data, textStatus, XMLHttpRequest) {
					$('.formwrapper').append('<p>Orden actualizado: ' + artjoin + '</p>');					
				}, 
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					$('.formwrapper').append('<p>Error</p>');
				}
			});
			
			}
	});



	//Llenar articulos
	(function() {
		var articlelist = $('.nori_articlelist');
		articlelist.append('<p>Cargando datos</p>');
		if(articlelist.data('process') == 'incheckout') {
			var popcommand = 'populateandsort';
		} else {
			var popcommand = 'populate';
		}

		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',
				command: popcommand
			},
			success: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').empty().append(data);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('.nori_articlelist').empty().append('ERROR:' + errorThrown);	
			}
		})
		})();

	$('#payandprint').on('click', function(e) {
		//Validate Fields
		$(".error").hide();
    	var hasError = false;
   		var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;    	
 
    	var emailaddressVal = $("#clientemail").val();
    	var addressVal = $('#clientaddress').val();
    	var phoneVal = $('#clientphone').val();
    	var nameVal = $('#clientname').val();

    	if(nameVal == '') {
    		$('#clientname').after('<span class="error">Por favor, escribe tu nombre.</span>')
    		hasError = true;
    	}	

    	if(addressVal == '') {
    		$('#clientaddress').after('<span class="error">Por favor, escribe tu dirección.</span>')
    		hasError = true;
    	}

    	if(phoneVal == '') {
    		$('#clientphone').after('<span class="error">Por favor, escribe tu teléfono.</span>')
    		hasError = true;
    	}

    	if(emailaddressVal == '') {
      		$("#clientemail").after('<span class="error">Por favor, escribe tu correo.</span>');
      		hasError = true;
    		} 
    	else if(!emailReg.test(emailaddressVal)) {
      		$("#clientemail").after('<span class="error">Escribe una dirección de correo válida.</span>');
      		hasError = true;
    		}
    	
 
    	if(hasError == true) {
    		 return false; }

    	else {

		//Collect Form data
		articles = $(this).data('articles');
		var formInputs = $('#nori_payform input');
		var formData = new Object();
			formInputs.each(function(index) {
				var prop = $(this).attr('name');
				formData[prop] = $(this).attr('value');
			});
			console.log(formData);
			resultbox			
				.empty()
				.append('<div class="alert"><img src="'+ noriAJAX.noriurl +'/imgs/ajax-loader.gif"/>  Generando PDF...</div>')
				.fadeIn();
			$.ajax({
				type: 'POST',
				url: noriAJAX.ajaxurl,
				data: {
					action: 'ajaxNori',
					articlelist: articles,
					forprint: 'yes',
					extradata: formData
				},
				success: function(data, textStatus, XMLHttpRequest) {
					resultbox.empty().hide()
					.append(data)
					.fadeIn()
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					resultbox.append('ERRORERRORERROR: ' + errorThrown);
				}
			});

		};			
		//Cancel default stuff
		e.preventDefault();
	});	

	$('#generar-ajax').on('click', function() {
		articles = $(this).data('articles');
		resultbox
			.empty()
			.hide()
			.append('<div class="alert"><img src="'+ noriAJAX.noriurl +'/imgs/ajax-loader.gif"/>  Generando PDF...</div>')
			.fadeIn();
		console.log(articles);
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxNori',
				articlelist : articles,
				forprint: 'no'
			},
			success: function(data, textStatus, XMLHttpRequest) {
				resultbox.empty().hide()
				.append(data)
				.fadeIn();				
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				resultbox.append('EERRRROR: ' + errorThrown);
			}	
		});
	});

	$('#generar-ajax-imprenta').on('click', function() {
		resultbox
			.empty()
			.append($('#nori_printform'));
		$('#nori_printform').slideDown(300);	
	});

	$('#add-article').on('click', function() {
		id = $(this).data('id');
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',
				id: id,
				command: 'add' 
			},
			success: function(data, textStatus, XMLHttpRequest) {
				var item = $(data).hide().fadeIn(1400)
				$('.nori_articlelist').prepend(item);
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});

	$('#borrar-articulos').on('click', function() {		
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',				
				command: 'delete-all' 
			},
			success: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').empty();
				$('#generar-ajax').data('articles', '');
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});
//Different calls cause I call this stuff via AJAX
	$(document).on('click', '.articledel', function() {
		
		parentli = $(this).parent('li');				

		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',				
				command: 'delete', 
				id: parentli.data('id')
			},
			success: function(data, textStatus, XMLHttpRequest) {								
				parentli.remove();
				console.log(parentli.data('id'));

			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});

});