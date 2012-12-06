jQuery(document).ready(function($) {	

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
				articlelist : articles
			},
			success: function(data, textStatus, XMLHttpRequest) {
				$('#noriresult').empty().hide()
				.append(data)
				.fadeIn();				
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#noriresult').append('EERRRROR: ' + errorThrown);
			}	
		});
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
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});
//Different calls cause I call this stuff via AJAX
	$(document).on('click', '.articledel', function() {
		parentli = $(this).parent('li')
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
			},
			error: function(data, textStatus, XMLHttpRequest) {
				$('.nori_articlelist').prepend('<li>ERROR</li>');	
			}
		});
	});

});