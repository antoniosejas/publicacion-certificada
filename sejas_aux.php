<?php

Class SEJAS_AUX {
    /**
     * $template = path hasta el view
     * $placeholders = array con las sustituciones
     */
    public static function parse($template,$placeholders)
    {
        $resultado = '';
        $tpl = file_get_contents($template.'.html');
        // $tpl = get_template_part($template);
        foreach ($placeholders as $clave => $valor) {
            $resultado = strtr($tpl , array('{{'.$clave.'}}' => $valor));
        }
        return $resultado;
    }
    /**
     * Incluyes todos los ficheros php de un directorio
     */
    public static function includeAll($folderName)
    {   
        foreach (glob(PCER::directory()."$folderName/*.php") as $filename)
        {
            include_once($filename);
        }
    }
}


