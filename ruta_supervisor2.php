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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ruta asesor</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap CSS (para que el modal funcione correctamente) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome CDN (sin kit) -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />

    <!-- Estilos propios -->
    <link rel="stylesheet" href="rutaAsesor.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-[1900px] mx-auto p-6 flex flex-col gap-6">
        <!-- HEADER -->
        <div class="bg-white shadow rounded-xl p-4 flex justify-between items-center">
            <div class="head-left flex flex-col md:flex-row items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-blue-600"></i>
                <h1 class="text-lg font-semibold text-gray-700">
                    CONTROL GESTIÓN CAMPO
                </h1>
            </div>

            <div class="head-right flex items-center gap-2 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm transition-all duration-300 cursor-pointer">
                <i class="fa-solid fa-arrow-left"></i>
                <a href="menu.php"
                    class="">
                    Volver al menú
                </a>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="bg-white shadow rounded-xl p-6">
            <form id="ruta-asesor-form">
                <div class="flex flex-col md:flex-row justify-between gap-4 w-full">
                    <div class="relative flex flex-col md:flex-row gap-4">
                        <!-- FECHAS -->
                        <div>
                            <label class="text-sm text-gray-500">Rango de Fechas</label>

                            <div class="flex gap-2">
                                <input id="ruta-asesor-date1"
                                    type="date"
                                    class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm ruta_asesor_datepicker transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    required>

                                <input id="ruta-asesor-date2"
                                    type="date"
                                    class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm ruta_asesor_datepicker transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    required>
                            </div>
                        </div>

                        <!-- CARTERA -->
                        <div class="relative">
                            <label class="text-sm text-gray-500">Cartera</label>
                            <select id="carteras-select"
                                onChange="handleAsesoresCartera(this)"
                                class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">

                            </select>

                            <div id="skeleton-cartera"
                                class="hidden absolute bottom-0 left-0 w-full h-10 rounded-lg bg-gray-200 animate-pulse"></div>
                        </div>

                        <!-- ASESOR -->
                        <div class="relative">
                            <label class="text-sm text-gray-500">Asesor</label>
                            <select id="asesores-cartera-select"
                                onChange="handleAsesor(this)"
                                class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">

                            </select>

                            <div id="skeleton-asesor"
                                class="hidden absolute bottom-0 left-0 w-full h-10 rounded-lg bg-gray-200 animate-pulse"></div>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="flex flex-col md:flex-row items-end gap-2">
                        <button type="submit"
                            class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm w-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Buscar</span>
                        </button>

                        <button type="button"
                            id="ruta_supervisor_btnGaleria"
                            class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm w-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gray-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-images"></i>
                            <span>Galería</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- MAPA + SIDEBAR -->
        <div class="flex flex-row lg:flex-col gap-6">
            <!-- MAPA -->
            <div class="w-[83%] bg-white shadow rounded-xl p-4">
                <h2 class="text-sm font-semibold text-gray-600 mb-3">
                    Mapa de Rutas y Marcadores
                </h2>

                <div id="mapCanvas" class="w-full h-[500px] rounded-lg bg-gray-100"></div>
            </div>


            <!-- SIDEBAR -->
            <div class="w-[17%] space-y-4">
                <!-- BUSCADOR -->
                <div class="bg-white shadow rounded-xl p-4">
                    <div class="container-ti flex flex-col md:flex-row gap-3">
                        <i class="fa-solid fa-location-dot"></i>
                        <h3 class="text-sm font-semibold text-gray-600 mb-3">
                            Buscador de Direcciones
                        </h3>
                    </div>

                    <input id="addressInput"
                        type="text"
                        placeholder="Ej: Av. Reforma 221..."
                        class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm mb-3 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">

                    <button onclick="addMarkerFromAddress()"
                        class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm w-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-plus"></i>
                        <span>Agregar marcador</span>
                    </button>
                </div>

                <!-- INFORME -->
                <div class="flex flex-col gap-3 bg-white shadow rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-600">
                        Informe Personalizado
                    </h3>

                    <select id="informe-personalizado-select"
                        class="border border-gray-300 rounded-lg px-3 py-2 w-full text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        <option value="">Seleccionar opción</option>
                        <option value="59"
                            data-cartera="F CONFIANZA CAMPO"
                            data-cartera-file="FINANCIERA_CONFIANZA_CAMPO">
                            F CONFIANZA CAMPO
                        </option>
                    </select>

                    <button id="btn-exportar-informe"
                        class="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm w-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Exportar Excel</span>
                    </button>

                    <button id="btn-descargar-imagenes"
                        class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 py-2 rounded-lg text-sm w-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        <i class="fa-solid fa-image"></i>
                        <span>Descargar Imágenes</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="bg-white shadow rounded-xl p-4 flex flex-col gap-4">
            <div class="container-tittle-reporte-asesor flex flex-col md:flex-row gap-3">
                <i class="fa-solid fa-table-list text-gray-500"></i>
                <h3 class="text-sm font-semibold text-gray-600">
                    Gestiones del Asesor
                </h3>
            </div>

            <!-- SKELETON -->
            <div id="tabla-skeleton" class="space-y-3 hidden">

                <div class="grid grid-cols-7 gap-4 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                </div>

                <div class="grid grid-cols-7 gap-4 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                </div>

                <div class="grid grid-cols-7 gap-4 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                </div>

            </div>

            <!-- EXPORTAR -->
            <div id="container-exportar" class="flex justify-end mb-3"></div>

            <!-- TABLA REAL -->
            <div id="tabla-gestiones-asesor" class="hidden overflow-x-auto">

                <!-- BUSCADOR -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">

                    <div class="relative w-full md:w-72">

                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-[#8392ab] text-sm"></i>
                        </div>

                        <input
                            type="search"
                            id="searchGestiones"
                            class="block w-full px-9 py-2 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-[#09c] focus:border-[#09c] outline-none"
                            placeholder="Buscar gestiones"
                            autocomplete="off">

                    </div>

                </div>

                <!-- TABLA -->
                <table class="w-full text-[#67748e] text-nowrap text-sm">

                    <thead>
                        <tr class="text-xs text-[#8392ab] text-left uppercase opacity-70 border-b border-[#e9ecef]">

                            <th class="py-3 px-4">Fecha</th>
                            <th class="py-3 px-4">Hora</th>
                            <th class="py-3 px-4">Identificador</th>
                            <th class="py-3 px-4">Efecto</th>
                            <th class="py-3 px-4">Observación</th>
                            <th class="py-3 px-4">Dirección</th>
                            <th class="py-3 px-4">Estado GPS</th>
                            <th class="py-3 px-4 text-center">Acción</th>

                        </tr>
                    </thead>

                    <tbody id="tablaGestionesBody"></tbody>

                </table>

                <!-- PAGINADOR -->
                <nav id="paginationGestiones" class="flex justify-end mt-4"></nav>

            </div>
        </div>
    </div>

    <!-- MODAL VER DETALLES DE LA GESTION -->
    <div id="modalContainer"></div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>

    <!-- Librerías -->
    <script src="./fileSaver.js"></script>

    <!-- jQuery (solo si tu JS lo usa) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- Script principal -->
    <script src="ruta_asesor.js"></script>

    <!-- Google Maps -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGFBIf_6mCinwpqWw2Q-lHwNmK6u2iMhE&libraries=places&callback=initMap">
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Verificar sesión -->
    <script src="verificarToken.js"></script>

    <!--  VISTA SUPERVISOR  -->
    <script>
        const skeleton = document.getElementById('tabla-skeleton');
        const tablaGestiones = document.getElementById('tabla-gestiones-asesor');

        skeleton.classList.remove('hidden');

        let allGestiones = [];
        let filteredGestiones = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        const setAsignedCarteras = () => {
            const skeleton = document.getElementById('skeleton-cartera');
            const select = document.getElementById('carteras-select');

            skeleton.classList.remove('hidden');
            select.disabled = true;

            const currentSearchData = window.location.search;
            const params = new URLSearchParams(currentSearchData);
            const idPersonal = params.get('id_usuario');

            fetch(`get_asignacion.php?userId=${idPersonal}`)
                .then(res => res.json())
                .then(data => {

                    showCarterasCheckboxes(data);

                })
                .finally(() => {

                    skeleton.classList.add('hidden');
                    select.disabled = false;

                });
        }

        setAsignedCarteras()

        let currentIdCartera;
        let currentCartera;
        let currentCarteraFile;

        // ============= INICIO DESCARGAR PRIMERA Y ULTIMA GESTION =============
        let informeEjecutado = false;

        const btnExportar = document.getElementById('btn-exportar-informe');
        const btnDescargarImg = document.getElementById('btn-descargar-imagenes');

        btnExportar.addEventListener('click', async () => {

            btnExportar.disabled = true;

            btnDescargarImg.disabled = true;
            informeEjecutado = false;

            try {

                const cartera = document.getElementById('informe-personalizado-select').value;
                const fecha1 = document.getElementById('ruta-asesor-date1').value;
                const fecha2 = document.getElementById('ruta-asesor-date2').value;
                const asesor = currentIdAsesor;

                if (!cartera) {
                    alert('Seleccione una cartera');
                    return;
                }

                if (!fecha1 || !fecha2) {
                    alert('Seleccione el rango de fechas');
                    return;
                }

                if (new Date(fecha1) > new Date(fecha2)) {
                    alert('La fecha inicio no puede ser mayor a la fecha fin');
                    return;
                }

                if (!asesor) {
                    alert('Seleccione un asesor');
                    return;
                }

                const response = await fetch('get_informe_personalizadoRutaAsesor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cartera,
                        fecha1,
                        fecha2,
                        asesor
                    })
                });

                const data = await response.json();

                if (!data.rows || !data.rows.length) {
                    alert('No se encontraron registros');
                    return;
                }

                informeEjecutado = true;
                btnDescargarImg.disabled = false;

                downloadAsExcel(
                    data.rows,
                    data.rows[0].CARTERA,
                    asesor === 'TODOS' ?
                    'TODOS LOS ASESORES' :
                    data.rows[0].NOMBRE_PERSONAL
                );

            } catch (error) {

                console.error("Error exportando:", error);
                alert("Ocurrió un error al generar el informe");

            } finally {
                btnExportar.disabled = false;
            }
        });

        btnDescargarImg.addEventListener('click', () => {
            if (!informeEjecutado) return;
            window.open('download_imagenes_informe.php', '_blank');
        });

        // ============== FIN DESCARGAR PRIMERA Y ULTIMA GESTION ==============

        const showCarterasCheckboxes = carteras => {
            const carteraSelect = document.getElementById('carteras-select');

            currentIdCartera = carteras[0].id_cartera;
            currentCartera = carteras[0].cartera;
            currentCarteraFile = carteras[0].id_tabla;

            handleAsesoresCartera()

            carteras.forEach(e => {
                const option = document.createElement("option");
                option.value = e.id_cartera;
                option.text = e.cartera;
                option.dataset.carteraFile = e.id_tabla;
                carteraSelect.appendChild(option);
            })
        }

        const handleSort = (a, b) => a.NOMBRE.localeCompare(b.NOMBRE);

        const handleAsesoresCartera = (selectEl = null) => {
            const skeleton = document.getElementById('skeleton-asesor');
            const select = document.getElementById('asesores-cartera-select');

            skeleton.classList.remove('hidden');
            select.disabled = true;

            if (selectEl && selectEl.value) {
                const opt = selectEl.options[selectEl.selectedIndex];

                currentIdCartera = opt.value;
                currentCartera = opt.text;
                currentCarteraFile = opt.dataset.carteraFile;

            }

            fetch(`get_asesores_by_cartera.php?idcartera=${currentIdCartera}`)
                .then(res => res.json())
                .then(data => {
                    select.innerHTML = '';

                    const optionTodos = document.createElement('option');
                    optionTodos.value = 'TODOS';
                    optionTodos.text = 'TODOS LOS ASESORES';

                    select.appendChild(optionTodos);

                    data.sort(handleSort).forEach(e => {
                        const option = document.createElement('option');
                        option.value = e.IDPERSONAL;
                        option.text = e.NOMBRE;

                        select.appendChild(option);
                    });

                    handleAsesor({
                        value: 'TODOS'
                    });
                })
                .finally(() => {
                    skeleton.classList.add('hidden');
                    select.disabled = false;
                });
        };

        const informeSelect = document.getElementById('informe-personalizado-select');

        if (informeSelect) {
            informeSelect.addEventListener('change', function() {

                if (!this.value) return;

                handleAsesoresCartera(this);

                informeEjecutado = false;
                btnDescargarImg.disabled = true;
            });
        }

        let currentIdAsesor = 0;
        let currentAsesor = '';

        const handleAsesor = e => {
            if (e.value === 'TODOS') {
                currentIdAsesor = 'TODOS';
                currentAsesor = 'TODOS LOS ASESORES';
                return;
            }

            if (e.value) {
                currentIdAsesor = e.value;
                currentAsesor = e.options[e.selectedIndex].text;
            } else {
                currentIdAsesor = e.IDPERSONAL;
                currentAsesor = e.NOMBRE;
            }
        }

        /************** INSERT HTML ELEMENT BEFORE *************/
        const insertBeforeElement = (el, newEl) => {
            el.parentNode.insertBefore(newEl, el);
        };

        const insertBefore = (el, newEl) => {
            if (typeof newEl === 'string') {
                el.insertAdjacentHTML('beforebegin', newEl);
            } else {
                insertBeforeElement(el, newEl);
            }
        };

        const createElementIfNotExists = (id, content, dataArray) => {
            let btn = document.getElementById(id);

            const container = document.getElementById('container-exportar');

            if (!btn) {

                btn = document.createElement('button');

                btn.id = id;

                btn.className =
                    "flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-green-500";

                btn.innerHTML = `
                    <i class="fa-solid fa-file-excel"></i>
                    <span>${content}</span>
                `;

                container.appendChild(btn);
            }

            btn.onclick = () => {
                downloadAsExcel(
                    dataArray,
                    currentCartera,
                    currentAsesor
                );
            };
        };

        const gpsEstadoIconMap = {
            CONFIABLE: `<i class="fa-solid fa-location-dot text-green-500" title="Confiable"></i>`,
            ACEPTABLE: `<i class="fa-solid fa-location-dot text-yellow-500" title="Aceptable"></i>`,
            BAJA_PRECISION: `<i class="fa-solid fa-triangle-exclamation text-orange-500" title="Baja precisión"></i>`,
            NO_CONFIABLE: `<i class="fa-solid fa-circle-xmark text-red-500" title="No confiable"></i>`,
            SIN_GPS: `<i class="fa-solid fa-location-slash text-gray-400" title="Sin GPS"></i>`
        };

        function getGpsEstadoIcon(estado) {
            return gpsEstadoIconMap[estado] ?? `<i class="fa-solid fa-question text-gray-400"></i>`;
        }

        /************** END INSERT HTML ELEMENT BEFORE *************/

        // BLOQUEAR CAMPOS DEL FORMULARIO

        const toggleFormState = (disabled) => {
            const form = document.getElementById('ruta-asesor-form');

            const elements = form.querySelectorAll('input, select, button');

            elements.forEach(el => {
                el.disabled = disabled;
            });
        }

        const handleSearchAsesorGestiones = () => {
            const rutaSupervisorForm = document.getElementById('ruta-asesor-form');

            const skeleton = document.getElementById('tabla-skeleton');
            const tablaGestiones = document.getElementById('tabla-gestiones-asesor');

            rutaSupervisorForm.addEventListener('submit', e => {

                e.preventDefault();

                toggleFormState(true);

                skeleton.classList.remove('hidden');

                tablaGestiones.classList.add('hidden');

                const date1 = document.getElementById('ruta-asesor-date1');
                const date2 = document.getElementById('ruta-asesor-date2');
                const asesorSelect = document.getElementById('asesores-cartera-select');

                fetch(`get_gestiones_asesor_2.php?idPersonal=${asesorSelect.value}&fecha=${date1.value}&fecha2=${date2.value}&idCartera=${currentIdCartera}`)
                    .then(response => response.json())
                    .then(data => {

                        const resultArray = data.result;

                        skeleton.classList.add('hidden');

                        if (resultArray && resultArray.length) {

                            // 🔹 cargar datos para el paginado
                            allGestiones = resultArray;
                            filteredGestiones = resultArray;

                            currentPage = 1;

                            tablaGestiones.classList.remove('hidden');

                            renderGestionesTable();

                            // EXPORTAR
                            const newArray = resultArray.map(({
                                FECHA,
                                IDENTIFICADOR,
                                IDEFECTO,
                                EFECTO,
                                MOTIVO,
                                CONTACTO,
                                OBSERVACION,
                                DIRECCION_DEPURADA,
                                ASESOR,
                                NOMCONTACTO,
                                PISOS,
                                PUERTA,
                                FACHADA,
                                FECHA_PROMESA,
                                MONTO_PROMESA,
                                cartera,
                                latitud,
                                longitud,
                                NOMBRE_GEOCAMPO_ESTADO_GPS
                            }) => ({
                                FECHA,
                                IDENTIFICADOR,
                                IDEFECTO,
                                EFECTO,
                                MOTIVO,
                                CONTACTO,
                                OBSERVACION,
                                DIRECCION_DEPURADA,
                                ASESOR,
                                NOMCONTACTO,
                                PISOS,
                                PUERTA,
                                FACHADA,
                                FECHA_PROMESA,
                                MONTO_PROMESA,
                                cartera,
                                latitud,
                                longitud,
                                NOMBRE_GEOCAMPO_ESTADO_GPS
                            }));

                            createElementIfNotExists(
                                'btn-gestiones-exportar',
                                'Exportar Registros',
                                newArray
                            );

                            // MAPA
                            const gestionesWithLocation = resultArray.filter(e => e.latitud !== '');

                            initMap(gestionesWithLocation);

                        } else {

                            const currentMainMap = document.querySelector("#mapCanvas");

                            currentMainMap.classList.add('hidden');

                            const tbody = document.getElementById("tablaGestionesBody");

                            if (tbody) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-gray-400">
                                            Sin registros
                                        </td>
                                    </tr>
                                `;
                            }
                        }

                    })
                    .catch(err => {

                        console.log('Error vista super: ', err);

                        skeleton.classList.add('hidden');

                        const tbody = document.getElementById("tablaGestionesBody");

                        if (tbody) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-red-500">
                                        Error cargando datos
                                    </td>
                                </tr>
                            `;
                        }

                    })
                    .finally(() => {

                        toggleFormState(false);

                    });

            });
        }

        handleSearchAsesorGestiones()

        // ============================================ PAGINADO ============================================

        function renderGestionesTable() {

            const tbody = document.getElementById("tablaGestionesBody");
            tbody.innerHTML = "";

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            const pageData = filteredGestiones.slice(start, end);

            if (!pageData.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-400">
                        Sin registros
                        </td>
                    </tr>
                `;
                return;
            }

            pageData.forEach(g => {

                const [fecha, hora] = g.FECHA.split(" ");

                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 text-xs">
                        <td class="py-3 px-4">${fecha}</td>
                        <td class="py-3 px-4">${hora ?? ""}</td>
                        <td class="py-3 px-4">${g.IDENTIFICADOR ?? ""}</td>
                        <td class="py-3 px-4">${g.EFECTO ?? ""}</td>
                        <td class="py-3 px-4">${g.OBSERVACION ?? ""}</td>
                        <td class="py-3 px-4">${g.DIRECCION_DEPURADA ?? ""}</td>
                        <td class="py-3 px-4 text-center cursor-pointer">
                            ${getGpsEstadoIcon(g.NOMBRE_GEOCAMPO_ESTADO_GPS)}
                        </td>
                        <td class="py-3 px-4 text-center">
                        <button class="text-blue-500"
                        onclick='openGestionModal(${JSON.stringify(g).replace(/'/g,"&apos;")})'>
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        </td>
                    </tr>
                `;
            });

            renderPaginationGestiones();
        }

        function renderPaginationGestiones() {
            const pagination = document.getElementById("paginationGestiones");
            const totalPages = Math.ceil(filteredGestiones.length / rowsPerPage);

            pagination.innerHTML = "";

            if (totalPages <= 1) return;

            const maxVisible = 7;

            let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let end = start + maxVisible - 1;

            if (end > totalPages) {
                end = totalPages;
                start = Math.max(1, end - maxVisible + 1);
            }

            const btn = (label, page, disabled = false, active = false) => `
                <button
                class="px-3 py-1 mx-1 border rounded text-sm
                ${active ? "bg-cyan-600 text-white" : "bg-white text-gray-600"}
                ${disabled ? "opacity-50 cursor-not-allowed" : "hover:bg-gray-100"}"
                ${disabled ? "disabled" : ""}
                onclick="goToPageGestiones(${page})">
                ${label}
                </button>
            `;

            // anterior
            pagination.innerHTML += btn("‹", currentPage - 1, currentPage === 1);

            // primera página
            if (start > 1) {
                pagination.innerHTML += btn(1, 1);

                if (start > 2) {
                    pagination.innerHTML += `<span class="px-2">...</span>`;
                }
            }

            // páginas centrales
            for (let i = start; i <= end; i++) {
                pagination.innerHTML += btn(i, i, false, i === currentPage);
            }

            // última página
            if (end < totalPages) {

                if (end < totalPages - 1) {
                    pagination.innerHTML += `<span class="px-2">...</span>`;
                }

                pagination.innerHTML += btn(totalPages, totalPages);
            }

            // siguiente
            pagination.innerHTML += btn("›", currentPage + 1, currentPage === totalPages);
        }

        function goToPageGestiones(page) {

            const totalPages = Math.ceil(filteredGestiones.length / rowsPerPage);

            if (page < 1 || page > totalPages) return;

            currentPage = page;

            renderGestionesTable();
        }

        document.getElementById("searchGestiones")
            .addEventListener("input", e => {

                const term = e.target.value.toLowerCase();

                filteredGestiones = allGestiones.filter(g =>
                    Object.values(g).some(v =>
                        String(v).toLowerCase().includes(term)
                    )
                );

                currentPage = 1;

                renderGestionesTable();
            });

        function openGestionModal(rowData) {

            fetch(`get_gestiones_detalle_asesorTailwind.php?carteraFile=${currentCarteraFile}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    body: JSON.stringify(rowData)
                })
                .then(response => response.json())
                .then(data => {

                    const modalContainer = document.getElementById('modalContainer');

                    modalContainer.innerHTML = data.html;

                    const modalElement = document.getElementById('modalData');

                    const modal = new bootstrap.Modal(modalElement);

                    modal.show();

                })
                .catch(err => {
                    console.error("Error cargando detalle:", err);
                });

        }

        /********************************************* GALERIA ************************************************/
        const idPersonal = <?php echo $_SESSION['id']; ?>;
        const date = document.getElementById('ruta-asesor-date1')
        const date2 = document.getElementById('ruta-asesor-date2')
        const btnGaleria = document.getElementById('ruta_supervisor_btnGaleria');

        const asesoresCarteraSelect = document.getElementById('asesores-cartera-select');

        btnGaleria.addEventListener('click', function(e) {

            let ventanaGaleria = window.open(
                `get_galeria_asesor.php?idPersonal=${currentIdAsesor}&date1=${date.value}&date2=${date2.value}&currentIdCartera=${currentIdCartera}&asesor=${currentAsesor}&cartera=${currentCartera}&carteraFile=${currentCarteraFile}`,
                '_blank');

        });
    </script>
</body>
<script src="./exportData.js"></script>

</html>