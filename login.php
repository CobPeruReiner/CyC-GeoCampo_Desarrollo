<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Lima');

// Medición de tiempo
$inicio_login = microtime(true);

// Configuración de sesión
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_domain', '');

if (function_exists('ob_start')) {
    ob_start();
}

session_name('geocampo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'horario.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (function_exists('ob_get_length') && ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario    = $_POST['usuario'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

$id = null;
$user = '';
$cargo = null;
$pass = '';
$nombres = '';
$apellidos = '';
$doc = '';
$cartera = '';
$tipoPersonal = '';
$idEstado = null;

// --- Autenticación ---
$stmt = $mysqli->prepare("
    SELECT IDPERSONAL, USUARIO, CARGO, PASSWORD, NOMBRES, APELLIDOS, DOC, id_cartera, TIPO_PERSONAL, IDESTADO
    FROM personal
    WHERE USUARIO = ?
");

if (!$stmt) {
    error_log('Fallo prepare login: ' . $mysqli->error);
    if (function_exists('ob_get_length') && ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error de servidor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('s', $usuario);

$stmt->execute();

$stmt->bind_result(
    $id,
    $user,
    $cargo,
    $pass,
    $nombres,
    $apellidos,
    $doc,
    $cartera,
    $tipoPersonal,
    $idEstado
);

$stmt->fetch();
$stmt->close();

if ($id) {

    // --- Validar password (bcrypt o md5) ---
    $passwordValido = false;

    if (strpos($pass, '$2') === 0) {
        // bcrypt
        $passwordValido = password_verify($contrasena, $pass);
    } else {
        // md5
        $passwordValido = hash_equals($pass, md5($contrasena));
    }

    if (!$passwordValido) {
        if (function_exists('ob_get_length') && ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idsPermitidos = [1235, 1391, 25, 1390, 647, 17, 1706];

    if (!validarHorario() && !in_array((int)$id, $idsPermitidos, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Acceso bloqueado, fuera de horario.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array((int)$idEstado, [1, 2], true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario inactivo'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Token
    $nuevoToken = bin2hex(random_bytes(16));

    $stmtUpdate = $mysqli->prepare("UPDATE personal SET api_token = ? WHERE IDPERSONAL = ?");
    if ($stmtUpdate) {
        $stmtUpdate->bind_param('si', $nuevoToken, $id);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    // Sesión
    $_SESSION['id'] = $id;
    $_SESSION['nombreCompleto'] = "$apellidos, $nombres";
    $_SESSION['doc'] = $doc;
    $_SESSION['cartera'] = $cartera;
    $_SESSION['cargo'] = $cargo;
    $_SESSION['tipo_personal'] = $tipoPersonal;
    $_SESSION['idEstado'] = (int)$idEstado;
    $_SESSION['token'] = $nuevoToken;

    session_write_close();

    if (function_exists('ob_get_length') && ob_get_length()) ob_clean();

    echo json_encode([
        'success'  => true,
        'idEstado' => (int)$idEstado
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Credenciales inválidas
if (function_exists('ob_get_length') && ob_get_length()) ob_clean();

echo json_encode([
    'success' => false,
    'message' => 'Credenciales incorrectas'
], JSON_UNESCAPED_UNICODE);

exit;
