<?php

Class PCER {
    const my_prefix = "pc_";
    const version = "1.0";
    const version_slug = "pcer_version";
    const paginas_slug = "pcer_paginas";

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
               UNIQUE KEY id (id)
             );";

       require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
       $debug=dbDelta( $sql );
       self::registrarVersion();


       // Creamos si no existen las páginas de mis_documentos, publicar_documento
       $paginasPost = get_option(self::paginas_slug,array());
       var_dump($paginasPost);
       die();
       $paginas = array('Mis Documentos' => 'mis_documentos', 'Subir Documento' => 'subir_documento');

       foreach ($paginas as $titulo => $unaPagina) {
         if (!isset($paginasPost[$unaPagina])) {
            // Create post object
            $my_post = array(
              'post_title'    => $titulo,
              'post_content'  => "[pcer_$unaPagina]",
              'post_type'  => 'page',
              'post_status'   => 'publish',
              'post_author'   => 1
            );
            // Insert the post into the database
            $paginasPost[$unaPagina] = wp_insert_post( $my_post , $error);
            var_dump($error);
            var_dump($paginasPost);
         }// end if no existe mis documentos
       }//end Foreach
       
       // Guardamos id's de las páginas del plugin.
       $debug = update_option(self::paginas_slug, $paginasPost);
       var_dump('self::paginas_slug',self::paginas_slug);
       var_dump($debug);

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

    }
}


