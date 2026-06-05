const $ = (selector) => document.querySelector(selector);

const estado = {
  idUsuario: Number($("#idUsuario")?.value || 0),
  cargando: true,
  guardando: false,
  asesor: null,
  cuentas: [],
  rutaDia: { existe: false, id_hoja_ruta: null, ids: [], cuentas: [] },
  resumenPendientes: { total: 0, fecha_mas_antigua: "" },
  rutaCuentas: [],
  seleccionadas: [],
  filtros: { distritos: [], productos: [], estados: [], criterios: [] },
  pagina: 1,
  porPagina: 10,
  paginacion: {
    pagina: 1,
    porPagina: 10,
    total: 0,
    totalPaginas: 1,
    desde: 0,
    hasta: 0,
  },
  seleccionGlobalCargando: false,
  dragTablaId: null,
  dragRutaId: null,
  mapaDireccion: null,
  marcadorDireccion: null,
  geocoderDireccion: null,
  direccionFuenteCorreccion: "",
  mapaRuta: null,
  marcadoresRuta: [],
  lineaRuta: null,
  directionsServiceRuta: null,
  directionsRenderersRuta: [],
  infoWindowRuta: null,
  dragMapaRutaId: null,
};

function hoyISO() {
  return new Date().toISOString().slice(0, 10);
}

