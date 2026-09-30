<?php

// CRDENCIALES DB LOCAL
$database = 'SISTEMAGEST';
$host = '192.168.1.36';
$username = 'geocampo';
$password = "hibZoX!wf./Ow_0T";

// CRDENCIALES DB LOCAL
// $database = 'SISTEMAGEST';
// $host = '192.168.1.31';
// $username = 'geocampo';
// $password = "hibZoX!wf./Ow_0T";

// CREDENCIALES DB AMAZON
// $database = 'SISTEMAGEST_CONTINGENCIA';
// $host = '181.66.252.129';
// $username = 'svr009';
// $password = "aresvela";

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");
