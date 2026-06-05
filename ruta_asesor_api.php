<?php

/**
 * API GEOCAMPO - Ruta semanal del asesor
 * Acciones:
 * - inicial: carteras disponibles y semana actual
 * - cuentas_ruta: cuentas que el asesor puso en ruta en la semana
 * - info_cliente: información dinámica del cliente según gui_table
 * - historial_gestion: estructura base del historial de gestiones
 * - visitas_cliente: cantidad de visitas por mes
 */
date_default_timezone_set('America/Lima');
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['id']) && empty($_GET['id_usuario']) && empty($_POST['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Sesión no válida o expirada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/config.php';
$mysqli->set_charset('utf8');

function responder_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function limpiar_texto($valor): string
{
    return trim((string)($valor ?? ''));
}

function query_all(mysqli $mysqli, string $sql, string $types = '', array $params = []): array
{
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta.');
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
        $id = (int)($_SESSION['id_ruta_asesor'] ?? $_SESSION['id'] ?? 0);
    }
    if ($id <= 0) {
        throw new Exception('No se recibió un asesor válido.');
    }
    return $id;
}

function validar_fecha(string $fecha): string
{
    $fecha = limpiar_texto($fecha) ?: date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new Exception('Fecha inválida.');
    }
    return $fecha;
}

