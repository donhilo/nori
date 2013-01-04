<?php 
function domParser($text) {

		//Strip tags

		$cleantext = strip_tags($text, '<p>, <em>, <br>, <strong>, <h1>, <h2>, <h3>, <h4>, <h5>, <blockquote>, <div>, <cite>, <sup>');

		$domdoc = new DOMDocument();
		
		//Turn stuff into an object for easey parsing

		$domdoc->loadHTML('<?xml encoding="UTF-8">' . $cleantext);		
		
		//Remove unwanted stuff		

		$xpath = new DOMXPath($domdoc);		
		$pes = $xpath->query('//p');
		$this->parsedtext = $pes;


		foreach($divs as $div):
			$div->parentNode->removeChild($div);
		endforeach;

		//Last step with filtered HTML
		$text = $domdoc->saveHTML();

	}


