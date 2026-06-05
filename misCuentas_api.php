<?php

/**
 * API GEOCAMPO - Mis Cuentas / Hoja de Ruta del asesor
 */
date_default_timezone_set('America/Lima');
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/config.php';
$mysqli->set_charset('utf8');

function responder_json($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function limpiar_texto($valor): string
{
    return trim((string)($valor ?? ''));
}

function fecha_hoy_peru(): string
{
    return date('Y-m-d');
}

function validar_fecha_ruta_diaria(string $fechaRuta): string
{
    // Se mantiene el nombre de la función para no romper llamadas existentes.
    // Ahora la fecha es una referencia dentro de la semana de trabajo, no solo "hoy".
    $fechaRuta = limpiar_texto($fechaRuta) ?: fecha_hoy_peru();
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRuta)) {
        throw new Exception('Fecha de ruta inválida.');
    }
    return $fechaRuta;
}

function obtener_rango_semana(string $fechaReferencia): array
{
    $fechaReferencia = validar_fecha_ruta_diaria($fechaReferencia);
    $dt = new DateTime($fechaReferencia);
    $inicio = clone $dt;
    $inicio->modify('monday this week');
    $fin = clone $inicio;
    $fin->modify('+6 days');

    return [
        'fecha_referencia' => $dt->format('Y-m-d'),
        'fecha_inicio_semana' => $inicio->format('Y-m-d'),
        'fecha_fin_semana' => $fin->format('Y-m-d'),
    ];
}

function obtener_periodo_por_fecha(mysqli $mysqli, string $fechaReferencia): ?array
{
    $rows = query_all($mysqli, "
        SELECT id_periodo, codigo, nombre, fecha_inicio, fecha_fin
        FROM geocampo_periodo
        WHERE activo = 1
          AND ? BETWEEN fecha_inicio AND fecha_fin
        ORDER BY fecha_inicio DESC
        LIMIT 1
    ", 's', [$fechaReferencia]);

    return $rows ? $rows[0] : null;
}

function distancia_haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $radioTierra = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $radioTierra * $c;
}

function ordenar_por_proximidad(array $cuentas): array
{
    $conCoordenadas = [];
    $sinCoordenadas = [];

    foreach ($cuentas as $cuenta) {
        $lat = (float)($cuenta['latitud'] ?? 0);
        $lng = (float)($cuenta['longitud'] ?? 0);
        if ($lat !== 0.0 && $lng !== 0.0) {
            $conCoordenadas[] = $cuenta;
        } else {
            $sinCoordenadas[] = $cuenta;
        }
    }

    if (count($conCoordenadas) <= 1) {
        return array_merge($conCoordenadas, $sinCoordenadas);
    }

    usort($conCoordenadas, function ($a, $b) {
        return [limpiar_texto($a['distrito']), limpiar_texto($a['ubigeo']), (int)$a['id']] <=> [limpiar_texto($b['distrito']), limpiar_texto($b['ubigeo']), (int)$b['id']];
    });

    $ordenadas = [];
    $actual = array_shift($conCoordenadas);
    $ordenadas[] = $actual;

    while ($conCoordenadas) {
        $latActual = (float)($actual['latitud'] ?? 0);
        $lngActual = (float)($actual['longitud'] ?? 0);
        $mejorIndice = 0;
        $mejorDistancia = null;

        foreach ($conCoordenadas as $indice => $candidata) {
            $distancia = distancia_haversine_km($latActual, $lngActual, (float)$candidata['latitud'], (float)$candidata['longitud']);
            if ($mejorDistancia === null || $distancia < $mejorDistancia) {
                $mejorDistancia = $distancia;
                $mejorIndice = $indice;
            }
        }

        $actual = $conCoordenadas[$mejorIndice];
        $ordenadas[] = $actual;
        array_splice($conCoordenadas, $mejorIndice, 1);
    }

    usort($sinCoordenadas, function ($a, $b) {
        return [limpiar_texto($a['distrito']), limpiar_texto($a['ubigeo']), (int)$a['id']] <=> [limpiar_texto($b['distrito']), limpiar_texto($b['ubigeo']), (int)$b['id']];
    });

    return array_merge($ordenadas, $sinCoordenadas);
}

