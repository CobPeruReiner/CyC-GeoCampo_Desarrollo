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

error_log("[guardarGestion] SID=" . session_id() . " keys=" . implode(',', array_keys($_SESSION)));

if (empty($_SESSION['id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
  exit;
}

require_once 'horario.php';

// ==================== REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

require_once 'getUbicacion.php';
require_once 'incrustarDatos.php';

// ==================== FIN REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

$id_tabla = isset($_GET['id_tabla']) ? $_GET['id_tabla'] : $_SESSION['id_tabla'];
$identificador = isset($_GET['identificador']) ? $_GET['identificador'] : '';

// ===================== REQUERIMIENTO OBTENER DISTANCIA EN METROS =====================

$accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : 0;

// ===================== FIN REQUERIMIENTO OBTENER DISTANCIA EN METROS =====================

date_default_timezone_set('America/Lima');

include('config.php');

if ($mysqli->connect_error) {
  die('Error de conexión: ' . $mysqli->connect_error);
}

$idsPermitidos = [1235, 1391, 25, 1390, 647, 17, 1706];

if (!validarHorario() && !in_array((int)$_SESSION['id'], $idsPermitidos, true)) {
  $response = [
    'success' => false,
    'message' => 'Gestión no permitida fuera del horario.'
  ];
  echo json_encode($response);
  exit();
}

$fecha = date('Y-m-d H:i:s');
$idaccion   = $_POST['idaccion'];
$idefecto   = $_POST['idefecto'];
$horaVisita = $_POST['hora_visita'] . ':00';

$idmotivo   = !empty($_POST['idmotivo'])   ? $_POST['idmotivo']   : 0;
$idcontacto = !empty($_POST['idcontacto']) ? $_POST['idcontacto'] : 0;

$observacion = $_POST['observacion'];
$iddireccion = !empty($_POST['iddireccion']) ? $_POST['iddireccion'] : 0;
$idpersonal  = $_SESSION['id'];
$nomcontacto = $_POST['nomcontacto'];
$pisos       = $_POST['pisos'];
$puerta      = $_POST['puerta'];
$fachada     = $_POST['fachada'];

$fechaPromesaRaw = isset($_POST['fecha_promesa']) ? trim((string)$_POST['fecha_promesa']) : '';
$montoPromesaRaw = isset($_POST['monto_promesa']) ? trim((string)$_POST['monto_promesa']) : '';

// Por defecto, los efectos que no son promesa guardan ambos valores como NULL.
$fecha_promesa = "NULL";
$monto_promesa = "NULL";

$latitud  = isset($_POST['latitud']) ? floatval($_POST['latitud']) : 0;
$longitud = isset($_POST['longitud']) ? floatval($_POST['longitud']) : 0;
$txt      = $_POST['txt'];

$idefecto = intval($idefecto);

// Validaciones básicas
if (!isset($idefecto) || $idefecto === null || empty($idefecto) || !isset($iddireccion) || $iddireccion === null || $iddireccion == 0 || empty($iddireccion)) {
  $response1 = array();
  $response1['success'] = false;
  $response1['message'] = "Error en registro, compruebe conexión, actualice la página e ingrese gestión nuevamente" . $mysqli->error;
  header('Content-Type: application/json');
  echo json_encode($response1);
  exit;
}

// VALIDAR SI EL EFECTO ES PROMESA
$stmt = $mysqli->prepare("SELECT promesa FROM efecto WHERE IDEFECTO = ?");
$stmt->bind_param("i", $idefecto);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$esPromesa = isset($row['promesa']) && intval($row['promesa']) === 1;

if ($esPromesa) {
  if ($fechaPromesaRaw === '') {
    echo json_encode([
      "success" => false,
      "message" => "Selecciona la fecha de promesa."
    ]);
    exit;
  }

  $zonaHoraria = new DateTimeZone('America/Lima');
  $fechaPromesaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaPromesaRaw, $zonaHoraria);
  $erroresFecha = DateTimeImmutable::getLastErrors();
  $fechaInvalida = $fechaPromesaObj === false
    || ($erroresFecha !== false && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))
    || ($fechaPromesaObj !== false && $fechaPromesaObj->format('Y-m-d') !== $fechaPromesaRaw);

  if ($fechaInvalida) {
    echo json_encode([
      "success" => false,
      "message" => "Revisa la fecha de promesa seleccionada."
    ]);
    exit;
  }

  $hoy = new DateTimeImmutable('today', $zonaHoraria);
  $ultimoDiaMes = $hoy->modify('last day of this month');

  if ($fechaPromesaObj < $hoy) {
    echo json_encode([
      "success" => false,
      "message" => "Selecciona una fecha desde hoy."
    ]);
    exit;
  }

  if ($fechaPromesaObj > $ultimoDiaMes) {
    echo json_encode([
      "success" => false,
      "message" => "Selecciona una fecha dentro de este mes."
    ]);
    exit;
  }

  if ($montoPromesaRaw === '') {
    echo json_encode([
      "success" => false,
      "message" => "Ingresa el monto de la promesa."
    ]);
    exit;
  }

  if (!is_numeric($montoPromesaRaw)) {
    echo json_encode([
      "success" => false,
      "message" => "Revisa el monto de la promesa."
    ]);
    exit;
  }

  $montoPromesaValor = (float)$montoPromesaRaw;

  if (!is_finite($montoPromesaValor) || $montoPromesaValor < 0) {
    echo json_encode([
      "success" => false,
      "message" => "Ingresa un monto igual o mayor a 0."
    ]);
    exit;
  }

  $fecha_promesa = "'" . $mysqli->real_escape_string($fechaPromesaRaw) . "'";
  $monto_promesa = number_format($montoPromesaValor, 2, '.', '');
}



