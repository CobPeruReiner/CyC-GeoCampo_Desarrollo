<?php

date_default_timezone_set('America/Lima');

error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set('error_log', 'debug.log');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
  $userId = (int)($_SESSION['id'] ?? 0);
}

if (($_SESSION['idEstado'] ?? 0) !== 2) {
  header("Location: menu.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Supervisor | Gestiones</title>

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

</head>

<body class="bg-gray-100 min-h-screen">

  <!-- CONTENEDOR -->
  <div class="max-w-[1700px] mx-auto px-6 py-8">

    <!-- TÍTULO -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-gray-800">
        Panel de Supervisión
      </h1>

      <form action="logout.php" method="post">
        <button
          type="submit"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
             rounded-lg border border-red-500 text-red-600
             hover:bg-red-500 hover:text-white
             transition-all duration-300 cursor-pointer">

          <i class="fa-solid fa-right-from-bracket"></i>
          Cerrar sesión
        </button>
      </form>
    </div>

    <!-- FILTROS -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- FECHA INICIO -->
        <div class="relative">
          <input
            type="date"
            id="fechaInicio"
            placeholder=" "
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed" />
          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Fecha inicio
          </label>
        </div>

        <!-- FECHA FIN -->
        <div class="relative">
          <input
            type="date"
            id="fechaFin"
            placeholder=" "
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed" />
          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Fecha fin
          </label>
        </div>

        <!-- CARTERA -->
        <div class="relative">
          <select id="selectCartera"
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed">
            <option value="" selected disabled>Seleccione una Cartera</option>
          </select>

          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Cartera
          </label>

          <div id="skeletonCartera"
            class="hidden absolute inset-0 rounded-[7px] bg-gray-200 animate-pulse"></div>
        </div>

        <!-- DEPARTAMENTO -->
        <div class="relative">
          <select id="selectDepartamento"
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed">
            <option value="" selected disabled>Seleccione Departamento</option>
          </select>

          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Departamento
          </label>

          <div id="skeletonDepartamento"
            class="hidden absolute inset-0 rounded-[7px] bg-gray-200 animate-pulse"></div>
        </div>

        <!-- PROVINCIA -->
        <div class="relative">
          <select id="selectProvincia"
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed">
            <option value="" selected disabled>Seleccione Provincia</option>
          </select>

          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Provincia
          </label>

          <div id="skeletonProvincia"
            class="hidden absolute inset-0 rounded-[7px] bg-gray-200 animate-pulse"></div>
        </div>

        <!-- DISTRITO -->
        <div class="relative">
          <select id="selectDistrito"
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed">
            <option value="" selected disabled>Seleccione Distrito</option>
          </select>

          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Distrito
          </label>

          <div id="skeletonDistrito"
            class="hidden absolute inset-0 rounded-[7px] bg-gray-200 animate-pulse"></div>
        </div>

        <!-- UNIDAD NEGOCIO -->
        <div class="relative">
          <select id="selectUnidadNegocio"
            class="peer w-full bg-transparent text-gray-700 text-sm border border-gray-200 rounded-[7px] px-3 py-2.5 focus:border-2 focus:border-[#09c] outline-none disabled:cursor-not-allowed">
            <option value="" selected disabled>Seleccione Unidad de Negocio</option>
          </select>

          <label class="absolute left-3 -top-1.5 text-[11px] text-gray-500 bg-white px-1">
            Unidad de Negocio
          </label>

          <div id="skeletonUnidadNegocio"
            class="hidden absolute inset-0 rounded-[7px] bg-gray-200 animate-pulse"></div>
        </div>

        <!-- BOTON -->
        <div class="md:col-span-5 flex justify-end items-center mt-2">
          <button id="btnConsultar"
            class="cursor-pointer inline-flex items-center justify-center gap-2 px-8 py-2.5 text-sm font-bold rounded-lg bg-cyan-600 text-white shadow-md shadow-cyan-600/20 hover:bg-cyan-700 hover:shadow-lg hover:shadow-cyan-600/30 focus:outline-none focus:ring-2 focus:ring-cyan-400/50 active:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300">
            Consultar
          </button>
        </div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="bg-white rounded-xl shadow-sm p-6">

      <!-- SKELETON -->
      <div id="skeleton" class="space-y-3">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <div class="grid grid-cols-7 gap-4 animate-pulse">
            <?php for ($j = 0; $j < 7; $j++): ?>
              <div class="h-4 bg-gray-200 rounded"></div>
            <?php endfor; ?>
          </div>
        <?php endfor; ?>
      </div>

      <!-- TABLA REAL (oculta por ahora) -->
      <div id="tabla" class="hidden overflow-x-auto">

        <!-- BUSCADOR -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
              <i class="fa-solid fa-magnifying-glass text-[#8392ab] text-sm"></i>
            </div>
            <input type="search" id="searchInput" class="block w-full px-9 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-[#09c] focus:border-[#09c] outline-none" placeholder="Buscar gestiones" autocomplete="off" value="">
          </div>
        </div>

        <!-- TABLA -->
        <table class="relative w-full text-[#67748e] text-nowrap">
          <thead>
            <tr class="text-xs text-[#8392ab] text-left uppercase opacity-70 border-b border-solid border-[#e9ecef]">
              <th class="py-3 px-6">Fecha</th>
              <th class="py-3 px-6">Hora</th>
              <th class="py-3 px-6">Identificador</th>
              <th class="py-3 px-6">Cliente</th>
              <th class="py-3 px-6">Efecto</th>
              <th class="py-3 px-6">Observación</th>
              <th class="py-3 px-6">Dirección</th>
              <th class="py-3 px-6">Estado GPS</th>
              <th class="py-3 px-6">Acción</th>
            </tr>
          </thead>
          <tbody id="tablaBody">
          </tbody>
        </table>

        <!-- PAGINADOR -->
        <nav id="pagination" class="flex justify-end mt-4"></nav>

      </div>

      <!-- BOTONES -->
      <div class="flex flex-col md:flex-row gap-4 mt-6 justify-end">
        <button
          id="btnDescargarFotos"
          onclick="descargarFotos()"
          disabled
          class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold rounded-lg border border-[rgb(52,71,103)] text-[rgb(52,71,103)] shadow-sm hover:bg-gray-100 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 active:opacity-85 active:shadow-none disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-300">
          Descargar fotos
        </button>
        <button
          onclick="descargarRegistros()"
          class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold rounded-lg border border-[#28a745] text-[#28a745] shadow-sm hover:bg-[#28a745] hover:text-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#28a745]/30 active:opacity-85 active:shadow-none disabled:cursor-not-allowed disabled:opacity-50 transition-all duration-300">
          Descargar registros
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DE IMAGENES (MINIATURAS) -->
  <div id="modalImagenes"
    class="fixed inset-0 bg-[rgba(0,0,0,0.4)] z-[205] flex justify-center items-center transition-all duration-500 invisible opacity-0">
    <div
      class="scroll absolute flex flex-col justify-between w-[90%] md:w-[70%] xl:w-[60%] h-auto overflow-y-auto bg-white border border-solid border-[#e0e0e0] rounded-md overflow-hidden transform translate-y-[600%] transition-transform duration-500">

      <div class="w-full h-full p-5">

        <div class="w-full flex flex-col py-5 px-4 border border-solid border-[#ccc] gap-5">

          <div class="relative w-full text-left">
            <h1 class="text-[#344767] text-xl font-bold">
              Imágenes de la gestión
            </h1>

            <button
              type="button"
              onclick="cerrarModal()"
              class="absolute right-0 top-0 text-xl cursor-pointer">
              ✕
            </button>
          </div>

          <hr />

          <div id="contenedorImagenes"
            class="grid grid-cols-1 md:grid-cols-3 gap-4">
          </div>

          <div class="w-full flex justify-end gap-3 pt-3">
            <button onclick="cerrarModal()"
              class="px-4 py-2 rounded-md border text-gray-600 hover:bg-gray-100 cursor-pointer">
              Cerrar
            </button>

            <button onclick="descargarSeleccionadas()"
              class="px-4 py-2 rounded-md bg-cyan-600 text-white hover:bg-cyan-700 cursor-pointer">
              Descargar seleccionadas
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- MODAL VER IMAGEN GRANDE -->
  <div id="modalVerImagen"
    class="fixed inset-0 bg-[rgba(0,0,0,0.4)] z-[210] flex justify-center items-center transition-all duration-500 invisible opacity-0">

    <div
      class="scroll absolute flex flex-col justify-between w-[90%] md:w-[70%] xl:w-[60%] max-h-[85vh] overflow-y-auto bg-white border border-solid border-[#e0e0e0] rounded-md overflow-hidden transform translate-y-[600%] transition-transform duration-500">

      <div class="w-full h-full p-5">

        <div class="w-full flex flex-col py-5 px-4 border border-solid border-[#ccc] gap-5">

          <div class="relative w-full text-left">
            <h1 class="text-[#344767] text-xl font-bold">
              Vista de imagen
            </h1>

            <button
              type="button"
              onclick="cerrarModalImagenGrande()"
              class="absolute right-0 top-0 text-xl cursor-pointer">
              ✕
            </button>
          </div>

          <hr />

          <div class="w-full flex justify-center items-center">
            <img id="imagenGrande"
              src=""
              class="max-h-[70vh] w-auto rounded-md object-contain">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SIMULACIÓN DE CARGA -->
  <script>
    const USER_ID = <?= $userId ?>;

    let ID_TABLA_ACTUAL = null;

    const btnConsultar = document.getElementById('btnConsultar');
    const btnDescargarFotos = document.getElementById('btnDescargarFotos');

    const skeleton = document.getElementById('skeleton');
    const tabla = document.getElementById('tabla');
    const tablaBody = document.getElementById('tablaBody');

    const selectCartera = document.getElementById('selectCartera');
    const skeletonCartera = document.getElementById('skeletonCartera');

    const selectDepartamento = document.getElementById('selectDepartamento');
    const skeletonDepartamento = document.getElementById('skeletonDepartamento');

    const selectProvincia = document.getElementById('selectProvincia');
    const skeletonProvincia = document.getElementById('skeletonProvincia');

    const selectDistrito = document.getElementById('selectDistrito');
    const skeletonDistrito = document.getElementById('skeletonDistrito');

    const selectUnidadNegocio = document.getElementById('selectUnidadNegocio');
    const skeletonUnidadNegocio = document.getElementById('skeletonUnidadNegocio');

    // selectProvincia.disabled = true;
    // selectDistrito.disabled = true;
    // selectUnidadNegocio.disabled = true;

    let allData = [];
    let filteredData = [];
    let currentPage = 1;
    const rowsPerPage = 10;

    let imagenesSeleccionadas = [];

    function abrirModalImagenes(row) {
      if (!ID_TABLA_ACTUAL) {
        alert('No se pudo determinar la cartera');
        return;
      }

      const carpeta = ID_TABLA_ACTUAL.startsWith('C_') ?
        ID_TABLA_ACTUAL.substring(2) :
        ID_TABLA_ACTUAL;

      const contenedor = document.getElementById('contenedorImagenes');
      contenedor.innerHTML = '';
      imagenesSeleccionadas = [];

      const fecha = row.FECHA ? row.FECHA.substring(0, 10) : '';

      ['imagen1', 'imagen2', 'imagen3'].forEach(key => {

        if (!row[key]) return;

        const nombre = row[key];

        const srcNueva = `fotos/${carpeta}/${fecha}/${nombre}`;
        const srcAntigua = `fotos/${carpeta}/${nombre}`;

        contenedor.innerHTML += `
          <div class="border rounded-lg overflow-hidden">

            <div class="relative w-full h-48 bg-gray-200 animate-pulse">

              <img
                src="${srcNueva}"
                loading="lazy"
                class="w-full h-full object-cover opacity-0 transition-opacity duration-300"
                onload="
                  this.classList.remove('opacity-0');
                  this.parentElement.classList.remove('animate-pulse','bg-gray-200');
                "
                onerror="
                  if(this.dataset.fallback!='1'){
                    this.dataset.fallback='1';
                    this.src='${srcAntigua}';
                  }
                "
              >

              <button
                type="button"
                onclick="verImagenGrande('${srcNueva}')"
                class="absolute top-2 right-2 bg-black/60 text-white
                      rounded-full p-2 hover:bg-black/80 transition cursor-pointer">
                <i class="fa-solid fa-eye"></i>
              </button>

            </div>

            <div class="p-2 flex items-center gap-2">
              <input type="checkbox"
                onchange="toggleImagen('${nombre}', this.checked)">
              <span class="text-sm truncate">${nombre}</span>
            </div>

          </div>
        `;
      });

      if (!contenedor.innerHTML) {
        contenedor.innerHTML = `
          <p class="text-gray-500 col-span-3 text-center">
            Esta gestión no tiene imágenes
          </p>
        `;
      }

      const modal = document.getElementById('modalImagenes');
      const caja = modal.querySelector('div');

      modal.classList.remove('invisible');
      modal.classList.remove('opacity-0');

      caja.classList.remove('translate-y-[600%]');
    }

    function cerrarModal() {
      const modal = document.getElementById('modalImagenes');
      const caja = modal.querySelector('div');

      modal.classList.add('opacity-0');
      modal.classList.add('invisible');

      caja.classList.add('translate-y-[600%]');
    }

    function verImagenGrande(src) {

      const modal = document.getElementById('modalVerImagen');
      const caja = modal.querySelector('div');
      const img = document.getElementById('imagenGrande');

      img.src = src;

      modal.classList.remove('invisible');
      modal.classList.remove('opacity-0');

      caja.classList.remove('translate-y-[600%]');
    }

    function cerrarModalImagenGrande() {
      const modal = document.getElementById('modalVerImagen');
      const caja = modal.querySelector('div');

      modal.classList.add('opacity-0');
      modal.classList.add('invisible');

      caja.classList.add('translate-y-[600%]');
    }

    function toggleImagen(nombre, checked) {
      if (checked) {
        imagenesSeleccionadas.push(nombre);
      } else {
        imagenesSeleccionadas = imagenesSeleccionadas.filter(i => i !== nombre);
      }
    }

    // -----------------------------
    // CARGAR CARTERAS
    // -----------------------------
    async function cargarCarteras() {
      skeletonCartera.classList.remove('hidden');
      selectCartera.classList.add('hidden');

      const res = await fetch(`get_asignacion.php?userId=${USER_ID}`);
      const data = await res.json();

      selectCartera.innerHTML =
        '<option value="" selected disabled>Seleccione una Cartera</option>';

      data.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id_cartera;
        opt.textContent = c.cartera;

        opt.dataset.tabla = c.id_tabla;

        selectCartera.appendChild(opt);
      });

      skeletonCartera.classList.add('hidden');
      selectCartera.classList.remove('hidden');
    }

    // MOSTRAR TABLA

    function renderTable() {
      tablaBody.innerHTML = '';

      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const pageData = filteredData.slice(start, end);

      if (!pageData.length) {
        tablaBody.innerHTML = `
          <tr>
            <td colspan="7" class="py-6 text-center text-gray-400">
              No se encontraron resultados
            </td>
          </tr>
        `;
        return;
      }

      pageData.forEach(g => {
        const [fecha, hora] = g.FECHA.split(' ');

        tablaBody.innerHTML += `
          <tr class="text-xs border-b hover:bg-gray-100">
            <td class="py-3 px-6">${fecha}</td>
            <td class="py-3 px-6">${hora ?? ''}</td>
            <td class="py-3 px-6">${g.IDENTIFICADOR ?? ''}</td>
            <td class="py-3 px-6">${g.NOMBRE ?? ''}</td>
            <td class="py-3 px-6">${g.EFECTO ?? ''}</td>
            <td class="py-3 px-6">${g.OBSERVACION ?? ''}</td>
            <td class="py-3 px-6">${g.DIRECCION_DEPURADA ?? ''}</td>
            <td class="py-3 px-6">${g.NOMBRE_GEOCAMPO_ESTADO_GPS ?? ''}</td>
            <td class="py-3 px-6 text-center">
                <i class="fa-solid fa-eye text-[#09f] cursor-pointer"
                  onclick='abrirModalImagenes(${JSON.stringify(g).replace(/'/g, "&apos;")})'>
                </i>
            </td>
          </tr>
        `;
      });

      renderPagination();

      btnDescargarFotos.disabled = filteredData.length === 0;
    }

    function renderPagination() {
      const pagination = document.getElementById('pagination');
      const totalPages = Math.ceil(filteredData.length / rowsPerPage);

      pagination.innerHTML = '';
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
          class="px-3 py-1 mx-1 border rounded-lg text-sm
          ${active ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600'}
          ${disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}"
          ${disabled ? 'disabled' : ''}
          onclick="goToPage(${page})">
          ${label}
        </button>
      `;

      // ⬅️ Anterior
      pagination.innerHTML += btn('‹', currentPage - 1, currentPage === 1);

      // Primera + …
      if (start > 1) {
        pagination.innerHTML += btn(1, 1);
        if (start > 2) pagination.innerHTML += `<span class="px-2">…</span>`;
      }

      // Páginas centrales
      for (let i = start; i <= end; i++) {
        pagination.innerHTML += btn(i, i, false, i === currentPage);
      }

      // … + última
      if (end < totalPages) {
        if (end < totalPages - 1) pagination.innerHTML += `<span class="px-2">…</span>`;
        pagination.innerHTML += btn(totalPages, totalPages);
      }

      // ➡️ Siguiente
      pagination.innerHTML += btn('›', currentPage + 1, currentPage === totalPages);
    }

    function goToPage(page) {
      const totalPages = Math.ceil(filteredData.length / rowsPerPage);
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      renderTable();
    }

    document.getElementById('searchInput').addEventListener('input', e => {
      const term = e.target.value.toLowerCase();

      filteredData = allData.filter(g =>
        Object.values(g).some(v =>
          String(v).toLowerCase().includes(term)
        )
      );

      currentPage = 1;
      renderTable();
    });

    // -----------------------------
    // EVENTOS
    // -----------------------------
    selectCartera.addEventListener('change', e => {
      const idCartera = e.target.value;
      const option = selectCartera.options[selectCartera.selectedIndex];

      ID_TABLA_ACTUAL = null;

      if (!idCartera || !option?.dataset?.tabla) {
        return;
      }

      ID_TABLA_ACTUAL = option.dataset.tabla;
    });

    btnConsultar.addEventListener('click', async () => {

      const cartera = selectCartera.value;
      const fecha1 = document.getElementById('fechaInicio').value;
      const fecha2 = document.getElementById('fechaFin').value;

      const departamento = selectDepartamento.value || '';
      const provincia = selectProvincia.value || '';
      const distrito = selectDistrito.value || '';
      const unidadNegocio = selectUnidadNegocio.value || null;

      if (!cartera || !fecha1 || !fecha2) {
        alert('Seleccione cartera, fecha inicio y fecha fin');
        return;
      }

      if (fecha2 < fecha1) {
        alert('La fecha fin no puede ser menor a la fecha inicio');
        return;
      }

      btnConsultar.disabled = true;
      skeleton.classList.remove('hidden');
      tabla.classList.add('hidden');

      try {

        const res = await fetch('get_informe_personalizado.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            cartera,
            fecha1,
            fecha2,
            departamento,
            provincia,
            distrito,
            unidadNegocio
          })
        });

        const json = await res.json();

        if (!json.rows || !Array.isArray(json.rows)) {
          alert('Respuesta inválida del servidor');
          return;
        }

        allData = json.rows;
        filteredData = json.rows;
        currentPage = 1;

        renderTable();
        tabla.classList.remove('hidden');

      } catch (err) {
        console.error(err);
        alert('Error al consultar gestiones');
      } finally {
        skeleton.classList.add('hidden');
        btnConsultar.disabled = false;
      }
    });

    // -----------------------------
    // DESCARGAR IMAGENES SELECCIONADAS
    // -----------------------------
    function descargarSeleccionadas() {
      if (!imagenesSeleccionadas.length) {
        alert('Seleccione al menos una imagen');
        return;
      }

      if (!ID_TABLA_ACTUAL) {
        alert('No se pudo determinar la carpeta de la cartera');
        return;
      }

      const carpeta = ID_TABLA_ACTUAL.startsWith('C_') ?
        ID_TABLA_ACTUAL.substring(2) :
        ID_TABLA_ACTUAL;

      const params = new URLSearchParams();
      params.append('carpeta', carpeta);
      imagenesSeleccionadas.forEach(img => params.append('imagenes[]', img));

      window.location.href = `descargar_imagenes.php?${params.toString()}`;
    }

    function limpiarValor(valor) {
      if (valor === null || valor === undefined) return '';

      return String(valor)
        .replace(/\r?\n|\r/g, ' ')
        .replace(/\t/g, ' ')
        .replace(/"/g, '""')
        .trim();
    }

    function descargarRegistros() {
      if (!filteredData.length) {
        alert('No hay registros para descargar');
        return;
      }

      const excluir = [
        'imagen1', 'imagen2', 'imagen3',
        'latitud', 'longitud'
      ];

      const dataLimpia = filteredData.map(row => {
        const nuevo = {};

        Object.entries(row).forEach(([key, value]) => {
          if (
            !key.startsWith('ID') &&
            !excluir.includes(key)
          ) {
            nuevo[key] = limpiarValor(value ?? '');
          }
        });

        if (!('NOMBRE_GEOCAMPO_ESTADO_GPS' in nuevo)) {
          nuevo['NOMBRE_GEOCAMPO_ESTADO_GPS'] = '';
        }

        return nuevo;
      });

      if (!dataLimpia.length) {
        alert('No hay registros para descargar');
        return;
      }

      const headers = Object.keys(dataLimpia[0]);
      if (!headers.length) {
        alert('No hay columnas para exportar');
        return;
      }

      const wsData = [
        headers,
        ...dataLimpia.map(r => headers.map(h => r[h]))
      ];

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet(wsData);

      const columnasTexto = ['IDENTIFICADOR', 'DNI_PERSONAL'];

      columnasTexto.forEach(nombreCol => {

        const colIndex = headers.indexOf(nombreCol);

        if (colIndex === -1) return;

        for (let r = 1; r < wsData.length; r++) {

          const cellAddress = XLSX.utils.encode_cell({
            r,
            c: colIndex
          });

          if (ws[cellAddress]) {
            ws[cellAddress].t = 's';
          }

        }

      });

      XLSX.utils.book_append_sheet(wb, ws, 'Registros');

      // -----------------------------
      // Nombre descriptivo del archivo
      // -----------------------------

      let nombreCartera = 'CARTERA';

      if (filteredData.length && filteredData[0].CARTERA) {
        nombreCartera = filteredData[0].CARTERA;
      }

      nombreCartera = nombreCartera
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^A-Za-z0-9 ]/g, '')
        .trim()
        .replace(/\s+/g, '_');

      // Rango real de fechas del reporte
      const fechas = filteredData
        .map(r => r.FECHA)
        .filter(Boolean)
        .sort();

      let fechaDesde = fechas.length ? fechas[0].substring(0, 10) : '';
      let fechaHasta = fechas.length ? fechas[fechas.length - 1].substring(0, 10) : '';

      const ahora = new Date();
      const hh = String(ahora.getHours()).padStart(2, '0');
      const mm = String(ahora.getMinutes()).padStart(2, '0');
      const ss = String(ahora.getSeconds()).padStart(2, '0');

      const nombreArchivo =
        `Informe_Registros_${nombreCartera}_${fechaDesde}_al_${fechaHasta}_${hh}${mm}${ss}.xlsx`;

      XLSX.writeFile(wb, nombreArchivo);
    }

    function descargarFotos() {
      window.location.href = 'download_imagenes_informe.php';
    }

    // -----------------------------
    // CARGAR DEPARTAMENTOS
    // -----------------------------
    async function cargarDepartamentos() {

      skeletonDepartamento.classList.remove('hidden');
      selectDepartamento.classList.add('hidden');

      const res = await fetch('api/get_departamentosPersonalizado.php');
      const data = await res.json();

      selectDepartamento.innerHTML =
        '<option value="">Todos los departamentos</option>';

      data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.DPTO;
        opt.textContent = d.DPTO;
        selectDepartamento.appendChild(opt);
      });

      selectDepartamento.value = '';

      skeletonDepartamento.classList.add('hidden');
      selectDepartamento.classList.remove('hidden');
    }

    // -----------------------------
    // CARGAR PROVINCIAS
    // -----------------------------
    async function cargarProvincias(dpto = '') {
      skeletonProvincia.classList.remove('hidden');
      selectProvincia.classList.add('hidden');

      const res = await fetch(
        `api/get_provinciasPersonalizado.php?dpto=${encodeURIComponent(dpto)}`
      );

      const data = await res.json();

      selectProvincia.innerHTML =
        '<option value="">Todas las provincias</option>';

      data.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.PROVINCIA;
        opt.textContent = p.PROVINCIA;
        selectProvincia.appendChild(opt);
      });

      selectProvincia.value = '';

      skeletonProvincia.classList.add('hidden');
      selectProvincia.classList.remove('hidden');
    }

    // -----------------------------
    // CARGAR DISTRITOS
    // -----------------------------
    async function cargarDistritos(dpto = '', provincia = '') {
      skeletonDistrito.classList.remove('hidden');
      selectDistrito.classList.add('hidden');

      const res = await fetch(
        `api/get_distritosPersonalizado.php?dpto=${encodeURIComponent(dpto)}&provincia=${encodeURIComponent(provincia)}`
      );

      const data = await res.json();

      selectDistrito.innerHTML =
        '<option value="">Todos los distritos</option>';

      data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.DISTRITO;
        opt.textContent = d.DISTRITO;
        selectDistrito.appendChild(opt);
      });

      selectDistrito.value = '';

      skeletonDistrito.classList.add('hidden');
      selectDistrito.classList.remove('hidden');
    }

    // -----------------------------
    // CARGAR UNIDADES
    // -----------------------------
    async function cargarUnidades(dpto = '', provincia = '', distrito = '', fecha1 = '', fecha2 = '') {
      skeletonUnidadNegocio.classList.remove('hidden');
      selectUnidadNegocio.classList.add('hidden');

      try {
        const res = await fetch(
          `api/get_unidades_negocioPersonalizado.php?dpto=${encodeURIComponent(dpto)}&provincia=${encodeURIComponent(provincia)}&distrito=${encodeURIComponent(distrito)}&fecha1=${encodeURIComponent(fecha1)}&fecha2=${encodeURIComponent(fecha2)}`
        );

        const data = await res.json();

        selectUnidadNegocio.innerHTML = '<option value="">Todas las unidades</option>';

        if (Array.isArray(data)) {
          data.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.NOMBRE_UNIDAD_NEGOCIO;
            opt.textContent = u.NOMBRE_UNIDAD_NEGOCIO;
            selectUnidadNegocio.appendChild(opt);
          });
        }
      } catch (error) {
        console.error('Error cargando unidades:', error);
        selectUnidadNegocio.innerHTML = '<option value="">Todas las unidades</option>';
      } finally {
        selectUnidadNegocio.value = '';
        skeletonUnidadNegocio.classList.add('hidden');
        selectUnidadNegocio.classList.remove('hidden');
      }
    }

    // -----------------------------
    // DEPARTAMENTO → PROVINCIAS / DISTRITOS / UNIDADES
    // -----------------------------
    selectDepartamento.addEventListener('change', async () => {
      const dpto = selectDepartamento.value;
      const fecha1 = document.getElementById('fechaInicio').value;
      const fecha2 = document.getElementById('fechaFin').value;

      selectProvincia.innerHTML = '<option value="">Todas las provincias</option>';
      selectDistrito.innerHTML = '<option value="">Todos los distritos</option>';
      selectUnidadNegocio.innerHTML = '<option value="">Todas las unidades</option>';

      selectProvincia.value = '';
      selectDistrito.value = '';
      selectUnidadNegocio.value = '';

      await cargarProvincias(dpto);
      await cargarDistritos(dpto, '');
      await cargarUnidades(dpto, '', '', fecha1, fecha2);
    });

    // -----------------------------
    // PROVINCIA → DISTRITOS / UNIDADES
    // -----------------------------
    selectProvincia.addEventListener('change', async () => {
      const dpto = selectDepartamento.value;
      const provincia = selectProvincia.value;
      const fecha1 = document.getElementById('fechaInicio').value;
      const fecha2 = document.getElementById('fechaFin').value;

      selectDistrito.innerHTML = '<option value="">Todos los distritos</option>';
      selectUnidadNegocio.innerHTML = '<option value="">Todas las unidades</option>';

      selectDistrito.value = '';
      selectUnidadNegocio.value = '';

      await cargarDistritos(dpto, provincia);
      await cargarUnidades(dpto, provincia, '', fecha1, fecha2);
    });

    // -----------------------------
    // DISTRITO → UNIDAD NEGOCIO
    // -----------------------------
    selectDistrito.addEventListener('change', async () => {
      const dpto = selectDepartamento.value;
      const provincia = selectProvincia.value;
      const distrito = selectDistrito.value;
      const fecha1 = document.getElementById('fechaInicio').value;
      const fecha2 = document.getElementById('fechaFin').value;

      selectUnidadNegocio.innerHTML = '<option value="">Todas las unidades</option>';
      selectUnidadNegocio.value = '';

      await cargarUnidades(dpto, provincia, distrito, fecha1, fecha2);
    });

    document.getElementById('fechaInicio').addEventListener('change', async () => {
      await cargarUnidades(
        selectDepartamento.value,
        selectProvincia.value,
        selectDistrito.value,
        document.getElementById('fechaInicio').value,
        document.getElementById('fechaFin').value
      );
    });

    document.getElementById('fechaFin').addEventListener('change', async () => {
      await cargarUnidades(
        selectDepartamento.value,
        selectProvincia.value,
        selectDistrito.value,
        document.getElementById('fechaInicio').value,
        document.getElementById('fechaFin').value
      );
    });

    // -----------------------------
    // INIT
    // -----------------------------
    cargarCarteras();
    cargarDepartamentos();
    cargarProvincias();
    cargarDistritos();
    cargarUnidades(
      '',
      '',
      '',
      document.getElementById('fechaInicio').value,
      document.getElementById('fechaFin').value
    );
  </script>

</body>

</html>