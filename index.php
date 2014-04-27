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


add_filter( 'nav_menu_link_attributes', 'filter_function_name' );

function filter_function_name( $atts, $item, $args ) {
    // Manipulate attributes

  var_dump('$atts');
  var_dump($atts);
    return $atts;
}

function wpse31748_exclude_menu_items( $items, $menu, $args ) {

  var_dump('wpse31748_exclude_menu_items');
    // Iterate over the items to search and destroy
    foreach ( $items as $key => $item ) {
      var_dump($item);
        if ( $item->object_id == 168 ) unset( $items[$key] );
    }

    return $items;
}
add_filter( 'wp_get_nav_menu_items', 'wpse31748_exclude_menu_items', null, 3 );


add_filter( 'wp_nav_menu_items', 'your_custom_menu_item', 10, 2 );
add_filter( 'wp_page_menu_items', 'your_custom_menu_item', 10, 2 );
function your_custom_menu_item ( $items, $args ) {
  var_dump('your_custom_menu_item');
    if (is_single() && $args->theme_location == 'primary') {
        $items .= '<li>Show whatever</li>';
    }
    return $items;
}




  add_filter( 'wp_page_menu_args', 'my_page_menu_args' );
  function my_page_menu_args( $args ) {
    var_dump('my_page_menu_args');
    var_dump($args);
    $args = array(
      'show_home' => 'Blog'
    );

    return $args;
  }