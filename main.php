<?php
/*
Plugin Name: Nori
Plugin URI: http://www.apie.cl
Description: PDF Generation for Wordpress using Tcpdf library.
Version: 0.6alpha
Author: Pablo Selín Carrasco Armijo
Author URI: http://www.apie.cl
License: GPL2
*/

/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
main.php
Main resources for PDF selection

TODO:
1. Refine session storage method and make it more bulletproof
2. Make an article storage system vía URL parameters so's you can get an URL vía email with your stored selection
3. Optimize UI

*/

//Constants
define( 'NORI_PATH', plugin_dir_path(__FILE__) );
define( 'NORI_LIBS', NORI_PATH );
define( 'NORI_URL', plugin_dir_url(__FILE__) );
define( 'NORI_FILESPATH', WP_CONTENT_DIR . '/norifiles/');
define( 'NORI_FILESURL', WP_CONTENT_URL . '/norifiles/');
define( 'TCPDF_URL', plugin_dir_url(__FILE__) . '/tcpdf');
define( 'TCPDF_PATH', NORI_LIBS . 'tcpdf/');
define( 'NORI_FONTS', NORI_PATH . 'fonts/');
define( 'NORI_GENFONTS', NORI_FILESPATH . 'tcpdf-fonts/');
define( 'NORI_PRINTER_DUDE', 'pablo@apie.cl');
define( 'NORI_COSTPERPAGE', 400);
define( 'NORI_MAXPAGES', 80);
define( 'NORI_MAXARTICLES', 15);
define( 'NORI_ALPHA', true);

//Development constant to enable only registered users to use the app.

define('NORI_DEV', true);
define('NORI_LOGO', NORI_PATH . 'logo/ayc_logo.png');

//Text chains

define('NORIMSG_GENERATE', 'Generar PDF' );
define('NORIMSG_SENDTOPRINT', 'Enviar a imprenta');
define('NORIMSG_ADDARTICLE', 'Añadir');
define('NORIMSG_COMPOSE', 'Crear PDF');
define('NORIMSG_DELETESELECTION', 'Borrar selección');
define('NORIMSG_SYSTEMTITLE', 'Crea tu edición en PDF');
define('NORIMSG_SHORTINTRO', 'Puedes seleccionar este artículo e incluirlo en tu propia edición en formato PDF.');
define('NORIMSG_LISTTITLE', 'Has seleccionado los siguientes artículos:');
define('NORIMSG_NOARTICLES', 'No hay ningún artículo seleccionado');
define('NORIMSG_RENDERINTRO', 'En esta página puedes generar una edición en formato PDF a partir de los artículos que has seleccionado.');
define('NORIMSG_REORDERINTRO', 'También puedes reordenar los artículos arrastrándolos y cambiando su posición en la lista.');
define('NORIMSG_TIMEWARNING', 'Ten en cuenta que a mayor cantidad de artículos, mayor es el tiempo que demorará la generación de la edición.');
define('NORIMSG_TRASHINTRO', 'Puedes borrar artículos de la lista haciendo clic en el basurero.');
define('NORIMSG_STOREINTRO', 'Puedes guardar tu selección de artículos vía URL haciendo clic aquí');
define('NORIMSG_CREATEMAGAZINE', 'Crea tu revista en PDF');
define('NORIMSG_WHATSTHIS', '¿Qué es esto?');
define('NORIMSG_FILEREADY', 'Tu revista PDF está lista para descargar');
define('NORIMSG_DOWNLOAD', 'Descargar');

//js text chains

define('NORIMSG_LOADINGSELECTION', 'Cargando selección...');
define('NORIMSG_GENERATING', 'Generando tu revista PDF ...');
define('NORIMSG_TIMEEXPLANATION', 'La creación de la revista en PDF puede tomar un tiempo dependiendo de la cantidad de artículos e imágenes. Por favor, no cierres esta página.');
define('NORIMSG_UPDATEDORDER', 'Orden actualizado!');
define('NORIMSG_ERROR', 'Error general');
define('NORIMSG_NONAME', 'Falta que pongas tu nombre');
define('NORIMSG_NOMAIL', 'Falta que pongas tu e-mail');
define('NORIMSG_NOADDRESS', 'Falta que pongas tu dirección de envío');
define('NORIMSG_NOPHONE', 'Falta que pongas tu teléfono');
define('NORIMSG_NOVALIDMAIL', 'Escribe una dirección de correo válida');




//TCPDF Config

