<?php
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


        <form id="agregar-gestion-form" action="guardargestion.php?id_tabla=<?php echo $id_tabla; ?>&identificador=<?php echo $identificador; ?>" method="post" enctype="multipart/form-data">

            <input type="hidden" id="latitud" name="latitud">
            <input type="hidden" id="longitud" name="longitud">
            <input type="hidden" id="txt" name="txt">


            <div class="form-group">

                <select class="form-control" id="idaccion" name="idaccion" onchange="hideLabel(this);" required>
                    <option value="" disabled selected>ACCION</option>
                    <option value="583">VISITA A TITULAR EN DOMICILIO</option>


                </select>
            </div>

            <div class="form-group">

                <select class="form-control" id="idefecto" name="idefecto" required>
                    <option value="" disabled selected>EFECTO</option>
                    <!-- Opciones para idaccion 205 -->
                    <option value="9422" data-idaccion="205">CAMBIÓ DE DOMICILIO</option>
                    <option value="9444" data-idaccion="205">CLIENTE O CONTACTO CORTA LA LLAMADA</option>
                    <option value="9439" data-idaccion="205">CLIENTE O CONTACTO INDICA HABER PAGADO</option>
                    <option value="9423" data-idaccion="205">CLIENTE O CONTACTO PROMETE VISITA</option>
                    <option value="9425" data-idaccion="205">CLIENTE O CONTACTO SOLICITA CONDONACIÓN</option>
                    <option value="9426" data-idaccion="205">CLIENTE O CONTACTO SOLICITA PLAZO</option>
                    <option value="9424" data-idaccion="205">CLIENTE SOLICITA DACIÓN DE PAGO</option>
                    <option value="9440" data-idaccion="205">COMPROMISO DE PAGO</option>
                    <option value="9420" data-idaccion="205">DIRECCIÓN NO EXISTE</option>
                    <option value="9448" data-idaccion="205">ENFERMEDAD DE CTE/TERCEROS</option>
                    <option value="9449" data-idaccion="205">FALLECIMIENTO DEL TITULAR</option>
                    <option value="9452" data-idaccion="205">INDICA NO RECONOCER EL SALDO</option>
                    <option value="9436" data-idaccion="205">INUBICABLE TOTAL</option>
                    <option value="9435" data-idaccion="205">IRRECUPERABLE</option>
                    <option value="9450" data-idaccion="205">IVR - NO RESPONDE LLAMADA</option>
                    <option value="9431" data-idaccion="205">MENSAJE A FAMILIAR /TERCERO</option>
                    <option value="9430" data-idaccion="205">MENSAJE CON AVAL</option>
                    <option value="9433" data-idaccion="205">MENSAJE EN GRABADORA O BUZÓN</option>
                    <option value="9442" data-idaccion="205">NEGOCIO EXISTE (INACTIVO)</option>
                    <option value="9451" data-idaccion="205">NO CONTESTA</option>
                    <option value="9454" data-idaccion="205">NO SE ENCUENTRA A NADIE EN DOMICILIO</option>
                    <option value="9446" data-idaccion="205">NÚMERO FUERA DE SERVICIO</option>
                    <option value="9421" data-idaccion="205">NÚMERO NO CORRESPONDE</option>
                    <option value="9453" data-idaccion="205">PARCIALMENTE AFECTADO POR COVID 19</option>
                    <option value="9447" data-idaccion="205">PROBLEMAS DE UBICACIÓN EN CASA</option>
                    <option value="9445" data-idaccion="205">RECLAMO</option>
                    <option value="9432" data-idaccion="205">RECORDATORIO DE PAGO</option>
                    <option value="9434" data-idaccion="205">SE DEJÓ CARTA BAJO LA PUERTA</option>
                    <option value="9429" data-idaccion="205">SE ENTREGÓ NOTIFICACIÓN A TITULAR / AVAL</option>
                    <option value="9437" data-idaccion="205">SE NIEGA A PAGAR</option>
                    <option value="9438" data-idaccion="205">SE RECAUDÓ EN CAMPO</option>
                    <option value="9428" data-idaccion="205">SOLICITA REFINANCIAMIENTO</option>
                    <option value="9427" data-idaccion="205">SOLICITUD DE REPROGRAMACIÓN</option>
                    <option value="9443" data-idaccion="205">TITULAR DESEMPLEADO</option>
                    <option value="9441" data-idaccion="205">TRÁMITE DE SEGURO</option>
                </select>
            </div>

            <div class="form-group">

                <select class="form-control" id="idmotivo" name="idmotivo" onchange="hideLabel(this);" required>
                    <option value="" disabled selected>MOTIVO DE NO PAGO</option>
                    <option value="0" data-idaccion="9454">ACCIDENTE</option>
                    <option value="0" data-idaccion="9454">ACCIDENTE DE TERCEROS</option>
                    <option value="0" data-idaccion="9454">AGRICOLA _BAJA PRODUCCION</option>
                    <option value="0" data-idaccion="9454">AGRICOLA _PLAGA</option>
                    <option value="0" data-idaccion="9454">BAJA EN PRODUCCION - REDUCCION DE VENTAS</option>
                    <option value="0" data-idaccion="9454">CAMBIO DE GIRO</option>
                    <option value="0" data-idaccion="9454">CLIENTE FUERA DE LA CIUDAD</option>
                    <option value="0" data-idaccion="9454">CREDITO PARA TERCEROS</option>
                    <option value="0" data-idaccion="9454">DEFICIENTE EVALUACION</option>
                    <option value="0" data-idaccion="9454">DESTINO DIFERENTE AL SOLICITADO</option>
                    <option value="0" data-idaccion="9454">ENFERMEDAD</option>
                    <option value="0" data-idaccion="9454">ENFERMEDAD DE TERCEROS</option>
                    <option value="0" data-idaccion="9454">ESTAFA - REDUCCION DE VENTA</option>
                    <option value="0" data-idaccion="9454">FALLECIMIENTO DEL TITULAR</option>
                    <option value="0" data-idaccion="9454">FALTA VOLUNTAD DE PAGO</option>
                    <option value="0" data-idaccion="9454">INGRESO DE COMPETENCIA - REDUCCION DE VENTA</option>
                    <option value="0" data-idaccion="9454">LICENCIA / SUSPENSION / SALIDA DE CENTRO LABORAL</option>
                    <option value="0" data-idaccion="9454">NO LE PAGAN SUELDO</option>
                    <option value="0" data-idaccion="9454">POSIBLE FRAUDE</option>
                    <option value="0" data-idaccion="9454">POSIBLE FUGA</option>
                    <option value="0" data-idaccion="9454">PRESO</option>
                    <option value="0" data-idaccion="9454">PROBLEMAS CONYUGALES</option>
                    <option value="0" data-idaccion="9454">PROBLEMAS CUENTAS POR COBRAR</option>
                    <option value="0" data-idaccion="9454">PROBLEMAS JUDICIALES / PENALES / TRIBUTARIOS</option>
                    <option value="0" data-idaccion="9454">ROBO - REDUCCION DE VENTA</option>
                    <option value="0" data-idaccion="9454">SOBREENDEUDAMIENTO</option>
                    <option value="0" data-idaccion="9454">YA NO TIENE NEGOCIO</option>

                </select>
            </div>

            <div class="form-group">

                <select class="form-control" id="idcontacto" name="idcontacto" onchange="hideLabel(this);" required>
                    <option value="" disabled selected>CONTACTO</option>
                    <option value="1" data-idmotivo="15522">TITULAR</option>
                    <option value="2" data-idmotivo="15525">TERCERO</option>
                    <option value="3" data-idmotivo="15528">OTRO</option>


                </select>
            </div>

            <div class="form-group">
                <textarea class="form-control" id="observacion" name="observacion" rows="4" placeholder="Observación"></textarea>
            </div>

            <div class="form-group">

                <select class="form-control" id="iddireccion" name="iddireccion" onchange="hideLabel(this);" required>
                    <option value="" disabled selected>DIRECCION</option>
                    <option value="1">DIRECCION DE ASIGNACION</option>
                    <option value="2">DIRECCION DE BUSQUEDA</option>
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
                <input type="date" class="form-control" id="fecha_promesa" name="fecha_promesa" placeholder="Fecha Promesa">
            </div>

            <div class="form-group">
                <input type="text" class="form-control" id="monto_promesa" name="monto_promesa" placeholder="Monto Promesa">
            </div>

            <div class="form-group">
                <label for="imagen1">Foto 1:</label>
                <input type="file" class="form-control" id="imagen1" name="imagen1">
            </div>

            <div class="form-group">
                <label for="imagen2">Foto 2:</label>
                <input type="file" class="form-control" id="imagen2" name="imagen2">
            </div>
            <div class="form-group">
                <label for="imagen3">Foto 3:</label>
                <input type="file" class="form-control" id="imagen3" name="imagen3">
            </div>
            <!-- Otros campos y controles aquí -->
            <button type="submit" class="btn btn-primary">Guardar Gestión</button>

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
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var latitud = position.coords.latitude;
                var longitud = position.coords.longitude;
                var txt = "1111";

                document.getElementById("latitud").value = latitud;
                document.getElementById("longitud").value = longitud;
                document.getElementById("txt").value = txt;
            });
        }
    </script>






    <!-- Agregar FontAwesome y Bootstrap JavaScript (si es necesario) -->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>