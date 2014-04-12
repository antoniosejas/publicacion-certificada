<?php
function pcer_short_subir_documento ($atts, $content = null)
{
    global $wpdb;
    global $current_user;//Para obtener su ID y 
    global $_dir_fehaciente;
    global $_path_archivo;
    set_time_limit(1800);//Tiempo limite de ejecucion en segundos, si es 0 no tiene restriccion
    $resultado = '';

    if ($_SERVER['REQUEST_METHOD']=='GET'){ //Via Capitulo 9.12.2 cookbook php.
     $resultado .= display_form(array(),array());
    } else{
        if ($_SERVER['REQUEST_METHOD']=='POST'){// Comprobamos que el formulario es enviado y la cookie
            $errores_y_clean=validate_form();
            $errores=$errores_y_clean[0];
            $clean=$errores_y_clean[1];
            if (!empty($errores)){
                $resultado .= '<h2 class="error">Error al descargar Documento.<br />Revise los siguientes campos</h2>';  
                $resultado .= display_form($errores,$clean);
            }else{
                //--
                //Procedemos a la descarga
                $existia_documento=$wpdb->get_results( "SELECT * FROM documentos where documentos.entidad_id = $current_user->ID and not documentos.deleted and documentos.url='".$clean['r_web_documento']."'");
                 //DEBUG:var_dump($existia_documento);
                if(empty($existia_documento)) {//el documento no existe -> intentamos copiarlo
                $ultimo_id=$wpdb->get_var('SELECT id FROM documentos ORDER BY id DESC LIMIT 1');// OJO, se supone que en la Base de datos no se borra a pelo, simplemente se activa el bit 'deleted'
                    $nombre_documento=date('Y-m-d').strtr('-'.strtolower($clean['r_nombre']).'-',array(' '=>'-')).++$ultimo_id.'.pdf';
                    if(copiar_documento($clean['r_web_documento'],$nombre_documento)){
                    //calculamos el md5 del archivo para guardarlo en la base de datos (y no tener que calcularlo cada día). y para pedir el sello de tiempo.
                    $hash_documento_sha1=sha1_file($_path_archivo);
                    $timestamp='™€…Ó8>:/´^·ÐjHy©ÚÐ';//AQUÍ!! HABRÍA QUE SELLAR !! .....(MIRAR sellar_documento.php.)
                    //Insertamos en base de datos
                    //OK
                    $wpdb->show_errors();//DEBUG:
                    if (false === $wpdb->insert(PCER::tablaDocumentos(),
                                                    array('url_md5' => md5($clean['r_web_documento'])
                                                        ,'url' => $clean['r_web_documento'] 
                                                        ,'url_fehaciente' => $_dir_fehaciente
                                                        ,'home' => $clean['r_home']
                                                        ,'nombre' => $clean['r_nombre']
                                                        ,'fecha_alta' => date('Y-m-d H:i:s')
                                                        ,'entidad_id' => $current_user->ID
                                                        ,'path' => $_path_archivo
                                                        ,'timestamp' => $timestamp
                                                        ,'hash_documento_sha1' => $hash_documento_sha1
                                                        ,'deleted' => 0)) ){
                                                                                                                //La direccion fehaciente la ha completado  copiar_documento
                    $resultado .= '<h2>ERROR Interno Sentencia sql</h2>';
                    }else{
                    //OK, el documento fue insertado
                    $resultado .= '<h2>Documento añadido correctamente.</h2>'.'Su url es la siguiente: <a href="'.$_dir_fehaciente.'" target="_blank">'.$_dir_fehaciente.'</a>';                             
                    }//end if error en sentencia sql

                    }else{
                    $resultado .= '<h2>Error en la copia, vuelva a intentarlo</h2>';                             
                    }//end if, copia correcta.
                }else{
                $resultado .= '<h2>ERROR: Documento ya existe.</h2>';        
                }//end if existia documento
            }//end if; count(errores)
        }else{//Mostramos error acceso invalido (estan automatizando el formulario.
            $resultado .= 'ERROR: SD1.';
        }//end if; Nos llaman de otro formulario que no es el nuestro, ya que falta el input hidden name token; o el navegador no soporta cookies
    }//end if metodo get


     return $resultado;
}
add_shortcode('pcer_'.basename(__FILE__,".php"), 'pcer_short_'.basename(__FILE__,".php"));  



// AUX Formulario
function validate_form(){
    $errores=array();
    //Limpiamos la variable POST
    $clean=array();
    $clean['r_home']=($_POST['r_home']=='http://')?'':$_POST['r_home'];//Deberiamos usar htmlentities??
    $clean['r_web_documento']=($_POST['r_web_documento']=='http://')?'':$_POST['r_web_documento'];
    $clean['r_nombre']=htmlentities($_POST['r_nombre']);
    // Rellenamos un array con los datos que queramos añadir
    
    //Secuencias de ifs comprobando la sintaxis de los datos:
    $spani='<b class="error">';
    $spanf='</b>';
    
    //Comprobacion url:
    if ($clean['r_web_documento']!=''){
        $resultado_comprobar_url_pdf=comprobar_url_pdf($clean['r_web_documento']);
        if (!$resultado_comprobar_url_pdf[0]){
            $errores['r_web_documento']=$spani.$resultado_comprobar_url_pdf[1].$spanf;
        }else{
            //OK, el servidor dice que es aplication/pdf y la primera linea de fichero dice que es un PDF.
            //$errores['r_web_documento']='URL OK.';//BORRAR
        }//end if comprobar_url_pdf;
    }//end if, la web no es vacia
        
    
    //TODO:comprobacion nif


    foreach ($clean as $clean_key => $clean_val){
        if ($clean[$clean_key]==''){ $errores[$clean_key]=$spani.'Rellene este campo'.$spanf;}
        if (strpos($clean[$clean_key],'&gt;')){ $errores[$clean_key]=$spani.'Prohibido el caracter &#62;'.$spanf;}
        if (strpos($clean[$clean_key],'&lt;')){ $errores[$clean_key]=$spani.'Prohibido el caracter &#60;'.$spanf;}
    }
    
    return array($errores,$clean);
}//end function validate_form

