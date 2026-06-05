<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
session_name('geocampo');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['id'])) {
  header('Location: index.php');
  exit;
}

$id_usuario = $_SESSION['id'];

$cargoPersonal = $_SESSION['cargo'] ?? 0;

$cargosAdmin = [1, 8, 15, 16, 17, 18, 19, 20, 21, 22];

$esModal = isset($_GET['modal']);

if (in_array($cargoPersonal, $cargosAdmin) && !$esModal) {
  header("Location: informeRegistrosOperativos.php");
  exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Operativo</title>

  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
  <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

  <style>
    body {
      background: #f8f9fa;
    }

    .card {
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
    }

    .header-custom {
      background: #343a40;
      color: white;
      border-radius: 10px 10px 0 0;
      padding: 15px;
    }

    .preview-card {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      border: 1px solid #e5e7eb;
    }

    .preview-img {
      width: 100%;
      max-height: 320px;
      object-fit: cover;
      display: block;
      transition: transform .3s ease;
    }

    .preview-card:hover .preview-img {
      transform: scale(1.02);
    }

    .btn-eliminar-foto {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.6);
      border: none;
      color: #fff;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all .2s ease;
    }

    .btn-eliminar-foto:hover {
      background: #dc3545;
      transform: scale(1.1);
    }

    .req {
      color: #dc3545;
    }

    .small-help {
      font-size: 12px;
    }

    .badge-warning {
      background: #ffc107;
      color: #212529;
    }

    .badge-success {
      background: #28a745;
    }

    .badge-danger {
      background: #dc3545;
    }

    .badge-info {
      background: #17a2b8;
    }

    .gps-buscando {
      animation: pulse 1.2s infinite;
    }

    .notyf__toast {
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
      padding: 12px 14px;
    }

    .notyf__message {
      font-size: 14px;
      line-height: 1.2;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
        opacity: 1;
      }

      50% {
        transform: scale(1.05);
        opacity: .7;
      }

      100% {
        transform: scale(1);
        opacity: 1;
      }
    }
  </style>
</head>

<body>
  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-md-8">

        <?php if (!$esModal): ?>
          <a href="menu.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-chevron-left"></i> Volver
          </a>
        <?php endif; ?>

        <div class="card">
          <div class="header-custom text-center">
            <h4 class="mb-0"><i class="fas fa-clipboard-list"></i> Registro de Operativo</h4>
          </div>

          <div class="card-body">
            <form id="formOperativo" method="post" action="guardarOperativo.php" enctype="multipart/form-data">

              <input type="hidden" name="id_personal" value="<?php echo (int)$id_usuario; ?>">
              <input type="hidden" name="latitud" id="latitud" required>
              <input type="hidden" name="longitud" id="longitud" required>
              <input type="hidden" name="accuracy" id="accuracy" required>
              <input type="hidden" name="fecha_creacion_dispositivo" id="fecha_creacion_dispositivo">

              <!-- GPS -->
              <button type="button" class="btn btn-outline-secondary mb-2" id="btnRefrescarGPS">
                <i class="fas fa-crosshairs"></i> Reintentar GPS
              </button>

              <span id="gpsStatus" class="badge badge-warning px-3 py-2 ml-2">
                <i class="fas fa-map-marker-alt"></i> GPS: esperando…
              </span>

              <div class="alert alert-light border mb-3 mt-2">
                <div class="d-flex flex-wrap justify-content-between">
                  <div class="mb-2">
                    <strong>Ubicación:</strong>
                    <div class="text-muted">
                      Lat: <span id="latPreview">—</span> | Lon: <span id="lonPreview">—</span>
                    </div>
                  </div>
                  <div class="mb-2">
                    <strong>Precisión:</strong>
                    <div class="text-muted"><span id="accPreview">—</span> m</div>
                  </div>
                </div>
                <small class="text-muted small-help">
                  La precisión puede variar. Si es alta, reintenta.
                </small>
              </div>

              <!-- DNI CLIENTE -->
              <div class="form-group">
                <label>DNI del Cliente <span class="req">*</span></label>
                <input
                  type="text"
                  class="form-control"
                  name="dni_cliente"
                  id="dni_cliente"
                  inputmode="numeric"
                  required>
              </div>

              <!-- FUNCIONARIO -->
              <div class="form-row">
                <div class="form-group col-md-8">
                  <label>Nombres y Apellidos del Funcionario <span class="req">*</span></label>
                  <input type="text" class="form-control" name="funcionario_nombre" required>
                </div>
                <div class="form-group col-md-4">
                  <label>Teléfono <span class="req">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    name="funcionario_telefono"
                    inputmode="numeric"
                    required>
                </div>
              </div>

              <!-- UNIDAD -->
              <div class="form-group">
                <label>Unidad de negocio <span class="req">*</span></label>
                <select class="form-control" name="unidad_negocio" id="unidad_negocio" required>
                  <option value="">Seleccione...</option>
                </select>
              </div>

              <!-- AGENCIA -->
              <div class="form-group">
                <label>Agencia <span class="req">*</span></label>
                <select class="form-control" name="agencia" id="agencia" disabled required>
                  <option value="">Seleccione unidad primero</option>
                </select>
              </div>

              <!-- AGENCIA OTROS -->
              <div class="form-group" id="wrapAgenciaOtro" style="display:none;">
                <label>Nombre Agencia (Otros) <span class="req">*</span></label>
                <input type="text" class="form-control" name="agencia_otro" id="agencia_otro">
              </div>

              <!-- FOTO -->
              <div class="form-group">
                <label for="foto" class="btn btn-outline-primary btn-block py-4 mb-0" id="btnFoto">
                  <i class="fas fa-camera-retro fa-2x mb-2"></i><br>
                  Tomar Fotografía<br>
                  <small>(obligatoria)</small>
                </label>

                <input type="file"
                  id="foto"
                  name="foto"
                  accept="image/*"
                  capture="environment"
                  required
                  style="position:absolute;left:-9999px;">

                <div id="fotoPreviewWrap" class="mt-3" style="display:none;">
                  <div class="preview-card">
                    <img id="fotoPreview" class="preview-img">
                    <button type="button" id="btnEliminarFoto" class="btn-eliminar-foto">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="text-right">
                <button type="submit" class="btn btn-dark" id="btnGuardar">
                  <span id="spinnerGuardar" class="spinner-border spinner-border-sm mr-2" style="display:none;"></span>
                  <i class="fas fa-save" id="iconGuardar"></i>
                  <span id="textGuardar">Guardar</span>
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    const CONFIG = {
      formId: "formOperativo",
      validarDNI: true,
      usarBackend: true,

      urlUnidades: "api/unidadesNegocio.php",
      urlAgencias: "api/agenciasGestor.php?unidad="
    };
  </script>

  <script src="JS/formBaseOperativoVisita.js"></script>

</body>

</html>