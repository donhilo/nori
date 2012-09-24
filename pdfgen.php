<?php
/*
PDF Generation scripts

TODO:

1. Replace HTML generation for PDF native generation preparsing de HTML
2. Make a method for joining different articles as chapters
3. Define a layout implementation method
4. Separate images from the rest of the content
5. Insert images (with captions in a separate section according to design)
6. Add option for inserting custom fields in the content (via external function)

*/


//Extiendo la clase para hacer pdfs.
class noriPDF extends TCPDF {
	var $artitle;

	public function setHeadText($text) {
		$this->artitle = $text;		
	}

	 //Page header
    public function Header() {
        // Set font
        $this->setFontSize(10);
        // Title
        $this->Cell(0, 15, $this->artitle, 0, false, 'L', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->setFontSize(10);
        // Page number
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

function nori_makePdf($postobj) {

//Configuration for language, you can change the file corresponding to the main language you want to use
require_once( NORI_LIBS . 'tcpdf/config/lang/spa.php');

$title = $postobj[0]->post_title;
//Random file name
$fileid = rand(10000,99999);

// create new PDF document

$pdf = new noriPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('A Pie');
$pdf->SetTitle('Ejemplo de Generador de PDF');
$pdf->SetSubject('Artículo');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');


$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

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

//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')


//Proto Index

$pdf->setFontSize(24);
$pdf->MultiCell(0,0,'Generador de contenidos en PDF', 0, 'C');

$pdf->Ln();

$pdf->setFontSize(18);
$pdf->Cell(0,0,'Índice de artículos seleccionados', 0, 1, 'C', 0, '', 0);

$pdf->Ln();

$pdf->setFontSize(16);
foreach($postobj as $post):
	$pdf->MultiCell(0,0, ' -' . $post->post_title , 0,'L');
	$pdf->Ln();
endforeach;

$htmlchain = NULL;

//Strip post object of images

//Construct something to build each article

foreach($postobj as $post):
	$pdf->AddPage();
	$curpage = $pdf->getPage();
	$pdf->setHeadText(get_the_title($post->ID));
	//Imagen destacada
	if(has_post_thumbnail($post->ID)){
		$img = get_post_thumbnail_id($post->ID);
		$imgsrc = wp_get_attachment_image_src($img, 'full');

		$pdf->Image($imgsrc[0], 15, 14, 100, 0, 'JPG', '', 'M', true, 300, 'C', false, false, 1, false, false, false);	
		$pdf->Ln();	
	}

	//Titulo
	$pdf->setFontSize(16);	
	$pdf->setHeaderData('','',$post->post_title, $post->post_title . $curpage );

	$precont = apply_filters('the_content', $post->post_content);
	//Clean HTML
	$content = strip_tags($precont, '<p><a><em><strong><ul><li><blockquote><cite>');

	$pdf->MultiCell(0,0,$post->post_title, 0, 'C');
	$pdf->Ln();
	// Print text using writeHTMLCell()
	$pdf->setFontSize(12);
	$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $content , $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);		
endforeach;

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output(NORI_FILESPATH .'articulo-'.$fileid.'.pdf', 'F');

echo 'The file is ready for download!';
echo '<a href="'.NORI_FILESURL . 'articulo-'.$fileid.'.pdf">Download </a>';
//============================================================+
// END OF FILE
//============================================================+
}