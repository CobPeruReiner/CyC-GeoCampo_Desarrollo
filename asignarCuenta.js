const $ = (selector) => document.querySelector(selector);

let cargandoInicial = true;
let cargandoCuentas = false;
let asignando = false;

let asesores = [];
let cuentas = [];
let filtrosBackend = {
  carteras: [],
  distritos: [],
  segmentos: [],
  estados: [],
};

let cuentasSeleccionadas = [];
let asesorSeleccionadoId = null;

let paginaActual = 1;
let cuentasPorPagina = 10;
let paginacion = {
  pagina: 1,
  porPagina: 10,
  total: 0,
  totalPaginas: 1,
  desde: 0,
  hasta: 0,
};

let modalCantidadPaginaAbierto = false;
let busquedaTimer = null;
let seleccionGlobalCargando = false;

let asesorPaginaActual = 1;
let asesoresPorPagina = 5;

function escaparHTML(valor) {
  return String(valor ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function asegurarModalConfirmacion() {
  let modal = $("#modalConfirmacionGlobal");
  if (modal) return modal;

  modal = document.createElement("div");
  modal.id = "modalConfirmacionGlobal";
  modal.className =
    "hidden fixed inset-0 z-50 items-center justify-center bg-black/40 px-4";
  modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
        <div>
          <h3 id="modalConfirmacionTitulo" class="text-base font-bold text-gray-900"></h3>
          <p id="modalConfirmacionSubtitulo" class="text-xs text-gray-500 mt-1"></p>
        </div>
        <button id="modalConfirmacionCerrar" type="button" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 cursor-pointer">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
      <div id="modalConfirmacionContenido" class="px-5 py-4 max-h-[60vh] overflow-y-auto text-sm text-gray-700"></div>
      <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
        <button id="modalConfirmacionCancelar" type="button" class="px-4 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-100 text-sm font-semibold text-gray-700 cursor-pointer">
          Cancelar
        </button>
        <button id="modalConfirmacionAceptar" type="button" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold cursor-pointer">
          Aceptar
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  lucide.createIcons();
  return modal;
}

function mostrarModalConfirmacion({
  titulo,
  subtitulo = "",
  contenidoHTML,
  textoAceptar = "Aceptar",
  textoCancelar = "Cancelar",
  claseAceptar = "bg-blue-600 hover:bg-blue-700",
}) {
  return new Promise((resolve) => {
    const modal = asegurarModalConfirmacion();
    const btnAceptar = $("#modalConfirmacionAceptar");
    const btnCancelar = $("#modalConfirmacionCancelar");
    const btnCerrar = $("#modalConfirmacionCerrar");

    $("#modalConfirmacionTitulo").textContent = titulo;
    $("#modalConfirmacionSubtitulo").textContent = subtitulo;
    $("#modalConfirmacionContenido").innerHTML = contenidoHTML;
    btnAceptar.textContent = textoAceptar;
    btnCancelar.textContent = textoCancelar;
    btnAceptar.className = `px-4 py-2 rounded-xl ${claseAceptar} text-white text-sm font-semibold cursor-pointer`;

    const cerrar = (resultado) => {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      btnAceptar.onclick = null;
      btnCancelar.onclick = null;
      btnCerrar.onclick = null;
      modal.onclick = null;
      document.removeEventListener("keydown", cerrarConEscape);
      resolve(resultado);
    };

    const cerrarConEscape = (event) => {
      if (event.key === "Escape") cerrar(false);
    };

    btnAceptar.onclick = () => cerrar(true);
    btnCancelar.onclick = () => cerrar(false);
    btnCerrar.onclick = () => cerrar(false);
    modal.onclick = (event) => {
      if (event.target === modal) cerrar(false);
    };
    document.addEventListener("keydown", cerrarConEscape);

    modal.classList.remove("hidden");
    modal.classList.add("flex");
    lucide.createIcons();
  });
}

function mostrarModalAviso({
  titulo = "Aviso",
  subtitulo = "",
  mensaje = "",
  icono = "info",
  claseIcono = "text-blue-600 bg-blue-50 border-blue-100",
}) {
  return mostrarModalConfirmacion({
    titulo,
    subtitulo,
    contenidoHTML: `
      <div class="flex gap-3 rounded-2xl border ${claseIcono} px-4 py-3">
        <i data-lucide="${icono}" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div class="text-sm leading-6 whitespace-pre-line">${escaparHTML(mensaje)}</div>
      </div>
    `,
    textoAceptar: "Entendido",
    textoCancelar: "Cerrar",
    claseAceptar: "bg-blue-600 hover:bg-blue-700",
  });
}

function mostrarErrorAsignacion(error) {
  const mensaje = error?.message || "No se pudo completar la operación.";

  return mostrarModalAviso({
    titulo: "No se pudo completar",
    subtitulo: "Revisa la selección y vuelve a intentar.",
    mensaje,
    icono: "circle-alert",
    claseIcono: "border-red-100 bg-red-50 text-red-700",
  });
}

function formatoMoneda(valor) {
  return new Intl.NumberFormat("es-PE", {
    style: "currency",
    currency: "PEN",
    maximumFractionDigits: 0,
  }).format(Number(valor || 0));
}

function construirQueryParams(params) {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query.append(key, value);
    }
  });

  return query.toString();
}

