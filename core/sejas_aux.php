<?php

Class SEJAS_AUX {
    /**
     * $template = path hasta el view
     * $placeholders = array con las sustituciones
     */
    public static function parse($template,$placeholders)
    {
        $resultado = file_get_contents($template.'.html');
        // $tpl = get_template_part($template);

        foreach ($placeholders as $clave => $valor) {
            $resultado = strtr($resultado , array('{{'.$clave.'}}' => $valor));
        }
        $resultado = preg_replace('/{{(.*)}}/','',$resultado);
        return $resultado;
    }
    /**
     * Incluyes todos los ficheros php de un directorio
     */
    public static function includeAll($folderName)
    {   
        foreach (glob(PCERHOME."/$folderName/*.php") as $filename)
        {
            include_once($filename);
        }
    }

    /**
     * Dado un array genera un desplegable en html. Sólo los valores option
     * Si se pasa la variable, $seleccionado, lo marcará para que aparezca como tal.
     */
    public static function generateOptions($arrayOptions, $seleccionado = null)
    {
        $resultado = '';
        $tpl = '<option value="{{clave}}" {{seleccionado}}>{{valor}}</option>';
        foreach ($arrayOptions as $key => $value) {
            $placeholders = array('{{clave}}' => $key, '{{valor}}' => $value, '{{seleccionado}}' => '');
            if ($key == $seleccionado) {
                $placeholders['{{seleccionado}}'] = 'selected="selected"';
            }
            $resultado .= strtr($tpl , $placeholders);
        }
        return $resultado;
    }
}


