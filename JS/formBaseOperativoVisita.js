document.addEventListener("DOMContentLoaded", function () {
  if (typeof CONFIG === "undefined") {
    console.error("CONFIG no está definido.");
    return;
  }

  const form = document.getElementById(CONFIG.formId);

  if (!form) {
    console.error("Formulario no encontrado:", CONFIG.formId);
    return;
  }

  /* ================= HELPERS ================= */

  const $ = (id) => document.getElementById(id);

  const latitud = $("latitud");
  const longitud = $("longitud");
  const accuracy = $("accuracy");
  const fechaDispositivo = $("fecha_creacion_dispositivo");
  const btnGuardar = $("btnGuardar");
  const spinnerGuardar = $("spinnerGuardar");
  const iconGuardar = $("iconGuardar");
  const textGuardar = $("textGuardar");

  /* ================= SOLO NÚMEROS ================= */

  function soloNumeros(input) {
    if (!input) return;

    input.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, "");
    });
  }

  // Aplicar a teléfono
  const telefono = form.querySelector('[name="funcionario_telefono"]');
  soloNumeros(telefono);

  // Aplicar a DNI si existe
  const dniInput = $("dni_cliente");
  soloNumeros(dniInput);

  /* ================= GPS ================= */

  function actualizarEstadoGPS(tipo, mensaje) {
    const badge = $("gpsStatus");
    if (!badge) return;

    badge.classList.remove(
      "badge-warning",
      "badge-success",
      "badge-danger",
      "badge-info",
      "gps-buscando",
    );

    if (tipo === "esperando") badge.classList.add("badge-warning");
    if (tipo === "buscando") badge.classList.add("badge-info", "gps-buscando");
    if (tipo === "ok") badge.classList.add("badge-success");
    if (tipo === "error") badge.classList.add("badge-danger");

    badge.innerHTML = '<i class="fas fa-map-marker-alt"></i> ' + mensaje;
  }

  function obtenerGPS() {
    actualizarEstadoGPS("buscando", "GPS: obteniendo ubicación...");

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        latitud.value = pos.coords.latitude;
        longitud.value = pos.coords.longitude;
        accuracy.value = pos.coords.accuracy;

        if ($("latPreview"))
          $("latPreview").textContent = pos.coords.latitude.toFixed(6);
        if ($("lonPreview"))
          $("lonPreview").textContent = pos.coords.longitude.toFixed(6);
        if ($("accPreview"))
          $("accPreview").textContent = Math.round(pos.coords.accuracy);

        actualizarEstadoGPS("ok", "GPS obtenido");
      },
      function () {
        actualizarEstadoGPS("error", "GPS no disponible");
        latitud.value = "";
        longitud.value = "";
        accuracy.value = "";
      },
      { enableHighAccuracy: true, timeout: 10000 },
    );
  }

  if ($("btnRefrescarGPS")) {
    actualizarEstadoGPS("esperando", "GPS: esperando...");
    obtenerGPS();
    $("btnRefrescarGPS").addEventListener("click", obtenerGPS);
  }

  /* ================= FOTO PREVIEW ================= */

  const inputFoto = $("foto");

  const btnEliminarFoto = $("btnEliminarFoto");

  if (inputFoto) {
    inputFoto.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (ev) {
        if ($("fotoPreview")) $("fotoPreview").src = ev.target.result;
        if ($("fotoPreviewWrap")) $("fotoPreviewWrap").style.display = "block";
        if ($("btnFoto")) $("btnFoto").style.display = "none";
      };
      reader.readAsDataURL(file);
    });
  }

  if (btnEliminarFoto && inputFoto) {
    btnEliminarFoto.addEventListener("click", function () {
      inputFoto.value = "";

      if ($("fotoPreviewWrap")) $("fotoPreviewWrap").style.display = "none";

      if ($("btnFoto")) $("btnFoto").style.display = "block";
    });
  }

  /* ================= UNIDAD / AGENCIA ================= */

  const unidad = $("unidad_negocio");
  const agencia = $("agencia");
  const wrapAgenciaOtro = $("wrapAgenciaOtro");
  const agenciaOtro = $("agencia_otro");

  function resetAgencia() {
    if (!agencia) return;
    agencia.innerHTML = '<option value="">Seleccione unidad primero</option>';
    agencia.disabled = true;

    if (wrapAgenciaOtro) wrapAgenciaOtro.style.display = "none";
    if (agenciaOtro) {
      agenciaOtro.value = "";
      agenciaOtro.required = false;
    }
  }

  if (unidad && agencia) {
    resetAgencia();

    /* ===== CARGAR UNIDADES AL INICIAR ===== */

    if (CONFIG.usarBackend && CONFIG.urlUnidades) {
      fetch(CONFIG.urlUnidades)
        .then((r) => r.json())
        .then((data) => {
          if (!data.success) return;

          data.rows.forEach((u) => {
            const opt = document.createElement("option");
            opt.value = u.id;
            opt.textContent = u.nombre;
            unidad.appendChild(opt);
          });
        });
    } else {
      if (CONFIG.unidadesMock) {
        CONFIG.unidadesMock.forEach((u) => {
          const opt = document.createElement("option");
          opt.value = u.id;
          opt.textContent = u.nombre;
          unidad.appendChild(opt);
        });
      }
    }

    /* ===== CUANDO CAMBIA UNIDAD ===== */

    unidad.addEventListener("change", function () {
      resetAgencia();
      if (!this.value) return;

      if (CONFIG.usarBackend && CONFIG.urlAgencias) {
        agencia.innerHTML = '<option value="">Cargando...</option>';

        fetch(CONFIG.urlAgencias + this.value)
          .then((r) => r.json())
          .then((data) => {
            if (!data.success) return;

            agencia.innerHTML = '<option value="">Seleccione...</option>';

            data.rows.forEach((a) => {
              const opt = document.createElement("option");
              opt.value = a.id;
              opt.textContent = a.nombre;

              if (a.nombre.toUpperCase().includes("OTROS")) {
                opt.dataset.otro = "1";
              }

              agencia.appendChild(opt);
            });

            agencia.disabled = false;
          });
      } else {
        const agencias = CONFIG.agenciasMock || {};
        if (!agencias[this.value]) return;

        agencia.innerHTML = '<option value="">Seleccione...</option>';

        agencias[this.value].forEach((a) => {
          const opt = document.createElement("option");
          opt.value = a.id;
          opt.textContent = a.nombre;
          agencia.appendChild(opt);
        });

        agencia.disabled = false;
      }
    });

    agencia.addEventListener("change", function () {
      const opt = this.options[this.selectedIndex];

      if (opt.dataset.otro === "1") {
        wrapAgenciaOtro.style.display = "block";
        agenciaOtro.required = true;
      } else {
        wrapAgenciaOtro.style.display = "none";
        agenciaOtro.required = false;
        agenciaOtro.value = "";
      }
    });
  }

  /* ================= NOTIFICACIONES (TOAST) ================= */

  const notyf = new Notyf({
    duration: 2800,
    position: { x: "right", y: "top" },
    dismissible: true,
    ripple: false,
    types: [
      { type: "info", background: "#1f2937", icon: false },
      { type: "warning", background: "#b45309", icon: false },
      { type: "error", background: "#b91c1c", icon: false },
      { type: "success", background: "#166534", icon: false },
    ],
  });

  function notificar(tipo, mensaje) {
    try {
      notyf.open({ type: tipo, message: mensaje });
    } catch {
      alert(mensaje);
    }
  }

  /* ================= SUBMIT ================= */

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    if (fechaDispositivo) {
      const now = new Date();

      const mysqlFormat =
        now.getFullYear() +
        "-" +
        String(now.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(now.getDate()).padStart(2, "0") +
        " " +
        String(now.getHours()).padStart(2, "0") +
        ":" +
        String(now.getMinutes()).padStart(2, "0") +
        ":" +
        String(now.getSeconds()).padStart(2, "0");

      fechaDispositivo.value = mysqlFormat;
    }

    const foto = form.querySelector("#foto");

    const obligatorios = [
      {
        sel: '[name="funcionario_nombre"]',
        msg: "Ingrese nombres y apellidos del funcionario.",
      },
      {
        sel: '[name="funcionario_telefono"]',
        msg: "Ingrese el teléfono del funcionario.",
      },
      { sel: "#unidad_negocio", msg: "Seleccione la unidad de negocio." },
      { sel: "#agencia", msg: "Seleccione la agencia." },
    ];

    const dni = document.getElementById("dni_cliente");

    if (dni) {
      obligatorios.unshift({
        sel: "#dni_cliente",
        msg: "Ingrese el DNI del cliente.",
      });
    }

    for (const item of obligatorios) {
      const el = form.querySelector(item.sel);
      if (!el) continue;

      if (el.disabled) {
        notificar("warning", "Primero seleccione una unidad de negocio.");
        document.getElementById("unidad_negocio")?.focus();
        return;
      }

      if ((el.value ?? "").trim() === "") {
        notificar("warning", item.msg);
        el.focus();
        return;
      }
    }

    const agenciaSel = form.querySelector("#agencia");

    if (agenciaSel) {
      const opt = agenciaSel.options[agenciaSel.selectedIndex];

      if (opt.dataset.otro === "1") {
        const otro = form.querySelector("#agencia_otro");

        if (!otro || (otro.value ?? "").trim() === "") {
          notificar("warning", "Ingrese el nombre de la agencia (Otros).");
          otro?.focus();
          return;
        }
      }
    }

    if (!latitud.value || !longitud.value) {
      notificar("error", "Debe obtener ubicación antes de guardar.");
      return;
    }

    if (
      !foto ||
      !foto.files ||
      foto.files.length === 0 ||
      foto.files[0].size === 0
    ) {
      notificar("warning", "Debe adjuntar la fotografía.");
      return;
    }

    if (!CONFIG.usarBackend) {
      console.log("Backend desactivado. Datos listos para enviar:");

      const datos = new FormData(form);

      for (let [key, value] of datos.entries()) console.log(key + ":", value);

      notificar("info", "Backend no configurado. Solo log en consola.");

      return;
    }

    // ===== MODO BACKEND (FETCH + JSON) =====
    try {
      if (btnGuardar) {
        btnGuardar.disabled = true;
        if (spinnerGuardar) spinnerGuardar.style.display = "inline-block";
        if (iconGuardar) iconGuardar.style.display = "none";
        if (textGuardar) textGuardar.textContent = "Guardando...";
      }

      const resp = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        credentials: "same-origin",
      });

      let data = null;
      try {
        data = await resp.json();
      } catch {
        const txt = await resp.text().catch(() => "");
        throw new Error("Respuesta no JSON. " + (txt ? txt.slice(0, 120) : ""));
      }

      if (!resp.ok) {
        throw new Error(data?.message || "Error HTTP " + resp.status);
      }

      if (data?.success) {
        notificar("success", data.message || "Guardado correctamente.");

        form.reset();
        resetAgencia?.();

        if ($("fotoPreviewWrap")) $("fotoPreviewWrap").style.display = "none";

        if ($("btnFoto")) $("btnFoto").style.display = "block";

        if ($("fotoPreview")) $("fotoPreview").src = "";

        if ($("btnRefrescarGPS")) obtenerGPS();
      } else {
        notificar("error", data?.message || "No se pudo guardar.");
      }
    } catch (err) {
      notificar("error", err?.message || "Error de red / servidor.");
    } finally {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        if (spinnerGuardar) spinnerGuardar.style.display = "none";
        if (iconGuardar) iconGuardar.style.display = "inline-block";
        if (textGuardar) textGuardar.textContent = "Guardar";
      }
    }
  });
});
