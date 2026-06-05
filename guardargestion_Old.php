<?php
require_once 'horario.php';
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

$id_tabla = isset($_GET['id_tabla']) ? $_GET['id_tabla'] : $_SESSION['id_tabla'];
$identificador = isset($_GET['identificador']) ? $_GET['identificador'] : '';


date_default_timezone_set('America/Lima');

// Incluye el archivo de configuración para obtener la conexión a la base de datos
include('config.php');


// Verificar la conexión
if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

if (!validarHorario()) {
    // header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Gestión no permitida fuera del horario.'];
    echo json_encode($response);
    exit();
}

// Obtener los datos del formulario
$fecha = date('Y-m-d H:i:s'); // Fecha y hora actual
$idaccion = $_POST['idaccion'];
$idefecto = $_POST['idefecto'];
$horaVisita = $_POST['hora_visita'] . ':00';

// $idmotivo = $_POST['idmotivo'];
// $idcontacto = $_POST['idcontacto'];
// Obtener los valores seleccionados, si están disponibles
$idmotivo = !empty($_POST['idmotivo']) ? $_POST['idmotivo'] : 0;
$idcontacto = !empty($_POST['idcontacto']) ? $_POST['idcontacto'] : 0;

$observacion = $_POST['observacion'];
// $iddireccion = $_POST['iddireccion'];
$iddireccion = !empty($_POST['iddireccion']) ? $_POST['iddireccion'] : 0;
$idpersonal = $_SESSION['id'];
$nomcontacto = $_POST['nomcontacto'];
$pisos = $_POST['pisos'];
$puerta = $_POST['puerta'];
$fachada = $_POST['fachada'];

// ==================================================================================
// $fecha_promesa = $_POST['fecha_promesa'];
// $monto_promesa = $_POST['monto_promesa'];
// $fecha_promesa = !empty($_POST['fecha_promesa']) ? $_POST['fecha_promesa'] : NULL;

$fecha_promesa = !empty($_POST['fecha_promesa']) ? "'{$_POST['fecha_promesa']}'" : "NULL";

// $monto_promesa = !empty($_POST['monto_promesa']) ? $_POST['monto_promesa'] : NULL;

$monto_promesa = !empty($_POST['monto_promesa']) ? $_POST['monto_promesa'] : "NULL";
if ($monto_promesa != "NULL") {
    $monto_promesa = "'$monto_promesa'";
}

// ==================================================================================

// $idcartera = 0; // Valor por defecto
$latitud = $_POST['latitud']; // Obtener de la ubicación geográfica
$longitud = $_POST['longitud']; // Obtener de la ubicación geográfica
$txt = $_POST['txt']; // Obtener de la ubicación geográfica

// $idefecto = 0;
$idefecto = intval($idefecto);

if (!isset($idefecto) || $idefecto === null || empty($idefecto) || !isset($iddireccion) || $iddireccion === null || $iddireccion == 0 || empty($iddireccion)) {
    $response1 = array();
    $response1['success'] = false;
    $response1['message'] = "Error en registro, compruebe conexión, actualice la página e ingrese gestión nuevamente" . $mysqli->error;
    header('Content-Type: application/json');
    echo json_encode($response1);
    exit;
}

