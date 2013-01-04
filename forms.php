<?php

function sendPDFforPrint($postvars, $link, $printer, $numpages) {	

	$subjectto = 'Gracias por solicitar una impresión desde '. get_bloginfo('name');
	$mailcontent = '<p>Sus datos fueron enviados exitosamente</p>';
	$mailcontent .= '<p>Grrracias</p>';	

	$subjectsale = 'Confirmación de solicitud de impresión en ' . get_bloginfo('name');
	$salecontent = '<h3>Confirmación de Orden</h3>';
	$salecontent .= '<p>Se ha solicitado una impresión de: </p>';
	$salecontent .= $postvars['clientname'];
	$salecontent .= '<ul>';
	$salecontent .= '<li>Correo: ' . $postvars['clientemail'] . '</li>';
	$salecontent .= '<li>Teléfono:' . $postvars['clientphone'] . '</li>';
	$salecontent .= '<li>Dirección:' . $postvars['clientaddress'] . '</li>';
	$salecontent .= '</ul>';
	$salecontent .= '<p>El archivo está disponible para descargar en esta URL:</p>';
	$salecontent .= '<p><a href="' . $link . '">Enlace de descarga</a></p>';
	$salecontent .= '<p><strong>Número de páginas: </strong> ' . $numpages . ' </p>';
	$salecontent .= '<p><strong>Costo de impresión: </strong> $CLP' . $numpages * NORI_COSTPERPAGE . '</p>';

	$client = $postvars['clientemail'];

	wp_mail($client, $subjectto, $mailcontent);	
	
	wp_mail($printer, $subjectsale, $salecontent);	
	
}

add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
add_filter( 'wp_mail_from', 'nori_mail_from' );

function nori_mail_from( $email )
{	
	$mail = 'info@arteycritica.org';
    return $mail;
}

add_filter( 'wp_mail_from_name', 'nori_mail_from_name' );
function nori_mail_from_name( $name )
{
    return get_bloginfo('name');
}