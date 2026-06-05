<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_domain', '');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['id'])) {
  die("Sesión no válida");
}

date_default_timezone_set('America/Lima');

include('../config.php');

if (!isset($mysqli) || $mysqli->connect_error) {
  die("Error conexión BD");
}

$IS_PROD = getenv('IS_DOCKER') == '1';

/*
|--------------------------------------------------------------------------
| URL base de fotos
|--------------------------------------------------------------------------
*/

$baseURL = $IS_PROD
  ? "https://geocampo.online/fotos/"
  : "http://localhost/fotos/";

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin    = $_GET['fecha_fin'] ?? '';
$unidad      = isset($_GET['unidad']) ? (int)$_GET['unidad'] : 0;

/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
SELECT 

    DATE_FORMAT(v.fecha_registro_bd,'%Y-%m-%d %H:%i:%s') AS fecha,

    v.funcionario_nombre AS funcionario,
    v.funcionario_telefono,

    CONCAT(pg.NOMBRES,' ',pg.APELLIDOS) AS gestor,

    u.NOMBRE_UNIDAD_NEGOCIO AS unidad,

    COALESCE(
        CONCAT(s.NOMBRE_AGENCIA,' (',IFNULL(s.DISTRITO,''),')'),
        v.agencia_otro
    ) AS agencia,

    eg.NOMBRE_GEOCAMPO_ESTADO_GPS AS estado_gps,

    v.latitud,
    v.longitud,
    v.accuracy,

    CONCAT('$baseURL', v.ruta_foto) AS url_foto

FROM GEOCAMPO_VISITA_OFICINA v

LEFT JOIN GEOCAMPO_ESTADO_GPS eg
    ON eg.ID_GEOCAMPO_ESTADO_GPS = v.ID_GEOCAMPO_ESTADO_GPS

LEFT JOIN personal pg
    ON pg.IDPERSONAL = v.idpersonal

LEFT JOIN UNIDADES_DE_NEGOCIO_CONFIANZA_CAMPO u
    ON u.ID_UNIDAD_NEGOCIO = v.ID_UNIDAD_NEGOCIO

LEFT JOIN SUCURSALES_CONFIANZA_CAMPO s
    ON s.ID_AGENCIA = v.ID_AGENCIA

WHERE 1=1
";

/*
|--------------------------------------------------------------------------
| Aplicar filtros
|--------------------------------------------------------------------------
*/

if (!empty($fechaInicio)) {
  $sql .= " AND DATE(v.fecha_registro_bd) >= '" . $mysqli->real_escape_string($fechaInicio) . "'";
}

if (!empty($fechaFin)) {
  $sql .= " AND DATE(v.fecha_registro_bd) <= '" . $mysqli->real_escape_string($fechaFin) . "'";
}

if ($unidad > 0) {
  $sql .= " AND v.ID_UNIDAD_NEGOCIO = $unidad";
}

$sql .= " ORDER BY v.fecha_registro_bd DESC";

/*
|--------------------------------------------------------------------------
| Ejecutar query
|--------------------------------------------------------------------------
*/

$result = $mysqli->query($sql);

/*
|--------------------------------------------------------------------------
| Headers Excel
|--------------------------------------------------------------------------
*/

$filename = "visitas_geocampo_" . date("Ymd_His") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

/*
|--------------------------------------------------------------------------
| Tabla
|--------------------------------------------------------------------------
*/

echo "<table border='1'>";

echo "<tr>
<th>Fecha</th>
<th>Funcionario</th>
<th>Telefono</th>
<th>Gestor</th>
<th>Unidad</th>
<th>Agencia</th>
<th>Estado GPS</th>
<th>Latitud</th>
<th>Longitud</th>
<th>Accuracy</th>
<th>Foto</th>
</tr>";

while ($row = $result->fetch_assoc()) {

  $url = $row['url_foto'];

  echo "<tr>
    <td>{$row['fecha']}</td>
    <td>{$row['funcionario']}</td>
    <td>{$row['funcionario_telefono']}</td>
    <td>{$row['gestor']}</td>
    <td>{$row['unidad']}</td>
    <td>{$row['agencia']}</td>
    <td>{$row['estado_gps']}</td>
    <td>{$row['latitud']}</td>
    <td>{$row['longitud']}</td>
    <td>{$row['accuracy']}</td>
    <td>=HYPERLINK(\"$url\",\"Ver Foto\")</td>
    </tr>";
}

echo "</table>";

exit;
