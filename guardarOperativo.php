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
  echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
  exit;
}

date_default_timezone_set('America/Lima');

require_once 'getUbicacion.php';
require_once 'incrustarDatos.php';

include('config.php');
if (!isset($mysqli) || $mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Error de conexión a BD.']);
  exit;
}

/* ===================== Helpers ===================== */

function limpiarTexto($s, $max = 250)
{
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', ' ', $s);
  if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
  return $s;
}

function parseFloatSafe($v)
{
  if ($v === null || $v === '') return 0.0;
  return (float)$v;
}

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

function calcularSegundosSync(?string $fechaDispositivoIso): int
{
  if (empty($fechaDispositivoIso)) return 0;

  $ts = strtotime($fechaDispositivoIso);
  if ($ts === false) return 0;

  $diff = time() - $ts;
  if ($diff < 0) $diff = 0;
  if ($diff > 2592000) $diff = 2592000;
  return (int)$diff;
}

function resolverUploadBase(): string
{
  $IS_PROD = getenv('IS_DOCKER') == '1';

  if ($IS_PROD) {
    return '/app/fotos';
  }

  return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/3.CyC-GeoCampo_67/CyC-GeoCampo/fotos';
}

function asegurarDir(string $dir): bool
{
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) return false;
  }
  return is_writable($dir);
}

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

function subirFotoConIncrustacion(
  string $campo,
  string $prefix,
  string $destDir,
  array $permitidos,
  int $MAX_BYTES,
  finfo $finfo,
  float $lat,
  float $lon,
  array $lineasExtra
): ?string {

  // DEBUG UPLOAD
  if (isset($_FILES[$campo])) {
    error_log("UPLOAD ERROR: " . $_FILES[$campo]['error']);
    error_log("UPLOAD SIZE: " . $_FILES[$campo]['size']);
    error_log("UPLOAD TMP: " . $_FILES[$campo]['tmp_name']);
  }

  if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) return null;

  if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) return null;

  if ((int)$_FILES[$campo]['size'] <= 0 || (int)$_FILES[$campo]['size'] > $MAX_BYTES) return null;

  $mime = $finfo->file($_FILES[$campo]['tmp_name']);
  if (!isset($permitidos[$mime])) return null;

  $ext = $permitidos[$mime];
  $timestamp = date('YmdHis');
  $rand = bin2hex(random_bytes(4));

  $finalName = "{$prefix}_{$timestamp}_{$rand}.{$ext}";
  $destPath  = $destDir . '/' . $finalName;

  if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destPath)) return null;

  reducirImagen($destPath, 1600);

  $lineas = [];

  $geo = getUbicacionDesdeGPS($lat, $lon);
  if ($geo) {
    $lineas[] = "{$geo['provincia']}, {$geo['distrito']}, {$geo['pais']}";
    if (!empty($geo['calle'])) {
      $lineas[] = "{$geo['calle']}, {$geo['distrito']}, {$geo['provincia']}, {$geo['pais']}";
    }
  }

  $lineas[] = "Lat {$lat}  Long {$lon}";
  $lineas[] = fechaHoraBonita(time());

  incrustarDatosEnImagen($destPath, $lineas);

  return $finalName;
}

/* ===================== Leer POST ===================== */

$idpersonal = (int)$_SESSION['id'];

$documento_cliente     = limpiarTexto($_POST['dni_cliente'] ?? '', 20);
$funcionario_nombre    = limpiarTexto($_POST['funcionario_nombre'] ?? '', 150);
$funcionario_telefono  = limpiarTexto($_POST['funcionario_telefono'] ?? '', 20);

$id_unidad_negocio = !empty($_POST['unidad_negocio']) ? (int)$_POST['unidad_negocio'] : null;
$id_agencia        = !empty($_POST['agencia']) && $_POST['agencia'] !== 'OTROS' ? (int)$_POST['agencia'] : null;
$agencia_otro      = limpiarTexto($_POST['agencia_otro'] ?? '', 150);

$latitud  = parseFloatSafe($_POST['latitud'] ?? 0);
$longitud = parseFloatSafe($_POST['longitud'] ?? 0);
$accuracy = parseFloatSafe($_POST['accuracy'] ?? 0);

$fecha_creacion_dispositivo = $_POST['fecha_creacion_dispositivo'] ?? null;

/* ===================== Validaciones ===================== */