if ($horaVisita < "07:00:00" || $horaVisita > "20:00:00") {
  $response = array(
    "success" => false,
    "message" => "La hora de visita debe estar entre las 07:00 y las 20:00"
  );
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}

$defaultBase = '/var/www/html/fotos';

if (!empty($_SERVER['DOCUMENT_ROOT'])) {
  $defaultBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/fotos';
}

$IS_PROD = getenv('IS_DOCKER') == '1';

if ($IS_PROD) {
  $UPLOAD_BASE = '/app/fotos';
} else {
  $UPLOAD_BASE = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/3.CyC-GeoCampo_67/CyC-GeoCampo/fotos';
}

$UPLOAD_BASE = rtrim($UPLOAD_BASE, '/');

$baseCarpeta  = substr($id_tabla ?? '', 2);

$carpeta_ruta = preg_replace('/[^A-Za-z0-9_\-]/', '', $baseCarpeta) ?: 'default';

$fechaCarpeta = date('Y-m-d');

$destDir = $UPLOAD_BASE . '/' . $carpeta_ruta . '/' . $fechaCarpeta;

error_log("[guardarGestion] UPLOAD_BASE=$UPLOAD_BASE destDir=$destDir docroot=" . ($_SERVER['DOCUMENT_ROOT'] ?? '') .
  " umask=" . sprintf('%o', umask()) .
  " post_max_size=" . ini_get('post_max_size') .
  " upload_max_filesize=" . ini_get('upload_max_filesize'));

if (!is_dir($destDir)) {
  if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) {
    error_log("No se pudo crear directorio: $destDir");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar carpeta de imágenes.']);
    exit;
  }
}

if (!is_writable($destDir)) {
  error_log("Directorio no escribible: $destDir perms=" . decoct(@fileperms($destDir) & 0777));
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'La carpeta de imágenes no es escribible.']);
  exit;
}

$MAX_BYTES = 15 * 1024 * 1024;

