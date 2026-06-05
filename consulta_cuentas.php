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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Cuentas</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        :root {
            --cyc-red: #c72026;
            --cyc-red-dark: #9f171d;
            --cyc-gray-900: #232323;
            --cyc-gray-700: #555555;
            --cyc-gray-200: #ececec;
            --cyc-gray-100: #f7f7f7;
            --cyc-white: #ffffff;
            --cyc-shadow: 0 16px 40px rgba(0, 0, 0, .10);
            --cyc-radius: 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(199, 32, 38, .10), transparent 32%),
                linear-gradient(135deg, #ffffff 0%, #f4f4f4 48%, #e9e9e9 100%);
            color: var(--cyc-gray-900);
        }

        .consulta-page {
            width: 100%;
            min-height: 100vh;
            padding: 34px 16px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .consulta-shell {
            width: min(1120px, 100%);
        }

        .consulta-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--cyc-red), var(--cyc-red-dark));
            color: var(--cyc-white);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 28px rgba(199, 32, 38, .25);
            flex: 0 0 auto;
        }

        .brand-mark i,
        .brand-mark svg {
            width: 28px;
            height: 28px;
        }

        .consulta-title {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--cyc-gray-900);
        }

        .consulta-subtitle {
            margin: 4px 0 0;
            color: var(--cyc-gray-700);
            font-size: .95rem;
            font-weight: 500;
        }

        .consulta-card {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: var(--cyc-radius);
            box-shadow: var(--cyc-shadow);
            overflow: hidden;
        }

        .consulta-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--cyc-gray-200);
            background: linear-gradient(90deg, rgba(199, 32, 38, .08), rgba(255, 255, 255, .6));
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .consulta-card-header .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--cyc-white);
            color: var(--cyc-red);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .08);
            flex: 0 0 auto;
        }

        .consulta-card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
            color: var(--cyc-gray-900);
        }

        .consulta-card-body {
            padding: 22px;
        }

        #search-form {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto;
            gap: 14px;
            align-items: end;
            margin: 0;
        }

        #search-form .form-group {
            margin-bottom: 0;
        }

        .consulta-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--cyc-gray-700);
            font-weight: 700;
            font-size: .92rem;
        }

        .consulta-input-wrap {
            position: relative;
        }

        .consulta-input-wrap .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cyc-red);
            width: 20px;
            height: 20px;
            pointer-events: none;
        }

        #dni.form-control {
            height: 48px;
            border-radius: 14px;
            border: 1px solid #d8d8d8;
            padding-left: 44px;
            font-weight: 600;
            color: var(--cyc-gray-900);
            background: var(--cyc-white);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        #dni.form-control:focus {
            border-color: var(--cyc-red);
            box-shadow: 0 0 0 .18rem rgba(199, 32, 38, .16);
        }

        .btn-cyc-primary,
        .btn-cyc-secondary {
            min-height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-weight: 700;
            border: 0;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
            white-space: nowrap;
        }

        .btn-cyc-primary {
            padding: 0 22px;
            background: linear-gradient(135deg, var(--cyc-red), var(--cyc-red-dark));
            color: var(--cyc-white) !important;
            box-shadow: 0 12px 24px rgba(199, 32, 38, .22);
        }

        .btn-cyc-primary:hover,
        .btn-cyc-primary:focus {
            color: var(--cyc-white) !important;
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(199, 32, 38, .28);
        }

        .btn-cyc-secondary {
            padding: 0 18px;
            background: var(--cyc-gray-900);
            color: var(--cyc-white) !important;
            text-decoration: none !important;
        }

        .btn-cyc-secondary:hover,
        .btn-cyc-secondary:focus {
            background: #111111;
            color: var(--cyc-white) !important;
            transform: translateY(-1px);
        }

        .detalle-resultados {
            margin-top: 20px;
        }

        .detalle-resultados:empty {
            display: none;
        }

        .resultados-panel {
            background: var(--cyc-white);
            border-radius: 18px;
            min-height: 76px;
        }

        .acciones-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        #resultados table {
            width: 100% !important;
            overflow: hidden;
            border-radius: 16px;
            background: var(--cyc-white);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
        }

        #resultados table th {
            background: var(--cyc-gray-900);
            color: var(--cyc-white);
            border: 0;
            font-weight: 700;
            white-space: nowrap;
        }

        #resultados table td {
            vertical-align: middle;
            border-top: 1px solid #eeeeee;
        }

        #resultados .btn,
        #resultados button {
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .consulta-page {
                padding: 18px 10px;
            }

            .consulta-hero {
                align-items: flex-start;
                margin-bottom: 14px;
            }

            .brand-mark {
                width: 48px;
                height: 48px;
                border-radius: 16px;
            }

            .consulta-card-header,
            .consulta-card-body {
                padding: 16px;
            }

            #search-form {
                grid-template-columns: 1fr;
            }

            .btn-cyc-primary,
            .btn-cyc-secondary {
                width: 100%;
            }

            .acciones-footer {
                justify-content: stretch;
            }

            .resultados-panel {
                overflow-x: hidden;
                -webkit-overflow-scrolling: auto;
                background: transparent;
            }

            #resultados .tabla-cuentas-responsive {
                width: 100% !important;
                min-width: 0 !important;
                font-size: .88rem;
                box-shadow: none;
                background: transparent;
                border-radius: 0;
            }

            #resultados .tabla-cuentas-responsive > thead {
                display: none;
            }

            #resultados .tabla-cuentas-responsive,
            #resultados .tabla-cuentas-responsive > tbody,
            #resultados .tabla-cuentas-responsive > tbody > tr,
            #resultados .tabla-cuentas-responsive > tbody > tr > td {
                display: block;
                width: 100%;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta {
                margin-bottom: 12px;
                border-radius: 16px;
                background: var(--cyc-white);
                box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
                overflow: hidden;
                border: 1px solid #eeeeee;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 12px 14px;
                border-top: 1px solid #eeeeee;
                text-align: right;
                font-size: .86rem;
                line-height: 1.35;
                word-break: break-word;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td:first-child {
                border-top: 0;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--cyc-gray-700);
                text-align: left;
                min-width: 38%;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td:empty {
                display: none;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td button,
            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td .btn,
            #resultados .tabla-cuentas-responsive > tbody > tr.fila-cuenta > td a {
                width: min(164px, 100%);
                min-height: 38px;
                margin-left: auto;
                display: inline-flex;
                justify-content: center;
                align-items: center;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.detalle-cuenta {
                margin: -4px 0 14px;
                border: 0;
                box-shadow: none;
                background: transparent;
            }

            #resultados .tabla-cuentas-responsive > tbody > tr.detalle-cuenta > td {
                display: block;
                padding: 0;
                border: 0;
                background: transparent;
                text-align: left;
            }

            #resultados .detalle-cuenta .table-responsive,
            #resultados .detalle-cuenta .table {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0;
            }

            #resultados .tabla-detalle-responsive {
                width: 100% !important;
                min-width: 0 !important;
                margin: 0;
                border: 1px solid #eeeeee;
                border-radius: 16px;
                background: var(--cyc-white);
                box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
                overflow: hidden;
                table-layout: fixed;
            }

            #resultados .tabla-detalle-responsive tbody {
                display: block;
                width: 100%;
            }

            #resultados .tabla-detalle-responsive tr {
                display: grid;
                grid-template-columns: minmax(112px, 42%) minmax(0, 1fr);
                width: 100%;
                margin: 0;
                border-bottom: 1px solid #eeeeee;
                background: var(--cyc-white);
            }

            #resultados .tabla-detalle-responsive tr:last-child {
                border-bottom: 0;
            }

            #resultados .tabla-detalle-responsive tr > td {
                min-width: 0;
                min-height: 48px;
                padding: 12px 14px !important;
                border: 0 !important;
                display: flex;
                align-items: center;
                font-size: .84rem;
                line-height: 1.35;
                color: var(--cyc-gray-900);
                background: transparent !important;
                word-break: normal;
                overflow-wrap: anywhere;
            }

            #resultados .tabla-detalle-responsive tr > td:first-child {
                width: auto !important;
                justify-content: flex-start;
                border-right: 1px solid #eeeeee !important;
                font-weight: 700;
                text-align: left;
            }

            #resultados .tabla-detalle-responsive tr > td:last-child {
                justify-content: flex-start;
                text-align: left;
            }

            #resultados .tabla-detalle-responsive .detalle-label-inner {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                min-width: 0;
                max-width: 100%;
                white-space: normal;
            }

            #resultados .tabla-detalle-responsive .detalle-icon {
                flex: 0 0 auto;
                width: 15px;
                height: 15px;
            }

            #resultados .tabla-detalle-responsive tr > td:empty {
                display: none;
            }

            #resultados .tabla-detalle-responsive tr > td[colspan] {
                grid-column: 1 / -1;
                display: block;
                text-align: center;
                border-right: 0 !important;
            }

            #resultados .tabla-detalle-responsive tr > td[colspan] .btn {
                width: 100%;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 420px) {
            .consulta-subtitle {
                font-size: .86rem;
            }

            .brand-block {
                gap: 10px;
            }

            .consulta-card-header h5 {
                font-size: .94rem;
            }
        }
    </style>
