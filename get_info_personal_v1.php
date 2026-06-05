<?php
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ⏱ INICIO DEL CRONÓMETRO GENERAL
$inicio_total = microtime(true);

// Parámetros
$id_tabla    = $_POST['id_tabla'] ?? $_SESSION['id_tabla'];
$dni         = $_POST['dni'] ?? '';
$id_cartera  = $_POST['idCartera'] ?? 0;
$id_usuario  = $_SESSION['id'] ?? 0;

$_SESSION['dni'] = $dni;

include('config.php');

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

// Elegir procedimiento según cartera
$inicio_query = microtime(true); // ⏱ inicio query
if ($id_tabla == 'C_PICHINCHA_DINERS_REFINANCIADOS' || $id_tabla == 'C_PICHINCHA_DINERS_NO_REFINANCIADOS' || $id_tabla == 'C_EFECTIVA_VENTA') {
    $stmt = $mysqli->prepare("CALL GetCuentasGeneral(?, ?)");
    $stmt->bind_param('ss', $id_tabla, $dni);
} elseif ($id_tabla == 'C_FINANCIERA_EFECTIVA_CAMPO') {
    $stmt = $mysqli->prepare("CALL GetCuentasFECampo(?)");
    $stmt->bind_param('s', $dni);
} else {
    $stmt = $mysqli->prepare("CALL GetCuentas2(?, ?)");
    $stmt->bind_param('ss', $id_tabla, $dni);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// ⏱ FIN DEL BLOQUE QUERY
$tiempo_query = microtime(true) - $inicio_query;
error_log(sprintf("[CONSULTA_CUENTAS] %s → tiempo de query: %.3f s (DNI=%s)", $id_tabla, $tiempo_query, $dni));

// Muestra los datos en una tabla principal
if ($result && $result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped tabla-principal">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>PRODUCTO</th>';
    echo '<th>OK</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {


        echo '<tr>';
        if ($id_tabla == 'C_PICHINCHA_DINERS_REFINANCIADOS' || $id_tabla == 'C_PICHINCHA_DINERS_NO_REFINANCIADOS' || $id_tabla == 'C_EFECTIVA_VENTA') {
            $row = array_change_key_case($row, CASE_LOWER);
            echo '<td>' . $row['identificador'] . '</td>';
            echo '<td>' . $row['producto'] . '</td>';
        } else {
            echo '<td>' . $row['identificador'] . '</td>';
            echo '<td>' . $row['PRODUCTO'] . '</td>';
        }
        echo '<td><button class="btn btn-info ver-detalles-btn" data-id="' . $row['identificador'] . '">OK</button></td>';
        echo '</tr>';

        echo '<tr class="detalle-cuenta" style="display: none;">';
        echo '<td colspan="3" class="detalle-celda">';

        echo '<table class="table table-bordered">';
        echo '<tbody>';

        // PICHINCHAS
        if ($id_tabla == 'C_PICHINCHA_DINERS_REFINANCIADOS' || $id_tabla == 'C_PICHINCHA_DINERS_NO_REFINANCIADOS' || $id_tabla == 'C_EFECTIVA_VENTA') {
            $columnas_excluidas =
                [
                    'id',
                    'numero_cuenta',
                    'producto',
                    'subproducto',
                    'moneda',
                    'saldoporpagar',
                    'montocampana',
                    'pordescuento',
                    'nomcampana',
                    'anocastigo',
                    'fechacastigo',
                    'rango',
                    'fechaultpa',
                    'agencia',
                    'riesgo',
                    'estado',
                    'calsbs',
                    'entireportadas',
                    'edad',
                    'estadociv',
                    'ingresos',
                    'fechaulti',
                    'cuopac',
                    'cuopag',
                    'cuoven',
                    'cuopen'
                ];

            $contador_filas = 0;

            foreach ($row as $campo => $valor) {
                if (!in_array($campo, $columnas_excluidas)) {
                    if ($campo == 'montoacobrar' || $campo == 'deudatotal' || $campo == 'capital') {
                        $valor = number_format($valor, 2, '.', ',');
                    }

                    $color_fondo = ($contador_filas % 2 == 0) ? '#f2f2f2' : '#e0e4ec';

                    $icono = '<i class="fas fa-check-circle" style="color: green;"></i>';

                    echo '<tr style="background-color: ' . $color_fondo . ';">';
                    echo '<td style="font-weight: bold;">' . $icono . ' ' . ucfirst($campo) . '</td>';
                    echo '<td>' . $valor . '</td>';
                    echo '</tr>';

                    $contador_filas++;
                }
            }
        }
        /* FE CAMPO ROWS */ else if ($id_tabla == 'C_FINANCIERA_EFECTIVA_CAMPO') {
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">🪪 Doc</td><td>' . $row['documento'] . '</td></tr>';
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">🧛 Nombre</td><td>' . $row['NOMBRE'] . '</td></tr>';
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">📌 Dpto</td><td>' . $row['DPTO'] . '</td></tr>';
            echo '<tr style="background-color: #eff7a8;"><td style="font-weight: bold;">🥇 Campaña</td><td>' . number_format($row['MONTOCAMPANA'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">👀 Cluster</td><td>';
            echo $row['TRAMOINICIAL'];
            echo '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 Tipo Producto</td><td>' . $row['PRODUCTO'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 Detalle Producto</td><td>' . $row['SUBPRODUCTO'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">📅 Fecha Castigo </td><td>' . $row['FECHACASTIGO'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">⚡ Moneda</td><td>' . $row['MONEDA'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 Saldo</td><td>' . number_format($row['SALDOPORPAGAR'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 DeuTot</td><td>' . number_format($row['DEUDATOTAL'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 Capital</td><td>' . number_format($row['CAPITAL'], 2, '.', ',') . '</td></tr>';
        } else {
            // DEFAULT
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">🪪 Doc</td><td>' . $row['documento'] . '</td></tr>';
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">🧛 Nombre</td><td>' . $row['NOMBRE'] . '</td></tr>';
            echo '<tr style="background-color: #ffffff;"><td style="font-weight: bold;">📌 Dpto</td><td>' . $row['DPTO'] . '</td></tr>';
            echo '<tr style="background-color: #eff7a8;"><td style="font-weight: bold;">🥇 Campaña</td><td>' . number_format($row['CAMPANA'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">👀 Tramo</td><td>';
            echo $row['TRAMOFINAL'];
            echo '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">⚠️ DiasMora</td><td>' . $row['DIASATRASO'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 CuoPac</td><td>' . $row['CUOPAC'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 CuoPag</td><td>' . $row['CUOPAG'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 CuoVen</td><td>' . $row['CUOVEN'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">🕘 CuoPen</td><td>' . $row['CUOPEN'] . '</td></tr>';
            echo '<tr style="background-color: #f2f2f2;"><td style="font-weight: bold;">📅 FechaVen</td><td>' . $row['FECHAVEN'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">⚡ Moneda</td><td>' . $row['MONEDA'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 Cuota</td><td>' . $row['VALORCUOTA'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">📅 UltPago</td><td>' . $row['FECHAULTPA'] . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 Saldo</td><td>' . number_format($row['SALDO'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 DeuTot</td><td>' . number_format($row['DEUDATOTAL'], 2, '.', ',') . '</td></tr>';
            echo '<tr style="background-color: #dddddd;"><td style="font-weight: bold;">💸 Capital</td><td>' . number_format($row['CAPITAL'], 2, '.', ',') . '</td></tr>';
        }

        echo '</tbody>';

        $url_destino = ($id_usuario == 1391)
            ? "agregargestion2.php?id_tabla={$id_tabla}&identificador=" . $row['identificador'] . "&id_cartera={$id_cartera}"
            : "agregargestion2.php?id_tabla={$id_tabla}&identificador=" . $row['identificador'] . "&id_cartera={$id_cartera}";

        // ==========================================================================================================
        echo "<tr><td colspan=3><a class=btn btn-primary href='$url_destino'>✅ Agregar Gestión</a></td></tr>";
        // ==========================================================================================================

        echo '</table>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';

    echo '</table>';
} else {
    echo '<p>No se encontraron resultados.</p>';
}

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}

// ⏱ FIN TOTAL
$tiempo_total = microtime(true) - $inicio_total;
error_log(sprintf("[CONSULTA_CUENTAS] tiempo total de ejecución: %.3f s (DNI=%s, Usuario=%d)", $tiempo_total, $dni, $id_usuario));
