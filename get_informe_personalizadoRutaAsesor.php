<?php
session_start();
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $mysqli->prepare(
  "CALL SP_GEOCAMPO_PRIMERA_ULTIMA_GESTION(?, ?, ?, ?)"
);
$stmt->bind_param(
  "ssss",
  $data['cartera'],
  $data['fecha1'],
  $data['fecha2'],
  $data['asesor']
);

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}

$_SESSION['informe_imagenes'] = $rows;

echo json_encode(['rows' => $rows]);