$permitidos = [
  'image/jpeg' => 'jpg',
  'image/jpg'  => 'jpg',
  'image/png'  => 'png',
  'image/heic' => 'jpg',
  'image/heif' => 'jpg'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);

$identificadorSafe = preg_replace('/[^A-Za-z0-9_\-]/', '', $identificador ?? '');

// ========================== REDUCIR TAMAÑO DE IMAGEN ==========================

function reducirImagen($ruta, $maxWidth = 1600)
{
  list($width, $height, $type) = getimagesize($ruta);

  if ($width <= $maxWidth) return;

  $ratio = $height / $width;
  $newWidth = $maxWidth;
  $newHeight = intval($maxWidth * $ratio);

  $src = imagecreatefromjpeg($ruta);
  if (!$src) return;

  $dst = imagecreatetruecolor($newWidth, $newHeight);

  imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

  imagejpeg($dst, $ruta, 80);

  imagedestroy($src);
  imagedestroy($dst);
}

// ==================== REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

function subirImagen(
  $campo,
  $prefix,
  $identificadorSafe,
  $destDir,
  $permitidos,
  $MAX_BYTES,
  $finfo,
  $lat,
  $lon,
  $procesarImagen = true
) {

  // DEBUG UPLOAD
  if (isset($_FILES[$campo])) {
    error_log("UPLOAD ERROR: " . $_FILES[$campo]['error']);
    error_log("UPLOAD SIZE: " . $_FILES[$campo]['size']);
    error_log("UPLOAD TMP: " . $_FILES[$campo]['tmp_name']);
  }

  if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) return null;
  if ($_FILES[$campo]['size'] > $MAX_BYTES) return null;

  $mime = $finfo->file($_FILES[$campo]['tmp_name']);
  if (!isset($permitidos[$mime])) return null;

  $ext = $permitidos[$mime];
  $timestamp = date('YmdHis');
  $rand = bin2hex(random_bytes(4));

  $finalName = "{$prefix}{$identificadorSafe}_{$timestamp}_{$rand}.{$ext}";
  $destPath  = $destDir . '/' . $finalName;

  if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destPath)) {
    return null;
  }

  // KIWIPAY (cartera 91) requiere conservar el archivo original, sin
  // reducción, consulta de ubicación ni texto incrustado en la imagen.
  if (!$procesarImagen) {
    return $finalName;
  }

  reducirImagen($destPath, 1600);

  $geo = getUbicacionDesdeGPS($lat, $lon);
  $ts  = time();

  $lineas = [];

  if ($geo) {
    $lineas[] = "{$geo['provincia']}, {$geo['distrito']}, {$geo['pais']}";

    if (!empty($geo['calle'])) {
      $lineas[] = "{$geo['calle']}, {$geo['distrito']}, {$geo['provincia']}, {$geo['pais']}";
    }
  }

  $lineas[] = "Lat {$lat}  Long {$lon}";

  $lineas[] = fechaHoraBonita($ts);

  incrustarDatosEnImagen($destPath, $lineas);

  return $finalName;
}

// ==================== FIN REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

// ===================== CLASIFICAR ESTADO GPS =====================

function obtenerEstadoUbicacion(float $accuracy, int $segundos, float $lat, float $lon): int
{
  if (
    $accuracy <= 0 ||
    $lat < -90 || $lat > 90 ||
    $lon < -180 || $lon > 180 ||
    ($lat == 0 && $lon == 0)
  ) {
    return 5;
  }

  if ($accuracy > 100 || $segundos > 1800) return 4;
  if ($accuracy > 60  || $segundos > 900)  return 3;
  if ($accuracy > 25  || $segundos > 300)  return 2;
  return 1;
}

// ===================== CALCULAR TIEMPO DE SINCRONIZACIÓN =====================

$segundosDiferencia = 0;

