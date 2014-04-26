<?php
function pcer_short_buscar_documentos ($atts, $content = null)
{
    global $wpdb;
    global $current_user;//Para obtener su ID and email
    global $nombre_usuario;
    $tpl = array(
        'fila' => PCER::views().'shortcodes/buscar_documentos_fila'
        ,'tabla'  => PCER::views().'shortcodes/buscar_documentos'
    );
    $placeholders = array();

    // Listado usuarios entidades
    
    foreach ($variable as $key => $value) {
        # code...
    }
    $placeholders['option_usuarios'] = SEJAS_AUX::generateOptions();;

    // Búsqueda documentos
    // Aconsejable poner un salt.
    $usuairo_id=($usuairo_id==0)?'%':($usuairo_id);
    $nombre_documento=($nombre_documento=='')?'%':$nombre_documento;//Si el campo es vacío poenmos un %
    $mes=($mes=='')?'%':$mes;//Si el campo es vacío poenmos un %
    $ano=($ano=='')?'%':$ano;//Si el campo es vacío poenmos un %

    $buscar_documentos = $wpdb->get_results( "SELECT * FROM {PCER::tablaDocumentos()} where nombre like '%$nombre_documento%' and entidad_id = '$usuairo_id' and NOT deleted");

    var_dump('buscar_documentos');
    var_dump($buscar_documentos);

    $placeholders['buscar_documentos_fila'] = '';
    if (count($buscar_documentos)>0) {
        foreach ($buscar_documentos as $documento) :
            $placeholdersFila = array(
                'documento_nombre' => $documento->nombre
                ,'nombre_usuario' => $documento->usuairo_id
                ,'documento_url' => $documento->url
                ,'enlace_sello' => $documento->enlace_sello
                ,'documento_fecha_alta' => $documento->fecha_alta
                ,'documento_url_fehaciente' => $documento->url_fehaciente
            );
            $placeholders['buscar_documentos_fila'] .= SEJAS_AUX::parse($tpl['fila'],$placeholdersFila);
         endforeach;//end foreach
    }else{
        $placeholders['buscar_documentos_fila'] = '<tr><td colspan="6">Lo siento, no hay documentos que coincidan con tu búsqueda.</td></tr>';
    }
     return SEJAS_AUX::parse($tpl['tabla'],$placeholders);
}
add_shortcode('pcer_'.basename(__FILE__,".php"), 'pcer_short_'.basename(__FILE__,".php"));  


