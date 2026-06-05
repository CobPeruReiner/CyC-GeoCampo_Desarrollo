<?php

date_default_timezone_set('America/Lima');
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Sesión no válida o expirada.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

function obtener_id_catalogo(mysqli $mysqli, string $tabla, string $campoId, string $codigo): int
{
    $sql = "SELECT {$campoId} AS id FROM {$tabla} WHERE codigo = ? AND activo = 1 LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando catálogo: ' . $mysqli->error);
    }
    $stmt->bind_param('s', $codigo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("No existe el código {$codigo} en {$tabla}");
    }

    return (int)$row['id'];
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

function obtener_filtros_desde_array(array $origen): array
{
    return [
        'fechaDesde' => limpiar_texto($origen['fechaDesde'] ?? ''),
        'fechaHasta' => limpiar_texto($origen['fechaHasta'] ?? ''),
        'cartera' => limpiar_texto($origen['cartera'] ?? ''),
        'distrito' => limpiar_texto($origen['distrito'] ?? ''),
        'segmento' => limpiar_texto($origen['segmento'] ?? ''),
        'estado' => limpiar_texto($origen['estado'] ?? ''),
        'busqueda' => limpiar_texto($origen['busqueda'] ?? ''),
    ];
}

function construir_where_cuentas(array $filtros, string &$types, array &$params): string
{
    $where = ["c.id IS NOT NULL"];
    $types = '';
    $params = [];

    $fechaExpr = "DATE(COALESCE(c.FECHA_ACTUALIZACION, c.FECHAVEN, NOW()))";

    if ($filtros['fechaDesde'] !== '') {
        $where[] = "{$fechaExpr} >= ?";
        $types .= 's';
        $params[] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta'] !== '') {
        $where[] = "{$fechaExpr} <= ?";
        $types .= 's';
        $params[] = $filtros['fechaHasta'];
    }

    if ($filtros['cartera'] !== '') {
        $where[] = "COALESCE(car.cartera, c.ID_CARTERA) = ?";
        $types .= 's';
        $params[] = $filtros['cartera'];
    }

    if ($filtros['distrito'] !== '') {

        $where[] = "(d.DISTRITO = ? OR d.PROVINCIA = ? OR d.DEPARTAMENTO = ?)";
        $types .= 'sss';
        $params[] = $filtros['distrito'];
        $params[] = $filtros['distrito'];
        $params[] = $filtros['distrito'];
    }

    if ($filtros['segmento'] !== '') {
        $where[] = "c.PRODUCTO = ?";
        $types .= 's';
        $params[] = $filtros['segmento'];
    }

    if ($filtros['estado'] === 'sin_asignar') {
        $where[] = "ga.id_asignacion IS NULL";
    } elseif ($filtros['estado'] === 'asignadas') {
        $where[] = "ga.id_asignacion IS NOT NULL";
    }

    if (($filtros['busqueda'] ?? '') !== '') {
        $where[] = "(
            c.NUMEROCUENTA LIKE ?
            OR c.identificador LIKE ?
            OR c.documento LIKE ?
            OR c.NOMBRE LIKE ?
            OR c.PRODUCTO LIKE ?
            OR c.SUBPRODUCTO LIKE ?
            OR c.ID_CARTERA LIKE ?
            OR COALESCE(car.cartera, '') LIKE ?
            OR COALESCE(d.DIRECCION_DEPURADA, '') LIKE ?
            OR COALESCE(d.DIRECCION, '') LIKE ?
            OR COALESCE(d.REF_DEPURADA, '') LIKE ?
            OR COALESCE(d.REF, '') LIKE ?
            OR COALESCE(d.DEPARTAMENTO, '') LIKE ?
            OR COALESCE(d.PROVINCIA, '') LIKE ?
            OR COALESCE(d.DISTRITO, '') LIKE ?
            OR COALESCE(pa.NOMBRES, '') LIKE ?
            OR COALESCE(pa.APELLIDOS, '') LIKE ?
        )";
        $like = '%' . $filtros['busqueda'] . '%';
        $types .= str_repeat('s', 17);
        for ($i = 0; $i < 17; $i++) {
            $params[] = $like;
        }
    }

    return 'WHERE ' . implode(' AND ', $where);
}

