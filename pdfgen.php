<?php
/*
PDF Generation scripts

TODO:

1. Define a layout implementation method
2. Define layout profiles in relation to number of images
3. Create a system for styling child elements.

*/


/* 	Makes an standard object article with stuff for publishing. 
* 	It can be populated with other stuff from other cms or database,
*	You just have to plug the function to make a content object	
*
*/

class noriContent {
	//Content variables, you can add more as you wish
	var $title;
	var $author;
	var $mainimage;
	var $contentimages;
	var $text;	
	var $excerpt;	
	var $date;
	var $type;
	var $maincolor;
	var $numimages;		

/*	Wordpress Content Layer.
* 	Here I make a content object for later use in the pdf generation
*
*/

	public function WPLayer($id) {		
		$article = get_post($id);		
		$this->title = $article->post_title;
		$this->numimages = 0;
	
		//If you need other type of autor you can also set it up.
		//Im taking it from some custom post meta
	
		if(get_post_meta($id, 'aycmb_autor', true)):			
			$authors = get_post_meta($id, 'aycmb_autor', false);		
			$nauts = count($authors);
			
			foreach($authors as $key=>$author):
				if($key > 0 && $key + 1 == $nauts ):
					$autstring .= ' y ';
				endif;
					$autstring .= get_the_title($author);				
			endforeach;

		endif;
		
		$this->author = $autstring;

		$this->date = mysql2date('F Y', $article->post_date, true);

		//First image is featured image, the others are the others images attached to the post.	

		if(has_post_thumbnail($id)){
				$this->mainimage = array();
				$thumbid = get_post_thumbnail_id($id);
				$src = wp_get_attachment_image_src($thumbid, 'full');
				$this->mainimage['src'] = getFullPath($src[0]);
				$this->mainimage['title'] = html_entity_decode(get_the_title($thumbid), ENT_QUOTES, 'UTF-8');
				$this->mainimage['width'] = $src[1];
				$this->mainimage['height'] = $src[2];
				$this->mainimage['relation'] = $this->mainimage['width'] / $this->mainimage['height'];
			}

		$args = array( 
			'post_type' => 'attachment', 
			'post_mime_type' => 'image',
			'post_parent' => $id,
			'exclude'=> $thumbid
		);	
		$images = get_children($args);			
		//print_r($images);
		if($images):			
			$this->contentimages = array();
			foreach($images as $image) {				
				$src = wp_get_attachment_url($image->ID);							
				$this->contentimages[] = array(
					'id' => $image->ID,
					'src' => getFullPath($src),
					'title' => html_entity_decode(get_the_title($image->ID), ENT_QUOTES, 'UTF-8')
					);			
			}
			$this->numimages = count($this->contentimages);
		endif;

		//Excerpt
		$this->excerpt = $article->post_excerpt;

		//Type
		$ptype = get_post_type($article->ID);
		$ptypeobj = get_post_type_object($ptype);
		$this->type = $ptypeobj->labels->name; 

		$this->setArticleTypeColor($ptype);

		// Print text using writeHTMLCell()
		$pretext = apply_filters('the_content', $article->post_content);

		//Clean HTML	

		$this->domParser($pretext);		
	}

	// cambia ciertos atributos basado en el tipo de contenido
	public function setArticleTypeColor($type) {
		switch($type):

			case('ayc_postcur'):
				$this->maincolor = '#36937F';								
			break;

			case('ayc_artcrit'):
				$this->maincolor = '#8F5D86';
			break;

			case('ayc_cronica'):
				$this->maincolor = '#DC9A51';
			break;

			case('ayc_entrevista'):
				$this->maincolor = '#6778B4';
			break;

			case('ayc_ensayo'):
				$this->maincolor = '#55536C';
			break;

			case('post'):
				$this->maincolor = '#B2BA8F';
			break;			

		endswitch;
	}


	/*
	*	Using PHP Dom, parse all the main content and get a clean HTML array with only the essential stuff, classified by tag name
	*	this way you can choose separate styling for each html element. I haven't figured out a way to style sub sub elements like
	*	<strong> and <em> yet.
	*/

