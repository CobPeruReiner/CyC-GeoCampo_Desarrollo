<?php
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
$_SESSION['id_ruta_asesor'] = $idUsuario;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ruta semanal del asesor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
            color: #fff;
            border-radius: 1rem;
            box-shadow: 0 12px 28px rgba(139, 0, 0, .18);
        }

        .corporate-back {
            background: rgba(255, 255, 255, .10);
            border-color: rgba(255, 255, 255, .28);
            color: #fff;
        }

        .corporate-back:hover {
            background: rgba(255, 255, 255, .18);
        }

        .btn-corp {
            background: var(--corp-red-hover);
            color: #fff;
        }

        .btn-corp:hover {
            background: var(--corp-dark-red);
        }

        .modal-backdrop {
            background: rgba(15, 23, 42, .55);
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
    </style>
</head>

<body class="bg-[#F4F6F8] text-gray-900">
    <main class="max-w-[1700px] mx-auto px-3 py-4 md:px-6 md:py-8">
        <input type="hidden" id="idUsuario" value="<?= htmlspecialchars((string)$idUsuario, ENT_QUOTES, 'UTF-8') ?>">

        <header class="corporate-header px-4 py-4 md:px-6 md:py-5 mb-5 md:mb-6">
            <div class="w-full flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0 flex flex-col gap-3 md:flex-row md:items-center md:gap-4">
                    <div class="flex items-center justify-between gap-3 md:block">
                        <button type="button" onclick="history.back()" class="corporate-back inline-flex items-center gap-2 border rounded-lg px-3 py-2 md:px-4 text-sm transition shrink-0">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Volver</span>
                        </button>
                        <div class="inline-flex md:hidden items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-full bg-white/10 border border-white/20 shrink-0">
                            <i data-lucide="route" class="w-3.5 h-3.5"></i>
                            <span>Ruta semanal</span>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="hidden md:inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-white/10 border border-white/20 mb-1">
                            <i data-lucide="route" class="w-3.5 h-3.5"></i> Ruta semanal
                        </div>
                        <h1 class="text-lg md:text-xl font-bold tracking-tight leading-snug break-words">Cuentas en ruta del asesor</h1>
                    </div>
                </div>

                <div class="text-sm text-white/90 leading-snug md:text-right">
                    <span class="block md:inline">Semana:</span>
                    <strong id="lblSemana" class="block md:inline">Cargando...</strong>
                </div>
            </div>
        </header>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-5">
            <div class="p-4 grid grid-cols-1 md:grid-cols-[220px_1fr_160px] gap-3 items-end">
                <div>
                    <label for="fechaReferencia" class="block text-xs text-gray-500 mb-1">Fecha de referencia</label>
                    <input id="fechaReferencia" type="date" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-sm outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
                </div>
                <div>
                    <label for="selectCartera" class="block text-xs text-gray-500 mb-1">Cartera</label>
                    <select id="selectCartera" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-sm outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50"></select>
                </div>
                <button id="btnActualizar" class="btn-corp h-10 rounded-xl text-sm font-semibold inline-flex items-center justify-center gap-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Actualizar
                </button>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 hidden">
            <article class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">Cuentas en ruta</p>
                <div class="flex items-center justify-between mt-2">
                    <strong id="kpiTotal" class="text-2xl">0</strong>
                    <span class="w-10 h-10 rounded-xl bg-red-50 text-[#A7191F] flex items-center justify-center"><i data-lucide="list-checks" class="w-5 h-5"></i></span>
                </div>
            </article>
            <article class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">Visitadas</p>
                <div class="flex items-center justify-between mt-2">
                    <strong id="kpiVisitadas" class="text-2xl">0</strong>
                    <span class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center"><i data-lucide="check-circle-2" class="w-5 h-5"></i></span>
                </div>
            </article>
            <article class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">Pendientes</p>
                <div class="flex items-center justify-between mt-2">
                    <strong id="kpiPendientes" class="text-2xl">0</strong>
                    <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5"></i></span>
                </div>
            </article>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-4 border-b border-gray-100 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-bold text-sm">Listado de cuentas programadas</h2>
                    <p id="lblPeriodoRuta" class="text-xs text-gray-400 mt-0.5"></p>
                </div>
                <div id="estadoCarga" class="text-xs text-gray-400">Cargando...</div>
            </div>

            <div class="px-4 py-3 border-b border-gray-100 grid grid-cols-1 md:grid-cols-[1fr_150px] gap-3">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input id="txtBuscarCuentas" type="search" placeholder="Buscar por DNI, cliente, cuenta o identificador" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 pl-9 pr-3 text-sm outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
                </div>
                <select id="selectPageSize" class="w-full h-10 rounded-xl bg-gray-50 border border-gray-200 px-3 text-sm outline-none focus:bg-white focus:border-[#FF161A] focus:ring-4 focus:ring-red-50">
                    <option value="10">10 por página</option>
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
            </div>

            <div class="hidden md:block overflow-auto scroll-suave">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left w-[140px]">DNI</th>
                            <th class="px-4 py-3 text-left min-w-[280px]">Cliente</th>
                            <th class="px-4 py-3 text-left w-[150px]">Estado</th>
                            <th class="px-4 py-3 text-center w-[260px]">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCuentas" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            <div id="cardsCuentas" class="md:hidden p-3 space-y-3"></div>

            <div id="emptyState" class="hidden p-10 text-center text-gray-500">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 mx-auto flex items-center justify-center mb-3">
                    <i data-lucide="folder-open" class="w-7 h-7 text-gray-400"></i>
                </div>
                <p class="font-semibold text-gray-700">No hay cuentas en ruta para este periodo.</p>
                <p class="text-sm">Primero agrega cuentas desde Mis Cuentas a la ruta correspondiente.</p>
            </div>

            <div id="paginacionCuentas" class="px-4 py-3 border-t border-gray-100 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"></div>
        </section>
    </main>

    <div id="modal" class="hidden fixed inset-0 z-50">
        <div class="modal-backdrop absolute inset-0" onclick="cerrarModal()"></div>
        <div class="absolute inset-x-4 top-8 md:inset-x-auto md:left-1/2 md:-translate-x-1/2 md:w-[920px] bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[88vh] flex flex-col">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 id="modalTitulo" class="font-bold text-lg">Detalle</h3>
                    <p id="modalSubtitulo" class="text-xs text-gray-500"></p>
                </div>
                <button onclick="cerrarModal()" class="w-9 h-9 rounded-xl border border-gray-200 hover:bg-gray-50 flex items-center justify-center">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div id="modalContenido" class="p-5 overflow-auto scroll-suave"></div>
        </div>
    </div>

    <script>
        const API = 'ruta_asesor_api.php';
        const state = {
            idUsuario: Number(document.getElementById('idUsuario').value),
            fecha: new Date().toISOString().slice(0, 10),
            idTable: 0,
            carteras: [],
            cuentas: [],
            page: 1,
            perPage: 25,
            q: '',
            paginacion: null
        };

        const els = {
            fecha: document.getElementById('fechaReferencia'),
            selectCartera: document.getElementById('selectCartera'),
            tbody: document.getElementById('tbodyCuentas'),
            cards: document.getElementById('cardsCuentas'),
            empty: document.getElementById('emptyState'),
            buscar: document.getElementById('txtBuscarCuentas'),
            pageSize: document.getElementById('selectPageSize'),
            paginacion: document.getElementById('paginacionCuentas'),
            lblPeriodoRuta: document.getElementById('lblPeriodoRuta'),
            lblSemana: document.getElementById('lblSemana'),
            estadoCarga: document.getElementById('estadoCarga'),
            kpiTotal: document.getElementById('kpiTotal'),
            kpiVisitadas: document.getElementById('kpiVisitadas'),
            kpiPendientes: document.getElementById('kpiPendientes'),
            modal: document.getElementById('modal'),
            modalTitulo: document.getElementById('modalTitulo'),
            modalSubtitulo: document.getElementById('modalSubtitulo'),
            modalContenido: document.getElementById('modalContenido')
        };

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            } [c]));
        }

        function apiUrl(action, extra = {}) {
            const params = new URLSearchParams({
                action,
                id_usuario: state.idUsuario,
                fecha: els.fecha.value || state.fecha,
                id_table: state.idTable || ''
            });
            Object.entries(extra).forEach(([k, v]) => params.set(k, v ?? ''));
            return `${API}?${params.toString()}`;
        }

        function apiUrlCuentas() {
            return apiUrl('cuentas_ruta', {
                q: state.q,
                page: state.page,
                per_page: state.perPage
            });
        }

        async function fetchJson(url) {
            const res = await fetch(url, {
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message || 'No se pudo completar la operación.');
            return data;
        }

        function abrirModal(titulo, subtitulo, contenido) {
            els.modalTitulo.textContent = titulo;
            els.modalSubtitulo.textContent = subtitulo || '';
            els.modalContenido.innerHTML = contenido;
            els.modal.classList.remove('hidden');
            lucide.createIcons();
        }

        function cerrarModal() {
            els.modal.classList.add('hidden');
            els.modalContenido.innerHTML = '';
        }

        window.cerrarModal = cerrarModal;

        function btnIcon(icon, title, cls, onClick) {
            return `<button title="${title}" onclick="${onClick}" class="w-9 h-9 rounded-xl border border-gray-200 ${cls} inline-flex items-center justify-center transition"><i data-lucide="${icon}" class="w-4 h-4"></i></button>`;
        }

        function renderAccionesCuenta(idx) {
            return `
              ${btnIcon('info', 'Información', 'hover:bg-red-50 hover:text-[#A7191F]', `verInfo(${idx})`)}
              ${btnIcon('history', 'Historial de gestión', 'hover:bg-gray-50 hover:text-gray-900', `verHistorial(${idx})`)}
              ${btnIcon('eye', 'Cantidad de visitas', 'hover:bg-amber-50 hover:text-amber-700', `verVisitas(${idx})`)}
              ${btnIcon('shield-plus', 'Asignar gestión', 'bg-green-600 text-white border-green-600 hover:bg-green-700', `abrirGestion(${idx})`)}
            `;
        }

        function renderPaginacionCuentas() {
            const p = state.paginacion || {
                page: 1,
                total_pages: 1,
                total: 0,
                from: 0,
                to: 0
            };
            const disabledPrev = p.page <= 1;
            const disabledNext = p.page >= p.total_pages;
            els.paginacion.innerHTML = `
              <div class="text-xs text-gray-500">
                Mostrando <strong>${escapeHtml(p.from || 0)}</strong> - <strong>${escapeHtml(p.to || 0)}</strong> de <strong>${escapeHtml(p.total || 0)}</strong> registro(s)
              </div>
              <div class="flex items-center justify-between md:justify-end gap-2">
                <button type="button" onclick="cambiarPaginaCuentas(${Number(p.page || 1) - 1})" ${disabledPrev ? 'disabled' : ''}
                  class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold ${disabledPrev ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}">
                  Anterior
                </button>
                <span class="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-bold text-gray-700">
                  Página ${escapeHtml(p.page || 1)} / ${escapeHtml(p.total_pages || 1)}
                </span>
                <button type="button" onclick="cambiarPaginaCuentas(${Number(p.page || 1) + 1})" ${disabledNext ? 'disabled' : ''}
                  class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold ${disabledNext ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}">
                  Siguiente
                </button>
              </div>
            `;
        }

        function renderCuentas(data) {
            state.cuentas = data.cuentas || [];
            state.paginacion = data.paginacion || null;
            els.lblSemana.textContent = data.semana?.etiqueta || '';
            els.lblPeriodoRuta.textContent = data.periodo?.nombre ? `Periodo: ${data.periodo.nombre}` : '';
            els.kpiTotal.textContent = data.resumen?.total ?? 0;
            els.kpiVisitadas.textContent = data.resumen?.visitadas ?? 0;
            els.kpiPendientes.textContent = data.resumen?.pendientes ?? 0;

            els.tbody.innerHTML = '';
            els.cards.innerHTML = '';
            els.empty.classList.toggle('hidden', state.cuentas.length > 0);
            renderPaginacionCuentas();
            if (!state.cuentas.length) {
                lucide.createIcons();
                return;
            }

            state.cuentas.forEach((c, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
          <td class="px-4 py-3 align-top">
            <div class="font-semibold text-gray-800">${escapeHtml(c.documento)}</div>
            <div class="text-[11px] text-gray-400">#${escapeHtml(c.identificador)}</div>
          </td>
          <td class="px-4 py-3 align-top">
            <div class="font-semibold text-gray-900">${escapeHtml(c.cliente)}</div>
            <div class="text-xs text-gray-500">Orden ${escapeHtml(c.orden_visita)} · ${escapeHtml(c.fecha_agendada || 'Sin hora')}</div>
          </td>
          <td class="px-4 py-3 align-top">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold ${escapeHtml(c.estado.clase)}">${escapeHtml(c.estado.descripcion)}</span>
          </td>
          <td class="px-4 py-3 text-center align-top">
            <div class="inline-flex items-center gap-2">${renderAccionesCuenta(idx)}</div>
          </td>
        `;
                els.tbody.appendChild(tr);

                const card = document.createElement('article');
                card.className = 'rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden';
                card.innerHTML = `
                  <div class="p-3 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="text-[11px] uppercase font-bold text-gray-400">DNI</div>
                        <div class="text-sm font-bold text-gray-900 break-words">${escapeHtml(c.documento)}</div>
                        <div class="text-[11px] text-gray-400 break-words">Identificador: ${escapeHtml(c.identificador)}</div>
                      </div>
                      <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-bold ${escapeHtml(c.estado.clase)}">${escapeHtml(c.estado.descripcion)}</span>
                    </div>

                    <div>
                      <div class="text-[11px] uppercase font-bold text-gray-400">Cliente</div>
                      <div class="text-sm font-semibold text-gray-900 leading-snug break-words">${escapeHtml(c.cliente)}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                      <div class="rounded-xl bg-gray-50 border border-gray-100 p-2">
                        <div class="font-bold text-gray-400 uppercase text-[10px]">Orden</div>
                        <div class="font-semibold text-gray-700">${escapeHtml(c.orden_visita)}</div>
                      </div>
                      <div class="rounded-xl bg-gray-50 border border-gray-100 p-2">
                        <div class="font-bold text-gray-400 uppercase text-[10px]">Agenda</div>
                        <div class="font-semibold text-gray-700 break-words">${escapeHtml(c.fecha_agendada || 'Sin hora')}</div>
                      </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1">${renderAccionesCuenta(idx)}</div>
                  </div>
                `;
                els.cards.appendChild(card);
            });
            lucide.createIcons();
        }

        async function cargarInicial() {
            els.fecha.value = state.fecha;
            const data = await fetchJson(apiUrl('inicial'));
            state.carteras = data.carteras || [];
            state.idTable = Number(data.cartera_seleccionada?.id_table || state.carteras[0]?.id_table || 0);
            els.selectCartera.innerHTML = state.carteras.map(c => `<option value="${c.id_table}">${escapeHtml(c.nombre)}</option>`).join('');
            els.selectCartera.value = state.idTable;
            await cargarCuentas();
        }

        async function cargarCuentas() {
            els.estadoCarga.textContent = 'Cargando...';
            try {
                state.idTable = Number(els.selectCartera.value || state.idTable || 0);
                const data = await fetchJson(apiUrlCuentas());
                renderCuentas(data);
                const p = data.paginacion || {};
                els.estadoCarga.textContent = `${p.total ?? state.cuentas.length} registro(s)`;
            } catch (err) {
                els.estadoCarga.textContent = 'Error';
                abrirModal('Aviso', '', `<div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">${escapeHtml(err.message)}</div>`);
            }
        }

        window.verInfo = async function(index) {
            const c = state.cuentas[index];
            abrirModal('Información del cliente', c.cliente, `<div class="text-sm text-gray-500">Cargando información...</div>`);
            try {
                const data = await fetchJson(apiUrl('info_cliente', {
                    identificador: c.identificador
                }));
                const valores = data.data?.valores || [];
                const html = valores.length ? `
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            ${valores.map(v => `
              <div class="border border-gray-200 rounded-xl p-3 bg-gray-50">
                <div class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">${escapeHtml(v.header)}</div>
                <div class="mt-1 text-sm text-gray-900 break-words">${escapeHtml(v.value || '-')}</div>
              </div>
            `).join('')}
          </div>
        ` : `<div class="p-4 rounded-xl bg-gray-50 text-gray-500 text-sm">No se encontró información activa del cliente.</div>`;
                abrirModal('Información del cliente', `${c.cliente} · ${c.documento}`, html);
            } catch (err) {
                abrirModal('Información del cliente', c.cliente, `<div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">${escapeHtml(err.message)}</div>`);
            }
        }

        function claseFuenteHistorial(fuente) {
            return fuente === 'CAMPO' ?
                'bg-emerald-50 text-emerald-700 border-emerald-200' :
                'bg-indigo-50 text-indigo-700 border-indigo-200';
        }

        function renderBotoneraHistorial(fuente, resumen) {
            const label = fuente === 'CAMPO' ? 'Campo' : 'Call';
            const icon = fuente === 'CAMPO' ? 'map-pin' : 'headphones';
            const r = resumen?.[fuente] || {};
            const botones = [
                ['TODOS', 'Todos', r.total || 0],
                ['CD', 'CD', r.CD || 0],
                ['CI', 'CI', r.CI || 0],
                ['NC', 'NC', r.NC || 0]
            ];

            return `
              <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                  <div class="inline-flex items-center gap-2 font-bold text-sm text-gray-800">
                    <span class="w-8 h-8 rounded-xl ${fuente === 'CAMPO' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700'} flex items-center justify-center">
                      <i data-lucide="${icon}" class="w-4 h-4"></i>
                    </span>
                    ${label}
                  </div>
                  <span class="text-xs text-gray-400">${Number(r.total || 0)} gestión(es)</span>
                </div>
                <div class="p-3 flex flex-wrap gap-2">
                  ${botones.map(([cat, txt, cant]) => `
                    <button type="button" data-fuente="${fuente}" data-cat="${cat}" onclick="filtrarHistorial('${fuente}', '${cat}')"
                      class="hist-filter-${fuente} rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold bg-gray-50 hover:bg-white hover:border-[#FF161A] transition">
                      ${txt} <span class="ml-1 text-gray-400">${cant}</span>
                    </button>
                  `).join('')}
                </div>
              </div>
            `;
        }

        function normalizarBusquedaHistorial(value) {
            return String(value ?? '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function obtenerEstadoHistorial(fuente) {
            if (!state.historial) {
                state.historial = {
                    items: [],
                    resumen: {},
                    filtros: {}
                };
            }
            if (!state.historial.filtros[fuente]) {
                state.historial.filtros[fuente] = {
                    categoria: 'TODOS',
                    q: '',
                    page: 1,
                    perPage: 10
                };
            }
            return state.historial.filtros[fuente];
        }

        function obtenerItemsHistorial(fuente) {
            const filtro = obtenerEstadoHistorial(fuente);
            const q = normalizarBusquedaHistorial(filtro.q);
            let items = (state.historial?.items || []).filter(it => it.fuente === fuente);

            if (filtro.categoria !== 'TODOS') {
                items = items.filter(it => (it.categoria?.codigo || 'OTROS') === filtro.categoria);
            }

            if (q) {
                items = items.filter(it => {
                    const texto = [
                        it.fecha,
                        it.hora,
                        it.efecto,
                        it.idefecto,
                        it.observacion,
                        it.contacto,
                        it.idcontacto,
                        it.telefono,
                        it.pdp,
                        it.fecha_pdp,
                        it.monto_pdp,
                        it.fuente_label
                    ].map(normalizarBusquedaHistorial).join(' ');
                    return texto.includes(q);
                });
            }

            const total = items.length;
            const perPage = Number(filtro.perPage || 10);
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            if (filtro.page > totalPages) filtro.page = totalPages;
            if (filtro.page < 1) filtro.page = 1;
            const from = total > 0 ? ((filtro.page - 1) * perPage) + 1 : 0;
            const to = total > 0 ? Math.min(filtro.page * perPage, total) : 0;
            const pageItems = items.slice((filtro.page - 1) * perPage, filtro.page * perPage);

            return {
                items,
                pageItems,
                total,
                totalPages,
                from,
                to,
                filtro
            };
        }

        function renderControlesHistorial(fuente, total) {
            const filtro = obtenerEstadoHistorial(fuente);
            return `
              <div class="mb-3 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-center">
                <div class="relative">
                  <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <input id="hist-buscar-${fuente}" type="text" value="${escapeHtml(filtro.q)}"
                    oninput="buscarHistorial('${fuente}', this.value)"
                    placeholder="Buscar por fecha, efecto, contacto, observación o teléfono..."
                    class="w-full rounded-xl border border-gray-200 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF161A]/20 focus:border-[#FF161A]">
                </div>
                <div class="flex items-center justify-between md:justify-end gap-2">
                  <span class="text-xs text-gray-500 whitespace-nowrap">${Number(total || 0)} registro(s)</span>
                  <select onchange="cambiarTamanoPaginaHistorial('${fuente}', this.value)"
                    class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold bg-white focus:outline-none focus:ring-2 focus:ring-[#FF161A]/20 focus:border-[#FF161A]">
                    ${[5, 10, 25, 50, 100].map(n => `<option value="${n}" ${Number(filtro.perPage) === n ? 'selected' : ''}>${n} por página</option>`).join('')}
                  </select>
                </div>
              </div>
            `;
        }

        function renderPaginacionHistorial(fuente, info) {
            const disabledPrev = info.filtro.page <= 1;
            const disabledNext = info.filtro.page >= info.totalPages;
            return `
              <div class="mt-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div class="text-xs text-gray-500">
                  Mostrando <strong>${escapeHtml(info.from)}</strong> - <strong>${escapeHtml(info.to)}</strong> de <strong>${escapeHtml(info.total)}</strong> registro(s)
                </div>
                <div class="flex items-center justify-between md:justify-end gap-2">
                  <button type="button" onclick="cambiarPaginaHistorial('${fuente}', ${Number(info.filtro.page || 1) - 1})" ${disabledPrev ? 'disabled' : ''}
                    class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold ${disabledPrev ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}">
                    Anterior
                  </button>
                  <span class="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-bold text-gray-700">
                    Página ${escapeHtml(info.filtro.page || 1)} / ${escapeHtml(info.totalPages || 1)}
                  </span>
                  <button type="button" onclick="cambiarPaginaHistorial('${fuente}', ${Number(info.filtro.page || 1) + 1})" ${disabledNext ? 'disabled' : ''}
                    class="px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold ${disabledNext ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}">
                    Siguiente
                  </button>
                </div>
              </div>
            `;
        }

        function renderTablaHistorial(pageItems, fuente) {
            if (!pageItems.length) {
                return `<div class="p-4 rounded-xl bg-gray-50 text-gray-500 text-sm">Sin gestiones de ${fuente === 'CAMPO' ? 'campo' : 'call'} para el filtro aplicado.</div>`;
            }

            const renderExtra = (it) => `
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-xs">
                <div class="rounded-xl bg-white border border-gray-200 p-2"><strong>Contacto:</strong> ${escapeHtml(it.contacto || '-')}</div>
                <div class="rounded-xl bg-white border border-gray-200 p-2"><strong>Teléfono:</strong> ${escapeHtml(it.telefono || '-')}</div>
                <div class="rounded-xl bg-white border border-gray-200 p-2"><strong>PDP:</strong> <span class="${it.es_promesa ? 'font-bold text-emerald-700' : ''}">${escapeHtml(it.pdp || 'No')}</span></div>
                <div class="rounded-xl bg-white border border-gray-200 p-2"><strong>Fecha promesa:</strong> ${escapeHtml(it.fecha_pdp || '-')}</div>
              </div>
            `;

            const renderPdpBadge = (it) => it.es_promesa ?
                `<span class="inline-flex px-2 py-1 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 font-bold">Promesa</span>` :
                `<span class="inline-flex px-2 py-1 rounded-full border bg-gray-50 text-gray-500 border-gray-200 font-bold">No</span>`;

            return `
              <div class="hidden md:block border border-gray-200 rounded-xl overflow-hidden">
                <table class="min-w-full text-xs">
                  <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                      <th class="px-3 py-2 text-left">Fecha *</th>
                      <th class="px-3 py-2 text-left">Hora *</th>
                      <th class="px-3 py-2 text-left">Tipo</th>
                      <th class="px-3 py-2 text-left min-w-[190px]">Efecto *</th>
                      <th class="px-3 py-2 text-left min-w-[240px]">Obs *</th>
                      <th class="px-3 py-2 text-center">Ver</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    ${pageItems.map((it, i) => {
                        const rowId = `${fuente}-${it.id}-${i}`;
                        return `
                          <tr>
                            <td class="px-3 py-2 whitespace-nowrap">${escapeHtml(it.fecha)}</td>
                            <td class="px-3 py-2 whitespace-nowrap">${escapeHtml(it.hora)}</td>
                            <td class="px-3 py-2">
                              <span class="inline-flex px-2 py-1 rounded-full border font-bold ${escapeHtml(it.categoria?.clase || 'bg-gray-50 text-gray-700 border-gray-200')}">${escapeHtml(it.categoria?.codigo || '-')}</span>
                            </td>
                            <td class="px-3 py-2 ${it.es_promesa ? 'bg-emerald-50 font-bold text-emerald-800' : ''}">${escapeHtml(it.efecto || it.idefecto || '-')}</td>
                            <td class="px-3 py-2">${escapeHtml(it.observacion || '-')}</td>
                            <td class="px-3 py-2 text-center"><button class="w-8 h-8 rounded-lg border border-gray-200 hover:bg-gray-50 inline-flex items-center justify-center" onclick="toggleHistorialExtra('${rowId}')"><i data-lucide="eye" class="w-4 h-4"></i></button></td>
                          </tr>
                          <tr id="hist-extra-${rowId}" class="hidden bg-gray-50">
                            <td colspan="6" class="px-3 py-3">${renderExtra(it)}</td>
                          </tr>
                        `;
                    }).join('')}
                  </tbody>
                </table>
              </div>

              <div class="md:hidden space-y-3">
                ${pageItems.map((it, i) => {
                    const rowId = `${fuente}-m-${it.id}-${i}`;
                    return `
                      <article class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                        <div class="p-3 space-y-3">
                          <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                              <div class="text-[11px] uppercase font-bold text-gray-400">${escapeHtml(it.fecha)} · ${escapeHtml(it.hora)}</div>
                              <div class="mt-1 text-sm font-bold leading-snug ${it.es_promesa ? 'text-emerald-800' : 'text-gray-900'}">${escapeHtml(it.efecto || it.idefecto || '-')}</div>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                              <span class="inline-flex px-2 py-1 rounded-full border text-[11px] font-bold ${escapeHtml(it.categoria?.clase || 'bg-gray-50 text-gray-700 border-gray-200')}">${escapeHtml(it.categoria?.codigo || '-')}</span>
                              ${it.es_promesa ? renderPdpBadge(it) : ''}
                            </div>
                          </div>

                          <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
                            <div class="text-[11px] uppercase font-bold text-gray-400 mb-1">Obs</div>
                            <div class="text-sm text-gray-700 break-words">${escapeHtml(it.observacion || '-')}</div>
                          </div>

                          <div class="flex items-center justify-between gap-2">
                            <button type="button" onclick="toggleHistorialExtra('${rowId}')" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-700 inline-flex items-center gap-1">
                              Más
                              <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" class="w-9 h-9 rounded-xl border border-gray-200 hover:bg-gray-50 inline-flex items-center justify-center" title="Ver">
                              <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                          </div>
                        </div>
                        <div id="hist-extra-${rowId}" class="hidden border-t border-gray-100 bg-gray-50 p-3">
                          ${renderExtra(it)}
                        </div>
                      </article>
                    `;
                }).join('')}
              </div>
            `;
        }

        function renderHistorialFuente(fuente) {
            const info = obtenerItemsHistorial(fuente);
            return `
              ${renderControlesHistorial(fuente, info.total)}
              ${renderTablaHistorial(info.pageItems, fuente)}
              ${renderPaginacionHistorial(fuente, info)}
            `;
        }

        function renderizarHistorialFuente(fuente) {
            const contenedor = document.getElementById(`hist-contenido-${fuente}`);
            if (!contenedor) return;
            contenedor.innerHTML = renderHistorialFuente(fuente);
            lucide.createIcons();
        }

        window.filtrarHistorial = function(fuente, categoria) {
            const filtro = obtenerEstadoHistorial(fuente);
            filtro.categoria = categoria;
            filtro.page = 1;

            document.querySelectorAll(`.hist-filter-${fuente}`).forEach(btn => {
                const activo = btn.dataset.cat === categoria;
                btn.classList.toggle('bg-[#FF161A]', activo);
                btn.classList.toggle('text-white', activo);
                btn.classList.toggle('border-[#FF161A]', activo);
                btn.classList.toggle('bg-gray-50', !activo);
            });

            renderizarHistorialFuente(fuente);
        }

        window.buscarHistorial = function(fuente, valor) {
            const filtro = obtenerEstadoHistorial(fuente);
            filtro.q = valor;
            filtro.page = 1;
            renderizarHistorialFuente(fuente);
            const input = document.getElementById(`hist-buscar-${fuente}`);
            if (input) {
                input.focus();
                const len = input.value.length;
                input.setSelectionRange(len, len);
            }
        }

        window.cambiarPaginaHistorial = function(fuente, page) {
            const filtro = obtenerEstadoHistorial(fuente);
            filtro.page = Number(page || 1);
            renderizarHistorialFuente(fuente);
        }

        window.cambiarTamanoPaginaHistorial = function(fuente, perPage) {
            const filtro = obtenerEstadoHistorial(fuente);
            filtro.perPage = Number(perPage || 10);
            filtro.page = 1;
            renderizarHistorialFuente(fuente);
        }

        window.verHistorial = async function(index) {
            const c = state.cuentas[index];
            abrirModal('Historial de gestión', c.cliente, `<div class="text-sm text-gray-500">Cargando historial...</div>`);
            try {
                const data = await fetchJson(apiUrl('historial_gestion', {
                    identificador: c.identificador
                }));
                const items = data.data?.items || [];
                const resumen = data.data?.resumen || {};

                state.historial = {
                    items,
                    resumen,
                    filtros: {
                        CAMPO: {
                            categoria: 'TODOS',
                            q: '',
                            page: 1,
                            perPage: 10
                        },
                        CALL: {
                            categoria: 'TODOS',
                            q: '',
                            page: 1,
                            perPage: 10
                        }
                    }
                };

                const html = items.length ? `
                  <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                      ${renderBotoneraHistorial('CAMPO', resumen)}
                      ${renderBotoneraHistorial('CALL', resumen)}
                    </div>
                    <div class="rounded-2xl border border-gray-200 overflow-hidden">
                      <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100 flex items-center gap-2 font-bold text-emerald-800">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> Gestiones de campo / GEOCAMPO
                      </div>
                      <div id="hist-contenido-CAMPO" class="p-3">${renderHistorialFuente('CAMPO')}</div>
                    </div>
                    <div class="rounded-2xl border border-gray-200 overflow-hidden">
                      <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center gap-2 font-bold text-indigo-800">
                        <i data-lucide="headphones" class="w-4 h-4"></i> Gestiones de call / gestion_tmk
                      </div>
                      <div id="hist-contenido-CALL" class="p-3">${renderHistorialFuente('CALL')}</div>
                    </div>
                  </div>
                ` : `<div class="p-4 rounded-xl bg-gray-50 text-gray-500 text-sm">Aún no hay gestiones registradas para este cliente.</div>`;
                abrirModal('Historial de gestión', `${c.cliente} · ${c.documento}`, html);
                setTimeout(() => {
                    filtrarHistorial('CAMPO', 'TODOS');
                    filtrarHistorial('CALL', 'TODOS');
                    lucide.createIcons();
                }, 0);
            } catch (err) {
                abrirModal('Historial de gestión', c.cliente, `<div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">${escapeHtml(err.message)}</div>`);
            }
        }

        window.toggleHistorialExtra = function(rowId) {
            document.getElementById(`hist-extra-${rowId}`)?.classList.toggle('hidden');
        }

        function formatearPeriodoVisita(periodo) {
            const meses = {
                '01': 'Enero',
                '02': 'Febrero',
                '03': 'Marzo',
                '04': 'Abril',
                '05': 'Mayo',
                '06': 'Junio',
                '07': 'Julio',
                '08': 'Agosto',
                '09': 'Septiembre',
                '10': 'Octubre',
                '11': 'Noviembre',
                '12': 'Diciembre'
            };

            const partes = String(periodo || '').split('-');
            if (partes.length === 2 && meses[partes[1]]) {
                return `${meses[partes[1]]} ${partes[0]}`;
            }

            return periodo || '-';
        }

        window.verVisitas = async function(index) {
            const c = state.cuentas[index];
            abrirModal('Cantidad de visitas', c.cliente, `<div class="text-sm text-gray-500">Cargando visitas...</div>`);
            try {
                const data = await fetchJson(apiUrl('visitas_cliente', {
                    identificador: c.identificador
                }));
                const items = data.data?.items || [];
                const total = items.reduce((acc, it) => acc + Number(it.cantidad || 0), 0);
                const html = items.length ? `
          <div class="rounded-2xl border border-gray-200 overflow-hidden bg-white">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visitas por periodo</p>
            </div>
            <div class="divide-y divide-gray-100">
              ${items.map(it => `
                <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                  <span class="font-medium text-gray-700">${escapeHtml(formatearPeriodoVisita(it.periodo))}</span>
                  <strong class="min-w-10 text-right text-gray-900">${escapeHtml(it.cantidad)}</strong>
                </div>
              `).join('')}
            </div>
            <div class="flex items-center justify-between gap-4 px-4 py-3 border-t-2 border-gray-300 bg-gray-50">
              <span class="text-sm font-bold text-gray-800">Total</span>
              <strong class="text-lg text-[#a30d18]">${total}</strong>
            </div>
          </div>
        ` : `<div class="p-4 rounded-xl bg-gray-50 text-gray-500 text-sm">Sin visitas registradas.</div>`;
                abrirModal('Cantidad de visitas', `${c.cliente} · ${c.documento}`, html);
            } catch (err) {
                abrirModal('Cantidad de visitas', c.cliente, `<div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">${escapeHtml(err.message)}</div>`);
            }
        }

        window.abrirGestion = async function(index) {
            const c = state.cuentas[index];
            const tablaNombre = c.tabla_nombre || c.id_tabla || '';
            const identificador = c.identificador || '';
            const idCartera = c.id_cartera || c.idcartera || '';
            const dni = c.documento || c.identificador || '';

            if (!tablaNombre || !identificador || !idCartera) {
                abrirModal('Asignar gestión', `${c.cliente || ''} · ${c.documento || ''}`, `
                  <div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">
                    No se pudo abrir el formulario por falta de información.
                  </div>
                `);
                return;
            }

            abrirModal('Asignar gestión', `${c.cliente} · ${c.documento}`, `
              <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200 text-sm text-gray-600">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center mb-3">
                  <i data-lucide="loader-circle" class="w-6 h-6 animate-spin"></i>
                </div>
                <p class="font-semibold text-gray-800 mb-1">Preparando formulario</p>
                <p>Estamos cargando la información del cliente para registrar la gestión.</p>
              </div>
            `);

            const destino = `agregargestion2.php?id_tabla=${encodeURIComponent(tablaNombre)}&identificador=${encodeURIComponent(identificador)}&id_cartera=${encodeURIComponent(idCartera)}`;

            try {
                const formData = new FormData();
                formData.append('id_tabla', tablaNombre);
                formData.append('dni', dni);
                formData.append('idCartera', idCartera);

                const res = await fetch('get_info_personal.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!res.ok) {
                    throw new Error('No se pudo preparar la información del cliente.');
                }

                await res.text();
                window.location.href = destino;
            } catch (err) {
                abrirModal('Asignar gestión', `${c.cliente} · ${c.documento}`, `
                  <div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">
                    ${escapeHtml(err.message || 'No se pudo abrir el formulario de gestión.')}
                  </div>
                `);
            }
        }

        let buscarTimer = null;

        window.cambiarPaginaCuentas = async function(page) {
            const totalPages = Number(state.paginacion?.total_pages || 1);
            state.page = Math.max(1, Math.min(Number(page || 1), totalPages));
            await cargarCuentas();
        }

        function resetearYCargarCuentas() {
            state.page = 1;
            cargarCuentas();
        }

        document.getElementById('btnActualizar').addEventListener('click', resetearYCargarCuentas);
        els.fecha.addEventListener('change', resetearYCargarCuentas);
        els.selectCartera.addEventListener('change', resetearYCargarCuentas);
        els.pageSize.addEventListener('change', () => {
            state.perPage = Number(els.pageSize.value || 25);
            resetearYCargarCuentas();
        });
        els.buscar.addEventListener('input', () => {
            clearTimeout(buscarTimer);
            buscarTimer = setTimeout(() => {
                state.q = els.buscar.value.trim();
                resetearYCargarCuentas();
            }, 350);
        });

        cargarInicial().catch(err => {
            els.estadoCarga.textContent = 'Error';
            abrirModal('Aviso', '', `<div class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">${escapeHtml(err.message)}</div>`);
        }).finally(() => lucide.createIcons());
    </script>
</body>

</html>