function normalizar_cuentas(array $cuentas): array
{
    foreach ($cuentas as &$cuenta) {
        $cuenta['id'] = (int)$cuenta['id'];
        $cuenta['id_asignacion'] = $cuenta['id_asignacion'] !== null ? (int)$cuenta['id_asignacion'] : null;
        $cuenta['asesorId'] = $cuenta['asesorId'] !== null ? (int)$cuenta['asesorId'] : null;
        $cuenta['importe'] = (float)($cuenta['importe'] ?? 0);

        $direccionDepurada = limpiar_texto($cuenta['direccion_depurada'] ?? '');
        $direccionOriginal = limpiar_texto($cuenta['direccion_original'] ?? '');
        $referenciaDepurada = limpiar_texto($cuenta['referencia_depurada'] ?? '');
        $referenciaOriginal = limpiar_texto($cuenta['referencia_original'] ?? '');

        $partesDireccion = array_filter([
            $direccionDepurada ?: $direccionOriginal,
            $referenciaDepurada ?: $referenciaOriginal,
        ]);

        if (empty($partesDireccion)) {
            $partesDireccion = array_filter([
                limpiar_texto($cuenta['departamento_dir'] ?? ''),
                limpiar_texto($cuenta['provincia_dir'] ?? ''),
                limpiar_texto($cuenta['distrito_dir'] ?? '')
            ]);
        }

        $departamentoDir = limpiar_texto($cuenta['departamento_dir'] ?? '');
        $provinciaDir = limpiar_texto($cuenta['provincia_dir'] ?? '');
        $distritoDir = limpiar_texto($cuenta['distrito_dir'] ?? '');
        $ubigeoDir = implode(' / ', array_filter([$departamentoDir, $provinciaDir, $distritoDir]));

        $cuenta['direccion'] = $partesDireccion ? implode(' / ', $partesDireccion) : 'Sin dirección registrada';
        $cuenta['departamento'] = $departamentoDir;
        $cuenta['provincia'] = $provinciaDir;
        $cuenta['distrito'] = $distritoDir ?: 'Sin distrito';
        $cuenta['ubigeo'] = $ubigeoDir ?: 'Sin ubigeo';
        $cuenta['segmento'] = limpiar_texto($cuenta['segmento']) ?: 'Sin producto';
        $cuenta['cartera'] = limpiar_texto($cuenta['cartera']) ?: 'Sin cartera';
        $cuenta['estado'] = $cuenta['asesorId'] === null ? 'Sin asignar' : $cuenta['estado'];
    }
    unset($cuenta);

    return $cuentas;
}

