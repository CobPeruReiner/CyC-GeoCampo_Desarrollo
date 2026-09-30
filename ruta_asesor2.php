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

$fechaInicial = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruta asesor</title>
    <link rel="stylesheet" href="rutaAsesor.css">
    <link rel="stylesheet" href="ruta_asesor2.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.6.0/js/bootstrap.min.js" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>

<body class="ruta-asesor2-page">
    <main class="ruta-asesor2-shell">
        <header class="ruta-asesor2-hero">
            <div>
                <span class="ruta-asesor2-eyebrow"><i class="fas fa-route"></i> Seguimiento de campo</span>
                <h1>Mis gestiones registradas</h1>
                <p>Consulta tus visitas, evidencias y ubicaciones registradas durante la jornada.</p>
            </div>
            <a href="menu.php" class="ruta-asesor2-back"><i class="fas fa-arrow-left"></i> Volver al menú</a>
        </header>

        <section class="ruta-asesor2-toolbar" aria-label="Filtro de gestiones">
            <div>
                <span class="ruta-asesor2-field-label">Fecha de consulta</span>
                <input id="ruta-asesor2-fecha" type="date" class="form-control ruta_asesor_datepicker" value="<?php echo htmlspecialchars($fechaInicial, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <p><i class="fas fa-info-circle"></i> Las gestiones se muestran según la fecha seleccionada.</p>
        </section>

        <section id="ruta-asesor2-resumen" class="ruta-asesor2-summary" aria-live="polite"></section>

        <section class="ruta-asesor2-panel ruta-asesor2-map-panel">
            <div class="ruta-asesor2-panel-heading">
                <div><span class="ruta-asesor2-panel-icon"><i class="fas fa-map-marked-alt"></i></span><div><h2>Mapa de gestiones</h2><p>Marcadores disponibles cuando la visita cuenta con GPS válido.</p></div></div>
            </div>
            <div id="mapCanvas" aria-label="Mapa de ubicaciones registradas"></div>
        </section>

        <section class="ruta-asesor2-panel ruta-asesor2-history-panel">
            <div class="ruta-asesor2-panel-heading">
                <div><span class="ruta-asesor2-panel-icon"><i class="fas fa-clipboard-list"></i></span><div><h2>Historial de la jornada</h2><p>Selecciona una gestión para consultar su información y evidencias.</p></div></div>
            </div>
            <div id="tabla-gestiones-asesor2"></div>
        </section>
    </main>

    <!-- Incluir el modal -->
    <div id="modalContainer"></div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>

    <!-- Agregar FontAwesome y Bootstrap JavaScript (si es necesario) -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

    <script defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGFBIf_6mCinwpqWw2Q-lHwNmK6u2iMhE"></script>
    <script defer src="ruta_asesor2.js"></script>

    <!-- VERIFICAR TOKEN -->
    <script src="verificarToken.js"></script>
</body>

</html>