function escaparHTML(valor) {
  return String(valor ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function crearModalAvisoSiNoExiste() {
  if (document.querySelector("#modalAvisoUx")) return;
  const modal = document.createElement("div");
  modal.id = "modalAvisoUx";
  modal.className =
    "fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/45 px-4";
  modal.innerHTML = `
    <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl border border-gray-100 overflow-hidden">
      <div class="p-5">
        <div class="flex items-start gap-3">
          <div id="modalAvisoIcono" class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 bg-blue-50 text-blue-600">
            <i data-lucide="info" class="w-5 h-5"></i>
          </div>
          <div class="min-w-0 flex-1">
            <h3 id="modalAvisoTitulo" class="text-sm font-bold text-gray-900">Aviso</h3>
            <p id="modalAvisoMensaje" class="mt-1 text-sm leading-5 text-gray-600"></p>
          </div>
        </div>
      </div>
      <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
        <button id="btnCerrarModalAviso" type="button" class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold">Entendido</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  modal.addEventListener("click", (event) => {
    if (event.target === modal) cerrarAviso();
  });
  document
    .querySelector("#btnCerrarModalAviso")
    .addEventListener("click", cerrarAviso);
}

function cerrarAviso() {
  const modal = document.querySelector("#modalAvisoUx");
  if (!modal) return;
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

function mensajeAmigable(
  mensaje,
  fallback = "No pudimos completar la acción. Inténtalo nuevamente.",
) {
  const texto = String(mensaje || "").trim();
  if (!texto) return fallback;
  const normalizado = texto.toLowerCase();

  if (
    normalizado.includes("google maps") &&
    normalizado.includes("no encontró")
  ) {
    return "No encontramos esa dirección. Revisa el texto o selecciona el punto en el mapa.";
  }
  if (
    normalizado.includes("google maps") &&
    (normalizado.includes("api") ||
      normalizado.includes("script") ||
      normalizado.includes("disponible"))
  ) {
    return "No se pudo cargar el mapa. Espera unos segundos y vuelve a intentar.";
  }
  if (
    normalizado.includes("data too long") ||
    normalizado.includes("sql") ||
    normalizado.includes("mysql") ||
    normalizado.includes("syntax") ||
    normalizado.includes("column")
  ) {
    return "No se pudo guardar la información. Revisa los datos ingresados o avisa a soporte.";
  }
  if (
    normalizado.includes("network") ||
    normalizado.includes("failed to fetch")
  ) {
    return "No hay conexión con el servidor. Revisa tu conexión e intenta nuevamente.";
  }
  if (normalizado.includes("hoja de ruta actualizada correctamente")) {
    return "La hoja de ruta se actualizó.";
  }
  if (normalizado.includes("hoja de ruta creada correctamente")) {
    return "Hoja de ruta creada.";
  }
  if (
    normalizado.includes("hoja de ruta") &&
    normalizado.includes("día actual")
  ) {
    return "Puedes trabajar la ruta semanal seleccionada.";
  }
  return texto.length > 160 ? fallback : texto;
}

function mostrarAviso(tipo = "info", titulo = "Aviso", mensaje = "") {
  crearModalAvisoSiNoExiste();
  const config = {
    success: {
      icono: "check-circle-2",
      clase: "bg-green-50 text-green-600",
      titulo: titulo || "Listo",
    },
    error: {
      icono: "circle-alert",
      clase: "bg-red-50 text-red-600",
      titulo: titulo || "No se pudo completar",
    },
    warning: {
      icono: "triangle-alert",
      clase: "bg-orange-50 text-orange-600",
      titulo: titulo || "Revisa este punto",
    },
    info: {
      icono: "info",
      clase: "bg-blue-50 text-blue-600",
      titulo: titulo || "Aviso",
    },
  };
  const item = config[tipo] || config.info;
  const modal = document.querySelector("#modalAvisoUx");
  const icono = document.querySelector("#modalAvisoIcono");
  const tituloEl = document.querySelector("#modalAvisoTitulo");
  const mensajeEl = document.querySelector("#modalAvisoMensaje");

  icono.className = `w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 ${item.clase}`;
  icono.innerHTML = `<i data-lucide="${item.icono}" class="w-5 h-5"></i>`;
  tituloEl.textContent = item.titulo;
  mensajeEl.textContent = mensajeAmigable(mensaje);
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  lucide.createIcons();
}

// Evita que cualquier aviso residual del módulo o de librerías auxiliares salga como alert del navegador.
// Se conserva una experiencia visual uniforme usando el modal de avisos.
window.alert = function (mensaje) {
  const mostrar = () =>
    mostrarAviso(
      "info",
      "Aviso",
      mensajeAmigable(mensaje, "Operación completada."),
    );
  if (document.body) {
    mostrar();
  } else {
    document.addEventListener("DOMContentLoaded", mostrar, { once: true });
  }
};

function formatoMoneda(valor) {
  return new Intl.NumberFormat("es-PE", {
    style: "currency",
    currency: "PEN",
    maximumFractionDigits: 0,
  }).format(Number(valor || 0));
}

function valorInput(selector) {
  return ($(selector)?.value || "").trim();
}

function partesUnicas(partes) {
  const vistas = new Set();
  return partes
    .map((parte) => String(parte || "").trim())
    .filter((parte) => {
      if (!parte) return false;
      const key = parte.toUpperCase();
      if (vistas.has(key)) return false;
      vistas.add(key);
      return true;
    });
}

function normalizarLatLng(latLng) {
  if (!latLng) return null;
  const lat =
    typeof latLng.lat === "function" ? latLng.lat() : Number(latLng.lat);
  const lng =
    typeof latLng.lng === "function" ? latLng.lng() : Number(latLng.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return { lat, lng };
}

function aplicarDireccionGuardadaEnEstado(direccionGuardada) {
  if (!direccionGuardada?.id_asignacion) return;
  const idAsignacion = Number(direccionGuardada.id_asignacion);
  const aplicar = (cuenta) => {
    if (Number(cuenta.id_asignacion) !== idAsignacion) return cuenta;
    const latitud = Number(direccionGuardada.latitud || 0);
    const longitud = Number(direccionGuardada.longitud || 0);
    return {
      ...cuenta,
      direccion_search: direccionGuardada.direccion_search || "",
      direccion_corregida: direccionGuardada.direccion_corregida || "",
      direccion_sugerida:
        direccionGuardada.direccion_corregida ||
        direccionGuardada.direccion_search ||
        "",
      distrito_corregido: direccionGuardada.distrito_corregido || "",
      ubigeo_corregido: direccionGuardada.ubigeo_corregido || "",
      latitud,
      longitud,
      coord_status: latitud !== 0 && longitud !== 0 ? "VALIDA" : "POR_VALIDAR",
    };
  };
  estado.cuentas = estado.cuentas.map(aplicar);
  estado.rutaCuentas = estado.rutaCuentas.map(aplicar);
}

function extraerDistritoGoogle(resultado) {
  const componentes = resultado?.address_components || [];
  const preferidos = [
    "locality",
    "administrative_area_level_3",
    "sublocality_level_1",
    "sublocality",
  ];
  for (const tipo of preferidos) {
    const comp = componentes.find((item) => item.types?.includes(tipo));
    if (comp?.long_name) return comp.long_name;
  }
  return "";
}

function extraerUbigeoGoogle(resultado) {
  const componentes = resultado?.address_components || [];
  const obtener = (tipo) =>
    componentes.find((item) => item.types?.includes(tipo))?.long_name || "";
  const departamento = obtener("administrative_area_level_1");
  const provincia = obtener("administrative_area_level_2");
  const distrito = extraerDistritoGoogle(resultado);
  return partesUnicas([departamento, provincia, distrito]).join(" / ");
}

function queryParams(params) {
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== "") q.append(k, v);
  });
  return q.toString();
}

async function apiGet(action, params = {}) {
  const q = queryParams({ action, id_usuario: estado.idUsuario, ...params });
  const res = await fetch(`misCuentas_api.php?${q}`, {
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  });
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || data.ok === false) {
    throw new Error(
      data?.error || data?.message || "No se pudo consultar la información.",
    );
  }
  return data;
}

async function apiPost(action, payload = {}) {
  const res = await fetch(
    `misCuentas_api.php?action=${encodeURIComponent(action)}`,
    {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ id_usuario: estado.idUsuario, ...payload }),
    },
  );
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || data.ok === false) {
    throw new Error(
      data?.error || data?.message || "No se pudo completar la operación.",
    );
  }
  return data;
}

function criterioPersonalizadoActivo() {
  return $("#criterioRuta")?.value === "PERSONALIZADO";
}

function moverItemArray(array, idOrigen, idDestino, keyFn = (item) => item) {
  const origen = array.findIndex((item) => keyFn(item) === idOrigen);
  const destino = array.findIndex((item) => keyFn(item) === idDestino);
  if (origen < 0 || destino < 0 || origen === destino) return array;
  const copia = [...array];
  const [item] = copia.splice(origen, 1);
  copia.splice(destino, 0, item);
  return copia;
}

function ordenarSeleccionadasSegunTabla() {
  const idsVisibles = estado.cuentas.map((cuenta) => cuenta.id_asignacion);
  const seleccionadasVisibles = idsVisibles.filter((id) =>
    estado.seleccionadas.includes(id),
  );
  const seleccionadasNoVisibles = estado.seleccionadas.filter(
    (id) => !idsVisibles.includes(id),
  );
  estado.seleccionadas = [...seleccionadasVisibles, ...seleccionadasNoVisibles];
}

async function obtenerIdsFiltrados() {
  const data = await apiGet("ids_filtrados", obtenerFiltros());
  return Array.isArray(data.ids)
    ? data.ids.map(Number).filter((id) => id > 0)
    : [];
}

function cuentaPorAsignacion(idAsignacion) {
  const id = Number(idAsignacion);
  return (
    estado.cuentas.find((cuenta) => Number(cuenta.id_asignacion) === id) ||
    estado.rutaCuentas.find((cuenta) => Number(cuenta.id_asignacion) === id)
  );
}

function fusionarCuentaRuta(cuentaActualizada) {
  if (!cuentaActualizada?.id_asignacion) return;
  const id = Number(cuentaActualizada.id_asignacion);
  const existente = estado.rutaCuentas.findIndex(
    (cuenta) => Number(cuenta.id_asignacion) === id,
  );
  if (existente >= 0)
    estado.rutaCuentas[existente] = {
      ...estado.rutaCuentas[existente],
      ...cuentaActualizada,
    };
}

function aplicarRutaDia(rutaDia, forzar = false) {
  if (!rutaDia) return;
  estado.rutaDia = {
    existe: Boolean(rutaDia.existe),
    id_hoja_ruta: rutaDia.id_hoja_ruta || null,
    ids: Array.isArray(rutaDia.ids)
      ? rutaDia.ids.map(Number).filter((id) => id > 0)
      : [],
    cuentas: Array.isArray(rutaDia.cuentas) ? rutaDia.cuentas : [],
  };
  estado.rutaCuentas = estado.rutaDia.cuentas;

  if (forzar || estado.seleccionadas.length === 0) {
    estado.seleccionadas = [...estado.rutaDia.ids];
  } else {
    estado.rutaDia.ids.forEach((id) => {
      if (!estado.seleccionadas.includes(id)) estado.seleccionadas.push(id);
    });
  }
}

function aplicarResumenPendientes(resumen) {
  estado.resumenPendientes = {
    total: Number(resumen?.total || 0),
    fecha_mas_antigua: resumen?.fecha_mas_antigua || "",
  };
}

function renderResumenPendientes() {
  const panel = $("#panelPendientes");
  if (!panel) return;
  const total = Number(estado.resumenPendientes?.total || 0);
  if (total <= 0) {
    panel.classList.add("hidden");
    return;
  }
  panel.classList.remove("hidden");
  const fecha = estado.resumenPendientes.fecha_mas_antigua;
  const texto =
    total === 1
      ? "Hay 1 visita pendiente dentro de la planificación semanal."
      : `Hay ${total} visitas pendientes dentro de la planificación semanal.`;
  $("#textoPendientes").textContent = fecha
    ? `${texto} Pendiente desde: ${fecha}.`
    : texto;
}

function formatearHora(fecha) {
  if (!fecha) return "-";
  const partes = String(fecha).split(" ");
  if (partes.length < 2) return fecha;
  return partes[1].slice(0, 5);
}

function htmlVisitasHoy(cuenta) {
  const cantidad = Number(cuenta.cantidad_visitas_dia || 0);
  if (cantidad <= 0) {
    return `<span class="inline-flex items-center gap-1 text-gray-400"><i data-lucide="minus-circle" class="w-3 h-3"></i>Sin visitas</span>`;
  }
  const texto = cantidad === 1 ? "1 visita" : `${cantidad} visitas`;
  return `
    <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
      <i data-lucide="footprints" class="w-3 h-3"></i>${texto}
    </span>
    <span class="block text-[10px] text-gray-400">Última: ${escaparHTML(formatearHora(cuenta.ultima_fecha_visita))}</span>
  `;
}

function htmlPendienteAnterior(cuenta) {
  if (!cuenta.pendiente_anterior) return "";
  return `
    <span class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 text-[10px] font-semibold">
      <i data-lucide="calendar-clock" class="w-3 h-3"></i>Pendiente anterior
    </span>
  `;
}

async function agregarPendientesARuta() {
  try {
    const btn = $("#btnAgregarPendientes");
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i>Agregando...`;
      lucide.createIcons();
    }
    const data = await apiGet("pendientes_reprogramar");
    const pendientes = Array.isArray(data.pendientes) ? data.pendientes : [];
    if (pendientes.length === 0) {
      aplicarResumenPendientes(data.resumen_pendientes || { total: 0 });
      render();
      mostrarAviso(
        "info",
        "Sin pendientes",
        "No tienes visitas pendientes para agregar.",
      );
      return;
    }

    let agregadas = 0;
    pendientes.forEach((cuenta) => {
      const id = Number(cuenta.id_asignacion);
      if (!id) return;
      fusionarCuentaRuta(cuenta);
      if (
        !estado.rutaCuentas.some((item) => Number(item.id_asignacion) === id)
      ) {
        estado.rutaCuentas.push(cuenta);
      }
      if (!estado.seleccionadas.includes(id)) {
        estado.seleccionadas.push(id);
        agregadas++;
      }
    });
    aplicarResumenPendientes(
      data.resumen_pendientes || estado.resumenPendientes,
    );
    render();
    mostrarAviso(
      "success",
      "Pendientes agregadas",
      agregadas > 0
        ? `${agregadas} visita(s) se agregaron a la ruta semanal.`
        : "Las visitas pendientes ya estaban consideradas en tu ruta semanal.",
    );
  } catch (error) {
    mostrarAviso("error", "No se pudo agregar", error.message);
  } finally {
    const btn = $("#btnAgregarPendientes");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<i data-lucide="plus-circle" class="w-4 h-4"></i>Agregar pendientes`;
      lucide.createIcons();
    }
  }
}

function cuentasSeleccionadasOrdenadasParaVista() {
  return estado.seleccionadas.map(cuentaPorAsignacion).filter(Boolean);
}

function obtenerFiltros() {
  return {
    fechaRuta: $("#filtroFechaRuta").value,
    distrito: $("#filtroDistrito").value,
    producto: $("#filtroProducto").value,
    estadoVisita: $("#filtroEstadoVisita").value,
    rangoImporte: $("#filtroRangoImporte").value,
    criterioRuta: $("#criterioRuta").value || "CERCANIA",
    page: estado.pagina,
    perPage: estado.porPagina,
  };
}

function textoCriterio(codigo) {
  const fijo = {
    CERCANIA: "Por Proximidad",
    DISTRITO: "Por Distrito",
    MONTO_MAYOR: "Montos Mayores",
    MONTO_MENOR: "Montos Menores",
    PERSONALIZADO: "Personalizado",
  };
  const criterio = estado.filtros.criterios.find(
    (item) => item.codigo === codigo,
  );
  return fijo[codigo] || criterio?.descripcion || codigo;
}

function setOptions(
  select,
  opciones,
  placeholder = "Todos",
  valueKey = null,
  labelKey = null,
) {
  select.innerHTML = `<option value="">${escaparHTML(placeholder)}</option>`;
  opciones.forEach((opcion) => {
    const value = valueKey ? opcion[valueKey] : opcion;
    const label = labelKey ? opcion[labelKey] : opcion;
    select.innerHTML += `<option value="${escaparHTML(value)}">${escaparHTML(label)}</option>`;
  });
}

function setCriterios(opciones) {
  const select = $("#criterioRuta");
  const actuales = Array.from(select.options).map((opt) => opt.value);
  opciones.forEach((opcion) => {
    if (!actuales.includes(opcion.codigo)) {
      select.innerHTML += `<option value="${escaparHTML(opcion.codigo)}">${escaparHTML(opcion.descripcion)}</option>`;
    }
  });
}

function renderLoader() {
  $("#tablaCuentas").innerHTML = Array.from({ length: 5 })
    .map(
      () => `
    <tr class="animate-pulse">
      <td class="px-4 py-4"><div class="h-4 w-4 rounded bg-gray-200"></div></td>
      <td class="px-3 py-4"><div class="h-4 w-36 rounded bg-gray-200 mb-2"></div><div class="h-3 w-16 rounded bg-gray-100"></div></td>
      <td class="px-3 py-4"><div class="h-4 w-44 rounded bg-gray-200 mb-2"></div><div class="h-4 w-32 rounded bg-gray-100"></div></td>
      <td class="px-3 py-4"><div class="h-4 w-24 rounded bg-gray-200"></div></td>
      <td class="px-3 py-4"><div class="h-4 w-16 rounded bg-gray-200 ml-auto"></div></td>
      <td class="px-3 py-4"><div class="h-4 w-16 rounded bg-gray-200"></div></td>
      <td class="px-3 py-4"><div class="h-6 w-24 rounded-full bg-gray-200"></div></td>
      <td class="px-3 py-4"><div class="h-5 w-20 rounded bg-gray-200"></div></td>
      <td class="px-3 py-4"><div class="h-8 w-24 rounded bg-gray-200"></div></td>
    </tr>
  `,
    )
    .join("");
}

function badgeEstado(codigo, texto) {
  const clases = {
    AGENDADO: "bg-blue-50 text-blue-600 border-blue-100",
    VISITADO: "bg-green-50 text-green-600 border-green-100",
    NO_ENCONTRADO: "bg-orange-50 text-orange-600 border-orange-100",
    NO_VISITADO: "bg-amber-50 text-amber-700 border-amber-100",
    PENDIENTE_VISITA: "bg-gray-100 text-gray-500 border-gray-200",
    EN_CAMINO: "bg-indigo-50 text-indigo-600 border-indigo-100",
    REPROGRAMADO: "bg-yellow-50 text-yellow-700 border-yellow-100",
    CANCELADO: "bg-gray-100 text-gray-500 border-gray-200",
    PENDIENTE: "bg-gray-100 text-gray-500 border-gray-200",
  };
  const iconos = {
    AGENDADO: "calendar-days",
    VISITADO: "check-circle-2",
    NO_ENCONTRADO: "map-pin-off",
    NO_VISITADO: "calendar-x",
    PENDIENTE_VISITA: "clock-3",
    EN_CAMINO: "navigation",
    REPROGRAMADO: "calendar-clock",
    CANCELADO: "ban",
    PENDIENTE: "clock-3",
  };
  return `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full border ${clases[codigo] || clases.PENDIENTE}"><i data-lucide="${iconos[codigo] || "clock-3"}" class="w-3 h-3"></i>${escaparHTML(texto)}</span>`;
}

function renderTabla() {
  if (estado.cargando) {
    renderLoader();
    return;
  }

  $("#totalCuentas").textContent = estado.paginacion.total;

  if (estado.cuentas.length === 0) {
    $("#tablaCuentas").innerHTML =
      `<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No se encontraron cuentas asignadas con los filtros seleccionados.</td></tr>`;
    return;
  }

  const permiteDragTabla = criterioPersonalizadoActivo();

  $("#tablaCuentas").innerHTML = estado.cuentas
    .map((cuenta, index) => {
      const checked = estado.seleccionadas.includes(cuenta.id_asignacion);
      const sugerida = cuenta.direccion_sugerida || "Sin dirección sugerida";
      const coordValida = cuenta.coord_status === "VALIDA";
      const numeroOrden =
        cuenta.orden_visita || estado.paginacion.desde + index;
      const claseDrag = permiteDragTabla ? "cursor-move" : "";

      return `
      <tr draggable="${permiteDragTabla ? "true" : "false"}" data-id-asignacion="${cuenta.id_asignacion}" class="filaCuenta ${claseDrag} ${checked ? "bg-blue-50/60" : "bg-white"} hover:bg-blue-50/40">
        <td class="px-4 py-3 align-middle">
          <input type="checkbox" class="checkCuenta rounded border-gray-300 accent-red-500" data-id-asignacion="${cuenta.id_asignacion}" ${checked ? "checked" : ""}>
        </td>
        <td class="px-3 py-3 align-middle">
          <div class="flex items-start gap-2">
            <span class="mt-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center font-bold">${numeroOrden}</span>
            <div>
              <strong class="block leading-4">${permiteDragTabla ? '<i data-lucide="grip-vertical" class="inline w-3 h-3 text-gray-400 mr-1"></i>' : ""}${escaparHTML(cuenta.cliente)}</strong>
              <span class="block text-[10px] text-gray-500">${escaparHTML(cuenta.cuenta)}</span>
              ${htmlPendienteAnterior(cuenta)}
            </div>
          </div>
        </td>
        <td class="px-3 py-3 align-middle max-w-[280px]">
          <div class="text-[10px] text-gray-500"><strong class="text-gray-700">ORG</strong> ${escaparHTML(cuenta.direccion_original)}</div>
          <div class="mt-1 inline-block bg-blue-50 border border-blue-100 rounded px-1.5 py-1 text-blue-600 text-[11px] leading-4">${escaparHTML(sugerida)}</div>
        </td>
        <td class="px-3 py-3 align-middle">
          <strong class="block">${escaparHTML(cuenta.distrito)}</strong>
          <span class="block text-[10px] text-gray-400">${escaparHTML(cuenta.producto)}</span>
        </td>
        <td class="px-3 py-3 align-middle text-right font-bold">${formatoMoneda(cuenta.importe)}</td>
        <td class="px-3 py-3 align-middle">
          <span class="inline-flex items-center gap-1 ${coordValida ? "text-emerald-600" : "text-orange-500"}">
            <i data-lucide="${coordValida ? "map-pinned" : "circle-alert"}" class="w-3 h-3"></i>
            ${coordValida ? "Válida" : "Por Validar"}
          </span>
        </td>
        <td class="px-3 py-3 align-middle">${badgeEstado(cuenta.estado_visita_codigo, cuenta.estado_visita)}</td>
        <td class="px-3 py-3 align-middle">${htmlVisitasHoy(cuenta)}</td>
        <td class="px-3 py-3 align-middle">
          <button type="button" class="btnDireccion px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-[11px]" data-id-asignacion="${cuenta.id_asignacion}">
            Dirección
          </button>
        </td>
      </tr>
    `;
    })
    .join("");

  document.querySelectorAll(".checkCuenta").forEach((check) => {
    check.addEventListener("change", () => {
      const id = Number(check.dataset.idAsignacion);
      if (check.checked) {
        if (!estado.seleccionadas.includes(id)) estado.seleccionadas.push(id);
      } else {
        estado.seleccionadas = estado.seleccionadas.filter(
          (item) => item !== id,
        );
      }
      render();
    });
  });

  document.querySelectorAll(".btnDireccion").forEach((btn) => {
    btn.addEventListener("click", () =>
      abrirModalDireccion(Number(btn.dataset.idAsignacion)),
    );
  });

  configurarDragTabla();
}

function configurarDragTabla() {
  if (!criterioPersonalizadoActivo()) return;

  document.querySelectorAll(".filaCuenta").forEach((fila) => {
    fila.addEventListener("dragstart", (event) => {
      estado.dragTablaId = Number(fila.dataset.idAsignacion);
      fila.classList.add("opacity-50");
      event.dataTransfer.effectAllowed = "move";
    });

    fila.addEventListener("dragover", (event) => {
      event.preventDefault();
      fila.classList.add("bg-blue-100");
    });

    fila.addEventListener("dragleave", () =>
      fila.classList.remove("bg-blue-100"),
    );

    fila.addEventListener("drop", (event) => {
      event.preventDefault();
      fila.classList.remove("bg-blue-100");
      const destino = Number(fila.dataset.idAsignacion);
      if (!estado.dragTablaId || estado.dragTablaId === destino) return;
      estado.cuentas = moverItemArray(
        estado.cuentas,
        estado.dragTablaId,
        destino,
        (cuenta) => cuenta.id_asignacion,
      );
      ordenarSeleccionadasSegunTabla();
      estado.dragTablaId = null;
      render();
    });

    fila.addEventListener("dragend", () => {
      fila.classList.remove("opacity-50", "bg-blue-100");
      estado.dragTablaId = null;
    });
  });
}

function renderVistaRuta() {
  const seleccionadas = cuentasSeleccionadasOrdenadasParaVista();
  $("#totalParadas").textContent = `${estado.seleccionadas.length} Paradas`;

  if (seleccionadas.length === 0) {
    $("#vistaRuta").innerHTML =
      `<div class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-xl p-4 text-center">Selecciona cuentas o carga tu ruta semanal.</div>`;
  } else {
    $("#vistaRuta").innerHTML = seleccionadas
      .map(
        (cuenta, index) => `
      <div draggable="true" data-id-asignacion="${cuenta.id_asignacion}" class="rutaItem cursor-move relative ruta-linea pl-10">
        <span class="absolute left-0 top-0 w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold">${index + 1}</span>
        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
          <div class="flex justify-between gap-3">
            <strong class="text-xs leading-4"><i data-lucide="grip-vertical" class="inline w-3 h-3 text-gray-400 mr-1"></i>${escaparHTML(cuenta.cliente)}</strong>
            <span class="text-xs font-bold text-blue-600 whitespace-nowrap">${formatoMoneda(cuenta.importe)}</span>
          </div>
          <p class="mt-1 text-[10px] text-gray-400 truncate">${escaparHTML(cuenta.direccion_sugerida || cuenta.direccion_original)}</p>
          <div class="mt-2 flex items-center justify-between gap-2">
            ${badgeEstado(cuenta.estado_visita_codigo, cuenta.estado_visita)}
            <span class="text-[10px] text-gray-400">${escaparHTML(cuenta.distrito)}</span>
          </div>
          <div class="mt-2 flex items-center justify-between gap-2 text-[10px]">
            <span>${htmlVisitasHoy(cuenta)}</span>
            ${cuenta.pendiente_anterior ? '<span class="text-amber-600 font-semibold">Pendiente anterior</span>' : ""}
          </div>
        </div>
      </div>
    `,
      )
      .join("");
  }

  const haySeleccion = estado.seleccionadas.length > 0;
  const btnCrear = $("#btnCrearRuta");
  if (btnCrear && !estado.guardando) {
    btnCrear.innerHTML = `<i data-lucide="clipboard-plus" class="w-4 h-4"></i>${estado.rutaDia.existe ? "Actualizar Ruta Semanal" : "Crear Ruta Semanal"}`;
  }
  $("#btnCrearRuta").disabled = !haySeleccion || estado.guardando;
  $("#btnMapa").disabled = !haySeleccion;
  const btnValidarMasivoRuta = $("#btnValidarMasivoRuta");
  if (btnValidarMasivoRuta)
    btnValidarMasivoRuta.disabled =
      !haySeleccion || cuentasPendientesCoordenadas().length === 0;
  $("#btnLimpiar").disabled = !haySeleccion || estado.guardando;
  $("#btnReordenar").disabled = !haySeleccion || estado.guardando;
  configurarDragVistaRuta();
}

function configurarDragVistaRuta() {
  document.querySelectorAll(".rutaItem").forEach((item) => {
    item.addEventListener("dragstart", (event) => {
      estado.dragRutaId = Number(item.dataset.idAsignacion);
      item.classList.add("opacity-50");
      event.dataTransfer.effectAllowed = "move";
    });

    item.addEventListener("dragover", (event) => {
      event.preventDefault();
      item.classList.add("bg-blue-50");
    });

    item.addEventListener("dragleave", () =>
      item.classList.remove("bg-blue-50"),
    );

    item.addEventListener("drop", (event) => {
      event.preventDefault();
      item.classList.remove("bg-blue-50");
      const destino = Number(item.dataset.idAsignacion);
      if (!estado.dragRutaId || estado.dragRutaId === destino) return;
      estado.seleccionadas = moverItemArray(
        estado.seleccionadas,
        estado.dragRutaId,
        destino,
      );
      estado.dragRutaId = null;
      render();
      if (modalMapaAbierto()) renderMapaCompletoRuta();
    });

    item.addEventListener("dragend", () => {
      item.classList.remove("opacity-50", "bg-blue-50");
      estado.dragRutaId = null;
    });
  });
}

function renderPaginacion() {
  const p = estado.paginacion;
  $("#paginacionInfo").textContent =
    `Mostrando ${p.desde} a ${p.hasta} de ${p.total} cuentas`;

  const total = p.totalPaginas || 1;
  const actual = p.pagina || 1;
  const paginas = [];
  const inicio = Math.max(1, actual - 2);
  const fin = Math.min(total, actual + 2);
  if (inicio > 1) paginas.push(1, "...");
  for (let i = inicio; i <= fin; i++) paginas.push(i);
  if (fin < total) paginas.push("...", total);

  $("#paginacionItems").innerHTML = `
    <button class="pageBtn w-8 h-8 rounded-full border border-gray-200 text-gray-500 hover:bg-gray-100 disabled:opacity-40" data-page="${actual - 1}" ${actual <= 1 ? "disabled" : ""}><i data-lucide="chevron-left" class="w-4 h-4 mx-auto"></i></button>
    ${paginas.map((page) => (page === "..." ? `<span class="w-8 h-8 flex items-center justify-center text-xs text-gray-400">...</span>` : `<button class="pageBtn w-8 h-8 rounded-full border border-gray-200 text-sm ${page === actual ? "bg-blue-500 text-white" : "text-gray-500 hover:bg-gray-100"}" data-page="${page}">${page}</button>`)).join("")}
    <button class="pageBtn w-8 h-8 rounded-full border border-gray-200 text-gray-500 hover:bg-gray-100 disabled:opacity-40" data-page="${actual + 1}" ${actual >= total ? "disabled" : ""}><i data-lucide="chevron-right" class="w-4 h-4 mx-auto"></i></button>
  `;

  document.querySelectorAll(".pageBtn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const page = Number(btn.dataset.page);
      if (!page || page < 1 || page > total || page === estado.pagina) return;
      estado.pagina = page;
      await cargarCuentas();
    });
  });
}