function query_all(mysqli $mysqli, string $sql, string $types = '', array $params = []): array
{
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $mysqli->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function obtener_id_usuario(): int
{
    $id = (int)($_GET['id_usuario'] ?? $_POST['id_usuario'] ?? 0);
    if ($id <= 0) {
        $id = (int)($_SESSION['id_mis_cuentas'] ?? $_SESSION['id'] ?? 0);
    }
    if ($id <= 0) {
        throw new Exception('No se recibió un asesor válido.');
    }
    return $id;
}

function obtener_id_catalogo(mysqli $mysqli, string $tabla, string $campoId, string $codigo): int
{
    $sql = "SELECT {$campoId} AS id FROM {$tabla} WHERE codigo = ? AND activo = 1 LIMIT 1";
    $rows = query_all($mysqli, $sql, 's', [$codigo]);
    if (!$rows) {
        throw new Exception("No existe el código {$codigo} en {$tabla}.");
    }
    return (int)$rows[0]['id'];
}

function obtener_catalogo_por_codigo(mysqli $mysqli, string $tabla, string $campoId, string $codigo): array
{
    $sql = "SELECT {$campoId} AS id, codigo, descripcion FROM {$tabla} WHERE codigo = ? AND activo = 1 LIMIT 1";
    $rows = query_all($mysqli, $sql, 's', [$codigo]);
    if (!$rows) {
        throw new Exception("No existe el código {$codigo} en {$tabla}.");
    }
    return $rows[0];
}

function obtener_filtros(array $origen): array
{
    $fechaRuta = validar_fecha_ruta_diaria(limpiar_texto($origen['fechaRuta'] ?? '') ?: fecha_hoy_peru());
    $semana = obtener_rango_semana($fechaRuta);

    return [
        'fechaRuta' => $fechaRuta,
        'fechaInicioSemana' => $semana['fecha_inicio_semana'],
        'fechaFinSemana' => $semana['fecha_fin_semana'],
        'distrito' => limpiar_texto($origen['distrito'] ?? ''),
        'producto' => limpiar_texto($origen['producto'] ?? ''),
        'estadoVisita' => limpiar_texto($origen['estadoVisita'] ?? ''),
        'rangoImporte' => limpiar_texto($origen['rangoImporte'] ?? ''),
        'criterioRuta' => limpiar_texto($origen['criterioRuta'] ?? 'CERCANIA'),
    ];
}

function construir_where_mis_cuentas(array $filtros, int $idAsesor, string &$types, array &$params): string
{
    $where = [
        'ga.activo = 1',
        'ga.id_asesor = ?',
        'c.id IS NOT NULL'
    ];
    $types = 'i';
    $params = [$idAsesor];

    if (($filtros['fechaInicioSemana'] ?? '') !== '' && ($filtros['fechaFinSemana'] ?? '') !== '') {
        $where[] = "(hrd.id_detalle IS NULL OR DATE(hrd.fecha_agendada) BETWEEN ? AND ?)";
        $types .= 'ss';
        $params[] = $filtros['fechaInicioSemana'];
        $params[] = $filtros['fechaFinSemana'];
    }

    if ($filtros['distrito'] !== '') {
        $where[] = 'd.DISTRITO = ?';
        $types .= 's';
        $params[] = $filtros['distrito'];
    }

    if ($filtros['producto'] !== '') {
        $where[] = 'c.PRODUCTO = ?';
        $types .= 's';
        $params[] = $filtros['producto'];
    }

    if ($filtros['estadoVisita'] !== '') {
        if ($filtros['estadoVisita'] === 'SIN_RUTA') {
            $where[] = 'hrd.id_detalle IS NULL';
        } else {
            $where[] = 'ev.codigo = ?';
            $types .= 's';
            $params[] = $filtros['estadoVisita'];
        }
    }

    if ($filtros['rangoImporte'] === '0-500') {
        $where[] = 'COALESCE(c.MONTOACOBRAR, 0) >= 0 AND COALESCE(c.MONTOACOBRAR, 0) < 500';
    } elseif ($filtros['rangoImporte'] === '500-1000') {
        $where[] = 'COALESCE(c.MONTOACOBRAR, 0) >= 500 AND COALESCE(c.MONTOACOBRAR, 0) < 1000';
    } elseif ($filtros['rangoImporte'] === '1000-5000') {
        $where[] = 'COALESCE(c.MONTOACOBRAR, 0) >= 1000 AND COALESCE(c.MONTOACOBRAR, 0) < 5000';
    } elseif ($filtros['rangoImporte'] === '5000+') {
        $where[] = 'COALESCE(c.MONTOACOBRAR, 0) >= 5000';
    }

    return 'WHERE ' . implode(' AND ', $where);
}

function obtener_order_by(string $criterio): string
{
    switch ($criterio) {
        case 'DISTRITO':
            return 'ORDER BY d.DISTRITO ASC, d.PROVINCIA ASC, d.DEPARTAMENTO ASC, c.id ASC';
        case 'MONTO_MAYOR':
            return 'ORDER BY COALESCE(c.MONTOACOBRAR, 0) DESC, c.id ASC';
        case 'MONTO_MENOR':
            return 'ORDER BY COALESCE(c.MONTOACOBRAR, 0) ASC, c.id ASC';
        case 'PERSONALIZADO':
            return 'ORDER BY COALESCE(hrd.orden_visita, 999999) ASC, c.id ASC';
        case 'CERCANIA':
        default:
            return 'ORDER BY CASE WHEN COALESCE(dc.latitud, 0) <> 0 AND COALESCE(dc.longitud, 0) <> 0 THEN 0 ELSE 1 END ASC, d.DISTRITO ASC, d.PROVINCIA ASC, d.DEPARTAMENTO ASC, c.id ASC';
    }
}

function from_mis_cuentas(array $filtros = [], string &$typesFrom = '', array &$paramsFrom = []): string
{
    $inicioSemana = limpiar_texto($filtros['fechaInicioSemana'] ?? '');
    $finSemana = limpiar_texto($filtros['fechaFinSemana'] ?? '');

    if ($inicioSemana === '' || $finSemana === '') {
        $semana = obtener_rango_semana(fecha_hoy_peru());
        $inicioSemana = $semana['fecha_inicio_semana'];
        $finSemana = $semana['fecha_fin_semana'];
    }

    // Los parámetros de este FROM van antes que los parámetros del WHERE.
    $typesFrom .= 'ssss';
    $paramsFrom[] = $inicioSemana;
    $paramsFrom[] = $finSemana;
    $paramsFrom[] = $inicioSemana . ' 00:00:00';
    $paramsFrom[] = $finSemana . ' 23:59:59';

    return "
        FROM geocampo_asignacion ga

        INNER JOIN C_FINANCIERA_EFECTIVA_CAMPO c
            ON c.id = ga.id_cuenta_campo

        LEFT JOIN geocampo_hoja_ruta_detalle hrd
            ON hrd.id_detalle = (
                SELECT d2.id_detalle
                FROM geocampo_hoja_ruta_detalle d2
                INNER JOIN geocampo_hoja_ruta hr2
                    ON hr2.id_hoja_ruta = d2.id_hoja_ruta
                INNER JOIN geocampo_estado_ruta er2
                    ON er2.id_estado_ruta = hr2.id_estado_ruta
                WHERE d2.id_asignacion = ga.id_asignacion
                  AND hr2.id_asesor = ga.id_asesor
                  AND COALESCE(hr2.tipo_ruta, 'DIARIA') = 'SEMANAL'
                  AND hr2.fecha_inicio_semana = ?
                  AND hr2.fecha_fin_semana = ?
                  AND er2.codigo IN ('CREADA', 'EN_PROCESO')
                ORDER BY d2.orden_visita ASC, d2.id_detalle DESC
                LIMIT 1
            )

        LEFT JOIN geocampo_hoja_ruta hr
            ON hr.id_hoja_ruta = hrd.id_hoja_ruta

        LEFT JOIN geocampo_estado_visita ev
            ON ev.id_estado_visita = hrd.id_estado_visita

        LEFT JOIN geocampo_estado_asignacion ea
            ON ea.id_estado_asignacion = ga.id_estado_asignacion

        LEFT JOIN (
            SELECT DISTINCT
                DOC,
                DEPARTAMENTO,
                PROVINCIA,
                DISTRITO,
                DIRECCION_DEPURADA,
                DIRECCION,
                REF_DEPURADA,
                REF
            FROM direcciones
            WHERE DOC IS NOT NULL
            AND DOC <> ''
            AND FUENTE = '11'
            AND COALESCE(IDESTADO, 1) = 1
        ) d
            ON d.DOC = c.documento
        LEFT JOIN geocampo_direccion_corregida dc
            ON dc.id_direccion_corregida = (
                SELECT dc2.id_direccion_corregida
                FROM geocampo_direccion_corregida dc2
                WHERE dc2.id_asignacion = ga.id_asignacion
                ORDER BY dc2.id_direccion_corregida DESC
                LIMIT 1
            )

        LEFT JOIN (
            SELECT
                g.IDENTIFICADOR,
                COUNT(*) AS cantidad_visitas_dia,
                MAX(g.FECHA) AS ultima_fecha_visita
            FROM GEOCAMPO g
            WHERE g.FECHA >= ?
              AND g.FECHA <= ?
            GROUP BY g.IDENTIFICADOR
        ) vgd
            ON vgd.IDENTIFICADOR = c.identificador
    ";
}


function select_campos_mis_cuentas(): string
{
    return "
        SELECT
            c.id,
            ga.id_asignacion,
            hrd.id_detalle,
            hrd.orden_visita,
            c.NUMEROCUENTA AS cuenta,
            c.NOMBRE AS cliente,
            c.PRODUCTO AS producto,
            d.DEPARTAMENTO AS departamento,
            d.DISTRITO AS distrito,
            CONCAT_WS(' / ', NULLIF(TRIM(d.DEPARTAMENTO), ''), NULLIF(TRIM(d.PROVINCIA), ''), NULLIF(TRIM(d.DISTRITO), '')) AS ubigeo,
            c.MONTOACOBRAR AS importe,
            d.DIRECCION_DEPURADA AS direccion_depurada,
            d.DIRECCION AS direccion_original_base,
            d.REF_DEPURADA AS referencia_depurada,
            d.REF AS referencia_original,
            d.DEPARTAMENTO AS departamento_dir,
            d.PROVINCIA AS provincia_dir,
            d.DISTRITO AS distrito_dir,
            CONCAT_WS(' / ', NULLIF(TRIM(d.DEPARTAMENTO), ''), NULLIF(TRIM(d.PROVINCIA), ''), NULLIF(TRIM(d.DISTRITO), '')) AS direccion_original_compuesta,
            CONCAT_WS(' / ', NULLIF(TRIM(d.DEPARTAMENTO), ''), NULLIF(TRIM(d.PROVINCIA), ''), NULLIF(TRIM(d.DISTRITO), '')) AS ubigeo_original_base,
            dc.direccion_search,
            dc.direccion_corregida,
            dc.distrito_original,
            dc.distrito_corregido,
            dc.ubigeo_original,
            dc.ubigeo_corregido,
            dc.latitud,
            dc.longitud,
            COALESCE(ev.codigo, ea.codigo, 'PENDIENTE') AS estado_visita_codigo,
            COALESCE(ev.descripcion, ea.descripcion, 'Pendiente') AS estado_visita,
            COALESCE(vgd.cantidad_visitas_dia, 0) AS cantidad_visitas_dia,
            vgd.ultima_fecha_visita,
            NULL AS id_detalle_pendiente,
            NULL AS fecha_pendiente,
            NULL AS estado_pendiente_codigo,
            NULL AS estado_pendiente
    ";
}

function normalizar_cuenta(array $row): array
{
    $direccionDepurada = limpiar_texto($row['direccion_depurada'] ?? '');
    $direccionBase = limpiar_texto($row['direccion_original_base'] ?? '');
    $referenciaDepurada = limpiar_texto($row['referencia_depurada'] ?? '');
    $referenciaOriginal = limpiar_texto($row['referencia_original'] ?? '');
    $distritoDir = limpiar_texto($row['distrito_dir'] ?? '');
    $provinciaDir = limpiar_texto($row['provincia_dir'] ?? '');
    $departamentoDir = limpiar_texto($row['departamento_dir'] ?? '');

    $partesOriginal = array_filter([
        $direccionDepurada ?: $direccionBase,
        $referenciaDepurada ?: $referenciaOriginal,
    ]);

    if (!$partesOriginal) {
        $partesOriginal = array_filter([
            $departamentoDir ?: limpiar_texto($row['departamento'] ?? ''),
            $provinciaDir,
            $distritoDir ?: limpiar_texto($row['distrito'] ?? ''),
            limpiar_texto($row['ubigeo'] ?? '')
        ]);
    }

    $direccionOriginal = $partesOriginal ? implode(' / ', $partesOriginal) : limpiar_texto($row['direccion_original_compuesta']);
    $ubigeoOriginal = limpiar_texto($row['ubigeo_original_base'] ?? '');
    if ($ubigeoOriginal === '') {
        $ubigeoOriginal = implode(' / ', array_filter([$departamentoDir, $provinciaDir, $distritoDir]));
    }

    $direccionSearch = limpiar_texto($row['direccion_search'] ?? '');
    $direccionCorregida = limpiar_texto($row['direccion_corregida'] ?? '');
    $distritoCorregido = limpiar_texto($row['distrito_corregido'] ?? '');
    $ubigeoCorregido = limpiar_texto($row['ubigeo_corregido'] ?? '');
    $direccionSugerida = $direccionCorregida !== '' ? $direccionCorregida : ($direccionSearch !== '' ? $direccionSearch : '');
    $latitud = (float)($row['latitud'] ?? 0);
    $longitud = (float)($row['longitud'] ?? 0);

    return [
        'id' => (int)$row['id'],
        'id_asignacion' => (int)$row['id_asignacion'],
        'id_detalle' => $row['id_detalle'] !== null ? (int)$row['id_detalle'] : null,
        'cuenta' => limpiar_texto($row['cuenta']) ?: limpiar_texto($row['id']),
        'cliente' => limpiar_texto($row['cliente']),
        'producto' => limpiar_texto($row['producto']) ?: 'Sin producto',
        'distrito' => $distritoDir ?: 'Sin distrito',
        'ubigeo' => $ubigeoOriginal,
        'departamento' => $departamentoDir,
        'provincia' => $provinciaDir,
        'distrito_original' => $distritoDir,
        'ubigeo_original' => $ubigeoOriginal,
        'importe' => (float)($row['importe'] ?? 0),
        'direccion_original' => $direccionOriginal !== '' ? $direccionOriginal : 'Sin dirección registrada',
        'direccion_search' => $direccionSearch,
        'direccion_corregida' => $direccionCorregida,
        'direccion_sugerida' => $direccionSugerida,
        'distrito_corregido' => $distritoCorregido,
        'ubigeo_corregido' => $ubigeoCorregido,
        'latitud' => $latitud,
        'longitud' => $longitud,
        'coord_status' => ($latitud !== 0.0 && $longitud !== 0.0) ? 'VALIDA' : 'POR_VALIDAR',
        'estado_visita_codigo' => limpiar_texto($row['estado_visita_codigo']) ?: 'PENDIENTE',
        'estado_visita' => limpiar_texto($row['estado_visita']) ?: 'Pendiente',
        'orden_visita' => $row['orden_visita'] !== null ? (int)$row['orden_visita'] : null,
        'cantidad_visitas_dia' => (int)($row['cantidad_visitas_dia'] ?? 0),
        'ultima_fecha_visita' => limpiar_texto($row['ultima_fecha_visita'] ?? ''),
        'pendiente_anterior' => !empty($row['id_detalle_pendiente']),
        'id_detalle_pendiente' => !empty($row['id_detalle_pendiente']) ? (int)$row['id_detalle_pendiente'] : null,
        'fecha_pendiente' => limpiar_texto($row['fecha_pendiente'] ?? ''),
        'estado_pendiente_codigo' => limpiar_texto($row['estado_pendiente_codigo'] ?? ''),
        'estado_pendiente' => limpiar_texto($row['estado_pendiente'] ?? ''),
    ];
}

function consultar_mis_cuentas(mysqli $mysqli, int $idAsesor, array $filtros, int $pagina, int $porPagina): array
{
    $pagina = max(1, $pagina);
    $porPagina = in_array($porPagina, [5, 10, 15, 25, 50, 100], true) ? $porPagina : 10;
    $offset = ($pagina - 1) * $porPagina;

    $types = '';
    $params = [];
    $where = construir_where_mis_cuentas($filtros, $idAsesor, $types, $params);
    $typesFrom = '';
    $paramsFrom = [];
    $from = from_mis_cuentas($filtros, $typesFrom, $paramsFrom);
    $queryTypes = $typesFrom . $types;
    $queryParams = array_merge($paramsFrom, $params);

    $totalRows = query_all($mysqli, "SELECT COUNT(*) AS total {$from} {$where}", $queryTypes, $queryParams);
    $total = (int)($totalRows[0]['total'] ?? 0);
    $totalPaginas = max(1, (int)ceil($total / $porPagina));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
    }

    $orderBy = obtener_order_by($filtros['criterioRuta']);

    $sql = select_campos_mis_cuentas() . "
        {$from}
        {$where}
        {$orderBy}
        LIMIT ? OFFSET ?
    ";

    if ($filtros['criterioRuta'] === 'CERCANIA') {
        $sqlSinLimite = preg_replace('/\s+LIMIT \? OFFSET \?\s*$/', '', $sql);
        $rowsOrdenables = query_all($mysqli, $sqlSinLimite, $queryTypes, $queryParams);
        $rowsOrdenables = ordenar_por_proximidad($rowsOrdenables);
        $rows = array_slice($rowsOrdenables, $offset, $porPagina);
    } else {
        $rows = query_all($mysqli, $sql, $queryTypes . 'ii', array_merge($queryParams, [$porPagina, $offset]));
    }

    return [
        'cuentas' => array_map('normalizar_cuenta', $rows),
        'paginacion' => [
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'desde' => $total === 0 ? 0 : $offset + 1,
            'hasta' => min($offset + $porPagina, $total),
        ]
    ];
}

