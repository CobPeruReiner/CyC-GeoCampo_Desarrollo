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

$fInicio  = $_GET['fechaInicio'] ?? null;
$fFin     = $_GET['fechaFin'] ?? null;
$unidad   = $_GET['unidad'] ?? null;
$agencia  = $_GET['agencia'] ?? null;
$gestor   = $_GET['gestor'] ?? null;

$sql = "
  SELECT 
      v.ID_GEOCAMPO_VISITA_OFICINA,

      DATE_FORMAT(v.fecha_registro_bd,'%Y-%m-%d %H:%i:%s') AS fecha,

      v.funcionario_nombre AS funcionario,
      v.funcionario_telefono AS funcionario_telefono,

      CONCAT(pg.NOMBRES,' ',pg.APELLIDOS) AS gestor,

      u.NOMBRE_UNIDAD_NEGOCIO AS unidad,

      COALESCE(
        CONCAT(s.NOMBRE_AGENCIA,' (',IFNULL(s.DISTRITO,''),')'),
        v.agencia_otro
      ) AS agencia,

      v.latitud,
      v.longitud,
      v.accuracy,

      eg.NOMBRE_GEOCAMPO_ESTADO_GPS AS estado_gps,
      eg.DESCRIPCION AS estado_gps_desc,

      CASE 
          WHEN v.accuracy <= 10 THEN 'bg-success-light'
          WHEN v.accuracy <= 30 THEN 'bg-warning-light'
          ELSE 'bg-danger-light'
      END AS gps_bg,

      v.ruta_foto

  FROM GEOCAMPO_VISITA_OFICINA v

  LEFT JOIN GEOCAMPO_ESTADO_GPS eg
    ON eg.ID_GEOCAMPO_ESTADO_GPS = v.ID_GEOCAMPO_ESTADO_GPS

  LEFT JOIN personal pg
    ON pg.IDPERSONAL = v.idpersonal

  LEFT JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
    ON u.ID_UNIDAD_NEGOCIO = v.ID_UNIDAD_NEGOCIO

  LEFT JOIN SUCURSALES_CONFIANZA_CAMPO s
    ON s.ID_AGENCIA = v.ID_AGENCIA

  WHERE 1=1
";

$params = [];
$types  = "";

if ($fInicio) {
  $sql .= " AND DATE(v.fecha_registro_bd) >= ?";
  $params[] = $fInicio;
  $types .= "s";
}

if ($fFin) {
  $sql .= " AND DATE(v.fecha_registro_bd) <= ?";
  $params[] = $fFin;
  $types .= "s";
}

if ($unidad) {
  $sql .= " AND u.NOMBRE_UNIDAD_NEGOCIO = ?";
  $params[] = $unidad;
  $types .= "s";
}

if ($agencia) {
  $sql .= " AND COALESCE(CONCAT(s.NOMBRE_AGENCIA,' (',IFNULL(s.DISTRITO,''),')'), v.agencia_otro) = ?";
  $params[] = $agencia;
  $types .= "s";
}

if ($gestor) {
  $sql .= " AND v.idpersonal = ?";
  $params[] = $gestor;
  $types .= "i";
}

$sql .= " ORDER BY v.fecha_registro_bd DESC";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error preparando consulta."
  ]);
  exit;
}

if ($params) {
  $stmt->bind_param($types, ...$params);
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
