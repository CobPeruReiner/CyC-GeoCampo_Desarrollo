<!-- <?php include 'get_gestiones_detalle_asesor.php'; ?> -->

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['row'])) {
$row = json_decode($_POST['row'], true);
echo $row;

echo '<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">';
    echo ' <div class="modal-dialog modal-dialog-centered" role="document">';
        echo ' <div class="modal-content">';
            echo ' <div class="modal-header">';
                echo ' <h5 class="modal-title" id="exampleModalLongTitle">Detalle de la gestión</h5>';
                echo ' <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    echo ' <span aria-hidden="true">&times;</span>';
                    echo ' </button>';
                echo ' </div>';
            echo ' <div class="modal-body">';
                echo ' <ul class="list-group">';
                    echo ' <li class="list-group-item">' . $row['FECHA'] . '</li>';
                    echo ' <li class="list-group-item">' . $row['IDENTIFICADOR'] . '</li>';
                    echo ' <li class="list-group-item">' . $row['IDEFECTO'] . '</li>';
                    echo ' <li class="list-group-item">' . $row['IDMOTIVO'] . '</li>';
                    echo ' <li class="list-group-item">' . $row['OBSERVACION'] . '</li>';
                    echo ' </ul>';
                echo ' </div>';
            echo ' <div class="modal-footer">';
                echo ' <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>';
                echo ' <button type="button" class="btn btn-primary">Ver mapa</button>';
                echo ' </div>';
            echo ' </div>';
        echo ' </div>';
    echo '</div>';
}else {
echo '<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">';
    echo ' <div class="modal-dialog modal-dialog-centered" role="document">';
        echo ' <div class="modal-content">';
            echo ' <div class="modal-header">';
                echo ' <h5 class="modal-title" id="exampleModalLongTitle">Detalle de la gestión</h5>';
                echo ' <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    echo ' <span aria-hidden="true">&times;</span>';
                    echo ' </button>';
                echo ' </div>';
            echo ' <div class="modal-body">';
                echo ' <p> No hay datos </p>';
                echo ' </div>';
            echo ' <div class="modal-footer">';
                echo ' <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>';
                echo ' <button type="button" class="btn btn-primary">Ver mapa</button>';
                echo ' </div>';
            echo ' </div>';
        echo ' </div>';
    echo '</div>';
}