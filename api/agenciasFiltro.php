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
  SELECT
    MIN(s.ID_AGENCIA) AS id,
    CASE
      WHEN s.NOMBRE_AGENCIA = 'OTROS' THEN
        CONCAT(
          s.NOMBRE_AGENCIA,
          ' - ',
          IFNULL(u.NOMBRE_UNIDAD_NEGOCIO, 'SIN UNIDAD')
        )
      ELSE
        CONCAT(
          s.NOMBRE_AGENCIA,
          ' (',
          IFNULL(s.DISTRITO, ''),
          ')'
        )
    END AS nombre
  FROM SUCURSALES_CONFIANZA_CAMPO s
  LEFT JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
    ON u.ID_UNIDAD_NEGOCIO = s.ID_UNIDAD_NEGOCIO
    AND u.ESTADO = 1
  WHERE (? = '' OR u.NOMBRE_UNIDAD_NEGOCIO = ?)
  GROUP BY nombre
  ORDER BY nombre
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
