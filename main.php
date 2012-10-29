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
define( 'NORI_FILESPATH', WP_CONTENT_DIR . '/norifiles/');
define( 'NORI_FILESURL', WP_CONTENT_URL . '/norifiles/');
define( 'TCPDF_URL', plugin_dir_url(__FILE__) . '/tcpdf');
define( 'TCPDF_PATH', NORI_LIBS . 'tcpdf/');
define( 'NORI_FONTS', NORI_PATH . 'fonts/');
define( 'NORI_GENFONTS', NORI_FILESPATH . 'tcpdf-fonts/');

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

/*
Session Management
We use sessions for storing article selection based on IDs.
Code from http://devondev.com/simple-session-support/
*/

add_action('init', 'nori_StartSession', 1);
add_action('wp_logout', 'nori_EndSession');
add_action('wp_login', 'nori_EndSession');

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

//Nori central functions for selecting articles, adding pdfs, etc.
function nori_centralOps() {
	echo '<style>
		h3.nori_headtitle {
			font-size:18px
			}
		ul.nori_articlelist li {
			margin-bottom:6px;
			padding:4px;
			background-color:#f0f0f0;
							}
		div.nori_wrapper {
			padding:6px;
			border:1px dotted #ccc;
			margin-bottom:12px;
		}
		.nori_wrapper input {
			margin-bottom:3px;
		}

						</style>	';
	echo '<div class="nori_wrapper">';
	// Script start
	$rustart = getrusage();

	if(isset($_POST['delete'])):
			nori_EndSession();	
	//Display the stored data
	elseif(isset($_POST['generar']) && isset($_SESSION['articlesel'])):
		//Set the articles

		$article_selection = $_SESSION['articlesel'];

		nori_makePdf($article_selection);

			//TIME CALCULATIONS
			$ru = getrusage();
			echo '<p class="nori_usage">';
			echo "Este proceso utilizó " . rutime($ru, $rustart, "utime") .
		    " ms para sus cálculos\n";
			echo "Gastó " . rutime($ru, $rustart, "stime") .
		    " en llamadas de sistema\n";
		    echo '</p>';

	//Add articles to session
	elseif(isset($_POST['submit']) || isset($_SESSION['articlesel']) || !isset($_POST['delete'])):

			if(isset($_POST['articleid']) && isset($_POST['submit'])):
				nori_addPost($_POST['articleid']);
			endif;
				$artids = nori_SessionGet('articlesel');
				if($artids):
					$norids = explode(',', $artids);
					printf(
						'<h3 class="nori_headtitle">Artículos seleccionados:</h3>'
						);
					echo '<ul class="nori_articlelist">';
					foreach($norids as $norid):
						printf(
							'<li> &bull; ' . get_the_title(intval($norid)) . ' <button class="remove" value="X" title="Quitar artículo">x</button></li>'
						);	
				endforeach;
			echo '</ul>';
			endif;
		endif;		

	//Show Form for adding articles

	nori_selectForm();
	echo '</div><!--Nori Wrapper-->';
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
		printf('<div class="formwrapper">
				<br/>
				<form id="selart" action="" method="POST">
					<input type="hidden" name="articleid" data-extra="'.$post->post_title.'" value="'.$post->ID.'"/>
					<input type="submit" value="Añadir artículo" id="submit" name="submit"/>					
					<input type="submit" value="Generar PDF" id="pdfgen" name="generar"/>
					<input type="submit" value="Vaciar selección" id="delete" name="delete"/>
				</form>
				<br/>
				</div>'
				);		
	}

//Calcular uso de tiempo
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

//Trabajar por directorios
function getFullPath($url){
return realpath(str_replace(get_bloginfo('url'), '.', $url));
}