<?php

$main_url = getenv('GEOCAMPO_BASE_URL') ?: 'https://geocampo.online';

const GEOCAMPO_HOST = 'geocampo.online';

function fetch_json_via_web(string $path, int $timeout = 8)
{
  $isDocker = file_exists('/.dockerenv') || getenv('IS_DOCKER');

  if ($isDocker) {
    $parsed = parse_url("/" . $path);
    $script = basename($parsed['path'] ?? '');
    if (strcasecmp($script, 'api.php') !== 0) {
      error_log("[fetch_json_via_web] abortado: script '{$script}' no es api.php");
      return false;
    }

    $qs = [];
    if (!empty($parsed['query'])) {
      parse_str($parsed['query'], $qs);
    }

    global $mysqli;
    $oldGet = $_GET;
    $oldReq = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_GET   = $qs;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $start = microtime(true);

    ob_start();
    try {
      include __DIR__ . '/api.php';
    } catch (\Throwable $e) {
      error_log("[fetch_json_via_web] include api.php lanzó: " . $e->getMessage());
    }
    $out = ob_get_clean();

    $elapsed = microtime(true) - $start;
    error_log(sprintf("[fetch_json_via_web] Tiempo de ejecución: %.3f s", $elapsed));

    $_GET   = $oldGet;
    $_SERVER['REQUEST_METHOD'] = $oldReq;

    if ($out !== '' && $out !== false && $out !== null) {
      error_log("[fetch_json_via_web] include local api.php OK");
      return preg_replace('/^\xEF\xBB\xBF/', '', $out);
    }

    error_log("[fetch_json_via_web] include local vacío/fallo");
    return false;
  }

  $host  = 'geocampo.online';
  $path  = ltrim($path, '/');
  $url   = "https://{$host}/{$path}";

  $ch  = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER     => ["User-Agent: geocampo-internal/1.0"]
  ]);

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  if ($resp !== false && $code >= 200 && $code < 300) {
    return preg_replace('/^\xEF\xBB\xBF/', '', $resp);
  }

  error_log("[fetch_json_via_web] HTTPS fallo url=$url code=$code err=$err");
  return false;
}

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

$id_tabla       = isset($_GET['id_tabla']) ? $_GET['id_tabla'] : $_SESSION['id_tabla'];
$identificador  = isset($_GET['identificador']) ? $_GET['identificador'] : '';

include('config.php');
if ($mysqli->connect_error) {
  die('Error de conexión: ' . $mysqli->connect_error);
}

$resp = fetch_json_via_web("api.php?getAcciones&id_tabla=" . urlencode($id_tabla));
if ($resp === false) {
  die('Error al obtener los datos del servicio.');
}

$datos_servicio = json_decode($resp, true);
if (!is_array($datos_servicio)) {
  die('Error al decodificar la respuesta JSON.');
}

$acciones = !empty($datos_servicio['success']) ? ($datos_servicio['acciones'] ?? []) : [];

$efectos = [0, 'EFECTO'];

$dni = $_SESSION['dni'] ?? '';

$resp = fetch_json_via_web(
  "api.php?getDirecciones&id_tabla=" . urlencode($id_tabla) . "&documento=" . urlencode($dni)
);

