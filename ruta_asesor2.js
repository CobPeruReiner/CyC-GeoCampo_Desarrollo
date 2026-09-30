(() => {
  const dateInput = document.getElementById('ruta-asesor2-fecha');
  const tableContainer = document.getElementById('tabla-gestiones-asesor2');
  const mapContainer = document.getElementById('mapCanvas');
  const modalContainer = document.getElementById('modalContainer');
  const summaryContainer = document.getElementById('ruta-asesor2-resumen');

  const text = (value) => String(value ?? '');
  const escapeHtml = (value) => text(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
  const display = (value, fallback = 'No registrado') => text(value).trim() || fallback;

  function validPosition(gestion) {
    const lat = Number(gestion.latitud);
    const lng = Number(gestion.longitud);
    return Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0 && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  }

  function renderSummary(gestiones) {
    const geolocalizadas = gestiones.filter(validPosition).length;
    const evidencias = gestiones.reduce((total, gestion) => total + Number(gestion.evidencias || 0), 0);
    summaryContainer.innerHTML = `
      <article class="ruta-asesor2-stat"><i class="fas fa-clipboard-check"></i><strong>${gestiones.length}</strong><span>Gestiones registradas</span></article>
      <article class="ruta-asesor2-stat"><i class="fas fa-map-marker-alt"></i><strong>${geolocalizadas}</strong><span>Con GPS válido</span></article>
      <article class="ruta-asesor2-stat"><i class="fas fa-camera"></i><strong>${evidencias}</strong><span>Evidencias adjuntas</span></article>`;
  }

  function renderEmpty(message) {
    tableContainer.innerHTML = `<div class="ruta-asesor2-empty"><i class="fas fa-clipboard"></i>${escapeHtml(message)}</div>`;
  }

  function renderTable(gestiones) {
    if (!gestiones.length) {
      renderEmpty('No registraste gestiones para la fecha seleccionada.');
      return;
    }
    const rows = gestiones.map((gestion) => {
      const hour = text(gestion.fecha).includes(' ') ? text(gestion.fecha).split(' ')[1] : gestion.fecha;
      const gps = validPosition(gestion)
        ? '<span class="ruta-asesor2-gps valid"><i class="fas fa-map-marker-alt"></i> GPS válido</span>'
        : `<span class="ruta-asesor2-gps invalid"><i class="fas fa-exclamation-triangle"></i> ${escapeHtml(display(gestion.gps_estado, 'Sin GPS'))}</span>`;
      return `<tr>
        <td><span class="ruta-asesor2-time">${escapeHtml(hour)}</span></td>
        <td><span class="ruta-asesor2-account">${escapeHtml(gestion.identificador)}</span></td>
        <td><span class="ruta-asesor2-effect">${escapeHtml(display(gestion.efecto))}</span></td>
        <td><span class="ruta-asesor2-motive">${escapeHtml(display(gestion.motivo))}</span></td>
        <td>${gps}</td>
        <td class="text-right"><button type="button" class="btn-ruta-asesor2-detalle" data-id="${Number(gestion.id)}"><i class="fas fa-eye"></i> Detalle${gestion.evidencias ? ` · ${gestion.evidencias} foto${gestion.evidencias === 1 ? '' : 's'}` : ''}</button></td>
      </tr>`;
    }).join('');
    tableContainer.innerHTML = `<table class="ruta-asesor2-table">
      <thead><tr><th>Hora</th><th>Cuenta</th><th>Efecto</th><th>Motivo</th><th>Ubicación</th><th></th></tr></thead>
      <tbody>${rows}</tbody></table>`;
  }

  function renderMap(gestiones) {
    const posiciones = gestiones.filter(validPosition);
    if (!posiciones.length) {
      mapContainer.classList.remove('hide-html-element');
      mapContainer.innerHTML = '<div class="ruta-asesor2-map-empty"><div><i class="fas fa-map-marker-alt"></i><strong>No hay ubicaciones GPS válidas</strong><span>Las gestiones se registraron sin ubicación o con GPS desactivado.</span></div></div>';
      return;
    }
    if (!window.google?.maps) {
      mapContainer.innerHTML = '<div class="ruta-asesor2-map-empty"><div><i class="fas fa-map"></i><strong>No fue posible cargar el mapa</strong><span>Verifica la conexión e inténtalo nuevamente.</span></div></div>';
      return;
    }
    mapContainer.classList.remove('hide-html-element');
    mapContainer.innerHTML = '';
    const map = new google.maps.Map(mapContainer, { mapTypeId: 'roadmap' });
    const bounds = new google.maps.LatLngBounds();
    const infoWindow = new google.maps.InfoWindow({ maxWidth: 330 });
    posiciones.forEach((gestion, index) => {
      const position = { lat: Number(gestion.latitud), lng: Number(gestion.longitud) };
      if (!Number.isFinite(position.lat) || !Number.isFinite(position.lng)) return;
      bounds.extend(position);
      const marker = new google.maps.Marker({ position, map, label: String(index + 1) });
      marker.addListener('click', () => {
        infoWindow.setContent(`<div><strong>Gestión ${index + 1}</strong><br>Cuenta: ${escapeHtml(gestion.identificador)}<br>Efecto: ${escapeHtml(display(gestion.efecto))}<br>Dirección: ${escapeHtml(display(gestion.direccion))}</div>`);
        infoWindow.open({ anchor: marker, map });
      });
    });
    if (!bounds.isEmpty()) map.fitBounds(bounds, 40);
  }

  function detailItem(label, value, full = false) {
    return `<article class="ruta-asesor2-detail-item ${full ? 'full' : ''}"><span>${escapeHtml(label)}</span><strong>${escapeHtml(display(value))}</strong></article>`;
  }

  async function openDetail(id) {
    const response = await fetch(`ruta_asesor2_detalle.php?id=${encodeURIComponent(id)}`);
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.message || 'No fue posible cargar el detalle.');
    const item = data.gestion;
    const photos = data.evidencias.length
      ? `<section class="ruta-asesor2-evidence"><h3><i class="fas fa-camera"></i> Evidencias de la visita</h3><div id="rutaAsesor2Carousel" class="carousel slide" data-ride="carousel"><div class="carousel-inner">${data.evidencias.map((evidence, index) => `<div class="carousel-item ${index === 0 ? 'active' : ''}"><img src="${escapeHtml(evidence.url)}" alt="Evidencia ${evidence.slot}"></div>`).join('')}</div>${data.evidencias.length > 1 ? '<a class="carousel-control-prev" href="#rutaAsesor2Carousel" role="button" data-slide="prev"><span class="carousel-control-prev-icon"></span><span class="sr-only">Anterior</span></a><a class="carousel-control-next" href="#rutaAsesor2Carousel" role="button" data-slide="next"><span class="carousel-control-next-icon"></span><span class="sr-only">Siguiente</span></a>' : ''}</div></section>`
      : '<p class="ruta-asesor2-no-evidence"><i class="fas fa-camera"></i> Esta gestión no tiene evidencias fotográficas.</p>';
    modalContainer.innerHTML = `<div class="modal fade ruta-asesor2-modal" id="modalRutaAsesor2" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Detalle de la gestión</h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><section class="ruta-asesor2-detail-grid">${detailItem('Fecha y hora', item.fecha)}${detailItem('Cuenta', item.identificador)}${detailItem('Efecto', item.efecto)}${detailItem('Motivo', item.motivo)}${detailItem('Contacto', item.contacto)}${detailItem('Pisos / puerta', `${display(item.pisos, '-') } / ${display(item.puerta, '-')}`)}${detailItem('Dirección', item.direccion, true)}${detailItem('Observación', item.observacion, true)}${detailItem('Fachada', item.fachada)}${detailItem('Promesa de pago', item.fecha_promesa ? `${item.fecha_promesa} · ${display(item.monto_promesa)}` : 'No registrada')}</section>${photos}</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div></div></div></div>`;
    $('#modalRutaAsesor2').modal('show');
  }

  async function load() {
    const fecha = dateInput.value;
    if (!fecha) return;
    tableContainer.innerHTML = '<div class="ruta-asesor2-loading"><i class="fas fa-spinner fa-spin"></i>Cargando gestiones…</div>';
    try {
      const response = await fetch(`ruta_asesor2_gestiones.php?fecha=${encodeURIComponent(fecha)}`);
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'No fue posible consultar las gestiones.');
      renderSummary(data.gestiones);
      renderTable(data.gestiones);
      renderMap(data.gestiones);
    } catch (error) {
      renderSummary([]);
      renderEmpty(error instanceof Error ? error.message : 'No fue posible consultar las gestiones.');
    }
  }

  dateInput.addEventListener('change', load);
  tableContainer.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-ruta-asesor2-detalle');
    if (!button) return;
    openDetail(button.dataset.id).catch((error) => alert(error instanceof Error ? error.message : 'No fue posible cargar el detalle.'));
  });
  window.addEventListener('load', load, { once: true });
})();
