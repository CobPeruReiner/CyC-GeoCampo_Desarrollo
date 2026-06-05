<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Lima');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if (isset($_GET['idcartera'])) {
    $currentIdCartera = $_GET['idcartera'];
    // Llamar al procedimiento almacenado "GetAsignacion" con el ID del usuario
    $query = "CALL GetAsesoresByCartera2(?)";
    $stmt = $mysqli->prepare($query);


    $stmt->bind_param('i', $currentIdCartera);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = array();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    // Devolver los datos en formato JSON
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo 'Idcartera no especificado.';
}
