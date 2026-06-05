<?php
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruta asesor</title>
    <link rel="stylesheet" href="rutaAsesor.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.6.0/js/bootstrap.min.js" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>

<body>
    <div class="container">
        <h3 class='ruta_asesor_title'>GESTIONES ASESOR</h3>
        <div class="d-flex justify-content-center mb-2">
            <div class="col-8">
                <input onchange='searchAsesorGestiones(this)' type='date' class="form-control ruta_asesor_datepicker">
            </div>
        </div>
        <!-- <input id="addressInput" type="text" placeholder="Buscar dirección...">
        <button onclick="addMarkerFromAddress()">Agregar Marcador</button> -->


        <!-- Incluir el mapa -->
        <div id="mapCanvas"></div>

        <div id="tabla-gestiones-asesor2"></div>
        <!-- Formulario para ingresar el DNI -->
        <div class="d-flex justify-content-center my-3">
            <a href="menu.php" class="btn btn-secondary">Volver al Menú</a>
        </div>
    </div>

    <!-- Incluir el modal -->
    <div id="modalContainer"></div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>

    <!-- Agregar FontAwesome y Bootstrap JavaScript (si es necesario) -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>

    <script src="ruta_asesor.js"></script>

    <!-- CURRENT API KEY -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGFBIf_6mCinwpqWw2Q-lHwNmK6u2iMhE&callback=showMap">
    </script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCGFBIf_6mCinwpqWw2Q-lHwNmK6u2iMhE&libraries=places&callback=initMap">
    </script>
    <!-- END CURRENT APU KEY -->

    <!--  TRAER LAS GESTIONES DEL ASESOR  -->
    <script>
        // Manejar la solicitud de búsqueda de cuentas
        const idPersonal = <?php echo $_SESSION['id']; ?>;

        function mostrarGestiones(date) {
            // Obteniendo fecha actual por defecto o por filtro de datepicker
            // Formateando la fecha al renderizar por primera vez la pagina
            if (!date) {
                date = new Date();
                // Obtiene los componentes de la fecha
                var year = date.getFullYear();
                var month = formatNumber(date.getMonth() + 1); // Se suma 1 porque los meses van de 0 a 11
                var day = formatNumber(date.getDate());

                // Formatea la fecha en el formato deseado "YYYY-mm-dd"
                var formattedDate = `${year}-${month}-${day}`;
                date = formattedDate
            }

            // Función auxiliar para formatear la fecha
            function formatNumber(number) {
                return number.toString().padStart(2, '0');
            }

            /****** PROCEDURE ******/
            // Llamar al procedimiento almacenado "GetAsignacion" usando Fetch API
            fetch(`get_gestiones_asesor.php?idPersonal=${idPersonal}&fecha=${date}`)
                .then(response => response.json())
                .then(data => {
                    // console.log(data)
                    // Mostrar la tabla de gestiones
                    const tablaGestiones = document.getElementById('tabla-gestiones-asesor2');
                    tablaGestiones.innerHTML = data.html;

                    // Acceder al array result
                    const resultArray = data.result;
                    // console.log(resultArray);
                    if (resultArray && resultArray.length) {
                        initMap(resultArray)
                    } else {
                        const currentMainMap = document.querySelector("#mapCanvas")
                        currentMainMap.classList.add('hide-html-element')
                    }

                    // Agregar la lógica de modal e info a los botones
                    let botones = document.querySelectorAll('.btn-test');
                    // Agrega el evento clic a cada botón
                    botones.forEach(function(boton) {
                        boton.addEventListener('click', function() {
                            let rowData = JSON.parse(boton.getAttribute('data-row'));

                            // Muestra la información en la consola
                            fetch('get_gestiones_detalle_asesor.php', {
                                    "method": 'POST',
                                    "headers": {
                                        'Content-Type': 'application/json; charset=utf-8'
                                    },
                                    "body": JSON.stringify(rowData)
                                })
                                .then(response => response.json())
                                .then(data => {
                                    // modal
                                    const modalContainer = document.getElementById(
                                        'modalContainer');
                                    modalContainer.innerHTML = data.html

                                    // hide map
                                    const currentMap = document.querySelector("#map")
                                    currentMap.classList.add('hide-html-element')

                                    $('#modalData').modal('toggle');

                                })

                        });
                    })

                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        const searchAsesorGestiones = e => {
            // console.log(e.value)
            // console.log(new Date)
            mostrarGestiones(e.value)

        }

        mostrarGestiones()
    </script>

    <!-- VERIFICAR TOKEN -->
    <script src="verificarToken.js"></script>
</body>

</html>