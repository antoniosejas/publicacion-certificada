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
