<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

/* ======================================================
   VALIDACIÓN DE PARÁMETROS
====================================================== */
if (
  empty($_GET['carpeta']) ||
  empty($_GET['imagenes']) ||
  !is_array($_GET['imagenes'])
) {
  http_response_code(400);
  exit('Parámetros incompletos');
}

$carpeta  = basename($_GET['carpeta']);
$imagenes = $_GET['imagenes'];

/* ======================================================
   RUTAS
====================================================== */
$basePath = __DIR__ . "/fotos/$carpeta";
$zipPath  = sys_get_temp_dir() . '/imagenes_' . time() . '.zip';

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  http_response_code(500);
  exit('No se pudo crear el ZIP');
}

$agregadas = 0;

/* ======================================================
   AGREGAR IMÁGENES
====================================================== */
foreach ($imagenes as $img) {

  $img = basename($img);

  $filePath = "$basePath/$img";

  if (file_exists($filePath)) {

    $zip->addFile($filePath, $img);
    $agregadas++;
    continue;
  }

  $encontrada = false;

  $carpetasFecha = glob($basePath . '/*', GLOB_ONLYDIR);

  foreach ($carpetasFecha as $dir) {

    $filePath = $dir . '/' . $img;

    if (file_exists($filePath)) {

      $zip->addFile($filePath, $img);
      $agregadas++;
      $encontrada = true;
      break;
    }
  }

  if (!$encontrada) {
    error_log("[ZIP] No encontrada: $img");
  }
}

$zip->close();

/* ======================================================
   VALIDACIÓN FINAL
====================================================== */
if ($agregadas === 0) {

  if (file_exists($zipPath)) {
    unlink($zipPath);
  }

  http_response_code(404);
  exit('No se encontraron imágenes válidas');
}

/* ======================================================
   DESCARGA
====================================================== */
while (ob_get_level()) {
  ob_end_clean();
}

/* ======================================================
   NOMBRE DE ARCHIVO DESCRIPTIVO
====================================================== */

$nombreCartera = $carpeta;

$nombreCartera = preg_replace('/[^A-Za-z0-9 ]/', '', $nombreCartera);
$nombreCartera = trim(preg_replace('/\s+/', '_', $nombreCartera));

$cantidad = $agregadas;

$timestamp = date('His');

$nombreZip = "Imagenes_Seleccionadas_{$nombreCartera}_{$cantidad}imgs_{$timestamp}.zip";

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombreZip . '"');
header('Content-Length: ' . filesize($zipPath));

readfile($zipPath);
unlink($zipPath);
exit;