//Configuration for language, you can change the file corresponding to the main language you want to use
require_once( NORI_LIBS . 'tcpdf/config/lang/spa.php');

//Configuration for TCPDF
require_once( NORI_PATH . 'tcpdf_nori.php');
define('K_TCPDF_EXTERNAL_CONFIG', NORI_PATH . 'tcpdf_nori.php');

//Initialization of storage for pdf files and stuff

if(!is_dir(NORI_FILESPATH)){
	mkdir(WP_CONTENT_DIR . '/norifiles', 0755);
}

//Load TCPDF

//Tcpdf main file
require_once( NORI_LIBS . 'tcpdf/tcpdf.php' );	

//PDF generation Script
require_once( NORI_PATH . 'pdfgen.php');

//Mailing and form stuff
require_once( NORI_PATH . 'forms.php');

//The session stuff
function nori_StartSession() {
    if(!session_id()) {
    	//This isnt working.
    	//session_set_cookie_params(86400, '/', get_bloginfo('url'));
        session_start();
    }
}

function nori_EndSession() {
    session_destroy ();
}

function nori_SessionGet($key, $default='') {
    if(isset($_SESSION[$key])) {
        return $_SESSION[$key];
    } else {
        return $default;
    }
}

function nori_SessionSet($key, $value) {	
    	$_SESSION[$key] = $value;    
}

//Function to populate array of selected articles

//The selected posts are stored in a session
//There will be also an option to store the selection in a personal URL that will be sent via mail

function nori_addPost($postid) {
	if(isset($_SESSION['articlesel'])){
			//there is more than one article selected
			$noriarts = $_SESSION['articlesel'];

			$ids = explode(',', $noriarts);
			//Check that the article is not previously added
			if(!in_array($postid, $ids)):
				$ids[] = $postid;
			endif;
			$impids = implode(',', $ids);
			nori_SessionSet('articlesel', $impids);	
		} else {
			//only one article selected
			$id = $postid;		
			//set data
			nori_SessionSet('articlesel', $id);
		}
}

function nori_removePost($postid) {
	if(isset($_SESSION['articlesel'])) {		
		$noriarts = $_SESSION['articlesel'];		
		$ids = explode(',', $noriarts);
		$remkey = array_search($postid, $ids);
		unset($ids[$remkey]);		
		$impids_del = implode(',', $ids);			
		nori_SessionSet('articlesel', $impids_del);			
	}
}

function nori_articleCount(){
	if(isset($_SESSION['articlesel'])):
		$arts = explode(',', $_SESSION['articlesel']);
		$noarts = count($arts);
		return $noarts;				
		exit();
	else:		
		exit();
	endif;		
}

function nori_snippet() {	
	global $post;		
		echo '<div class="nori-css nori_snippet">';		
		echo '<p class="norititle">
			<span class="norititle-top">' . NORIMSG_CREATEMAGAZINE .'</span>
			<span class="norisub">' . NORIMSG_WHATSTHIS .'</span>
			</p>';
		echo '<span class="noricounter btn btn-small btn-info"><i class="icon-list-alt icon-white"></i> <span class="nori_number"> ... </span></span>';	
		if(is_single()):
			printf('<span title="Añadir artículo a tu selección" class="btn btn-small btn-success" data-id="' . $post->ID .'" id="add-article"><i class="icon-white icon-plus"></i> ' . NORIMSG_ADDARTICLE . '</span>');				
		else:
			printf('<span title="No puedes añadir nada aquí" class="btn btn-small btn-success disabled" data-id="' . $post->ID .'" id="add-article"><i class="icon-white icon-plus"></i> ' . NORIMSG_ADDARTICLE . '</span>');						
		endif;

		if($_GET['norimake'] == 1 || !isset($_SESSION['articlesel'])):
			printf('<span id="trigger-norisection" class="norimake-btn nori-btn btn btn-small btn-primary disabled inactive"><i class="icon-white icon-cog"></i> ' . NORIMSG_COMPOSE .' </span>');
		else:
			printf('<span id="trigger-norisection" class="norimake-btn nori-btn btn btn-small btn-primary inactive"><i class="icon-white icon-cog"></i> ' . NORIMSG_COMPOSE .' </span>');
		endif;

		//echo '<span class="info"></span>'
		echo '</div>';			
}