</head>

<body>
    <main class="consulta-page">
        <div class="consulta-shell">
            <section class="consulta-hero" aria-label="Encabezado de consulta">
                <div class="brand-block">
                    <div class="brand-mark" aria-hidden="true">
                        <i data-lucide="search-check"></i>
                    </div>
                    <div>
                        <h4 class="consulta-title">Consulta de Información</h4>
                    </div>
                </div>
            </section>

            <section class="consulta-card">
                <div class="consulta-card-header">
                    <span class="header-icon" aria-hidden="true"><i data-lucide="id-card"></i></span>
                    <h5>Datos de búsqueda</h5>
                </div>

                <div class="consulta-card-body">
                    <!-- Formulario para ingresar el DNI -->
                    <form id="search-form">
                        <input type="hidden" id="id_tabla" value="<?php echo htmlspecialchars($id_tabla, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group">
                            <label for="dni" class="consulta-label">
                                <i data-lucide="badge-check"></i>
                                Ingrese el DNI
                            </label>
                            <div class="consulta-input-wrap">
                                <i class="input-icon" data-lucide="user-round-search"></i>
                                <input type="text" class="form-control" id="dni" name="dni" required autocomplete="off" inputmode="numeric" placeholder="Ej. 08264290">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-cyc-primary" id="buscarCuentas">
                            <i data-lucide="search"></i>
                            Buscar
                        </button>
                    </form>

                    <!-- Resultado de la consulta -->
                    <div class="detalle-resultados resultados-panel" id="resultados">
                        <!-- Aquí se mostrará la tabla de resultados -->
                    </div>

                    <div class="acciones-footer">
                        <a href="menu.php" class="btn-cyc-secondary">
                            <i data-lucide="arrow-left"></i>
                            Volver al Menú
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>

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
        if (window.lucide) {
            lucide.createIcons();
        }

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
                    prepararTablaResponsive();
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                }
            });
        });

        function prepararTablaResponsive() {
            const $tablaPrincipal = $('#resultados > table').first();
            $tablaPrincipal.addClass('tabla-cuentas-responsive');

            const headersPrincipal = [];
            $tablaPrincipal.find('> thead > tr > th').each(function() {
                headersPrincipal.push($(this).text().trim());
            });

            $tablaPrincipal.find('> tbody > tr').each(function() {
                const $fila = $(this);

                if (!$fila.hasClass('detalle-cuenta')) {
                    $fila.addClass('fila-cuenta');
                }

                $fila.find('> td').each(function(index) {
                    if (headersPrincipal[index]) {
                        $(this).attr('data-label', headersPrincipal[index]);
                    }
                });
            });

            $('#resultados tr.detalle-cuenta table').each(function() {
                const $tablaDetalle = $(this);
                const headersDetalle = [];

                $tablaDetalle.addClass('tabla-detalle-responsive');

                $tablaDetalle.find('> thead > tr > th').each(function() {
                    headersDetalle.push($(this).text().trim());
                });

                $tablaDetalle.find('> tbody > tr').each(function() {
                    $(this).find('> td').each(function(index) {
                        if (headersDetalle[index]) {
                            $(this).attr('data-label', headersDetalle[index]);
                        }
                    });
                });
            });
        }

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