function display_form($errores,$clean){
    $placeholders = array();
    foreach ($errores as $key => $value) {
        $placeholders['Error_'.$key] = $value;
    }
    foreach ($clean as $key => $value) {
        $placeholders[$key] = $value;
    }
    $tpl = PCER::views().'shortcodes/subir_documento';

    return SEJAS_AUX::parse($tpl,$placeholders);
}//end function display_form;

// AUX COPIAR DOCUMENTO
/**
 * 
 */
function copiar_documento_aux($url,$destino){ //Devuelve errores vacio si todo ha ido bien.
global $_path_archivo;
$errores='';
    if(!@copy($url,$destino)){
        $errores= error_get_last();
        $errores=$errores['message'].' ';
        //DEBUG:echo "COPY ERROR: ".$errors['type'];
        //DEBUG:echo "<br />\n".$errors['message'];
    } else {
        $_path_archivo=realpath($destino);//actualizamos el path del archivo para calcular el md5, y guardarlo en la BBDD// Solo tiene valor si la copia ha sido exitosa.
        $errores='';
    }
    return $errores;
}//end copiar_documento_aux

/**
 * 
 */
function copiar_documento($url,$nombre_documento){
    global $current_user;
    global $nombre_usuario;//Es elegido en el header.
    global $_dir_fehaciente;
    $nombre_usuario=strtr($nombre_usuario,array(' '=>'-'));
    $res=false;
    //si no existe la carpeta de la entidad -> la creamos
    $nombre_carpeta = "documentos/".$nombre_usuario.'/';
    if(!is_dir($nombre_carpeta)){
        @mkdir($nombre_carpeta, 0755);
    }
    $error=copiar_documento_aux($url,$nombre_carpeta.$nombre_documento);
    if (''==$error){//La copia se ha realizado correctamente.
        $_dir_fehaciente.=$nombre_carpeta.$nombre_documento;
        echo 'COPIA CORRECTA';//DEBUG:
        $res=true;
    }else{
        $res=false;
        $error;//el error ha sido devuelto al copiar_documento();
        echo 'ERROR: Copiar Documento'.$error; //Error
    }//end if;
    return $res;
}//end copiar_documento.

/**
 * 
 */
function comprobar_url_pdf($url){//return boolean
 $res=false;//devolvemos este resultado, true si existe el documento y es un pdf.
 $error='';//especifica el error de la url
            if (! $url_info = parse_url ( $url )) {
                return false;
            }
            switch ($url_info ['scheme']) {
                case 'https' :
                    $scheme = 'ssl://';
                    $port = 443;
                    break;
                case 'http' :
                default :
                    $scheme = '';
                    $port = 80;
            }            
            $data = "";
            $fid = fsockopen ( $scheme . $url_info ['host'], $port, $errno, $errstr, 30 );
            if ($fid) {
                fputs ( $fid, 'HEAD ' . (isset ( $url_info ['path'] ) ? $url_info ['path'] : '/') . (isset ( $url_info ['query'] ) ? '?' . $url_info ['query'] : '') . " HTTP/1.0\r\n" . "Connection: close\r\n" . 'Host: ' . $url_info ['host'] . "\r\n\r\n" );
                while ( ! feof ( $fid ) ) {
                    $data .= fgets ( $fid, 128 );
                }
                
                //OK
                //OKIMPORTANTE//var_dump($data);//DEBUG: info sobre
                if (strpos($data,'application/pdf')>0){//Comprobamos que el documento EXISTE Y es un PDF.
                    fclose ( $fid );
                    $fid = fopen($url,'r');//fsockopen ( $scheme . $url_info ['host'], $port, $errno, $errstr, 30 );
                    //fputs ( $fid, 'GET ' . (isset ( $url_info ['path'] ) ? $url_info ['path'] : '/') . (isset ( $url_info ['query'] ) ? '?' . $url_info ['query'] : '') . " HTTP/1.0\r\n" . "Connection: close\r\n" . 'Host: ' . $url_info ['host'] . "\r\n\r\n" );
                    $primera_linea=fgets ($fid);
                    if (strpos($primera_linea,'PDF')>0){//Si en la primera linea parece la version del pdf, emepzamos la descarga. e.o.c. No es un pdf
                        $res=true;
                    }else{//No es un pdf
                        $res=false;
                        $error='El documento no es un pdf.';
                    }
                }else{
                    $res=false;
                    $error=(strpos($data,'Location:'))?'La url es una redirección':'El Documento no es un pdf';
                 }//end if, Es pdf?
                 
                 //fputs( $fid, "GET /" . $usepath . " HTTP/1.1\r\n" );//via: http://foros.hackerss.com/lofiversion/index.php/t1673.html
                 //fputs( $fid, "Host: " . $host . "\r\n"); 
                 fclose ( $fid );
                 
            } else {
                $error= 'Problemas al conectar con el Servidor. Por favor revise la url:'.htmlentities($url);
                $error.=$errstr;//DEBUG:
                $res= false;
            }
            return array($res,$error);
}//end comprobar_url_pdf;