if ($documento_cliente === '') {
  echo json_encode(['success' => false, 'message' => 'Ingrese el DNI del cliente.']);
  exit;
}
if ($funcionario_nombre === '') {
  echo json_encode(['success' => false, 'message' => 'Ingrese nombres y apellidos del funcionario.']);
  exit;
}
if ($funcionario_telefono === '') {
  echo json_encode(['success' => false, 'message' => 'Ingrese el teléfono del funcionario.']);
  exit;
}
if (empty($id_unidad_negocio)) {
  echo json_encode(['success' => false, 'message' => 'Seleccione la unidad de negocio.']);
  exit;
}
if (empty($_POST['agencia'])) {
  echo json_encode(['success' => false, 'message' => 'Seleccione la agencia.']);
  exit;
}
if (($_POST['agencia'] ?? '') === 'OTROS' && $agencia_otro === '') {
  echo json_encode(['success' => false, 'message' => 'Ingrese el nombre de la agencia (Otros).']);
  exit;
}
if (!$latitud || !$longitud) {
  echo json_encode(['success' => false, 'message' => 'Debe obtener ubicación antes de guardar.']);
  exit;
}
if (empty($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
  echo json_encode(['success' => false, 'message' => 'Debe adjuntar la fotografía.']);
  exit;
}

/* ===================== Calcular estado GPS ===================== */

$segundosDiferencia = calcularSegundosSync($fecha_creacion_dispositivo);
$tiempoFormateado   = gmdate("H:i:s", $segundosDiferencia);

$estadoGPS = obtenerEstadoUbicacion($accuracy, $segundosDiferencia, $latitud, $longitud);

/* ===================== Subida de foto ===================== */

$UPLOAD_BASE = resolverUploadBase();

$carpetaCartera = 'FINANCIERA_CONFIANZA_CAMPO';
$subcarpetaTipo = 'operativo';
$fechaCarpeta   = date('Y-m-d');

$destDir = $UPLOAD_BASE . '/' . $carpetaCartera . '/' . $subcarpetaTipo . '/' . $fechaCarpeta;

error_log("[guardarOperativo] UPLOAD_BASE=$UPLOAD_BASE destDir=$destDir post_max_size=" . ini_get('post_max_size') .
  " upload_max_filesize=" . ini_get('upload_max_filesize'));

if (!asegurarDir($destDir)) {
  echo json_encode(['success' => false, 'message' => 'La carpeta de imágenes no es escribible o no se pudo crear.']);
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

$lineasExtra = [
  "Tipo: OPERATIVO",
  "DNI: " . $documento_cliente,
  "Funcionario: " . $funcionario_nombre,
  "Tel: " . $funcionario_telefono,
  "Unidad: " . (string)$id_unidad_negocio,
  "Agencia: " . (($_POST['agencia'] ?? '') === 'OTROS' ? ('OTROS - ' . $agencia_otro) : (string)$id_agencia),
  "GPS Estado: " . (string)$estadoGPS . "  Acc: " . (string)$accuracy . "m  Sync: " . $tiempoFormateado,
];

$fotoNombre = subirFotoConIncrustacion(
  'foto',
  'OP',
  $destDir,
  $permitidos,
  $MAX_BYTES,
  $finfo,
  $latitud,
  $longitud,
  $lineasExtra
);

if ($fotoNombre === null) {
  echo json_encode(['success' => false, 'message' => 'No se pudo subir la fotografía (tipo/tamaño/archivo).']);
  exit;
}

$ruta_foto = $carpetaCartera . '/' . $subcarpetaTipo . '/' . $fechaCarpeta . '/' . $fotoNombre;

/* ===================== Insert BD (prepared) ===================== */

$sql = "INSERT INTO GEOCAMPO_OPERATIVO
(
  idpersonal,
  documento_cliente,
  funcionario_nombre,
  funcionario_telefono,
  ID_UNIDAD_NEGOCIO,
  ID_AGENCIA,
  agencia_otro,
  latitud,
  longitud,
  accuracy,
  ID_GEOCAMPO_ESTADO_GPS,
  fecha_creacion_dispositivo,
  ruta_foto
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  error_log("[guardarOperativo] prepare error: " . $mysqli->error);
  echo json_encode(['success' => false, 'message' => 'Error interno al preparar inserción.']);
  exit;
}

$id_agencia_bind = $id_agencia;
$id_unidad_bind  = $id_unidad_negocio;

$fecha_disp_bind = null;
if (!empty($fecha_creacion_dispositivo)) {
  $ts = strtotime($fecha_creacion_dispositivo);
  $fecha_disp_bind = $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
}

error_log("Cantidad ? en SQL: " . substr_count($sql, '?'));
error_log("Cantidad tipos: " . strlen("isssiisdddiss"));

$stmt->bind_param(
  "isssiisdddiss",
  $idpersonal,
  $documento_cliente,
  $funcionario_nombre,
  $funcionario_telefono,
  $id_unidad_bind,
  $id_agencia_bind,
  $agencia_otro,
  $latitud,
  $longitud,
  $accuracy,
  $estadoGPS,
  $fecha_disp_bind,
  $ruta_foto
);

$ok = $stmt->execute();
if (!$ok) {
  error_log("[guardarOperativo] execute error: " . $stmt->error);
  echo json_encode(['success' => false, 'message' => 'Error al insertar registro: ' . $stmt->error]);
  $stmt->close();
  $mysqli->close();
  exit;
}

$idInsert = $stmt->insert_id;

$stmt->close();
$mysqli->close();

echo json_encode([
  'success' => true,
  'message' => 'Registro Operativo ingresado correctamente.',
  'id' => $idInsert,
  'ruta_foto' => $ruta_foto,
  'accuracy_gps' => $accuracy,
  'tiempo_sincronizacion' => $tiempoFormateado,
  'estado_gps' => $estadoGPS
]);
exit;