function consultar_cuentas_paginadas(mysqli $mysqli, array $filtros, int $pagina, int $porPagina): array
{
    $pagina = max(1, $pagina);
    $porPagina = in_array($porPagina, [5, 10, 15, 25, 50, 100], true) ? $porPagina : 10;
    $offset = ($pagina - 1) * $porPagina;

    $types = '';
    $params = [];
    $where = construir_where_cuentas($filtros, $types, $params);

    $from = "
        FROM C_FINANCIERA_EFECTIVA_CAMPO c
        LEFT JOIN cartera car
            ON car.id = c.ID_CARTERA
        LEFT JOIN geocampo_asignacion ga
            ON ga.id_cuenta_campo = c.id
        AND ga.activo = 1
        LEFT JOIN personal pa
            ON pa.IDPERSONAL = ga.id_asesor
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
        ) d
            ON d.DOC = c.documento
    ";

    $totalRows = query_all($mysqli, "SELECT COUNT(*) AS total {$from} {$where}", $types, $params);
    $total = (int)($totalRows[0]['total'] ?? 0);
    $totalPaginas = max(1, (int)ceil($total / $porPagina));

    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
    }

    $sql = "
        SELECT
            c.id,
            c.NUMEROCUENTA AS cuenta,
            c.NOMBRE AS cliente,
            c.PRODUCTO AS segmento,
            d.DEPARTAMENTO AS departamento,
            d.PROVINCIA AS provincia,
            d.DISTRITO AS distrito,
            CONCAT_WS(' / ', NULLIF(TRIM(d.DEPARTAMENTO), ''), NULLIF(TRIM(d.PROVINCIA), ''), NULLIF(TRIM(d.DISTRITO), '')) AS ubigeo,
            d.DIRECCION_DEPURADA AS direccion_depurada,
            d.DIRECCION AS direccion_original,
            d.REF_DEPURADA AS referencia_depurada,
            d.REF AS referencia_original,
            d.DEPARTAMENTO AS departamento_dir,
            d.PROVINCIA AS provincia_dir,
            d.DISTRITO AS distrito_dir,
            c.MONTOACOBRAR AS importe,
            c.ID_CARTERA AS id_cartera,
            COALESCE(car.cartera, c.ID_CARTERA) AS cartera,
            DATE(COALESCE(c.FECHA_ACTUALIZACION, c.FECHAVEN, NOW())) AS fecha,
            ga.id_asignacion,
            ga.id_asesor AS asesorId,
            ea.codigo AS estadoCodigo,
            COALESCE(ea.descripcion, 'Sin asignar') AS estado
        {$from}
        {$where}
        ORDER BY c.id DESC
        LIMIT ? OFFSET ?
    ";

    $cuentas = query_all($mysqli, $sql, $types . 'ii', array_merge($params, [$porPagina, $offset]));

    return [
        'cuentas' => normalizar_cuentas($cuentas),
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

function cargar_asesores(mysqli $mysqli): array
{
    $idUsuario = (int)$_SESSION['id'];

    $asesoresSql = "
        SELECT
            p.IDPERSONAL AS id,
            TRIM(CONCAT(COALESCE(p.NOMBRES, ''), ' ', COALESCE(p.APELLIDOS, ''))) AS nombre,

            (
                SELECT COUNT(*)
                FROM geocampo_asignacion ga_asig
                WHERE ga_asig.id_asesor = p.IDPERSONAL
                  AND ga_asig.activo = 1
            ) AS asignadas,

            (
                SELECT COUNT(*)
                FROM geocampo_hoja_ruta hr_sem
                INNER JOIN geocampo_estado_ruta er_sem
                    ON er_sem.id_estado_ruta = hr_sem.id_estado_ruta
                INNER JOIN geocampo_hoja_ruta_detalle hrd_sem
                    ON hrd_sem.id_hoja_ruta = hr_sem.id_hoja_ruta
                INNER JOIN geocampo_asignacion ga_sem
                    ON ga_sem.id_asignacion = hrd_sem.id_asignacion
                   AND ga_sem.activo = 1
                   AND ga_sem.id_asesor = p.IDPERSONAL
                WHERE hr_sem.id_asesor = p.IDPERSONAL
                  AND er_sem.codigo NOT IN ('ANULADA', 'CERRADA')
                  AND (
                        (
                            hr_sem.fecha_inicio_semana IS NOT NULL
                            AND hr_sem.fecha_fin_semana IS NOT NULL
                            AND hr_sem.fecha_inicio_semana <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
                            AND hr_sem.fecha_fin_semana >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                        )
                        OR (
                            hr_sem.fecha_inicio_semana IS NULL
                            AND hr_sem.fecha_ruta BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                                  AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
                        )
                  )
            ) AS ruta_semana,

            0 AS pendientes,

            (
                SELECT COUNT(*)
                FROM GEOCAMPO g_hoy
                WHERE g_hoy.IDPERSONAL = p.IDPERSONAL
                  AND g_hoy.IDCARTERA = 63
                  AND DATE(g_hoy.FECHA) = CURDATE()
            ) AS visitas_hoy,

            (
                SELECT COUNT(*)
                FROM GEOCAMPO g_sem
                WHERE g_sem.IDPERSONAL = p.IDPERSONAL
                  AND g_sem.IDCARTERA = 63
                  AND DATE(g_sem.FECHA) BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                                          AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
            ) AS visitas_semana
        FROM personal p
        WHERE COALESCE(p.IDESTADO, 1) = 1
          AND p.IDPERSONAL <> ?
          AND EXISTS (
              SELECT 1
              FROM asignacion_tabla at
              INNER JOIN tabla_log tl
                  ON at.id_tabla = tl.id
              INNER JOIN cartera ca
                  ON tl.id_cartera = ca.id
              WHERE at.id_usuario = p.IDPERSONAL
                AND tl.id_cartera = 63
                AND ca.estado = 1
                AND tl.estado = 0
          )
        ORDER BY nombre
    ";

    $asesores = query_all($mysqli, $asesoresSql, 'i', [$idUsuario]);

    foreach ($asesores as &$asesor) {
        $asesor['id'] = (int)$asesor['id'];
        $asesor['asignadas'] = (int)$asesor['asignadas'];
        $asesor['ruta_semana'] = (int)($asesor['ruta_semana'] ?? 0);
        $asesor['ruta_hoy'] = $asesor['ruta_semana'];
        $asesor['pendientes'] = 0;
        $asesor['visitas_hoy'] = (int)($asesor['visitas_hoy'] ?? 0);
        $asesor['visitas_semana'] = (int)($asesor['visitas_semana'] ?? 0);
        $asesor['visitas'] = $asesor['visitas_semana'];
        $asesor['online'] = false;
    }
    unset($asesor);

    return $asesores;
}

function cargar_filtros(mysqli $mysqli): array
{
    $carteras = query_all($mysqli, "
        SELECT DISTINCT COALESCE(car.cartera, c.ID_CARTERA) AS valor
        FROM C_FINANCIERA_EFECTIVA_CAMPO c
        LEFT JOIN cartera car ON car.id = c.ID_CARTERA
        WHERE c.ID_CARTERA IS NOT NULL AND TRIM(c.ID_CARTERA) <> ''
        ORDER BY valor
    ");

    $distritos = query_all($mysqli, "
        SELECT DISTINCT d.DISTRITO AS valor
        FROM C_FINANCIERA_EFECTIVA_CAMPO c
        INNER JOIN (
            SELECT DISTINCT
                DOC,
                DISTRITO
            FROM direcciones
            WHERE DOC IS NOT NULL
            AND DOC <> ''
            AND FUENTE = '11'
            AND DISTRITO IS NOT NULL
            AND TRIM(DISTRITO) <> ''
        ) d
            ON d.DOC = c.documento
        ORDER BY d.DISTRITO
    ");

    $segmentos = query_all($mysqli, "
        SELECT DISTINCT PRODUCTO AS valor
        FROM C_FINANCIERA_EFECTIVA_CAMPO
        WHERE PRODUCTO IS NOT NULL AND TRIM(PRODUCTO) <> ''
        ORDER BY PRODUCTO
    ");

    return [
        'carteras' => array_values(array_filter(array_map(fn($r) => limpiar_texto($r['valor']), $carteras))),
        'distritos' => array_values(array_filter(array_map(fn($r) => limpiar_texto($r['valor']), $distritos))),
        'segmentos' => array_values(array_filter(array_map(fn($r) => limpiar_texto($r['valor']), $segmentos))),
        'estados' => [
            ['value' => 'sin_asignar', 'label' => 'Sin asignar'],
            ['value' => 'asignadas', 'label' => 'Asignadas']
        ]
    ];
}

function cargar_inicial(mysqli $mysqli): void
{
    $pagina = max(1, (int)($_GET['page'] ?? 1));
    $porPagina = max(1, (int)($_GET['perPage'] ?? 10));
    $filtros = obtener_filtros_desde_array($_GET);
    $resultadoCuentas = consultar_cuentas_paginadas($mysqli, $filtros, $pagina, $porPagina);

    responder_json([
        'ok' => true,
        'asesores' => cargar_asesores($mysqli),
        'filtros' => cargar_filtros($mysqli),
        'cuentas' => $resultadoCuentas['cuentas'],
        'paginacion' => $resultadoCuentas['paginacion']
    ]);
}

function cargar_cuentas(mysqli $mysqli): void
{
    $pagina = max(1, (int)($_GET['page'] ?? 1));
    $porPagina = max(1, (int)($_GET['perPage'] ?? 10));
    $filtros = obtener_filtros_desde_array($_GET);
    $resultadoCuentas = consultar_cuentas_paginadas($mysqli, $filtros, $pagina, $porPagina);

    responder_json([
        'ok' => true,
        'cuentas' => $resultadoCuentas['cuentas'],
        'paginacion' => $resultadoCuentas['paginacion']
    ]);
}

function obtener_ids_por_filtros(mysqli $mysqli, array $filtros): array
{
    $types = '';
    $params = [];
    $where = construir_where_cuentas($filtros, $types, $params);

    $sql = "
        SELECT c.id
        FROM C_FINANCIERA_EFECTIVA_CAMPO c
        LEFT JOIN cartera car
            ON car.id = c.ID_CARTERA
        LEFT JOIN geocampo_asignacion ga
            ON ga.id_cuenta_campo = c.id
        AND ga.activo = 1
        LEFT JOIN personal pa
            ON pa.IDPERSONAL = ga.id_asesor
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
        ) d
            ON d.DOC = c.documento
        {$where}
        ORDER BY c.id DESC
    ";

    $rows = query_all($mysqli, $sql, $types, $params);
    return array_values(array_unique(array_map(fn($row) => (int)$row['id'], $rows)));
}


function obtener_restricciones_reasignacion(mysqli $mysqli, array $ids, int $idAsesorDestino): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

    if (empty($ids) || $idAsesorDestino <= 0) {
        return [
            'ruta_hoy' => [],
            'pendientes_previas' => []
        ];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids)) . 'i';
    $params = array_merge($ids, [$idAsesorDestino]);

    $sql = "
        SELECT DISTINCT
            ga.id_asignacion,
            ga.id_cuenta_campo AS id_cuenta,
            ga.id_asesor AS id_asesor_actual,
            TRIM(CONCAT(COALESCE(p.NOMBRES, ''), ' ', COALESCE(p.APELLIDOS, ''))) AS asesor_actual,
            c.NUMEROCUENTA AS cuenta,
            c.NOMBRE AS cliente,
            hr.fecha_ruta,
            er.codigo AS estado_ruta_codigo,
            ev.codigo AS estado_visita_codigo
        FROM geocampo_asignacion ga
        INNER JOIN geocampo_hoja_ruta_detalle hrd
            ON hrd.id_asignacion = ga.id_asignacion
        INNER JOIN geocampo_hoja_ruta hr
            ON hr.id_hoja_ruta = hrd.id_hoja_ruta
        INNER JOIN geocampo_estado_ruta er
            ON er.id_estado_ruta = hr.id_estado_ruta
        INNER JOIN geocampo_estado_visita ev
            ON ev.id_estado_visita = hrd.id_estado_visita
        INNER JOIN C_FINANCIERA_EFECTIVA_CAMPO c
            ON c.id = ga.id_cuenta_campo
        LEFT JOIN personal p
            ON p.IDPERSONAL = ga.id_asesor
        WHERE ga.activo = 1
          AND ga.id_cuenta_campo IN ({$placeholders})
          AND ga.id_asesor <> ?
          AND er.codigo NOT IN ('ANULADA', 'CERRADA')
          AND (
                hr.fecha_ruta = CURDATE()
                OR (
                    hr.fecha_ruta < CURDATE()
                    AND ev.codigo IN ('AGENDADO', 'PENDIENTE_VISITA', 'NO_VISITADO')
                )
          )
        ORDER BY hr.fecha_ruta DESC, asesor_actual, c.NUMEROCUENTA
    ";

    $rows = query_all($mysqli, $sql, $types, $params);
    $restricciones = [
        'ruta_hoy' => [],
        'pendientes_previas' => []
    ];

    foreach ($rows as $row) {
        $fechaRuta = limpiar_texto($row['fecha_ruta'] ?? '');
        if ($fechaRuta === date('Y-m-d')) {
            $restricciones['ruta_hoy'][] = $row;
        } else {
            $restricciones['pendientes_previas'][] = $row;
        }
    }

    return $restricciones;
}

function formatear_cuentas_restringidas(array $rows, int $limite = 5): string
{
    $ejemplos = [];
    foreach (array_slice($rows, 0, $limite) as $row) {
        $cuenta = limpiar_texto($row['cuenta']) ?: (string)$row['id_cuenta'];
        $cliente = limpiar_texto($row['cliente']);
        $asesor = limpiar_texto($row['asesor_actual']) ?: 'gestor actual';
        $ejemplos[] = $cliente ? "{$cuenta} - {$cliente} ({$asesor})" : "{$cuenta} ({$asesor})";
    }

    $detalle = implode('; ', $ejemplos);
    if (count($rows) > $limite) {
        $detalle .= ' y otras cuentas más';
    }
    return $detalle;
}

function validar_reasignacion_sin_hoja_ruta(mysqli $mysqli, array $ids, int $idAsesorDestino): void
{
    // Regla vigente: umbral abierto.
    // La reasignación no se bloquea por rutas, visitas o pendientes anteriores.
    // El historial se conserva en geocampo_asignacion_historial y la nueva asignación queda activa.
    return;
}

function procesar_asignacion_ids(mysqli $mysqli, array $ids, int $idAsesor, int $idSupervisor): int
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

    if (empty($ids)) {
        throw new Exception('No se encontraron cuentas para asignar.');
    }

    if ($idAsesor <= 0) {
        throw new Exception('Asesor inválido.');
    }

    // Regla de umbral abierto: se permite reasignar aunque la cuenta tenga ruta o visitas previas.
    // La ruta histórica queda como trazabilidad de la asignación anterior y la nueva asignación queda activa.

    $idEstadoPendiente = obtener_id_catalogo($mysqli, 'geocampo_estado_asignacion', 'id_estado_asignacion', 'PENDIENTE');
    $idTipoAsignacion = obtener_id_catalogo($mysqli, 'geocampo_tipo_movimiento_asignacion', 'id_tipo_movimiento', 'ASIGNACION');
    $idTipoReasignacion = obtener_id_catalogo($mysqli, 'geocampo_tipo_movimiento_asignacion', 'id_tipo_movimiento', 'REASIGNACION');

    $selectActual = $mysqli->prepare("\n        SELECT id_asignacion, id_asesor\n        FROM geocampo_asignacion\n        WHERE id_cuenta_campo = ?\n          AND activo = 1\n        ORDER BY id_asignacion DESC\n        LIMIT 1\n    ");

    $desactivarActual = $mysqli->prepare("\n        UPDATE geocampo_asignacion\n        SET activo = 0,\n            fecha_actualizacion = NOW()\n        WHERE id_asignacion = ?\n    ");

    $insertAsignacion = $mysqli->prepare("\n        INSERT INTO geocampo_asignacion (\n            id_cuenta_campo,\n            id_asesor,\n            id_supervisor,\n            id_estado_asignacion,\n            fecha_asignacion,\n            activo\n        ) VALUES (?, ?, ?, ?, NOW(), 1)\n    ");

    $insertHistorial = $mysqli->prepare("\n        INSERT INTO geocampo_asignacion_historial (\n            id_asignacion,\n            id_cuenta_campo,\n            id_asesor_anterior,\n            id_asesor_nuevo,\n            id_supervisor,\n            id_tipo_movimiento,\n            observacion,\n            fecha_movimiento\n        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())\n    ");

    if (!$selectActual || !$desactivarActual || !$insertAsignacion || !$insertHistorial) {
        throw new Exception('Error preparando sentencias de asignación: ' . $mysqli->error);
    }

    $procesadas = 0;

    foreach ($ids as $idCuentaCampo) {
        $selectActual->bind_param('i', $idCuentaCampo);
        $selectActual->execute();
        $actual = $selectActual->get_result()->fetch_assoc();

        $idAsignacionAnterior = null;
        $idAsesorAnterior = null;
        $idTipoMovimiento = $idTipoAsignacion;

        if ($actual) {
            $idAsignacionAnterior = (int)$actual['id_asignacion'];
            $idAsesorAnterior = (int)$actual['id_asesor'];
            $idTipoMovimiento = $idTipoReasignacion;

            $desactivarActual->bind_param('i', $idAsignacionAnterior);
            $desactivarActual->execute();
        }

        $insertAsignacion->bind_param('iiii', $idCuentaCampo, $idAsesor, $idSupervisor, $idEstadoPendiente);
        $insertAsignacion->execute();
        $idAsignacionNueva = (int)$mysqli->insert_id;

        $observacion = $idAsignacionAnterior
            ? 'Reasignación'
            : 'Asignación';

        $insertHistorial->bind_param(
            'iiiiiis',
            $idAsignacionNueva,
            $idCuentaCampo,
            $idAsesorAnterior,
            $idAsesor,
            $idSupervisor,
            $idTipoMovimiento,
            $observacion
        );
        $insertHistorial->execute();

        $procesadas++;
    }

    $selectActual->close();
    $desactivarActual->close();
    $insertAsignacion->close();
    $insertHistorial->close();

    return $procesadas;
}

