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

$cargoPersonal = $_SESSION['cargo'] ?? 0;

$cargosAdmin = [1, 8, 15, 16, 17, 18, 19, 20, 21, 22];

if (!in_array($cargoPersonal, $cargosAdmin)) {
  header("Location: formRegistroOperativo.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Informe | Registros Operativos</title>

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">

  <div class="max-w-[1700px] mx-auto px-6 py-8">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-6">

      <div class="flex items-center gap-4">
        <a href="menu.php"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition-all">
          <i class="fa-solid fa-arrow-left"></i>
          Volver
        </a>

        <h1 class="text-2xl font-semibold text-gray-800">
          Panel de Registros Operativos
        </h1>
      </div>

      <div class="flex gap-3">
        <button onclick="abrirModal()" class="cursor-pointer inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-bold rounded-lg bg-cyan-600 text-white shadow-md shadow-cyan-600/20 hover:bg-cyan-700 hover:shadow-lg hover:shadow-cyan-600/30 focus:outline-none focus:ring-2 focus:ring-cyan-400/50 active:opacity-90 active:shadow-none disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300">
          <i class="fa-solid fa-plus"></i>
          Nuevo Operativo
        </button>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">

      <!-- FILTROS (PRIMERA FILA) -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

        <!-- FECHA INICIO -->
        <div class="relative">
          <input type="date" id="fechaInicio" placeholder=" "
            class="peer w-full outline-none bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-cyan-600 transition-all" />
          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Fecha inicio
          </label>
        </div>

        <!-- FECHA FIN -->
        <div class="relative">
          <input type="date" id="fechaFin" placeholder=" "
            class="peer w-full outline-none bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-cyan-600 transition-all" />
          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Fecha fin
          </label>
        </div>

        <!-- UNIDAD -->
        <div>
          <select id="selectUnidad"
            class="w-full text-sm outline-none border border-gray-200 rounded-[7px] px-3 py-2.5 disabled:cursor-not-allowed">
            <option value="">Seleccione Unidad</option>
          </select>
        </div>

        <!-- GESTOR -->
        <div>
          <select id="selectGestor" disabled
            class="w-full text-sm outline-none border border-gray-200 rounded-[7px] px-3 py-2.5 disabled:cursor-not-allowed">
            <option value="">Seleccione Gestor</option>
          </select>
        </div>

        <!-- AGENCIA -->
        <div>
          <select id="selectAgencia" disabled
            class="w-full text-sm outline-none border border-gray-200 rounded-[7px] px-3 py-2.5 disabled:cursor-not-allowed">
            <option value="">Seleccione Agencia</option>
          </select>
        </div>

      </div>

      <!-- BOTONES (SEGUNDA FILA) -->
      <div class="flex justify-end gap-4 mt-6">
        <button
          id="btnConsultar"
          class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold rounded-lg border border-[rgb(52,71,103)] text-[rgb(52,71,103)] shadow-sm hover:bg-gray-100 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 active:opacity-85 active:shadow-none disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-300">

          <i class="fa-solid fa-magnifying-glass"></i>
          Consultar
        </button>
        <button
          id="btnExportar"
          onclick="exportar()"
          disabled
          class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold rounded-lg border border-[#28a745] text-[#28a745] shadow-sm hover:bg-[#28a745] hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#28a745]/30 active:opacity-85 active:shadow-none disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-300">

          <i class="fa-solid fa-file-excel"></i>
          Exportar
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">

      <!-- BUSCADOR -->
      <div class="flex justify-between items-center mb-4">
        <input type="search" id="searchInput"
          class="px-4 py-2 text-sm border border-gray-300 rounded-lg w-72 focus:outline-none focus:ring-2 focus:ring-cyan-600/50 outline-none"
          placeholder="Buscar..." autocomplete="off">
      </div>

      <!-- TABLA -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-gray-600">
          <thead class="text-xs uppercase text-gray-400 border-b">
            <tr>
              <th class="py-3 px-4 text-left">Fecha Registro</th>
              <th class="py-3 px-4 text-left">DNI</th>
              <th class="py-3 px-4 text-left">Cliente</th>
              <th class="py-3 px-4 text-left">Funcionario</th>
              <th class="py-3 px-4 text-left">Telefono Funcionario</th>
              <th class="py-3 px-4 text-left">Gestor</th>
              <th class="py-3 px-4 text-left">Unidad Negocio</th>
              <th class="py-3 px-4 text-left">Agencia</th>
              <th class="py-3 px-4 text-left">Estado GPS</th>
              <th class="py-3 px-4 text-center">Acción</th>
            </tr>
          </thead>
          <tbody id="tablaBody"></tbody>
        </table>
      </div>

      <!-- PAGINADOR -->
      <div id="pagination" class="flex justify-end mt-4"></div>

    </div>


    <!-- MODAL DE VISITA -->
    <div id="modalVisita"
      class="fixed inset-0 bg-black/50 flex items-center justify-center
         opacity-0 invisible transition-opacity duration-300">

      <div
        class="bg-white w-full max-w-2xl rounded-xl p-6 relative
           transform translate-y-10 transition-all duration-300">

        <button onclick="cerrarModal()"
          class="absolute top-3 right-3 text-gray-500 cursor-pointer">
          ✕
        </button>

        <iframe src="formRegistroOperativo.php?modal=1"
          class="w-full h-[500px] border-0"></iframe>

      </div>
    </div>

    <!-- MODAL DE DETALLE -->
    <div id="modalDetalle"
      class="fixed inset-0 bg-black/50 flex items-center justify-center
         opacity-0 invisible transition-opacity duration-300">

      <div
        class="bg-white w-full max-w-[630px] rounded-xl p-6 relative
           transform translate-y-10 transition-all duration-300">

        <button onclick="cerrarDetalle()"
          class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 cursor-pointer">
          ✕
        </button>

        <div id="detalleContenido" class="text-sm text-gray-700"></div>

      </div>
    </div>

  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {

      let allData = [];
      let filteredData = [];
      let currentPage = 1;
      const rowsPerPage = 8;
      let isLoading = false;

      function setFechasHoy() {

        const hoy = new Date().toISOString().split('T')[0];

        document.getElementById('fechaInicio').value = hoy;
        document.getElementById('fechaFin').value = hoy;

      }

      /* ===============================
         API INFORME
      ================================ */

      async function fetchDataSimulada(filtros = {}) {

        mostrarSkeleton(true);

        const params = new URLSearchParams(filtros);

        try {

          const resp = await fetch("api/informeOperativos.php?" + params.toString());

          if (!resp.ok) {
            alert("Error HTTP consultando informe");
            return [];
          }

          const data = await resp.json();

          if (!data.success) {
            alert("Error al consultar datos");
            return [];
          }

          return data.data;

        } catch (err) {

          console.error(err);
          alert("Error de conexión con el servidor");
          return [];

        }

      }

      /* ===============================
         CONSULTAR
      ================================ */

      document.getElementById('btnConsultar').addEventListener('click', async () => {

        const btnConsultar = document.getElementById('btnConsultar');
        const btnExportar = document.getElementById('btnExportar');

        if (isLoading) return;

        isLoading = true;

        btnConsultar.disabled = true;
        btnExportar.disabled = true;

        const filtros = obtenerFiltros();
        const data = await fetchDataSimulada(filtros);

        allData = data;
        filteredData = [...allData];
        currentPage = 1;

        mostrarSkeleton(false);
        renderTable();

        btnConsultar.disabled = false;
        isLoading = false;

      });

      /* ===============================
         FILTROS
      ================================ */

      function obtenerFiltros() {

        return {
          fechaInicio: document.getElementById('fechaInicio').value,
          fechaFin: document.getElementById('fechaFin').value,
          unidad: document.getElementById('selectUnidad').value,
          gestor: document.getElementById('selectGestor').value,
          agencia: document.getElementById('selectAgencia').value
        };

      }

      /* ===============================
         CARGAR UNIDADES
      ================================ */

      async function cargarUnidades() {
        const select = document.getElementById("selectUnidad");
        select.innerHTML = `<option value="">Seleccione Unidad</option>`;

        try {
          const resp = await fetch("api/unidadesFiltro.php");
          if (!resp.ok) return;

          const data = await resp.json();
          if (!data.success) return;

          data.data.forEach(u => {
            const option = document.createElement("option");
            option.value = u.nombre;
            option.textContent = u.nombre;
            select.appendChild(option);
          });

        } catch (err) {
          console.error("Error cargando unidades", err);
        }
      }

      /* ===============================
         CAMBIO DE UNIDAD
      ================================ */

      document.getElementById("selectUnidad").addEventListener("change", async e => {
        const unidad = e.target.value;

        skeletonSelect("selectAgencia");
        skeletonSelect("selectGestor");

        await cargarAgencias(unidad);
        await cargarPersonal(unidad);
      });

      /* ===============================
         CARGAR AGENCIAS
      ================================ */

      async function cargarAgencias(unidad = '') {
        const select = document.getElementById("selectAgencia");
        select.innerHTML = `<option value="">Seleccione Agencia</option>`;

        try {
          let url = "api/agenciasFiltro.php";

          if (unidad) {
            url += "?unidad=" + encodeURIComponent(unidad);
          }

          const resp = await fetch(url);
          limpiarSkeletonSelect("selectAgencia", "Seleccione Agencia");

          if (!resp.ok) return;

          const data = await resp.json();
          if (!data.success) return;

          data.data.forEach(a => {
            const option = document.createElement("option");
            option.value = a.nombre;
            option.textContent = a.nombre;
            select.appendChild(option);
          });

        } catch (err) {
          limpiarSkeletonSelect("selectAgencia", "Seleccione Agencia");
          console.error("Error cargando agencias", err);
        }
      }

      /* ===============================
         CARGAR PERSONAL
      ================================ */

      async function cargarPersonal(unidad = '') {
        const select = document.getElementById("selectGestor");
        select.innerHTML = `<option value="">Seleccione Gestor</option>`;

        try {
          let url = "api/personalFiltro.php";

          if (unidad) {
            url += "?unidad=" + encodeURIComponent(unidad);
          }

          const resp = await fetch(url);
          limpiarSkeletonSelect("selectGestor", "Seleccione Gestor");

          if (!resp.ok) return;

          const data = await resp.json();
          if (!data.success) return;

          data.data.forEach(p => {
            const option = document.createElement("option");
            option.value = p.id;
            option.textContent = p.nombre;
            select.appendChild(option);
          });

        } catch (err) {
          limpiarSkeletonSelect("selectGestor", "Seleccione Gestor");
          console.error("Error cargando personal", err);
        }
      }

      /* ===============================
         SKELETON
      ================================ */

      function mostrarSkeleton(show) {

        const tabla = document.getElementById('tablaBody');

        if (show) {

          tabla.innerHTML = '';

          for (let i = 0; i < 8; i++) {

            tabla.innerHTML += `
              <tr class="animate-pulse">
                ${'<td class="py-4 px-4"><div class="h-4 bg-gray-200 rounded"></div></td>'.repeat(8)}
              </tr>
            `;

          }

        }

      }

      function skeletonSelect(selectId) {

        const select = document.getElementById(selectId);

        select.disabled = true;

        select.innerHTML = `
          <option value="">Cargando...</option>
        `;

        select.classList.add('animate-pulse', 'bg-gray-100');

      }

      function limpiarSkeletonSelect(selectId, texto) {

        const select = document.getElementById(selectId);

        select.classList.remove('animate-pulse', 'bg-gray-100');

        select.innerHTML = `<option value="">${texto}</option>`;

        select.disabled = false;

      }

      /* ===============================
         TABLA
      ================================ */

      function renderTable() {

        document.getElementById('btnExportar').disabled = filteredData.length === 0;

        const tablaBody = document.getElementById('tablaBody');
        tablaBody.innerHTML = '';

        if (!filteredData.length) {

          tablaBody.innerHTML = `
            <tr>
              <td colspan="10" class="py-6 text-center text-gray-400">
                No se encontraron resultados
              </td>
            </tr>
          `;

          return;
        }

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const pageData = filteredData.slice(start, end);

        pageData.forEach(row => {

          tablaBody.innerHTML += `
            <tr class="border-b hover:bg-gray-50 text-sm">

              <td class="py-3 px-4">${row.fecha}</td>

              <td class="py-3 px-4 font-medium">
                ${row.dni ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.cliente ?? 'APOYO'}
              </td>

              <td class="py-3 px-4">
                ${row.funcionario ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.funcionario_telefono ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.gestor ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.unidad ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.agencia ?? ''}
              </td>

              <td class="py-3 px-4">
                ${row.estado_gps ?? ''}
              </td>

              <td class="py-3 px-4 text-center">
                <i class="fa-solid fa-eye text-cyan-600 cursor-pointer"
                  onclick='abrirDetalle(${JSON.stringify(row).replace(/'/g,"&apos;")})'>
                </i>
              </td>

            </tr>
          `;

        });

        renderPagination();
      }
      /* ===============================
         PAGINACIÓN
      ================================ */

      function renderPagination() {

        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(filteredData.length / rowsPerPage);

        pagination.innerHTML = '';
        if (totalPages <= 1) return;

        const maxVisible = 5;

        let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let end = start + maxVisible - 1;

        if (end > totalPages) {
          end = totalPages;
          start = Math.max(1, end - maxVisible + 1);
        }

        const btn = (label, page, disabled = false, active = false) => `
          <button
            class="px-3 py-1 mx-1 border rounded-lg text-sm
            ${active ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600'}
            ${disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}"
            ${disabled ? 'disabled' : ''}
            onclick="goToPage(${page})">
            ${label}
          </button>
        `;

        pagination.innerHTML += btn('‹', currentPage - 1, currentPage === 1);

        for (let i = start; i <= end; i++) {
          pagination.innerHTML += btn(i, i, false, i === currentPage);
        }

        pagination.innerHTML += btn('›', currentPage + 1, currentPage === totalPages);

      }

      window.goToPage = function(page) {

        const totalPages = Math.ceil(filteredData.length / rowsPerPage);

        if (page < 1 || page > totalPages) return;

        currentPage = page;
        renderTable();

      }

      /* ===============================
         BUSCADOR
      ================================ */

      document.getElementById('searchInput').addEventListener('input', e => {

        const value = e.target.value.toLowerCase();

        filteredData = allData.filter(r =>
          Object.values(r).some(val =>
            String(val).toLowerCase().includes(value)
          )
        );

        currentPage = 1;

        renderTable();

      });

      /* ===============================
         MODAL DETALLE
      ================================ */

      window.abrirDetalle = function(row) {

        const modal = document.getElementById('modalDetalle');
        const caja = modal.querySelector('div');
        const contenedor = document.getElementById('detalleContenido');

        if (!row.ruta_foto) {

          contenedor.innerHTML = `
            <div class="py-16 text-center text-gray-500">
              <i class="fa-solid fa-image text-3xl mb-3"></i>
              <p>Esta visita no tiene fotografía registrada</p>
            </div>
          `;

        } else {

          const src = `fotos/${row.ruta_foto}`;

          contenedor.innerHTML = `
            <div class="flex justify-center">

              <div class="relative w-full max-w-3xl rounded-xl overflow-hidden shadow bg-gray-200 animate-pulse">

                <img
                  src="${src}"
                  loading="lazy"
                  class="w-full h-auto object-contain opacity-0 transition-opacity duration-300"
                  onload="
                    this.classList.remove('opacity-0');
                    this.parentElement.classList.remove('animate-pulse','bg-gray-200');
                  "
                  onerror="
                    this.parentElement.classList.remove('animate-pulse','bg-gray-200');
                    this.parentElement.innerHTML =
                    '<p class=&quot;text-center text-gray-500 py-10&quot;>No se pudo cargar la imagen</p>';
                  "
                >

              </div>

            </div>
          `;
        }

        modal.classList.remove('invisible', 'opacity-0');
        caja.classList.remove('translate-y-10');
      };

      window.cerrarDetalle = function() {

        const modal = document.getElementById('modalDetalle');
        const caja = modal.querySelector('div');

        modal.classList.add('opacity-0');
        caja.classList.add('translate-y-10');

        setTimeout(() => {
          modal.classList.add('invisible');
        }, 300);

      };

      const modalDetalle = document.getElementById('modalDetalle');

      modalDetalle.addEventListener('click', function(e) {

        if (e.target === modalDetalle) {
          cerrarDetalle();
        }

      });

      window.abrirModal = function() {
        const modal = document.getElementById('modalVisita');
        const caja = modal.querySelector('div');

        modal.classList.remove('invisible', 'opacity-0');
        caja.classList.remove('translate-y-10');
      }

      window.cerrarModal = function() {

        const modal = document.getElementById('modalVisita');
        const caja = modal.querySelector('div');
        const iframe = modal.querySelector('iframe');

        modal.classList.add('opacity-0');
        caja.classList.add('translate-y-10');

        setTimeout(() => {
          modal.classList.add('invisible');

          iframe.src = iframe.src;

        }, 300);
      }

      const modal = document.getElementById('modalVisita');

      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          cerrarModal();
        }
      });

      /* ===============================
         EXPORTAR
      ================================ */

      window.exportar = function() {

        if (!filteredData.length) {
          alert("No hay registros para exportar");
          return;
        }

        const dataLimpia = filteredData.map(row => {

          const baseFoto =
            location.hostname === "localhost" ?
            "http://localhost/fotos/" :
            "https://geocampo.online/fotos/";

          return {
            Fecha: row.fecha,
            DNI: row.dni ?? "",
            Cliente: row.cliente ?? "APOYO",
            Funcionario: row.funcionario ?? "",
            Telefono: row.funcionario_telefono ?? "",
            Gestor: row.gestor ?? "",
            Unidad: row.unidad ?? "",
            Agencia: row.agencia ?? "",
            Estado_GPS: row.estado_gps ?? "",
            Foto_URL: row.ruta_foto ? baseFoto + row.ruta_foto : ""
          };

        });

        const headers = Object.keys(dataLimpia[0]);

        const wsData = [
          headers,
          ...dataLimpia.map(r => headers.map(h => r[h]))
        ];

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(wsData);

        XLSX.utils.book_append_sheet(wb, ws, "Operativo");

        const ahora = new Date();

        const fecha =
          ahora.getFullYear() +
          String(ahora.getMonth() + 1).padStart(2, "0") +
          String(ahora.getDate()).padStart(2, "0");

        const hora =
          String(ahora.getHours()).padStart(2, "0") +
          String(ahora.getMinutes()).padStart(2, "0") +
          String(ahora.getSeconds()).padStart(2, "0");

        const nombreArchivo = `Informe_Operativo_${fecha}_${hora}.xlsx`;

        XLSX.writeFile(wb, nombreArchivo);

      };

      /* ===============================
         INIT
      ================================ */

      setFechasHoy();
      cargarUnidades();
      cargarAgencias();
      cargarPersonal();
      document.getElementById('btnConsultar').click();

    });
  </script>

</body>

</html>