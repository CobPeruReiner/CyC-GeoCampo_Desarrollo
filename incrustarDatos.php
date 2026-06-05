<?php

function fechaHoraBonita($timestamp)
{
  $dias = [
    'Sunday'    => 'Domingo',
    'Monday'    => 'Lunes',
    'Tuesday'   => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday'  => 'Jueves',
    'Friday'    => 'Viernes',
    'Saturday'  => 'Sábado'
  ];

  $diaIngles = date('l', $timestamp);
  $diaEsp   = $dias[$diaIngles] ?? $diaIngles;

  $fecha = date('d/m/Y', $timestamp);
  $hora  = date('h:i a', $timestamp);

  return "{$diaEsp}, {$fecha} {$hora}";
}

function incrustarDatosEnImagen($rutaImagen, array $lineasTexto)
{
  $info = getimagesize($rutaImagen);
  if ($info === false) return;

  switch ($info['mime']) {
    case 'image/jpeg':
      $img = imagecreatefromjpeg($rutaImagen);
      break;
    case 'image/png':
      $img = imagecreatefrompng($rutaImagen);
      break;
    default:
      return;
  }

  $ancho = imagesx($img);
  $alto  = imagesy($img);

  // ===== CONFIG =====
  $padding         = 16;
  $lineGap         = 8;
  $fontSizeTitulo  = 16;
  $fontSizeTexto   = 12;
  $radius          = 12;
  $fontFile        = __DIR__ . '/fonts/DejaVuSans.ttf';

  if (!file_exists($fontFile)) {
    imagedestroy($img);
    return;
  }

  // ===== Medir ancho real del texto =====
  $maxTextWidth = 0;
  $lineHeights  = [];

  foreach ($lineasTexto as $i => $linea) {

    $size = ($i === 0) ? $fontSizeTitulo : $fontSizeTexto;

    $bbox = imagettfbbox($size, 0, $fontFile, $linea);

    $width  = abs($bbox[2] - $bbox[0]);
    $height = abs($bbox[7] - $bbox[1]);

    $maxTextWidth = max($maxTextWidth, $width);
    $lineHeights[] = $height;
  }

  // ===== Dimensiones del cuadro =====
  $boxWidth  = $maxTextWidth + ($padding * 2);
  $boxWidth  = max(intval($ancho * 0.40), min($boxWidth, intval($ancho * 0.85)));

  $boxHeight = array_sum($lineHeights)
    + ($lineGap * (count($lineHeights) - 1))
    + ($padding * 2);

  // ===== Posición (derecha) =====
  $x1 = $ancho - $boxWidth - $padding;
  $y1 = $alto  - $boxHeight - $padding;
  $x2 = $x1 + $boxWidth;
  $y2 = $y1 + $boxHeight;

  $fondo  = imagecolorallocatealpha($img, 0, 0, 0, 70);
  $blanco = imagecolorallocate($img, 255, 255, 255);

  // ===== Rectángulo redondeado =====
  imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $fondo);
  imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $fondo);

  imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $fondo);
  imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $fondo);
  imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $fondo);
  imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $fondo);

  // ===== Texto =====
  $y = $y1 + $padding;

  foreach ($lineasTexto as $i => $linea) {

    $size = ($i === 0) ? $fontSizeTitulo : $fontSizeTexto;

    $y += $lineHeights[$i];

    imagettftext(
      $img,
      $size,
      0,
      $x1 + $padding,
      $y,
      $blanco,
      $fontFile,
      $linea
    );

    $y += $lineGap;
  }

  // ===== Guardar =====
  if ($info['mime'] === 'image/jpeg') {
    imagejpeg($img, $rutaImagen, 90);
  } else {
    imagepng($img, $rutaImagen);
  }

  imagedestroy($img);
}
