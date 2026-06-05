<?php
// ===== CORS =====
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Si el navegador manda OPTIONS (preflight), cortamos acá
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // METODOS SANTANDER CAMPO
    if (isset($_GET['getAcciones']) && isset($_GET['id_tabla'])) {

        $id_tabla = $_GET['id_tabla'];
        // error_log($id_tabla);
        // Consulta para obtener información de la base de datos
        if ($id_tabla == 'C_SANTANDER_CAMPO') {
            // error_log('Campo query');
            $consulta = "SELECT * FROM accion where idcartera = (SELECT id_cartera FROM tabla_log WHERE nombre = ?) and tipo = 2 AND accion = 'HACER VISITA' AND idestado = 1;";
        } elseif ($id_tabla == 'C_FINANCIERA_CONFIANZA_CAMPO') {
            // error_log('FC Query');
            $consulta = "SELECT * FROM accion where idcartera = (SELECT id_cartera FROM tabla_log WHERE nombre = ?) and tipo = 2 AND accion = 'VISITA A TITULAR EN DOMICILIO' AND idestado = 1;";
        } else {
            // error_log('Otro query');
            $consulta = "SELECT * FROM accion where idcartera = (SELECT id_cartera FROM tabla_log WHERE nombre = ?) and tipo = 2 AND idestado = 1;";
        }
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param('s', $id_tabla);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtener los resultados en un array
        $acciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $acciones[] = $fila;
        }
        // $mensaje = "Contenido del array: " . print_r($acciones, true);
        // error_log($mensaje);
        // Devolver resultados en formato JSON
        echo json_encode(['success' => true, 'acciones' => $acciones]);
    } elseif (isset($_GET['getEfectos']) && isset($_GET['idaccion'])) {
        // Obtener el parámetro idaccion
        $idaccion = $_GET['idaccion'];

        // Consulta para obtener información de la base de datos
        $consulta = "SELECT * FROM efecto WHERE idaccion = ? AND idestado = 1 ORDER BY EFECTO ASC;";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param('i', $idaccion);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtener los resultados en un array
        $efectos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $efectos[] = $fila;
        }

        // Devolver resultados en formato JSON
        echo json_encode(['success' => true, 'efectos' => $efectos]);
    } elseif (isset($_GET['getMotivos']) && isset($_GET['idefecto'])) {
        // Obtener el parámetro idaccion
        $idefecto = $_GET['idefecto'];

        // Consulta para obtener información de la base de datos
        $consulta = "SELECT * FROM motivo WHERE IDEFECTO = ? AND IDESTADO = 1;";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param('i', $idefecto);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtener los resultados en un array
        $motivos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $motivos[] = $fila;
        }

        // Devolver resultados en formato JSON
        echo json_encode(['success' => true, 'motivos' => $motivos]);
    } elseif (isset($_GET['getContactos']) && isset($_GET['idefecto'])) {
        // Obtener el parámetro idaccion
        $idefecto = $_GET['idefecto'];

        // Consulta para obtener información de la base de datos
        $consulta = "SELECT * FROM contacto WHERE IDEFECTO = ? AND IDESTADO = 1;";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param('i', $idefecto);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtener los resultados en un array
        $contactos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $contactos[] = $fila;
        }

        // Devolver resultados en formato JSON
        echo json_encode(['success' => true, 'contactos' => $contactos]);
    } elseif (isset($_GET['getDirecciones']) && isset($_GET['documento']) && isset($_GET['id_tabla'])) {
        // Obtener el parámetro documento
        $documento = $_GET['documento'];
        $id_tabla = $_GET['id_tabla'];

        // Consulta para obtener información de la base de datos
        // $consulta = "SELECT * FROM direcciones WHERE DOC = ? AND IDESTADO = 1;";
        $consulta = "
        SELECT MAX(d.IDDIRECCION) AS IDDIRECCION, d.DOC, d.FUENTE, d.DIRECCION_DEPURADA 
        FROM direcciones d
        LEFT JOIN cliente c ON c.id = d.fuente
        WHERE d.FUENTE = (
        SELECT id FROM cliente
        WHERE id = (SELECT idcliente FROM cartera
        where id =(SELECT id_cartera FROM tabla_log WHERE nombre = ?))
        AND estado = 1
        )
        AND d.DOC = ?
        AND d.DIRECCION_DEPURADA IS NOT NULL
        AND d.IDESTADO = 1
        GROUP BY d.DOC, d.FUENTE, d.DIRECCION_DEPURADA;
        ";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param('ss', $id_tabla, $documento);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Obtener los resultados en un array
        $direcciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $direcciones[] = $fila;
        }

        // Devolver resultados en formato JSON
        echo json_encode(['success' => true, 'direcciones' => $direcciones]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Solicitud no válida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
