<?php

declare(strict_types=1);

session_start();
require_once 'config.php';

// Limpiar el token en BD si existe una sesión activa
if (isset($_SESSION['id'])) {
  $stmt = $mysqli->prepare("UPDATE personal SET api_token = NULL WHERE IDPERSONAL = ?");
  $stmt->bind_param('i', $_SESSION['id']);
  $stmt->execute();
  $stmt->close();
}

// Destruir la sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
}
session_destroy();

// Redirigir
header('Location: index.php');
exit;
