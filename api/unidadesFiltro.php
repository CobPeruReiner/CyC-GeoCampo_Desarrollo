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

$sql = "
  SELECT DISTINCT
    u.NOMBRE_UNIDAD_NEGOCIO AS nombre
  FROM UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
  WHERE TRIM(IFNULL(u.NOMBRE_UNIDAD_NEGOCIO, '')) <> ''
    AND u.ESTADO = 1
  ORDER BY u.NOMBRE_UNIDAD_NEGOCIO
";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error preparando consulta."
  ]);
  exit;
}

$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

$stmt->close();

echo json_encode([
  "success" => true,
  "data" => $data
]);