if (!empty($_POST['fecha_creacion_dispositivo'])) {

  $fechaDispositivo = $_POST['fecha_creacion_dispositivo'];
  $fechaDispositivoTS = strtotime($fechaDispositivo);

  if ($fechaDispositivoTS !== false) {

    $fechaServidorTS = time();
    $segundosDiferencia = $fechaServidorTS - $fechaDispositivoTS;

    if ($segundosDiferencia < 0) {
      $segundosDiferencia = 0;
    }

    if ($segundosDiferencia > 2592000) {
      $segundosDiferencia = 2592000;
    }
  }
}

$tiempoFormateado = gmdate("H:i:s", $segundosDiferencia);

// ===================== OBTENER ESTADO GPS =====================

$estadoGPS = obtenerEstadoUbicacion(
  $accuracy,
  $segundosDiferencia,
  $latitud,
  $longitud
);

// ===================== FIN CÁLCULO =====================

// ==================== REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

$idcartera = isset($_POST['id_cartera']) ? (int)$_POST['id_cartera'] : 0;
$procesarImagen = $idcartera !== 91;

$imagen1 = subirImagen(
  'imagen1',
  'Imagen1',
  $identificadorSafe,
  $destDir,
  $permitidos,
  $MAX_BYTES,
  $finfo,
  $latitud,
  $longitud,
  $procesarImagen
);

$imagen2 = subirImagen(
  'imagen2',
  'Imagen2',
  $identificadorSafe,
  $destDir,
  $permitidos,
  $MAX_BYTES,
  $finfo,
  $latitud,
  $longitud,
  $procesarImagen
);

$imagen3 = subirImagen(
  'imagen3',
  'Imagen3',
  $identificadorSafe,
  $destDir,
  $permitidos,
  $MAX_BYTES,
  $finfo,
  $latitud,
  $longitud,
  $procesarImagen
);

// ==================== FIN REQUERIMIENTO INCRUSTAR DATOS EN IMAGEN ====================

$img1Sql = is_null($imagen1) ? "NULL" : "'" . $mysqli->real_escape_string($imagen1) . "'";
$img2Sql = is_null($imagen2) ? "NULL" : "'" . $mysqli->real_escape_string($imagen2) . "'";
$img3Sql = is_null($imagen3) ? "NULL" : "'" . $mysqli->real_escape_string($imagen3) . "'";

$identificador_sql = $mysqli->real_escape_string($identificador);

$sql = "CALL SP_InsertarGEOCAMPO_PRUEBA(
    '$identificador_sql',
    '$id_tabla',
    '$idefecto',
    '$idmotivo',
    '$idcontacto',
    '$observacion',
    '$iddireccion',
    '$idpersonal',
    '$nomcontacto',
    '$pisos',
    '$puerta',
    '$fachada',
    $fecha_promesa,
    $monto_promesa,
    '$idcartera',
    '$latitud',
    '$longitud',
    '$txt',
    $img1Sql,
    $img2Sql,
    $img3Sql,
    '$horaVisita',
    '$accuracy',
    '$estadoGPS',
    '$segundosDiferencia'
)";


function tablaFisicaExisteGestion(mysqli $mysqli, string $tabla): bool
{
  if (!preg_match('/^[A-Za-z0-9_]+$/', $tabla)) return false;

  $stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
  ");
  if (!$stmt) return false;

  $stmt->bind_param('s', $tabla);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  return (int)($row['total'] ?? 0) > 0;
}

function resolverNombreTablaGestion(mysqli $mysqli, array $row): string
{
  $candidatos = [
    'tabla', 'TABLA', 'nomtable', 'NOMTABLE', 'nombre_tabla', 'NOMBRE_TABLA',
    'tabla_fisica', 'TABLA_FISICA', 'table_name', 'TABLE_NAME', 'nom_tabla', 'NOM_TABLA'
  ];

  foreach ($candidatos as $campo) {
    $valor = trim((string)($row[$campo] ?? ''));
    if ($valor !== '' && preg_match('/^[A-Za-z0-9_]+$/', $valor) && tablaFisicaExisteGestion($mysqli, $valor)) {
      return $valor;
    }
  }

  foreach ($row as $valor) {
    $valor = trim((string)$valor);
    if ($valor !== '' && preg_match('/^C_[A-Za-z0-9_]+$/', $valor) && tablaFisicaExisteGestion($mysqli, $valor)) {
      return $valor;
    }
  }

  return '';
}

