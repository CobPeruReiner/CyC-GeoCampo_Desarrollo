<?php
// Incluye el archivo de configuración para obtener la conexión a la base de datos
include('config.php');

// Verifica si se recibió el valor de idaccion
if (isset($_GET['idaccion'])) {
    $idaccion = $_GET['idaccion'];

    // Realiza una consulta a la base de datos para obtener los efectos basados en idaccion
    if ($stmt = $mysqli->prepare("CALL SP_MostrarEfectos(?)")) {
        $stmt->bind_param('i', $idaccion); // Supongo que el parámetro es de tipo entero (i)
        $stmt->execute();
        $result = $stmt->get_result();

        // Genera las opciones del combo idefecto
        $options = '';
        while ($row = $result->fetch_assoc()) {
            $options .= '<option value="' . $row['IDEFECTO'] . '">' . $row['EFECTO'] . '</option>';
        }

        $stmt->close();

        // Devuelve las opciones como respuesta a la solicitud AJAX
        echo $options;
    } else {
        echo '<option value="">Error al cargar efectos</option>';
    }
} else {
    echo '<option value="">Selecciona una acción primero</option>';
}
