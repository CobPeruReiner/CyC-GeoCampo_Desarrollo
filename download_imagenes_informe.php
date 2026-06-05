<?php
session_start();

/* ======================================================
   1️⃣ VALIDACIÓN DE SESIÓN
====================================================== */
if (empty($_SESSION['informe_imagenes'])) {
  http_response_code(400);
  exit('No hay datos para descargar');
}

$rows = $_SESSION['informe_imagenes'];

/* ======================================================
   2️⃣ MAPEO DE CARTERAS → CARPETAS EN DISCO
====================================================== */
$carpetas = [
  59 => 'FINANCIERA_CONFIANZA_CAMPO',
  // futuras carteras aquí
];

/* ======================================================
   3️⃣ CONFIGURACIÓN DE RUTAS
====================================================== */
$basePath = '/app/fotos'; // volumen montado en Docker
$zipPath  = sys_get_temp_dir() . '/informe_imagenes_' . time() . '.zip';

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  http_response_code(500);
  exit('No se pudo crear el ZIP');
}

$archivosAgregados = 0;

/* ======================================================
   4️⃣ AGRUPAR POR FECHA + ASESOR
====================================================== */
$grupos = [];

foreach ($rows as $row) {

  if (empty($row['FECHA']) || empty($row['NOMBRE_PERSONAL'])) {
    continue;
  }

  $fecha  = substr($row['FECHA'], 0, 10);
  $asesor = preg_replace('/[^A-Za-z0-9 ]/', '', $row['NOMBRE_PERSONAL']);

  $key = $fecha . '|' . $asesor;

  if (!isset($grupos[$key])) {
    $grupos[$key] = [
      'fecha' => $fecha,
      'asesor' => $asesor,
      'identificadores' => [],
      'rows' => []
    ];
  }

  $grupos[$key]['identificadores'][] = $row['IDENTIFICADOR'] ?? '';
  $grupos[$key]['rows'][] = $row;
}

/* ======================================================
   5️⃣ CREAR ZIP USANDO addFile (SIN CARGAR EN MEMORIA)
====================================================== */
foreach ($grupos as $grupo) {

  $fecha  = $grupo['fecha'];
  $asesor = $grupo['asesor'];

  // IDs únicos y ordenados
  $ids = array_filter(array_unique($grupo['identificadores']));
  sort($ids);

  $nombreCarpeta = $asesor . '_' . implode('_', $ids);

  // Determinar primera y última gestión
  $fechasGestiones = array_column($grupo['rows'], 'FECHA');
  sort($fechasGestiones);

  $primeraFecha = $fechasGestiones[0];
  $ultimaFecha  = end($fechasGestiones);

  foreach ($grupo['rows'] as $row) {

    $idCartera = (int)($row['IDCARTERA'] ?? 0);
    if (!isset($carpetas[$idCartera])) continue;

    $fechaHora = date('Ymd_His', strtotime($row['FECHA']));

    $tipo = '';
    if ($row['FECHA'] === $primeraFecha) $tipo = 'PRIMERA';
    if ($row['FECHA'] === $ultimaFecha)  $tipo = 'ULTIMA';

    $contadorImagen = 1;

    foreach (['imagen1', 'imagen2', 'imagen3'] as $img) {

      if (empty($row[$img])) continue;

      $carpetaCartera = $basePath . '/' . $carpetas[$idCartera];
      $fechaCarpeta   = substr($row['FECHA'], 0, 10);

      $filePath = $carpetaCartera . '/' . $fechaCarpeta . '/' . $row[$img];

      if (!file_exists($filePath)) {

        $filePath = $carpetaCartera . '/' . $row[$img];

        if (!file_exists($filePath)) {
          error_log("[ZIP] Archivo no existe: $row[$img]");
          continue;
        }
      }

      $extension = pathinfo($row[$img], PATHINFO_EXTENSION);

      $nombreArchivo = $fechaHora . '_' . $tipo . '_' . $contadorImagen . '.' . $extension;

      $zip->addFile(
        $filePath,
        "$fecha/$nombreCarpeta/$nombreArchivo"
      );

      $contadorImagen++;
      $archivosAgregados++;
    }
  }
}

$zip->close();

/* ======================================================
   6️⃣ VALIDACIONES FINALES
====================================================== */
if ($archivosAgregados === 0) {
  if (file_exists($zipPath)) {
    unlink($zipPath);
  }
  http_response_code(404);
  exit('No se encontraron imágenes para descargar');
}

// Limpiar buffers
while (ob_get_level()) {
  ob_end_clean();
}

/* ======================================================
   7️⃣ NOMBRE DE ZIP DESCRIPTIVO
====================================================== */

// Nombre de cartera
$nombreCartera = 'CARTERA';

if (!empty($rows[0]['CARTERA'])) {
  $nombreCartera = $rows[0]['CARTERA'];
}

$nombreCartera = preg_replace('/[^A-Za-z0-9 ]/', '', $nombreCartera);
$nombreCartera = trim(preg_replace('/\s+/', '_', $nombreCartera));

// Rango real de fechas del informe
$fechas = array_column($rows, 'FECHA');
sort($fechas);

$fechaDesde = substr($fechas[0], 0, 10);
$fechaHasta = substr(end($fechas), 0, 10);

// Timestamp corto para evitar (1), (2)
$timestamp = date('His');

$nombreZip = "Informe_{$nombreCartera}_{$fechaDesde}_al_{$fechaHasta}_{$timestamp}.zip";

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombreZip . '"');
header('Content-Length: ' . filesize($zipPath));

readfile($zipPath);
unlink($zipPath);
exit;