function renderCheckTodos() {
  const checkTodos = $("#checkTodos");
  const totalFiltrado = estado.paginacion.total || 0;
  const seleccionadas = estado.seleccionadas.length;

  checkTodos.checked = totalFiltrado > 0 && seleccionadas >= totalFiltrado;
  checkTodos.indeterminate = seleccionadas > 0 && seleccionadas < totalFiltrado;
  checkTodos.disabled =
    totalFiltrado === 0 ||
    estado.guardando ||
    estado.cargando ||
    estado.seleccionGlobalCargando;
}

function render() {
  $("#criterioActivo").textContent = textoCriterio($("#criterioRuta").value);
  renderTabla();
  renderVistaRuta();
  renderPaginacion();
  renderCheckTodos();
  renderResumenPendientes();
  lucide.createIcons();
}

async function cargarInicial() {
  try {
    estado.cargando = true;
    render();
    const data = await apiGet("inicial", obtenerFiltros());
    estado.asesor = data.asesor;
    estado.filtros = data.filtros;
    estado.cuentas = data.cuentas || [];
    estado.paginacion = data.paginacion;
    aplicarRutaDia(data.ruta_dia, true);
    aplicarResumenPendientes(data.resumen_pendientes);
    $("#nombreAsesor").textContent = estado.asesor?.nombre || "Sin asesor";
    setOptions($("#filtroDistrito"), estado.filtros.distritos);
    setOptions($("#filtroProducto"), estado.filtros.productos);
    setOptions(
      $("#filtroEstadoVisita"),
      estado.filtros.estados,
      "Todos",
      "codigo",
      "descripcion",
    );
    setCriterios(estado.filtros.criterios || []);
  } catch (error) {
    mostrarAviso("error", "No se pudo cargar", error.message);
  } finally {
    estado.cargando = false;
    render();
  }
}