function obtener_rango_semana(string $fechaReferencia): array
{
    $fechaReferencia = validar_fecha($fechaReferencia);
    $dt = new DateTime($fechaReferencia);
    $inicio = clone $dt;
    $inicio->modify('monday this week');
    $fin = clone $inicio;
    $fin->modify('+6 days');
    return [
        'fecha_referencia' => $dt->format('Y-m-d'),
        'fecha_inicio' => $inicio->format('Y-m-d'),
        'fecha_fin' => $fin->format('Y-m-d'),
        'etiqueta' => $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y'),
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

    if (!$rows) {
        return null;
    }

    return [
        'id_periodo' => (int)$rows[0]['id_periodo'],
        'codigo' => limpiar_texto($rows[0]['codigo']),
        'nombre' => limpiar_texto($rows[0]['nombre']),
        'fecha_inicio' => limpiar_texto($rows[0]['fecha_inicio']),
        'fecha_fin' => limpiar_texto($rows[0]['fecha_fin']),
    ];
}

function normalizar_paginacion(): array
{
    $page = (int)($_GET['page'] ?? $_POST['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? $_POST['per_page'] ?? 25);

    if ($page < 1) {
        $page = 1;
    }

    if (!in_array($perPage, [10, 25, 50, 100], true)) {
        $perPage = 25;
    }

    return [$page, $perPage, ($page - 1) * $perPage];
}

function validar_nombre_tabla(string $tableName): string
{
    $tableName = limpiar_texto($tableName);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
        throw new Exception('La tabla de cartera no es válida.');
    }
    return $tableName;
}

function obtener_carteras_asesor(mysqli $mysqli, int $idUsuario): array
{
    return query_all($mysqli, "
        SELECT
            cartera.cartera AS nombre,
            tabla_log.id AS id_table,
            tabla_log.id_cartera AS idcartera,
            cartera.tipo AS tipo_cartera
        FROM tabla_log
        INNER JOIN asignacion_tabla
            ON tabla_log.id = asignacion_tabla.id_tabla
        INNER JOIN cartera
            ON tabla_log.id_cartera = cartera.id
        INNER JOIN cliente
            ON cartera.idcliente = cliente.id
        WHERE asignacion_tabla.id_usuario = ?
          AND cartera.estado = 1
          AND tabla_log.estado = 0
        ORDER BY cartera.cartera
    ", 'i', [$idUsuario]);
}

function obtener_cartera_seleccionada(mysqli $mysqli, int $idUsuario, int $idTableSolicitado = 0): array
{
    $carteras = obtener_carteras_asesor($mysqli, $idUsuario);
    if (!$carteras) {
        throw new Exception('No tienes carteras de campo asignadas.');
    }

    if ($idTableSolicitado > 0) {
        foreach ($carteras as $cartera) {
            if ((int)$cartera['id_table'] === $idTableSolicitado) {
                return $cartera;
            }
        }
    }

    return $carteras[0];
}

function obtener_nombre_tabla(mysqli $mysqli, int $idTable): string
{
    $rows = query_all($mysqli, "SELECT nombre FROM tabla_log WHERE id = ? LIMIT 1", 'i', [$idTable]);
    if (!$rows) {
        throw new Exception('No se encontró la tabla de la cartera.');
    }
    return validar_nombre_tabla($rows[0]['nombre']);
}

function obtener_columnas_gui(mysqli $mysqli, int $idTable): array
{
    $rows = query_all($mysqli, "
        SELECT campo, alias, color, type, width, orden
        FROM gui_table
        WHERE id_table = ?
        ORDER BY orden ASC, id ASC
    ", 'i', [$idTable]);

    $columnas = [];
    foreach ($rows as $row) {
        $field = limpiar_texto($row['campo']);
        if ($field === '') {
            continue;
        }
        $columnas[] = [
            'field' => $field,
            'header' => limpiar_texto($row['alias']) ?: $field,
            'type' => limpiar_texto($row['type']) ?: 'TEXT',
            'width' => (int)($row['width'] ?: 130),
            'color' => limpiar_texto($row['color'] ?? ''),
        ];
    }
    return $columnas;
}

function normalizar_estado(string $codigo, string $descripcion): array
{
    $codigo = strtoupper(limpiar_texto($codigo) ?: 'PENDIENTE');
    $descripcion = limpiar_texto($descripcion) ?: 'Pendiente';
    $clase = 'bg-gray-100 text-gray-700 border-gray-200';
    if (in_array($codigo, ['VISITADO', 'GESTIONADO', 'CERRADO'], true)) {
        $clase = 'bg-green-50 text-green-700 border-green-200';
    } elseif (in_array($codigo, ['PENDIENTE', 'CREADA'], true)) {
        $clase = 'bg-amber-50 text-amber-700 border-amber-200';
    } elseif (in_array($codigo, ['REPROGRAMADO'], true)) {
        $clase = 'bg-blue-50 text-blue-700 border-blue-200';
    }
    return ['codigo' => $codigo, 'descripcion' => $descripcion, 'clase' => $clase];
}

function listar_cuentas_ruta(mysqli $mysqli, int $idUsuario, int $idTable, string $fechaReferencia, string $busqueda = '', int $page = 1, int $perPage = 25): array
{
    $semana = obtener_rango_semana($fechaReferencia);
    $periodo = obtener_periodo_por_fecha($mysqli, $semana['fecha_referencia']);
    $cartera = obtener_cartera_seleccionada($mysqli, $idUsuario, $idTable);
    $idTable = (int)$cartera['id_table'];
    $idCartera = (int)$cartera['idcartera'];
    $tableName = obtener_nombre_tabla($mysqli, $idTable);
    $tabla = "`{$tableName}`";
    $busqueda = limpiar_texto($busqueda);

    $where = "
        WHERE hr.id_asesor = ?
          AND COALESCE(hr.tipo_ruta, 'DIARIA') = 'SEMANAL'
          AND COALESCE(tb.ESTADO, 'ACTIVO') = 'ACTIVO'
    ";
    $typesBase = 'i';
    $paramsBase = [$idUsuario];

    if ($periodo) {
        $where .= "
          AND (
                (hr.id_periodo IS NOT NULL AND hr.id_periodo = ?)
                OR (hr.id_periodo IS NULL AND hr.fecha_inicio_semana = ? AND hr.fecha_fin_semana = ?)
              )
        ";
        $typesBase .= 'iss';
        $paramsBase[] = (int)$periodo['id_periodo'];
        $paramsBase[] = $semana['fecha_inicio'];
        $paramsBase[] = $semana['fecha_fin'];
    } else {
        $where .= "
          AND hr.fecha_inicio_semana = ?
          AND hr.fecha_fin_semana = ?
        ";
        $typesBase .= 'ss';
        $paramsBase[] = $semana['fecha_inicio'];
        $paramsBase[] = $semana['fecha_fin'];
    }

    if ($busqueda !== '') {
        $like = '%' . $busqueda . '%';
        $where .= "
          AND (
                tb.IDENTIFICADOR LIKE ?
                OR COALESCE(tb.DOCUMENTO, '') LIKE ?
                OR COALESCE(tb.NOMBRE, '') LIKE ?
                OR COALESCE(tb.NUMEROCUENTA, '') LIKE ?
              )
        ";
        $typesBase .= 'ssss';
        array_push($paramsBase, $like, $like, $like, $like);
    }

    $from = "
        FROM geocampo_hoja_ruta hr
        INNER JOIN geocampo_hoja_ruta_detalle hrd
            ON hrd.id_hoja_ruta = hr.id_hoja_ruta
        INNER JOIN geocampo_asignacion ga
            ON ga.id_asignacion = hrd.id_asignacion
           AND ga.activo = 1
           AND ga.id_asesor = hr.id_asesor
        INNER JOIN {$tabla} tb
            ON tb.id = ga.id_cuenta_campo
        LEFT JOIN geocampo_estado_visita ev
            ON ev.id_estado_visita = hrd.id_estado_visita
    ";

    $rowsResumen = query_all($mysqli, "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN COALESCE(ev.codigo, 'PENDIENTE') IN ('VISITADO', 'GESTIONADO', 'CERRADO') THEN 1 ELSE 0 END) AS visitadas,
            SUM(CASE WHEN COALESCE(ev.codigo, 'PENDIENTE') NOT IN ('VISITADO', 'GESTIONADO', 'CERRADO') THEN 1 ELSE 0 END) AS pendientes
        {$from}
        {$where}
    ", $typesBase, $paramsBase);

    $total = (int)($rowsResumen[0]['total'] ?? 0);
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $typesData = 'i' . $typesBase . 'ii';
    $paramsData = array_merge([$idCartera], $paramsBase, [$perPage, $offset]);

    $sql = "
        SELECT
            tb.id AS id_cuenta,
            tb.IDENTIFICADOR AS identificador,
            COALESCE(tb.DOCUMENTO, tb.IDENTIFICADOR) AS documento,
            COALESCE(tb.NOMBRE, '') AS cliente,
            COALESCE(tb.NUMEROCUENTA, '') AS cuenta,
            ga.id_asignacion,
            hrd.id_detalle,
            hrd.orden_visita,
            hrd.fecha_agendada,
            hrd.fecha_visita,
            COALESCE(ev.codigo, 'PENDIENTE') AS estado_codigo,
            COALESCE(ev.descripcion, 'Pendiente') AS estado_descripcion,
            COALESCE(v.cantidad_visitas, 0) AS cantidad_visitas
        {$from}
        LEFT JOIN (
            SELECT IDENTIFICADOR, COUNT(*) AS cantidad_visitas
            FROM GEOCAMPO
            WHERE IDCARTERA = ?
            GROUP BY IDENTIFICADOR
        ) v
            ON v.IDENTIFICADOR = tb.IDENTIFICADOR
        {$where}
        ORDER BY DATE(hrd.fecha_agendada) ASC, hrd.orden_visita ASC, hrd.id_detalle ASC
        LIMIT ? OFFSET ?
    ";

    $rows = query_all($mysqli, $sql, $typesData, $paramsData);
    $cuentas = [];
    foreach ($rows as $row) {
        $estado = normalizar_estado($row['estado_codigo'], $row['estado_descripcion']);
        $cuentas[] = [
            'id_cuenta' => (int)$row['id_cuenta'],
            'id_asignacion' => (int)$row['id_asignacion'],
            'id_detalle' => (int)$row['id_detalle'],
            'identificador' => limpiar_texto($row['identificador']),
            'documento' => limpiar_texto($row['documento']),
            'cliente' => limpiar_texto($row['cliente']) ?: 'Sin nombre',
            'cuenta' => limpiar_texto($row['cuenta']),
            'orden_visita' => (int)$row['orden_visita'],
            'fecha_agendada' => limpiar_texto($row['fecha_agendada']),
            'fecha_visita' => limpiar_texto($row['fecha_visita']),
            'estado' => $estado,
            'cantidad_visitas' => (int)$row['cantidad_visitas'],
            'id_table' => $idTable,
            'idcartera' => $idCartera,
        ];
    }

    $fromRow = $total > 0 ? $offset + 1 : 0;
    $toRow = $total > 0 ? min($offset + count($cuentas), $total) : 0;

    return [
        'semana' => $semana,
        'periodo' => $periodo,
        'cartera' => $cartera,
        'cuentas' => $cuentas,
        'resumen' => [
            'total' => $total,
            'visitadas' => (int)($rowsResumen[0]['visitadas'] ?? 0),
            'pendientes' => (int)($rowsResumen[0]['pendientes'] ?? 0),
        ],
        'paginacion' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $fromRow,
            'to' => $toRow,
            'q' => $busqueda,
        ],
    ];
}

function obtener_info_cliente(mysqli $mysqli, int $idUsuario, int $idTable, string $identificador): array
{
    $cartera = obtener_cartera_seleccionada($mysqli, $idUsuario, $idTable);
    $idTable = (int)$cartera['id_table'];
    $tableName = obtener_nombre_tabla($mysqli, $idTable);
    $columnas = obtener_columnas_gui($mysqli, $idTable);
    $tabla = "`{$tableName}`";

    $rows = query_all($mysqli, "
        SELECT *
        FROM {$tabla}
        WHERE IDENTIFICADOR = ?
          AND ESTADO = 'ACTIVO'
        LIMIT 1
    ", 's', [$identificador]);

    $registro = $rows[0] ?? [];
    if (!$columnas && $registro) {
        foreach (array_keys($registro) as $campo) {
            if (count($columnas) >= 40) break;
            $columnas[] = ['field' => $campo, 'header' => $campo, 'type' => 'TEXT', 'width' => 130, 'color' => ''];
        }
    }

    $valores = [];
    foreach ($columnas as $col) {
        $field = $col['field'];
        $valores[] = [
            'field' => $field,
            'header' => $col['header'],
            'type' => $col['type'],
            'width' => $col['width'],
            'value' => $registro[$field] ?? '',
        ];
    }

    return [
        'cartera' => $cartera,
        'table_name' => $tableName,
        'identificador' => $identificador,
        'columnas' => $columnas,
        'valores' => $valores,
        'registro' => $registro,
    ];
}

function normalizar_categoria_historial($idCategoria, string $categoria): array
{
    $id = (int)($idCategoria ?? 0);
    $cat = strtoupper(limpiar_texto($categoria));

    if ($id === 5 || strpos($cat, 'DIRECTO') !== false) {
        return ['codigo' => 'CD', 'nombre' => 'Contacto directo', 'clase' => 'bg-green-50 text-green-700 border-green-200'];
    }

    if ($id === 11 || strpos($cat, 'INDIRECTO') !== false) {
        return ['codigo' => 'CI', 'nombre' => 'Contacto indirecto', 'clase' => 'bg-blue-50 text-blue-700 border-blue-200'];
    }

    if ($id === 10 || strpos($cat, 'NO CONTACTO') !== false) {
        return ['codigo' => 'NC', 'nombre' => 'No contacto', 'clase' => 'bg-red-50 text-red-700 border-red-200'];
    }

    return ['codigo' => 'OTROS', 'nombre' => $categoria !== '' ? $categoria : 'Sin categoría', 'clase' => 'bg-gray-50 text-gray-700 border-gray-200'];
}

function formatear_item_historial(array $row, string $fuente): array
{
    $fecha = limpiar_texto($row['fecha_raw'] ?? '');
    $hora = limpiar_texto($row['hora_raw'] ?? '');
    if ($hora === '' && $fecha !== '') {
        $hora = date('H:i:s', strtotime($fecha));
    }

    $categoria = normalizar_categoria_historial($row['IDCATEGORIA'] ?? 0, limpiar_texto($row['CATEGORIA'] ?? ''));
    $esCampo = $fuente === 'CAMPO';
    $fechaPromesaRaw = limpiar_texto($row['FECHA_PROMESA'] ?? '');
    $fechaPromesa = $fechaPromesaRaw !== '' ? date('d/m/Y', strtotime($fechaPromesaRaw)) : '';

    return [
        'id' => (int)($row['id_origen'] ?? 0),
        'fuente' => $fuente,
        'fuente_label' => $esCampo ? 'Campo' : 'Call',
        'tabla_origen' => $esCampo ? 'GEOCAMPO' : 'gestion_tmk',
        'fecha' => $fecha !== '' ? date('d/m/Y', strtotime($fecha)) : '',
        'fecha_raw' => $fecha,
        'hora' => $hora,
        'idefecto' => limpiar_texto($row['IDEFECTO'] ?? ''),
        'efecto' => limpiar_texto($row['EFECTO'] ?? '') ?: limpiar_texto($row['IDEFECTO'] ?? ''),
        'idcontacto' => limpiar_texto($row['IDCONTACTO'] ?? ''),
        'contacto' => limpiar_texto($row['CONTACTO_NOMBRE'] ?? '') ?: limpiar_texto($row['IDCONTACTO'] ?? ''),
        'idmotivo' => limpiar_texto($row['IDMOTIVO'] ?? ''),
        'motivo' => limpiar_texto($row['IDMOTIVO'] ?? ''),
        'categoria' => $categoria,
        'observacion' => limpiar_texto($row['OBSERVACION'] ?? ''),
        'telefono' => limpiar_texto($row['IDTELEFONO'] ?? ''),
        'es_promesa' => (int)($row['PROMESA'] ?? 0) === 1,
        'pdp' => ((int)($row['PROMESA'] ?? 0) === 1) ? 'Promesa' : 'No',
        'monto_pdp' => limpiar_texto($row['MONTO_PROMESA'] ?? ''),
        'fecha_pdp' => $fechaPromesa,
        'extra' => [
            'nom_contacto' => limpiar_texto($row['NOMCONTACTO'] ?? ''),
            'pisos' => limpiar_texto($row['PISOS'] ?? ''),
            'puerta' => limpiar_texto($row['PUERTA'] ?? ''),
            'fachada' => limpiar_texto($row['FACHADA'] ?? ''),
            'latitud' => limpiar_texto($row['latitud'] ?? ''),
            'longitud' => limpiar_texto($row['longitud'] ?? ''),
            'id_direccion' => limpiar_texto($row['IDDIRECCION'] ?? ''),
            'id_personal' => limpiar_texto($row['IDPERSONAL'] ?? ''),
            'estado_contingencia' => limpiar_texto($row['ESTADO_CONTINGENCIA'] ?? ''),
        ]
    ];
}

function listar_historial_gestion(mysqli $mysqli, int $idUsuario, int $idTable, string $identificador): array
{
    $cartera = obtener_cartera_seleccionada($mysqli, $idUsuario, $idTable);
    $idCartera = (int)$cartera['idcartera'];
    $idTable = (int)$cartera['id_table'];

    $rowsCampo = query_all($mysqli, "
        SELECT
            G.ID AS id_origen,
            G.FECHA AS fecha_raw,
            COALESCE(G.HoraRegistro, TIME(G.FECHA)) AS hora_raw,
            G.IDEFECTO,
            E.EFECTO,
            E.IDCATEGORIA,
            E.promesa AS PROMESA,
            C.CATEGORIA,
            G.IDCONTACTO,
            CO.CONTACTO AS CONTACTO_NOMBRE,
            G.IDMOTIVO,
            G.OBSERVACION,
            G.NOMCONTACTO,
            G.PISOS,
            G.PUERTA,
            G.FACHADA,
            G.FECHA_PROMESA,
            G.MONTO_PROMESA,
            G.latitud,
            G.longitud,
            G.IDDIRECCION,
            G.IDPERSONAL,
            G.ESTADO_CONTINGENCIA,
            NULL AS IDTELEFONO
        FROM GEOCAMPO G
        LEFT JOIN efecto E
            ON E.IDEFECTO = G.IDEFECTO
        LEFT JOIN categoria C
            ON C.IDCATEGORIA = E.IDCATEGORIA
        LEFT JOIN contacto CO
            ON CO.IDCONTACTO = G.IDCONTACTO
        WHERE G.IDENTIFICADOR = ?
          AND G.IDCARTERA = ?
        ORDER BY G.FECHA DESC, G.ID DESC
    ", 'si', [$identificador, $idCartera]);

    $rowsCall = query_all($mysqli, "
        SELECT
            GT.id AS id_origen,
            GT.fecha_tmk AS fecha_raw,
            TIME(GT.fecha_tmk) AS hora_raw,
            GT.IDEFECTO,
            E.EFECTO,
            E.IDCATEGORIA,
            E.promesa AS PROMESA,
            C.CATEGORIA,
            GT.IDCONTACTO,
            CO.CONTACTO AS CONTACTO_NOMBRE,
            GT.IDMOTIVO,
            GT.OBSERVACION,
            GT.NOMCONTACTO,
            GT.PISOS,
            GT.PUERTA,
            GT.FACHADA,
            GT.fecha_promesa AS FECHA_PROMESA,
            GT.monto_promesa AS MONTO_PROMESA,
            NULL AS latitud,
            NULL AS longitud,
            GT.IDDIRECCION,
            GT.IDPERSONAL,
            GT.ESTADO_CONTINGENCIA,
            GT.IDTELEFONO
        FROM gestion_tmk GT
        LEFT JOIN efecto E
            ON E.IDEFECTO = GT.IDEFECTO
        LEFT JOIN categoria C
            ON C.IDCATEGORIA = E.IDCATEGORIA
        LEFT JOIN contacto CO
            ON CO.IDCONTACTO = GT.IDCONTACTO
        WHERE GT.IDENTIFICADOR = ?
          AND (GT.ID_CARTERA = ? OR GT.id_table = ?)
        ORDER BY GT.fecha_tmk DESC, GT.id DESC
    ", 'sii', [$identificador, $idCartera, $idTable]);

    $items = [];
    foreach ($rowsCampo as $row) {
        $items[] = formatear_item_historial($row, 'CAMPO');
    }
    foreach ($rowsCall as $row) {
        $items[] = formatear_item_historial($row, 'CALL');
    }

    usort($items, function ($a, $b) {
        $fa = strtotime($a['fecha_raw'] ?: '1970-01-01 00:00:00');
        $fb = strtotime($b['fecha_raw'] ?: '1970-01-01 00:00:00');
        if ($fa === $fb) {
            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        }
        return $fb <=> $fa;
    });

    $resumen = [
        'CAMPO' => ['total' => 0, 'CD' => 0, 'CI' => 0, 'NC' => 0, 'OTROS' => 0],
        'CALL' => ['total' => 0, 'CD' => 0, 'CI' => 0, 'NC' => 0, 'OTROS' => 0],
    ];

    foreach ($items as $item) {
        $fuente = $item['fuente'];
        $cat = $item['categoria']['codigo'];
        $resumen[$fuente]['total']++;
        if (!isset($resumen[$fuente][$cat])) {
            $resumen[$fuente][$cat] = 0;
        }
        $resumen[$fuente][$cat]++;
    }

    return ['items' => $items, 'resumen' => $resumen];
}

function listar_visitas_cliente(mysqli $mysqli, int $idUsuario, int $idTable, string $identificador): array
{
    $cartera = obtener_cartera_seleccionada($mysqli, $idUsuario, $idTable);
    $idCartera = (int)$cartera['idcartera'];

    $rows = query_all($mysqli, "
        SELECT
            DATE_FORMAT(FECHA, '%Y-%m') AS periodo,
            COUNT(*) AS cantidad
        FROM GEOCAMPO
        WHERE IDENTIFICADOR = ?
          AND IDCARTERA = ?
        GROUP BY DATE_FORMAT(FECHA, '%Y-%m')
        ORDER BY periodo DESC
    ", 'si', [$identificador, $idCartera]);

    return ['items' => $rows];
}

try {
    $action = limpiar_texto($_GET['action'] ?? $_POST['action'] ?? '');
    $idUsuario = obtener_id_usuario();
    $_SESSION['id_ruta_asesor'] = $idUsuario;

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'inicial') {
        $fecha = validar_fecha($_GET['fecha'] ?? date('Y-m-d'));
        $carteras = obtener_carteras_asesor($mysqli, $idUsuario);
        $cartera = $carteras ? obtener_cartera_seleccionada($mysqli, $idUsuario, (int)($_GET['id_table'] ?? 0)) : null;
        responder_json([
            'ok' => true,
            'id_usuario' => $idUsuario,
            'semana' => obtener_rango_semana($fecha),
            'carteras' => $carteras,
            'cartera_seleccionada' => $cartera,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'cuentas_ruta') {
        $idTable = (int)($_GET['id_table'] ?? 0);
        $fecha = validar_fecha($_GET['fecha'] ?? date('Y-m-d'));
        [$page, $perPage] = normalizar_paginacion();
        $busqueda = limpiar_texto($_GET['q'] ?? '');
        $data = listar_cuentas_ruta($mysqli, $idUsuario, $idTable, $fecha, $busqueda, $page, $perPage);
        responder_json(['ok' => true] + $data);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'info_cliente') {
        $idTable = (int)($_GET['id_table'] ?? 0);
        $identificador = limpiar_texto($_GET['identificador'] ?? '');
        if ($identificador === '') {
            throw new Exception('No se recibió el cliente.');
        }
        responder_json(['ok' => true, 'data' => obtener_info_cliente($mysqli, $idUsuario, $idTable, $identificador)]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'historial_gestion') {
        $idTable = (int)($_GET['id_table'] ?? 0);
        $identificador = limpiar_texto($_GET['identificador'] ?? '');
        if ($identificador === '') {
            throw new Exception('No se recibió el cliente.');
        }
        responder_json(['ok' => true, 'data' => listar_historial_gestion($mysqli, $idUsuario, $idTable, $identificador)]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'visitas_cliente') {
        $idTable = (int)($_GET['id_table'] ?? 0);
        $identificador = limpiar_texto($_GET['identificador'] ?? '');
        if ($identificador === '') {
            throw new Exception('No se recibió el cliente.');
        }
        responder_json(['ok' => true, 'data' => listar_visitas_cliente($mysqli, $idUsuario, $idTable, $identificador)]);
    }

    responder_json(['ok' => false, 'message' => 'Acción no válida.'], 400);
} catch (Throwable $e) {
    responder_json(['ok' => false, 'message' => $e->getMessage()], 500);
}
