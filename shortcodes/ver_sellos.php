<?php
function pcer_short_ver_sellos ($atts, $content = null)
{
    $resultado = "";

    if (isset($_REQUEST['doc_id']) && is_numeric($_REQUEST['doc_id'])) {
        $sellos = PCER::getSellos($_REQUEST['doc_id']);
        $placeholders['ver_sellos_fila'] = "";
        foreach ($sellos as $unSello) {
            $placeholdersAux = array(
                'id' => $unSello->id
                ,'fecha' => $unSello->fecha
                ,'hash' => $unSello->hash_documento_sha1
                ,'sello' => $unSello->ver_sellos_fila
            );
            $placeholders['ver_sellos_fila'] .= SEJAS_AUX::parse(PCER::views().'shortcodes/ver_sellos_fila',$placeholdersAux);
        }
        $placeholders['ver_sellos_fila'] = (""==$placeholders['ver_sellos_fila'])?"El documento no tiene sellos asociados.":$placeholders['ver_sellos_fila'];

        $tpl = PCER::views().'shortcodes/ver_sellos';
        $resultado = SEJAS_AUX::parse($tpl,$placeholders);
    }else{
        //TODO: Notificar error
        $resultado = "No se encontraon sellos asociados al documento indicado";
    }
    return $resultado;
}
add_shortcode('pcer_'.basename(__FILE__,".php"), 'pcer_short_'.basename(__FILE__,".php"));  
