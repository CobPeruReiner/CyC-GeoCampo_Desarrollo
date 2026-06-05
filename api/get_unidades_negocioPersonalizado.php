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

$dpto = trim($_GET['dpto'] ?? '');
$provincia = trim($_GET['provincia'] ?? '');
$distrito = trim($_GET['distrito'] ?? '');

$fecha1 = $_GET['fecha1'] ?? '';
$fecha2 = $_GET['fecha2'] ?? '';

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
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'message' => 'Fechas inválidas.'
    ]);
    exit;
  }
}

if (empty($periodos)) {
  $periodos[] = date('Ym');
}

$placeholdersSc = implode(',', array_fill(0, count($periodos), '?'));
$placeholdersUn = implode(',', array_fill(0, count($periodos), '?'));

$sql = "
  SELECT DISTINCT
      un.NOMBRE_UNIDAD_NEGOCIO
  FROM C_FINANCIERA_CONFIANZA_CAMPO cf
  JOIN SUCURSALES_CONFIANZA_CAMPO sc
      ON sc.NOMBRE_AGENCIA = cf.NOMBRE_SUCURSAL_DE_CREDITO
  JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO un
      ON un.ID_UNIDAD_NEGOCIO = sc.ID_UNIDAD_NEGOCIO
  WHERE (? = '' OR cf.DPTO = ?)
    AND (? = '' OR cf.PROVINCIA = ?)
    AND (? = '' OR cf.DISTRITO = ?)
    AND sc.PERIODO IN ($placeholdersSc)
    AND un.PERIODO IN ($placeholdersUn)
    AND TRIM(IFNULL(un.NOMBRE_UNIDAD_NEGOCIO, '')) <> ''
  GROUP BY un.NOMBRE_UNIDAD_NEGOCIO
  ORDER BY un.NOMBRE_UNIDAD_NEGOCIO
";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Error preparando consulta.'
  ]);
  exit;
}

$params = [
  $dpto,
  $dpto,
  $provincia,
  $provincia,
  $distrito,
  $distrito,
  ...$periodos,
  ...$periodos
];

$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

$stmt->close();

echo json_encode($data);
exit;