async function cargarCuentas(forzarRutaDia = false) {
  try {
    estado.cargando = true;
    render();
    const data = await apiGet("cuentas", obtenerFiltros());
    estado.cuentas = data.cuentas || [];
    estado.paginacion = data.paginacion;
    aplicarRutaDia(data.ruta_dia, forzarRutaDia);
    aplicarResumenPendientes(data.resumen_pendientes);
  } catch (error) {
    mostrarAviso("error", "No se pudo cargar", error.message);
  } finally {
    estado.cargando = false;
    render();
  }
}

function abrirModalDireccion(idAsignacion) {
  const cuenta = estado.cuentas.find(
    (item) => item.id_asignacion === idAsignacion,
  );
  if (!cuenta) return;

  const distritoOriginal = cuenta.distrito_original || cuenta.distrito || "";
  const ubigeoOriginal = cuenta.ubigeo_original || cuenta.ubigeo || "";

  $("#direccionAsignacionId").value = idAsignacion;
  $("#direccionOriginalModal").value = cuenta.direccion_original || "";
  $("#distritoOriginalModal").value = distritoOriginal;
  $("#ubigeoOriginalModal").value = ubigeoOriginal;
  $("#ubicacionOriginalModal").textContent = ubigeoOriginal
    ? `Ubicación original: ${ubigeoOriginal}`
    : "Ubicación original no registrada.";

  // Search y sugerida se cargan solo desde geocampo_direccion_corregida; no se rellenan con la dirección original.
  $("#direccionSearchModal").value = cuenta.direccion_search || "";
  $("#direccionCorregidaModal").value = cuenta.direccion_corregida || "";
  $("#distritoCorregidoModal").value = cuenta.distrito_corregido || "";
  $("#ubigeoCorregidoModal").value = cuenta.ubigeo_corregido || "";
  $("#latitudDireccionModal").value = cuenta.latitud || "";
  $("#longitudDireccionModal").value = cuenta.longitud || "";
  limpiarMapaDireccionModal();

  $("#modalDireccion").classList.remove("hidden");
  $("#modalDireccion").classList.add("flex");

  if (cuenta.latitud && cuenta.longitud) {
    setTimeout(
      () =>
        mostrarMapaDireccion(
          Number(cuenta.latitud),
          Number(cuenta.longitud),
          false,
        ),
      50,
    );
  }

  lucide.createIcons();
}