function obtener_resumen_reasignacion(mysqli $mysqli, array $ids, int $idAsesorDestino): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

    if (empty($ids)) {
        return [
            'tieneConflictos' => false,
            'totalConflictos' => 0,
            'grupos' => []
        ];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids)) . 'i';
    $params = array_merge($ids, [$idAsesorDestino]);

    $sql = "
        SELECT
            ga.id_cuenta_campo AS id_cuenta,
            ga.id_asesor AS id_asesor_actual,
            TRIM(CONCAT(COALESCE(p.NOMBRES, ''), ' ', COALESCE(p.APELLIDOS, ''))) AS asesor_actual,
            c.NUMEROCUENTA AS cuenta,
            c.NOMBRE AS cliente
        FROM geocampo_asignacion ga
        INNER JOIN C_FINANCIERA_EFECTIVA_CAMPO c
            ON c.id = ga.id_cuenta_campo
        LEFT JOIN personal p
            ON p.IDPERSONAL = ga.id_asesor
        WHERE ga.activo = 1
          AND ga.id_cuenta_campo IN ({$placeholders})
          AND ga.id_asesor <> ?
        ORDER BY asesor_actual, c.NUMEROCUENTA
    ";

    $rows = query_all($mysqli, $sql, $types, $params);
    $grupos = [];

    foreach ($rows as $row) {
        $idActual = (int)$row['id_asesor_actual'];
        if (!isset($grupos[$idActual])) {
            $grupos[$idActual] = [
                'id_asesor_actual' => $idActual,
                'asesor_actual' => limpiar_texto($row['asesor_actual']) ?: 'Gestor sin nombre',
                'cantidad' => 0,
                'cuentas' => []
            ];
        }

        $grupos[$idActual]['cantidad']++;
        if (count($grupos[$idActual]['cuentas']) < 8) {
            $grupos[$idActual]['cuentas'][] = [
                'id_cuenta' => (int)$row['id_cuenta'],
                'cuenta' => limpiar_texto($row['cuenta']) ?: (string)$row['id_cuenta'],
                'cliente' => limpiar_texto($row['cliente'])
            ];
        }
    }

    return [
        'tieneConflictos' => count($rows) > 0,
        'totalConflictos' => count($rows),
        'grupos' => array_values($grupos)
    ];
}

