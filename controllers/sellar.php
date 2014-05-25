<?php

/**
  * Cada vez que se visita esta página realiza un sellado a todos los documentos cuya fecha de finalización todavía no han terminado.
  * Esta página debe tener programado un cron.
  *
  * Este documento se pude llamar directamente desde fuera del wordpress, y también se ejecuta al incluir pcer_plugin.php
  **/

if (isset($_REQUEST['sellar_documentos'])) {
	require_once(dirname(__FILE__).'/../3161/timestamp.php');
	require_once(dirname(__FILE__).'/../../../../wp-config.php');

	$documentos_a_sellar = PCER::getDocuments("fecha_alta < NOW() AND fecha_baja > NOW()");

	foreach ($documentos_a_sellar as $unDocumento) { 
		$documento_descargado = PCER::downloadDocument($unDocumento->url);
		//Realiza 3 intentos durante la descarga.
		$intentos = 3;
		$i = 0;
		$sha1DocumentoDescargado = sha1($documento_descargado);
		while ($intentos < $intentos && $sha1DocumentoDescargado != $unDocumento->hash_documento_sha1) {
			$documento_descargado = file_get_contents($unDocumento->url);
			$sha1DocumentoDescargado = sha1($documento_descargado);
			$i++;
		}
		
		if ($unDocumento->hash_documento_sha1 == $sha1DocumentoDescargado ) {
			//Todo ok
		}else{
			//Notificar error
			$texto = "Los hashes no coinciden:\n";
			$texto .= "Documento descargado: ".$sha1DocumentoDescargado."\n";
			$texto .= "Sha1 primer sello: ".$unDocumento->hash_documento_sha1 ."\n";
			$texto .= "SHA1 Copia local:".sha1_file($unDocumento->path)."\n";
			PCER::notificarERROR($unDocumento->id,$texto);
		}
		
		$selloArray = Timestamp::getTimestampArray(PCER::getTsaUrl(), $sha1DocumentoDescargado);
		if (!$selloArray->error) {
			//Todo OK	
			PCER::addSello($unDocumento->id, date('Y-m-d H:i:s',$selloArray->response_time), $sha1DocumentoDescargado, $selloArray->response_string);
		}else{
			//Fallo con la TSA
			$texto = "Fallo al firmar con la tsa: ".PCER::getTsaUrl()."\n";
			$texto .= "SELLO: ". var_export($selloArray,true);
			PCER::notificarERROR($unDocumento->id,$texto);
		}
		
	}
}