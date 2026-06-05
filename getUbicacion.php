<?php

/**
 * Obtiene dirección aproximada a partir de latitud y longitud
 * usando Nominatim (OpenStreetMap)
 */
function getUbicacionDesdeGPS($lat, $lon)
{
  if (empty($lat) || empty($lon)) {
    return null;
  }

  $lat = urlencode($lat);
  $lon = urlencode($lon);

  $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=18&addressdetails=1";

  $opts = [
    "http" => [
      "header" => "User-Agent: GeoCampo/1.0\r\n"
    ]
  ];

  $context = stream_context_create($opts);

  $json = @file_get_contents($url, false, $context);
  if ($json === false) {
    error_log("[getUbicacionDesdeGPS] Error al llamar Nominatim");
    return null;
  }

  $data = json_decode($json, true);
  if (!isset($data['address'])) {
    return null;
  }

  $addr = $data['address'];

  return [
    'distrito'  => $addr['suburb']
      ?? $addr['city_district']
      ?? $addr['neighbourhood']
      ?? '',
    'provincia' => $addr['city']
      ?? $addr['county']
      ?? '',
    'calle'     => trim(($addr['road'] ?? '') . ' ' . ($addr['house_number'] ?? '')),
    'pais'      => $addr['country'] ?? ''
  ];
}
