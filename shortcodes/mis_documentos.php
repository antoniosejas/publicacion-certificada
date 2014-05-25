<?php
function pcer_short_mis_documentos ($atts, $content = null)
{
    global $wpdb;
    global $current_user;//Para obtener su ID and email
    global $nombre_usuario;

    get_currentuserinfo();

    $tpl = array(
        'fila' => PCER::views().'shortcodes/mis_documentos_fila'
        ,'tabla'  => PCER::views().'shortcodes/mis_documentos'
    );
    $placeholders = array();
    $placeholders['mis_documentos_fila'] = '';

    $mis_documentos = $wpdb->get_results( "SELECT * FROM ".PCER::tablaDocumentos()." where entidad_id = $current_user->ID and not deleted");

    if (count($mis_documentos)>0) {
        foreach ($mis_documentos as $documento) :
            $placeholdersFila = array(
                'documento_nombre' => $documento->nombre
                ,'nombre_usuario' => $current_user->user_nicename
                ,'documento_url' => $documento->url
                ,'enlace_sello' => $documento->enlace_sello
                ,'documento_fecha_alta' => $documento->fecha_alta
                ,'documento_url_fehaciente' => $documento->url_fehaciente
                ,'enlace_sellos' => $documento->url_fehaciente
            );
            $placeholders['mis_documentos_fila'] .= SEJAS_AUX::parse($tpl['fila'],$placeholdersFila);
         endforeach;//end foreach
    }else{
        $placeholders['mis_documentos_fila'] = '<tr><td colspan="6">Lo siento, no tienes ning&uacute;n documento subido a la plataforma.</td></tr>';
    }

     return SEJAS_AUX::parse($tpl['tabla'],$placeholders);
}
add_shortcode('pcer_'.basename(__FILE__,".php"), 'pcer_short_'.basename(__FILE__,".php"));  


