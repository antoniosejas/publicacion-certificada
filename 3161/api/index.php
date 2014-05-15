<?php
/**
  * Debe recibir como entrada un hash (sha1) para firmar y una url de la tsa con la que queremos firmar
  * Si nos incluyen la variable validate, procederá a validar el sello de tiempo, validate debe ser una url que apunte al certificado público de la tsa.
 **/
require_once "../timestamp.php";
$tsa = (isset($_GET['tsa']))?$_GET['tsa']:'http://zeitstempel.dfn.de';
$my_hash = (isset($_GET['debug'])&&!isset($_GET['sha1']))?$_GET['sha1']:sha1('hola');
echo Timestamp::getTimestamp($tsa,$my_hash);
