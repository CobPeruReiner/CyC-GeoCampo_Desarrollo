<?php

declare(strict_types=1);

session_name('geocampo');
session_start();

/* ======================================================
   1️⃣ VALIDAR DATA
====================================================== */
if (empty($_SESSION['informe_imagenes'])) {
  http_response_code(400);
  exit('No hay datos para descargar');
}

$rows = $_SESSION['informe_imagenes'];

require_once 'config.php';

/* ======================================================
   2️⃣ MAPEO CARTERA → CARPETA FÍSICA
====================================================== */
$carpetas = [
  59 => 'FINANCIERA_CONFIANZA_CAMPO',
];

/* ======================================================
   3️⃣ RESOLVER NOMBRE DEL ASESOR (OPCIÓN 1)
====================================================== */
$mapaAsesores = [];

$res = $mysqli->query("
  SELECT
      tb15.IDPERSONAL,
      CONCAT(TRIM(tb15.APELLIDOS), ', ', tb15.NOMBRES) AS NOMBRE
  FROM personal tb15
");

while ($r = $res->fetch_assoc()) {
  $mapaAsesores[$r['IDPERSONAL']] = $r['NOMBRE'];
}

// Inyectar nombre al dataset
foreach ($rows as &$row) {
  $id = $row['IDPERSONAL'] ?? null;
  $row['NOMBRE_PERSONAL'] = $mapaAsesores[$id] ?? 'SIN_ASESOR';
}
unset($row);

/* ======================================================
   4️⃣ RANGO DE FECHAS
====================================================== */
$fechas = array_column($rows, 'FECHA');
sort($fechas);

$fechaInicio = substr($fechas[0], 0, 10);
$fechaFin    = substr(end($fechas), 0, 10);

$carpetaRango = "{$fechaInicio}_a_{$fechaFin}";

/* ======================================================
   5️⃣ CONFIGURACIÓN ZIP
====================================================== */
$basePath = '/app/fotos'; // ruta real en tu servidor / docker
$tmpZip   = sys_get_temp_dir() . '/fotos_' . uniqid() . '.zip';

$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  http_response_code(500);
  exit('No se pudo crear el ZIP');
}

$archivosAgregados = 0;

/* ======================================================
   6️⃣ AGREGAR IMÁGENES (SIN RENOMBRAR)
====================================================== */
foreach ($rows as $row) {

  $idCartera = (int)($row['IDCARTERA'] ?? 0);
  if (!isset($carpetas[$idCartera])) continue;

  $asesor = preg_replace(
    '/[^A-Za-z0-9 ]/',
    '',
    $row['NOMBRE_PERSONAL']
  );

  foreach (['imagen1', 'imagen2', 'imagen3'] as $img) {

    if (empty($row[$img])) continue;

    $filePath = $basePath . '/' . $carpetas[$idCartera] . '/' . $row[$img];
    if (!file_exists($filePath)) continue;

    $zip->addFile(
      $filePath,
      "$carpetaRango/$asesor/{$row[$img]}"
    );

    $archivosAgregados++;
  }
}

$zip->close();

/* ======================================================
   7️⃣ VALIDACIONES FINALES
====================================================== */
if ($archivosAgregados === 0) {
  unlink($tmpZip);
  http_response_code(404);
  exit('No se encontraron imágenes');
}

/* ======================================================
   8️⃣ NOMBRE FINAL DEL ZIP (ANTI (1)(2))
====================================================== */
$nombreZip = sprintf(
  'Fotos_%s_%s_a_%s_%s.zip',
  $carpetas[$rows[0]['IDCARTERA']],
  $fechaInicio,
  $fechaFin,
  date('Ymd_His')
);

/* ======================================================
   9️⃣ DESCARGA
====================================================== */
while (ob_get_level()) {
  ob_end_clean();
}

header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"$nombreZip\"");
header('Content-Length: ' . filesize($tmpZip));

readfile($tmpZip);
unlink($tmpZip);
exit;
