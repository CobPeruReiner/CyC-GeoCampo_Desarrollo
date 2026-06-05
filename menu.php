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

$cargoPersonal = $_SESSION['cargo'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="rutaAsesor.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="menu-container">
        <h3 style="color: #a32d2d;">Hola,💪</h3>
        <h4>🏍️<?php echo $_SESSION['nombreCompleto']; ?></h4>
        <!--<h3>DNI: <?php echo $_SESSION['doc']; ?></h3> -->
        <marquee behavior="scroll" direction="left" style="color: #383838; background-color: #eff7a8; font-size: 18px;">
            ⚠️Advertencia: Todo pago debe realizarse en las agencias autorizadas. Está prohibido recibir dinero de los
            clientes.⚠️
        </marquee>
        <div class="form-group">

            <select class="form-control" id="id_tabla" name="id_tabla">

            </select>
        </div>
        <!-- Menú con opciones -->
        <div class="custom-list-group">
            <a href="#" class="custom-list-group-item list-group-item-action consulta-informacion">
                <i class="fas fa-info-circle"></i> Consulta de Informacion
            </a>
            <a href="#" class="custom-list-group-item list-group-item-action ruta-asesor">
                <i class="fas fa-map-marker-alt"></i> Ruta Asesor
            </a>
            <a href="#" class="custom-list-group-item list-group-item-action ruta-supervisor hide-html-element">
                <i class="fas fa-user-shield"></i> Control gestión campo
            </a>
            <!-- Este mismo boton sera para supervisor/asesor solo que con condicional al momento de re direccionar -->
            <a href="#" class="custom-list-group-item list-group-item-action gestion-cuentas hide-html-element">
                <i class="fas fa-clipboard-check"></i>
                <span class="gestion-cuentas-texto"></span>
            </a>
            <a href="#" class="custom-list-group-item list-group-item-action registro-visita-oficina hide-html-element">
                <i class="fas fa-building"></i> Registro Visita a Oficina
            </a>
            <a href="#" class="custom-list-group-item list-group-item-action registro-operativo hide-html-element">
                <i class="fas fa-clipboard-check"></i> Operativo
            </a>
        </div>

        <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
    </div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>


    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        const cargosAdmin = [1, 8, 15, 16, 17, 18, 19, 20, 21, 22];

        document.addEventListener('DOMContentLoaded', function() {
            const userId = <?php echo (int)$_SESSION['id']; ?>;
            const carteraSesion = <?php echo (int)($_SESSION['cartera'] ?? 0); ?>;
            const cargoUsuario = <?php echo (int)$cargoPersonal; ?>;

            const carteraSelect = document.getElementById('id_tabla');

            function cargarOpciones() {

                let tieneCartera59 = carteraSesion === 59;
                let tieneCartera63 = carteraSesion === 63;

                fetch(`get_asignacion.php?userId=${userId}`)
                    .then(response => response.json())
                    .then(data => {

                        data.forEach(item => {

                            const option = document.createElement('option');
                            option.value = item.id_tabla;
                            option.textContent = item.cartera;
                            option.dataset.idCartera = item.id_cartera;

                            carteraSelect.appendChild(option);

                            const idCartera = parseInt(item.id_cartera);

                            if (idCartera === 59) {
                                tieneCartera59 = true;
                            }

                            if (idCartera === 63) {
                                tieneCartera63 = true;
                            }

                        });

                        if (tieneCartera59) {

                            const registroVisitaLink = document.querySelector('.registro-visita-oficina');
                            const registroOperativoLink = document.querySelector('.registro-operativo');

                            if (registroVisitaLink) {
                                registroVisitaLink.classList.remove('hide-html-element');

                                registroVisitaLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    window.location.href = `formRegistroVisitaOficina.php?id_usuario=${userId}`;
                                });
                            }

                            if (registroOperativoLink) {
                                registroOperativoLink.classList.remove('hide-html-element');

                                registroOperativoLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    window.location.href = `formRegistroOperativo.php?id_usuario=${userId}`;
                                });
                            }
                        }

                        if (tieneCartera63) {

                            const gestionCuentasLink = document.querySelector('.gestion-cuentas');
                            const gestionCuentasTexto = document.querySelector('.gestion-cuentas-texto');

                            const esAdmin = cargosAdmin.includes(cargoUsuario);

                            if (gestionCuentasLink && gestionCuentasTexto) {
                                gestionCuentasLink.classList.remove('hide-html-element');

                                if (esAdmin) {
                                    gestionCuentasTexto.textContent = 'Asignar Cuentas';

                                    gestionCuentasLink.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        window.location.href = `asignarCuentas.php?id_usuario=${userId}`;
                                    });
                                } else {
                                    gestionCuentasTexto.textContent = 'Mis Cuentas';

                                    gestionCuentasLink.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        window.location.href = `misCuentas.php?id_usuario=${userId}`;
                                    });
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            cargarOpciones();

            const consultaInformacionLink = document.querySelector('.consulta-informacion');

            consultaInformacionLink.addEventListener('click', function(e) {
                e.preventDefault();

                const selectedOption = carteraSelect.options[carteraSelect.selectedIndex];

                const value = selectedOption.value;
                const textContent = selectedOption.textContent;
                const dataset = selectedOption.dataset.idCartera;

                window.location.href = `consulta_cuentas.php?id_tabla=${value}&id_cartera=${dataset}`;
            });

            /*************************** RUTA DE ASESOR (NEW) OK ******************************/
            const rutaAsesorLink = document.querySelector('.ruta-asesor');

            const idPersonal = <?php echo $_SESSION['id']; ?>;

            rutaAsesorLink.addEventListener('click', function(e) {
                e.preventDefault();

                window.location.href = `ruta_asesor.php?id_usuario=${idPersonal}`;
            });
            /*************************** FIN RUTA DE ASESOR ******************************/

            /*************************** RUTA DE ASESOR (VISTA SUPERVISOR) (NEW) ******************************/
            const adminIds = [14, 17, 21, 22, 25, 27, 38, 44, 49, 71, 78, 173, 185, 186, 202, 203, 207, 240, 297, 348, 352, 410, 411, 413, 470, 478, 480, 482, 515, 647, 655, 668, 735, 765, 769, 788, 793, 802, 813, 820, 861, 1275, 1316, 1391, 1449, 259]

            const rutaSupervisorLink = document.querySelector('.ruta-supervisor');

            if (adminIds.includes(idPersonal)) {
                rutaSupervisorLink.classList.remove('hide-html-element')
                rutaSupervisorLink.addEventListener('click', function(e) {
                    e.preventDefault();

                    window.location.href = `ruta_supervisor.php?id_usuario=${idPersonal}`;
                });
            }
            /*************************** FIN RUTA DE ASESOR (VISTA SUPERVISOR) ******************************/

            /*************************** REGISTRO VISITA OFICINA ******************************/
            const registroVisitaLink = document.querySelector('.registro-visita-oficina');
            if (adminIds.includes(idPersonal)) {
                registroVisitaLink.classList.remove('hide-html-element');
                registroVisitaLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = `formRegistroVisitaOficina.php?id_usuario=${idPersonal}`;
                });
            }

            /*************************** REGISTRO OPERATIVO ******************************/
            const registroOperativoLink = document.querySelector('.registro-operativo');
            if (adminIds.includes(idPersonal)) {
                registroOperativoLink.classList.remove('hide-html-element');
                registroOperativoLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = `formRegistroOperativo.php?id_usuario=${idPersonal}`;
                });
            }
        });
    </script>

    <!-- VERIFICAR TOKEN -->
    <script src="verificarToken.js"></script>
</body>

</html>