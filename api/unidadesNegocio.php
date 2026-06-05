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
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida o expirada.'
    ]);
    exit;
}

date_default_timezone_set('America/Lima');

include('../config.php');

if (!isset($mysqli) || $mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a BD.'
    ]);
    exit;
}

$idPersonal = (int)$_SESSION['id'];

$IS_PROD = getenv('IS_DOCKER') == '1';

try {

    if ($IS_PROD) {

        $sql = "
            SELECT
                u.ID_UNIDAD_NEGOCIO AS id,
                u.NOMBRE_UNIDAD_NEGOCIO AS nombre
            FROM UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
            INNER JOIN PERSONAL_UNIDAD_CONFIANZA pu
                ON pu.ID_UNIDAD_NEGOCIO = u.ID_UNIDAD_NEGOCIO
            WHERE pu.ID_PERSONAL = ?
            AND pu.ESTADO = 1
            AND u.ESTADO = 1
            AND u.ID_UNIDAD_NEGOCIO = (
                SELECT MAX(u2.ID_UNIDAD_NEGOCIO)
                FROM UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u2
                WHERE u2.NOMBRE_UNIDAD_NEGOCIO = u.NOMBRE_UNIDAD_NEGOCIO
                    AND u2.ESTADO = 1
            )
            ORDER BY u.NOMBRE_UNIDAD_NEGOCIO;
        ";
    } else {

        $sql = "
        SELECT
            u.ID_UNIDAD_NEGOCIO AS id,
            u.NOMBRE_UNIDAD_NEGOCIO AS nombre
        FROM UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
        INNER JOIN PERSONAL_UNIDAD_CONFIANZA pu
            ON pu.ID_UNIDAD_NEGOCIO = u.ID_UNIDAD_NEGOCIO
            AND pu.PERIODO = u.PERIODO
        WHERE pu.ID_PERSONAL = ?
        AND u.PERIODO = (
            SELECT MAX(PERIODO)
            FROM UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO
        )
        ORDER BY u.NOMBRE_UNIDAD_NEGOCIO
        ";
    }

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando consulta");
    }

    $stmt->bind_param("i", $idPersonal);
    $stmt->execute();

    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode([
        "success" => true,
        "rows" => $rows
    ]);

    $stmt->close();
    $mysqli->close();
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error consultando unidades"
    ]);
}