	public function domParser($text) {

		//Strip tags

		$cleantext = strip_tags($text, '<p>, <em>, <br>, <strong>, <h1>, <h2>, <h3>, <h4>, <h5>, <blockquote>, <div>, <cite>, <sup>, <img>, <li>');

		$domdoc = new DOMDocument();
		
		//Turn stuff into an object for easey parsing
		$utf8domdoc = mb_convert_encoding($cleantext, 'HTML-ENTITIES', "UTF-8");
		$domdoc->loadHTML($utf8domdoc);		
		
		//Remove unwanted stuff		

		$xpath = new DOMXPath($domdoc);
		$divs = $xpath->query('//div');
		
		foreach($divs as $div):
			$div->parentNode->removeChild($div);
		endforeach;

		$cleandom = $xpath->query('//p | //h1 | //h2 | //h3 | //h4 | //h5 | //li');

		foreach($cleandom as $key=>$cd):
			$this->text[$key]['element'] = $cd->nodeName;
			$this->text[$key]['content'] = $cd->nodeValue;
		endforeach;

		//Last step with filtered HTML
		// $this->text = $domdoc->saveHTML();

	}
}

/*
* Extending TCPDF Class for custom stuff and making the PDF.
*/


//Extiendo la clase para hacer pdfs.
class noriPDF extends TCPDF {
	var $artitle;
	var $art_type;	
	var $maincolor;
	var $pubtitle;
	var $bleed_margin;
	var $pagesize;
	var $frontpage_image;


	//Inicializa las páginas y cosas básicas de formato
	public function initMagazine() {
		// set display mode
		$this->SetDisplayMode($zoom='fullpage', $layout='TwoColumnRight', $mode='UseNone');

		// set pdf viewer preferences
		$this->setViewerPreferences(array('Duplex' => 'DuplexFlipLongEdge'));

    	//Makes a nasty bug with the TOCPAGE
    	//$this->setBooklet(true);    	

    	$this->bleed_margin = 5;

    	$this->pagesize[0] = 230 + $this->bleed_margin;     	
    	$this->pagesize[1] = 310 + $this->bleed_margin;
    	
    	$frontpages = array();
    	$frontpages[] = array(
    						'src' => NORI_URL . 'frontpages/daniel_reyes_portada.jpg',
    						'name' => 'Daniel Reyes León');
    	$frontpages[] = array(
    						'src' => NORI_URL . 'frontpages/javier_rodriguez_portada.jpg',
    						'name' => 'Javier Rodríguez');
    	$frontpages[] = array(
    						'src' => NORI_URL . 'frontpages/jorge_gonzalez_portada.jpg',
    						'name' => 'Jorge González'
    					);	   			
    		

    	$chfront = array_rand($frontpages);
    	
    	$this->frontpage_image = $frontpages[$chfront];
    	
    	$this->index_image = NORI_URL . 'examples/creditos_test_blanco.jpg';
    	$this->indexbg_image = NORI_URL . 'examples/indice_test.jpg';

    	$pagesize = array($this->pagesize[0], $this->pagesize[1]);

    	$this->setPageFormat($pagesize, 'P');
    	    	
	}

	public function setAllCropMarks() {
		//Add crop marks.
    	//Marcas de corte		
		$this->cropMark($this->bleed_margin, $this->bleed_margin,1,10, 'TL');
		$this->cropMark($this->pagesize[0] - $this->bleed_margin , $this->bleed_margin,1,10, 'TR');
		$this->cropMark($this->pagesize[0] - $this->bleed_margin, $this->pagesize[1] - $this->bleed_margin,1,5, 'BR');
		$this->cropMark($this->bleed_margin, $this->pagesize[1] - $this->bleed_margin,1,5, 'BL');
	}

