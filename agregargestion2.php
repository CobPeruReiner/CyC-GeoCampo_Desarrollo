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
  error_log('Error de conexión MySQL: ' . $mysqli->connect_error);
  die('No pudimos conectar con el sistema. Inténtalo nuevamente en unos minutos.');
}

$resp = fetch_json_via_web("api.php?getAcciones&id_tabla=" . urlencode($id_tabla));
if ($resp === false) {
  die('No pudimos cargar las acciones disponibles. Actualiza la página e inténtalo nuevamente.');
}

$datos_servicio = json_decode($resp, true);
if (!is_array($datos_servicio)) {
  error_log('[agregargestion2] Respuesta inválida al cargar acciones: ' . substr((string)$resp, 0, 500));
  die('No pudimos preparar el formulario. Actualiza la página e inténtalo nuevamente.');
}

$acciones = !empty($datos_servicio['success']) ? ($datos_servicio['acciones'] ?? []) : [];

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

function h($value): string
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agregar Gestión</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <style>
    :root {
      --corp-red: #ff101f;
      --corp-red-dark: #c90d18;
      --corp-gray: #8f8f8f;
      --corp-dark: #151a24;
      --corp-muted: #687386;
      --corp-border: #e4e7ee;
      --corp-bg: #f4f6fa;
      --corp-soft-red: #fff0f1;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      padding-bottom: 2rem !important;
      font-family: 'Montserrat', Arial, sans-serif;
      color: var(--corp-dark);
      background:
        radial-gradient(circle at top left, rgba(255, 16, 31, .12), transparent 34%),
        linear-gradient(135deg, #ffffff 0%, #f4f6fa 55%, #eef1f6 100%);
    }

    .page-shell {
      width: min(1120px, calc(100% - 28px));
      margin: 0 auto;
      padding: 22px 0 34px;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      background: rgba(255, 255, 255, .92);
      border: 1px solid rgba(226, 230, 239, .9);
      border-radius: 24px;
      padding: 14px 18px;
      box-shadow: 0 18px 45px rgba(21, 26, 36, .08);
      backdrop-filter: blur(12px);
      margin-bottom: 18px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .brand-mark {
      display: grid;
      grid-template-columns: repeat(2, 20px);
      grid-template-rows: repeat(2, 20px);
      gap: 5px;
      flex: 0 0 auto;
    }

    .brand-mark span {
      border-radius: 4px;
    }

    .brand-mark span:nth-child(1),
    .brand-mark span:nth-child(4) {
      background: var(--corp-red);
    }

    .brand-mark span:nth-child(2),
    .brand-mark span:nth-child(3) {
      background: var(--corp-gray);
    }

    .brand-title {
      font-weight: 800;
      letter-spacing: .02em;
      line-height: 1.05;
      white-space: nowrap;
    }

    .brand-subtitle {
      color: var(--corp-muted);
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .11em;
      margin-top: 2px;
    }

    .client-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 999px;
      background: var(--corp-soft-red);
      color: var(--corp-red-dark);
      border: 1px solid rgba(255, 16, 31, .16);
      padding: 9px 12px;
      font-size: .82rem;
      font-weight: 800;
      max-width: 360px;
    }

    .client-pill span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .hero-card {
      position: relative;
      overflow: hidden;
      border-radius: 26px;
      padding: 24px;
      background: linear-gradient(135deg, #252a33 0%, #3a3f48 100%);
      color: #fff;
      box-shadow: 0 24px 52px rgba(21, 26, 36, .18);
      margin-bottom: 18px;
    }

    .hero-card::after {
      content: '';
      position: absolute;
      width: 170px;
      height: 170px;
      right: -52px;
      top: -54px;
      border-radius: 50%;
      background: rgba(255, 16, 31, .42);
    }

    .hero-kicker {
      position: relative;
      z-index: 1;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .22);
      font-weight: 800;
      font-size: .78rem;
      letter-spacing: .02em;
      margin-bottom: 14px;
    }

    .hero-title {
      position: relative;
      z-index: 1;
      font-weight: 800;
      font-size: clamp(1.75rem, 4vw, 2.65rem);
      line-height: 1.04;
      margin: 0 0 10px;
      letter-spacing: -.03em;
    }

    .hero-text {
      position: relative;
      z-index: 1;
      margin: 0;
      color: rgba(255, 255, 255, .80);
      font-weight: 500;
      max-width: 720px;
    }

    .status-panel {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 18px;
    }

    .status-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, .92);
      border: 1px solid var(--corp-border);
      border-radius: 20px;
      padding: 14px;
      box-shadow: 0 14px 34px rgba(21, 26, 36, .06);
      min-width: 0;
    }

    .status-icon {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      color: var(--corp-red);
      background: var(--corp-soft-red);
    }

    .status-label {
      color: var(--corp-muted);
      font-size: .76rem;
      font-weight: 700;
      margin-bottom: 1px;
    }

    .status-value {
      font-weight: 800;
      font-size: .95rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .form-card {
      background: rgba(255, 255, 255, .96);
      border: 1px solid var(--corp-border);
      border-radius: 26px;
      box-shadow: 0 20px 50px rgba(21, 26, 36, .08);
      overflow: hidden;
    }

    .form-section {
      padding: 20px 22px;
      border-bottom: 1px solid var(--corp-border);
    }

    .form-section:last-child {
      border-bottom: 0;
    }

    .section-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
    }

    .section-icon {
      width: 42px;
      height: 42px;
      border-radius: 15px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--corp-soft-red);
      color: var(--corp-red);
      flex: 0 0 auto;
    }

    .section-title {
      margin: 0;
      font-size: 1.05rem;
      font-weight: 800;
    }

    .section-help {
      margin: 2px 0 0;
      color: var(--corp-muted);
      font-size: .82rem;
      font-weight: 600;
    }

    label {
      color: #3b4352;
      font-size: .84rem;
      font-weight: 800;
      margin-bottom: 7px;
    }

    .form-control {
      min-height: 48px;
      border-radius: 15px;
      border: 1px solid #dce1ea;
      color: var(--corp-dark);
      font-weight: 600;
      transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    textarea.form-control {
      min-height: 108px;
    }

    .form-control:focus {
      border-color: rgba(255, 16, 31, .55);
      box-shadow: 0 0 0 .2rem rgba(255, 16, 31, .12);
    }

    .form-control:disabled {
      background: #f0f2f6;
      color: #99a1af;
      cursor: not-allowed;
    }

    .gps-alert,
    .user-alert {
      display: none;
      align-items: flex-start;
      gap: 10px;
      border-radius: 16px;
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: .88rem;
      font-weight: 700;
    }

    .gps-alert.show,
    .user-alert.show {
      display: flex;
    }

    .gps-alert.ok {
      background: #eefaf1;
      color: #146c2e;
      border: 1px solid #cfeeda;
    }

    .gps-alert.warn,
    .user-alert.warn {
      background: #fff8ed;
      color: #a34100;
      border: 1px solid #ffd8a6;
    }

    .user-alert.error {
      background: #fff0f1;
      color: #b20d17;
      border: 1px solid rgba(255, 16, 31, .18);
    }

    .photo-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .photo-card {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 8px;
      min-height: 150px;
      text-align: center;
      border-radius: 20px;
      border: 1.5px dashed #d7dce6;
      background: #fff;
      color: var(--corp-dark);
      cursor: pointer;
      padding: 18px 12px;
      transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }

    .photo-card:hover,
    .photo-card:focus-within {
      transform: translateY(-2px);
      border-color: rgba(255, 16, 31, .55);
      background: #fff8f9;
      box-shadow: 0 16px 34px rgba(21, 26, 36, .08);
    }

    .photo-card i {
      width: 46px;
      height: 46px;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--corp-soft-red);
      color: var(--corp-red);
      font-size: 1.25rem;
    }

    .photo-title {
      font-weight: 800;
    }

    .photo-help {
      color: var(--corp-muted);
      font-size: .78rem;
      font-weight: 700;
    }

    .photo-name {
      color: var(--corp-red-dark);
      font-size: .76rem;
      font-weight: 800;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .file-hidden {
      position: absolute;
      opacity: 0;
      pointer-events: none;
      width: 1px;
      height: 1px;
    }

    .actions-bar {
      position: sticky;
      bottom: 0;
      z-index: 10;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding: 16px 22px;
      background: rgba(255, 255, 255, .92);
      border-top: 1px solid var(--corp-border);
      backdrop-filter: blur(10px);
    }

    .btn-corp {
      min-height: 50px;
      border: 0;
      border-radius: 16px;
      padding: 0 22px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      font-weight: 800;
      color: #fff;
      background: var(--corp-red);
      box-shadow: 0 12px 24px rgba(255, 16, 31, .22);
      transition: transform .15s ease, background .15s ease, box-shadow .15s ease, opacity .15s ease;
    }

    .btn-corp:hover,
    .btn-corp:focus {
      color: #fff;
      background: var(--corp-red-dark);
      transform: translateY(-1px);
      box-shadow: 0 16px 30px rgba(255, 16, 31, .28);
      outline: none;
    }

    .btn-corp:disabled {
      opacity: .72;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .spinner-inline {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, .55);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
    }

    .btn-corp.loading .spinner-inline {
      display: inline-block;
    }

    .btn-corp.loading .btn-icon {
      display: none;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    @media (max-width: 768px) {
      .page-shell {
        width: min(100% - 22px, 540px);
        padding-top: 12px;
      }

      .topbar {
        border-radius: 20px;
        align-items: flex-start;
      }

      .client-pill {
        display: none;
      }

      .hero-card {
        padding: 20px;
        border-radius: 22px;
      }

      .hero-title {
        font-size: 1.85rem;
      }

      .status-panel {
        grid-template-columns: 1fr;
      }

      .form-section {
        padding: 18px 16px;
      }

      .photo-grid {
        grid-template-columns: 1fr;
      }

      .actions-bar {
        padding: 14px 16px;
      }

      .btn-corp {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <main class="page-shell">
    <header class="topbar">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <div>
          <div class="brand-title">COBRANZAS PERÚ</div>
          <div class="brand-subtitle">GEOCAMPO</div>
        </div>
      </div>
      <div class="client-pill" title="Identificador de cliente">
        <i class="fas fa-id-card"></i>
        <span><?php echo h($identificador); ?></span>
      </div>
    </header>

    <section class="hero-card">
      <h1 class="hero-title">Agregar gestión</h1>
    </section>

    <div id="user-alert" class="user-alert" role="alert"></div>
    <div id="gps-alert" class="gps-alert warn" role="status">
      <i class="fas fa-location-arrow mt-1"></i>
      <span>Estamos validando la ubicación del dispositivo.</span>
    </div>

    <form id="agregar-gestion-form"
      action="guardargestion.php?id_tabla=<?php echo urlencode($id_tabla); ?>&identificador=<?php echo urlencode($identificador); ?>"
      method="post" enctype="multipart/form-data" novalidate>

      <input type="hidden" id="promesa-efecto" name="promesa-efecto">
      <input type="hidden" id="latitud" name="latitud">
      <input type="hidden" id="longitud" name="longitud">
      <input type="hidden" id="txt" name="txt">
      <input type="hidden" name="accuracy" id="accuracy">
      <input type="hidden" id="fecha_creacion_dispositivo" name="fecha_creacion_dispositivo">
      <input type="hidden" id="id_cartera" name="id_cartera">

      <div class="form-card">
        <section class="form-section">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-tasks"></i></div>
            <div>
              <h2 class="section-title">Gestión realizada</h2>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="idaccion">Acción</label>
              <select class="form-control" id="idaccion" name="idaccion" required>
                <option value="" disabled selected>Selecciona una acción</option>
                <?php foreach ($acciones as $accion): ?>
                  <option value="<?php echo h($accion['IDACCION'] ?? ''); ?>"><?php echo h($accion['ACCION'] ?? ''); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="idefecto">Efecto</label>
              <select class="form-control" id="idefecto" name="idefecto" required disabled>
                <option value="" disabled selected>Primero selecciona una acción</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="idmotivo">Motivo</label>
              <select class="form-control" id="idmotivo" name="idmotivo">
                <option id="idmotivo_default" value="" disabled selected>Selecciona un motivo</option>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="idcontacto">Contacto</label>
              <select class="form-control" id="idcontacto" name="idcontacto">
                <option id="idcontacto_default" value=" " disabled selected>Selecciona un contacto</option>
              </select>
            </div>
          </div>

          <div class="form-group mb-0">
            <label for="observacion">Observación</label>
            <textarea class="form-control" id="observacion" name="observacion" rows="3" placeholder="Escribe una observación..."></textarea>
          </div>
        </section>

        <section class="form-section">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-home"></i></div>
            <div>
              <h2 class="section-title">Verificación del domicilio</h2>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="iddireccion">Dirección</label>
              <select class="form-control" id="iddireccion" name="iddireccion" <?php if ($requiredAttribute) echo "required"; ?>>
                <option value="" disabled selected>Selecciona una dirección</option>
                <?php foreach ($direcciones as $direccion): ?>
                  <option value="<?php echo h($direccion['IDDIRECCION'] ?? ''); ?>">
                    <?php echo h($direccion['DIRECCION_DEPURADA'] ?? ''); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="nomcontacto">Número de contacto</label>
              <input type="text" class="form-control" id="nomcontacto" name="nomcontacto" placeholder="Ej. 987654321">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="pisos">Pisos</label>
              <input type="text" class="form-control" id="pisos" name="pisos" placeholder="Ej. 2 pisos">
            </div>
            <div class="form-group col-md-4">
              <label for="puerta">Puerta</label>
              <input type="text" class="form-control" id="puerta" name="puerta" placeholder="Ej. negra / metal">
            </div>
            <div class="form-group col-md-4">
              <label for="fachada">Fachada</label>
              <input type="text" class="form-control" id="fachada" name="fachada" placeholder="Ej. blanca / ladrillo">
            </div>
          </div>
        </section>

        <section class="form-section">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-handshake"></i></div>
            <div>
              <h2 class="section-title">Compromiso de pago</h2>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="fecha_promesa">Fecha promesa</label>
              <input type="date" class="form-control" id="fecha_promesa" name="fecha_promesa" disabled>
            </div>
            <div class="form-group col-md-4">
              <label for="monto_promesa">Monto promesa</label>
              <input type="number" class="form-control" id="monto_promesa" name="monto_promesa" placeholder="0.00" min="0.01" step="0.01" disabled>
            </div>
            <div class="form-group col-md-4">
              <label for="hora_visita">Hora visita</label>
              <input type="time" class="form-control" id="hora_visita" name="hora_visita" required min="07:00" max="20:00">
            </div>
          </div>
        </section>

        <section class="form-section">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-camera"></i></div>
            <div>
              <h2 class="section-title">Adjuntar Evidenciaa</h2>
            </div>
          </div>

          <div class="photo-grid">
            <label for="imagen1" class="photo-card">
              <i class="fas fa-camera-retro"></i>
              <span class="photo-title">Foto 1</span>
              <span class="photo-help">Obligatoria</span>
              <span class="photo-name" id="imagen1-name">Sin archivo seleccionado</span>
              <input type="file" class="file-hidden" id="imagen1" name="imagen1" accept="image/*" capture="environment" required>
            </label>

            <label for="imagen2" class="photo-card">
              <i class="fas fa-camera"></i>
              <span class="photo-title">Foto 2</span>
              <span class="photo-help">Opcional</span>
              <span class="photo-name" id="imagen2-name">Sin archivo seleccionado</span>
              <input type="file" class="file-hidden" id="imagen2" name="imagen2" accept="image/*" capture="environment">
            </label>

            <label for="imagen3" class="photo-card">
              <i class="fas fa-images"></i>
              <span class="photo-title">Foto 3</span>
              <span class="photo-help">Opcional</span>
              <span class="photo-name" id="imagen3-name">Sin archivo seleccionado</span>
              <input type="file" class="file-hidden" id="imagen3" name="imagen3" accept="image/*" capture="environment">
            </label>
          </div>
        </section>

        <div class="actions-bar">
          <button type="submit" name="btnAddGestion" id="btn-add-gestion" class="btn-corp">
            <span class="spinner-inline" aria-hidden="true"></span>
            <i class="fas fa-save btn-icon"></i>
            <span id="btn-add-text">Guardar gestión</span>
          </button>
        </div>
      </div>
    </form>
  </main>

  <?php include 'MSsesionExpirada.html'; ?>

  <script>
    const main_url = <?php echo json_encode(getenv('GEOCAMPO_BASE_URL') ?: 'https://geocampo.online'); ?>;

    const form = document.getElementById('agregar-gestion-form');
    const btnAdd = document.getElementById('btn-add-gestion');
    const btnText = document.getElementById('btn-add-text');
    const userAlert = document.getElementById('user-alert');
    const gpsAlert = document.getElementById('gps-alert');
    const gpsResumen = document.getElementById('gps-resumen');

    function showUserMessage(type, message) {
      userAlert.className = 'user-alert show ' + (type || 'warn');
      const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
      userAlert.innerHTML = `<i class="fas ${icon} mt-1"></i><span>${escapeHtml(message)}</span>`;
      userAlert.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    }

    function hideUserMessage() {
      userAlert.className = 'user-alert';
      userAlert.innerHTML = '';
    }

    function setGpsMessage(type, message) {
      gpsAlert.className = 'gps-alert show ' + type;
      gpsAlert.innerHTML = `<i class="fas fa-location-arrow mt-1"></i><span>${escapeHtml(message)}</span>`;
      gpsResumen.textContent = type === 'ok' ? 'GPS validado' : 'Requiere atención';
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function setSelectLoading(select, message) {
      select.innerHTML = `<option value="" disabled selected>${message}</option>`;
      select.disabled = true;
    }

    function resetSelect(select, message) {
      select.innerHTML = `<option value="" disabled selected>${message}</option>`;
      select.disabled = false;
    }

    function fetchJson(url, friendlyMessage) {
      return fetch(url)
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP ' + response.status);
          }
          return response.json();
        })
        .catch(error => {
          console.error(friendlyMessage, error);
          showUserMessage('error', 'No pudimos cargar la información solicitada. Revisa tu conexión e inténtalo nuevamente.');
          throw error;
        });
    }

    function hideLabel() {
      return true;
    }

    function initGeolocation() {
      const urlParams = new URLSearchParams(window.location.search);
      const idCartera = urlParams.get('id_cartera');
      document.getElementById('id_cartera').value = idCartera || '';

      if (!navigator.geolocation) {
        setGpsMessage('warn', 'Este navegador no permite validar ubicación. Usa un navegador compatible para registrar la visita.');
        console.error('Geolocalización no compatible con este navegador.');
        return;
      }

      setGpsMessage('warn', 'Estamos validando la ubicación del dispositivo.');

      navigator.geolocation.getCurrentPosition(function(position) {
        const latitud = position.coords.latitude;
        const longitud = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        console.log('GPS obtenido', {
          latitud,
          longitud,
          accuracy
        });

        document.getElementById('latitud').value = latitud;
        document.getElementById('longitud').value = longitud;
        document.getElementById('accuracy').value = accuracy;
        document.getElementById('txt').value = 'GPS ACTIVADO';

        setGpsMessage('ok', 'Ubicación validada correctamente. Ya puedes registrar la gestión.');
      }, function(error) {
        console.error('Error al obtener ubicación:', error);
        document.getElementById('txt').value = 'GPS NO ACTIVADO';
        setGpsMessage('warn', 'Activa la ubicación del dispositivo y actualiza la página antes de guardar la gestión.');
      }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      });
    }

    function cargarEfectos(idaccion) {
      const efectosSelect = document.getElementById('idefecto');
      const motivosSelect = document.getElementById('idmotivo');
      const contactosSelect = document.getElementById('idcontacto');

      hideUserMessage();
      setSelectLoading(efectosSelect, 'Cargando efectos...');
      resetSelect(motivosSelect, 'Selecciona un motivo');
      resetSelect(contactosSelect, 'Selecciona un contacto');
      motivosSelect.removeAttribute('required');
      contactosSelect.removeAttribute('required');

      const urlServicioEfectos = main_url + '/api.php?getEfectos&idaccion=' + encodeURIComponent(idaccion);

      fetchJson(urlServicioEfectos, 'Error al obtener efectos')
        .then(data => {
          if (!data.success) {
            console.error('Respuesta sin éxito al obtener efectos:', data);
            showUserMessage('error', 'No encontramos efectos disponibles para la acción seleccionada.');
            resetSelect(efectosSelect, 'Sin efectos disponibles');
            return;
          }

          resetSelect(efectosSelect, 'Selecciona un efecto');
          (data.efectos || []).forEach(function(efecto) {
            const option = document.createElement('option');
            option.value = efecto.IDEFECTO;
            option.textContent = efecto.EFECTO;
            option.dataset.promesa = efecto.promesa;
            efectosSelect.appendChild(option);
          });
        });
    }

    function cargarMotivos(idefecto) {
      const motivosSelect = document.getElementById('idmotivo');
      setSelectLoading(motivosSelect, 'Cargando motivos...');

      const urlServicioMotivos = main_url + '/api.php?getMotivos&idefecto=' + encodeURIComponent(idefecto);

      fetchJson(urlServicioMotivos, 'Error al obtener motivos')
        .then(data => {
          resetSelect(motivosSelect, 'Selecciona un motivo');

          if (data.success && data.motivos && data.motivos.length > 0) {
            data.motivos.forEach(function(motivo) {
              const option = document.createElement('option');
              option.value = motivo.IDMOTIVO;
              option.textContent = motivo.MOTIVO;
              motivosSelect.appendChild(option);
            });
            motivosSelect.setAttribute('required', 'required');
          } else {
            motivosSelect.removeAttribute('required');
            motivosSelect.innerHTML = '<option value="" disabled selected>No aplica motivo</option>';
          }
        });
    }

    function cargarContactos(idefecto) {
      const contactosSelect = document.getElementById('idcontacto');
      setSelectLoading(contactosSelect, 'Cargando contactos...');

      const urlServicioContactos = main_url + '/api.php?getContactos&idefecto=' + encodeURIComponent(idefecto);

      fetchJson(urlServicioContactos, 'Error al obtener contactos')
        .then(data => {
          resetSelect(contactosSelect, 'Selecciona un contacto');

          if (data.success && data.contactos && data.contactos.length > 0) {
            data.contactos.forEach(function(contacto) {
              const option = document.createElement('option');
              option.value = contacto.IDCONTACTO;
              option.textContent = contacto.CONTACTO;
              contactosSelect.appendChild(option);
            });
            contactosSelect.setAttribute('required', 'required');
          } else {
            contactosSelect.removeAttribute('required');
            contactosSelect.innerHTML = '<option value=" " disabled selected>No aplica contacto</option>';
          }
        });
    }

    function configurarPromesa(select) {
      const selectedOption = select.options[select.selectedIndex];
      const promesa = selectedOption ? selectedOption.dataset.promesa : 0;
      const montoInput = document.getElementById('monto_promesa');
      const fechaInput = document.getElementById('fecha_promesa');

      document.getElementById('promesa-efecto').value = promesa || 0;

      if (String(promesa) === '1') {
        montoInput.required = true;
        fechaInput.required = true;
        montoInput.disabled = false;
        fechaInput.disabled = false;

        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const primerDia = `${yyyy}-${mm}-01`;
        const ultimoDia = new Date(yyyy, hoy.getMonth() + 1, 0).toISOString().split('T')[0];

        fechaInput.min = primerDia;
        fechaInput.max = ultimoDia;
        showUserMessage('warn', 'El efecto seleccionado requiere registrar fecha y monto de promesa.');
      } else {
        montoInput.required = false;
        fechaInput.required = false;
        montoInput.disabled = true;
        fechaInput.disabled = true;
        montoInput.value = '';
        fechaInput.value = '';
        fechaInput.removeAttribute('min');
        fechaInput.removeAttribute('max');
        hideUserMessage();
      }
    }

    document.getElementById('idaccion').addEventListener('change', function() {
      if (this.value) cargarEfectos(this.value);
    });

    document.getElementById('idefecto').addEventListener('change', function() {
      if (!this.value) return;
      cargarMotivos(this.value);
      cargarContactos(this.value);
      configurarPromesa(this);
    });

    ['imagen1', 'imagen2', 'imagen3'].forEach(function(id) {
      const input = document.getElementById(id);
      const label = document.getElementById(id + '-name');
      input.addEventListener('change', function() {
        label.textContent = this.files && this.files.length ? this.files[0].name : 'Sin archivo seleccionado';
      });
    });

    form.addEventListener('submit', function(event) {
      hideUserMessage();

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        form.classList.add('was-validated');
        showUserMessage('error', 'Completa los campos obligatorios antes de guardar la gestión.');
        return;
      }

      const ahoraUTC = new Date().toISOString();
      document.getElementById('fecha_creacion_dispositivo').value = ahoraUTC;

      btnAdd.disabled = true;
      btnAdd.classList.add('loading');
      btnText.textContent = 'Guardando...';
    });

    initGeolocation();
  </script>

  <script src="resize.js"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="verificarToken.js"></script>
</body>

</html>