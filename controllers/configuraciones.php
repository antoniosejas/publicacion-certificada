<?php

PCERConfiguraciones::run();

class PCERConfiguraciones{
	const plugin_name="Configuraciones";
	const plugin_name_slug="configuraciones";

	// INICIO
	/*
	Main function of the plugin
	*/

	public static function run(){
		self::configuraciones_register_head_elements_admin();
		self::configuraciones_register_head_elements();
	}

	public static function configuraciones_register_head_elements_admin(){
		if(is_admin() ){
			$src = self::plugin_url() . 'js/configuraciones-admin.js';
			wp_register_script( 'configuraciones', $src );
			wp_enqueue_script( 'configuraciones' );

			//ADMIN PANEL
			add_action('admin_menu', array('PCERConfiguraciones', 'configuraciones_add_admin_menu') );
		}
	}


	public static function configuraciones_register_head_elements(){

	}


	public static function configuraciones_add_admin_menu(){

		if (function_exists('add_options_page')) {
			//add_menu_page
			$page_title = "Publicaci&oacute;n Certificada";
			$menu_title = "Publicaci&oacute;n Certificada";
			$capability=8;
			add_options_page($page_title, $menu_title, $capability, basename(__FILE__), array('PCERConfiguraciones', 'configuraciones_admin_panel' ));
		}

		// Sección propia
		// CSS
		add_action( 'admin_head', array('PCERConfiguraciones','admin_css') );
		// Menú
		add_menu_page('Publicaci&oacute;n Certificada', 'Publicaci&oacute;n Certificada', 'manage_options', 
		    'rs_theme_settings', array('PCERConfiguraciones','configuracion_general'), self::plugin_url().'images/logo-publicacion-certificada-50.png');
		
		// add_submenu_page('pcer_theme_settings', 
		//     'Galerías', 'Galerías', 'manage_options', 
		//     'pcer_theme_settings_galerias', array('PCERConfiguraciones','admin_galerias')); 

	}
	public static function admin_css() {
		wp_enqueue_style( 'style-admin', self::plugin_url() . 'css/style-pcer.css' );
	}
	// Sección admin Rolling Stone
	public static function configuracion_general() {
	    if (!current_user_can('manage_options')) {
	        wp_die('You do not have sufficient permissions to access this page.');
	    }
	    if (isset($_POST['envio'])) {
	    	
	    }
	    echo file_get_contents(self::plugin_path().'configuracion_general.html');
	}

	public static function plugin_url() {
		return plugins_url().'/publicacion-certificada/views/configuracion/';
	}
	public static function plugin_path() {
		return PCER::views().'configuracion/';
	}
}