$requiredAttribute = false;
if ($resp !== false) {
  $datos_servicio = json_decode($resp, true);
  if (is_array($datos_servicio) && !empty($datos_servicio['success'])) {
    $direcciones = $datos_servicio['direcciones'] ?? [];
    $requiredAttribute = count($direcciones) > 0;
  } else {
    $direcciones = [];
  }
} else {
  $direcciones = [];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agregar Gestión</title>
  <link rel="stylesheet" href="style2.css">
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body style="padding-bottom: 1.5rem!important;">
  <div class="container py-4">
    <h3 class="mb-4">Agregar Gestión</h3>

    <!-- <p>ID de sesión: <?php echo isset($_SESSION['id']) ? $_SESSION['id'] : 'No definido'; ?></p> -->
    <!-- <p>ID de tabla: <?php echo $id_tabla; ?></p> -->
    <h4>ID: <?php echo $identificador; ?></h4>


    <form id="agregar-gestion-form"
      action="guardargestion.php?id_tabla=<?php echo $id_tabla; ?>&identificador=<?php echo $identificador; ?>"
      method="post" enctype="multipart/form-data" onsubmit="btnAddGestion.disabled = true; return true;">

      <input type="hidden" id="promesa-efecto" name="promesa-efecto">
      <input type="hidden" id="latitud" name="latitud">
      <input type="hidden" id="longitud" name="longitud">
      <input type="hidden" id="txt" name="txt">
      <input type="hidden" name="accuracy" id="accuracy">
      <input type="hidden" id="fecha_creacion_dispositivo" name="fecha_creacion_dispositivo">

      <input type="hidden" id="id_cartera" name="id_cartera">

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">
            <i class="fas fa-tasks form-section-icon"></i> Gestión
          </h5>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="idaccion">Acción</label>
              <select class="form-control" id="idaccion" name="idaccion" onchange="cargarEfectos(this.value)"
                required>
                <option value="" disabled selected>ACCION</option>
                <?php foreach ($acciones as $accion): ?>
                  <option value="<?php echo $accion['IDACCION']; ?>"><?php echo $accion['ACCION']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="idefecto">Efecto</label>
              <select class="form-control" id="idefecto" name="idefecto" required>
                <option value="" disabled selected>EFECTO</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="idmotivo">Motivo</label>
              <select class="form-control" id="idmotivo" name="idmotivo" onchange="hideLabel(this);">
                <option id="idmotivo_default" value="" disabled selected>MOTIVO</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="idcontacto">Contacto</label>
              <select class="form-control" id="idcontacto" name="idcontacto">
                <option id="idcontacto_default" value=" " disabled selected>CONTACTO</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="observacion">Observación</label>
            <textarea class="form-control" id="observacion" name="observacion" rows="3" placeholder="Escriba una observación..."></textarea>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Domicilio</h5>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="iddireccion">Dirección</label>
              <select class="form-control" id="iddireccion" name="iddireccion" onchange="hideLabel(this);"
                <?php if ($requiredAttribute) echo "required"; ?>>
                <option value="" disabled selected>DIRECCION</option>
                <?php foreach ($direcciones as $direccion): ?>
                  <option value="<?php echo $direccion['IDDIRECCION']; ?>">
                    <?php echo $direccion['DIRECCION_DEPURADA']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="nomcontacto">Número de Contacto</label>
              <input type="text" class="form-control" id="nomcontacto" name="nomcontacto" placeholder="Num Contacto">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="pisos">Pisos</label>
              <input type="text" class="form-control" id="pisos" name="pisos" placeholder="Pisos">
            </div>
            <div class="form-group col-md-4">
              <label for="puerta">Puerta</label>
              <input type="text" class="form-control" id="puerta" name="puerta" placeholder="Puerta">
            </div>
            <div class="form-group col-md-4">
              <label for="fachada">Fachada</label>
              <input type="text" class="form-control" id="fachada" name="fachada" placeholder="Fachada">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Promesa</h5>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="fecha_promesa">Fecha Promesa:</label>
              <input type="date" class="form-control" id="fecha_promesa" name="fecha_promesa"
                placeholder="Fecha Promesa">
            </div>
            <div class="form-group col-md-6">
              <label for="monto_promesa">Monto Promesa</label>
              <input type="number" class="form-control" id="monto_promesa" name="monto_promesa"
                placeholder="Monto Promesa" min="0.01" step="0.01">
            </div>
          </div>
          <div class="form-group">
            <label for="hora_visita">Hora Visita (7:00 - 20:00)</label>
            <input type="time" class="form-control" id="hora_visita" name="hora_visita" required min="07:00" max="20:00">
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">
            <i class="fas fa-camera form-section-icon"></i> Fotografías
          </h5>
          <div class="form-row text-center">
            <div class="form-group col-md-4">
              <label for="imagen1" class="btn btn-outline-primary btn-block py-4">
                <i class="fas fa-camera-retro fa-2x mb-2"></i><br>
                Tomar Foto 1<br><small>(obligatoria)</small>
              </label>
              <input type="file" class="form-control" id="imagen1" name="imagen1" accept="image/*" capture="environment" required style="height: 0;position: absolute;top: 50%;z-index: -1;">
            </div>
            <div class="form-group col-md-4">
              <label for="imagen2" class="btn btn-outline-secondary btn-block py-4">
                <i class="fas fa-camera fa-2x mb-2"></i><br>
                Tomar Foto 2<br><small>(opcional)</small>
              </label>
              <input type="file" class="form-control" id="imagen2" name="imagen2" accept="image/*" capture="environment" style="display: none;">
            </div>
            <div class="form-group col-md-4">
              <label for="imagen3" class="btn btn-outline-secondary btn-block py-4">
                <i class="fas fa-camera fa-2x mb-2"></i><br>
                Tomar Foto 3<br><small>(opcional)</small>
              </label>
              <input type="file" class="form-control" id="imagen3" name="imagen3" accept="image/*" capture="environment" style="display: none;">
            </div>
          </div>
        </div>
      </div>

      <div class="text-center">
        <button type="submit" name='btnAddGestion' id='btn-add-gestion' class="btn btn-success btn-lg px-5">
          <i class="fas fa-save"></i> Guardar Gestión
        </button>
      </div>
    </form>
  </div>

  <?php include 'MSsesionExpirada.html'; ?>

  <script>
    function hideLabel(selectElement) {
      var label = document.getElementById("accionLabel");
      if (selectElement.value === "") {
        label.style.display = "block";
      } else {
        label.style.display = "none";
      }
    }
  </script>

  <script>
    const main_url = "<?php echo getenv('GEOCAMPO_BASE_URL') ?: 'https://geocampo.online'; ?>";

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
          var latitud = position.coords.latitude;
          var longitud = position.coords.longitude;
          var accuracy = position.coords.accuracy;

          console.log("Lat:", latitud);
          console.log("Long:", longitud);
          console.log("Accuracy:", accuracy, "metros");

          var txt = "GPS ACTIVADO";

          document.getElementById("latitud").value = latitud;
          document.getElementById("longitud").value = longitud;
          document.getElementById("accuracy").value = accuracy;
          document.getElementById("txt").value = txt;

          const urlParams = new URLSearchParams(window.location.search);
          const idCartera = urlParams.get('id_cartera');
          document.getElementById("id_cartera").value = idCartera;

        },
        function(error) {
          console.error("Error al obtener la ubicación:", error.message);

          if (error.code === error.PERMISSION_DENIED) {
            alert("Ubicación no activada, activarla y refrescar la página");
          }
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    } else {
      alert('Geolocalización no compatible con este navegador');
      console.error("Geolocalización no compatible con este navegador.");
    }

    function cargarEfectos(idaccion) {
      var urlServicioEfectos = main_url + "/api.php?getEfectos&idaccion=" + idaccion;

      fetch(urlServicioEfectos)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            var efectosSelect = document.getElementById("idefecto");

            efectosSelect.innerHTML = '<option value="" disabled selected>EFECTO</option>';

            data.efectos.forEach(function(efecto) {
              var option = document.createElement("option");
              option.value = efecto.IDEFECTO;
              option.textContent = efecto.EFECTO;
              option.dataset.promesa = efecto.promesa;
              efectosSelect.appendChild(option);
            });

            efectosSelect.onchange = function(event) {
              cargarMotivos(this.value);
              cargarContactos(this.value);

              var selectedOption = event.target.options[event.target.selectedIndex];
              var promesa = selectedOption.dataset.promesa;

              document.getElementById("promesa-efecto").value = promesa || 0;

              var montoInput = document.getElementById("monto_promesa");
              var fechaInput = document.getElementById("fecha_promesa");

              if (promesa == 1) {
                montoInput.required = true;
                fechaInput.required = true;

                montoInput.disabled = false;
                fechaInput.disabled = false;

                const hoy = new Date();
                const yyyy = hoy.getFullYear();
                const mm = String(hoy.getMonth() + 1).padStart(2, '0');

                const primerDia = `${yyyy}-${mm}-01`;
                const ultimoDia = new Date(yyyy, hoy.getMonth() + 1, 0)
                  .toISOString()
                  .split('T')[0];

                fechaInput.min = primerDia;
                fechaInput.max = ultimoDia;

              } else {
                montoInput.required = false;
                fechaInput.required = false;

                montoInput.disabled = true;
                fechaInput.disabled = true;

                montoInput.value = '';
                fechaInput.value = '';
                fechaInput.removeAttribute('min');
                fechaInput.removeAttribute('max');
              }
            };

          } else {
            console.error("Error al obtener efectos:", data.message);
          }
        })
        .catch(error => {
          console.error("Error de red al obtener efectos:", error);
        });
    }

    function cargarMotivos(idefecto) {

      var urlServicioMotivos = main_url + "/api.php?getMotivos&idefecto=" + idefecto;

      fetch(urlServicioMotivos)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            var motivosSelect = document.getElementById("idmotivo");

            while (motivosSelect.options && motivosSelect.options.length > 1) {
              motivosSelect.remove(1);
            }

            if (data.motivos && data.motivos.length > 0) {
              data.motivos.forEach(function(motivo) {
                var option = document.createElement("option");
                option.value = motivo.IDMOTIVO;
                option.textContent = motivo.MOTIVO;
                motivosSelect.appendChild(option);
              });
              motivosSelect.setAttribute('required', '');
            } else {
              var option = document.createElement("option");

              option.value = "";

              option.textContent = "MOTIVO";

              option.disabled = true;

              var existingOption = motivosSelect.querySelector('#idmotivo_default');

              if (!existingOption) {
                motivosSelect.appendChild(option);
              }
              motivosSelect.removeAttribute('required');
            }


          } else {
            console.error("Error al obtener motivos:", data.message);
          }
        })
        .catch(error => {
          console.error("Error de red al obtener motivos:", error);
        });
    }

    function cargarContactos(idefecto) {
      var urlServicioContactos = main_url + "/api.php?getContactos&idefecto=" + idefecto;

      fetch(urlServicioContactos)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            var contactosSelect = document.getElementById("idcontacto");

            while (contactosSelect.options && contactosSelect.options.length > 1) {
              contactosSelect.remove(1);
            }

            if (data.contactos && data.contactos.length > 0) {
              data.contactos.forEach(function(contacto) {
                var option = document.createElement("option");

                option.value = contacto.IDCONTACTO;

                option.textContent = contacto.CONTACTO;

                contactosSelect.appendChild(option);
              });

              contactosSelect.setAttribute('required', true);
            } else {
              var option = document.createElement("option");

              option.value = " ";

              option.textContent = "CONTACTO";

              option.disabled = true;

              var existingOption = contactosSelect.querySelector('#idcontacto_default');

              if (!existingOption) {
                contactosSelect.appendChild(option);
              }
              contactosSelect.setAttribute('required', false);
            }

          } else {
            console.error("Error al obtener contactos:", data.message);
          }
        })
        .catch(error => {
          console.error("Error de red al obtener contactos:", error);
        });
    }

    document.getElementById("agregar-gestion-form").addEventListener("submit", function() {
      const ahoraUTC = new Date().toISOString();
      document.getElementById("fecha_creacion_dispositivo").value = ahoraUTC;
    });

    var imagen1 = document.getElementById("imagen1");
    var imagen2 = document.getElementById("imagen2");
    var imagen3 = document.getElementById("imagen3");
  </script>

  <script src="resize.js"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <!-- VERIFICAR TOKEN -->
  <script src="verificarToken.js"></script>
</body>

</html>