<?php

// CRDENCIALES DB LOCAL
$database = 'SISTEMAGEST_DESARROLLO';
$host = '192.168.1.39';
$username = 'raul';
$password = "loquecallamoslosadmin1";

// CRDENCIALES DB LOCAL
// $database = 'SISTEMAGEST';
// $host = '192.168.1.31';
// $username = 'cycwebcob';
// $password = "k4&{'Ba7Np1";

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
