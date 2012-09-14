<?php
/*
PDF Generation scripts
*/

function nori_makePdf($postobj) {

$title = $postobj[0]->post_title;
$fileid = rand(10000,99999);

//Load TCPDF
//Configuration for language, you can change the file corresponding to the main language you want to use
require_once( NORI_LIBS . 'tcpdf/config/lang/spa.php');

//Tcpdf main file
require_once( NORI_LIBS . 'tcpdf/tcpdf.php' );	
	
//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2012-07-25
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               Manor Coach House, Church Hill
//               Aldershot, Hants, GU12 4RQ
//               UK
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('A Pie');
$pdf->SetTitle('Ejemplo de Generador de PDF');
$pdf->SetSubject('Artículo');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');


$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 12, '', true);
// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// Set some content to print

$htmlchain = '<h1>PDF generado con TCPDF</h1>';

$htmlchain .= '<h2>Índice de artículos seleccionados</h2>';

$htmlchain .= '<ul>';
foreach($postobj as $post):
	$htmlchain .= '<li>'.$post->post_title.'</li>';
endforeach;
$htmlchain .= '</ul>';

$htmlchain .= '<hr/>';


foreach($postobj as $post):
	$htmlchain .=  '<h2>'.$post->post_title.'</h2>';
	$htmlchain .= '<p>'.apply_filters('the_content', $post->post_content).'</p>';
endforeach;


$html = <<<EOD
	$htmlchain
EOD;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output(NORI_FILESPATH .'articulo-'.$fileid.'.pdf', 'F');

//============================================================+
// END OF FILE
//============================================================+
}