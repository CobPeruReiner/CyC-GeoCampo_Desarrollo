<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('geocampo');
session_start();

if (empty($_SESSION['id'])) {
    http_response_code(401);
    exit('Sesión no válida.');
}

$idGestion = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$slot = filter_var($_GET['slot'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
if (!$idGestion || !$slot) {
    http_response_code(422);
    exit('Evidencia no válida.');
}

require_once 'config.php';

$idPersonal = (int) $_SESSION['id'];
$campoImagen = "imagen{$slot}";
$sql = "
    SELECT g.FECHA AS fecha, g.IDCARTERA AS id_cartera, g.{$campoImagen} AS archivo,
           COALESCE(NULLIF(g.NOMTABLE, ''), tl.nombre) AS tabla
    FROM GEOCAMPO g
    LEFT JOIN tabla_log tl ON tl.id_cartera = g.IDCARTERA AND tl.estado = 0
    WHERE g.ID = ? AND g.IDPERSONAL = ?
    ORDER BY tl.id DESC
    LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $idGestion, $idPersonal);
$stmt->execute();
$evidencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

$archivo = basename((string) ($evidencia['archivo'] ?? ''));
if (!$evidencia || $archivo === '' || $archivo !== $evidencia['archivo']) {
    http_response_code(404);
    exit('Evidencia no disponible.');
}

$tabla = (string) ($evidencia['tabla'] ?? '');
$carpetaCartera = preg_replace('/^C_/', '', $tabla);
if (!preg_match('/^[A-Za-z0-9_]+$/', $carpetaCartera)) {
    http_response_code(404);
    exit('No se encontró la carpeta de evidencias.');
}

$fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $evidencia['fecha']);
if (!$fecha) {
    http_response_code(404);
    exit('La fecha de la evidencia no es válida.');
}

function directorios_evidencia_ruta_asesor2(): array
{
    $raizConfigurada = trim((string) getenv('GEOCAMPO_EVIDENCE_DIR'));
    $directorios = [];
    if ($raizConfigurada !== '') {
        $directorios[] = $raizConfigurada;
    }
    // Rutas relativas para despliegues donde frontend y backend comparten proyecto.
    $directorios[] = __DIR__ . DIRECTORY_SEPARATOR . 'fotos';
    $directorios[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fotos';
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $directorios[] = rtrim((string) $_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fotos';
    }
    $directorios[] = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
    $directorios[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
    // Compatibilidad únicamente para el entorno local de desarrollo de Windows.
    $perfil = getenv('USERPROFILE');
    if ($perfil) {
        $directorios[] = $perfil . DIRECTORY_SEPARATOR . 'Desktop' . DIRECTORY_SEPARATOR . '9.AppMovilCampo' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
    }
    return array_values(array_unique($directorios));
}

$rutaArchivo = null;
foreach (directorios_evidencia_ruta_asesor2() as $raiz) {
    $candidata = $raiz . DIRECTORY_SEPARATOR . $carpetaCartera . DIRECTORY_SEPARATOR . $fecha->format('Y-m-d') . DIRECTORY_SEPARATOR . $archivo;
    if (is_file($candidata)) {
        $rutaArchivo = $candidata;
        break;
    }
}

if (!$rutaArchivo) {
    http_response_code(404);
    exit('No se encontró el archivo de evidencia.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($rutaArchivo) ?: 'image/jpeg';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(415);
    exit('Formato de evidencia no compatible.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($rutaArchivo));
header('Cache-Control: private, max-age=3600');
readfile($rutaArchivo);
