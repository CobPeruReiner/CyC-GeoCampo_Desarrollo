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

$unidad = $_GET['unidad'] ?? '';

$sql = "
  SELECT DISTINCT
    p.IDPERSONAL AS id,
    CONCAT(p.NOMBRES, ' ', p.APELLIDOS) AS nombre
  FROM PERSONAL_UNIDAD_CONFIANZA pu
  INNER JOIN personal p
    ON p.IDPERSONAL = pu.ID_PERSONAL
  INNER JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
    ON u.ID_UNIDAD_NEGOCIO = pu.ID_UNIDAD_NEGOCIO
  WHERE (? = '' OR u.NOMBRE_UNIDAD_NEGOCIO = ?)
  ORDER BY p.NOMBRES, p.APELLIDOS
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

$stmt->bind_param("ss", $unidad, $unidad);

$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
  $data[] = $row;
}

$stmt->close();

echo json_encode([
  "success" => true,
  "data" => $data
]);
