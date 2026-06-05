function verMapa() {
  const latitudValue =
    document.getElementsByClassName("current-latitud")[0].innerHTML;
  const longitudValue =
    document.getElementsByClassName("current-longitud")[0].innerHTML;

  var latitud = parseFloat(latitudValue);
  var longitud = parseFloat(longitudValue);

  showMap(latitud, longitud);

  const currentMap = document.querySelector("#map");
  currentMap.classList.remove("hide-html-element");
}

function showMap(latitud, longitud) {
  const coord = { lat: latitud, lng: longitud };

  const mapHtml = document.getElementById("map");

  const map = new google.maps.Map(mapHtml, {
    zoom: 18,
    center: coord,
  });

  const marker = new google.maps.Marker({
    position: coord,
    map: map,
  });
}

// // Initialize and add the map
var map;
var bounds;
function initMap(data) {
  bounds = new google.maps.LatLngBounds();
  var mapOptions = {
    mapTypeId: "roadmap",
  };

  // Display a map on the web page
  map = new google.maps.Map(document.getElementById("mapCanvas"), mapOptions);
  map.setTilt(50);

  // Multiple markers location, latitude, and longitude
  // const asignacionDirections = [];

  const markers = data.map((e, index) => {
    // asignacionDirections.push(e.DIRECCION_DEPURADA)
    return [
      `Gestión ${index + 1}`,
      parseFloat(e.latitud),
      parseFloat(e.longitud),
    ];
  });

  // Info window content
  const infoWindowContent = data.map((e, index) => {
    return [
      `<div class='container'>
                <div class="row">
                    <h5 class='col'>Gestion ${index + 1}</h5>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">📅 FECHA:</div>
                    <div class="col p-0 ml-n3">${e.FECHA}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">📌 IDENTIFICADOR:</div>
                    <div class="col p-0 ml-n3">${e.IDENTIFICADOR}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">⚡ EFECTO:</div>
                    <div class="col p-0 ml-n3">${e.EFECTO}:</div>
                </div>



                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">⚡ MOTIVO:</div>
                    <div class="col p-0 ml-n3">${e.MOTIVO ? e.MOTIVO : ""}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">👀 OBSERVACION:</div>
                    <div class="col p-0 ml-n3">${e.OBSERVACION}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">🗺 DIRECCION:</div>
                    <div class="col p-0 ml-n3">${
                      e.DIRECCION_DEPURADA ? e.DIRECCION_DEPURADA : ""
                    }</div>
                </div>    
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">🙍‍♂️ NOMCONTACTO:</div>
                    <div class="col p-0 ml-n3">${e.NOMCONTACTO}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">🏠 PISOS:</div>
                    <div class="col p-0 ml-n3">${e.PISOS}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">🚪 PUERTA:</div>
                    <div class="col p-0 ml-n3">${e.PUERTA}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6 mx-0">💒 FACHADA:</div>
                    <div class="col p-0 ml-n3">${e.FACHADA}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6">📅 FECHA_PROMESA:</div>
                    <div class="col p-0 ml-n3">${e.FECHA_PROMESA}</div>
                </div>
                <div class="row mb-1 ml-n4">
                    <div class="col-6">💸 MONTO_PROMESA:</div>
                    <div class="col p-0 ml-n3">${e.MONTO_PROMESA}</div>
                </div>
            </div>
            `,
    ];
  });

  // Add multiple markers to map
  // var infoWindow = new google.maps.InfoWindow(), marker, i;
  var infoWindow = new google.maps.InfoWindow({
      maxWidth: 500,
    }),
    marker,
    i;

  // Place each marker on the map
  // var geocoder = new google.maps.Geocoder(); // NEW FOR DIRECTIONS

  for (i = 0; i < markers.length; i++) {
    /*****************SET ASIGNACION DIRECTIONS********************/
    // const currentDirection = asignacionDirections[i]
    // // Obtener la ubicación desde la dirección
    // geocoder.geocode({ 'address': currentDirection }, function (results, status) {
    //     if (status == 'OK' && results[0].geometry) {
    //         // Agregar marcador desde la dirección ingresada
    //         addMarker(results[0].geometry.location, 'Nuevo Marcador Asignacion', '<p>Dirección: ' + currentDirection + '</p>', '#000');
    //     } else {
    //         // alert('No se pudo encontrar la dirección: ' + status);
    //         console.log('No se pudo encontrar la dirección: ' + currentDirection, status);
    //     }
    // });

    /*************************************** */

    var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
    bounds.extend(position);
    marker = new google.maps.Marker({
      position: position,
      map: map,
      title: markers[i][0],
      icon: {
        path: google.maps.SymbolPath.CHEVRON_DOWN,
        scale: 2,
      },
    });

    // Add info window to marker
    google.maps.event.addListener(
      marker,
      "click",
      (function (marker, i) {
        return function () {
          infoWindow.setContent(infoWindowContent[i][0]);
          infoWindow.open(map, marker);
        };
      })(marker, i),
    );

    // Center the map to fit all markers on the screen
    map.fitBounds(bounds);
  }

  // Set zoom level
  var boundsListener = google.maps.event.addListener(
    map,
    "bounds_changed",
    function (event) {
      this.setZoom(7);
      google.maps.event.removeListener(boundsListener);
    },
  );

  /******************* SET DIRECCIONES DE ASIGNACION (NEW) *********************/
  // console.log(asignacionDirections);

  const currentMainMap = document.querySelector("#mapCanvas");
  currentMainMap.classList.remove("hide-html-element");

  /*********************************** MARKER DE DIRECCIONES ********************************/
  // Autocompletado de direcciones
  var input = document.getElementById("addressInput");
  var autocomplete = new google.maps.places.Autocomplete(input);

  // Evento al seleccionar una dirección del autocompletado
  autocomplete.addListener("place_changed", function () {
    var place = autocomplete.getPlace();
    if (place.geometry) {
      // Agregar marcador desde la dirección seleccionada
      addMarker(
        place.geometry.location,
        "Nuevo Marcador",
        "<p>Dirección: " + place.formatted_address + "</p>",
        "#000",
      );
    }
  });
}

