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
$nombreCompleto = htmlspecialchars((string)($_SESSION['nombreCompleto'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
$primerNombre = trim(explode(' ', (string)($_SESSION['nombreCompleto'] ?? 'Usuario'))[0] ?? 'Usuario');
$primerNombre = htmlspecialchars($primerNombre !== '' ? $primerNombre : 'Usuario', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú | GeoCampo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        :root {
            --cyc-red: #ff151f;
            --cyc-red-dark: #c90f17;
            --cyc-red-soft: #fff1f2;
            --cyc-gray: #8d8d8d;
            --cyc-gray-dark: #3f3f46;
            --cyc-black: #111827;
            --cyc-muted: #64748b;
            --cyc-line: #e5e7eb;
            --cyc-bg: #f5f6f8;
            --cyc-card: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Montserrat', Arial, sans-serif;
            color: var(--cyc-black);
            background: var(--cyc-bg);
        }

        .app-shell {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding: 20px 18px 36px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            flex: 0 0 auto;
        }

        .brand-mark span {
            border-radius: 3px;
        }

        .brand-mark span:nth-child(1),
        .brand-mark span:nth-child(4) {
            background: var(--cyc-red);
        }

        .brand-mark span:nth-child(2) {
            background: #b3b3b3;
        }

        .brand-mark span:nth-child(3) {
            background: #7b7b7b;
        }

        .brand-title strong {
            display: block;
            font-size: 1rem;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: .035em;
        }

        .brand-title small {
            display: block;
            margin-top: 3px;
            color: var(--cyc-muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 15px;
            border-radius: 14px;
            border: 1px solid #fecdd3;
            background: #fff;
            color: var(--cyc-red-dark);
            font-size: .86rem;
            font-weight: 800;
            text-decoration: none;
            transition: .18s ease;
            white-space: nowrap;
        }

        .logout-btn:hover,
        .logout-btn:focus {
            text-decoration: none;
            color: #fff;
            background: var(--cyc-red);
            border-color: var(--cyc-red);
            box-shadow: 0 12px 28px rgba(255, 21, 31, .18);
            outline: none;
        }

        .header-card {
            display: grid;
            grid-template-columns: 1fr minmax(280px, 380px);
            gap: 18px;
            align-items: center;
            padding: 22px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid var(--cyc-line);
            box-shadow: 0 18px 45px rgba(17, 24, 39, .06);
        }

        .header-copy {
            min-width: 0;
        }

        .menu-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--cyc-red-soft);
            color: var(--cyc-red-dark);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .header-card h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2.6vw, 2.1rem);
            line-height: 1.15;
            font-weight: 800;
        }

        .header-card p {
            margin: 8px 0 0;
            color: var(--cyc-muted);
            font-size: .92rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .selector-card {
            padding: 16px;
            border-radius: 20px;
            background: #fafafa;
            border: 1px solid var(--cyc-line);
        }

        .selector-card label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 9px;
            color: #374151;
            font-size: .8rem;
            font-weight: 800;
        }

        .selector-card label i {
            color: var(--cyc-red);
        }

        #id_tabla {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            border: 1px solid var(--cyc-line);
            background: #fff;
            color: #111827;
            font-weight: 800;
            font-size: .9rem;
            box-shadow: none;
            transition: .18s ease;
        }

        #id_tabla:focus {
            border-color: var(--cyc-red);
            box-shadow: 0 0 0 4px rgba(255, 21, 31, .10);
            outline: none;
        }

        #id_tabla:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: wait;
        }

        .notice {
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 16px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: .86rem;
            font-weight: 800;
        }

        .notice i {
            color: #f97316;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 18px 2px 12px;
        }

        .section-title h2 {
            margin: 0;
            font-size: 1.03rem;
            font-weight: 800;
        }

        .section-title small {
            color: var(--cyc-muted);
            font-size: .78rem;
            font-weight: 700;
        }

        .custom-list-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .custom-list-group-item {
            display: grid;
            grid-template-columns: 44px 1fr 24px;
            gap: 13px;
            align-items: center;
            min-height: 92px;
            padding: 16px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid var(--cyc-line);
            color: var(--cyc-black);
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(17, 24, 39, .045);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
        }

        .custom-list-group-item:hover,
        .custom-list-group-item:focus {
            color: var(--cyc-black);
            text-decoration: none;
            transform: translateY(-2px);
            border-color: rgba(255, 21, 31, .38);
            box-shadow: 0 18px 38px rgba(17, 24, 39, .08);
            outline: none;
        }

        .custom-list-group-item[aria-disabled="true"] {
            opacity: .65;
            pointer-events: none;
            cursor: wait;
        }

        .menu-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: var(--cyc-red-soft);
            color: var(--cyc-red);
            font-size: 1.05rem;
            flex: 0 0 auto;
        }

        .menu-text strong {
            display: block;
            margin-bottom: 4px;
            font-size: .94rem;
            line-height: 1.2;
            font-weight: 800;
        }

        .menu-text small {
            display: block;
            color: var(--cyc-muted);
            font-size: .77rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .menu-arrow {
            justify-self: end;
            color: #9ca3af;
            transition: .18s ease;
        }

        .custom-list-group-item:hover .menu-arrow,
        .custom-list-group-item:focus .menu-arrow {
            color: var(--cyc-red);
            transform: translateX(3px);
        }

        .hide-html-element {
            display: none !important;
        }

        .skeleton-card {
            min-height: 92px;
            border-radius: 18px;
            border: 1px solid var(--cyc-line);
            background: linear-gradient(90deg, #eef2f7 25%, #fff 37%, #eef2f7 63%);
            background-size: 400% 100%;
            animation: shimmer 1.1s ease-in-out infinite;
        }

        .toast-message {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 9999;
            max-width: 360px;
            display: none;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 15px;
            border-radius: 16px;
            background: #111827;
            color: #fff;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .22);
            font-size: .86rem;
            font-weight: 700;
        }

        .toast-message.show {
            display: flex;
        }

        .toast-message i {
            margin-top: 2px;
            color: #fca5a5;
        }

        .loading-layer {
            position: fixed;
            inset: 0;
            z-index: 9998;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(245, 246, 248, .68);
            backdrop-filter: blur(4px);
        }

        .loading-layer.show {
            display: flex;
        }

        .loader-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 17px;
            border-radius: 17px;
            background: #fff;
            border: 1px solid var(--cyc-line);
            box-shadow: 0 18px 42px rgba(17, 24, 39, .14);
            color: #374151;
            font-size: .9rem;
            font-weight: 800;
        }

        .spinner {
            width: 23px;
            height: 23px;
            border-radius: 50%;
            border: 3px solid #fee2e2;
            border-top-color: var(--cyc-red);
            animation: spin .75s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }

        @media (max-width: 820px) {
            .header-card {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .app-shell {
                padding: 14px 13px 28px;
            }

            .topbar {
                padding-bottom: 14px;
            }

            .brand-mark {
                width: 38px;
                height: 38px;
            }

            .brand-title strong {
                font-size: .92rem;
            }

            .brand-title small {
                font-size: .66rem;
            }

            .logout-btn {
                width: 42px;
                height: 42px;
                min-height: 42px;
                padding: 0;
                border-radius: 13px;
            }

            .logout-btn span {
                display: none;
            }

            .header-card {
                padding: 18px;
                border-radius: 21px;
            }

            .header-card h1 {
                font-size: 1.38rem;
            }

            .header-card p {
                font-size: .86rem;
            }

            .selector-card {
                padding: 13px;
                border-radius: 17px;
            }

            .notice {
                align-items: flex-start;
                font-size: .78rem;
                line-height: 1.45;
            }

            .section-title {
                align-items: flex-start;
                flex-direction: column;
                gap: 3px;
            }

            .custom-list-group {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .custom-list-group-item {
                min-height: 82px;
                padding: 13px;
                border-radius: 16px;
                grid-template-columns: 40px 1fr 20px;
            }

            .menu-icon {
                width: 40px;
                height: 40px;
                border-radius: 13px;
            }

            .menu-text strong {
                font-size: .88rem;
            }

            .menu-text small {
                font-size: .72rem;
            }

            .toast-message {
                left: 13px;
                right: 13px;
                bottom: 13px;
                max-width: none;
            }
        }
    </style>
</head>

<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="brand-title">
                    <strong>COBRANZAS PERÚ</strong>
                    <small>GeoCampo</small>
                </div>
            </div>
            <a href="logout.php" class="logout-btn" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar sesión</span>
            </a>
        </header>

        <main>
            <section class="header-card">
                <div class="header-copy">
                    <span class="menu-chip"><i class="fas fa-th-large"></i> Menú principal</span>
                    <h1>Hola, <?php echo $primerNombre; ?></h1>
                </div>

                <div class="selector-card">
                    <label for="id_tabla"><i class="fas fa-briefcase"></i> Cartera asignada</label>
                    <select class="form-control" id="id_tabla" name="id_tabla" disabled>
                        <option value="">Cargando carteras...</option>
                    </select>
                </div>
            </section>

            <div class="notice" role="note">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Todo pago debe realizarse en agencias autorizadas. Está prohibido recibir dinero de los clientes.</span>
            </div>

            <div class="section-title">
                <div>
                </div>
            </div>

            <div id="menuSkeleton" class="custom-list-group" aria-hidden="true">
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
            </div>

            <div id="menuGrid" class="custom-list-group" style="display: none;">
                <a href="#" class="custom-list-group-item list-group-item-action consulta-informacion">
                    <span class="menu-icon"><i class="fas fa-search"></i></span>
                    <span class="menu-text">
                        <strong>Consulta de información</strong>
                        <small>Busca cuentas y datos del cliente.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>

                <a href="#" class="custom-list-group-item list-group-item-action ruta-asesor">
                    <span class="menu-icon"><i class="fas fa-route"></i></span>
                    <span class="menu-text">
                        <strong>Ruta asesor</strong>
                        <small>Revisa cuentas programadas y visitas.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>

                <a href="#" class="custom-list-group-item list-group-item-action ruta-supervisor hide-html-element">
                    <span class="menu-icon"><i class="fas fa-user-shield"></i></span>
                    <span class="menu-text">
                        <strong>Control gestión campo</strong>
                        <small>Supervisa rutas y avance operativo.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>

                <a href="#" class="custom-list-group-item list-group-item-action gestion-cuentas hide-html-element">
                    <span class="menu-icon"><i class="fas fa-clipboard-check"></i></span>
                    <span class="menu-text">
                        <strong class="gestion-cuentas-texto"></strong>
                        <small>Administra o revisa cuentas asignadas.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>

                <a href="#" class="custom-list-group-item list-group-item-action registro-visita-oficina hide-html-element">
                    <span class="menu-icon"><i class="fas fa-building"></i></span>
                    <span class="menu-text">
                        <strong>Registro visita a oficina</strong>
                        <small>Registra atención presencial.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>

                <a href="#" class="custom-list-group-item list-group-item-action registro-operativo hide-html-element">
                    <span class="menu-icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="menu-text">
                        <strong>Operativo</strong>
                        <small>Accede al registro operativo.</small>
                    </span>
                    <i class="fas fa-chevron-right menu-arrow"></i>
                </a>
            </div>
        </main>
    </div>

    <div id="loadingLayer" class="loading-layer" aria-live="polite" aria-busy="true">
        <div class="loader-box">
            <span class="spinner"></span>
            <span>Preparando acceso...</span>
        </div>
    </div>

    <div id="toastMessage" class="toast-message" role="alert">
        <i class="fas fa-info-circle"></i>
        <span id="toastText">No se pudo completar la acción.</span>
    </div>

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
            const idPersonal = <?php echo (int)$_SESSION['id']; ?>;
            const adminIds = [14, 17, 21, 22, 25, 27, 38, 44, 49, 71, 78, 173, 185, 186, 202, 203, 207, 240, 297, 348, 352, 410, 411, 413, 470, 478, 480, 482, 515, 647, 655, 668, 735, 765, 769, 788, 793, 802, 813, 820, 861, 1275, 1316, 1391, 1449, 259];

            const carteraSelect = document.getElementById('id_tabla');
            const menuSkeleton = document.getElementById('menuSkeleton');
            const menuGrid = document.getElementById('menuGrid');
            const loadingLayer = document.getElementById('loadingLayer');
            const toastMessage = document.getElementById('toastMessage');
            const toastText = document.getElementById('toastText');

            function showToast(message) {
                toastText.textContent = message;
                toastMessage.classList.add('show');
                window.clearTimeout(showToast.timer);
                showToast.timer = window.setTimeout(() => toastMessage.classList.remove('show'), 3600);
            }

            function setPageLoading(isLoading) {
                loadingLayer.classList.toggle('show', isLoading);
                document.querySelectorAll('.custom-list-group-item').forEach(item => {
                    item.setAttribute('aria-disabled', isLoading ? 'true' : 'false');
                });
            }

            function goTo(url) {
                setPageLoading(true);
                window.location.href = url;
            }

            function finishInitialLoading() {
                menuSkeleton.style.display = 'none';
                menuGrid.style.display = 'grid';
            }

            function cargarOpciones() {
                let tieneCartera59 = carteraSesion === 59;
                let tieneCartera63 = carteraSesion === 63;

                carteraSelect.disabled = true;
                carteraSelect.innerHTML = '<option value="">Cargando carteras...</option>';

                fetch(`get_asignacion.php?userId=${userId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        carteraSelect.innerHTML = '';

                        if (!Array.isArray(data) || data.length === 0) {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'Sin carteras asignadas';
                            carteraSelect.appendChild(option);
                            showToast('No encontramos carteras asignadas para tu usuario.');
                            return;
                        }

                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id_tabla;
                            option.textContent = item.cartera;
                            option.dataset.idCartera = item.id_cartera;
                            carteraSelect.appendChild(option);

                            const idCartera = parseInt(item.id_cartera, 10);
                            if (idCartera === 59) tieneCartera59 = true;
                            if (idCartera === 63) tieneCartera63 = true;
                        });

                        carteraSelect.disabled = false;

                        if (tieneCartera59) {
                            const registroVisitaLink = document.querySelector('.registro-visita-oficina');
                            const registroOperativoLink = document.querySelector('.registro-operativo');

                            if (registroVisitaLink) {
                                registroVisitaLink.classList.remove('hide-html-element');
                                registroVisitaLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    goTo(`formRegistroVisitaOficina.php?id_usuario=${userId}`);
                                });
                            }

                            if (registroOperativoLink) {
                                registroOperativoLink.classList.remove('hide-html-element');
                                registroOperativoLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    goTo(`formRegistroOperativo.php?id_usuario=${userId}`);
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
                                    gestionCuentasTexto.textContent = 'Asignar cuentas';
                                    gestionCuentasLink.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        goTo(`asignarCuentas.php?id_usuario=${userId}`);
                                    });
                                } else {
                                    gestionCuentasTexto.textContent = 'Mis cuentas';
                                    gestionCuentasLink.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        goTo(`misCuentas.php?id_usuario=${userId}`);
                                    });
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('[MENU] Error cargando carteras:', error);
                        carteraSelect.innerHTML = '<option value="">No se pudieron cargar las carteras</option>';
                        showToast('No pudimos cargar tus carteras. Intenta actualizar la página.');
                    })
                    .finally(() => {
                        finishInitialLoading();
                    });
            }

            cargarOpciones();

            const consultaInformacionLink = document.querySelector('.consulta-informacion');
            consultaInformacionLink.addEventListener('click', function(e) {
                e.preventDefault();

                const selectedOption = carteraSelect.options[carteraSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value || !selectedOption.dataset.idCartera) {
                    showToast('Selecciona una cartera para consultar información.');
                    return;
                }

                goTo(`consulta_cuentas.php?id_tabla=${encodeURIComponent(selectedOption.value)}&id_cartera=${encodeURIComponent(selectedOption.dataset.idCartera)}`);
            });

            const rutaAsesorLink = document.querySelector('.ruta-asesor');
            rutaAsesorLink.addEventListener('click', function(e) {
                e.preventDefault();
                goTo(`ruta_asesor.php?id_usuario=${idPersonal}`);
            });

            const rutaSupervisorLink = document.querySelector('.ruta-supervisor');
            if (adminIds.includes(idPersonal)) {
                rutaSupervisorLink.classList.remove('hide-html-element');
                rutaSupervisorLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    goTo(`ruta_supervisor2.php?id_usuario=${idPersonal}`);
                });
            }

            const registroVisitaLink = document.querySelector('.registro-visita-oficina');
            if (adminIds.includes(idPersonal) && registroVisitaLink) {
                registroVisitaLink.classList.remove('hide-html-element');
                registroVisitaLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    goTo(`formRegistroVisitaOficina.php?id_usuario=${idPersonal}`);
                });
            }

            const registroOperativoLink = document.querySelector('.registro-operativo');
            if (adminIds.includes(idPersonal) && registroOperativoLink) {
                registroOperativoLink.classList.remove('hide-html-element');
                registroOperativoLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    goTo(`formRegistroOperativo.php?id_usuario=${idPersonal}`);
                });
            }
        });
    </script>

    <script src="verificarToken.js"></script>
</body>

</html>