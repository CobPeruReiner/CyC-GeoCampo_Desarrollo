<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$cartera = $data['cartera'] ?? '';
$fecha1 = $data['fecha1'] ?? '';
$fecha2 = $data['fecha2'] ?? '';

$dpto = trim($data['departamento'] ?? '');
$prov = trim($data['provincia'] ?? '');
$dist = trim($data['distrito'] ?? '');

$unidad = trim($data['unidadNegocio'] ?? '');

$asesor = 'TODOS';
$periodos = [];

if (!empty($fecha1) && !empty($fecha2)) {
  try {
    $inicio = new DateTime($fecha1);
    $fin = new DateTime($fecha2);

    if ($inicio > $fin) {
      [$inicio, $fin] = [$fin, $inicio];
    }

    $cursor = new DateTime($inicio->format('Y-m-01'));
    $limite = new DateTime($fin->format('Y-m-01'));
    $limite->modify('+1 month');

    while ($cursor < $limite) {
      $periodos[] = $cursor->format('Ym');
      $cursor->modify('+1 month');
    }
  } catch (Exception $e) {
    echo json_encode([
      'error' => true,
      'message' => 'Fechas inválidas',
      'details' => $e->getMessage()
    ]);
    exit;
  }
}

if (empty($periodos)) {
  $periodos[] = date('Ym');
}

if ($unidad !== '') {
  $placeholdersPu = implode(',', array_fill(0, count($periodos), '?'));
  $placeholdersUn = implode(',', array_fill(0, count($periodos), '?'));

  $sqlUnidad = "
    SELECT GROUP_CONCAT(DISTINCT pu.ID_PERSONAL) AS asesores
    FROM PERSONAL_UNIDAD_CONFIANZA pu
    JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO un
      ON un.ID_UNIDAD_NEGOCIO = pu.ID_UNIDAD_NEGOCIO
     AND un.PERIODO = pu.PERIODO
    WHERE un.NOMBRE_UNIDAD_NEGOCIO = ?
      AND pu.PERIODO IN ($placeholdersPu)
      AND un.PERIODO IN ($placeholdersUn)
  ";

  $stmt = $mysqli->prepare($sqlUnidad);

  if (!$stmt) {
    echo json_encode([
      'error' => true,
      'message' => 'Error en prepare unidad',
      'details' => $mysqli->error
    ]);
    exit;
  }

  $params = [
    $unidad,
    ...$periodos,
    ...$periodos
  ];

  $types = 's' . str_repeat('s', count($periodos) * 2);

  $stmt->bind_param($types, ...$params);
  $stmt->execute();

  $res = $stmt->get_result();
  $row = $res->fetch_assoc();

  if (!empty($row['asesores'])) {
    $asesor = $row['asesores'];
  }

  $stmt->close();
} else {
  $placeholdersS = implode(',', array_fill(0, count($periodos), '?'));
  $placeholdersPu = implode(',', array_fill(0, count($periodos), '?'));

  $sqlSucursal = "
    SELECT GROUP_CONCAT(DISTINCT pu.ID_PERSONAL) AS asesores
    FROM SUCURSALES_CONFIANZA_CAMPO s
    JOIN PERSONAL_UNIDAD_CONFIANZA pu
        ON pu.ID_UNIDAD_NEGOCIO = s.ID_UNIDAD_NEGOCIO
       AND pu.PERIODO = s.PERIODO
    WHERE (? = '' OR s.DEPARTAMENTO = ?)
      AND (? = '' OR s.PROVINCIA = ?)
      AND (? = '' OR s.DISTRITO = ?)
      AND s.PERIODO IN ($placeholdersS)
      AND pu.PERIODO IN ($placeholdersPu)
  ";

  $stmt = $mysqli->prepare($sqlSucursal);

  if (!$stmt) {
    echo json_encode([
      'error' => true,
      'message' => 'Error en prepare sucursales',
      'details' => $mysqli->error
    ]);
    exit;
  }

  $params = [
    $dpto,
    $dpto,
    $prov,
    $prov,
    $dist,
    $dist,
    ...$periodos,
    ...$periodos
  ];

  $types = str_repeat('s', 6 + count($periodos) + count($periodos));

  $stmt->bind_param($types, ...$params);
  $stmt->execute();

  $res = $stmt->get_result();
  $row = $res->fetch_assoc();

  if (!empty($row['asesores'])) {
    $asesor = $row['asesores'];
  }

  $stmt->close();
}

$stmt = $mysqli->prepare("CALL SP_GEOCAMPO_PRIMERA_ULTIMA_GESTION(?, ?, ?, ?)");

if (!$stmt) {
  echo json_encode([
    'error' => true,
    'message' => 'Error en prepare',
    'details' => $mysqli->error
  ]);
  exit;
}

$stmt->bind_param("ssss", $cartera, $fecha1, $fecha2, $asesor);

if (!$stmt->execute()) {
  echo json_encode([
    'error' => true,
    'message' => 'Error en execute',
    'details' => $stmt->error
  ]);
  exit;
}

$result = $stmt->get_result();

if ($result === false) {
  echo json_encode([
    'error' => true,
    'message' => 'Error en get_result',
    'details' => $stmt->error ?: $mysqli->error
  ]);
  exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}

$result->free();
$stmt->close();

while ($mysqli->more_results() && $mysqli->next_result()) {
  $extraResult = $mysqli->store_result();
  if ($extraResult instanceof mysqli_result) {
    $extraResult->free();
  }
}

echo json_encode([
  'error' => false,
  'rows' => $rows
]);
exit;