//Nori central functions for selecting articles, adding pdfs, etc.
function nori_centralOps($render = false) {			
		echo '<div class="nori_wrapper nori-css">';
		
		if($render == true):
			echo '<ul class="nori_articlelist" data-process="incheckout">';		
		else:
			echo '<h4>' . NORIMSG_SYSTEMTITLE . '</h4>';
			if(isset($_SESSION['articlesel'])):
				echo '<p>' . NORIMSG_LISTTITLE . '</p>';
			else:
				echo '<p>' . NORIMSG_SHORTINTRO . '</p>';
			endif;
			echo '<ul class="nori_articlelist" data process="compiling">';		
		endif;
		echo '</ul>';

		//Show Form for adding articles

		nori_selectForm($render);

		echo '</div><!--Nori Wrapper-->';	
}


function nori_selectForm($render) {
	global $post;	
	//Adds Form to article selection
		printf('<div class="formwrapper nori-css"><br/>');												
					
			if($render == true):				

				printf('<span class="nori-btn btn btn-success" id="generar-ajax"><i class="icon-book icon-white"></i> ' . NORIMSG_GENERATE . '</span>');
				//printf('<span class="nori-btn btn btn-success" id="generar-ajax-imprenta"><i class="icon-book icon-white"></i> ' . NORIMSG_SENDTOPRINT . '</span>');				

			else:
				printf('<span class="nori-btn btn" data-id="' . $post->ID .'" id="add-article"><i class="icon-plus"></i> ' . NORIMSG_ADDARTICLE . '</span>');				
				
			
			endif;

			printf('<span class="nori-btn btn btn-inverse" id="borrar-articulos" name="delete-all"><i class="icon-white icon-trash"></i> ' . NORIMSG_DELETESELECTION .'</span>');
		printf('<br/></div>');			
				
	}

//Calcular uso de tiempo
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

//Trabajar por directorios
function getFullPath($url){
	//return realpath(str_replace(get_bloginfo('url'), '.', $url));
	return $url;
}

//Añadir una página especial para el procesado del pdf, pago y otras hierbas

function noriSection() {
	$getnoripost = $_GET['norimake'];
	if($getnoripost == 1):
		include( NORI_PATH . 'render.php');	
		exit();
	endif;
}

add_action('template_redirect', 'noriSection', 1);


//Añadir una sección de carga via AJAX

function noriSection_ajax() {
	echo '<div id="nori_make_renderbox" class="nori-css">';
	echo '<div>';
	echo '<h1>' . NORIMSG_SYSTEMTITLE . '</h1>';
	echo '<div class="introstuff">';
	echo '<p> ' . NORIMSG_RENDERINTRO . '</p>';
	echo '<p> ' . NORIMSG_TIMEWARNING . '</p>';
	echo '<p> ' . NORIMSG_LISTTITLE .  '</p>';
	echo '</div>';

    nori_centralOps(true);

	echo '<div class="legend">';
	echo '<p> <i class="icon icon-move"></i> ' . NORIMSG_REORDERINTRO . ' </p>';
	echo '<p> <i class="icon icon-trash"></i> ' . NORIMSG_TRASHINTRO . '</p>';
	echo '</div>';

	echo '<div id="nori_result">';
	echo '</div>';
	echo '</div>';
	echo '<span class="btn btn-primary" data-function="toggle-section"><i class="icon-white icon-remove"></i> Cerrar</span>';
	echo '</div>';	
	exit();
}

/*
Session Management
We use sessions for storing article selection based on IDs.
Code from http://devondev.com/simple-session-support/
*/

add_action('init', 'nori_StartSession', 1);
add_action('wp_logout', 'nori_EndSession');
add_action('wp_login', 'nori_EndSession');

/*
Ajax Functions
*/
//Make the pdf

function ajaxNori() {	
	$articles = $_SESSION['articlesel'];		

	if($articles):
		if($_POST['forprint'] == 'yes'):
			$extradata = $_POST['extradata'];			
			nori_makePdf($articles, true, $extradata);						
			exit();
		else:		
			nori_makePdf($articles);
			exit();
		endif;		
	endif;
}

add_action('wp_ajax_ajaxNori', 'ajaxNori');
add_action('wp_ajax_nopriv_ajaxNori', 'ajaxNori');


//Single item layout
function articleUnit($id, $checkout = false, $onlypop = false) {
	if($checkout == true && $onlypop == false):
		echo '<li class="articleUnit incheckout" data-id="' . $id .'" id="selarticle-' . $id .'"> <i class="icon-move icon-white"></i> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash icon-white"></i></li>';
	elseif($checkout == false && $onlypop == false):
		echo '<li class="articleUnit" data-id="' . $id .'" id="selarticle-' . $id .'"> ' . get_the_title(intval($id)) . ' <i title="Quitar artículo" class="nori-ui articledel icon-trash icon-white"></i></li>';
	else:
		echo '<li class="articleUnit" data-id="' . $id .'" id="selarticle-' . $id .'"> ' . get_the_title(intval($id)) . '  <i title="Quitar artículo" class="nori-ui articledel icon-trash"></i></li>';
	endif;
}


