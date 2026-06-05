<?php
header('Content-Type: application/json');

// Ruta de la imagen
$imagePath = "C:/Users/USER/Desktop/DISCO D/Imagen19569447_20240207113516.jpg";

// Verifica si el archivo existe
if (!file_exists($imagePath)) {
    echo json_encode(["error" => "La imagen no se encuentra en la ruta especificada."]);
    exit;
}

// Lee los metadatos EXIF de la imagen
$exifData = exif_read_data($imagePath, 0, true);
$response = [];

// Función para convertir coordenadas GPS a decimal
function gpsToDecimal($coordinate, $hemisphere) {
    $degrees = count($coordinate) > 0 ? gpsToFloat($coordinate[0]) : 0;
    $minutes = count($coordinate) > 1 ? gpsToFloat($coordinate[1]) : 0;
    $seconds = count($coordinate) > 2 ? gpsToFloat($coordinate[2]) : 0;
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    return ($hemisphere == 'S' || $hemisphere == 'W') ? -$decimal : $decimal;
}

// Convierte fracciones GPS a flotante
function gpsToFloat($coordPart) {
    $parts = explode('/', $coordPart);
    if (count($parts) <= 1) {
        return $parts[0];
    }
    return floatval($parts[0]) / floatval($parts[1]);
}

// Extrae información de GPS
if (isset($exifData['GPS'])) {
    $gps = $exifData['GPS'];
    $response['latitude'] = gpsToDecimal($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
    $response['longitude'] = gpsToDecimal($gps['GPSLongitude'], $gps['GPSLongitudeRef']);
} else {
    $response['latitude'] = null;
    $response['longitude'] = null;
}

// Extrae la fecha y hora de captura
if (isset($exifData['IFD0']['DateTimeOriginal'])) {
    $response['datetime'] = $exifData['IFD0']['DateTimeOriginal'];
} else {
    $response['datetime'] = null;
}

// Devuelve la respuesta como JSON
echo json_encode($response);