function cerrarModalDireccion() {
  $("#modalDireccion").classList.add("hidden");
  $("#modalDireccion").classList.remove("flex");
}

async function guardarDireccion() {
  try {
    $("#btnGuardarDireccion").disabled = true;
    const payload = {
      id_asignacion: Number($("#direccionAsignacionId").value),
      direccion_original: $("#direccionOriginalModal").value,
      direccion_search: $("#direccionSearchModal").value,
      direccion_corregida: $("#direccionCorregidaModal").value,
      distrito_original: $("#distritoOriginalModal").value,
      distrito_corregido: $("#distritoCorregidoModal").value,
      ubigeo_original: $("#ubigeoOriginalModal").value,
      ubigeo_corregido: $("#ubigeoCorregidoModal").value,
      latitud: $("#latitudDireccionModal").value,
      longitud: $("#longitudDireccionModal").value,
      fuente_correccion: fuenteCorreccionParaGuardar(),
    };
    const data = await apiPost("guardar_direccion", payload);
    aplicarDireccionGuardadaEnEstado(data.direccion);
    render();
    mostrarAviso(
      "success",
      "Dirección guardada",
      "La dirección se guardó correctamente.",
    );
    cerrarModalDireccion();
    await cargarCuentas();
  } catch (error) {
    mostrarAviso("error", "No se pudo guardar", error.message);
  } finally {
    $("#btnGuardarDireccion").disabled = false;
  }
}

async function crearRuta() {
  if (estado.seleccionadas.length === 0) return;
  try {
    estado.guardando = true;
    $("#btnCrearRuta").innerHTML =
      `<i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i> Guardando...`;
    lucide.createIcons();
    const idsRuta = cuentasSeleccionadasOrdenadasParaVista().map(
      (cuenta) => cuenta.id_asignacion,
    );
    const todasLasSeleccionadasEstanCargadas = estado.seleccionadas.every(
      (id) => cuentaPorAsignacion(id),
    );
    const data = await apiPost("guardar_o_actualizar_ruta", {
      idsAsignacion: todasLasSeleccionadasEstanCargadas
        ? idsRuta
        : estado.seleccionadas,
      criterioRuta: "PERSONALIZADO",
      fechaRuta: $("#filtroFechaRuta").value || hoyISO(),
    });
    mostrarAviso(
      "success",
      "Hoja de ruta guardada",
      "La ruta se guardó correctamente.",
    );
    await cargarCuentas(true);
  } catch (error) {
    mostrarAviso("error", "No se pudo guardar", error.message);
  } finally {
    estado.guardando = false;
    render();
  }
}

function limpiarMapaDireccionModal() {
  const mapa = $("#mapaDireccionModal");
  const mensaje = $("#mensajeMapaDireccion");
  if (mapa) mapa.classList.add("hidden");
  if (mensaje) {
    mensaje.classList.add("hidden");
    mensaje.textContent = "";
  }
  estado.mapaDireccion = null;
  estado.marcadorDireccion = null;
}

function obtenerDireccionParaGoogleMaps() {
  const direccionCorregida = valorInput("#direccionCorregidaModal");
  const direccionSearch = valorInput("#direccionSearchModal");
  const direccionOriginal = valorInput("#direccionOriginalModal");
  const distritoCorregido = valorInput("#distritoCorregidoModal");
  const distritoOriginal = valorInput("#distritoOriginalModal");
  const ubigeoCorregido = valorInput("#ubigeoCorregidoModal");
  const ubigeoOriginal = valorInput("#ubigeoOriginalModal");

  const direccionBase =
    direccionCorregida || direccionSearch || direccionOriginal;
  const ubicacionBase = ubigeoCorregido || ubigeoOriginal;
  const distritoBase = distritoCorregido || distritoOriginal;

  return partesUnicas([
    direccionBase,
    distritoBase,
    ubicacionBase,
    "Perú",
  ]).join(", ");
}

function determinarFuenteCorreccionDireccion() {
  const direccionCorregida = valorInput("#direccionCorregidaModal");
  const direccionSearch = valorInput("#direccionSearchModal");

  if (direccionCorregida) return "ASESOR";
  if (direccionSearch) return "SEARCH";
  return "SISTEMA";
}

function fuenteCorreccionParaGuardar() {
  const fuenteActual = String(estado.direccionFuenteCorreccion || "")
    .trim()
    .toUpperCase();
  if (["SISTEMA", "ASESOR", "SEARCH", "SUPERVISOR", "OTRO"].includes(fuenteActual)) {
    return fuenteActual;
  }
  return determinarFuenteCorreccionDireccion();
}

function aplicarResultadoGoogle(resultado, sobrescribirSugerida = false) {
  const lat = resultado.geometry.location.lat();
  const lng = resultado.geometry.location.lng();
  const direccionGoogle = resultado.formatted_address || "";
  const distritoGoogle = extraerDistritoGoogle(resultado);
  const ubigeoGoogle = extraerUbigeoGoogle(resultado);

  $("#latitudDireccionModal").value = lat;
  $("#longitudDireccionModal").value = lng;

  // Google Maps nunca debe reemplazar Dirección Search. Solo completa o ajusta la Dirección Sugerida.
  if (sobrescribirSugerida || !valorInput("#direccionCorregidaModal")) {
    $("#direccionCorregidaModal").value = direccionGoogle;
  }
  if (!valorInput("#distritoCorregidoModal") && distritoGoogle) {
    $("#distritoCorregidoModal").value = distritoGoogle;
  }
  if (!valorInput("#ubigeoCorregidoModal") && ubigeoGoogle) {
    $("#ubigeoCorregidoModal").value = ubigeoGoogle;
  }
}

