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

$idGestion = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$idGestion) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'La gestión solicitada no es válida.']);
    exit;
}

require_once 'config.php';

$idPersonal = (int) $_SESSION['id'];
$sql = "
    SELECT
        g.ID AS id, g.FECHA AS fecha, g.IDENTIFICADOR AS identificador,
        e.EFECTO AS efecto, m.MOTIVO AS motivo, g.OBSERVACION AS observacion,
        d.DIRECCION_DEPURADA AS direccion, g.NOMCONTACTO AS contacto,
        g.PISOS AS pisos, g.PUERTA AS puerta, g.FACHADA AS fachada,
        g.FECHA_PROMESA AS fecha_promesa, g.MONTO_PROMESA AS monto_promesa,
        g.latitud, g.longitud, g.imagen1, g.imagen2, g.imagen3
    FROM GEOCAMPO g
    LEFT JOIN efecto e ON e.IDEFECTO = g.IDEFECTO
    LEFT JOIN motivo m ON m.IDMOTIVO = g.IDMOTIVO
    LEFT JOIN direcciones d ON d.IDDIRECCION = g.IDDIRECCION
    WHERE g.ID = ? AND g.IDPERSONAL = ?
    LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $idGestion, $idPersonal);
$stmt->execute();
$gestion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gestion) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'No encontramos esa gestión en tu historial.']);
    exit;
}

$evidencias = [];
foreach ([1, 2, 3] as $slot) {
    if (!empty($gestion["imagen{$slot}"])) {
        $evidencias[] = [
            'slot' => $slot,
            'url' => 'ruta_asesor2_evidencia.php?id=' . rawurlencode((string) $idGestion) . '&slot=' . $slot,
        ];
    }
}
unset($gestion['imagen1'], $gestion['imagen2'], $gestion['imagen3']);
$gestion['latitud'] = (float) $gestion['latitud'];
$gestion['longitud'] = (float) $gestion['longitud'];

echo json_encode(['ok' => true, 'gestion' => $gestion, 'evidencias' => $evidencias], JSON_UNESCAPED_UNICODE);
