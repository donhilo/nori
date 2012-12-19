jQuery(document).ready(function($) {	
	$('#nori_printform').hide();	

	$('.nori_articlelist').sortable({
		update: function(event, ui){
			var articlesel = new Array();
			
			$('.nori_articlelist li').each(function() {				
				articlesel.push($(this).data('id'));
			});			
			
			var artjoin = articlesel.join()
			
			$('#generar-ajax').data('articles', artjoin);			
			}
	});



	//Llenar articulos
	(function() {
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',
				command: 'populate'
			},
			success: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').append(data);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('.nori_articlelist').append('ERROR:' + errorThrown);	
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
					$('#nori_result').empty().hide()
					.append(data)
					.fadeIn()
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					$('#nori_result').append('ERRORERRORERROR: ' + errorThrown);
				}
			});

		};			
		//Cancel default stuff
		e.preventDefault();
	});	

	$('#generar-ajax').on('click', function() {
		articles = $(this).data('articles');
		$('#noriresult').hide()
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
				$('#nori_result').empty().hide()
				.append(data)
				.fadeIn();				
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#nori_result').append('EERRRROR: ' + errorThrown);
			}	
		});
	});

	$('#generar-ajax-imprenta').on('click', function() {
		$('#nori_printform').slideDown('300');
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
		
		parentli = $(this).parent('li')
		articles = $('#generar-ajax').data('articles').split(',');
		deletedarticle = articles.indexOf(parentli.data('id'));

		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',				
				command: 'delete', 
				id: parentli.data('id')
			},
			success: function(data, textStatus, XMLHttpRequest) {
				articles.splice(deletedarticle,1);
				console.log(deletedarticle);
				parentli.remove();

			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});

});