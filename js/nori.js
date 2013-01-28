//General nori functions.

//Populate article list main function, it accepts stuff.
function populate(action, element) {
	jQuery.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',
				command: action
			},
			success: function(data, textStatus, XMLHttpRequest) {
				jQuery(element).empty().append(data);
				sortableList();
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				jQuery(element).empty().append( noriAJAX.msg_error + ':' + errorThrown);	
			}
		});
}

function sortableList() {
	jQuery('#nori_make_renderbox .nori_articlelist').sortable({

		update: function(event, ui){
			var articlesel = new Array();
			
			jQuery('.nori_articlelist li').each(function() {				
				articlesel.push(jQuery(this).data('id'));
			});			


			var artjoin = articlesel.join()
			console.log(artjoin);
			jQuery.ajax({
				type: 'POST',
				url: noriAJAX.ajaxurl,
				data: {
					action: 'ajaxSessionNori',
					command: 'update',
					orderdata: artjoin
				},
				success: function(data, textStatus, XMLHttpRequest) {
					jQuery('.nori_wrapper ul.nori_articlelist').prepend('<span class="updatedorder label label-success">'+ noriAJAX.msg_updatedorder + '</span>');
					jQuery('span.updatedorder').fadeOut(2000);					
				}, 
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					jQuery('.formwrapper').append('<p>' + noriAJAX.msg_error +'</p>');
				}
			});

			}
	});
}

jQuery(document).ready(function($) {
	//Añadir sección al final del body
	$('body').append('<section class="nori-css" id="nori_section"></section>');	

	//Variables de elementos recurrentes
	var articlecount = $('.noricounter .nori_number');
	var resultbox = $('.nori_wrapper');
	var uistuff = $('.introstuff, .legend');
	var printform = $('#nori_printform');

	printform.hide();	


	//Llenar articulos y contador de artículos - autoejecutable
	(function() {

		var articlelist = $('.nori_articlelist');		
		
		articlelist.append('<p>' + noriAJAX.msg_loadingselection +'</p>');
		
		if(articlelist.data('process') == 'incheckout') {
			var popcommand = 'populateandsort';
		} else {
			var popcommand = 'populate';
		}

		populate(popcommand, '#nori_make_renderbox .nori_articlelist');

		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',
				command: 'count'
			},
			success: function(data, textStatus, XMLHttpRequest) {
				if(data.length > 0){
					articlecount.empty().append(data);
				} else {
					articlecount.empty().append('0');
				}

			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				articlecount.empty().append(errorThrown);	
			}
		});
	})();
	

	$(document).on('click', '.btn[data-function="toggle-section"]', function() {
		$('#nori_section').slideUp(600).empty();
		$('#trigger-norisection').removeClass('active');
		$('#trigger-norisection').addClass('inactive');

	});

	$(document).on('click', '#generar-ajax', function() {
		
		var resultbox = $('.nori_wrapper');
		var uistuff = $('.introstuff, .legend');

		uistuff.fadeOut();
		articles = $(this).data('articles');

		resultbox
			.empty()
			.hide()
			.append('<div class="nori-ajaxstatus nori-generating"><h3><img src="'+ noriAJAX.noriurl +'/imgs/ajax-loader.gif"/> ' + noriAJAX.msg_generating + '</h3><p>' + noriAJAX.msg_timeexplanation +'</p></div>')
			.fadeIn();		
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
				resultbox.append(noriAJAX.msg_error +': ' + errorThrown);
			}	
		});
	});

	$(document).on('click', 'button.make-edition', function() {
		
		var resultbox = $('.nori_wrapper');		
		var edid = $(this).data('edition-id');
		resultbox
			.empty()
			.hide()
			.append('<div class="nori-ajaxstatus nori-generating"><h3><img src="'+ noriAJAX.noriurl +'/imgs/ajax-loader.gif"/> ' + noriAJAX.msg_generating + '</h3><p>' + noriAJAX.msg_timeexplanation +'</p></div>')
			.fadeIn();		
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxNori',
				edition: 'yes',
				edid: edid,
				forprint: 'no'
			},
			success: function(data, textStatus, XMLHttpRequest) {								
				resultbox.empty().hide()
				.append(data)
				.fadeIn();
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				resultbox.append(noriAJAX.msg_error +': ' + errorThrown);
			}	
		});
	});

	$('#add-article').on('click', function() {
		if($(this).hasClass('disabled')){
			$('#nori_section')
			.empty()
			.append('<div id="nori_make_renderbox"><div class="nori-ajaxstatus nori-warning">Ya has agregado este artículo </div></div>')
			.slideDown(600)
			.delay(1000)
			.slideUp(600);
		} else {
			id = $(this).data('id');
			articlecount.empty().append('<img src="' + noriAJAX.noriurl +'/imgs/clock.gif">');
			$.ajax({
				type: 'POST',
				url: noriAJAX.ajaxurl,
				data: {
					action: 'ajaxSessionNori',
					id: id,
					command: 'add' 
				},
				success: function(data, textStatus, XMLHttpRequest) {
					//Activar si es que estaba desactivao			
					var trigger = $('#trigger-norisection');
					if(trigger.hasClass('disabled')) {					
						trigger.removeClass('disabled')
					}
					var item = $(data).hide().fadeIn(1400)
					$('.nori_articlelist').prepend(item);
					$.ajax({
						type: 'POST',
						url: noriAJAX.ajaxurl,
					data: {
						action: 'ajaxSessionNori',
						command: 'count'
						},
						success: function(data, textStatus, XMLHttpRequest) {
						articlecount.empty().append(data);
						},
						error: function(XMLHttpRequest, textStatus, errorThrown) {
						articlecount.empty().append(errorThrown);	
						}
					});
					$('#add-article').addClass('disabled');					
				},
				error: function(data, textStatus, XMLHttpRequest) {
					$('.nori_articlelist').prepend('<li>' + noriAJAX.msg_error +'</li>');	
				}
			});
		}
	});

	$(document).on('click', '#borrar-articulos', function() {		
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
				$('.nori_number').empty().append('0');
				$('#trigger-norisection').addClass('disabled');
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li> ' + noriAJAX.msg_error + ' </li>');	
			}
		});
	});
