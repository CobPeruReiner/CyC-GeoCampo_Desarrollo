<?php
date_default_timezone_set('America/Lima');
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set("error_log", 'debug.log');
// require 'test.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCAMPO COBPERU</title>

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tu CSS personalizado -->
    <link rel="stylesheet" href="styles.css">

    <!-- Font Awesome desde CDN (sin CORS) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-yTq3qK4iHaewyFHzQ+GtZJmD7qY51rnkPU/NWB+Xx6iCPZJItXp6ykmK9EoTGlZQY6zzjBK12a+3S3mG3BPpTQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>

<body>
    <!-- Contenedor del formulario de inicio de sesión -->
    <div class="login-container">
        <div class="img-container">
            <img src="imagenes/login.jpg" alt="Imagen de inicio de sesión">
        </div>
        <h2>GCAMPO COBPERU | Iniciar Sesión</h2>
        <form id="login-form" action="login.php" method="post">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
        <div id="error-message" class="error-message"></div>
    </div>

    <!-- Contenedor del dialog de sesión expirada -->
    <div class="modal fade" id="modalSesionCerrada" tabindex="-1" aria-labelledby="modalSesionCerradaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4" style="border: none; font-family: 'Montserrat', sans-serif;">

                <!-- Encabezado elegante en gris oscuro -->
                <div class="modal-header text-white" style="background-color: #343a40; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                    <h5 class="modal-title" id="modalSesionCerradaLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Cambio de Sesión
                    </h5>
                </div>

                <!-- Cuerpo del modal -->
                <div class="modal-body text-center text-secondary">
                    <p class="fs-5 mb-3">
                        Se cerró la sesión anterior. Ahora estás conectado en este dispositivo.
                    </p>
                    <i class="fas fa-user-shield fa-3x text-secondary mb-2"></i>
                </div>

                <!-- Botón personalizado -->
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-dark px-4 py-2 rounded-pill" id="continuarBtn">
                        <i class="fas fa-check-circle me-2"></i> Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap 5 JS (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Login login -->
    <script src="login.js"></script>
</body>

</html>