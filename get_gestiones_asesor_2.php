<?php
require_once 'config.php';

if (isset($_GET['idPersonal'], $_GET['fecha'], $_GET['idCartera'])) {

    $idPersonal = $_GET['idPersonal'];
    $fecha = $_GET['fecha'];
    $fecha2 = $_GET['fecha2'] ?? null;
    $idCartera = $_GET['idCartera'];

    // ==========================
    // CASO 1: TODOS LOS ASESORES
    // ==========================
    if ($idPersonal === 'TODOS') {

        $query = "
            SELECT 
            g.FECHA,
            g.IDENTIFICADOR,
            g.IDEFECTO,
            e.EFECTO,
            g.IDMOTIVO,
            m.MOTIVO,
            g.IDCONTACTO,
            c.CONTACTO,
            g.OBSERVACION,
            g.IDDIRECCION,
            d.DIRECCION_DEPURADA,
            g.IDPERSONAL,
            g.NOMCONTACTO,
            g.PISOS,
            g.PUERTA,
            g.FACHADA,
            g.FECHA_PROMESA,
            g.MONTO_PROMESA,
            g.IDCARTERA,
            g.latitud,
            g.longitud,
            g.imagen1,
            g.imagen2,
            g.imagen3,
            gps.NOMBRE_GEOCAMPO_ESTADO_GPS
        FROM GEOCAMPO g
        LEFT JOIN efecto e 
            ON g.IDEFECTO = e.IDEFECTO
        LEFT JOIN motivo m 
            ON g.IDMOTIVO = m.IDMOTIVO
        LEFT JOIN contacto c 
            ON g.IDCONTACTO = c.IDCONTACTO
        LEFT JOIN direcciones d 
            ON g.IDDIRECCION = d.IDDIRECCION
        LEFT JOIN GEOCAMPO_ESTADO_GPS gps
            ON g.ID_GEOCAMPO_ESTADO_GPS = gps.ID_GEOCAMPO_ESTADO_GPS
        WHERE g.IDCARTERA = ?
        AND g.FECHA BETWEEN ?
            AND IFNULL(
                ? + INTERVAL 1 DAY - INTERVAL 1 SECOND,
                ? + INTERVAL 1 DAY - INTERVAL 1 SECOND
            )
        AND g.latitud <> ''
        ORDER BY g.ID DESC;
        ";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param(
            'isss',
            $idCartera,
            $fecha,
            $fecha2,
            $fecha
        );
    }

    // ==========================
    // CASO 2: UN ASESOR ESPECÍFICO
    // ==========================
    else {

        if ($fecha2) {
            $query = "CALL GetCampoGestiones3(?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('iiss', $idPersonal, $idCartera, $fecha, $fecha2);
        } else {
            $query = "CALL GetCampoGestiones3(?, ?, ?, NULL)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('iis', $idPersonal, $idCartera, $fecha);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    echo 'Parámetros incompletos.';
}


$rows = [];

ob_start();

if ($result && $result->num_rows > 0) {
    echo '<table class="relative w-full text-[#67748e] text-nowrap" style="width:100%;">';
    echo '<thead>';
    echo '<tr clas="text-xs text-[#8392ab] text-left uppercase opacity-70 border-b border-solid border-[#e9ecef]">';
    echo '<th class="py-3 px-6">Fecha</th>';
    echo '<th class="py-3 px-6">Hora</th>';
    echo '<th class="py-3 px-6">Identificador</th>';
    echo '<th class="py-3 px-6">Efecto</th>';
    echo '<th class="py-3 px-6">Observación</th>';
    echo '<th class="py-3 px-6">Estado GPS</th>';
    echo '<th class="py-3 px-6">ACCION</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $fechaFormateada = date("d/m/Y H:i:s", strtotime($row['FECHA']));
        $row['FECHA'] = $fechaFormateada;
        $rows[] = $row;
        echo '<tr class="text-xs border-b hover:bg-gray-100">';
        echo '<td class="py-3 px-6">' . explode(' ', $row['FECHA'])[0] . '</td>';
        echo '<td class="py-3 px-6">' . explode(' ', $row['FECHA'])[1] . '</td>';
        echo '<td class="py-3 px-6">' . $row['IDENTIFICADOR'] . '</td>';
        echo '<td class="py-3 px-6">' . $row['EFECTO'] . '</td>';
        echo '<td class="py-3 px-6">' . $row['OBSERVACION'] . '</td>';
        echo '<td class="py-3 px-6">' . $row['NOMBRE_GEOCAMPO_ESTADO_GPS'] . '</td>';
        echo "<td class='hide-html-element'}>" . $row['latitud'] . '</td>';
        echo "<td class='hide-html-element'}>" . $row['longitud'] . '</td>';
        echo '<td role="button" class="py-3 px-6 text-center btn-test" data-row="' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . '"><i class="fa-solid fa-eye"></i></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo "<p class='text-center'>No se encontraron resultados.</p>";
}

$html = ob_get_clean();

$response = [
    'html' => $html,
    'result' => $rows
];

echo json_encode($response);

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