//Different calls cause I call this stuff via AJAX
	$(document).on('click', '.articledel', function() {	
		parentli = $(this).parent('li');						
		parentli.css('background-color', 'rgba(186, 75, 49, 0.8)')
		$.ajax({
			type: 'POST',
			url: noriAJAX.ajaxurl,
			data: {
				action: 'ajaxSessionNori',				
				command: 'delete', 
				id: parentli.data('id')
			},

			success: function(data, textStatus, XMLHttpRequest) {								
				parentli					
					.fadeOut(1000, function() {
					$(this).remove();
				});
				$('.noricounter').popover('show');
				populate('onlypopulate', '.nori_snippet .nori_articlelist');									
				console.log('removed' + parentli.data('id'));
					$.ajax({
						type: 'POST',
						url: noriAJAX.ajaxurl,
					data: {
						action: 'ajaxSessionNori',
						command: 'count'
						},
						success: function(data, textStatus, XMLHttpRequest) {
						articlecount.empty().append(data);
						},
						error: function(XMLHttpRequest, textStatus, errorThrown) {
						articlecount.empty().append(errorThrown);	
						}
					});	
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li> ' + noriAJAX.msg_error + ' </li>');	
			}
		});
	});

	

	//Boton para activar sección de contenidos desplegable

	$('#trigger-norisection.active').click(function(){
		$('#nori_section')
			.empty()		
			.slideUp(600);
	});

	$('#trigger-norisection.inactive').click(function(){
		if($(this).hasClass('disabled')) {
			$('#nori_section')
				.empty()
				.append('<div id="nori_make_renderbox"><div class="nori-ajaxstatus nori-warning">Aún no has agregado ningún artículo a la canasta</div></div>')
				.slideDown(600)
				.delay(1000)
				.slideUp(600);
		} else if($(this).hasClass('active')) {
			$('#nori_section')				
				.slideUp(600)
				.empty();
				$(this).removeClass('active');					
		} else {
			$(this).removeClass('inactive');
			$(this).addClass('active');
			$.ajax({
				type: 'POST',
				url: noriAJAX.ajaxurl,
				data: {
					action: 'ajaxSessionNori',
					command: 'ajaxSection'				
				},
				success: function(data, textStatus, XMLHttpRequest) {
					$('#nori_section').empty().append(data).slideDown(600);
					populate('populateandsort', '#nori_make_renderbox ul.nori_articlelist');				

				}, 
				error: function(data, textStatus, XMLHttpRequest) {
					$('#nori_section').append('ERROR');
				}
			});
		}
	});

	$('.noricounter').popover({
		title: 'Canasta de artículos',
		placement: 'bottom',
		content: '<ul class="nori_articlelist"> Cargando artículos ... </ul>',
		html: true		
		});

	$('body').click(function() {
		$('.noricounter').popover('hide');
		$('#trigger-norisection').popover('hide');		
	});

	$('.norititle').hover(function() {
		$(this).popover('show')
	},
		function() {
			$(this).popover('hide');
		}
	);

	// $('body').delegate('.norititle', 'click', 
	// 	function(event) {
	// 		event.stopPropagation();
	// 		if(event.type == 'click') {
	// 			var e = $(this);
	// 			var shown = popover && popover.tip().is(':visible');
	// 			if(shown) return;
	// 			e.popover('show');
	// 		}
	// 		});

	$('body').delegate('.noricounter', 'click', 		
		function(event) {
			event.stopPropagation();
			if(event.type == 'click'){
				var e = $(this);
				var popover = $(this).data('popover');
            	var shown = popover && popover.tip().is(':visible');
            	populate('onlypopulate', '.nori_snippet .nori_articlelist');
            	if(shown) return;
				e.popover('show');
				
			} 
		});

	$('.norititle').popover({
		title: 'Arma tu propia Revista Arte y Crítica (PDF)',
		placement: 'bottom',
		content: '<p>Si quieres tener una edición descargable con los artículos que te interesan, agrégalos a tu “Canasta de Artículos” (botón Agregar) mientras navegas en el sitio. Desde ahí puedes cambiar el orden de aparición, borrar o agregar otros para luego generar y descargar tu PDF personalizado.</p>'+ '<p>Puedes generar diferentes PDF, cada uno con diferentes artículos según tus gustos e intereses. Sólo debes crear uno a la vez: seleccionas los artículos &rarr; generas PDF &rarr; lo descargas,  &rarr; borras tu canasta de artículos y armas uno nuevo.</p><p>¡Es simple!</p>' + '<p>También lo quieres en formato Kindle o similares? ¿te gustaría tu propia edición pero impresa? ¿más personalizada?...bueno ¡calma!, esta es nuestra primera fase, ya vendrán novedades.</p>',
		html: true

	});		


	//Tooltips

	$('.noricounter').tooltip({
		placement: 'bottom',
		title: 'N° de artículos seleccionados.'
	});

	$('#add-article').tooltip({
		placement: 'bottom',
		title: 'Agregar este artículo a tu selección.'
	});

	$('.norimake-btn').tooltip({
		placement: 'bottom',
		title: 'Crear una revista en PDF a partir de tu selección.'
	});	

	$('body').tooltip({
		selector: 'li.articleUnit i'
	})

	

