<?php

Class PCER {
    const my_prefix = "pc_";
    const version = "1.0";
    const version_slug = "pcer_version";
    const paginas_slug = "pcer_paginas";
    const tsaurl_slug = "pcer_tsaurl";
    const tsaurl_default = "http://zeitstempel.dfn.de";

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
        ,'Ver sellos' => 'ver_sellos'
      );
    }

    /**
     * Devielve el array de paginas creadas en base de datos
     */
    public static function get_option_paginas()
    {
      return get_option(self::paginas_slug,array());
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
      return PCERHOME.'/';
    }
    /**
     * Devuelve el path absoluto hasta la carpeta views del plugin
     */
    public static function views()
    {
      return PCERHOME.'/views/';
    }
    public static function tablaDocumentos()
    {
        return self::prefix(). "documentos"; 
    }
    public static function tablaSellos()
    {
        return self::prefix(). "sellos"; 
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
        $todoOk = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
        if (!$todoOk) {
          $texto = "Error al guardar documento: ".var_export($parametros,true)."\n";
          $texto .= "SQLERROR: ".$wpdb->last_error;
          self::notificarError($id_documento,$texto);
        }
        return $todoOk;
    }
    /**
      *
      */
    public static function getDocuments($where = '1=1')
    {
        global $wpdb;
        $table_name = self::tablaDocumentos();
        $resultados = $wpdb->get_results(
          mysql_real_escape_string("SELECT * FROM $table_name WHERE $where")
          );
        return $resultados;
    }
    /**
     *
     */
    public static function addSello($id_documento, $fecha, $sha1, $sello)
    {
        global $wpdb;
        $table_name = self::tablaSellos();
        $parametros = array( 'id_documento' => $id_documento, 'fecha' => $fecha, 'fecha_creacion' => current_time('mysql'), 'hash_documento_sha1' => $sha1, 'timestamp' => $sello, 'deleted' => 0 );
        $todoOk = $wpdb->insert( $table_name, $parametros );
        if (!$todoOk) {
          $texto = "Error al guardar Sello: ".var_export($parametros,true)."\n";
          $texto .= "SQLERROR: ".$wpdb->last_error;
          self::notificarError($id_documento,$texto);
        }
        return $todoOk;
    }
    /**
     *
     */
    public static function getSellos($id_documento)
    {
        global $wpdb;
        $table_name = self::tablaSellos();
        $resultados = $wpdb->get_results(
          mysql_real_escape_string("SELECT * FROM $table_name WHERE id_documento = $id_documento")
          );
        return $resultados;
    }
    /**
      *
      */
    public static function notificarError($id_documento, $texto)
    {
      var_export($texto);
      $headers = '';
      wp_mail( get_option('admin_email'), "[Publicacion Certificada] Error con documento $id_documento", $texto, $headers );
    }
    /**
      *
      */
    public static function downloadDocument($url)
    {
      $documento_descargado = file_get_contents($url);
      //TODO: posible mejora, descargarse el documento mediante curl, y sellar también las cabeceras
      return $documento_descargado;
    }
    /**
     * 
     */
    public static function pcer_install () {
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      //Actualizamos 
      self::setTsaUrl(self::tsaurl_default);

      // Creamos/Actualizamos la base de datos
      //Tabla Documentos
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

       $debug = dbDelta( $sql );
       
       //Tabla sellos
       $sql = "CREATE TABLE " . self::tablaSellos() . " (
               id bigint(20) NOT NULL AUTO_INCREMENT,
               id_documento bigint(20) NOT NULL,
               fecha timestamp NOT NULL ,
               fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               hash_documento_sha1 varchar(40) COLLATE utf8_spanish_ci NOT NULL,
               timestamp text COLLATE utf8_spanish_ci,
               deleted tinyint(1) NOT NULL DEFAULT '0',
               PRIMARY KEY id (id)
             );";

       $debug = dbDelta( $sql );
       self::registrarVersion();


       // Creamos si no existen las páginas de mis_documentos, publicar_documento
       $paginasPost = self::get_option_paginas();

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
              ,'ping_status'  => 'closed'
            );
            if ($unaPagina != 'buscar_documentos') {
              $my_post['post_status'] = 'private';
            }
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

        SEJAS_AUX::includeAll("controllers");
        
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
    public static function pcer_add_page_menu($output) {
    $paginas = PCER::get_option_paginas();
    foreach ($paginas as $slug => $pagina_id) {
     if ('buscar_documentos' != $slug && 'ver_sellos' != $slug) {
      $clase = (get_the_ID() == $pagina_id)?'current_page_item':'';
       $output .= '<li class="'.$clase.'"><a href="'.get_permalink($pagina_id).'">'.get_the_title($pagina_id).'</a></li>'; 
     }
    }  
    return $output;
  }
    /**
     * Registra y guarda todos los items en el menú
     */
    public static function menus_register($value='')
    {
      if( is_user_logged_in() ) {
        // Sólo añadimos las páginas si el usuario está logueado.
        add_filter('wp_list_pages', array('PCER', 'pcer_add_page_menu'));
      }

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

    public static function setTsaUrl($urlTsa)
    {
      echo "actualizando tsaurl: $urlTsa";
      update_option(self::tsaurl_slug,  $urlTsa);
    }
    /**
    * Devuelve la variable de la tabla options, si el campo está vacío, devuelve el valor por defecto.
    **/
    public static function getTsaUrl()
    {
      $tsa = get_option(self::tsaurl_slug);
      return (""==$tsa)?self::tsaurl_default:$tsa;
    }
}