function ajaxSessionNori() {
	global $post;
	$command = $_POST['command'];
	$id = $_POST['id'];
	
	switch($command):
		case('add'):			
			$posts = 0;
			//Check that is not the same post added twice
			if(isset($_SESSION['articlesel'])):
				$posts = explode(',', $_SESSION['articlesel']);
			endif;
			if(is_array($posts)):
				if(!in_array($id, $posts)):
					nori_addPost($id);					
					//articleUnit($id, false);					
				endif;
			elseif($posts != $id):
					nori_addPost($id);
					//articleUnit($id, false);
			endif;
			exit();
		break;
		
		case('delete'):
			if(isset($_SESSION['articlesel'])):
				nori_removePost($id);
			endif;						
			exit();
		break;
		
		case('delete-all'):
			nori_EndSession();
			exit();
		break;

		case('populate'):
			if(isset($_SESSION['articlesel'])):				
				$posts = explode(',', $_SESSION['articlesel']);				
					foreach($posts as $id):
						articleUnit($id, false);
					endforeach;				
			endif;
			exit();
		break;

		case('onlypopulate'):
			if(isset($_SESSION['articlesel'])):				
				$posts = explode(',', $_SESSION['articlesel']);				
					foreach($posts as $id):
						articleUnit($id, false, true);
					endforeach;				
			endif;
			exit();
		break;

		case('count'):									
			echo nori_articleCount();						
			exit();
		break;

		case('populateandsort'):
			if(isset($_SESSION['articlesel'])):
				$posts = explode(',', $_SESSION['articlesel']);				
					foreach($posts as $id):
						articleUnit($id, true);
					endforeach;				
			endif;
			exit();
		break;
		
		case('update'):

			if(isset($_POST['orderdata'])):					
				nori_SessionSet('articlesel', $_POST['orderdata']);				
			endif;
			exit();
		break;

		case('ajaxSection'):
			noriSection_ajax();
			exit();
		break;		

	endswitch;
		
}



add_action('wp_ajax_ajaxSessionNori', 'ajaxSessionNori');
add_action('wp_ajax_nopriv_ajaxSessionNori', 'ajaxSessionNori');


//Add articles

//Render resulting page

//Styles and scripts

function noristylesandscripts() {	
	
	if(!is_admin()):
		wp_register_style('noricss', NORI_URL . '/nori.css');
		wp_enqueue_style('noricss');

		wp_register_style('bootstrap-nori', NORI_URL . '/bootstrap-nori.css');
		wp_enqueue_style( 'bootstrap-nori');

		wp_register_script('jquery-ui', NORI_URL . '/js/jquery-ui-1.9.2.custom.min.js', 'jquery');
		wp_enqueue_script('jquery-ui');		

		wp_register_script('jquery-form', NORI_URL . '/js/jquery.form.js', 'jquery');
		wp_enqueue_script('jquery-form');

		wp_register_script('norijs', NORI_URL . '/js/nori.js', array('jquery', 'jquery-ui', 'jquery-form'));
		wp_enqueue_script('norijs');

		wp_register_script('bootstrap', NORI_URL . '/js/bootstrap.min.js');
		wp_enqueue_script('bootstrap');

		wp_localize_script('norijs', 'noriAJAX', array(
		 	'ajaxurl' => admin_url( 'admin-ajax.php' ),
		 	'noriurl' => NORI_URL,
		 	'msg_generating' => NORIMSG_GENERATING,
		 	'msg_error' => NORIMSG_ERROR,
		 	'msg_updatedorder' => NORIMSG_UPDATEDORDER,
		 	'msg_nophone' => NORIMSG_NOPHONE,
		 	'msg_noaddress' => NORIMSG_NOADDRESS,
		 	'msg_nomail' => NORIMSG_NOMAIL,
		 	'msg_noname' => NORIMSG_NONAME,
		 	'msg_loadingselection' => NORIMSG_LOADINGSELECTION,
		 	'msg_novalidmail' => NORIMSG_NOVALIDMAIL,
		 	'msg_timeexplanation' => NORIMSG_TIMEEXPLANATION
		 	));
	endif;
	
}

add_action('wp_enqueue_scripts', 'noristylesandscripts');

