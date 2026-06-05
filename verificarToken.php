<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Lima');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

header('Content-Type: application/json; charset=utf-8');

include('config.php');

$id = $_SESSION['id'] ?? null;
$token = $_SESSION['token'] ?? null;

if (!$id || !$token) {
  echo json_encode(['valid' => false]);
  exit;
}

$stmt = $mysqli->prepare("SELECT api_token FROM personal WHERE IDPERSONAL = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($tokenBD);
$stmt->fetch();
$stmt->close();

if ($tokenBD !== $token) {
  session_destroy();
  echo json_encode(['valid' => false]);
} else {
  echo json_encode(['valid' => true]);
}
