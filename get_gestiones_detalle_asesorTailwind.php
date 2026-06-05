<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_domain', '');

session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}


if (isset($_POST)) {
  $id_tabla = isset($_GET['carteraFile']) ? $_GET['carteraFile'] : 0;

  $data = file_get_contents("php://input");

  $row = json_decode($data, true);

  $htmlDetails = '<div class="modal fade" id="modalData" tabindex="-1" role="dialog" aria-labelledby="modalDataTitle" aria-hidden="true">';
  $htmlDetails .= '  <div class="modal-dialog modal-lg" role="document">';
  $htmlDetails .= '    <div class="modal-content">';
  $htmlDetails .= '      <div class="modal-header">';
  $htmlDetails .= '        <h5 class="modal-title" id="exampleModalLongTitle">Detalle de la gestión</h5>';
  $htmlDetails .= '        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
  $htmlDetails .= '        </button>';
  $htmlDetails .= '      </div>';
  $htmlDetails .= '      <div class="list-container">';
  $htmlDetails .= '       <ul class="list-group">';
  $htmlDetails .= '                <li class="list-group-item">📅 FECHA :    ' . $row['FECHA'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">📌 IDENTIFICADOR :    ' . $row['IDENTIFICADOR'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">⚡ EFECTO  : ' . $row['EFECTO'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">⚡ MOTIVO  : ' . $row['MOTIVO'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">📡 ESTADO GPS : ' . ($row['NOMBRE_GEOCAMPO_ESTADO_GPS'] ?? 'SIN DATOS') . '</li>';
  $htmlDetails .= '                <li class="list-group-item">👀 OBSERVACION   :  ' . $row['OBSERVACION'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">🗺 DIRECCION   :  ' . $row['DIRECCION_DEPURADA'] . '</li>';
  $htmlDetails .= '        </ul>';
  $htmlDetails .= '       <ul class="list-group">';
  $htmlDetails .= '                <li class="list-group-item">🙍‍♂️ NOMCONTACTO   :  ' . $row['NOMCONTACTO'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">🏠 PISOS :    ' . $row['PISOS'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">🚪 PUERTA    :   ' . $row['PUERTA'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">💒 FACHADA   :  ' . $row['FACHADA'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">📅 FECHA_PROMESA :    ' . $row['FECHA_PROMESA'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item">💸 MONTO_PROMESA :    ' . $row['MONTO_PROMESA'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item current-latitud" hidden>' . $row['latitud'] . '</li>';
  $htmlDetails .= '                <li class="list-group-item current-longitud" hidden>' . $row['longitud'] . '</li>';
  $htmlDetails .= '        </ul>';
  $htmlDetails .= '      </div>';


  $carpeta_ruta = substr($id_tabla, 2);

  $fechaCarpeta = '';

  $fechaObj = DateTime::createFromFormat('d/m/Y H:i:s', $row['FECHA']);

  if (!$fechaObj) {
    $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $row['FECHA']);
  }

  if ($fechaObj) {
    $fechaCarpeta = $fechaObj->format('Y-m-d');
  }

  $baseCarpeta = "./fotos/" . $carpeta_ruta;

  $primeraImagen = '';

  if (!empty($row['imagen1'])) {

    $rutaNueva = $baseCarpeta . "/" . $fechaCarpeta . "/" . $row['imagen1'];
    $rutaAntigua = $baseCarpeta . "/" . $row['imagen1'];

    if (file_exists($rutaNueva)) {
      $primeraImagen = $rutaNueva;
    } elseif (file_exists($rutaAntigua)) {
      $primeraImagen = $rutaAntigua;
    }
  }

  if (!empty($primeraImagen)) {

    $htmlDetails .= '<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">';
    $htmlDetails .= '<div class="carousel-inner">';

    $firstImage = true;

    for ($i = 1; $i <= 3; $i++) {

      $nombreImagen = $row["imagen$i"];
      if (empty($nombreImagen)) continue;

      $rutaNueva = $baseCarpeta . "/" . $fechaCarpeta . "/" . $nombreImagen;
      $rutaAntigua = $baseCarpeta . "/" . $nombreImagen;

      $rutaImagen = '';

      if (file_exists($rutaNueva)) {
        $rutaImagen = $rutaNueva;
      } elseif (file_exists($rutaAntigua)) {
        $rutaImagen = $rutaAntigua;
      }

      if (!empty($rutaImagen)) {

        $claseActive = $firstImage ? 'active' : '';

        $htmlDetails .= '<div class="carousel-item ' . $claseActive . '">';
        $htmlDetails .= '<img src="' . $rutaImagen . '" alt="Imagen ' . $i . '" class="d-block w-100 image_asesor">';
        $htmlDetails .= '</div>';

        $firstImage = false;
      }
    }

    $htmlDetails .= '</div>';

    $htmlDetails .= '<button class="carousel-control-prev" type="button" data-target="#carouselExampleControls" data-slide="prev">';
    $htmlDetails .= '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
    $htmlDetails .= '<span class="sr-only">Previous</span>';
    $htmlDetails .= '</button>';

    $htmlDetails .= '<button class="carousel-control-next" type="button" data-target="#carouselExampleControls" data-slide="next">';
    $htmlDetails .= '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
    $htmlDetails .= '<span class="sr-only">Next</span>';
    $htmlDetails .= '</button>';

    $htmlDetails .= '</div>';
  }
  /*************************************************************/
  $htmlDetails .= '      <div id="map" class="hide-html-element"></div>';
  $htmlDetails .= '      <div class="modal-footer">';
  $htmlDetails .= '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
  $htmlDetails .= '        <button type="button" class="btn btn-primary currentMap" onclick="verMapa()">Ver mapa</button>';
  $htmlDetails .= '      </div>';
  $htmlDetails .= '    </div>';
  $htmlDetails .= '  </div>';
  $htmlDetails .= '</div>';

  $responseData = array('html' => $htmlDetails, 'otro_dato' => 'valor');
  echo json_encode($responseData);
} else {
  echo 'No hay datos';
}

if (isset($mysqli) && $mysqli instanceof mysqli) {
  $mysqli->close();
}
