<?php

/*
Plugin Name: Publicación Certificada
Plugin URI: http://publicacion.sejas.es/
Version: 1.0
Author: Antonio Sejas
Description:  This plugin  adds functions and short codes for certificated publication, with timestamp RFC3161
*/
include('pcer_plugin.php');
include('sejas_aux.php');
function pcer_install()
{
  PCER::pcer_install();
}
// En la instalación creamos la tabla de documentos.
register_activation_hook( __FILE__, array('PCER', 'pcer_install' ) );

// CARGAMOS MÁS ELEMENTOS DEL PLUGIN
// Comprobamos si hay que actualizar la base de datos
add_action( 'plugins_loaded', array('PCER', 'loaded' ) );


if ( !function_exists('fb_add_page_link') ) {
  function fb_add_page_link($output) {
    $paginas = PCER::get_option_paginas();
    foreach ($paginas as $slug => $pagina_id) {
     if ('buscar_documentos' != $slug) {
       $output .= '<li><a href="'.get_permalink($pagina_id).'">'.get_the_title($pagina_id).'</a></li>'; 
     }
    }  
    return $output;
  }
  
  add_filter('wp_list_pages', 'fb_add_page_link');
  // add_filter('wp_page_menu', 'fb_add_page_link');

}