<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['id'])) {
  http_response_code(401);
  echo json_encode([
    'success' => false,
    'message' => 'Sesión no válida o expirada.'
  ]);
  exit;
}

date_default_timezone_set('America/Lima');

include('../config.php');

if (!isset($mysqli) || $mysqli->connect_error) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Error de conexión a BD.'
  ]);
  exit;
}

$sql = "SELECT DISTINCT DPTO 
        FROM C_FINANCIERA_CONFIANZA_CAMPO
        ORDER BY DPTO";

$result = $mysqli->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode($data);
