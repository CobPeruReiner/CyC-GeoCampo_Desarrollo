<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('geocampo');
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Tu sesión ha finalizado. Ingresa nuevamente.']);
    exit;
}

$fecha = trim((string) ($_GET['fecha'] ?? ''));
$fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Selecciona una fecha válida.']);
    exit;
}

require_once 'config.php';

$idPersonal = (int) $_SESSION['id'];
$sql = "
    SELECT
        g.ID AS id,
        g.FECHA AS fecha,
        g.IDENTIFICADOR AS identificador,
        e.EFECTO AS efecto,
        m.MOTIVO AS motivo,
        g.OBSERVACION AS observacion,
        d.DIRECCION_DEPURADA AS direccion,
        g.NOMCONTACTO AS contacto,
        g.PISOS AS pisos,
        g.PUERTA AS puerta,
        g.FACHADA AS fachada,
        g.FECHA_PROMESA AS fecha_promesa,
        g.MONTO_PROMESA AS monto_promesa,
        g.latitud,
        g.longitud,
        g.txt AS gps_estado,
        g.imagen1,
        g.imagen2,
        g.imagen3
    FROM GEOCAMPO g
    LEFT JOIN efecto e ON e.IDEFECTO = g.IDEFECTO
    LEFT JOIN motivo m ON m.IDMOTIVO = g.IDMOTIVO
    LEFT JOIN direcciones d ON d.IDDIRECCION = g.IDDIRECCION
    WHERE g.IDPERSONAL = ?
      AND g.FECHA >= ?
      AND g.FECHA < DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY g.ID DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('iss', $idPersonal, $fecha, $fecha);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as &$row) {
    $row['latitud'] = (float) $row['latitud'];
    $row['longitud'] = (float) $row['longitud'];
    $row['gps_valido'] = $row['latitud'] !== 0.0 && $row['longitud'] !== 0.0;
    $row['evidencias'] = count(array_filter([$row['imagen1'], $row['imagen2'], $row['imagen3']]));
    unset($row['imagen1'], $row['imagen2'], $row['imagen3']);
}
unset($row);

echo json_encode(['ok' => true, 'fecha' => $fecha, 'gestiones' => $rows], JSON_UNESCAPED_UNICODE);
