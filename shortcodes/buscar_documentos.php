<?php
function pcer_short_buscar_documentos ($atts, $content = null)
{
    global $wpdb;
    global $current_user;//Para obtener su ID and email
    global $nombre_usuario;
    $placeholders = array();
    // 
        if (isset($_POST['form_id']) && $_POST['form_id'] == 1097821 ) {
            $usuario_id = esc_sql($_POST['r_nombre_entidad']);
            $nombre_documento = esc_sql($_POST['r_nombre_documento']);
            $fecha = esc_sql($_POST['r_fecha']);

            // Ponemos los values de los inputs
            $placeholders['r_nombre_documento'] = esc_attr($_POST['r_nombre_documento']);
            $placeholders['r_fecha'] = esc_attr($_POST['r_fecha']);
        }
    // 
    $tpl = array(
        'fila' => PCER::views().'shortcodes/buscar_documentos_fila'
        ,'tabla'  => PCER::views().'shortcodes/buscar_documentos'
    );

    // Listado usuarios entidades
    // $args = array('role' => 'subscriber'); // podemos limitarlos a los "suscribers"
    $usuarios = get_users();
    $usuarios_options = array('0' => 'Todos los propietarios');
    foreach ($usuarios as $unUsuario) {
       $usuarios_options[$unUsuario->ID] = $unUsuario->user_nicename;
    }
    $placeholders['option_usuarios'] = SEJAS_AUX::generateOptions($usuarios_options,$usuario_id);;

    // Búsqueda documentos
    // Aconsejable poner un salt.
    $usuario_id =($usuario_id == 0 || $usuario_id == null )?'%':($usuario_id);
    $nombre_documento=($nombre_documento == '' || $nombre_documento == null )?'%':$nombre_documento;//Si el campo es vacío poenmos un %
    $fecha=($fecha == '' || $fecha == null )?'%':$fecha;//Si el campo es vacío poenmos un %

    $andEntidad = '';
    if ('%' !=  $usuario_id) {
        $andEntidad = "and entidad_id = '$usuario_id'";
    }   
    $andFecha = '';
    if ('%' !=  $fecha) {
        $andFecha = "and fecha_alta >= '$fecha'";
    } 

    $sql = "SELECT * FROM ".PCER::tablaDocumentos()." where nombre like '%$nombre_documento%' $andEntidad $andFecha and NOT deleted";
    var_dump($sql);
    $buscar_documentos = $wpdb->get_results($sql);

    // Ponemos toda la lista de archivos
    $placeholders['buscar_documentos_fila'] = '';
    if (count($buscar_documentos)>0) {
        foreach ($buscar_documentos as $documento) :
            $placeholdersFila = array(
                'documento_nombre' => $documento->nombre
                ,'nombre_usuario' => $documento->entidad_id
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


