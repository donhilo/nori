<?php
/*
PDF Generation scripts

TODO:

1. Replace HTML generation for PDF native generation preparsing de HTML
2. Define a layout implementation method
3. Add option for inserting custom fields in the content (via external function)
4. Make a better OOP structure for the whole stuff
5. Make progress indicator for generation of pdf

*/

// Makes an standard object article with stuff for publishing. 
// It can be populated with other stuff from other cms or database.
class noriContent {
	//Content variables, you can add more as you wish
	var $title;
	var $author;
	var $mainimage;
	var $contentimages;
	var $text;
	var $meta;		

//Wordpress Content Layer
	public function WPLayer($id) {
		$article = get_post($id);		
		$this->title = $article->post_title;
		$this->author = $article->post_author;

		//First image is featured image, the others are the others images attached to the post.	

		if(has_post_thumbnail($id)){
				$this->mainimage = array();
				$thumbid = get_post_thumbnail_id($id);
				$src = wp_get_attachment_image_src($thumbid, 'full');
				$this->mainimage['src'] = $src[0];
				$this->mainimage['title'] = unhtmlentities(get_the_title($thumbid));
			}

		$args = array( 
			'post_type' => 'attachment', 
			'post_mime_type' => 'image',
			'post_parent' => $id
		);	
		$images = get_children($args);			
		//print_r($images);
		if($images):			
			$this->contentimages = array();
			foreach($images as $key=>$image) {				
				$src = wp_get_attachment_url($image->ID);							
				$this->contentimages[$key] = array(
					'id' => $image->ID,
					'src' => $src,
					'title' => unhtmlentities(get_the_title($image->ID))
					);			
			}			
		endif;

		// Print text using writeHTMLCell()
		$pretext = apply_filters('the_content', $article->post_content);

		//Clean HTML
		$this->text = strip_tags($pretext, '<p><a><em><strong><ul><li><blockquote><cite>');

		$this->meta = get_post_custom($id);		
	}
}


//Extiendo la clase para hacer pdfs.
class noriPDF extends TCPDF {
	var $artitle;

	public function setHeadText($text) {
		$this->artitle = $this->unhtmlentities($text);		
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

    public function Chapter($postid) {
    	$content = new noriContent;
    	$content->WPLayer($postid);

    	$this->setHeadText($content->title);
		$this->AddPage();	
		
		//Indice
		$this->Bookmark($content->title, 0, 0, '', '', array(0,64,128));

		$mainimage = $content->mainimage;
		if($mainimage){						
			//print_r($mainimage);
			$this->Image($mainimage['src'], 15, 14, 100, 0, 'JPG', '', 'M', true, 300, 'C', false, false, 1, false, false, false);	
			$this->Ln();							
		}
		
	
		//Titulo				
		$this->setFontSize(16);
		
		
		
		$this->MultiCell(0,0,$content->title, 0, 'C');
		$this->Ln();		
		
		$this->setFontSize(12);	
		$this->writeHTMLCell($w=0, $h=0, $x='', $y='', $content->text , $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);	
		$this->endPage();

		$contentimages = $content->contentimages;

		if($contentimages):
			$this->AddPage();									

			//Coordenadas
			$y = 10;
			foreach($contentimages as $image){				
				$this->Image($image['src'], 10, $y, 80, 0, 'JPG', '', 'M', true, 300, 'C', false, false, 1, false, false, false);
				$curY = $this->getImageRBY();
				$this->setY($curY+1);
				$curY = ($curY+2);					
				$this->MultiCell(0,0,$image['title'], 0, 'C');				
				$y += $curY+4;
			}			
		endif;
    }    
}



function nori_makePdf($postobj) {	
	$artids = explode(',', $postobj);
	
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
	$pdf->SetFont('dejavusans', '', 12, '', true);



	$htmlchain = NULL;

	//Strip post object of images

	//Construct something to build each article

	foreach($artids as $postid):
		$pdf->Chapter($postid);
	endforeach;

	$pdf->setHeadText('Indice');
	$pdf->addTOCPage();
	$pdf->addTOC(1, 'courier', '.', 'Indice', '', array(128,0,0));

	// end of TOC page
	$pdf->endTOCPage();

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