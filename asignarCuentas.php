<?php
date_default_timezone_set('America/Lima');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['id'])) {
  header("Location: index.php");
  exit;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <title>Hoja de Ruta - Asignación General</title>

  <link rel="stylesheet" href="asignarCuenta.css">

  <style>
    :root {
      --corp-red: #FF161A;
      --corp-dark-red: #8B0000;
      --corp-red-hover: #A7191F;
      --corp-gray: #A6A6A6;
      --corp-dark-gray: #7F7F7F;
      --corp-bg: #F4F6F8;
    }

    body {
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
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

    .corporate-card {
      border-color: #e5e7eb;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    }
  </style>
</head>

<body class="bg-[#F4F6F8] text-gray-900">

  <main class="max-w-[1700px] mx-auto px-6 py-8">

    <!-- Header superior -->
    <header class="corporate-header h-[82px] flex items-center px-6 mb-6">
      <div class="flex items-center gap-4">
        <button type="button"
          onclick="history.back()"
          class="corporate-back h-10 px-4 flex items-center gap-2 rounded-lg border text-sm font-medium cursor-pointer transition">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          Volver
        </button>

        <h1 class="text-2xl font-semibold tracking-wide text-gray-900">
          Asignación General
        </h1>
      </div>
    </header>

    <div class="px-6 pb-6">

      <!-- Filtros -->
      <section class="bg-white border border-gray-200 rounded-2xl mb-5 shadow-sm overflow-hidden">

        <!-- Header filtros -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 border-b border-gray-100">
          <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-900">
              <span class="w-8 h-8 rounded-xl bg-red-50 text-[#8B0000] flex items-center justify-center">
                <i data-lucide="funnel" class="w-4 h-4"></i>
              </span>
              <span>Filtros Avanzados de Cartera</span>
            </div>
          </div>

          <button id="btnLimpiarFiltros" disabled
            class="h-9 px-3 inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-100 bg-red-50 text-[#8B0000] text-xs font-medium hover:bg-red-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition">
            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
            Limpiar filtros
          </button>
        </div>

        <!-- Campos -->
        <div class="p-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">

            <div class="space-y-1.5">
              <label for="filtroFecha" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-gray-400"></i>
                Fecha desde
              </label>
              <input id="filtroFecha" type="date" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
            </div>

            <div class="space-y-1.5">
              <label for="filtroFechaHasta" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-gray-400"></i>
                Fecha hasta
              </label>
              <input id="filtroFechaHasta" type="date" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
            </div>

            <div class="space-y-1.5">
              <label for="filtroCartera" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="briefcase-business" class="w-3.5 h-3.5 text-gray-400"></i>
                Cartera
              </label>
              <select id="filtroCartera" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
                <option value="">Cargando...</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label for="filtroDistrito" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400"></i>
                Ubigeo / Distrito
              </label>
              <select id="filtroDistrito" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
                <option value="">Cargando...</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label for="filtroSegmento" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="layers-3" class="w-3.5 h-3.5 text-gray-400"></i>
                Segmento / Producto
              </label>
              <select id="filtroSegmento" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
                <option value="">Cargando...</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label for="filtroEstado" class="flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <i data-lucide="user-check" class="w-3.5 h-3.5 text-gray-400"></i>
                Asesor / Estado
              </label>
              <select id="filtroEstado" disabled
                class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-xs text-gray-800 outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50 disabled:opacity-60 transition">
                <option value="">Cargando...</option>
              </select>
            </div>

          </div>
        </div>

      </section>

      <!-- Contenido principal -->
      <section class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-5">

        <!-- Tabla -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

          <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-[#8B0000]"></span>
              <h2 class="text-sm">Cuentas / Registros Encontrados</h2>
              <span id="totalCuentas" class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">0</span>
            </div>
          </div>

          <div class="px-4 py-3 bg-white flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-4 text-xs">
              <span>
                Seleccionadas:
                <strong id="totalSeleccionadas" class="text-[#8B0000]">0</strong>
              </span>

              <button id="btnLimpiarSeleccion" disabled
                class="flex items-center gap-2 text-gray-400 hover:text-gray-700 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                <i data-lucide="x" class="w-3 h-3"></i>
                Limpiar Selección
              </button>
            </div>

            <div class="flex gap-2">
              <button id="btnAsignarSeleccionadas" disabled
                class="flex items-center gap-2 bg-[#8B0000] hover:bg-[#A7191F] text-white text-xs font-semibold px-4 py-2 rounded-xl disabled:opacity-50 disabled:hover:bg-[#8B0000] disabled:cursor-not-allowed cursor-pointer">
                <i data-lucide="users" class="w-4 h-4"></i>
                Asignar Seleccionadas
              </button>

              <button id="btnAsignarFiltradas" disabled
                class="flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-xs font-semibold px-4 py-2 rounded-xl disabled:opacity-50 disabled:hover:bg-white disabled:cursor-not-allowed cursor-pointer">
                <i data-lucide="shuffle" class="w-4 h-4"></i>
                Asignar Todos los Filtrados
              </button>
            </div>
          </div>

          <div class="relative px-4 py-3 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-xs text-[#8392ab]">
              <div id="selectorCantidadPagina" class="relative flex items-center gap-2">
                <button id="btnCantidadPagina" type="button"
                  class="relative flex items-center gap-3 border border-solid border-[#dadce0] rounded px-2 py-1 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span id="cantidadPorPaginaLabel">10</span>
                  <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>

                <div id="modalCantidadPagina" class="hidden absolute bottom-8 left-0 bg-gray-50 shadow-md z-10 rounded border border-gray-100 overflow-hidden">
                  <div class="relative flex flex-col gap-1 text-xs text-[rgb(96_125_139/1)]">
                    <button type="button" data-cantidad="5" class="cantidadPaginaOpcion px-3 py-2 hover:bg-gray-200 cursor-pointer text-left">5</button>
                    <button type="button" data-cantidad="10" class="cantidadPaginaOpcion px-3 py-2 hover:bg-gray-200 cursor-pointer text-left">10</button>
                    <button type="button" data-cantidad="15" class="cantidadPaginaOpcion px-3 py-2 hover:bg-gray-200 cursor-pointer text-left">15</button>
                    <button type="button" data-cantidad="25" class="cantidadPaginaOpcion px-3 py-2 hover:bg-gray-200 cursor-pointer text-left">25</button>
                    <button type="button" data-cantidad="50" class="cantidadPaginaOpcion px-3 py-2 hover:bg-gray-200 cursor-pointer text-left">50</button>
                  </div>
                </div>
              </div>

              <span>cuentas por página</span>
            </div>

            <div class="relative w-full sm:w-[320px]">
              <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400">
                <i data-lucide="search" class="w-4 h-4"></i>
              </div>
              <input
                type="search"
                id="busquedaGlobal"
                class="block w-full px-9 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-[#FF161A] focus:border-[#FF161A] outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="Buscar cuenta, cliente, documento, dirección, asesor..."
                autocomplete="off"
                disabled />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead class="bg-gray-50 text-gray-500">
                <tr>
                  <th class="px-4 py-3 text-left w-10">
                    <input id="checkTodos" type="checkbox" disabled class="rounded border-gray-300 accent-[#FF161A]">
                  </th>
                  <th class="px-3 py-3 text-left">Cuenta</th>
                  <th class="px-3 py-3 text-left">Cliente</th>
                  <th class="px-3 py-3 text-left">Dirección</th>
                  <th class="px-3 py-3 text-left">Distrito / Ubigeo</th>
                  <th class="px-3 py-3 text-left">Importe</th>
                  <th class="px-3 py-3 text-left">Asesor Actual</th>
                  <th class="px-3 py-3 text-left">Estado</th>
                </tr>
              </thead>

              <tbody id="tablaCuentas" class="divide-y divide-gray-100"></tbody>
            </table>
          </div>

          <div class="px-4 py-3 border-t border-gray-100 flex flex-col gap-3">
            <div class="pagination-container flex-1 flex flex-col md:flex-row md:justify-between md:items-center gap-3">
              <div id="paginacionInfo" class="cantidad-products-container text-[#8392ab] text-sm">
                Mostrando 0 a 0 de 0 cuentas
              </div>
              <div id="paginacionItems" class="paginacion-products-container flex gap-0.5 justify-end"></div>
            </div>
          </div>
        </div>

        <!-- Panel asesores -->
        <aside class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 h-fit">

          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm">Asesores de Campo</h2>
            <span id="totalAsesores" class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">0</span>
          </div>

          <div id="listaAsesores" class="space-y-2"></div>

          <div class="mt-3 pt-3 border-t border-gray-100 flex flex-col gap-2">
            <div id="asesoresPaginacionInfo" class="text-xs text-[#8392ab]">
              Mostrando 0 a 0 de 0 asesores
            </div>
            <div id="asesoresPaginacionItems" class="flex items-center justify-end gap-1"></div>
          </div>

          <div class="mt-5 bg-red-50 border border-red-100 rounded-xl p-3">
            <h3 class="font-bold text-xs mb-1">Resumen de Transacción:</h3>
            <p id="resumenTransaccion" class="text-xs text-gray-500">
              Cargando información...
            </p>
          </div>

          <button id="btnAsignarFinal" disabled
            class="mt-3 w-full flex items-center justify-center gap-2 bg-[#8B0000] hover:bg-[#A7191F] text-white font-semibold text-sm py-3 rounded-2xl shadow disabled:opacity-50 disabled:hover:bg-[#8B0000] disabled:cursor-not-allowed cursor-pointer">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            Asignar cuentas al asesor
          </button>

        </aside>
      </section>

      <!-- Toast -->
      <div id="toast"
        class="hidden fixed right-6 bottom-6 bg-[#151515] text-white rounded-2xl shadow-xl px-4 py-4 w-[330px] gap-3 items-start">
        <div class="w-8 h-8 rounded-full bg-emerald-900 text-emerald-400 flex items-center justify-center">
          <i data-lucide="check" class="w-4 h-4"></i>
        </div>

        <div class="flex-1">
          <h3 class="text-sm font-bold">¡Cuentas Asignadas!</h3>
          <p id="toastMensaje" class="text-xs text-gray-400 mt-1"></p>
        </div>

        <button id="btnCerrarToast" class="bg-white text-[#8B0000] text-xs font-semibold px-3 py-1 rounded-full self-end disabled:cursor-not-allowed cursor-pointer">
          Aceptar
        </button>
      </div>

  </main>


  <script src="asignarCuenta.js"></script>
</body>

</html>