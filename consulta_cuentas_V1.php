<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_domain', '');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

header('Content-Type: text/html; charset=UTF-8');

// Obtén el valor de id_tabla desde la sesión o el parámetro GET
$id_tabla = isset($_GET['id_tabla']) ? $_GET['id_tabla'] : $_SESSION['id_tabla'];

// Verificar si se envió un formulario de búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibe el DNI del formulario
    $dni = isset($_POST['dni']) ? $_POST['dni'] : '';

    // Incluye el archivo de configuración para obtener la conexión a la base de datos


    include('config.php');

    // Verificar la conexión (esto no es necesario si config.php ya lo hace)
    if ($mysqli->connect_error) {
        die('Error de conexión: ' . $mysqli->connect_error);
    }

    // Preparar la llamada al procedimiento almacenado
    $stmt = $mysqli->prepare("CALL GetCuentas(?, ?)");
    $stmt->bind_param('ss', $id_tabla, $dni);

    // Ejecutar el procedimiento almacenado
    $stmt->execute();

    // Obtener el resultado de la consulta
    $result = $stmt->get_result();

    // Cerrar el statement
    $stmt->close();
}

// Verificar si se hizo clic en el botón "Seleccionar"
if (isset($_POST['seleccionar'])) {
    // Obtener el valor del identificador desde el botón "Seleccionar"
    $identificador = $_POST['seleccionar'];

    // Preparar la llamada al procedimiento almacenado "GetInfoPersonal"
    $stmtInfo = $mysqli->prepare("CALL GetInfoPersonal(?, ?)");
    $stmtInfo->bind_param('ss', $id_tabla, $identificador);

    // Ejecutar el procedimiento almacenado
    $stmtInfo->execute();

    // Obtener el resultado de la consulta
    $resultInfo = $stmtInfo->get_result();

    // Cerrar el statement
    $stmtInfo->close();
}

// Cerrar la conexión a la base de datos
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close(); // Cierra la conexión a la base de datos
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Cuentas</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="container">
        <h4>Consulta de Información</h4>
        <!-- Formulario para ingresar el DNI -->
        <form id="search-form">
            <input type="hidden" id="id_tabla" value="<?php echo $id_tabla; ?>">
            <div class="form-group">
                <label for="dni">Ingrese el DNI:</label>
                <input type="text" class="form-control" id="dni" name="dni" required>
            </div>
            <button type="submit" class="btn btn-dark" id="buscarCuentas">Buscar</button>
        </form>
        <br>
        <!-- Resultado de la consulta -->
        <div class="detalle-resultados" id="resultados">
            <!-- Aquí se mostrará la tabla de resultados -->
        </div>
        <a href="menu.php" class="btn btn-secondary">Volver al Menú</a>
    </div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>

    <!-- Agregar FontAwesome y Bootstrap JavaScript (si es necesario) -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Manejar la solicitud de búsqueda de cuentas
        const urlParams = new URLSearchParams(window.location.search);

        const idCartera = urlParams.get('id_cartera');
        // console.log('consulta cuentas: ', idCartera)

        $(document).on('submit', '#search-form', function(e) {
            e.preventDefault();

            const id_tabla = $('#id_tabla').val();
            const dni = $('#dni').val();

            $.ajax({
                type: 'POST',
                url: 'get_info_personal.php',
                data: {
                    id_tabla: id_tabla,
                    dni: dni,
                    idCartera
                },
                dataType: 'html',
                success: function(response) {
                    // Mostrar la respuesta en el elemento 'resultados'
                    $('#resultados').html(response);
                }
            });
        });

        // Manejar el evento de clic para expandir o contraer detalles
        $(document).on('click', '.ver-detalles-btn', function() {
            const detalleRow = $(this).closest('tr').next('.detalle-cuenta');
            detalleRow.toggle();
        });
    </script>

    <!-- VERIFICAR TOKEN -->
    <script src="verificarToken.js"></script>
</body>

</html>