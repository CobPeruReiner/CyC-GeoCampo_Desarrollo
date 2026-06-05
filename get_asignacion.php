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

// ⏱ INICIO DEL CRONÓMETRO
$inicio = microtime(true);

if (isset($_GET['userId'])) {
    $userId = (int)$_GET['userId'];

    // Llamar al procedimiento almacenado "GetAsignacion2" con el ID del usuario
    $query = "CALL GetAsignacion2(?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    // ⏱ FIN DEL CRONÓMETRO
    $tiempo = microtime(true) - $inicio;
    error_log(sprintf("[CARTERAS] userId=%d → tiempo de carga: %.3f s", $userId, $tiempo));

    // Devolver los datos en formato JSON
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Usuario no especificado']);
}