async function apiGet(action, params = {}) {
  const query = construirQueryParams({ action, ...params });
  const response = await fetch(`geocampo_api.php?${query}`, {
    method: "GET",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
    },
  });

  const data = await response.json().catch(() => null);

  if (!response.ok || !data || data.ok === false) {
    throw new Error(data?.message || "No se pudo consultar la base de datos.");
  }

  return data;
}

async function apiPost(action, payload) {
  const response = await fetch(
    `geocampo_api.php?action=${encodeURIComponent(action)}`,
    {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    },
  );

  const data = await response.json().catch(() => null);

  if (!response.ok || !data || data.ok === false) {
    throw new Error(data?.message || "No se pudo registrar la asignación.");
  }

  return data;
}

function obtenerAsesor(id) {
  return asesores.find((asesor) => asesor.id === id);
}

function obtenerNombreAsesor(id) {
  const asesor = obtenerAsesor(id);
  return asesor ? asesor.nombre : "Sin Asignar";
}
function obtenerIniciales(nombre) {
  const partes = String(nombre || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (partes.length === 0) return "?";
  if (partes.length === 1) return partes[0].slice(0, 2).toUpperCase();

  return `${partes[0][0]}${partes[1][0]}`.toUpperCase();
}

function obtenerTotalPaginasAsesores() {
  return Math.max(1, Math.ceil(asesores.length / asesoresPorPagina));
}

function normalizarPaginaAsesores() {
  const totalPaginas = obtenerTotalPaginasAsesores();
  asesorPaginaActual = Math.min(Math.max(1, asesorPaginaActual), totalPaginas);
}

function setSelectLoading(select) {
  select.innerHTML = `<option value="">Cargando...</option>`;
  select.disabled = true;
  select.classList.add("animate-pulse", "bg-gray-100", "cursor-wait");
}

function setSelectOptions(select, opciones, placeholder = "Todos") {
  select.innerHTML = `<option value="">${escaparHTML(placeholder)}</option>`;

  opciones.forEach((opcion) => {
    if (typeof opcion === "string") {
      select.innerHTML += `<option value="${escaparHTML(opcion)}">${escaparHTML(opcion)}</option>`;
    } else {
      select.innerHTML += `<option value="${escaparHTML(opcion.value)}">${escaparHTML(opcion.label)}</option>`;
    }
  });

  select.disabled = false;
  select.classList.remove("animate-pulse", "bg-gray-100", "cursor-wait");
}

function setControlesDisabled(disabled) {
  [
    "#filtroFecha",
    "#filtroFechaHasta",
    "#filtroCartera",
    "#filtroDistrito",
    "#filtroSegmento",
    "#filtroEstado",
    "#filtroPago",
    "#btnLimpiarFiltros",
    "#checkTodos",
    "#btnCantidadPagina",
    "#busquedaGlobal",
  ].forEach((selector) => {
    const elemento = $(selector);
    if (elemento) elemento.disabled = disabled;
  });

  actualizarEstadoBotones();
}

function obtenerFiltros() {
  return {
    fechaDesde: $("#filtroFecha").value,
    fechaHasta: $("#filtroFechaHasta").value,
    cartera: $("#filtroCartera").value,
    distrito: $("#filtroDistrito").value,
    segmento: $("#filtroSegmento").value,
    estado: $("#filtroEstado").value,
    pago: $("#filtroPago") ? $("#filtroPago").value : "",
    busqueda: $("#busquedaGlobal") ? $("#busquedaGlobal").value.trim() : "",
  };
}

function obtenerParametrosCuentas() {
  return {
    ...obtenerFiltros(),
    page: paginaActual,
    perPage: cuentasPorPagina,
  };
}

function renderLoaderTabla() {
  const tabla = $("#tablaCuentas");
  tabla.innerHTML = "";

  for (let i = 0; i < 8; i++) {
    tabla.innerHTML += `
      <tr class="animate-pulse">
        <td class="py-4 px-4"><div class="h-4 w-4 bg-gray-200 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-20 bg-gray-200 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-36 bg-gray-200 rounded mb-2"></div><div class="h-3 w-14 bg-gray-100 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-32 bg-gray-200 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-24 bg-gray-200 rounded mb-2"></div><div class="h-3 w-12 bg-gray-100 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-20 bg-gray-200 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-4 w-28 bg-gray-200 rounded"></div></td>
        <td class="py-4 px-3"><div class="h-6 w-24 bg-gray-200 rounded-full"></div></td>
        <td class="py-4 px-3"><div class="h-6 w-20 bg-gray-200 rounded-full"></div></td>
      </tr>
    `;
  }
}

function renderLoaderAsesores() {
  const lista = $("#listaAsesores");
  lista.innerHTML = "";

  for (let i = 0; i < 4; i++) {
    lista.innerHTML += `
      <div class="animate-pulse border border-gray-100 rounded-xl p-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gray-200"></div>
        <div class="flex-1">
          <div class="h-4 w-32 bg-gray-200 rounded mb-2"></div>
          <div class="flex gap-3">
            <div class="h-3 w-12 bg-gray-100 rounded"></div>
            <div class="h-3 w-12 bg-gray-100 rounded"></div>
            <div class="h-3 w-12 bg-gray-100 rounded"></div>
          </div>
        </div>
        <div class="w-8 h-8 rounded-full bg-gray-100"></div>
      </div>
    `;
  }
}

function renderCuentas() {
  if (cargandoInicial || cargandoCuentas) {
    renderLoaderTabla();
    return;
  }

  $("#totalCuentas").textContent = paginacion.total;

  if (cuentas.length === 0) {
    $("#tablaCuentas").innerHTML = `
      <tr>
        <td colspan="9" class="px-4 py-8 text-center text-gray-400">
          No se encontraron cuentas con los filtros seleccionados.
        </td>
      </tr>
    `;
    return;
  }

  $("#tablaCuentas").innerHTML = cuentas
    .map((cuenta) => {
      const seleccionada = cuentasSeleccionadas.includes(cuenta.id);

      const tienePago = Number(cuenta.tiene_pago || 0) === 1;
      const pagoTitulo = tienePago
        ? `Fecha: ${cuenta.fecha_pago || "-"} | Monto: ${formatoMoneda(cuenta.monto_pago || 0)}`
        : "Sin pago registrado en el periodo";

      return `
        <tr class="${seleccionada ? "bg-blue-50/60" : "bg-white"} hover:bg-blue-50/40">
          <td class="px-4 py-3">
            <input 
              type="checkbox"
              ${seleccionada ? "checked" : ""}
              ${asignando ? "disabled" : ""}
              data-id="${cuenta.id}"
              class="checkCuenta rounded border-gray-300 accent-red-500 disabled:opacity-50"
            >
          </td>

          <td class="px-3 py-3 font-bold">${escaparHTML(cuenta.cuenta || cuenta.id)}</td>

          <td class="px-3 py-3">
            <strong>${escaparHTML(cuenta.cliente)}</strong>
            <span class="block text-[10px] text-gray-400">${escaparHTML(cuenta.segmento)}</span>
          </td>

          <td class="px-3 py-3 text-gray-500">${cuenta.direccion_depurada ? escaparHTML(cuenta.direccion_depurada) : ""}</td>

          <td class="px-3 py-3">
            <strong>${escaparHTML(cuenta.distrito)}</strong>
            <span class="block text-[10px] text-gray-400">${escaparHTML(cuenta.ubigeo)}</span>
          </td>

          <td class="px-3 py-3 font-bold">${formatoMoneda(cuenta.importe)}</td>

          <td class="px-3 py-3 font-semibold">${escaparHTML(obtenerNombreAsesor(cuenta.asesorId))}</td>

          <td class="px-3 py-3">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 border text-gray-500">
              <i data-lucide="clock-3" class="w-3 h-3"></i>
              ${escaparHTML(cuenta.estado)}
            </span>
          </td>

          <td class="px-3 py-3">
            <span title="${escaparHTML(pagoTitulo)}" class="inline-flex items-center gap-1 px-2 py-1 rounded-full border ${tienePago ? "bg-emerald-50 border-emerald-100 text-emerald-700" : "bg-red-50 border-red-100 text-red-700"}">
              <i data-lucide="${tienePago ? "badge-check" : "circle-x"}" class="w-3 h-3"></i>
              ${tienePago ? "Pago" : "No pago"}
            </span>
          </td>
        </tr>
      `;
    })
    .join("");

  document.querySelectorAll(".checkCuenta").forEach((check) => {
    check.addEventListener("change", () => {
      const id = Number(check.dataset.id);

      if (check.checked) {
        if (!cuentasSeleccionadas.includes(id)) {
          cuentasSeleccionadas.push(id);
        }
      } else {
        cuentasSeleccionadas = cuentasSeleccionadas.filter(
          (cuentaId) => cuentaId !== id,
        );
      }

      render();
    });
  });
}

function renderAsesores() {
  if (cargandoInicial) {
    renderLoaderAsesores();
    renderPaginacionAsesores();
    return;
  }

  $("#totalAsesores").textContent = asesores.length;

  if (asesores.length === 0) {
    $("#listaAsesores").innerHTML = `
      <div class="text-xs text-gray-400 border border-gray-100 rounded-xl p-3">
        No se encontraron asesores activos.
      </div>
    `;
    renderPaginacionAsesores();
    return;
  }

  normalizarPaginaAsesores();
  const inicio = (asesorPaginaActual - 1) * asesoresPorPagina;
  const fin = inicio + asesoresPorPagina;
  const asesoresPagina = asesores.slice(inicio, fin);

  $("#listaAsesores").innerHTML = asesoresPagina
    .map((asesor) => {
      const seleccionado = asesor.id === asesorSeleccionadoId;
      const iniciales = obtenerIniciales(asesor.nombre);

      return `
        <button 
          data-id="${asesor.id}"
          ${asignando ? "disabled" : ""}
          class="asesorItem w-full rounded-xl p-3 flex items-center gap-3 text-left transition disabled:opacity-50
            ${seleccionado ? "border border-blue-500 bg-blue-50" : "border border-gray-100 hover:border-blue-300 bg-white"}"
        >
          <div class="relative shrink-0">
            <span class="w-10 h-10 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-semibold select-none">
              ${escaparHTML(iniciales)}
            </span>
            ${asesor.online ? `<span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-green-500 border border-white"></span>` : ""}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <strong class="text-sm leading-5 break-words">${escaparHTML(asesor.nombre)}</strong>
              ${seleccionado ? `<span class="shrink-0 text-[10px] bg-blue-500 text-white px-2 py-0.5 rounded-full">SELECCIONADO</span>` : ""}
            </div>

            <div class="grid grid-cols-2 gap-1.5 mt-2 text-[11px]" title="Resumen operativo del asesor">
              <span class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-gray-600">
                <strong class="text-gray-800">${Number(asesor.asignadas || 0)}</strong> Asignadas
              </span>
              <span class="rounded-lg border border-blue-100 bg-blue-50 px-2 py-1 text-blue-700">
                <strong>${Number(asesor.ruta_semana ?? asesor.ruta_hoy ?? 0)}</strong> Ruta semana
              </span>
              <span class="rounded-lg border border-amber-100 bg-amber-50 px-2 py-1 text-amber-700">
                <strong>${Number(asesor.pendientes || 0)}</strong> Pendientes
              </span>
              <span class="rounded-lg border border-emerald-100 bg-emerald-50 px-2 py-1 text-emerald-700">
                <strong>${Number(asesor.visitas_semana ?? asesor.visitas_hoy ?? asesor.visitas ?? 0)}</strong> Visitas semana
              </span>
            </div>

            ${
              Number(asesor.ruta_semana ?? asesor.ruta_hoy ?? 0) > 0 ||
              Number(asesor.pendientes || 0) > 0
                ? `
              <div class="mt-2 space-y-1 text-[11px] leading-4">
                ${
                  Number(asesor.ruta_semana ?? asesor.ruta_hoy ?? 0) > 0
                    ? `
                  <div class="flex items-start gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-2 py-1 text-blue-700">
                    <i data-lucide="route" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                    <span>Tiene ruta semanal activa.</span>
                  </div>
                `
                    : ""
                }
                ${
                  Number(asesor.pendientes || 0) > 0
                    ? `
                  <div class="flex items-start gap-1.5 rounded-lg border border-amber-100 bg-amber-50 px-2 py-1 text-amber-700">
                    <i data-lucide="clock-3" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                    <span>Puede recibir cuentas reasignadas.</span>
                  </div>
                `
                    : ""
                }
              </div>
            `
                : ""
            }
          </div>

          <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${seleccionado ? "bg-blue-500 text-white" : "border border-gray-200 text-gray-400"}">
            <i data-lucide="${seleccionado ? "check" : "chevron-right"}" class="w-4 h-4"></i>
          </span>
        </button>
      `;
    })
    .join("");

  document.querySelectorAll(".asesorItem").forEach((item) => {
    item.addEventListener("click", () => {
      asesorSeleccionadoId = Number(item.dataset.id);
      render();
    });
  });

  renderPaginacionAsesores();
}

function renderPaginacionAsesores() {
  const info = $("#asesoresPaginacionInfo");
  const items = $("#asesoresPaginacionItems");
  if (!info || !items) return;

  if (cargandoInicial) {
    info.textContent = "Cargando asesores...";
    items.innerHTML = "";
    return;
  }

  if (asesores.length === 0) {
    info.textContent = "Mostrando 0 a 0 de 0 asesores";
    items.innerHTML = "";
    return;
  }

  normalizarPaginaAsesores();
  const totalPaginas = obtenerTotalPaginasAsesores();
  const desde = (asesorPaginaActual - 1) * asesoresPorPagina + 1;
  const hasta = Math.min(
    asesorPaginaActual * asesoresPorPagina,
    asesores.length,
  );

  info.textContent = `Mostrando ${desde} a ${hasta} de ${asesores.length} asesores`;

  const botonClase =
    "w-8 h-8 flex justify-center items-center text-sm text-[#8392ab] border border-[#dee2e6] shadow-sm rounded-full cursor-pointer transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed";
  items.innerHTML = `
    <button type="button" id="asesorPrev" class="${botonClase} bg-transparent hover:bg-gray-100" ${asesorPaginaActual <= 1 || asignando ? "disabled" : ""}>
      <i data-lucide="chevron-left" class="w-4 h-4"></i>
    </button>
    <span class="h-8 px-3 flex items-center justify-center text-xs text-gray-500 border border-gray-100 rounded-full bg-gray-50">
      ${asesorPaginaActual} / ${totalPaginas}
    </span>
    <button type="button" id="asesorNext" class="${botonClase} bg-transparent hover:bg-gray-100" ${asesorPaginaActual >= totalPaginas || asignando ? "disabled" : ""}>
      <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </button>
  `;

  const prev = $("#asesorPrev");
  const next = $("#asesorNext");

  if (prev) {
    prev.addEventListener("click", () => {
      if (asesorPaginaActual <= 1 || asignando) return;
      asesorPaginaActual -= 1;
      render();
    });
  }

  if (next) {
    next.addEventListener("click", () => {
      if (asesorPaginaActual >= totalPaginas || asignando) return;
      asesorPaginaActual += 1;
      render();
    });
  }
}

function renderResumen() {
  if (cargandoInicial) {
    $("#resumenTransaccion").innerHTML = `
      <span class="inline-flex items-center gap-2">
        <i data-lucide="loader-circle" class="w-3 h-3 animate-spin"></i>
        Cargando información...
      </span>
    `;
    return;
  }

  const asesor = obtenerAsesor(asesorSeleccionadoId);
  const cantidad = cuentasSeleccionadas.length;

  $("#totalSeleccionadas").textContent = cantidad;

  if (!asesor || cantidad === 0) {
    $("#resumenTransaccion").innerHTML =
      `Selecciona cuentas y un asesor para continuar.`;
    return;
  }

  const avisosAsesor = [];
  if (Number(asesor.ruta_semana ?? asesor.ruta_hoy ?? 0) > 0) {
    avisosAsesor.push(`
      <div class="mt-2 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
        <i data-lucide="route" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <span>Este gestor tiene ruta semanal activa.</span>
      </div>
    `);
  }
  if (Number(asesor.pendientes || 0) > 0) {
    avisosAsesor.push(`
      <div class="mt-2 flex items-start gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-700">
        <i data-lucide="clock-3" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <span>Puede recibir cuentas reasignadas.</span>
      </div>
    `);
  }

  $("#resumenTransaccion").innerHTML = `
    Se asignarán
    <strong class="text-blue-600">${cantidad} cuenta${cantidad !== 1 ? "s" : ""}</strong>
    seleccionada${cantidad !== 1 ? "s" : ""} al asesor
    <strong class="text-gray-700">${escaparHTML(asesor.nombre)}</strong>.
    ${avisosAsesor.join("")}
  `;
  lucide.createIcons();
}

function renderCheckTodos() {
  const checkTodos = $("#checkTodos");

  if (
    cargandoInicial ||
    cargandoCuentas ||
    asignando ||
    seleccionGlobalCargando
  ) {
    checkTodos.disabled = true;
    checkTodos.checked = false;
    checkTodos.indeterminate = false;
    return;
  }

  const totalFiltrado = paginacion.total || 0;
  const cantidadSeleccionadas = cuentasSeleccionadas.length;

  checkTodos.checked =
    totalFiltrado > 0 && cantidadSeleccionadas >= totalFiltrado;
  checkTodos.indeterminate =
    cantidadSeleccionadas > 0 && cantidadSeleccionadas < totalFiltrado;
  checkTodos.disabled = totalFiltrado === 0;
}

function actualizarEstadoBotones() {
  const cantidadSeleccionadas = cuentasSeleccionadas.length;
  const cantidadFiltradas = cargandoInicial ? 0 : paginacion.total;
  const tieneAsesor = asesorSeleccionadoId !== null;

  $("#btnLimpiarSeleccion").disabled =
    cargandoInicial ||
    asignando ||
    seleccionGlobalCargando ||
    cantidadSeleccionadas === 0;
  $("#btnAsignarSeleccionadas").disabled =
    cargandoInicial ||
    asignando ||
    seleccionGlobalCargando ||
    cantidadSeleccionadas === 0 ||
    !tieneAsesor;
  $("#btnAsignarFinal").disabled =
    cargandoInicial ||
    asignando ||
    seleccionGlobalCargando ||
    cantidadSeleccionadas === 0 ||
    !tieneAsesor;
  $("#btnAsignarFiltradas").disabled =
    cargandoInicial ||
    asignando ||
    seleccionGlobalCargando ||
    cantidadFiltradas === 0 ||
    !tieneAsesor;
  $("#btnLimpiarFiltros").disabled =
    cargandoInicial || asignando || seleccionGlobalCargando;
}

function renderBotones() {
  if (asignando) {
    $("#btnAsignarFinal").innerHTML =
      `<i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i> Asignando...`;
    $("#btnAsignarSeleccionadas").innerHTML =
      `<i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i> Asignando...`;
    $("#btnAsignarFiltradas").innerHTML =
      `<i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i> Asignando...`;

    $("#btnAsignarFinal").classList.add("cursor-wait", "animate-pulse");
    $("#btnAsignarSeleccionadas").classList.add("cursor-wait", "animate-pulse");
    $("#btnAsignarFiltradas").classList.add("cursor-wait", "animate-pulse");
  } else {
    $("#btnAsignarFinal").innerHTML =
      `<i data-lucide="check-circle" class="w-4 h-4"></i> Asignar cuentas al asesor`;
    $("#btnAsignarSeleccionadas").innerHTML =
      `<i data-lucide="users" class="w-4 h-4"></i> Asignar Seleccionadas`;
    $("#btnAsignarFiltradas").innerHTML =
      `<i data-lucide="shuffle" class="w-4 h-4"></i> Asignar Todos los Filtrados`;

    $("#btnAsignarFinal").classList.remove("cursor-wait", "animate-pulse");
    $("#btnAsignarSeleccionadas").classList.remove(
      "cursor-wait",
      "animate-pulse",
    );
    $("#btnAsignarFiltradas").classList.remove("cursor-wait", "animate-pulse");
  }

  actualizarEstadoBotones();
}

function obtenerPaginasVisibles() {
  const total = paginacion.totalPaginas;
  const actual = paginacion.pagina;
  const paginas = [];

  if (total <= 7) {
    for (let i = 1; i <= total; i++) paginas.push(i);
    return paginas;
  }

  paginas.push(1);

  if (actual > 4) paginas.push("...");

  const inicio = Math.max(2, actual - 1);
  const fin = Math.min(total - 1, actual + 1);
  for (let i = inicio; i <= fin; i++) paginas.push(i);

  if (actual < total - 3) paginas.push("...");

  paginas.push(total);
  return paginas;
}

function renderPaginacion() {
  const info = $("#paginacionInfo");
  const items = $("#paginacionItems");
  const labelCantidad = $("#cantidadPorPaginaLabel");
  const modal = $("#modalCantidadPagina");

  if (!info || !items) return;

  labelCantidad.textContent = cuentasPorPagina;
  modal.classList.toggle("hidden", !modalCantidadPaginaAbierto);
  modal.classList.toggle("visible", modalCantidadPaginaAbierto);

  if (cargandoInicial || cargandoCuentas) {
    info.textContent = "Cargando registros...";
    items.innerHTML = "";
    return;
  }

  info.textContent = `Mostrando ${paginacion.desde} a ${paginacion.hasta} de ${paginacion.total} cuentas`;

  const botonClase =
    "w-9 h-9 flex justify-center items-center text-sm text-[#8392ab] border border-[#dee2e6] shadow-sm rounded-full cursor-pointer transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed";

  const botones = [];
  botones.push(`
    <button class="pagPrev ${botonClase} bg-transparent hover:bg-gray-100" ${paginacion.pagina <= 1 || asignando ? "disabled" : ""}>
      <i data-lucide="chevron-left" class="w-4 h-4"></i>
    </button>
  `);

  obtenerPaginasVisibles().forEach((page) => {
    if (page === "...") {
      botones.push(
        `<span class="w-9 h-9 flex items-center justify-center text-sm text-gray-400">...</span>`,
      );
      return;
    }

    const activa = page === paginacion.pagina;
    botones.push(`
      <button data-page="${page}" class="pagItem ${botonClase} ${activa ? "bg-[#09c] text-white" : "bg-transparent hover:bg-gray-100"}" ${asignando ? "disabled" : ""}>
        ${page}
      </button>
    `);
  });

  botones.push(`
    <button class="pagNext ${botonClase} bg-transparent hover:bg-gray-100" ${paginacion.pagina >= paginacion.totalPaginas || asignando ? "disabled" : ""}>
      <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </button>
  `);

  items.innerHTML = botones.join("");

  document.querySelectorAll(".pagItem").forEach((btn) => {
    btn.addEventListener("click", () =>
      cambiarPagina(Number(btn.dataset.page)),
    );
  });

  const prev = document.querySelector(".pagPrev");
  const next = document.querySelector(".pagNext");
  if (prev)
    prev.addEventListener("click", () => cambiarPagina(paginacion.pagina - 1));
  if (next)
    next.addEventListener("click", () => cambiarPagina(paginacion.pagina + 1));
}

function render() {
  renderCuentas();
  renderAsesores();
  renderResumen();
  renderCheckTodos();
  renderBotones();
  renderPaginacion();
  lucide.createIcons();
}

async function cargarCuentas() {
  try {
    cargandoCuentas = true;
    render();

    const data = await apiGet("cuentas", obtenerParametrosCuentas());
    cuentas = data.cuentas || [];
    paginacion = data.paginacion || paginacion;
    paginaActual = paginacion.pagina;

    if (data.tablaSeleccionada?.id_table && $("#filtroCartera").value === "") {
      $("#filtroCartera").value = String(data.tablaSeleccionada.id_table);
    }

    cargandoCuentas = false;
    render();
  } catch (error) {
    cargandoCuentas = false;
    $("#tablaCuentas").innerHTML = `
      <tr>
        <td colspan="9" class="px-4 py-8 text-center text-red-500">
          ${escaparHTML(error.message)}
        </td>
      </tr>
    `;
    renderBotones();
    renderPaginacion();
  }
}

async function cargarDatosIniciales() {
  try {
    cargandoInicial = true;

    setSelectLoading($("#filtroCartera"));
    setSelectLoading($("#filtroDistrito"));
    setSelectLoading($("#filtroSegmento"));
    setSelectLoading($("#filtroEstado"));

    $("#filtroFecha").disabled = true;
    $("#filtroFechaHasta").disabled = true;
    $("#busquedaGlobal").disabled = true;
    $("#filtroFecha").classList.add(
      "animate-pulse",
      "bg-gray-100",
      "cursor-wait",
    );
    $("#filtroFechaHasta").classList.add(
      "animate-pulse",
      "bg-gray-100",
      "cursor-wait",
    );

    render();

    const data = await apiGet("inicial", obtenerParametrosCuentas());

    asesores = data.asesores || [];
    cuentas = data.cuentas || [];
    filtrosBackend = data.filtros || filtrosBackend;
    paginacion = data.paginacion || paginacion;
    paginaActual = paginacion.pagina;

    setSelectOptions(
      $("#filtroCartera"),
      filtrosBackend.carteras || [],
      "Seleccione cartera",
    );

    if (data.tablaSeleccionada?.id_table) {
      $("#filtroCartera").value = String(data.tablaSeleccionada.id_table);
    }
    setSelectOptions(
      $("#filtroDistrito"),
      filtrosBackend.distritos || [],
      "Todos",
    );
    setSelectOptions(
      $("#filtroSegmento"),
      filtrosBackend.segmentos || [],
      "Todos",
    );
    setSelectOptions($("#filtroEstado"), filtrosBackend.estados || [], "Todos");
    setSelectOptions($("#filtroPago"), filtrosBackend.pagos || [], "Todos");

    $("#filtroFecha").disabled = false;
    $("#filtroFechaHasta").disabled = false;
    $("#busquedaGlobal").disabled = false;
    $("#filtroFecha").classList.remove(
      "animate-pulse",
      "bg-gray-100",
      "cursor-wait",
    );
    $("#filtroFechaHasta").classList.remove(
      "animate-pulse",
      "bg-gray-100",
      "cursor-wait",
    );

    cuentasSeleccionadas = [];
    asesorSeleccionadoId = null;
    asesorPaginaActual = 1;
    cargandoInicial = false;

    render();
  } catch (error) {
    cargandoInicial = false;
    setControlesDisabled(false);
    $("#tablaCuentas").innerHTML = `
      <tr>
        <td colspan="9" class="px-4 py-8 text-center text-red-500">
          ${escaparHTML(error.message)}
        </td>
      </tr>
    `;
    $("#listaAsesores").innerHTML = `
      <div class="text-xs text-red-500 border border-red-100 rounded-xl p-3">
        ${escaparHTML(error.message)}
      </div>
    `;
    renderBotones();
    renderPaginacion();
  }
}

async function recargarDespuesDeAsignar() {
  const dataInicial = await apiGet("inicial", obtenerParametrosCuentas());
  asesores = dataInicial.asesores || [];
  cuentas = dataInicial.cuentas || [];
  filtrosBackend = dataInicial.filtros || filtrosBackend;
  paginacion = dataInicial.paginacion || paginacion;
  paginaActual = paginacion.pagina;

  if (
    dataInicial.tablaSeleccionada?.id_table &&
    $("#filtroCartera").value === ""
  ) {
    $("#filtroCartera").value = String(dataInicial.tablaSeleccionada.id_table);
  }
}

async function obtenerIdsFiltrados() {
  const data = await apiGet("ids_filtrados", obtenerFiltros());
  return data.ids || [];
}

function construirContenidoReasignacion(preview, asesorDestino) {
  const gruposHTML = (preview.grupos || [])
    .map((grupo) => {
      const cuentasHTML = (grupo.cuentas || [])
        .map(
          (cuenta) => `
            <li class="py-1">
              <span class="font-semibold text-gray-800">${escaparHTML(cuenta.cuenta)}</span>
              ${cuenta.cliente ? `<span class="text-gray-500">/ ${escaparHTML(cuenta.cliente)}</span>` : ""}
            </li>
          `,
        )
        .join("");

      const restantes = grupo.cantidad - (grupo.cuentas || []).length;
      const restantesHTML =
        restantes > 0
          ? `<li class="py-1 text-gray-500">... y ${restantes} cuenta(s) más</li>`
          : "";

      return `
        <div class="border border-amber-100 bg-amber-50/60 rounded-xl p-3">
          <p class="text-sm text-gray-800">
            <strong>${escaparHTML(grupo.asesor_actual)}</strong> tiene
            <strong>${escaparHTML(grupo.cantidad)}</strong> cuenta(s) asignada(s):
          </p>
          <ul class="mt-2 list-disc pl-5 text-xs text-gray-700">
            ${cuentasHTML}${restantesHTML}
          </ul>
        </div>
      `;
    })
    .join("");

  return `
    <div class="space-y-4">
      <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
        <i data-lucide="triangle-alert" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>
          <p class="font-semibold">Hay ${escaparHTML(preview.totalConflictos)} cuenta(s) con gestor actual.</p>
          <p class="text-xs mt-1">Puedes continuar. El historial se conservará.</p>
        </div>
      </div>

      <div class="space-y-3">${gruposHTML}</div>

      <p class="text-sm text-gray-700">
        ¿Confirmas la reasignación al gestor
        <strong>${escaparHTML(asesorDestino.nombre)}</strong>?
      </p>
    </div>
  `;
}

async function confirmarReasignacion({
  ids = [],
  usarFiltros = false,
  asesor,
}) {
  const preview = await apiPost("preview_reasignacion", {
    ids,
    usarFiltros,
    filtros: usarFiltros ? obtenerFiltros() : undefined,
    cartera: $("#filtroCartera").value,
    asesorId: asesor.id,
  });

  if (!preview.tieneConflictos) {
    return true;
  }

  return mostrarModalConfirmacion({
    titulo: "Confirmar reasignación",
    subtitulo: "La cuenta pasará al gestor seleccionado.",
    contenidoHTML: construirContenidoReasignacion(preview, asesor),
    textoAceptar: "Sí, reasignar",
    textoCancelar: "Cancelar",
    claseAceptar: "bg-amber-600 hover:bg-amber-700",
  });
}

async function asignarCuentas(ids) {
  const asesor = obtenerAsesor(asesorSeleccionadoId);

  if (!asesor) {
    await mostrarModalAviso({
      titulo: "Selecciona un asesor",
      mensaje: "Elige un asesor antes de asignar cuentas.",
      icono: "user-check",
    });
    return;
  }

  if (ids.length === 0) {
    await mostrarModalAviso({
      titulo: "Selecciona cuentas",
      mensaje: "Marca al menos una cuenta para continuar.",
      icono: "check-square",
    });
    return;
  }

  try {
    const continuar = await confirmarReasignacion({ ids, asesor });
    if (!continuar) return;

    asignando = true;
    setControlesDisabled(true);
    render();

    const data = await apiPost("asignar", {
      ids,
      cartera: $("#filtroCartera").value,
      asesorId: asesor.id,
    });

    await recargarDespuesDeAsignar();

    cuentasSeleccionadas = [];
    asignando = false;
    setControlesDisabled(false);

    mostrarToast(
      data.message ||
        `Las cuentas fueron asignadas correctamente a ${asesor.nombre}.`,
    );
    render();
  } catch (error) {
    asignando = false;
    setControlesDisabled(false);
    mostrarErrorAsignacion(error);
    render();
  }
}

async function asignarCuentasFiltradas() {
  const asesor = obtenerAsesor(asesorSeleccionadoId);

  if (!asesor) {
    await mostrarModalAviso({
      titulo: "Selecciona un asesor",
      mensaje: "Elige un asesor antes de asignar cuentas.",
      icono: "user-check",
    });
    return;
  }

  if (paginacion.total === 0) {
    await mostrarModalAviso({
      titulo: "Sin cuentas para asignar",
      mensaje: "No hay cuentas con los filtros actuales.",
      icono: "filter-x",
    });
    return;
  }

  try {
    const continuarReasignacion = await confirmarReasignacion({
      usarFiltros: true,
      asesor,
    });
    if (!continuarReasignacion) return;

    const confirma = await mostrarModalConfirmacion({
      titulo: "Confirmar asignación filtrada",
      subtitulo:
        "Esta acción aplicará a todos los registros encontrados con los filtros actuales.",
      contenidoHTML: `
        <p>
          Se asignarán todas las cuentas que cumplen los filtros actuales
          (<strong>${escaparHTML(paginacion.total)}</strong>) al asesor
          <strong>${escaparHTML(asesor.nombre)}</strong>.
        </p>
        <p class="mt-3 text-gray-500">¿Deseas continuar?</p>
      `,
      textoAceptar: "Sí, asignar",
      textoCancelar: "Cancelar",
    });
    if (!confirma) return;

    asignando = true;
    setControlesDisabled(true);
    render();

    const data = await apiPost("asignar_filtradas", {
      asesorId: asesor.id,
      filtros: obtenerFiltros(),
    });

    await recargarDespuesDeAsignar();

    cuentasSeleccionadas = [];
    asignando = false;
    setControlesDisabled(false);

    mostrarToast(
      data.message ||
        `Las cuentas filtradas fueron asignadas correctamente a ${asesor.nombre}.`,
    );
    render();
  } catch (error) {
    asignando = false;
    setControlesDisabled(false);
    mostrarErrorAsignacion(error);
    render();
  }
}

function mostrarToast(mensaje) {
  $("#toastMensaje").textContent = mensaje;

  const toast = $("#toast");
  toast.classList.remove("hidden");
  toast.classList.add("flex");

  setTimeout(() => {
    ocultarToast();
  }, 4000);

  lucide.createIcons();
}

function ocultarToast() {
  const toast = $("#toast");
  toast.classList.add("hidden");
  toast.classList.remove("flex");
}

function cambiarPagina(page) {
  const nuevaPagina = Math.min(Math.max(1, page), paginacion.totalPaginas);
  if (nuevaPagina === paginaActual || cargandoCuentas || asignando) return;

  paginaActual = nuevaPagina;
  cargarCuentas();
}

function cambiarCantidadPorPagina(cantidad) {
  cuentasPorPagina = cantidad;
  paginaActual = 1;
  modalCantidadPaginaAbierto = false;
  cuentasSeleccionadas = [];
  cargarCuentas();
}

$("#checkTodos").addEventListener("change", async () => {
  const check = $("#checkTodos");

  if (check.checked) {
    try {
      seleccionGlobalCargando = true;
      render();

      const idsFiltrados = await obtenerIdsFiltrados();
      cuentasSeleccionadas = idsFiltrados;

      seleccionGlobalCargando = false;
      render();
    } catch (error) {
      seleccionGlobalCargando = false;
      check.checked = false;
      mostrarErrorAsignacion(error);
      render();
    }
  } else {
    cuentasSeleccionadas = [];
    render();
  }
});

$("#btnLimpiarSeleccion").addEventListener("click", () => {
  cuentasSeleccionadas = [];
  render();
});

$("#btnAsignarSeleccionadas").addEventListener("click", () => {
  asignarCuentas([...cuentasSeleccionadas]);
});

$("#btnAsignarFinal").addEventListener("click", () => {
  asignarCuentas([...cuentasSeleccionadas]);
});

$("#btnAsignarFiltradas").addEventListener("click", asignarCuentasFiltradas);

$("#btnLimpiarFiltros").addEventListener("click", () => {
  $("#filtroFecha").value = "";
  $("#filtroFechaHasta").value = "";
  $("#filtroCartera").value = "";
  $("#filtroDistrito").value = "";
  $("#filtroSegmento").value = "";
  $("#filtroEstado").value = "";
  $("#busquedaGlobal").value = "";

  cuentasSeleccionadas = [];
  paginaActual = 1;
  cargarCuentas();
});

$("#btnCerrarToast").addEventListener("click", ocultarToast);

$("#btnCantidadPagina").addEventListener("click", () => {
  if (asignando || cargandoInicial || cargandoCuentas) return;
  modalCantidadPaginaAbierto = !modalCantidadPaginaAbierto;
  renderPaginacion();
});

document.querySelectorAll(".cantidadPaginaOpcion").forEach((opcion) => {
  opcion.addEventListener("click", () => {
    cambiarCantidadPorPagina(Number(opcion.dataset.cantidad));
  });
});

document.addEventListener("click", (event) => {
  const contenedor = $("#selectorCantidadPagina");
  if (contenedor && !contenedor.contains(event.target)) {
    modalCantidadPaginaAbierto = false;
    renderPaginacion();
  }
});

[
  "#filtroFecha",
  "#filtroFechaHasta",
  "#filtroCartera",
  "#filtroDistrito",
  "#filtroSegmento",
  "#filtroEstado",
  "#filtroPago",
].forEach((selector) => {
  $(selector).addEventListener("change", () => {
    const fechaDesde = $("#filtroFecha").value;
    const fechaHasta = $("#filtroFechaHasta").value;

    if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
      $("#filtroFechaHasta").value = fechaDesde;
    }

    cuentasSeleccionadas = [];
    paginaActual = 1;
    cargarCuentas();
  });
});

$("#busquedaGlobal").addEventListener("input", () => {
  clearTimeout(busquedaTimer);
  busquedaTimer = setTimeout(() => {
    cuentasSeleccionadas = [];
    paginaActual = 1;
    cargarCuentas();
  }, 350);
});

cargarDatosIniciales();