	//Hace la portada
	public function makeFrontpage($front_image) {	
		$auto_page_break = $this->getAutoPageBreak();
		$this->SetAutoPageBreak(false, 0);
		$this->AddPage();		
		$bMargin = $this->getBreakMargin();				
		//Portada es una pura imagen
		$this->Image($front_image['src'], 0, 0, $this->pagesize[0], $this->pagesize[1], '', '', '', false, 300, '', false, false, 0);
		
	}

//Colofon
	public function makePreIndex($index_image) {
		$auto_page_break = $this->getAutoPageBreak();		
		$this->SetAutoPageBreak(false, 0);		
		$this->AddPage();		
		$bMargin = $this->getBreakMargin();				
		//Portada es una pura imagen
		$this->Image($index_image, 0, 0, $this->pagesize[0], $this->pagesize[1], '', '', '', false, 300, '', false, false, 0);
		//Añadir datos del colofon
		$this->SetLeftMargin(35);
		$this->setY(64);
		//Colofon puesto a la maleta, luego habrá que desarrollar un panel para añadir esta info
		$blocks = array();
		$blocks[] = 	
		array(
			'Adrede Editora',
			'Seminario #295, Providencia',
			'Santiago de Chile',
			'+56 2 250 456 78',
			'info@adrededitora.cl',
			'adrededitora.cl'
			);
		$blocks[] = 
		array(
			'Asesoría digital:',
			'A Pie',
			'escribenos@apie.cl',
			'+56 9 947 042 53',
			'Santiago de Chile'
			);
		$blocks[] = 
		array(
			'Imagen portada:',
			'Cortesía de ' . $this->frontpage_image['name']
			);
		$blocks[] =
		array(
			'Licencia:',
			'Algunos derechos reservados.',
			'Revista Arte y Crítica se encuentra bajo una Licencia Creative Commons:',
			'Atribución / No Comercial / Sin derivadas / 3.0 /  Internacional.'
			);

		foreach($blocks as $block){
			$this->colophon_block($block);
			$this->Ln();
			$this->Ln();
			}

		}  


	public function colophon_block($linesarray) {
		foreach($linesarray as $key=>$line){
			if($key == 0):
				$this->setFontSize(16);
			else:
				$this->setFontSize(12);
			endif;	
			$this->Cell(100, 0, $line);
			$this->Ln();			
		}
	}


 //Page header
    public function Header() {
    	if($this->PageNo() != 1):
	    	$this->SetY(4 + $this->bleed_margin);	    	
	        // Set font        
	        $pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
			$this->setFillColorArray($this->convertHTMLColorToDec($this->maincolor));
			$this->setWhiteColorText();
			$this->SetFont($pt_sans, '', 10, NORI_GENFONTS . $pt_sans , false);		
	        $this->setFontSize(8);
	        // Title Line
	        $curY = $this->getY();
	        $this->setDrawColorArray($this->convertHTMLColorToDec($this->maincolor));
	        $this->Line(0, $curY, PDF_MARGIN_LEFT, $curY);
	        // Title
	        $this->setCellPadding(0.2);
	        $this->Cell(30, 4, $this->art_type, 0, false, 'L', true, '', 0, false, 'M', 'M');
	    endif;    
        
    }

    // Page footer
    public function Footer() {
    	if($this->pageNo() != 1):
	        // Position at 15 mm from bottom
	        $this->SetY(-7 - $this->bleed_margin);
	        // Set font
	        $pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
	        $this->setBlackColorText();
			$this->SetFont($pt_sans, '', 10, NORI_GENFONTS . $pt_sans , false);		
	        $this->setFontSize(10);
	        // Page number   
	        $curY = $this->getY();     
	        $this->Line(160, $curY + 3, 183, $curY + 3);
	        $this->Cell(0, 0, $this->title . '  |   '.$this->getAliasNumPage() , 0, false, 'R', 0, '', 0, false, 'T', 'M');
	        endif;	    
	    	$this->setAllCropMarks();    	    
    }	

    //Procesa las imágenes del capítulo
    public function process_chapter_images($contentimages) {
	$this->AddPage();									
			//Coordenadas
			$y = 10 + $this->bleed_margin;
			foreach($contentimages as $image){				
				$this->Image($image['src'], 10 + $this->bleed_margin, $y, 80, 0, 'JPG', '', 'M', true, 300, 'C', false, false, 1, false, false, false);
				$curY = $this->getImageRBY();
				$this->setY($curY);
				$curY = ($curY+0.6);									
				$this->MultiCell(0,0,$image['title'], 0, 'C');				
				//doy el salto a la otra imagen
				$y = $curY+6;
			}

	}
	
	public function setBlackColorText() {
		$this->setColor('text', 0, 0, 0, 100);
	}