function cargar_filtros(mysqli $mysqli, int $idAsesor): array
{
    $distritos = query_all($mysqli, "
        SELECT DISTINCT d.DISTRITO AS valor
        FROM geocampo_asignacion ga
        INNER JOIN C_FINANCIERA_EFECTIVA_CAMPO c 
            ON c.id = ga.id_cuenta_campo
        LEFT JOIN (
            SELECT DISTINCT
                DOC,
                DISTRITO
            FROM direcciones
            WHERE DOC IS NOT NULL
            AND DOC <> ''
            AND FUENTE = '11'
            AND COALESCE(IDESTADO, 1) = 1
            AND DISTRITO IS NOT NULL
            AND TRIM(DISTRITO) <> ''
        ) d 
            ON d.DOC = c.documento
        WHERE ga.activo = 1 
        AND ga.id_asesor = ? 
        AND d.DISTRITO IS NOT NULL 
        AND TRIM(d.DISTRITO) <> ''
        ORDER BY d.DISTRITO
    ", 'i', [$idAsesor]);

    $productos = query_all($mysqli, "
        SELECT DISTINCT c.PRODUCTO AS valor
        FROM geocampo_asignacion ga
        INNER JOIN C_FINANCIERA_EFECTIVA_CAMPO c ON c.id = ga.id_cuenta_campo
        WHERE ga.activo = 1 AND ga.id_asesor = ? AND c.PRODUCTO IS NOT NULL AND TRIM(c.PRODUCTO) <> ''
        ORDER BY c.PRODUCTO
    ", 'i', [$idAsesor]);

    $estados = query_all($mysqli, "
        SELECT codigo, descripcion
        FROM geocampo_estado_visita
        WHERE activo = 1
        ORDER BY id_estado_visita
    ");

    $criterios = query_all($mysqli, "
        SELECT codigo, descripcion
        FROM geocampo_criterio_ruta
        WHERE activo = 1
        ORDER BY id_criterio_ruta
    ");

    $estados[] = ['codigo' => 'SIN_RUTA', 'descripcion' => 'Sin hoja de ruta'];

    return [
        'distritos' => array_values(array_filter(array_map(fn($r) => limpiar_texto($r['valor']), $distritos))),
        'productos' => array_values(array_filter(array_map(fn($r) => limpiar_texto($r['valor']), $productos))),
        'estados' => $estados,
        'criterios' => $criterios,
    ];
}

function cargar_asesor(mysqli $mysqli, int $idAsesor): array
{
    $rows = query_all($mysqli, "
        SELECT IDPERSONAL AS id, TRIM(CONCAT(COALESCE(NOMBRES, ''), ' ', COALESCE(APELLIDOS, ''))) AS nombre
        FROM personal
        WHERE IDPERSONAL = ?
        LIMIT 1
    ", 'i', [$idAsesor]);

    if (!$rows) {
        throw new Exception('No se encontró el asesor solicitado.');
    }

    return [
        'id' => (int)$rows[0]['id'],
        'nombre' => limpiar_texto($rows[0]['nombre']) ?: 'Asesor sin nombre'
    ];
}

function cargar_inicial(mysqli $mysqli): void
{
    $idAsesor = obtener_id_usuario();
    $filtros = obtener_filtros($_GET);
    $filtros['fechaRuta'] = validar_fecha_ruta_diaria($filtros['fechaRuta'] ?: fecha_hoy_peru());
    $pagina = max(1, (int)($_GET['page'] ?? 1));
    $porPagina = max(1, (int)($_GET['perPage'] ?? 10));

    $resultado = consultar_mis_cuentas($mysqli, $idAsesor, $filtros, $pagina, $porPagina);
    responder_json([
        'ok' => true,
        'asesor' => cargar_asesor($mysqli, $idAsesor),
        'filtros' => cargar_filtros($mysqli, $idAsesor),
        'cuentas' => $resultado['cuentas'],
        'paginacion' => $resultado['paginacion'],
        'ruta_dia' => consultar_ruta_dia($mysqli, $idAsesor, $filtros['fechaRuta']),
        'resumen_pendientes' => consultar_resumen_pendientes($mysqli, $idAsesor)
    ]);
}

function cargar_cuentas(mysqli $mysqli): void
{
    $idAsesor = obtener_id_usuario();
    $filtros = obtener_filtros($_GET);
    $filtros['fechaRuta'] = validar_fecha_ruta_diaria($filtros['fechaRuta'] ?: fecha_hoy_peru());
    $pagina = max(1, (int)($_GET['page'] ?? 1));
    $porPagina = max(1, (int)($_GET['perPage'] ?? 10));

    $resultado = consultar_mis_cuentas($mysqli, $idAsesor, $filtros, $pagina, $porPagina);
    responder_json([
        'ok' => true,
        'cuentas' => $resultado['cuentas'],
        'paginacion' => $resultado['paginacion'],
        'ruta_dia' => consultar_ruta_dia($mysqli, $idAsesor, $filtros['fechaRuta']),
        'resumen_pendientes' => consultar_resumen_pendientes($mysqli, $idAsesor)
    ]);
}

function obtener_ids_filtrados(mysqli $mysqli): void
{
    $idAsesor = obtener_id_usuario();
    $filtros = obtener_filtros($_GET);
    $filtros['fechaRuta'] = validar_fecha_ruta_diaria($filtros['fechaRuta'] ?: fecha_hoy_peru());
    $types = '';
    $params = [];
    $where = construir_where_mis_cuentas($filtros, $idAsesor, $types, $params);
    $typesFrom = '';
    $paramsFrom = [];
    $from = from_mis_cuentas($filtros, $typesFrom, $paramsFrom);
    $queryTypes = $typesFrom . $types;
    $queryParams = array_merge($paramsFrom, $params);
    $orderBy = obtener_order_by($filtros['criterioRuta']);

    $sql = "
        SELECT
            c.id,
            ga.id_asignacion,
            d.DISTRITO AS distrito,
            CONCAT_WS(' / ', NULLIF(TRIM(d.DEPARTAMENTO), ''), NULLIF(TRIM(d.PROVINCIA), ''), NULLIF(TRIM(d.DISTRITO), '')) AS ubigeo,
            dc.latitud,
            dc.longitud
        {$from}
        {$where}
        {$orderBy}
    ";

    $rows = query_all($mysqli, $sql, $queryTypes, $queryParams);
    if ($filtros['criterioRuta'] === 'CERCANIA') {
        $rows = ordenar_por_proximidad($rows);
    }

    responder_json([
        'ok' => true,
        'ids' => array_values(array_unique(array_map(fn($row) => (int)$row['id_asignacion'], $rows)))
    ]);
}

function validar_asignaciones_asesor(mysqli $mysqli, int $idAsesor, array $idsAsignacion): array
{
    $idsAsignacion = array_values(array_unique(array_filter(array_map('intval', $idsAsignacion), fn($id) => $id > 0)));
    if (!$idsAsignacion) {
        throw new Exception('Debe seleccionar al menos una cuenta.');
    }

    $placeholders = implode(',', array_fill(0, count($idsAsignacion), '?'));
    $types = str_repeat('i', count($idsAsignacion) + 1);
    $params = array_merge([$idAsesor], $idsAsignacion);

    $rows = query_all($mysqli, "
        SELECT id_asignacion
        FROM geocampo_asignacion
        WHERE activo = 1
          AND id_asesor = ?
          AND id_asignacion IN ({$placeholders})
    ", $types, $params);

    $validas = array_map(fn($r) => (int)$r['id_asignacion'], $rows);
    if (count($validas) !== count($idsAsignacion)) {
        throw new Exception('Una o más cuentas no pertenecen al asesor o ya no están activas.');
    }

    $setValidas = array_flip($validas);
    return array_values(array_filter($idsAsignacion, fn($id) => isset($setValidas[$id])));
}

function crear_hoja_ruta(mysqli $mysqli): void
{
    // Compatibilidad con la acción antigua. La creación actual trabaja por semana.
    crear_o_actualizar_hoja_ruta($mysqli);
}

function obtener_hoja_ruta_activa(mysqli $mysqli, int $idAsesor, string $fechaRuta): ?array
{
    $semana = obtener_rango_semana($fechaRuta);
    $rows = query_all($mysqli, "
        SELECT r.id_hoja_ruta, r.nombre_ruta, r.fecha_inicio_semana, r.fecha_fin_semana, r.id_periodo
        FROM geocampo_hoja_ruta r
        INNER JOIN geocampo_estado_ruta er ON er.id_estado_ruta = r.id_estado_ruta
        WHERE r.id_asesor = ?
          AND COALESCE(r.tipo_ruta, 'DIARIA') = 'SEMANAL'
          AND r.fecha_inicio_semana = ?
          AND r.fecha_fin_semana = ?
          AND er.codigo IN ('CREADA', 'EN_PROCESO')
        ORDER BY r.id_hoja_ruta DESC
        LIMIT 1
    ", 'iss', [$idAsesor, $semana['fecha_inicio_semana'], $semana['fecha_fin_semana']]);

    return $rows ? $rows[0] : null;
}


function consultar_ruta_dia(mysqli $mysqli, int $idAsesor, string $fechaRuta): array
{
    // Se conserva el nombre de la respuesta como ruta_dia para no romper el JS,
    // pero ahora representa la ruta semanal activa.
    $fechaRuta = validar_fecha_ruta_diaria($fechaRuta);
    $semana = obtener_rango_semana($fechaRuta);
    $ruta = obtener_hoja_ruta_activa($mysqli, $idAsesor, $fechaRuta);
    if (!$ruta) {
        return [
            'existe' => false,
            'id_hoja_ruta' => null,
            'ids' => [],
            'cuentas' => [],
            'tipo_ruta' => 'SEMANAL',
            'fecha_inicio_semana' => $semana['fecha_inicio_semana'],
            'fecha_fin_semana' => $semana['fecha_fin_semana']
        ];
    }

    $idHojaRuta = (int)$ruta['id_hoja_ruta'];
    $detalles = query_all($mysqli, "
        SELECT id_asignacion, orden_visita
        FROM geocampo_hoja_ruta_detalle
        WHERE id_hoja_ruta = ?
        ORDER BY COALESCE(orden_visita, 999999), id_detalle
    ", 'i', [$idHojaRuta]);

    $ids = array_values(array_unique(array_map(fn($r) => (int)$r['id_asignacion'], $detalles)));
    if (!$ids) {
        return [
            'existe' => true,
            'id_hoja_ruta' => $idHojaRuta,
            'ids' => [],
            'cuentas' => [],
            'tipo_ruta' => 'SEMANAL',
            'fecha_inicio_semana' => $semana['fecha_inicio_semana'],
            'fecha_fin_semana' => $semana['fecha_fin_semana']
        ];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $filtros = [
        'fechaRuta' => $fechaRuta,
        'fechaInicioSemana' => $semana['fecha_inicio_semana'],
        'fechaFinSemana' => $semana['fecha_fin_semana'],
        'criterioRuta' => 'PERSONALIZADO'
    ];
    $typesFrom = '';
    $paramsFrom = [];
    $from = from_mis_cuentas($filtros, $typesFrom, $paramsFrom);
    $sql = select_campos_mis_cuentas() . "
        {$from}
        WHERE ga.activo = 1
          AND ga.id_asesor = ?
          AND ga.id_asignacion IN ({$placeholders})
    ";

    $types = $typesFrom . 'i' . str_repeat('i', count($ids));
    $params = array_merge($paramsFrom, [$idAsesor], $ids);
    $rows = query_all($mysqli, $sql, $types, $params);
    $cuentas = array_map('normalizar_cuenta', $rows);
    $porId = [];
    foreach ($cuentas as $cuenta) {
        $porId[(int)$cuenta['id_asignacion']] = $cuenta;
    }

    $cuentasOrdenadas = [];
    foreach ($ids as $idAsignacion) {
        if (isset($porId[$idAsignacion])) {
            $cuentasOrdenadas[] = $porId[$idAsignacion];
        }
    }

    return [
        'existe' => true,
        'id_hoja_ruta' => $idHojaRuta,
        'ids' => $ids,
        'cuentas' => $cuentasOrdenadas,
        'tipo_ruta' => 'SEMANAL',
        'fecha_inicio_semana' => $semana['fecha_inicio_semana'],
        'fecha_fin_semana' => $semana['fecha_fin_semana']
    ];
}


function consultar_resumen_pendientes(mysqli $mysqli, int $idAsesor): array
{
    // La migración a ruta semanal deja obsoleta la lógica de "sumar pendientes del día anterior".
    // Las cuentas no visitadas permanecen dentro de la misma ruta semanal hasta que se visiten o reprograme la semana.
    return [
        'total' => 0,
        'fecha_mas_antigua' => ''
    ];
}

function obtener_pendientes_reprogramar(mysqli $mysqli): void
{
    responder_json([
        'ok' => true,
        'pendientes' => [],
        'resumen_pendientes' => [
            'total' => 0,
            'fecha_mas_antigua' => ''
        ]
    ]);
}

function obtener_detalles_pendientes_por_asignacion(mysqli $mysqli, int $idAsesor, array $idsAsignacion): array
{
    return [];
}

function registrar_historial_ruta(mysqli $mysqli, int $idHojaRuta, ?int $idDetalle, ?int $idAsignacion, string $tipoEvento, ?string $valorAnterior, ?string $valorNuevo, string $observacion, int $idUsuario): void
{
    $stmt = $mysqli->prepare("
        INSERT INTO geocampo_hoja_ruta_historial (
            id_hoja_ruta, id_detalle, id_asignacion, tipo_evento, valor_anterior, valor_nuevo, observacion, id_usuario, fecha_evento
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('iiissssi', $idHojaRuta, $idDetalle, $idAsignacion, $tipoEvento, $valorAnterior, $valorNuevo, $observacion, $idUsuario);
    $stmt->execute();
    $stmt->close();
}

function crear_o_actualizar_hoja_ruta(mysqli $mysqli): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responder_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
    }

    $idAsesor = (int)($input['id_usuario'] ?? $_SESSION['id_mis_cuentas'] ?? $_SESSION['id'] ?? 0);
    $idsAsignacion = $input['idsAsignacion'] ?? [];
    $criterio = limpiar_texto($input['criterioRuta'] ?? 'PERSONALIZADO');
    $fechaRuta = limpiar_texto($input['fechaRuta'] ?? date('Y-m-d'));

    if ($idAsesor <= 0) {
        responder_json(['ok' => false, 'message' => 'Asesor inválido.'], 400);
    }
    try {
        $fechaRuta = validar_fecha_ruta_diaria($fechaRuta);
    } catch (Throwable $e) {
        responder_json(['ok' => false, 'message' => $e->getMessage()], 400);
    }

    try {
        $mysqli->begin_transaction();

        $idsValidos = validar_asignaciones_asesor($mysqli, $idAsesor, $idsAsignacion);
        $idCriterio = obtener_id_catalogo($mysqli, 'geocampo_criterio_ruta', 'id_criterio_ruta', $criterio);
        $idEstadoRuta = obtener_id_catalogo($mysqli, 'geocampo_estado_ruta', 'id_estado_ruta', 'CREADA');
        $idEstadoVisitaAgendado = obtener_id_catalogo($mysqli, 'geocampo_estado_visita', 'id_estado_visita', 'AGENDADO');
        $idEstadoAsignacion = obtener_id_catalogo($mysqli, 'geocampo_estado_asignacion', 'id_estado_asignacion', 'AGENDADO');

        $semana = obtener_rango_semana($fechaRuta);
        $fechaInicioSemana = $semana['fecha_inicio_semana'];
        $fechaFinSemana = $semana['fecha_fin_semana'];
        $fechaRutaBase = $fechaInicioSemana;
        $fechaAgendada = $fechaInicioSemana . ' 09:00:00';
        $tipoRuta = 'SEMANAL';
        $periodo = obtener_periodo_por_fecha($mysqli, $fechaRutaBase);
        $idPeriodo = $periodo ? (int)$periodo['id_periodo'] : null;
        $nombrePeriodo = $periodo ? limpiar_texto($periodo['nombre']) : date('m/Y', strtotime($fechaRutaBase));
        $nombreRuta = 'Ruta semanal ' . $fechaInicioSemana . ' al ' . $fechaFinSemana . ' - ' . $nombrePeriodo . ' - Asesor ' . $idAsesor;

        $rutaExistente = obtener_hoja_ruta_activa($mysqli, $idAsesor, $fechaRutaBase);
        $accion = 'creada';

        if ($rutaExistente) {
            $idHojaRuta = (int)$rutaExistente['id_hoja_ruta'];
            $accion = 'actualizada';
            $stmtRuta = $mysqli->prepare("
                UPDATE geocampo_hoja_ruta
                SET nombre_ruta = ?,
                    id_criterio_ruta = ?,
                    id_periodo = ?,
                    tipo_ruta = ?,
                    fecha_ruta = ?,
                    fecha_inicio_semana = ?,
                    fecha_fin_semana = ?,
                    fecha_actualizacion = NOW(),
                    id_usuario_actualizacion = ?
                WHERE id_hoja_ruta = ? AND id_asesor = ?
            ");
            $stmtRuta->bind_param('siissssiii', $nombreRuta, $idCriterio, $idPeriodo, $tipoRuta, $fechaRutaBase, $fechaInicioSemana, $fechaFinSemana, $idAsesor, $idHojaRuta, $idAsesor);
            $stmtRuta->execute();
            $stmtRuta->close();
        } else {
            $stmtRuta = $mysqli->prepare("
                INSERT INTO geocampo_hoja_ruta (
                    id_asesor,
                    nombre_ruta,
                    id_criterio_ruta,
                    id_estado_ruta,
                    fecha_ruta,
                    id_periodo,
                    tipo_ruta,
                    fecha_inicio_semana,
                    fecha_fin_semana,
                    fecha_creacion,
                    fecha_actualizacion,
                    id_usuario_actualizacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
            ");
            $stmtRuta->bind_param('isiisisssi', $idAsesor, $nombreRuta, $idCriterio, $idEstadoRuta, $fechaRutaBase, $idPeriodo, $tipoRuta, $fechaInicioSemana, $fechaFinSemana, $idAsesor);
            $stmtRuta->execute();
            $idHojaRuta = (int)$mysqli->insert_id;
            $stmtRuta->close();
        }

        $detallesActuales = query_all($mysqli, "
            SELECT d.id_detalle, d.id_asignacion, d.id_estado_visita, ev.codigo AS estado_codigo
            FROM geocampo_hoja_ruta_detalle d
            LEFT JOIN geocampo_estado_visita ev ON ev.id_estado_visita = d.id_estado_visita
            WHERE d.id_hoja_ruta = ?
        ", 'i', [$idHojaRuta]);

        $detallePorAsignacion = [];
        foreach ($detallesActuales as $detalle) {
            $detallePorAsignacion[(int)$detalle['id_asignacion']] = $detalle;
        }

        $pendientesPorAsignacion = obtener_detalles_pendientes_por_asignacion($mysqli, $idAsesor, $idsValidos);
        $reprogramadosAgregados = 0;

        $stmtInsertDetalle = $mysqli->prepare("
            INSERT INTO geocampo_hoja_ruta_detalle (
                id_hoja_ruta,
                id_asignacion,
                id_detalle_origen,
                es_reprogramado,
                orden_visita,
                id_estado_visita,
                fecha_agendada,
                fecha_reprogramacion,
                id_usuario_reprogramacion,
                motivo_reprogramacion,
                fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtUpdateDetalle = $mysqli->prepare("
            UPDATE geocampo_hoja_ruta_detalle
            SET orden_visita = ?, fecha_agendada = ?
            WHERE id_detalle = ?
        ");
        $stmtUpdateAsignacion = $mysqli->prepare("
            UPDATE geocampo_asignacion
            SET id_estado_asignacion = ?, fecha_actualizacion = NOW()
            WHERE id_asignacion = ? AND id_asesor = ? AND activo = 1
        ");

        $orden = 1;
        foreach ($idsValidos as $idAsignacion) {
            if (isset($detallePorAsignacion[$idAsignacion])) {
                $idDetalle = (int)$detallePorAsignacion[$idAsignacion]['id_detalle'];
                $stmtUpdateDetalle->bind_param('isi', $orden, $fechaAgendada, $idDetalle);
                $stmtUpdateDetalle->execute();
            } else {
                $pendienteOrigen = $pendientesPorAsignacion[$idAsignacion] ?? null;
                $idDetalleOrigen = $pendienteOrigen ? (int)$pendienteOrigen['id_detalle'] : null;
                $esReprogramado = $idDetalleOrigen ? 1 : 0;
                $fechaReprogramacion = $esReprogramado ? date('Y-m-d H:i:s') : null;
                $idUsuarioReprogramacion = $esReprogramado ? $idAsesor : null;
                $motivoReprogramacion = $esReprogramado ? 'Pendiente agregado a nueva hoja de ruta' : null;

                $stmtInsertDetalle->bind_param(
                    'iiiiiissis',
                    $idHojaRuta,
                    $idAsignacion,
                    $idDetalleOrigen,
                    $esReprogramado,
                    $orden,
                    $idEstadoVisitaAgendado,
                    $fechaAgendada,
                    $fechaReprogramacion,
                    $idUsuarioReprogramacion,
                    $motivoReprogramacion
                );
                $stmtInsertDetalle->execute();
                $idDetalleNuevo = (int)$mysqli->insert_id;

                if ($esReprogramado) {
                    $reprogramadosAgregados++;
                    registrar_historial_ruta(
                        $mysqli,
                        $idHojaRuta,
                        $idDetalleNuevo,
                        $idAsignacion,
                        'REPROGRAMACION',
                        (string)$idDetalleOrigen,
                        (string)$idDetalleNuevo,
                        'Cuenta pendiente agregada a la ruta actual.',
                        $idAsesor
                    );
                }
            }

            $stmtUpdateAsignacion->bind_param('iii', $idEstadoAsignacion, $idAsignacion, $idAsesor);
            $stmtUpdateAsignacion->execute();
            $orden++;
        }

        $stmtInsertDetalle->close();
        $stmtUpdateDetalle->close();
        $stmtUpdateAsignacion->close();

        registrar_historial_ruta(
            $mysqli,
            $idHojaRuta,
            null,
            null,
            $accion === 'actualizada' ? 'ACTUALIZACION_ORDEN' : 'CREACION_RUTA',
            null,
            (string)count($idsValidos),
            $accion === 'actualizada' ? 'Ruta semanal actualizada desde Mis Cuentas.' : 'Ruta semanal creada desde Mis Cuentas.',
            $idAsesor
        );

        $mysqli->commit();

        responder_json([
            'ok' => true,
            'message' => $accion === 'actualizada' ? 'Hoja de ruta actualizada correctamente.' : 'Hoja de ruta creada correctamente.',
            'accion' => $accion,
            'id_hoja_ruta' => $idHojaRuta,
            'paradas' => count($idsValidos)
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        responder_json([
            'ok' => false,
            'message' => 'No se pudo guardar la hoja de ruta.',
            'error' => $e->getMessage()
        ], 500);
    }
}

function guardar_direccion(mysqli $mysqli): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responder_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
    }

    $idAsesor = (int)($input['id_usuario'] ?? $_SESSION['id_mis_cuentas'] ?? $_SESSION['id'] ?? 0);
    $idAsignacion = (int)($input['id_asignacion'] ?? 0);
    $direccionOriginal = limpiar_texto($input['direccion_original'] ?? '');
    $direccionSearch = limpiar_texto($input['direccion_search'] ?? '');
    $direccionCorregida = limpiar_texto($input['direccion_corregida'] ?? '');
    $distritoOriginal = limpiar_texto($input['distrito_original'] ?? '');
    $distritoCorregido = limpiar_texto($input['distrito_corregido'] ?? '');
    $ubigeoOriginal = limpiar_texto($input['ubigeo_original'] ?? '');
    $ubigeoCorregido = limpiar_texto($input['ubigeo_corregido'] ?? '');
    $latitud = isset($input['latitud']) && $input['latitud'] !== '' ? (float)$input['latitud'] : null;
    $longitud = isset($input['longitud']) && $input['longitud'] !== '' ? (float)$input['longitud'] : null;
    $codigoFuente = strtoupper(limpiar_texto($input['fuente_correccion'] ?? ''));
    $fuentesPermitidas = ['SISTEMA', 'SEARCH', 'ASESOR', 'SUPERVISOR', 'OTRO'];

    if ($codigoFuente === '') {
        if ($direccionCorregida !== '') {
            $codigoFuente = 'ASESOR';
        } elseif ($direccionSearch !== '') {
            $codigoFuente = 'SEARCH';
        } else {
            $codigoFuente = 'SISTEMA';
        }
    }

    if (!in_array($codigoFuente, $fuentesPermitidas, true)) {
        responder_json(['ok' => false, 'message' => 'Fuente de corrección inválida.'], 400);
    }

    $validado = ($latitud !== null && $longitud !== null) ? 1 : 0;

    if ($idAsesor <= 0 || $idAsignacion <= 0) {
        responder_json(['ok' => false, 'message' => 'Datos inválidos para registrar dirección.'], 400);
    }
    if ($direccionSearch === '' && $direccionCorregida === '') {
        responder_json(['ok' => false, 'message' => 'Debe ingresar una dirección search o corregida.'], 400);
    }

    try {
        validar_asignaciones_asesor($mysqli, $idAsesor, [$idAsignacion]);
        $idFuente = obtener_id_catalogo($mysqli, 'geocampo_fuente_correccion_direccion', 'id_fuente_correccion', $codigoFuente);

        $stmt = $mysqli->prepare("        
            INSERT INTO geocampo_direccion_corregida (
                id_asignacion,
                direccion_original,
                direccion_search,
                direccion_corregida,
                distrito_original,
                distrito_corregido,
                ubigeo_original,
                ubigeo_corregido,
                latitud,
                longitud,
                id_fuente_correccion,
                validado,
                id_usuario_registro,
                fecha_registro,
                fecha_validacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), CASE WHEN ? = 1 THEN NOW() ELSE NULL END)
        ");
        if (!$stmt) throw new Exception('Error preparando dirección: ' . $mysqli->error);
        $stmt->bind_param(
            'isssssssddiiii',
            $idAsignacion,
            $direccionOriginal,
            $direccionSearch,
            $direccionCorregida,
            $distritoOriginal,
            $distritoCorregido,
            $ubigeoOriginal,
            $ubigeoCorregido,
            $latitud,
            $longitud,
            $idFuente,
            $validado,
            $idAsesor,
            $validado
        );
        $stmt->execute();
        $idDireccionCorregida = (int)$mysqli->insert_id;
        $filasAfectadas = (int)$stmt->affected_rows;
        $stmt->close();

        if ($filasAfectadas < 1 || $idDireccionCorregida <= 0) {
            throw new Exception('El registro de dirección no fue insertado. Filas afectadas: ' . $filasAfectadas . '.');
        }

        $rowsVerificacion = query_all(
            $mysqli,
            "SELECT id_direccion_corregida, id_asignacion, direccion_original, direccion_search, direccion_corregida, distrito_original, distrito_corregido, ubigeo_original, ubigeo_corregido, latitud, longitud, id_fuente_correccion, validado, fecha_registro, fecha_validacion
             FROM geocampo_direccion_corregida
             WHERE id_direccion_corregida = ?
             LIMIT 1",
            'i',
            [$idDireccionCorregida]
        );

        if (!$rowsVerificacion) {
            throw new Exception('La dirección fue procesada, pero no se encontró al verificar el registro insertado. ID generado: ' . $idDireccionCorregida . '.');
        }

        responder_json([
            'ok' => true,
            'message' => 'Dirección registrada.',
            'id_direccion_corregida' => $idDireccionCorregida,
            'direccion' => $rowsVerificacion[0]
        ]);
    } catch (Throwable $e) {
        responder_json([
            'ok' => false,
            'message' => 'No se pudo registrar la dirección.',
            'error' => $e->getMessage()
        ], 500);
    }
}

try {
    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'inicial') {
        cargar_inicial($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'cuentas') {
        cargar_cuentas($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'ids_filtrados') {
        obtener_ids_filtrados($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'pendientes_reprogramar') {
        obtener_pendientes_reprogramar($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_ruta') {
        crear_hoja_ruta($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'guardar_o_actualizar_ruta') {
        crear_o_actualizar_hoja_ruta($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'guardar_direccion') {
        guardar_direccion($mysqli);
    }

    responder_json(['ok' => false, 'message' => 'Acción no válida.'], 404);
} catch (Throwable $e) {
    responder_json([
        'ok' => false,
        'message' => 'Error interno en mis cuentas.',
        'error' => $e->getMessage()
    ], 500);
}