function mostrarMapaDireccion(lat, lng, permitirReverse = true) {
  if (!window.google?.maps?.Map) return;

  const contenedor = $("#mapaDireccionModal");
  const mensaje = $("#mensajeMapaDireccion");
  const posicion = { lat: Number(lat), lng: Number(lng) };
  if (!Number.isFinite(posicion.lat) || !Number.isFinite(posicion.lng)) return;

  contenedor.classList.remove("hidden");
  mensaje.classList.remove("hidden");
  mensaje.textContent =
    "Puedes mover el marcador o hacer clic en el mapa para ajustar la ubicación. La selección actualizará la dirección sugerida y las coordenadas.";

  estado.mapaDireccion = new google.maps.Map(contenedor, {
    center: posicion,
    zoom: 16,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: false,
  });

  estado.marcadorDireccion = new google.maps.Marker({
    position: posicion,
    map: estado.mapaDireccion,
    draggable: true,
  });

  estado.mapaDireccion.addListener("click", (event) => {
    actualizarUbicacionDesdeMapa(event.latLng);
  });

  estado.marcadorDireccion.addListener("dragend", (event) => {
    actualizarUbicacionDesdeMapa(event.latLng);
  });

  if (permitirReverse) {
    estado.mapaDireccion.setCenter(posicion);
  }
}

async function actualizarUbicacionDesdeMapa(latLng) {
  estado.direccionFuenteCorreccion = "ASESOR";
  const posicion = normalizarLatLng(latLng);
  if (!posicion) return;
  $("#latitudDireccionModal").value = posicion.lat;
  $("#longitudDireccionModal").value = posicion.lng;

  if (estado.marcadorDireccion) {
    if (typeof estado.marcadorDireccion.setPosition === "function") {
      estado.marcadorDireccion.setPosition(posicion);
    } else {
      estado.marcadorDireccion.position = posicion;
    }
  }
  if (estado.mapaDireccion) {
    estado.mapaDireccion.panTo(posicion);
  }

  if (!window.google?.maps?.Geocoder) return;
  estado.geocoderDireccion =
    estado.geocoderDireccion || new google.maps.Geocoder();

  try {
    const { results } = await estado.geocoderDireccion.geocode({
      location: posicion,
    });
    const resultado = results?.[0];
    if (resultado) aplicarResultadoGoogle(resultado, true);
  } catch (_) {
    // Aunque falle el reverse geocode, mantenemos latitud/longitud elegidas por el usuario.
  }
}

function direccionParaValidacionMasiva(cuenta) {
  const direccionBase =
    cuenta.direccion_corregida ||
    cuenta.direccion_search ||
    cuenta.direccion_original ||
    "";
  const distritoBase =
    cuenta.distrito_corregido ||
    cuenta.distrito_original ||
    cuenta.distrito ||
    "";
  const ubigeoBase =
    cuenta.ubigeo_corregido || cuenta.ubigeo_original || cuenta.ubigeo || "";
  return partesUnicas([direccionBase, distritoBase, ubigeoBase, "Perú"]).join(
    ", ",
  );
}

function cuentasPendientesCoordenadas() {
  const fuente = cuentasOrdenadasParaMapa();
  return fuente.filter((cuenta) => !cuentaTieneCoordenadas(cuenta));
}