	public function setWhiteColorText() {
		$this->setColor('text', 0, 0, 0, 0);
	}


	public function setPageBackground($bgcolor) {

	}

	//Crea ek título del artículo
	public function articleTitle($title, $font, $size) {
		//Titulo
		// Set font
		$this->SetFont($font, '', 12, NORI_GENFONTS . $pt_sans , false);		
		$this->setFontSize($size);
		
		$parsed_title = strtoupper_es($title);
		
		$this->setCellHeightRatio(0.9);				


		$this->setTextColorArray($this->convertHTMLColorToDec($this->maincolor));

		$this->MultiCell(210,20,$parsed_title, 0, 'L', false, 1, 16, $this->GetY(), true, 1, false, true, 0, 'T', true);
		
		$this->Ln(4);
	}


	public function mainImage($article_layout, $mainimage) {
				$relation = $mainimage['relation'];
				$comp_height = round(230 / $relation, 0, PHP_ROUND_HALF_UP);
				$this->Image($mainimage['src'], 0 + $this->bleed_margin, 0 + $this->bleed_margin, 230, $comp_height , '', '', 'T', 1, 300, 'C', false, false, false, true, false, false);
				
				$this->setWhiteColorText();
				$this->setFontSize(8);
				
				$curY = $this->getImageRBY();				
				$this->setFillColor(0,0,0,100);
				$this->setAlpha(0.6);
				$this->Text(0, $curY - 6 , $mainimage['title'], false, false, true, 0,0, 'R', true);
				$this->setAlpha(1);
				//The image is tall, I need a new page
				
				if( $curY > 220):
					$this->addPage();
				else:					
					$this->SetY($curY + 4);
				endif;

				
	}

	//Processes the initial article content: Title, author, mainimage, date.