if ($horaVisita < "07:00" || $horaVisita > "20:00") {
    $response = array(
        "success" => false,
        "message" => "La hora de visita debe estar entre las 07:00 y las 20:00"
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// nombrar las imagenes
$imagen1 = 'Imagen1' . $identificador . '_' . date('YmdHis') . '.jpg';
$imagen2 = 'Imagen2' . $identificador . '_' . date('YmdHis') . '.jpg';
$imagen3 = 'Imagen3' . $identificador . '_' . date('YmdHis') . '.jpg';

// Definir un array asociativo de errores
$fileErrors = array(
    UPLOAD_ERR_OK => 'No hay error, la carga se realizo correctamente',
    UPLOAD_ERR_INI_SIZE => 'El archivo cargado excede la directiva upload_max_filesize en php.ini',
    UPLOAD_ERR_FORM_SIZE => 'El archivo cargado excede la directiva MAX_FILE_SIZE especificada en el formulario HTML',
    UPLOAD_ERR_PARTIAL => 'El archivo solo se ha subido parcialmente',
    UPLOAD_ERR_NO_FILE => 'No se ha subido ningun archivo',
    UPLOAD_ERR_NO_TMP_DIR => 'Falta una carpeta temporal',
    UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
    UPLOAD_ERR_EXTENSION => 'Una extension PHP detuvo la carga del archivo'
);

// Función para imprimir el mensaje de error
function imprimirError($errorCode, $fileErrors, $htmlFileId)
{
    if (array_key_exists($errorCode, $fileErrors)) {
        error_log("Error en archivo " . $htmlFileId . ", error: " . $fileErrors[$errorCode]);
    } else {
        error_log("Error en archivo " . $htmlFileId . ", código de error desconocido: " . $errorCode);
    }
}

function moveFile($filename, $htmlFileId, $id_tabla, $fileErrors)
{
    // Verificar si se subió un archivo sin errores
    if (isset($_FILES[$htmlFileId])) {
        // Tamaño máximo permitido en bytes (2 MB)
        // $tamanoMaximo = 2 * 1024 * 1024;

        // Tipos de archivo permitidos (jpg y png)
        // $tiposPermitidos = ['image/jpeg', 'image/png'];

        // Validar tamaño y tipo del archivo
        // if ($_FILES['miArchivo']['size'] <= $tamanoMaximo && in_array($_FILES['miArchivo']['type'], $tiposPermitidos)) {
        //     // Procesar el archivo
        // } else {
        //     echo "El archivo no cumple con los requisitos de tamaño o tipo.";
        // }
        $errorCode = $_FILES[$htmlFileId]['error'];
        if ($errorCode === 0) {
            $carpeta_ruta = substr($id_tabla, 2);
            // $ubicacionFinal = $directorioDestino . basename($_FILES['imagen1']['name']);

            // last file path
            // $ubicacionFinal = './fotos/' . $carpeta_ruta . '/' . $filename;
            $ubicacionFinal = '/var/www/html/CyC-GeoCampo/fotos/' . $carpeta_ruta . '/' . $filename;

            if (move_uploaded_file($_FILES[$htmlFileId]['tmp_name'], $ubicacionFinal)) {
                move_uploaded_file($_FILES[$htmlFileId]['tmp_name'], $ubicacionFinal);
            } else {
                error_log("Error al mover el archivo " . $htmlFileId);
            }
        } else {
            // Commented because of long logs, but working
            // imprimirError($errorCode, $fileErrors, $htmlFileId);
        }

        // move_uploaded_file($_FILES['imagen1']['tmp_name'], './fotos/' . $carpeta_ruta . '/' . $imagen1);

    } else {
        error_log($htmlFileId . " no encontrada");
    }
}

// move imagenes files if were selected
moveFile($imagen1, 'imagen1', $id_tabla, $fileErrors);
moveFile($imagen2, 'imagen2', $id_tabla, $fileErrors);
moveFile($imagen3, 'imagen3', $id_tabla, $fileErrors);

// Dinamic adding idcartera and setting image directory depending on tablename

$idcartera = $_POST['id_cartera'];

// Mover las imágenes cargadas a una ubicación en el servidor
// move_uploaded_file($_FILES['imagen1']['tmp_name'], '/var/www/html/CyC-GeoCampo/fotos/' . $carpeta_ruta . '/' . $imagen1);
// move_uploaded_file($_FILES['imagen2']['tmp_name'], '/var/www/html/CyC-GeoCampo/fotos/' . $carpeta_ruta . '/' . $imagen2);
// move_uploaded_file($_FILES['imagen3']['tmp_name'], '/var/www/html/CyC-GeoCampo/fotos/' . $carpeta_ruta . '/' . $imagen3);

// move_uploaded_file($_FILES['compressedFile']['tmp_name'], './fotos/' . $carpeta_ruta . '/' . $imagen1);

// move_uploaded_file($_FILES['imagen1']['tmp_name'], './fotos/' . $carpeta_ruta . '/' . $imagen1);
// move_uploaded_file($_FILES['imagen2']['tmp_name'], './fotos/' . $carpeta_ruta . '/' . $imagen2);
// move_uploaded_file($_FILES['imagen3']['tmp_name'], './fotos/' . $carpeta_ruta . '/' . $imagen3);

// Llamar al procedimiento almacenado
$sql = "CALL SP_InsertarGEOCAMPO_PRUEBA('$fecha','$identificador', '$id_tabla', '$idefecto', '$idmotivo', '$idcontacto', '$observacion', '$iddireccion', '$idpersonal', '$nomcontacto', '$pisos', '$puerta', '$fachada', $fecha_promesa, $monto_promesa, '$idcartera', '$latitud', '$longitud', '$txt', '$imagen1', '$imagen2', '$imagen3', '$horaVisita')";

// if ($mysqli->query($sql) === TRUE) {

//     // echo '<tr style="background-color: #edfcff;"><td style="font-weight: bold;">Gestión Ingresada correctamente.</td></tr>';
//     echo '<script>';
//     echo 'alert("Gestión Ingresada correctamente.");';
//     echo 'window.history.go(-2);'; // Retroceder vistas en el historial del navegador
//     echo '</script>';

// } else {
//     echo "Error al insertar registro: " . $mysqli->error;
//     echo '<script>';
//     echo 'console.log($mysqli->error);';
//     echo 'window.history.go(-2);'; // Retroceder vistas en el historial del navegador
//     echo '</script>';
// }

$response = array();

if ($mysqli->query($sql) === TRUE) {
    $response['success'] = true;
    $response['message'] = "Gestión Ingresada correctamente.";
} else {
    $response['success'] = false;
    $response['message'] = "Error al insertar registro: " . $mysqli->error;
}

header('Content-Type: application/json');
echo json_encode($response);

// REDIRECCION A BUSCAR NUEVO CLIENTE
// echo '<script>';
// echo 'setTimeout(function(){ window.history.go(-2); }, 1000);'; // Redirigir después de 1 segundo
// echo '</script>';

exit;

// Cerrar la conexión
$mysqli->close();