function resolverContextoGeocampoGestion(mysqli $mysqli, $idTablaParam, int $idCarteraPost): array
{
  $valor = trim((string)$idTablaParam);
  $ctx = [
    'id_table' => 0,
    'id_cartera' => $idCarteraPost,
    'tabla' => ''
  ];

  if ($valor !== '' && preg_match('/^[A-Za-z0-9_]+$/', $valor) && !ctype_digit($valor) && tablaFisicaExisteGestion($mysqli, $valor)) {
    $ctx['tabla'] = $valor;
  }

  if ($valor !== '' && ctype_digit($valor)) {
    $idTabla = (int)$valor;
    $stmt = $mysqli->prepare("SELECT * FROM tabla_log WHERE id = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('i', $idTabla);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($row) {
        $ctx['id_table'] = (int)($row['id'] ?? $idTabla);
        $ctx['id_cartera'] = (int)($row['id_cartera'] ?? $row['IDCARTERA'] ?? $idCarteraPost);
        $ctx['tabla'] = resolverNombreTablaGestion($mysqli, $row);
      }
    }
  }

  if ($ctx['tabla'] === '') {
    $stmt = $mysqli->prepare("SELECT * FROM tabla_log WHERE id_cartera = ? AND estado = 0 ORDER BY id DESC LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('i', $idCarteraPost);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($row) {
        $ctx['id_table'] = (int)($row['id'] ?? 0);
        $ctx['id_cartera'] = (int)($row['id_cartera'] ?? $idCarteraPost);
        $ctx['tabla'] = resolverNombreTablaGestion($mysqli, $row);
      }
    }
  }

  if ($ctx['tabla'] === '' && tablaFisicaExisteGestion($mysqli, 'C_FINANCIERA_EFECTIVA_CAMPO')) {
    $ctx['tabla'] = 'C_FINANCIERA_EFECTIVA_CAMPO';
  }

  return $ctx;
}

function obtenerIdEstadoVisitaGestion(mysqli $mysqli, string $codigo): int
{
  $stmt = $mysqli->prepare("SELECT id_estado_visita FROM geocampo_estado_visita WHERE codigo = ? AND activo = 1 LIMIT 1");
  if (!$stmt) return 0;
  $stmt->bind_param('s', $codigo);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $row ? (int)$row['id_estado_visita'] : 0;
}

function obtenerIdEstadoAsignacionGestion(mysqli $mysqli, string $codigo): int
{
  $stmt = $mysqli->prepare("SELECT id_estado_asignacion FROM geocampo_estado_asignacion WHERE codigo = ? AND activo = 1 LIMIT 1");
  if (!$stmt) return 0;
  $stmt->bind_param('s', $codigo);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $row ? (int)$row['id_estado_asignacion'] : 0;
}

