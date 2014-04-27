<?php

Class PCER {
    const my_prefix = "pc_";
    const version = "1.1";
    const version_slug = "pcer_version";
    const paginas_slug = "pcer_paginas";

    /**
     * Devuelve las páginas que se crearán y guardarán en options
     * Estas son necesarias para cumplir con las funcionalidades de una publicación certificada.
     */
    private static function paginas()
    {
      // Cada una de las páginas tiene asociado un shortcode en la carpeta shortcodes del siguiente [tipo pcer_short_{{slug}}]
      return array(
        //titulo => // Slug
         'Mis Documentos' => 'mis_documentos' // Permite
        ,'Subir Documento' => 'subir_documento'
        ,'Buscar Documentos' => 'buscar_documentos'
      );
    }

    private static function prefix()
    {
        global $wpdb;
        return $wpdb->prefix .self::my_prefix;
    }
    /**
     * Devuelve el directorio absoluto del plugin.
     */
    public static function directory()
    {
      return dirname(__FILE__).'/';
    }
    /**
     * Devuelve el path absoluto hasta la carpeta views del plugin
     */
    public static function views()
    {
      return dirname(__FILE__).'/views/';
    }
    public static function tablaDocumentos()
    {
        return self::prefix(). "documentos"; 
    }
    public static function registrarVersion()
    {
        update_option( self::version_slug, self::version );
    }
    /**
     * 
     */
    public static function addDocument($parametros)
    {
        global $wpdb;
        $table_name = self::tablaDocumentos();
        $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
    }
    /**
     * 
     */
    public static function pcer_install () {
      // Creamos/Actualizamos la base de datos
       $table_name = self::tablaDocumentos();
       $sql = "CREATE TABLE " . $table_name . " (
               id bigint(20) NOT NULL AUTO_INCREMENT,
               entidad_id bigint(20) NOT NULL,
               csv varchar(100) COLLATE utf8_spanish_ci NOT NULL,
               url_md5 varchar(32) COLLATE utf8_spanish_ci NOT NULL,
               url varchar(512) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
               url_fehaciente varchar(512) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
               path varchar(512) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
               home varchar(512) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
               nombre varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
               fecha_alta timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               fecha_baja timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
               ip varchar(64) COLLATE utf8_spanish_ci NOT NULL,
               timestamp text COLLATE utf8_spanish_ci,
               hash_documento_sha1 varchar(40) COLLATE utf8_spanish_ci NOT NULL,
               deleted tinyint(1) NOT NULL DEFAULT '0',
               versiones text COLLATE utf8_spanish_ci,
               PRIMARY KEY id (id),
               UNIQUE KEY csv (csv)
             );";

       require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
       $debug=dbDelta( $sql );
       self::registrarVersion();


       // Creamos si no existen las páginas de mis_documentos, publicar_documento
       $paginasPost = get_option(self::paginas_slug,array());

       $paginas = self::paginas();

       foreach ($paginas as $titulo => $unaPagina) {
         // Si la página guardada no está en options o no existe (porque el usuario la ha borrado)
         if (!isset($paginasPost[$unaPagina]) || !get_page($paginasPost[$unaPagina]) ) {
            // creamos la página con el shortcode como contenido.
            $my_post = array(
               'post_title'      => $titulo
              ,'post_content'    => "[pcer_$unaPagina]"
              ,'post_type'       => 'page'
              ,'post_status'     => 'publish'
              ,'post_author'     => 1
              ,'comment_status'  => 'closed'
            );
            // Insert the post into the database
            $paginasPost[$unaPagina] = wp_insert_post( $my_post , $error);
         }// end if no existe mis documentos
       }//end Foreach
       // Guardamos id's de las páginas del plugin.
       $debug = update_option(self::paginas_slug, $paginasPost);
    }
    /**
     * 
     */
    public static function pcer_update_db_check() {
        if (get_site_option( self::version_slug ) != self::version) {
           self::pcer_install();
        }
    }
    /**
     * 
     */
    public static function loaded() {
        // Chequeamos si la versión de la base de datos es la misma versión.
        self::pcer_update_db_check();

        // Incluimos todos los shortcodes
        // Para ver los shortcodes públicos, ver README.md
        SEJAS_AUX::includeAll("shortcodes");
        
        // Menús
        self::menus_register();

    }
    /**
     * Menú logueado, filter en loaded.
     */
    public static function pcer_nav_menu_args( $args = '' ) {         
      if( is_user_logged_in() ) {
          $args['menu'] = 'pc_logged';
      } else {
         // $args['menu'] = 'logged-out';
      }
      return $args;
    }

    /**
     * Registra y guarda todos los items en el menú
     */
    public static function menus_register($value='')
    {

      // // Menús
      // if ( has_nav_menu( 'primary' ) ) {
      //      register_nav_menu( 'primary', 'Primary Navigation' );
      // }
      // register_nav_menu( 'pc_loggedin' , 'Usuarios logueados');

      // // si está logueado cambiamos el menú principal 
      // add_filter( 'wp_nav_menu_args', array('PCER', 'pcer_nav_menu_args') );

      // $menu_name = 'pc_logged';
      // // Si no existe el menú 'pc_loggedin'
      // $menu_exists = wp_get_nav_menu_object( $menu_name );
      // // Entonces lo creamos, y guardamos las páginas correspondientes
      // if( !$menu_exists){
      //     $menu_id = wp_create_nav_menu($menu_name);
      //   // Set up default menu items
      //     wp_update_nav_menu_item($menu_id, 0, array(
      //         'menu-item-title' =>  __('Home'),
      //         'menu-item-classes' => 'home',
      //         'menu-item-url' => home_url( '/' ), 
      //         'menu-item-status' => 'publish'));
      // }
    }

    /**
     * Devuelve el md5 de $id + un salt que fue genreado al instalar el plugin.
     *
     */
    public static function dameCSV($id)
    {
      return md5($id.'variable');
    }
}


