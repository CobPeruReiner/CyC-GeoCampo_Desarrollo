<?php
// Current domain name / ip
$main_url = getenv('GEOCAMPO_BASE_URL') ?: 'https://geocampo.online';

// Obtén los parámetros necesarios
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

$id_tabla = isset($_GET['id_tabla']) ? $_GET['id_tabla'] : $_SESSION['id_tabla'];
$identificador = isset($_GET['identificador']) ? $_GET['identificador'] : '';


// Incluye el archivo de configuración para obtener la conexión a la base de datos
include('config.php');

// Verificar la conexión (esto no es necesario si config.php ya lo hace)
if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}
/******************************* CARTERA ****************************/
// Llamada al servicio web para obtener información de la base de datos
$url_servicio = $main_url . "/api.php?getAcciones&id_tabla=$id_tabla";

//$respuesta_servicio = file_get_contents($url_servicio);
//new
$respuesta_servicio = @file_get_contents($url_servicio);

if ($respuesta_servicio === false) {
    // Manejar el error, por ejemplo:
    die('Error al obtener los datos del servicio.');
}
// Eliminar espacios y caracteres no imprimibles al inicio de la cadena
//$respuesta_servicio = trim($respuesta_servicio);
// Eliminar BOM y espacios al inicio de la cadena
//$respuesta_servicio = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', utf8_encode($respuesta_servicio));
$respuesta_servicio = preg_replace('/^\xEF\xBB\xBF/', '', $respuesta_servicio);
// Imprimir la respuesta para depuración
//echo 'Respuesta del servicio: ' . $respuesta_servicio;
//end new
//$datos_servicio = json_decode($respuesta_servicio, true);
//new
$datos_servicio = json_decode($respuesta_servicio, true);

if ($datos_servicio !== null) {
    if ($datos_servicio['success']) {
        $acciones = $datos_servicio['acciones'];
        // Puedes procesar las acciones obtenidas aquí
    } else {
        // Manejar el caso en que la llamada al servicio web no sea exitosa
        $acciones = [];
    }
} else {
    // Manejar el caso en que la respuesta no sea un JSON válido
    die('Error al decodificar la respuesta JSON.');
}
//end new

// Manejar los resultados como sea necesario


//$efectos = [];
$efectos = [0, 'EFECTO'];

/*********************** DIRECCIONES ************************/
$dni = isset($_SESSION['dni']) ? $_SESSION['dni'] : '';
// Llamada al servicio web para obtener información de la base de datos
$url_servicio = $main_url . "/api.php?getDirecciones&id_tabla=$id_tabla&documento=$dni";
// echo '<script>';
// echo $dni;
// echo '</script>';
$respuesta_servicio = @file_get_contents($url_servicio);
$respuesta_servicio = preg_replace('/^\xEF\xBB\xBF/', '', $respuesta_servicio);
$datos_servicio = json_decode($respuesta_servicio, true);

// Manejar boolean con true en false dependiendo de si hay o no direcciones, para el required del select
$requiredAttribute = false;