function esperar(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function geocodificarCuentaParaValidacion(cuenta) {
  estado.geocoderDireccion =
    estado.geocoderDireccion || new google.maps.Geocoder();
  const direccion = direccionParaValidacionMasiva(cuenta);
  if (!direccion.trim()) return null;

  const { results } = await estado.geocoderDireccion.geocode({
    address: direccion,
    region: "PE",
  });
  return results?.[0] || null;
}

async function validarDireccionesMasivamente() {
  const pendientes = cuentasPendientesCoordenadas();
  if (pendientes.length === 0) {
    mostrarAviso(
      "success",
      "Direcciones listas",
      "Todas las cuentas seleccionadas ya tienen ubicación.",
    );
    return;
  }

  if (!window.google?.maps?.Geocoder) {
    mostrarAviso(
      "warning",
      "Mapa no disponible",
      "Espera unos segundos y vuelve a intentar.",
    );
    return;
  }

  const botones = ["#btnValidarMasivoRuta", "#btnValidarMasivoMapa"]
    .map((selector) => $(selector))
    .filter(Boolean);
  botones.forEach((btn) => {
    btn.disabled = true;
    btn.dataset.textoOriginal = btn.textContent;
    btn.textContent = "Validando direcciones...";
  });

  let validadas = 0;
  let noValidadas = 0;

  try {
    for (const cuenta of pendientes) {
      try {
        const resultado = await geocodificarCuentaParaValidacion(cuenta);
        if (!resultado) {
          noValidadas++;
          continue;
        }

        const lat = resultado.geometry.location.lat();
        const lng = resultado.geometry.location.lng();
        const direccionGoogle = resultado.formatted_address || "";
        const distritoGoogle = extraerDistritoGoogle(resultado);
        const ubigeoGoogle = extraerUbigeoGoogle(resultado);

        const data = await apiPost("guardar_direccion", {
          id_asignacion: cuenta.id_asignacion,
          direccion_original: cuenta.direccion_original || "",
          direccion_search: cuenta.direccion_search || "",
          direccion_corregida: cuenta.direccion_corregida || direccionGoogle,
          distrito_original: cuenta.distrito_original || cuenta.distrito || "",
          distrito_corregido: cuenta.distrito_corregido || distritoGoogle,
          ubigeo_original: cuenta.ubigeo_original || cuenta.ubigeo || "",
          ubigeo_corregido: cuenta.ubigeo_corregido || ubigeoGoogle,
          latitud: lat,
          longitud: lng,
          fuente_correccion: cuenta.direccion_corregida
            ? "ASESOR"
            : cuenta.direccion_search
              ? "SEARCH"
              : "SISTEMA",
        });

        aplicarDireccionGuardadaEnEstado(data.direccion);
        validadas++;
        await esperar(150);
      } catch (_) {
        noValidadas++;
      }
    }

    render();
    if (modalMapaAbierto()) renderMapaCompletoRuta();
    await cargarCuentas(true);

    if (validadas > 0 && noValidadas > 0) {
      mostrarAviso(
        "info",
        "Validación terminada",
        `${validadas} dirección(es) validadas. ${noValidadas} quedaron pendientes.`,
      );
    } else if (validadas > 0) {
      mostrarAviso(
        "success",
        "Direcciones validadas",
        `${validadas} dirección(es) fueron ubicadas correctamente.`,
      );
    } else {
      mostrarAviso(
        "warning",
        "Sin resultados",
        "No se pudieron ubicar las direcciones seleccionadas.",
      );
    }
  } finally {
    botones.forEach((btn) => {
      btn.disabled = false;
      btn.textContent = btn.dataset.textoOriginal || "Validar direcciones";
    });
  }
}

async function geocodificarDireccionModal() {
  const fuenteDetectada = determinarFuenteCorreccionDireccion();
  const direccion = obtenerDireccionParaGoogleMaps();

  if (!direccion.trim()) {
    mostrarAviso(
      "warning",
      "Falta una dirección",
      "Ingresa una dirección o usa una dirección disponible para validar la ubicación.",
    );
    return;
  }

  if (!window.google?.maps?.Geocoder) {
    mostrarAviso(
      "warning",
      "Mapa no disponible",
      "Espera unos segundos y vuelve a intentar.",
    );
    return;
  }

  estado.geocoderDireccion =
    estado.geocoderDireccion || new google.maps.Geocoder();
  $("#btnGeocodificarDireccion").disabled = true;
  $("#btnGeocodificarDireccion").textContent = "Validando...";

  try {
    const { results } = await estado.geocoderDireccion.geocode({
      address: direccion,
      region: "PE",
    });
    const resultado = results?.[0];
    if (!resultado)
      throw new Error(
        "Google Maps no encontró coordenadas para esa dirección.",
      );

    estado.direccionFuenteCorreccion = fuenteDetectada;
    aplicarResultadoGoogle(resultado, false);
    mostrarMapaDireccion(
      resultado.geometry.location.lat(),
      resultado.geometry.location.lng(),
    );
  } catch (error) {
    mostrarAviso(
      "error",
      "No se pudo validar",
      error.message || "No se pudo validar la dirección. Intenta nuevamente.",
    );
  } finally {
    $("#btnGeocodificarDireccion").disabled = false;
    $("#btnGeocodificarDireccion").textContent = "Validar con Google Maps";
  }
}

function modalMapaAbierto() {
  const modal = $("#modalMapaRuta");
  return modal && !modal.classList.contains("hidden");
}

function colorEstadoRuta(codigo) {
  // Marcadores del mapa solicitados en rojo para que el gestor los identifique rápido.
  return "#ef4444";
}

function claseEstadoMapa(codigo) {
  // En el mapa y la lista de recorrido, la numeración principal se mantiene en rojo.
  return "bg-red-500";
}

function cuentasOrdenadasParaMapa() {
  return cuentasSeleccionadasOrdenadasParaVista();
}

function direccionPrincipalCuenta(cuenta) {
  return (
    cuenta.direccion_corregida ||
    cuenta.direccion_search ||
    cuenta.direccion_sugerida ||
    cuenta.direccion_original ||
    "Sin dirección registrada"
  );
}

function cuentaTieneCoordenadas(cuenta) {
  const lat = Number(cuenta.latitud || 0);
  const lng = Number(cuenta.longitud || 0);
  return Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0;
}

function renderListaMapaRuta() {
  const cuentas = cuentasOrdenadasParaMapa();
  const lista = $("#listaMapaRuta");
  if (!lista) return;

  $("#badgeMapaParadas").textContent = `${cuentas.length} paradas`;
  $("#resumenMapaRuta").textContent =
    `Ruta semanal: ${cuentas.length} visita(s). Arrastra para cambiar el orden.`;

  if (cuentas.length === 0) {
    lista.innerHTML = `<div class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-xl p-4 text-center">No hay visitas seleccionadas para mostrar en el mapa.</div>`;
    return;
  }

  lista.innerHTML = cuentas
    .map((cuenta, index) => {
      const conCoords = cuentaTieneCoordenadas(cuenta);
      return `
        <div draggable="true" data-id-asignacion="${cuenta.id_asignacion}" class="mapaRutaItem cursor-move rounded-2xl border ${conCoords ? "border-gray-100" : "border-orange-100 bg-orange-50/30"} bg-white p-3 shadow-sm hover:border-blue-200">
          <div class="flex items-start gap-3">
            <span class="w-6 h-6 rounded-full ${claseEstadoMapa(cuenta.estado_visita_codigo)} text-white text-xs flex items-center justify-center font-bold shrink-0">${index + 1}</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <strong class="text-xs leading-4 truncate"><i data-lucide="grip-vertical" class="inline w-3 h-3 text-gray-400 mr-1"></i>${escaparHTML(cuenta.cliente)}</strong>
                <span class="text-xs font-bold text-gray-900 whitespace-nowrap">${formatoMoneda(cuenta.importe)}</span>
              </div>
              <p class="mt-1 text-[10px] text-gray-400 truncate">${escaparHTML(cuenta.direccion_original)}</p>
              <p class="mt-1 text-[10px] text-blue-600 truncate">${escaparHTML(cuenta.direccion_corregida || cuenta.direccion_search || cuenta.direccion_sugerida || "Sin dirección sugerida")}</p>
              <div class="mt-2 flex items-center justify-between gap-2">
                ${badgeEstado(cuenta.estado_visita_codigo, cuenta.estado_visita)}
                <span class="text-[10px] ${conCoords ? "text-gray-400" : "text-orange-500"}">${conCoords ? escaparHTML(cuenta.distrito) : "Pendiente"}</span>
              </div>
            </div>
          </div>
        </div>`;
    })
    .join("");

  configurarDragListaMapaRuta();
  lucide.createIcons();
}

function configurarDragListaMapaRuta() {
  document.querySelectorAll(".mapaRutaItem").forEach((item) => {
    item.addEventListener("dragstart", (event) => {
      estado.dragMapaRutaId = Number(item.dataset.idAsignacion);
      item.classList.add("opacity-50");
      event.dataTransfer.effectAllowed = "move";
    });

    item.addEventListener("dragover", (event) => {
      event.preventDefault();
      item.classList.add("bg-blue-50");
    });

    item.addEventListener("dragleave", () =>
      item.classList.remove("bg-blue-50"),
    );

    item.addEventListener("drop", (event) => {
      event.preventDefault();
      item.classList.remove("bg-blue-50");
      const destino = Number(item.dataset.idAsignacion);
      if (!estado.dragMapaRutaId || estado.dragMapaRutaId === destino) return;
      estado.seleccionadas = moverItemArray(
        estado.seleccionadas,
        estado.dragMapaRutaId,
        destino,
      );
      estado.dragMapaRutaId = null;
      renderVistaRuta();
      renderMapaCompletoRuta();
    });

    item.addEventListener("dragend", () => {
      item.classList.remove("opacity-50", "bg-blue-50");
      estado.dragMapaRutaId = null;
    });
  });
}

function limpiarMapaRuta() {
  if (estado.lineaRuta) {
    estado.lineaRuta.setMap(null);
    estado.lineaRuta = null;
  }
  estado.directionsRenderersRuta.forEach((renderer) => renderer.setMap(null));
  estado.directionsRenderersRuta = [];
  estado.marcadoresRuta.forEach((m) => {
    if (m?.setMap) m.setMap(null);
    else if (m) m.map = null;
  });
  estado.marcadoresRuta = [];
}

function contenidoInfoRuta(cuenta, index) {
  const sugerida =
    cuenta.direccion_corregida ||
    cuenta.direccion_search ||
    cuenta.direccion_sugerida ||
    "Sin dirección sugerida";
  return `
    <div style="min-width:260px;max-width:360px;font-family:Inter,Arial,sans-serif">
      <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:8px">
        <span style="width:22px;height:22px;border-radius:999px;background:${colorEstadoRuta(cuenta.estado_visita_codigo)};color:white;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">${index + 1}</span>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escaparHTML(cuenta.cliente)}</div>
          <div style="font-size:11px;color:#2563eb;font-weight:700;margin-top:2px">${formatoMoneda(cuenta.importe)}</div>
        </div>
      </div>
      <div style="border-top:1px solid #eef2f7;padding-top:8px;font-size:11px;color:#6b7280;line-height:1.45">
        <div><strong style="font-size:10px;color:#374151">DIR. ORIGINAL</strong> ${escaparHTML(cuenta.direccion_original || "Sin dirección")}</div>
        <div style="margin-top:6px"><strong style="font-size:10px;color:#2563eb">SUGERIDA</strong> ${escaparHTML(sugerida)}</div>
        <div style="margin-top:8px"><strong>Distrito:</strong> ${escaparHTML(cuenta.distrito || "Sin distrito")}</div>
        <div style="margin-top:4px"><strong>Estado actual:</strong> ${escaparHTML(cuenta.estado_visita || "Pendiente")}</div>
        <div style="margin-top:4px"><strong>Visitas semana:</strong> ${Number(cuenta.cantidad_visitas_dia || 0)}</div>
      </div>
    </div>`;
}

function crearMarcadorRuta(cuenta, index, posicion) {
  const color = colorEstadoRuta(cuenta.estado_visita_codigo);
  const marker = new google.maps.Marker({
    position: posicion,
    map: estado.mapaRuta,
    label: {
      text: String(index + 1),
      color: "#ffffff",
      fontSize: "12px",
      fontWeight: "700",
    },
    title: cuenta.cliente,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 12,
      fillColor: color,
      fillOpacity: 1,
      strokeColor: "#ffffff",
      strokeWeight: 2,
    },
  });

  marker.addListener("click", () => {
    estado.infoWindowRuta =
      estado.infoWindowRuta || new google.maps.InfoWindow();
    estado.infoWindowRuta.setContent(contenidoInfoRuta(cuenta, index));
    estado.infoWindowRuta.open(estado.mapaRuta, marker);
  });

  return marker;
}

