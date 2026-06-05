<?php
require_once 'config.php';

if (isset($_GET['idPersonal']) && isset($_GET['fecha'])) {
    $idPersonal = $_GET['idPersonal'];
    $fecha = $_GET['fecha'];

    if (isset($_GET['fecha2'])) {
        $fecha2 = $_GET['fecha2'];
        $query = "CALL GetCampoGestiones2(?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iss', $idPersonal, $fecha, $fecha2);
    } else {
        $query = "CALL GetCampoGestiones2(?, ?, NULL)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('is', $idPersonal, $fecha);
    }

    // Llamar al procedimiento almacenado "GetAsignacion" con el ID del usuario
    // $query = "CALL GetCampoGestiones(?, ?, NULL)";

    $stmt->execute();
    $result = $stmt->get_result();

    $stmt->close();
} else {
    echo 'Id usuario no especificado.';
}

// Almacenar los datos en una variable
$rows = [];

// Iniciar el buffer de salida
ob_start();

// Muestra los datos en una tabla principal
if ($result && $result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped table-hover">';
    echo '<thead class="thead-dark">';
    echo '<tr>';
    echo '<th class="text-center">HORA</th>';
    echo '<th class="text-center">ID</th>';
    echo '<th class="text-center">EFECTO</th>';
    echo '<th class="text-center">MOTIVO</th>';
    echo '<th class="text-center">ACCION</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;  // Almacena la fila en el array
        echo '<tr>';
        echo '<td>' . explode(' ', $row['FECHA'])[1] . '</td>';
        echo '<td>' . $row['IDENTIFICADOR'] . '</td>';
        // echo '<td>' . $row ['IDEFECTO'] . '</td>';
        echo '<td>' . $row['EFECTO'] . '</td>';
        // echo '<td>' . $row ['IDMOTIVO'] . '</td>';
        echo '<td>' . $row['MOTIVO'] . '</td>';
        echo "<td class='hide-html-element'}>" . $row['latitud'] . '</td>';
        echo "<td class='hide-html-element'}>" . $row['longitud'] . '</td>';
        echo '<td class="text-center"><button type="button" class="btn btn-primary btn-test" data-row="' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . '"> Más </button></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo "<p class='text-center'>No se encontraron resultados.</p>";
}

// Obtener el contenido del buffer de salida y limpiarlo
$html = ob_get_clean();

$response = [
    'html' => $html,           // Obtener el HTML generado
    'result' => $rows             // Inicializar el array result
];

// Enviar la respuesta como JSON
echo json_encode($response);

// Cerrar la conexión a la base de datos
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close(); // Cierra la conexión a la base de datos
}