function addMarkerFromAddress() {
  var input = document.getElementById("addressInput");
  var geocoder = new google.maps.Geocoder();

  // Obtener la ubicación desde la dirección
  geocoder.geocode({ address: input.value }, function (results, status) {
    if (status == "OK" && results[0].geometry) {
      // Agregar marcador desde la dirección ingresada
      addMarker(
        results[0].geometry.location,
        "Nuevo Marcador",
        "<p>Dirección: " + input.value + "</p>",
        "#000",
      );
    } else {
      alert("No se pudo encontrar la dirección: " + status);
    }
  });
}

function addMarker(position, title, content, color) {
  var marker = new google.maps.Marker({
    position: position,
    map: map,
    title: title,
    icon: {
      // path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,  // Puedes cambiar el tipo de marcador según tus preferencias
      // path: google.maps.SymbolPath.CHEVRON_DOWN,  // Puedes cambiar el tipo de marcador según tus preferencias
      path: google.maps.SymbolPath.BACKWARD_OPEN_ARROW, // Puedes cambiar el tipo de marcador según tus preferencias
      fillColor: color,
      fillOpacity: 1,
      strokeWeight: 0,
      scale: 7, // Ajusta el tamaño del marcador según tus preferencias
    },
  });

  // Agregar info window al marcador
  var infoWindow = new google.maps.InfoWindow({
    content: content,
  });

  marker.addListener("click", function () {
    infoWindow.open(map, marker);
  });

  // Ajustar los límites del mapa para incluir el nuevo marcador
  bounds.extend(position);
  map.fitBounds(bounds);
}
