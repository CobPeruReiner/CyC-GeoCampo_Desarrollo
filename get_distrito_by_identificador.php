<?php
require_once 'config.php';

if (isset($_GET['identificador'])) {
    $identificador = $_GET['identificador'];

    $query = "CALL GetDistritoByIdentificador(?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $identificador);

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    $response = [
        'result' => $data
    ];

    // Enviar la respuesta como JSON
    echo json_encode($response);

    // Cerrar la conexión a la base de datos
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
} else {
    echo 'identificador no especificado para buscar distrito.';
}
