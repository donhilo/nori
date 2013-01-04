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
<<<<<<< HEAD

=======
4. Add and remove the articles via AJAXXX
>>>>>>> a7e0b4e743d0e529abc37fd38fa4d86b96518297


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

//Development constant to enable only registered users to use the app.

define( 'NORI_DEV', true);
define('NORI_LOGO', NORI_PATH . 'logo/ayc_logo.png');

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

//Nori central functions for selecting articles, adding pdfs, etc.
function nori_centralOps() {
	if(is_user_logged_in()):		
		echo '<div class="nori_wrapper">';
		
		if($_GET['norimake'] == 1):
			echo '<ul class="nori_articlelist" data-process="incheckout">';		
		else:
			echo '<ul class="nori_articlelist" data process="compiling">';		
		endif;
		echo '</ul>';

		//Show Form for adding articles

		nori_selectForm();

		echo '</div><!--Nori Wrapper-->';
	endif;
}


function nori_selectForm() {
	global $post;	
	//Adds Form to article selection
		printf('<div class="formwrapper"><br/>');												
					
			if($_GET['norimake'] == 1):				

				printf('<span class="nori-btn btn btn-success" id="generar-ajax"><i class="icon-book icon-white"></i> Generar PDF</span>');
				printf('<span class="nori-btn btn btn-success" id="generar-ajax-imprenta"><i class="icon-book icon-white"></i> Enviar a imprenta</span>');

			else:
				printf('<span class="nori-btn btn" data-id="' . $post->ID .'" id="add-article"><i class="icon-plus"></i>Añadir</span>');				
				printf('<a class="nori-btn btn" href="' . add_query_arg('norimake', 1, get_bloginfo('url')) . '"><i class="icon-cog"></i> Componer libro </a>');
			
			endif;

			printf('<span class="nori-btn btn btn-inverse" id="borrar-articulos" name="delete-all"><i class="icon-white icon-trash"></i> Borrar todos los artículos</span>');
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
function articleUnit($id, $checkout = false) {
	if($checkout == true):
		echo '<li class="articleUnit incheckout" data-id="' . $id .'" id="selarticle-' . $id .'"> <i class="icon-move"></i> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash"></i></li>';
	else:
		echo '<li class="articleUnit" data-id="' . $id .'" id="selarticle-' . $id .'"> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash"></i></li>';
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
					articleUnit($id, false);					
				endif;
			elseif($posts != $id):
					nori_addPost($id);
					articleUnit($id, false);

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

	endswitch;
		
}



add_action('wp_ajax_ajaxSessionNori', 'ajaxSessionNori');
add_action('wp_ajax_nopriv_ajaxSessionNori', 'ajaxSessionNori');


//Add articles

//Render resulting page

//Styles and scripts

function noristylesandscripts() {	
	
	//if(!is_admin()):
		wp_register_style('noricss', NORI_URL . '/nori.css');
		wp_enqueue_style('noricss');

		wp_register_script('jquery-ui', NORI_URL . '/js/jquery-ui-1.9.2.custom.min.js', 'jquery');
		wp_enqueue_script('jquery-ui');		

		wp_register_script('jquery-form', NORI_URL . '/js/jquery.form.js', 'jquery');
		wp_enqueue_script('jquery-form');

		wp_register_script('norijs', NORI_URL . '/js/nori.js', array('jquery-ui', 'jquery-form'));
		wp_enqueue_script('norijs');

		wp_register_script('bootstrap', NORI_URL . '/js/bootstrap.min.js', 'jquery');
		wp_enqueue_script('bootstrap');

		wp_localize_script('norijs', 'noriAJAX', array(
		 	'ajaxurl' => admin_url( 'admin-ajax.php' ),
		 	'noriurl' => NORI_URL
		 	));
	//endif;
	
}

add_action('wp_enqueue_scripts', 'noristylesandscripts');