	public function articleIntro($content, $layout) {
		$mainimage = $content->mainimage;
			
		if($mainimage){										
				$this->mainImage($layout, $mainimage);						 						
			}
		
		//Adding Fonts
		
		//Not a TTF font, had to convert using: http://www.freefontconverter.com/ and then http://www.xml-convert.com/ttftopdf and then do a manual upload to the font definition folder
		$bebasneue = $this->addFont( 'bebasn');	

		//Normal TTF fonts, not so traumatic
		$pt_sans = $this->addTTFfont( NORI_FONTS . 'PT_Sans_Narrow/PT_Sans-Narrow-Web-Regular.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );	
		$opensanslight = $this->addTTFfont( NORI_FONTS . 'Open_Sans/OpenSans-Light.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );
		$opensanslightitalic = $this->addTTFfont( NORI_FONTS . 'Open_Sans/OpenSans-LightItalic.ttf' ,'TrueTypeUnicode' , '', 32, NORI_GENFONTS );			

		$this->articleTitle($content->title, 'bebasn', 48);

		//Excerpt

		$this->SetFont($opensanslight, '', 12, NORI_GENFONTS . $opensanslight, false);
		$this->setFontSize(16);

		$this->setCellHeightRatio(1);
		$this->MultiCell(210, 0, $content->excerpt, 0, 'L', false);

		$this->Ln(4);		

		if($content->author):			

			//Author
			$nchars = $this->getNumChars($content->author);
			$cellwidth = $nchars * 3 + 6;

			$this->setFontSize(12);
			$this->SetFillColorArray($this->convertHTMLColorToDec($this->maincolor));
			$this->setWhiteColorText();			
			$this->setCellPaddings(1, 1, 1, 1);
			$this->Cell( $cellwidth, 0, 'por ' . $content->author, 0, 0, 'L', true);		

		$this->Ln(8);

		endif;

		//Date

		$this->setBlackColorText();
		$this->Cell(0,0, $content->date);
		$this->Ln(8);

		//Si hay poco espacio necesito otra página
		if($this->GetY() > 260):
			$this->addPage();
		endif;

	}

	//Procesa el contenido de cada capítulo
    public function Chapter($postid) {

    	$content = new noriContent;
    	$content->WPLayer($postid);

    	$this->maincolor = $content->maincolor;     	

    	$contentimages = $content->contentimages;
    	$numimages = count($content->contentimages);

    	if($numimages >= 1):
    		$layout = 'images_layout';    		
    	else:
	    	$layout = 'standard_layout';
	    endif;
    	
    	$this->addPage();	    		    	
		$this->Bookmark($content->title, 0, 0, '', '', array(0,64,128));
		

    	$chapter_page = 0;

    	//Need to count images and make holes for them...

		//Page region layouts		

    	$this->art_type = $content->type;
    	
		//Article Intro

		$this->startPageGroup();
		$this->articleIntro($content, $layout);	

			if($layout == 'standard_layout'):

				$this->setEqualColumns(3, 68);	
				$cellheight = 0;
				$cellwidth = 0;

			elseif($layout == 'images_layout'):
				$this->setEqualColumns(3, 68);
				$first_image = $content->contentimages[0];
				$cellheight = 0;
				$cellwidth = 0;						

		endif;

		//Contenido
		
		$this->setFontSize(9);			
		
		$this->setCellHeightRatio(1.25);	 		 		
		
		$paragraphs = $content->text;		

		$this->setBlackColorText();			

		$this->renderMainContent($paragraphs, $cellwidth);			
			
		//Reset things
		$this->endPage();
		$this->resetColumns();				
    }

//Renders text content
public function renderMainContent($content, $cellwidth){
	foreach($content as $paragraph):				
		//html_entity_decode(get_the_title($thumbid), ENT_QUOTES, 'UTF-8')
		$string_iso = $paragraph['content'];	
				switch($paragraph['element']):
					case('p'):
						$this->setFontSize(9);
						$this->multiCell($cellwidth, 0, $string_iso , 0, 'L', false );
			 			$this->Ln(4);		 			
			 		break;			 		
			 		case('li'):
			 			$this->setFontSize(9);
			 			$list_item = '- ' . $string_iso;
			 			$this->multiCell($cellwidth, 0, $list_item , 0, 'L', false );
			 			$this->Ln(2);
			 		break;			 			
			 		case('h1'):
			 		case('h2'):
			 		case('h3'):
			 		case('h4'):
			 		case('h5'):
			 			$this->setFontSize(12);
			 			$this->multiCell($cellwidth, 0, $string_iso , 0, 'L', false );
			 			$this->Ln(4);
			 		break;
			 		default:
			 			$this->multiCell($cellwidth, 0, $string_iso , 0, 'L', false );
			 			$this->Ln(4);	
				endswitch;
		endforeach;	
}

public function nori_makePrintableBooklet() {
	//Get number of pages
	$numpages = $this->getNumPages();	
	if($numpages < NORI_MAXPAGES):
		//Si el número de páginas no es multiplo de 4 necesito añadir páginas en consecuencia para hacer un cuadernillo imprimible
		if($numpages % 4 != 0):
			$remains = ($numpages + 4) - ($numpages % 4);
		
			$this->setPage($numpages);	
		
			while($numpages < $remains):
				//replace with filling options
				$this->AddPage();
				$numpages++;
			endwhile;

		endif;
	else:
		echo '<p>Ha superado el número máximo de páginas para imprimir. Quizás podría retirar algunos artículos.</p>';
	endif;
}

 

}


function nori_makePdf($postobj, $use = 'web', $extradata = NULL) {	

	$artids = explode(',', $postobj);
	
	//Random file name based on time
	$fileid = uniqid();

	// create new PDF document

	$pdf = new noriPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);	

	$pdf->setFontSubsetting(false);

	
	// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('A Pie');
	$pdf->SetTitle('Arte y Crítica');
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


	$pdf->initMagazine();		

	//Construct something to build each article

	foreach($artids as $postid):
		$pdf->Chapter($postid);
	endforeach;	

	//Make frontpage and put it in front
	if($pdf->frontpage_image):
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->makeFrontpage($pdf->frontpage_image);
		$curpage = $pdf->pageNo();
		$pdf->movePage($curpage, 1);		
	endif;

	//Make preindex and put it in second page
	if($pdf->index_image):
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->makePreIndex($pdf->index_image);
		$curpage = $pdf->pageNo();
		$pdf->movePage($curpage, 2);
	endif;


	// Indice		
	$pdf->addTOCPage();
	$pdf->SetAutoPageBreak(false, 0);
	//Poner la imagen de fondo
	$pdf->Image($pdf->indexbg_image, 0, 0, $pdf->pagesize[0], $pdf->pagesize[1], '', '', '', false, 300, '', false, false, 0);
	$pdf->SetTopMargin(124);
	$pdf->SetLeftMargin(30);
	$pdf->SetRightMargin(30);
	$pdf->SetFont($opensanslight, '', 12, NORI_GENFONTS . $opensanslight, false);
	$pdf->setFontSize(12);
	$pdf->setBlackColorText();				
	$pdf->addTOC(3,'', '.', 'Indice', '', array(1,1,1));	
	$pdf->endTOCPage();

	//Sort pages for printing (need a nice way of combine pages)

	if($use == 'print'):
	 	$pdf->nori_makePrintableBooklet();
	endif;


	// ---------------------------------------------------------

	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	$numpages = $pdf->getNumPages();	
	
	switch($use){
		case('web'):
			$pdf->Output(NORI_FILESPATH .'arte-y-critica-org-'.$fileid.'.pdf', 'F');
			echo '<div class="nori-ajaxstatus nori-success">';
			echo '<h3>'. NORIMSG_FILEREADY .'</h3>';				
			echo '<p><a class="btn btn-primary" href="'.NORI_FILESURL . 'arte-y-critica-org-'.$fileid.'.pdf"><i class="icon-download-alt icon-white"></i> ' . NORIMSG_DOWNLOAD .'</a></p>';			
			echo '</div>';
		break;
		case('print'):
			$pdf->Output(NORI_FILESPATH .'arte-y-critica-org-print-'.$fileid.'.pdf', 'F');
			echo '<h3>Información y datos enviados por mail.</h3>';
			$pdflink = NORI_FILESURL . 'arte-y-critica-org-'.$fileid.'.pdf';					
			sendPDFforPrint($extradata, $pdflink, NORI_PRINTER_DUDE, $numpages);
		break;
		case('edition'):
			//Check if file exists (this generation is expensive.)
			if(!file_exists(NORI_FILESPATH . 'edicion-arte-y-critica-org-'.$extradata.'.pdf')){				
					$pdf->Output(NORI_FILESPATH .'edicion-arte-y-critica-org-'.$extradata.'.pdf', 'F');				
					$pdflink = NORI_FILESURL . 'edicion-arte-y-critica-org-'.$extradata.'.pdf';
					//Print stuff in file
					update_post_meta( $extradata, 'edicion_url', $pdflink);
					echo '<h3>Archivo de edición Generado</h3>';				
					echo '<p><a class="btn btn-success" href="'.NORI_FILESURL . 'edicion-arte-y-critica-org-'.$extradata.'.pdf"><i class="icon-download-alt icon-white"></i> ' . NORIMSG_DOWNLOAD .'</a></p>';			
					echo '<p>También se ha añadido la URL del archivo a un campo personalizado que se vinculará automáticamente al número de edición</p>';
				} else {
					echo '<h3>Ya habías generado antes el archivo de edición</h3>';
				}
		break;
	}
	
	
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

//Generates a compilation of the number ONCE
//Really specific to the website.

function nori_makePdfEdition($edition) {
	//args postcuratorial
	$ids = array();
	$args_postcuratorial = array(
							'numberposts'=> 1,
							'post_type'=> 'ayc_postcur',
							'meta_key' => 'aycmb_edicion',
							'meta_value'=> $edition
							);
	//args resto articulos
	$args_restoarticulos = array(
							'numberposts' => -1,
							'post_type' => array(
									'ayc_artcrit',
									'ayc_cronica',
									'ayc_entrevista',
									'ayc_ensayo',
									'post'
									),
							'meta_key' => 'aycmb_edicion',
							'meta_value'=> $edition,
							'orderby'=> 'rand'											
							);
	$postcuratorial = get_posts($args_postcuratorial);
	$restoarticulos = get_posts($args_restoarticulos);
	foreach($restoarticulos as $ra) {
		$ids[] = $ra->ID;		
	}
		
		array_unshift($ids, $postcuratorial[0]->ID);
		$strids = implode(',', $ids);
		nori_makePdf($strids, 'edition', $edition);
} 