function ids_filtrados(mysqli $mysqli): void
{
    $filtros = obtener_filtros_desde_array($_GET);
    $ids = obtener_ids_por_filtros($mysqli, $filtros);

    responder_json([
        'ok' => true,
        'ids' => $ids,
        'total' => count($ids)
    ]);
}

function preview_reasignacion(mysqli $mysqli): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responder_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
    }

    $idAsesor = (int)($input['asesorId'] ?? 0);
    if ($idAsesor <= 0) {
        responder_json(['ok' => false, 'message' => 'Asesor inválido.'], 400);
    }

    $ids = [];
    if (!empty($input['usarFiltros'])) {
        $filtros = obtener_filtros_desde_array($input['filtros'] ?? []);
        $ids = obtener_ids_por_filtros($mysqli, $filtros);
    } else {
        $ids = $input['ids'] ?? [];
    }

    // Umbral abierto: el preview solo informa si la cuenta ya pertenece a otro gestor.
    // No bloquea por ruta, visita o pendiente anterior.
    $resumen = obtener_resumen_reasignacion($mysqli, $ids, $idAsesor);

    $totalEvaluadas = count(array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0))));

    responder_json(array_merge([
        'ok' => true,
        'totalEvaluadas' => $totalEvaluadas
    ], $resumen));
}

