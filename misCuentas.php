<?php

/**
 * GEOCAMPO - Hoja de Ruta / Mis Registros
 * Recibe: misCuentas.php?id_usuario=IDPERSONAL
 */
date_default_timezone_set('America/Lima');
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$idUsuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : (int)($_SESSION['id'] ?? 0);
if ($idUsuario <= 0) {
  http_response_code(400);
  echo 'No se recibió un asesor válido.';
  exit;
}

$_SESSION['id_mis_cuentas'] = $idUsuario;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hoja de Ruta - Mis Registros</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body {
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .scroll-suave {
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }

    .scroll-suave::-webkit-scrollbar {
      height: 8px;
      width: 8px;
    }

    .scroll-suave::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }

    .ruta-linea::before {
      content: '';
      position: absolute;
      left: 12px;
      top: 28px;
      bottom: -26px;
      width: 1px;
      border-left: 1px dashed #d7dde8;
    }

    .ruta-linea:last-child::before {
      display: none;
    }


    :root {
      --corp-red: #FF161A;
      --corp-dark-red: #8B0000;
      --corp-red-hover: #A7191F;
      --corp-gray: #A6A6A6;
      --corp-dark-gray: #7F7F7F;
      --corp-bg: #F4F6F8;
    }

    .corporate-header {
      background: linear-gradient(135deg, var(--corp-dark-red), var(--corp-red-hover));
      color: #ffffff;
      border-radius: 1rem;
      box-shadow: 0 12px 28px rgba(139, 0, 0, 0.18);
    }

    .corporate-header h1 {
      color: #ffffff;
    }

    .corporate-back {
      background: rgba(255, 255, 255, 0.10);
      border-color: rgba(255, 255, 255, 0.28);
      color: #ffffff;
    }

    .corporate-back:hover {
      background: rgba(255, 255, 255, 0.18);
    }

  </style>
</head>

