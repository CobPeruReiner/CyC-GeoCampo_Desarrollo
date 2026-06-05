<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_domain', '');

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

$dpto = $_GET['dpto'] ?? '';
$provincia = $_GET['provincia'] ?? '';

$sql = "SELECT DISTINCT DISTRITO
        FROM C_FINANCIERA_CONFIANZA_CAMPO
        WHERE (? = '' OR DPTO = ?)
          AND (? = '' OR PROVINCIA = ?)
        ORDER BY DISTRITO";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ssss", $dpto, $dpto, $provincia, $provincia);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode($data);