function asignar(mysqli $mysqli): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responder_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
    }

    $ids = $input['ids'] ?? [];
    $idAsesor = (int)($input['asesorId'] ?? 0);
    $idSupervisor = (int)$_SESSION['id'];

    try {
        $mysqli->begin_transaction();
        $procesadas = procesar_asignacion_ids($mysqli, $ids, $idAsesor, $idSupervisor);
        $mysqli->commit();

        responder_json([
            'ok' => true,
            'message' => "Se asignaron {$procesadas} cuenta(s) correctamente.",
            'procesadas' => $procesadas
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        responder_json([
            'ok' => false,
            'message' => 'No se pudo completar la asignación.',
            'error' => $e->getMessage()
        ], 500);
    }
}

function asignar_filtradas(mysqli $mysqli): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        responder_json(['ok' => false, 'message' => 'JSON inválido.'], 400);
    }

    $idAsesor = (int)($input['asesorId'] ?? 0);
    $filtros = obtener_filtros_desde_array($input['filtros'] ?? []);
    $idSupervisor = (int)$_SESSION['id'];

    try {
        $ids = obtener_ids_por_filtros($mysqli, $filtros);

        $mysqli->begin_transaction();
        $procesadas = procesar_asignacion_ids($mysqli, $ids, $idAsesor, $idSupervisor);
        $mysqli->commit();

        responder_json([
            'ok' => true,
            'message' => "Se asignaron {$procesadas} cuenta(s) filtrada(s) correctamente.",
            'procesadas' => $procesadas
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        responder_json([
            'ok' => false,
            'message' => 'No se pudo completar la asignación filtrada.',
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
        ids_filtrados($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview_reasignacion') {
        preview_reasignacion($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'asignar') {
        asignar($mysqli);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'asignar_filtradas') {
        asignar_filtradas($mysqli);
    }

    responder_json(['ok' => false, 'message' => 'Acción no válida.'], 404);
} catch (Throwable $e) {
    responder_json([
        'ok' => false,
        'message' => 'Error interno del API.',
        'error' => $e->getMessage()
    ], 500);
}