function marcarRutaComoVisitada(mysqli $mysqli, string $identificador, int $idPersonal, float $latitud, float $longitud, string $observacion, string $fechaGestion, $idTablaParam = '', int $idCarteraPost = 0): array
{
  $resultado = [
    'detalle_actualizado' => 0,
    'asignacion_actualizada' => 0,
    'id_detalle' => null,
    'id_asignacion' => null,
    'fecha_ruta' => null,
    'motivo' => null
  ];

  $identificador = trim($identificador);
  $fechaGestion = trim($fechaGestion);

  if ($identificador === '' || $idPersonal <= 0 || $fechaGestion === '') {
    $resultado['motivo'] = 'Datos insuficientes para actualizar hoja de ruta.';
    return $resultado;
  }

  $ctx = resolverContextoGeocampoGestion($mysqli, $idTablaParam, $idCarteraPost);
  $tablaCuentas = $ctx['tabla'];
  $idTable = (int)$ctx['id_table'];
  $idCarteraCtx = (int)$ctx['id_cartera'];

  if ($tablaCuentas === '') {
    $resultado['motivo'] = 'No se pudo resolver la tabla física de la cartera para actualizar GeoCampo.';
    return $resultado;
  }

  $idEstadoVisitaVisitado = obtenerIdEstadoVisitaGestion($mysqli, 'VISITADO');
  if ($idEstadoVisitaVisitado <= 0) {
    $resultado['motivo'] = 'No existe estado de visita VISITADO activo.';
    return $resultado;
  }

  $idEstadoAsignacionVisitado = obtenerIdEstadoAsignacionGestion($mysqli, 'VISITADO');

  $filtroContextoAsignacion = '';
  if ($idTable > 0) {
    $filtroContextoAsignacion .= " AND ga.id_table = {$idTable} ";
  }
  if ($idCarteraCtx > 0) {
    $filtroContextoAsignacion .= " AND ga.id_cartera = {$idCarteraCtx} ";
  }

  $stmt = $mysqli->prepare("
    SELECT
      hrd.id_detalle,
      hrd.id_asignacion,
      hr.fecha_ruta
    FROM geocampo_hoja_ruta_detalle hrd
    INNER JOIN geocampo_hoja_ruta hr
      ON hr.id_hoja_ruta = hrd.id_hoja_ruta
    INNER JOIN geocampo_estado_ruta er
      ON er.id_estado_ruta = hr.id_estado_ruta
    INNER JOIN geocampo_asignacion ga
      ON ga.id_asignacion = hrd.id_asignacion
    INNER JOIN {$tablaCuentas} c
      ON c.id = ga.id_cuenta_campo
    LEFT JOIN geocampo_estado_visita ev
      ON ev.id_estado_visita = hrd.id_estado_visita
    WHERE ga.id_asesor = ?
      AND ga.activo = 1
      {$filtroContextoAsignacion}
      AND c.IDENTIFICADOR = ?
      AND er.codigo IN ('CREADA', 'EN_PROCESO')
      AND COALESCE(ev.codigo, '') <> 'VISITADO'
      AND (
            (
              hr.fecha_inicio_semana IS NOT NULL
              AND hr.fecha_fin_semana IS NOT NULL
              AND DATE(?) BETWEEN hr.fecha_inicio_semana AND hr.fecha_fin_semana
            )
            OR (
              hr.fecha_inicio_semana IS NULL
              AND DATE(?) BETWEEN DATE_SUB(hr.fecha_ruta, INTERVAL WEEKDAY(hr.fecha_ruta) DAY)
                              AND DATE_ADD(DATE_SUB(hr.fecha_ruta, INTERVAL WEEKDAY(hr.fecha_ruta) DAY), INTERVAL 6 DAY)
            )
          )
    ORDER BY
      CASE WHEN DATE(hr.fecha_ruta) = DATE(?) THEN 0 ELSE 1 END,
      hrd.orden_visita IS NULL,
      hrd.orden_visita,
      hrd.id_detalle DESC
    LIMIT 1
  ");

  if (!$stmt) {
    $resultado['motivo'] = 'No se pudo preparar consulta de detalle de hoja de ruta: ' . $mysqli->error;
    return $resultado;
  }

  $stmt->bind_param('issss', $idPersonal, $identificador, $fechaGestion, $fechaGestion, $fechaGestion);
  $stmt->execute();
  $detalle = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$detalle) {
    // Fallback: si la gestión existe pero no se halló detalle de ruta, al menos sincroniza la asignación activa.
    if ($idEstadoAsignacionVisitado > 0) {
      $stmt = $mysqli->prepare("
        UPDATE geocampo_asignacion ga
        INNER JOIN {$tablaCuentas} c
          ON c.id = ga.id_cuenta_campo
        LEFT JOIN geocampo_estado_asignacion ea
          ON ea.id_estado_asignacion = ga.id_estado_asignacion
        SET ga.id_estado_asignacion = ?,
            ga.fecha_actualizacion = NOW()
        WHERE ga.id_asesor = ?
          AND ga.activo = 1
          {$filtroContextoAsignacion}
          AND c.IDENTIFICADOR = ?
          AND (ea.codigo IS NULL OR ea.codigo IN ('PENDIENTE', 'AGENDADO', 'REPROGRAMADO'))
      ");
      if ($stmt) {
        $stmt->bind_param('iis', $idEstadoAsignacionVisitado, $idPersonal, $identificador);
        $stmt->execute();
        $resultado['asignacion_actualizada'] = max(0, (int)$stmt->affected_rows);
        $stmt->close();
      }
    }

    $resultado['motivo'] = $resultado['asignacion_actualizada'] > 0
      ? 'Asignación actualizada como visitada; no se encontró detalle de ruta vigente.'
      : 'No se encontro detalle pendiente en hoja de ruta para la semana de la gestion.';
    return $resultado;
  }

  $idDetalle = (int)$detalle['id_detalle'];
  $idAsignacion = (int)$detalle['id_asignacion'];

  $resultado['id_detalle'] = $idDetalle;
  $resultado['id_asignacion'] = $idAsignacion;
  $resultado['fecha_ruta'] = $detalle['fecha_ruta'];

  $stmt = $mysqli->prepare("
    UPDATE geocampo_hoja_ruta_detalle
    SET
      id_estado_visita = ?,
      fecha_visita = ?,
      resultado_visita = 'VISITADO',
      observacion = ?,
      latitud = ?,
      longitud = ?,
      fecha_actualizacion = NOW()
    WHERE id_detalle = ?
  ");

  if ($stmt) {
    $stmt->bind_param('issddi', $idEstadoVisitaVisitado, $fechaGestion, $observacion, $latitud, $longitud, $idDetalle);
    $stmt->execute();
    $resultado['detalle_actualizado'] = max(0, (int)$stmt->affected_rows);
    $stmt->close();
  }

  if ($idEstadoAsignacionVisitado > 0 && $idAsignacion > 0) {
    $stmt = $mysqli->prepare("
      UPDATE geocampo_asignacion
      SET
        id_estado_asignacion = ?,
        fecha_actualizacion = NOW()
      WHERE id_asignacion = ?
        AND id_asesor = ?
        AND activo = 1
    ");

    if ($stmt) {
      $stmt->bind_param('iii', $idEstadoAsignacionVisitado, $idAsignacion, $idPersonal);
      $stmt->execute();
      $resultado['asignacion_actualizada'] = max(0, (int)$stmt->affected_rows);
      $stmt->close();
    }
  }

  $resultado['motivo'] = 'Hoja de ruta actualizada correctamente.';
  return $resultado;
}

$response = array();

if ($mysqli->query($sql) === TRUE) {

  while ($mysqli->more_results() && $mysqli->next_result()) {
    if ($res = $mysqli->store_result()) {
      $res->free();
    }
  }

  $actualizacionRuta = marcarRutaComoVisitada(
    $mysqli,
    $identificador,
    (int)$idpersonal,
    (float)$latitud,
    (float)$longitud,
    $observacion,
    $fecha,
    $id_tabla,
    (int)$idcartera
  );

  $response['success'] = true;
  $response['message'] = "Gestión ingresada correctamente.";
  $response['ruta_actualizada'] = $actualizacionRuta;
} else {
  $response['success'] = false;
  $response['message'] = "Error al insertar registro: " . $mysqli->error;
}

$response['accuracy_gps'] = $accuracy;
$response['tiempo_sincronizacion'] = $tiempoFormateado;

$mysqli->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;
