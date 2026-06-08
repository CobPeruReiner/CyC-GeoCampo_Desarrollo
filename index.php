<?php
date_default_timezone_set('America/Lima');
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set("error_log", 'debug.log');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCAMPO COBPERU | Iniciar sesión</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <main class="login-shell">
        <section class="login-card" aria-label="Formulario de inicio de sesión">
            <div class="login-hero" aria-hidden="true">
                <div class="hero-overlay"></div>
                <img src="imagenes/login.jpg" alt="">
                <div class="hero-content">
                    <span class="brand-pill"><i class="fa-solid fa-location-dot"></i> GEOCAMPO</span>
                    <h1>Gestión de campo COBPERU</h1>
                </div>
            </div>

            <div class="login-panel">
                <img class="company-logo" src="imagenes/logo-cobperu.png" alt="Cobranzas Perú">
                <div class="login-header">
                    <div class="brand-mark">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h2>Iniciar sesión</h2>
                    </div>
                </div>

                <form id="login-form" action="login.php" method="post" novalidate>
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" autocomplete="username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contrasena">Contraseña</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar contraseña">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div id="error-message" class="alert-message" role="alert" aria-live="polite" hidden></div>

                    <button type="submit" id="btnLogin" class="btn-login">
                        <span class="btn-content">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Ingresar</span>
                        </span>
                        <span class="btn-loader" aria-hidden="true">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            Validando...
                        </span>
                    </button>
                </form>
            </div>
        </section>
    </main>

    <div class="modal fade" id="modalSesionCerrada" tabindex="-1" aria-labelledby="modalSesionCerradaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content session-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSesionCerradaLabel">
                        <i class="fa-solid fa-shield-halved me-2"></i> Sesión actualizada
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="modal-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <p class="mb-0">Se cerró la sesión anterior. Ahora estás conectado en este dispositivo.</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-dark px-4 py-2 rounded-pill" id="continuarBtn">
                        <i class="fa-solid fa-check-circle me-2"></i> Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="login.js"></script>
</body>

</html>