// Manejar los resultados como sea necesario
if ($datos_servicio['success']) {
    $direcciones = $datos_servicio['direcciones'];
    if (count($direcciones) > 0) {
        // echo '<pre>';
        // print_r('Hay direcciones');
        // print_r($direcciones);
        // echo '</pre>';
        $requiredAttribute = true;
    } else {
        // echo '<pre>';
        // print_r('No hay direcciones');
        // print_r($direcciones);
        // echo '</pre>';
        $direcciones = [];
        // echo '<pre>';
        // print_r('Vaceando array');
        // print_r($direcciones);
        // echo '</pre>';
        $requiredAttribute = false;
    }
} else {
    // Manejar el caso en que la llamada al servicio web no sea exitosa
    $direcciones = [];
    echo '<pre>';
    print_r('Error');
    print_r($direcciones);
    echo '</pre>';
    $requiredAttribute = false;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Gestión</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="container">
        <h4>Agregar Gestión</h4>

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

            <input type="hidden" id="id_cartera" name="id_cartera">

            <div class="form-group">

                <!-- <select class="form-control" id="idaccion" name="idaccion" onchange="hideLabel(this);" required> -->
                <select class="form-control" id="idaccion" name="idaccion" onchange="cargarEfectos(this.value)"
                    required>
                    <option value="" disabled selected>ACCION</option>
                    <!-- <option value="205">HACER VISITA</option> -->
                    <?php foreach ($acciones as $accion): ?>
                        <option value="<?php echo $accion['IDACCION']; ?>"><?php echo $accion['ACCION']; ?></option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">

                <!-- <select class="form-control" id="idefecto" name="idefecto" required> -->
                <select class="form-control" id="idefecto" name="idefecto" required onchange="{
                    cargarMotivos(this.value)
                    cargarContactos(this.value)
                }">
                    <!-- <option id="idefecto_default" value="EFECTO" disabled selected>EFECTO</option> -->
                    <option value="" disabled selected>EFECTO</option>

                    <?php echo (int)$efectos[0]; ?>"><?php echo $efectos[1]; ?>

                </select>
            </div>

            <div class="form-group">

                <select class="form-control" id="idmotivo" name="idmotivo" onchange="hideLabel(this);">
                    <option id="idmotivo_default" value="" disabled selected>MOTIVO</option>


                </select>
            </div>

            <div class="form-group">

                <select class="form-control" id="idcontacto" name="idcontacto">
                    <option id="idcontacto_default" value=" " disabled selected>CONTACTO</option>

                </select>
            </div>

            <div class="form-group">
                <textarea class="form-control" id="observacion" name="observacion" rows="4"
                    placeholder="Observación"></textarea>
            </div>

            <div class="form-group">

                <select class="form-control" id="iddireccion" name="iddireccion" onchange="hideLabel(this);"
                    <?php if ($requiredAttribute) echo "required"; ?>>
                    <option value="" disabled selected>DIRECCION</option>
                    <?php foreach ($direcciones as $direccion): ?>
                        <option value="<?php echo $direccion['IDDIRECCION']; ?>">
                            <?php echo $direccion['DIRECCION_DEPURADA']; ?></option>
                    <?php endforeach; ?>

                </select>
            </div>


            <div class="form-group">
                <input type="text" class="form-control" id="nomcontacto" name="nomcontacto" placeholder="Num Contacto">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="pisos" name="pisos" placeholder="Pisos">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="puerta" name="puerta" placeholder="Puerta">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="fachada" name="fachada" placeholder="Fachada">
            </div>

            <div class="form-group">
                <label for="Fecha_promesa">Fecha Promesa:</label>
                <input type="date" class="form-control" id="fecha_promesa" name="fecha_promesa"
                    placeholder="Fecha Promesa">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="monto_promesa" name="monto_promesa"
                    placeholder="Monto Promesa">
            </div>

            <div class="form-group">
                <label for="hora_visita">Hora Visita: (Debe estar en una rango de 7:00 a.m. a 20:00 p.m.)</label>
                <input type="time" class="form-control" id="hora_visita" name="hora_visita" required min="07:00" max="20:00">
            </div>

            <div class="form-group">
                <label for="imagen1">Foto 1:</label>
                <input type="file" class="form-control" id="imagen1" name="imagen1" required>
            </div>

            <!-- <div class="form-group">
   		 <label for="imagen1">Foto 1 (obligatoria):</label>
   		 <input type="file" 
           		class="form-control" 
           		id="imagen1" 
           		name="imagen1" 
           		accept="image/*" 
           		capture="environment" 
           		required>
	   </div> -->


            <div class="form-group">
                <label for="imagen2">Foto 2:</label>
                <input type="file" class="form-control" id="imagen2" name="imagen2">
            </div>
            <div class="form-group">
                <label for="imagen3">Foto 3:</label>
                <input type="file" class="form-control" id="imagen3" name="imagen3">
            </div>

            <button type="submit" name='btnAddGestion' id='btn-add-gestion' class="btn btn-primary">Guardar Gestión</button>

        </form>
    </div>
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
                    console.log(latitud)
                    console.log(longitud)
                    var txt = "GPS ACTIVADO";

                    document.getElementById("latitud").value = latitud;
                    document.getElementById("longitud").value = longitud;
                    document.getElementById("txt").value = txt;

                    // Get id_cartera
                    const urlParams = new URLSearchParams(window.location.search);
                    const idCartera = urlParams.get('id_cartera');
                    document.getElementById("id_cartera").value = idCartera;
                },
                function(error) {
                    // Manejar el error aquí
                    console.error("Error al obtener la ubicación:", error.message);
                    if (error.code === error.PERMISSION_DENIED) {
                        alert("Ubicación no activada, activarla y refrescar la página");
                    }
                });
        } else {
            // Navegador no compatible con geolocalización
            alert('Geolocalización no compatible con este navegador')
            console.error("Geolocalización no compatible con este navegador.");
        }

        /* DYNAMIC EFECTOS */
        // Función para cargar dinámicamente los efectos basados en la acción seleccionada
        function cargarEfectos(idaccion) {
            // Llamada al servicio web para obtener información de la tabla "efecto" basada en el idaccion seleccionado
            var urlServicioEfectos = main_url + "/api.php?getEfectos&idaccion=" + idaccion;

            fetch(urlServicioEfectos)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Generar dinámicamente las opciones del segundo <select> con JavaScript
                        var efectosSelect = document.getElementById("idefecto");
                        // while (efectosSelect.options && efectosSelect.options.length > 1) {
                        //     efectosSelect.remove(1);
                        // }
                        //efectosSelect.innerHTML = '<option value="">EFECTO</option>';
                        efectosSelect.innerHTML = '';

                        data.efectos.forEach(function(efecto) {
                            var option = document.createElement("option");
                            option.value = efecto.IDEFECTO;
                            option.textContent = efecto.EFECTO;
                            option.dataset.promesa = efecto.promesa;
                            efectosSelect.appendChild(option);
                        });

                        // Agregar un listener para guardar la promesa cuando se seleccione un efecto
                        efectosSelect.addEventListener("change", function(event) {
                            var selectedOption = event.target.options[event.target.selectedIndex];
                            var promesa = selectedOption.dataset.promesa; // Obtener el valor de "promesa"

                            if (promesa) {
                                console.log("Promesa seleccionada:", promesa);

                                document.getElementById("promesa-efecto").value = promesa;

                                var montoInput = document.getElementById("monto_promesa");
                                var fechaInput = document.getElementById("fecha_promesa");

                                if (promesa == 1) {
                                    montoInput.setAttribute('required', '');
                                    fechaInput.setAttribute('required', '');

                                    montoInput.disabled = false;
                                    fechaInput.disabled = false;

                                    console.log("Campos son obligatorios");

                                    console.log("montoInput.required:", montoInput.required);
                                    console.log("fechaInput.required:", fechaInput.required);
                                } else {
                                    montoInput.removeAttribute('required');
                                    fechaInput.removeAttribute('required');
                                    montoInput.disabled = true;
                                    fechaInput.disabled = true;

                                    console.log("montoInput.required:", montoInput.required);
                                    console.log("fechaInput.required:", fechaInput.required);
                                    console.log("Campos ya no son obligatorios");
                                }
                            }
                        });

                        //document.querySelector('#idefecto option[value=""]').style.display = 'none';
                    } else {
                        console.error("Error al obtener efectos:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error de red al obtener efectos:", error);
                });
        }

        function cargarMotivos(idefecto) {
            // Llamada al servicio web para obtener información de la tabla "motivo" basada en el idefecto seleccionado

            var urlServicioMotivos = main_url + "/api.php?getMotivos&idefecto=" + idefecto;

            fetch(urlServicioMotivos)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // console.log('motivos: ', data.motivos)
                        // Actualizar dinámicamente el tercer select con los motivos correspondientes
                        var motivosSelect = document.getElementById("idmotivo");
                        while (motivosSelect.options && motivosSelect.options.length > 1) {
                            motivosSelect.remove(1);
                        }
                        //motivosSelect.innerHTML = ''; // Limpiar opciones existentes

                        if (data.motivos && data.motivos.length > 0) {
                            data.motivos.forEach(function(motivo) {
                                var option = document.createElement("option");
                                //option.value = "";
                                option.value = motivo.IDMOTIVO;
                                option.textContent = motivo.MOTIVO;
                                motivosSelect.appendChild(option);
                            });
                            motivosSelect.setAttribute('required', '');
                            //document.querySelector('#idmotivo_default').style.display = 'none';
                        } else {
                            var option = document.createElement("option");
                            //option.value = "MOTIVO";
                            option.value = "";
                            option.textContent = "MOTIVO";
                            option.disabled = true;
                            // Asegurarse de que la opción "MOTIVO" no se duplique
                            //var existingOption = motivosSelect.querySelector('option[value="MOTIVO"]');
                            var existingOption = motivosSelect.querySelector('#idmotivo_default');
                            if (!existingOption) {
                                motivosSelect.appendChild(option);
                            }
                            // motivosSelect.setAttribute('required', false);
                            motivosSelect.removeAttribute('required');
                        }


                        // Ocultar la opción inicial "MOTIVO"
                        //document.querySelector('#idmotivo option[value="MOTIVO"]').style.display = 'none';
                    } else {
                        console.error("Error al obtener motivos:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error de red al obtener motivos:", error);
                });
        }

        function cargarContactos(idefecto) {
            // Llamada al servicio web para obtener información de la tabla "contacto" basada en el idefecto seleccionado
            var urlServicioContactos = main_url + "/api.php?getContactos&idefecto=" + idefecto;

            fetch(urlServicioContactos)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // console.log('contactos: ', data.contactos)
                        // Actualizar dinámicamente el cuarto select con los contactos correspondientes
                        var contactosSelect = document.getElementById("idcontacto");
                        while (contactosSelect.options && contactosSelect.options.length > 1) {
                            contactosSelect.remove(1);
                        }
                        //contactosSelect.innerHTML = ''; // Limpiar opciones existentes
                        if (data.contactos && data.contactos.length > 0) {
                            data.contactos.forEach(function(contacto) {
                                var option = document.createElement("option");
                                option.value = contacto.IDCONTACTO;
                                option.textContent = contacto.CONTACTO;
                                contactosSelect.appendChild(option);
                            });
                            contactosSelect.setAttribute('required', true);
                            // document.querySelector('#idcontacto option[value="CONTACTO"]').style.display = 'none';
                        } else {
                            //document.querySelector('#idcontacto option[value="CONTACTO"]').style.display = 'block';
                            var option = document.createElement("option");
                            option.value = " ";
                            option.textContent = "CONTACTO";
                            option.disabled = true;
                            // Asegurarse de que la opción "CONTACTO" no se duplique
                            //var existingOption = contactosSelect.querySelector('option[value="CONTACTO"]');
                            var existingOption = contactosSelect.querySelector('#idcontacto_default');
                            if (!existingOption) {
                                contactosSelect.appendChild(option);
                            }
                            contactosSelect.setAttribute('required', false);
                        }

                        // Ocultar la opción inicial "CONTACTO"
                        //document.querySelector('#idcontacto option[value="CONTACTO"]').style.display = 'none';
                    } else {
                        console.error("Error al obtener contactos:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error de red al obtener contactos:", error);
                });
        }

        // const megasAllowed = 4

        // function handleImageUpload(inputElement) {
        //     inputElement.onchange = function() {
        //         if (this.files[0].size > megasAllowed * 1048576) {
        //             alert(`Error, archivo mayor a ${megasAllowed}MB`);
        //             this.value = "";
        //         }
        //     };
        // }

        var imagen1 = document.getElementById("imagen1");
        var imagen2 = document.getElementById("imagen2");
        var imagen3 = document.getElementById("imagen3");

        // handleImageUpload(imagen1);
        // handleImageUpload(imagen2);
        // handleImageUpload(imagen3);



        // Lógica para manejar el envío del formulario
        // function manejarEnvioFormulario(image_file, endpoint) {
        //     let formData = new FormData();
        //     formData.append("imagen_comprimida", image_file);

        //     // Enviar el formulario con FormData
        //     fetch(endpoint, {
        //             method: 'POST',
        //             body: formData
        //         })
        //         .then(response => {
        //             if (response.ok) {
        //                 console.log('Formulario enviado correctamente');
        //             } else {
        //                 console.error('Error al enviar formulario');
        //             }
        //         })
        //         .catch(error => {
        //             console.error('Error en la solicitud:', error);
        //         });
        // }
    </script>
    <!-- Agregar FontAwesome y Bootstrap JavaScript (si es necesario) -->
    <script src="resize.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>