//Cosas de imprenta

$('#payandprint').on('click', function(e) {
		uistuff.fadeOut();
		//Validate Fields
		$(".error").hide();
    	var hasError = false;
   		var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;    	
 
    	var emailaddressVal = $("#clientemail").val();
    	var addressVal = $('#clientaddress').val();
    	var phoneVal = $('#clientphone').val();
    	var nameVal = $('#clientname').val();

    	if(nameVal == '') {
    		$('#clientname').after('<span class="error">' + noriAJAX.msg_noname + '</span>')
    		hasError = true;
    	}	

    	if(addressVal == '') {
    		$('#clientaddress').after('<span class="error">' + noriAJAX.msg_noaddress + '</span>')
    		hasError = true;
    	}

    	if(phoneVal == '') {
    		$('#clientphone').after('<span class="error">' + noriAJAX.msg_nophone + '</span>')
    		hasError = true;
    	}

    	if(emailaddressVal == '') {
      		$("#clientemail").after('<span class="error"> ' + noriAJAX.msg_nomail + '</span>');
      		hasError = true;
    		} 
    	else if(!emailReg.test(emailaddressVal)) {
      		$("#clientemail").after('<span class="error">' + noriAJAX.msg_novalidmail + '</span>');
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
			resultbox			
				.empty()
				.append('<h3><img src="'+ noriAJAX.noriurl +'/imgs/ajax-loader.gif"/>  ' + noriAJAX.msg_generating + '</h3><p>' + noriAJAX.msg_timeexplanation +'</p>')
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
					resultbox.append( noriAJAX.msg_error +': ' + errorThrown);
				}
			});

		};			
		//Cancel default stuff
		e.preventDefault();
	});	

	$('#generar-ajax-imprenta').on('click', function() {
		resultbox
			.empty()
			.append(printform);
		printform.slideDown(300);	
	});

});