<?php

Class PCER {
    const my_prefix = "pc_";
    const version = "1.0";
    const version_slug = "pcer_version";

    private static function prefix()
    {
        global $wpdb;
        return $wpdb->prefix .self::my_prefix;
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
       var_dump($debug);
       self::registrarVersion();
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

}


