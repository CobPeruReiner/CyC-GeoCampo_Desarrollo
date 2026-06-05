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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria Asesor</title>
    <link rel="stylesheet" href="getGaleriaAsesor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body>
    <div class="container">
        <h3 class='text-center p-3 mb-4 bg-secondary text-white mt-3'>GALERIA DE FOTOS ASESOR</h3>
        <div class="form-row d-flex justify-content-between mb-5">
            <div class="form-group d-flex align-items-center">
                <label class="me-2">Fecha: </label>
                <input type='date' disabled class="form-control me-2 border-0 text-center ruta_asesor_datepicker" id='galeria-datepicker1'>
                <input type='date' disabled class="form-control me-2 border-0 text-center ruta_asesor_datepicker" id='galeria-datepicker2'>
            </div>
            <div class="form-group d-flex align-items-center">
                <label for="Cartera" class="me-2">Cartera: </label>
                <input type="text" class="form-control border-0" name="Cartera" id="galeria-cartera" disabled>
            </div>
            <div class="form-group col-5 d-flex align-items-center">
                <label for="asesor" class="me-2">Asesor: </label>
                <input type="text" class="form-control border-0" name="asesor" id="galeria-asesor" disabled>
            </div>
        </div>

        <main>
            <div class="album py-5 bg-light">
                <div class="container">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-5 g-3" id='galery-asesor-container'></div>
                    <div id="pagination-controls" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </main>

        <div class="d-flex justify-content-center my-3">
            <button onclick="closeGaleria()" class="btn btn-secondary">Cerrar Ventana</button>
        </div>
    </div>

    <!-- MODAL DE NUEVO INICIO DE SESION -->
    <?php include 'MSsesionExpirada.html'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const galeryContainer = document.getElementById('galery-asesor-container');

            const idPersonal = urlParams.get('idPersonal');
            const date1 = urlParams.get('date1');
            const date2 = urlParams.get('date2');
            const currentIdCartera = urlParams.get('currentIdCartera');
            const currentAsesor = urlParams.get('asesor');
            const currentCartera = urlParams.get('cartera');
            const carteraFile = urlParams.get('carteraFile');

            const datePicker1 = document.getElementById('galeria-datepicker1');
            const datePicker2 = document.getElementById('galeria-datepicker2');
            const cartera = document.getElementById('galeria-cartera');
            const asesor = document.getElementById('galeria-asesor');

            datePicker1.setAttribute('value', date1);
            datePicker2.setAttribute('value', date2);
            cartera.setAttribute('value', currentCartera);
            asesor.setAttribute('value', currentAsesor);

            const allImages = [];
            let currentPage = 1;
            const imagesPerPage = 10;

            function handleSortByDate(a, b) {
                const [d1, t1] = a.FECHA.split(' ');
                const [d2, t2] = b.FECHA.split(' ');
                const [day1, month1, year1] = d1.split('/');
                const [day2, month2, year2] = d2.split('/');
                const dateA = new Date(`${year1}-${month1}-${day1} ${t1}`);
                const dateB = new Date(`${year2}-${month2}-${day2} ${t2}`);
                return dateB - dateA;
            }

            function renderPage(page) {
                galeryContainer.innerHTML = '';
                const start = (page - 1) * imagesPerPage;
                const end = start + imagesPerPage;
                const imagesToShow = allImages.slice(start, end);

                imagesToShow.forEach(imgData => {
                    const newDiv = document.createElement('div');
                    newDiv.classList.add('col');

                    const img = new Image();
                    img.onload = function() {
                        newDiv.innerHTML = `
                        <div class="card shadow-sm">
                            <div class="card-image">
                                <a href="${imgData.src}" data-fancybox="gallery" data-caption="${imgData.fecha}">
                                    <img src="${imgData.src}" alt="Image Gallery">
                                </a>
                            </div>
                            <div class="card-body p-1">
                                <p class="card-text text-center">${imgData.fecha}</p>
                            </div>
                        </div>`;
                        galeryContainer.appendChild(newDiv);
                    };
                    img.onerror = function() {
                        console.error(`La imagen ${imgData.name} no se pudo cargar`);
                    };
                    img.src = imgData.src;
                });

                renderPaginationControls();
            }

            function renderPaginationControls() {
                const totalPages = Math.ceil(allImages.length / imagesPerPage);
                const container = document.getElementById('pagination-controls');
                container.innerHTML = '';

                if (totalPages <= 1) return;

                const prevBtn = document.createElement('button');
                prevBtn.textContent = 'Anterior';
                prevBtn.classList.add('btn', 'btn-outline-secondary', 'me-2');
                prevBtn.disabled = currentPage === 1;
                prevBtn.onclick = () => {
                    currentPage--;
                    renderPage(currentPage);
                };

                const nextBtn = document.createElement('button');
                nextBtn.textContent = 'Siguiente';
                nextBtn.classList.add('btn', 'btn-outline-secondary');
                nextBtn.disabled = currentPage === totalPages;
                nextBtn.onclick = () => {
                    currentPage++;
                    renderPage(currentPage);
                };

                const pageIndicator = document.createElement('span');
                pageIndicator.classList.add('mx-3', 'align-self-center');
                pageIndicator.textContent = `Página ${currentPage} de ${totalPages}`;

                container.appendChild(prevBtn);
                container.appendChild(pageIndicator);
                container.appendChild(nextBtn);
            }

            fetch(`get_gestiones_asesor_2.php?idPersonal=${idPersonal}&fecha=${date1}&fecha2=${date2}&idCartera=${currentIdCartera}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.result) return;

                    const carteraDirectory = carteraFile.slice(2);
                    const currentDirectory = `./fotos/${carteraDirectory}`;
                    const sortedData = data.result.sort(handleSortByDate);

                    const imagePromises = sortedData.flatMap(e => {
                        const promises = [];

                        for (let i = 1; i < 4; i++) {

                            const imgName = e[`imagen${i}`];
                            if (!imgName) continue;

                            let fechaCarpeta = '';
                            try {
                                const [d, t] = e.FECHA.split(' ');
                                const [day, month, year] = d.split('/');
                                fechaCarpeta = `${year}-${month}-${day}`;
                            } catch (err) {}

                            const imgPathFecha = `${currentDirectory}/${fechaCarpeta}/${imgName}`;
                            const imgPathRaiz = `${currentDirectory}/${imgName}`;

                            const promise = fetch(imgPathFecha, {
                                    method: 'HEAD'
                                })
                                .then(res => {

                                    if (res.ok) {

                                        allImages.push({
                                            src: imgPathFecha,
                                            fecha: e.FECHA,
                                            name: imgName
                                        });

                                    } else {

                                        return fetch(imgPathRaiz, {
                                                method: 'HEAD'
                                            })
                                            .then(res2 => {
                                                if (res2.ok) {
                                                    allImages.push({
                                                        src: imgPathRaiz,
                                                        fecha: e.FECHA,
                                                        name: imgName
                                                    });
                                                }
                                            });

                                    }

                                })
                                .catch(() => {
                                    console.warn(`Error al verificar imagen: ${imgName}`);
                                });

                            promises.push(promise);
                        }

                        return promises;
                    });

                    Promise.all(imagePromises).then(() => {
                        renderPage(currentPage);
                    });
                });


            window.closeGaleria = () => {
                window.close();
            };
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="custom.js"></script>

    <!-- VERIFICAR TOKEN -->
    <script src="verificarToken.js"></script>
</body>

</html>