function dividirRutaEnTramos(path, maxPuntosPorTramo = 25) {
  if (!Array.isArray(path) || path.length < 2) return [];
  const tramos = [];
  let inicio = 0;
  while (inicio < path.length - 1) {
    const fin = Math.min(inicio + maxPuntosPorTramo - 1, path.length - 1);
    const tramo = path.slice(inicio, fin + 1);
    if (tramo.length >= 2) tramos.push(tramo);
    inicio = fin;
  }
  return tramos;
}

function trazarRecorridoPorCalles(path) {
  if (!window.google?.maps?.DirectionsService || path.length < 2) return false;

  estado.directionsServiceRuta =
    estado.directionsServiceRuta || new google.maps.DirectionsService();
  const tramos = dividirRutaEnTramos(path, 25);
  if (!tramos.length) return false;

  tramos.forEach((tramo) => {
    const renderer = new google.maps.DirectionsRenderer({
      map: estado.mapaRuta,
      suppressMarkers: true,
      preserveViewport: true,
      polylineOptions: {
        strokeColor: "#ef4444",
        strokeOpacity: 0.9,
        strokeWeight: 4,
      },
    });
    estado.directionsRenderersRuta.push(renderer);

    estado.directionsServiceRuta.route(
      {
        origin: tramo[0],
        destination: tramo[tramo.length - 1],
        waypoints: tramo
          .slice(1, -1)
          .map((punto) => ({ location: punto, stopover: true })),
        travelMode: google.maps.TravelMode.DRIVING,
        optimizeWaypoints: false,
      },
      (result, status) => {
        if (status === "OK" && result) {
          renderer.setDirections(result);
        }
      },
    );
  });

  return true;
}

function renderMapaCompletoRuta() {
  if (!modalMapaAbierto() || !window.google?.maps?.Map) return;
  const contenedor = $("#mapaRutaCompleto");
  if (!contenedor) return;

  const cuentas = cuentasOrdenadasParaMapa();
  const cuentasConCoords = cuentas.filter(cuentaTieneCoordenadas);
  const cuentasSinCoords = cuentas.length - cuentasConCoords.length;

  if (!estado.mapaRuta) {
    estado.mapaRuta = new google.maps.Map(contenedor, {
      center: { lat: -12.0464, lng: -77.0428 },
      zoom: 12,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
    });
  }

  limpiarMapaRuta();
  renderListaMapaRuta();

  const mensaje = $("#mensajeMapaRuta");
  if (cuentasSinCoords > 0) {
    mensaje.classList.remove("hidden");
    mensaje.textContent = `${cuentasSinCoords} visita(s) aún no tienen ubicación en el mapa.`;
  } else {
    mensaje.classList.add("hidden");
    mensaje.textContent = "";
  }

  if (cuentasConCoords.length === 0) {
    estado.mapaRuta.setCenter({ lat: -12.0464, lng: -77.0428 });
    estado.mapaRuta.setZoom(11);
    return;
  }

  const bounds = new google.maps.LatLngBounds();
  const path = [];
  cuentas.forEach((cuenta, index) => {
    if (!cuentaTieneCoordenadas(cuenta)) return;
    const posicion = {
      lat: Number(cuenta.latitud),
      lng: Number(cuenta.longitud),
    };
    path.push(posicion);
    bounds.extend(posicion);
    estado.marcadoresRuta.push(crearMarcadorRuta(cuenta, index, posicion));
  });

  if (path.length >= 2) {
    const recorridoTrazado = trazarRecorridoPorCalles(path);
    if (!recorridoTrazado && mensaje) {
      mensaje.classList.remove("hidden");
      mensaje.textContent =
        "No se pudo trazar el recorrido por calles en este momento.";
    }
  }

  if (path.length === 1) {
    estado.mapaRuta.setCenter(path[0]);
    estado.mapaRuta.setZoom(16);
  } else {
    estado.mapaRuta.fitBounds(bounds, 70);
  }
}

function abrirModalMapaCompleto() {
  if (estado.seleccionadas.length === 0) return;
  const modal = $("#modalMapaRuta");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  setTimeout(() => {
    renderListaMapaRuta();
    renderMapaCompletoRuta();
  }, 80);
  lucide.createIcons();
}

function cerrarModalMapaCompleto() {
  const modal = $("#modalMapaRuta");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

async function guardarRutaDesdeMapa() {
  if (estado.seleccionadas.length === 0) return;
  try {
    estado.guardando = true;
    const btn = $("#btnGuardarMapaRuta");
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin"></i> Guardando...`;
    lucide.createIcons();

    const idsRuta = cuentasOrdenadasParaMapa().map(
      (cuenta) => cuenta.id_asignacion,
    );
    const data = await apiPost("guardar_o_actualizar_ruta", {
      idsAsignacion: idsRuta,
      criterioRuta: "PERSONALIZADO",
      fechaRuta: $("#filtroFechaRuta").value || hoyISO(),
    });

    mostrarAviso(
      "success",
      "Hoja de ruta guardada",
      "La ruta se guardó correctamente.",
    );
    cerrarModalMapaCompleto();
    await cargarCuentas(true);
  } catch (error) {
    mostrarAviso("error", "No se pudo guardar", error.message);
  } finally {
    estado.guardando = false;
    const btn = $("#btnGuardarMapaRuta");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<i data-lucide="save" class="w-3.5 h-3.5"></i> Guardar ruta`;
    }
    render();
  }
}

function configurarEventos() {
  [
    "#filtroFechaRuta",
    "#filtroDistrito",
    "#filtroProducto",
    "#filtroEstadoVisita",
    "#filtroRangoImporte",
    "#criterioRuta",
  ].forEach((selector) => {
    $(selector).addEventListener("change", async () => {
      estado.pagina = 1;
      await cargarCuentas();
    });
  });

  $("#cuentasPorPagina").addEventListener("change", async () => {
    estado.porPagina = Number($("#cuentasPorPagina").value || 10);
    estado.pagina = 1;
    await cargarCuentas();
  });

  $("#checkTodos").addEventListener("change", async () => {
    const check = $("#checkTodos");
    if (check.checked) {
      try {
        estado.seleccionGlobalCargando = true;
        render();
        estado.seleccionadas = await obtenerIdsFiltrados();
      } catch (error) {
        check.checked = false;
        mostrarAviso("error", "No se pudo seleccionar", error.message);
      } finally {
        estado.seleccionGlobalCargando = false;
        render();
      }
    } else {
      estado.seleccionadas = [];
      render();
    }
  });

  $("#btnLimpiar").addEventListener("click", () => {
    estado.seleccionadas = [];
    render();
  });

  $("#btnReordenar").addEventListener("click", () => {
    estado.seleccionadas.reverse();
    render();
  });

  $("#btnCrearRuta").addEventListener("click", crearRuta);
  $("#btnMapa").addEventListener("click", abrirModalMapaCompleto);
  $("#btnValidarMasivoRuta")?.addEventListener(
    "click",
    validarDireccionesMasivamente,
  );
  $("#btnValidarMasivoMapa")?.addEventListener(
    "click",
    validarDireccionesMasivamente,
  );
  $("#btnCerrarModalMapa").addEventListener("click", cerrarModalMapaCompleto);
  $("#btnCancelarMapaRuta").addEventListener("click", cerrarModalMapaCompleto);
  $("#btnGuardarMapaRuta").addEventListener("click", guardarRutaDesdeMapa);
  $("#btnAgregarPendientes")?.addEventListener("click", agregarPendientesARuta);
  $("#direccionCorregidaModal").addEventListener("input", () => {
    if (valorInput("#direccionCorregidaModal")) estado.direccionFuenteCorreccion = "ASESOR";
  });
  $("#direccionSearchModal").addEventListener("input", () => {
    if (valorInput("#direccionSearchModal") && !valorInput("#direccionCorregidaModal")) {
      estado.direccionFuenteCorreccion = "SEARCH";
    }
  });
  $("#btnCerrarModalDireccion").addEventListener("click", cerrarModalDireccion);
  $("#btnCancelarDireccion").addEventListener("click", cerrarModalDireccion);
  $("#btnGuardarDireccion").addEventListener("click", guardarDireccion);
  $("#btnGeocodificarDireccion").addEventListener(
    "click",
    geocodificarDireccionModal,
  );
}

document.addEventListener("DOMContentLoaded", async () => {
  $("#filtroFechaRuta").value = hoyISO();
  configurarEventos();
  await cargarInicial();
});
