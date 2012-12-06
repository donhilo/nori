<?php
/*
Plugin Name: Nori
Plugin URI: http://www.apie.cl
Description: PDF Generation for Wordpress using Tcpdf library.
Version: 0.1
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
4. Add and remove the articles via AJAXXX


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
		
		unset($ids[$postid]);
		
		//$impids = implode(',' $ids);
		nori_SessionSet('articlesel', $impids);			
	}
}

//Nori central functions for selecting articles, adding pdfs, etc.
function nori_centralOps() {
	if(is_user_logged_in()):		
		echo '<div class="nori_wrapper">';
		
		echo '<ul class="nori_articlelist">';
		// if(isset($_SESSION['articlesel'])):
				
		// 			$artids = nori_SessionGet('articlesel');
		// 			if($artids):
		// 				$norids = explode(',', $artids);
						
						
		// 				foreach($norids as $norid):
							
		// 					echo '<li data-id="' . $norid .'" id="selarticle-' . $norid .'"> <i class="icon-move"></i> ' . get_the_title(intval($norid)) . ' <i class="nori-ui articledel icon-trash"></i></li>';
							
		// 			endforeach;							

		// 			endif;	
		
		// endif;		

		echo '</ul>';

		//Show Form for adding articles

		nori_selectForm();

		echo '</div><!--Nori Wrapper-->';
	endif;
}


//Widget to add articles to selection, i have to clean lots of example code.


add_action( 'widgets_init', 'nori_widget' );


function nori_widget() {
	register_widget( 'Nori_Widget' );
}

class Nori_Widget extends WP_Widget {

	function Nori_Widget() {
		$widget_ops = array( 'classname' => 'nori_widget', 'description' => __('Widget de selección de artículos ', 'nori') );
		
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'nori-widget' );
		
		$this->WP_Widget( 'nori-widget', __('Selector de artículos', 'nori'), $widget_ops, $control_ops );
	}
	
	function widget( $args, $instance ) {
		global $post;
		extract( $args );		
		//Our variables from the widget settings.
		$title = apply_filters('widget_title', __('Selecci&oacute;n de art&iacute;culos') );
		$name = $instance['name'];
		$show_info = isset( $instance['show_info'] ) ? $instance['show_info'] : false;

		echo $before_widget;

		// Display the widget title 
		if ( $title )
			echo $before_title . $title . $after_title;


		nori_centralOps();		
		
		echo $after_widget;
	}

	//Update the widget 
	 
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML 
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['name'] = strip_tags( $new_instance['name'] );		

		return $instance;
	}

	//Crappy stuff, please clean
	function form( $instance ) {

		//Set up some default widget settings.
		$defaults = array( 'title' => __('Selector de articulos', 'nori_widget'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		//Widget Title: Text Input.
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'example'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>		

	<?php
	}
}

function nori_selectForm() {
	global $post;	
	//Adds Form to article selection
		printf('<div class="formwrapper"><br/>');												
					
			if($_GET['norimake'] == 1):				

				printf('<span class="nori-btn btn btn-success" id="generar-ajax" data-articles="'. $_SESSION['articlesel'] .'"><i class="icon-book icon-white"></i> Generar PDF</span>');

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
	$articles = $_POST['articlelist'];
	if($articles):		
		nori_makePdf($articles);
		exit();
	endif;
}

add_action('wp_ajax_ajaxNori', 'ajaxNori');
add_action('wp_ajax_nopriv_ajaxNori', 'ajaxNori');

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
					echo '<li data-id="' . $id .'" id="selarticle-' . $id .'"> <i class="icon-move"></i> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash"></i></li>';
					//print_r($posts);
				endif;
			elseif($posts != $id):
					nori_addPost($id);
					echo '<li data-id="' . $id .'" id="selarticle-' . $id .'"> <i class="icon-move"></i> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash"></i></li>';				
			endif;
			exit();
		break;
		
		case('delete'):
			nori_removePost($id);
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
					echo '<li data-id="' . $id .'" id="selarticle-' . $id .'"> <i class="icon-move"></i> ' . get_the_title(intval($id)) . ' <i class="nori-ui articledel icon-trash"></i></li>';
				endforeach;
			endif;
			exit();

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

		wp_register_script('norijs', NORI_URL . '/js/nori.js', 'jquery-ui');
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