<body class="bg-[#F4F6F8] text-gray-900">

  <main class="max-w-[1700px] mx-auto px-6 py-8">
    <input type="hidden" id="idUsuario" value="<?= htmlspecialchars((string)$idUsuario, ENT_QUOTES, 'UTF-8') ?>">

    <header class="corporate-header h-[82px] flex items-center px-6 mb-6">
      <div class="flex items-center gap-4">
        <button type="button" onclick="history.back()" class="corporate-back inline-flex items-center gap-2 border rounded-lg px-4 py-2 text-sm transition">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          Volver
        </button>
        <h1 class="ml-5 text-xl font-bold tracking-tight">Hoja de Ruta - Mis Registros</h1>
      </div>
    </header>

    <main class="p-3 md:p-6 space-y-5">
      <section class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i data-lucide="sliders-horizontal" class="w-4 h-4 text-[#FF161A]"></i>
            <h2 class="text-sm font-bold">Filtros de Mis Cuentas Asignadas</h2>
          </div>
          <div class="text-xs text-gray-400">
            Asesor: <strong id="nombreAsesor" class="text-gray-500">Cargando...</strong>
          </div>
        </div>

        <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[170px_170px_170px_170px_170px_1fr] gap-3 items-end">
          <div class="space-y-1.5">
            <label for="filtroFechaRuta" class="text-xs text-gray-500">Semana de Ruta</label>
            <input id="filtroFechaRuta" type="date" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
          </div>

          <div class="space-y-1.5">
            <label for="filtroDistrito" class="text-xs text-gray-500">Distrito</label>
            <select id="filtroDistrito" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
              <option value="">Todos</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label for="filtroProducto" class="text-xs text-gray-500">Producto / Segmento</label>
            <select id="filtroProducto" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
              <option value="">Todos</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label for="filtroEstadoVisita" class="text-xs text-gray-500">Estado de Visita</label>
            <select id="filtroEstadoVisita" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
              <option value="">Todos</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label for="filtroRangoImporte" class="text-xs text-gray-500">Rango de Importe</label>
            <select id="filtroRangoImporte" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
              <option value="">Todos</option>
              <option value="0-500">S/ 0 a S/ 500</option>
              <option value="500-1000">S/ 500 a S/ 1,000</option>
              <option value="1000-5000">S/ 1,000 a S/ 5,000</option>
              <option value="5000+">Mayor a S/ 5,000</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label for="criterioRuta" class="flex items-center gap-1.5 text-xs font-semibold text-[#8B0000]">
              <i data-lucide="route" class="w-3.5 h-3.5"></i>
              Criterio de Ruta
            </label>
            <select id="criterioRuta" class="w-full h-10 rounded-xl bg-red-50 border border-red-200 px-3 text-xs text-[#8B0000] outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
              <option value="CERCANIA">Por Proximidad</option>
              <option value="DISTRITO">Por Distrito</option>
              <option value="MONTO_MAYOR">Montos Mayores</option>
              <option value="MONTO_MENOR">Montos Menores</option>
              <option value="PERSONALIZADO">Personalizado</option>
            </select>
          </div>
        </div>
      </section>

      <section id="panelPendientes" class="hidden bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
              <i data-lucide="calendar-clock" class="w-4 h-4"></i>
            </div>
            <div>
              <p class="text-sm font-bold text-amber-900">Pendientes dentro de ruta semanal</p>
              <p id="textoPendientes" class="text-xs text-amber-700 mt-0.5">Puedes reprogramarlas dentro de la misma semana.</p>
            </div>
          </div>
          <button id="btnAgregarPendientes" type="button" class="h-9 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold flex items-center justify-center gap-2">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            Ver pendientes
          </button>
        </div>
      </section>

      <section class="grid grid-cols-1 xl:grid-cols-[1fr_350px] gap-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
          <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <i data-lucide="clipboard-list" class="w-4 h-4 text-[#FF161A]"></i>
              <h2 class="text-sm font-bold">Mis Cuentas Asignadas</h2>
              <span id="totalCuentas" class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">0</span>
            </div>
            <div class="text-xs text-gray-500">
              Criterio activo: <strong id="criterioActivo">Por Proximidad</strong>
            </div>
          </div>

          <div class="overflow-x-auto scroll-suave">
            <table class="min-w-[980px] w-full text-xs">
              <thead class="bg-gray-50 text-gray-500">
                <tr>
                  <th class="px-4 py-3 w-10 text-left"><input id="checkTodos" type="checkbox" class="rounded border-gray-300 accent-[#FF161A]"></th>
                  <th class="px-3 py-3 text-left">Cliente / Cuenta</th>
                  <th class="px-3 py-3 text-left">Dirección Original / Dirección Sugerida</th>
                  <th class="px-3 py-3 text-left">Distrito / Producto</th>
                  <th class="px-3 py-3 text-right">Importe</th>
                  <th class="px-3 py-3 text-left">Coord. Status</th>
                  <th class="px-3 py-3 text-left">Estado Visita</th>
                  <th class="px-3 py-3 text-left">Visitas Semana</th>
                  <th class="px-3 py-3 text-left">Acción</th>
                </tr>
              </thead>
              <tbody id="tablaCuentas" class="divide-y divide-gray-100"></tbody>
            </table>
          </div>

          <div class="px-4 py-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-[#8392ab]">
              <select id="cuentasPorPagina" class="h-8 rounded border border-gray-200 bg-white px-2 outline-none">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span>cuentas por página</span>
            </div>
            <div class="flex-1 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <div id="paginacionInfo" class="text-sm text-[#8392ab]">Mostrando 0 a 0 de 0 cuentas</div>
              <div id="paginacionItems" class="flex justify-end gap-1"></div>
            </div>
          </div>
        </div>

        <aside class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 h-fit">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <i data-lucide="route" class="w-4 h-4 text-[#FF161A]"></i>
              <h2 class="text-sm font-bold">Vista Previa de Ruta Semanal</h2>
            </div>
            <span id="totalParadas" class="text-xs bg-[#8B0000] text-white px-2 py-0.5 rounded-full">0 Paradas</span>
          </div>

          <div id="vistaRuta" class="space-y-3 max-h-[420px] overflow-y-auto scroll-suave pr-1"></div>

          <div class="mt-5 space-y-3">
            <button id="btnCrearRuta" type="button" disabled class="w-full h-11 rounded-2xl bg-[#8B0000] hover:bg-[#A7191F] text-white text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50 disabled:hover:bg-[#8B0000] disabled:cursor-not-allowed">
              <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
              Crear Hoja de Ruta
            </button>
            <button id="btnMapa" type="button" disabled class="w-full h-9 rounded-xl border border-gray-200 hover:bg-gray-50 text-xs font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
              <i data-lucide="map" class="w-4 h-4"></i>
              Ver en Mapa Completo
            </button>
            <button id="btnValidarMasivoRuta" type="button" disabled class="w-full h-9 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-[#8B0000] text-xs font-semibold flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
              <i data-lucide="map-pin-check" class="w-4 h-4"></i>
              Validar direcciones
            </button>
            <div class="grid grid-cols-2 gap-3">
              <button id="btnLimpiar" type="button" disabled class="h-9 rounded-xl border border-gray-200 hover:bg-gray-50 text-xs text-gray-500 flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                Limpiar
              </button>
              <button id="btnReordenar" type="button" disabled class="h-9 rounded-xl border border-gray-200 hover:bg-gray-50 text-xs flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="arrow-up-down" class="w-3.5 h-3.5"></i>
                Reordenar
              </button>
            </div>
          </div>
        </aside>
      </section>
    </main>

    <div id="modalDireccion" class="fixed inset-0 hidden items-center justify-center bg-black/40 z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-bold text-sm">Registrar dirección sugerida</h3>
          <button id="btnCerrarModalDireccion" type="button" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
        <div class="p-5 space-y-3 text-xs">
          <input type="hidden" id="direccionAsignacionId">
          <input type="hidden" id="distritoOriginalModal">
          <input type="hidden" id="ubigeoOriginalModal">
          <div>
            <label class="text-gray-500">Dirección original</label>
            <textarea id="direccionOriginalModal" readonly class="mt-1 w-full min-h-16 rounded-xl border border-gray-200 bg-gray-50 p-3 outline-none"></textarea>
            <p id="ubicacionOriginalModal" class="mt-1 text-[11px] text-gray-400"></p>
          </div>
          <div>
            <label class="text-gray-500">Dirección Search</label>
            <textarea id="direccionSearchModal" class="mt-1 w-full min-h-16 rounded-xl border border-gray-200 p-3 outline-none focus:border-[#FF161A] focus:ring-4 focus:ring-red-50" placeholder="Dirección Search"></textarea>
          </div>
          <div>
            <label class="text-gray-500">Dirección corregida / sugerida</label>
            <textarea id="direccionCorregidaModal" class="mt-1 w-full min-h-16 rounded-xl border border-gray-200 p-3 outline-none focus:border-[#FF161A] focus:ring-4 focus:ring-red-50" placeholder="Dirección sugerida"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-gray-500">Distrito corregido</label>
              <input id="distritoCorregidoModal" class="mt-1 w-full h-10 rounded-xl border border-gray-200 px-3 outline-none focus:border-[#FF161A] focus:ring-4 focus:ring-red-50" placeholder="">
            </div>
            <div>
              <label class="text-gray-500">Ubigeo corregido</label>
              <input id="ubigeoCorregidoModal" class="mt-1 w-full h-10 rounded-xl border border-gray-200 px-3 outline-none focus:border-[#FF161A] focus:ring-4 focus:ring-red-50" placeholder="">
            </div>
          </div>
          <input type="hidden" id="latitudDireccionModal">
          <input type="hidden" id="longitudDireccionModal">
          <button id="btnGeocodificarDireccion" type="button" class="w-full h-10 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-[#8B0000] text-xs font-semibold">
            Validar con Google Maps
          </button>
          <div id="mapaDireccionModal" class="hidden h-64 rounded-xl border border-gray-200 overflow-hidden"></div>
          <p id="mensajeMapaDireccion" class="hidden text-[11px] text-gray-500"></p>
        </div>
        <div class="px-5 py-4 bg-gray-50 flex justify-end gap-2">
          <button id="btnCancelarDireccion" type="button" class="px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-xs">Cancelar</button>
          <button id="btnGuardarDireccion" type="button" class="px-4 py-2 rounded-xl bg-[#8B0000] hover:bg-[#A7191F] text-white text-xs font-semibold">Guardar nuevo dato</button>
        </div>
      </div>
    </div>


    <div id="modalMapaRuta" class="fixed inset-0 hidden bg-slate-900/50 z-50 p-4">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-[1500px] h-[88vh] mx-auto overflow-hidden flex flex-col">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-sm flex items-center gap-2">
              <i data-lucide="map" class="w-4 h-4 text-[#FF161A]"></i>
              Mapa completo de hoja de ruta
            </h3>
            <p id="resumenMapaRuta" class="text-[11px] text-gray-400 mt-1">Ordena las visitas arrastrando las cuentas.</p>
          </div>
          <button id="btnCerrarModalMapa" type="button" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>

        <div class="flex-1 grid grid-cols-1 xl:grid-cols-[360px_1fr] min-h-0">
          <aside class="border-r border-gray-100 bg-white min-h-0 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
              <div>
                <p class="text-[11px] uppercase tracking-wider text-[#FF161A] font-bold">Hoja de ruta</p>
                <p class="text-xs font-semibold text-gray-700">Secuencia de visitas</p>
              </div>
              <span id="badgeMapaParadas" class="text-[11px] bg-red-50 text-[#8B0000] px-2 py-1 rounded-full font-semibold">0 paradas</span>
            </div>
            <div id="listaMapaRuta" class="flex-1 overflow-y-auto scroll-suave p-3 space-y-2"></div>
            <div class="px-4 py-3 border-t border-gray-100 text-[11px] text-gray-400">
              Puedes validar direcciones pendientes para completar la ruta en el mapa.
            </div>
          </aside>

          <section class="relative min-h-0 bg-slate-100">
            <div class="absolute top-3 left-3 right-3 z-10 bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-sm px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <div class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                <i data-lucide="navigation" class="w-4 h-4 text-[#FF161A]"></i>
                <span>Mapa</span>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500">
                <span class="font-semibold text-gray-400">LEYENDA:</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-[#8B0000]"></span>Punto de visita</span>
                <span class="inline-flex items-center gap-1"><span class="w-8 h-0.5 bg-[#8B0000] inline-block"></span>Recorrido</span>
              </div>
            </div>
            <div id="mapaRutaCompleto" class="w-full h-full min-h-[520px]"></div>
            <div id="mensajeMapaRuta" class="absolute bottom-4 left-4 right-4 z-10 hidden rounded-2xl bg-white/95 border border-orange-100 shadow-sm px-4 py-3 text-xs text-orange-600"></div>
          </section>
        </div>

        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <p class="text-[11px] text-gray-500">Puedes ordenar la secuencia antes de guardar la ruta semanal.</p>
          <div class="flex justify-end gap-2">
            <button id="btnCancelarMapaRuta" type="button" class="px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-xs">Cerrar</button>
            <button id="btnValidarMasivoMapa" type="button" class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-[#8B0000] text-xs font-semibold flex items-center gap-2">
              <i data-lucide="map-pin-check" class="w-3.5 h-3.5"></i>
              Validar direcciones
            </button>
            <button id="btnGuardarMapaRuta" type="button" class="px-4 py-2 rounded-xl bg-[#8B0000] hover:bg-[#A7191F] text-white text-xs font-semibold flex items-center gap-2">
              <i data-lucide="save" class="w-3.5 h-3.5"></i>
              Guardar ruta semanal
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>

  <script>
    window.initMap = window.initMap || function() {};
  </script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGFBIf_6mCinwpqWw2Q-lHwNmK6u2iMhE&libraries=places,marker&callback=initMap"></script>
  <script src="misCuentas.js?v=20260601_feedback_mapa_ruta_v1"></script>
</body>

</html>