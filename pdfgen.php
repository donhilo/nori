<?php
/*
PDF Generation scripts

TODO:

1. Define a layout implementation method
2. Define layout profiles
3. Create a system for styling child elements.

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
	var $excerpt;	
	var $date;		

//Wordpress Content Layer
	public function WPLayer($id) {
		$article = get_post($id);		
		$this->title = $article->post_title;
	
		//If you need other type of autor you can also set it up.
	
		if(get_post_meta($id, 'aycmb_autor', true)):
			$authors = get_post_meta($id, 'aycmb_autor', false);
			foreach($authors as $author):
				$authnames[] = get_the_title($author);
			endforeach;
		endif;

		$this->author = $authnames;

		$this->date = mysql2date('M Y', $article->post_date, true);

		//First image is featured image, the others are the others images attached to the post.	

		if(has_post_thumbnail($id)){
				$this->mainimage = array();
				$thumbid = get_post_thumbnail_id($id);
				$src = wp_get_attachment_image_src($thumbid, 'full');
				$this->mainimage['src'] = getFullPath($src[0]);
				$this->mainimage['title'] = html_entity_decode(get_the_title($thumbid), ENT_QUOTES, 'UTF-8');
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
					'src' => getFullPath($src),
					'title' => html_entity_decode(get_the_title($image->ID), ENT_QUOTES, 'UTF-8')
					);			
			}			
		endif;

		//Excerpt
		$this->excerpt = $article->post_excerpt;

		// Print text using writeHTMLCell()
		$pretext = apply_filters('the_content', $article->post_content);

		//Clean HTML	

		$this->domParser($pretext);		
	}

	public function domParser($text) {

		//Strip tags

		$cleantext = strip_tags($text, '<p>, <em>, <br>, <strong>, <h1>, <h2>, <h3>, <h4>, <h5>, <blockquote>, <div>, <cite>, <sup>');

		$domdoc = new DOMDocument();
		
		//Turn stuff into an object for easey parsing

		$domdoc->loadHTML('<?xml encoding="UTF-8">' . $cleantext);		
		
		//Remove unwanted stuff		

		$xpath = new DOMXPath($domdoc);
		$divs = $xpath->query('//div');
		
		foreach($divs as $div):
			$div->parentNode->removeChild($div);
		endforeach;

		$cleandom = $xpath->query('//p | //h1 | //h2 | //h3 | //h4 | //h5');

		foreach($cleandom as $key=>$cd):
			$this->text[$key]['element'] = $cd->nodeName;
			$this->text[$key]['content'] = $cd->nodeValue;
		endforeach;

		//Last step with filtered HTML
		// $this->text = $domdoc->saveHTML();

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
        $pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
		$this->SetFont($pt_sans, '', 10, NORI_GENFONTS . $pt_sans , false);		

        $this->setFontSize(10);
        // Title
        $this->Cell(0, 15, $this->artitle, 0, false, 'L', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
		$this->SetFont($pt_sans, '', 10, NORI_GENFONTS . $pt_sans , false);		
        $this->setFontSize(10);
        // Page number
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    //Procesa las imágenes del capítulo
    public function process_chapter_images($contentimages) {
	$this->AddPage();									
			//Coordenadas
			$y = 10;
			foreach($contentimages as $image){				
				$this->Image($image['src'], 10, $y, 80, 0, 'JPG', '', 'M', true, 300, 'C', false, false, 1, false, false, false);
				$curY = $this->getImageRBY();
				$this->setY($curY);
				$curY = ($curY+0.6);									
				$this->MultiCell(0,0,$image['title'], 0, 'C');				
				//doy el salto a la otra imagen
				$y = $curY+6;
			}

	}

	//Inicializa las páginas y cosas básicas de formato
	public function initMagazine() {
		// set display mode
		$this->SetDisplayMode($zoom='fullpage', $layout='TwoColumnRight', $mode='UseNone');

		// set pdf viewer preferences
		$this->setViewerPreferences(array('Duplex' => 'DuplexFlipLongEdge'));

    	$this->setBooklet(true);
    	$this->setHeadText($content->title);

    	$page_height = 310;
    	$page_width = 230;

    	$pagesize = array($page_height,$page_width);

    	$this->setPageFormat($pagesize, 'P');
	}

	//Crea ek título del artículo
	public function articleTitle($title, $font, $size) {
		//Titulo
		// Set font
		$this->SetFont($font, '', 12, NORI_GENFONTS . $pt_sans , false);		
		$this->setFontSize($size);
		
		$parsed_title = strtoupper_es($title);
		
		$this->setCellHeightRatio(0.9);				

		$this->MultiCell(210,20,$parsed_title, 0, 'L', false, 1, 16, $this->GetY(), true, 1, false, true, 0, 'T', true);
		
		$this->Ln(4);
	}

	public function mainImage($article_layout, $mainimage) {
		switch($article_layout):
			case('standard_layout'):			
				$this->Image($mainimage['src'], 0, 0, 230, 310, 'JPG', '', 'L', 1, 300, 'C', false, false, false, true, false, false);														
				//The image is tall, I need a new page
				$curY = $this->getImageRBY();
				if( $curY > 200):
					$this->addPage();
				else:					
					$this->SetY($curY + 8);
				endif;
			break;
			default:
				$this->Image($mainimage['src'], 0, 0, 230, 310, 'JPG', '', 'L', 1, 300, 'C', false, false, false, true, false, false);	
				$this->addPage();					
			break;
		endswitch;
	}

	//Procesa el contenido de cada capítulo
    public function Chapter($postid) {
    	$content = new noriContent;
    	$content->WPLayer($postid);
    	
    	$article_layout = 'standard_layout';

    	$this->initMagazine();		
		
		//Indice
		$this->addPage();
		$this->Bookmark($content->title, 0, 0, '', '', array(0,64,128));

		$mainimage = $content->mainimage;
		
		if($mainimage){						
				$this->mainImage($article_layout, $mainimage);						 						
			}
		
		//Adding Fonts
		$pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );	
		$opensanslight = $this->addTTFfont( NORI_FONTS . 'Open_Sans/OpenSans-Light.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
		$opensanslightitalic = $this->addTTFfont( NORI_FONTS . 'Open_Sans/OpenSans-LightItalic.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );			

		$this->articleTitle($content->title, $pt_sans, 48);

		//Excerpt

		$this->SetFont($opensanslight, '', 12, NORI_GENFONTS . $opensanslight, false);
		$this->setFontSize(16);

		$this->setCellHeightRatio(1);
		$this->MultiCell(210, 0, $content->excerpt, 0, 'L', false);

		$this->Ln(4);

		$this->setFontSize(12);

		if($content->author):

			$this->Cell(0, 0, 'por ');
			$this->Ln(8);

			//Author
			foreach($content->author as $author):			
				$this->Cell(0, 0, $author);
			endforeach;

		$this->Ln(8);

		endif;

		//Date

		$this->Cell(0,0, $content->date);
		$this->Ln(8);

		//Si hay poco espacio necesito otra página
		if($this->GetY() > 260):
			$this->addPage();
		endif;

		//Contenido
		// Set font
		//$this->SetFont($opensanslight, '', 12, NORI_GENFONTS . $opensanslight , false);		
		$this->setFontSize(9);	
		

		//Split this in more calls maybe?

		$this->setEqualColumns(3, 60);
		$this->setCellHeightRatio(1.25);

		
		$paragraphs = $content->text;
		
		$this->selectColumn();

		//Cambiando dependiendo del tipo de elemento

		foreach($paragraphs as $paragraph):
			switch($paragraph['element']):
				case('p'):
					$this->setFontSize(9);
					$this->multiCell(0, 0, $paragraph['content'] , 0, 'L', false);
		 			$this->Ln(4);
		 		break;
		 		case('h1'):
		 		case('h2'):
		 		case('h3'):
		 		case('h4'):
		 		case('h5'):
		 			$this->setFontSize(12);
		 			$this->multiCell(0, 0, $paragraph['content'] , 0, 'L', false);
		 			$this->Ln(4);
		 		break;
		 		default:
		 			$this->multiCell(0, 0, $paragraph['content'] , 0, 'L', false);
		 			$this->Ln(4);	
			endswitch;		 	
		endforeach;

		// $this->selectColumn();
		// $this->writeHTML($content->text , true, false, true, false, 'L');	
		// $this->Ln();

		$this->endPage();

		$this->resetColumns();

		$contentimages = $content->contentimages;

    }

}

function nori_makePdf($postobj) {


	$artids = explode(',', $postobj);
	
	//Random file name based on time
	$fileid = uniqid();

	// create new PDF document

	$pdf = new noriPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	

	$pdf->setFontSubsetting(false);

	
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

	$htmlchain = NULL;

	//Strip post object of images

	//Construct something to build each article

	foreach($artids as $postid):
		$pdf->Chapter($postid);
	endforeach;

	// Indice
	// $pdf->setHeadText('Indice');
	// $pdf->addTOCPage();
	// $pdf->addTOC(1, 'courier', '.', 'Indice', '', array(128,0,0));

	// end of TOC page
	// $pdf->endTOCPage();

	// ---------------------------------------------------------

	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	$pdf->Output(NORI_FILESPATH .'articulo-'.$fileid.'.pdf', 'F');

	echo '<p>El archivo está listo para descargar</p>';
	echo '<p><a href="'.NORI_FILESURL . 'articulo-'.$fileid.'.pdf">Descargar</a></p>';
	
	//============================================================+
	// END OF FILE
	//============================================================+
}

//Util Functions 
//Uppercase for spanish chars!
function strtoupper_es($a) { 
    return strtr(mb_strtoupper($a, "utf-8"), array( 
      " á" => " A", 
      " é" => " E", 
      " í" => " I", 
      " ó" => " O", 
      " ú" => " U",
      " ñ" => " Ñ" 
    )); 
} 