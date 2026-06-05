<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Lima');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

// -------------------------
// VALIDACIONES
// -------------------------
$idCartera  = $_GET['idCartera']  ?? null;
$idPersonal = $_GET['idPersonal'] ?? null;
$fecha      = $_GET['fechaInicio'] ?? null;
$fecha2     = $_GET['fechaFin'] ?? null;

if (!$idCartera || !$idPersonal || !$fecha) {
  echo json_encode(['error' => 'Parámetros incompletos']);
  exit;
}

// -------------------------
// CASO 1: TODOS LOS ASESORES
// -------------------------
if ($idPersonal === 'TODOS') {

  $query = "
        SELECT g.FECHA, g.IDENTIFICADOR, g.IDEFECTO, e.EFECTO,
               g.IDMOTIVO, m.MOTIVO, g.IDCONTACTO, c.CONTACTO,
               g.OBSERVACION, g.IDDIRECCION, d.DIRECCION_DEPURADA,
               g.IDPERSONAL, g.NOMCONTACTO, g.PISOS, g.PUERTA,
               g.FACHADA, g.FECHA_PROMESA, g.MONTO_PROMESA,
               g.IDCARTERA, g.latitud, g.longitud,
               g.imagen1, g.imagen2, g.imagen3
        FROM GEOCAMPO g
        LEFT JOIN efecto e ON g.IDEFECTO = e.IDEFECTO
        LEFT JOIN motivo m ON g.IDMOTIVO = m.IDMOTIVO
        LEFT JOIN contacto c ON g.IDCONTACTO = c.IDCONTACTO
        LEFT JOIN direcciones d ON g.IDDIRECCION = d.IDDIRECCION
        WHERE g.IDCARTERA = ?
          AND g.FECHA BETWEEN ?
              AND IFNULL(
                  ? + INTERVAL 1 DAY - INTERVAL 1 SECOND,
                  ? + INTERVAL 1 DAY - INTERVAL 1 SECOND
              )
          AND g.latitud <> ''
        ORDER BY g.ID DESC
    ";

  $stmt = $mysqli->prepare($query);
  $stmt->bind_param(
    'isss',
    $idCartera,
    $fecha,
    $fecha2,
    $fecha
  );
}
// -------------------------
// CASO 2: ASESOR ESPECÍFICO
// -------------------------
else {

  if ($fecha2) {
    $query = "CALL GetCampoGestiones3(?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iiss', $idPersonal, $idCartera, $fecha, $fecha2);
  } else {
    $query = "CALL GetCampoGestiones3(?, ?, ?, NULL)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iis', $idPersonal, $idCartera, $fecha);
  }
}

// -------------------------
// EJECUCIÓN
// -------------------------
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

$stmt->close();

$_SESSION['informe_imagenes'] = $data;

echo json_encode($data, JSON_UNESCAPED_UNICODE);
