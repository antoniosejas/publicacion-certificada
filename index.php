<?php

/*
Plugin Name: Publicación Certificada
Plugin URI: http://publicacion.sejas.es/
Version: 1.0
Author: Antonio Sejas
Author URI: http://antonio.sejas.es/
Description:  This plugin  adds functions and short codes for certificated publication, with timestamp RFC3161
*/
define(PCERHOME, dirname(__FILE__));
include('core/sejas_aux.php');
include('core/pcer_plugin.php');

function pcer_install()
{
  PCER::pcer_install();
}
// En la instalación creamos la tabla de documentos.
register_activation_hook( __FILE__, array('PCER', 'pcer_install' ) );

// CARGAMOS MÁS ELEMENTOS DEL PLUGIN
// Comprobamos si hay que actualizar la base de datos
add_action( 'plugins_loaded', array('PCER', 'loaded' ) );


/**
 * Elimina la palabra private del titulo de las paginas
 */
function pcer_page_title($string) {
$string = str_ireplace("private: ", "", $string);
return $string;
}
add_filter('the_title', 'pcer_page_title');