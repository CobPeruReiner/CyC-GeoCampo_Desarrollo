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

$idPersonal = (int)$_SESSION['id'];
$idUnidad   = isset($_GET['unidad']) ? (int)$_GET['unidad'] : 0;

if ($idUnidad <= 0) {
  http_response_code(400);
  echo json_encode([
    "success" => false,
    "message" => "Unidad inválida"
  ]);
  exit;
}

$IS_PROD = getenv('IS_DOCKER') == '1';

try {

  if ($IS_PROD) {

    $sql = "
      SELECT
        s.ID_AGENCIA AS id,
        CONCAT(
          s.NOMBRE_AGENCIA,
          ' (',
          IFNULL(s.DISTRITO,''),
          ')'
        ) AS nombre
      FROM SUCURSALES_CONFIANZA_CAMPO s
      INNER JOIN PERSONAL_UNIDAD_CONFIANZA pu
        ON pu.ID_UNIDAD_NEGOCIO = s.ID_UNIDAD_NEGOCIO
      WHERE pu.ID_PERSONAL = ?
        AND s.ID_UNIDAD_NEGOCIO = ?
        AND pu.ESTADO = 1
      ORDER BY s.NOMBRE_AGENCIA;
    ";
  } else {

    $sql = "
      SELECT
        s.ID_AGENCIA AS id,
        CONCAT(
          s.NOMBRE_AGENCIA,
          ' (',
          IFNULL(s.DISTRITO,''),
          ')'
        ) AS nombre
      FROM SUCURSALES_CONFIANZA_CAMPO s
      INNER JOIN PERSONAL_UNIDAD_CONFIANZA pu
        ON pu.ID_UNIDAD_NEGOCIO = s.ID_UNIDAD_NEGOCIO
        AND pu.PERIODO = s.PERIODO
      WHERE pu.ID_PERSONAL = ?
      AND s.ID_UNIDAD_NEGOCIO = ?
      AND s.PERIODO = (
          SELECT MAX(PERIODO)
          FROM SUCURSALES_CONFIANZA_CAMPO
      )
      ORDER BY s.NOMBRE_AGENCIA
    ";
  }

  $stmt = $mysqli->prepare($sql);

  if (!$stmt) {
    throw new Exception("Error preparando consulta");
  }

  $stmt->bind_param("ii", $idPersonal, $idUnidad);
  $stmt->execute();

  $result = $stmt->get_result();

  $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }

  echo json_encode([
    "success" => true,
    "rows" => $rows
  ]);

  $stmt->close();
  $mysqli->close();
} catch (Exception $e) {

  http_response_code(500);

  echo json_encode([
    "success" => false,
    "message" => "Error consultando agencias"
  ]);
}
