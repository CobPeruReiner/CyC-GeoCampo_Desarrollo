<?php
session_name('geocampo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$inicio_total = microtime(true);

$id_tabla_raw = $_POST['id_tabla'] ?? ($_SESSION['id_tabla'] ?? '');
$dni          = $_POST['dni'] ?? '';
$id_cartera   = $_POST['idCartera'] ?? 0;
$id_usuario   = $_SESSION['id'] ?? 0;

$_SESSION['dni'] = $dni;

include('config.php');

if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function get_table_metadata(mysqli $mysqli, $id_tabla_raw)
{
    $metadata = [
        'id_table' => is_numeric($id_tabla_raw) ? (int)$id_tabla_raw : null,
        'nombre_tabla' => (string)$id_tabla_raw,
    ];

    if (is_numeric($id_tabla_raw)) {
        $stmt = $mysqli->prepare('SELECT nombre FROM tabla_log WHERE id = ? LIMIT 1');
        if ($stmt) {
            $id_table = (int)$id_tabla_raw;
            $stmt->bind_param('i', $id_table);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $metadata['nombre_tabla'] = $row['nombre'];
            }
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM tabla_log WHERE nombre = ? LIMIT 1');
        if ($stmt) {
            $nombre_tabla = (string)$id_tabla_raw;
            $stmt->bind_param('s', $nombre_tabla);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $metadata['id_table'] = (int)$row['id'];
            }
            $stmt->close();
        }
    }

    return $metadata;
}

function get_gui_config(mysqli $mysqli, $id_table)
{
    if (empty($id_table)) {
        return [];
    }

    $stmt = $mysqli->prepare(
        'SELECT campo, gui, color, alias, orden, type, width
         FROM gui_table
         WHERE id_table = ? AND gui IS NOT NULL AND campo IS NOT NULL
         ORDER BY gui ASC, orden ASC, id ASC'
    );

    if (!$stmt) {
        return [];
    }

    $id_table = (int)$id_table;
    $stmt->bind_param('i', $id_table);
    $stmt->execute();
    $result = $stmt->get_result();

    $config = [];
    while ($result && $row = $result->fetch_assoc()) {
        $row['campo'] = trim((string)$row['campo']);
        if ($row['campo'] === '') {
            continue;
        }
        $row['alias'] = trim((string)($row['alias'] ?? '')) ?: $row['campo'];
        $row['type']  = strtoupper(trim((string)($row['type'] ?? 'TEXT'))) ?: 'TEXT';
        $row['gui']   = (int)$row['gui'];
        $row['orden'] = (int)($row['orden'] ?? 1);
        $config[] = $row;
    }

    $stmt->close();
    return $config;
}

function build_row_index(array $row)
{
    $index = [];
    foreach ($row as $key => $value) {
        $index[strtolower((string)$key)] = $value;
    }
    return $index;
}

function get_row_value(array $row_index, $campo)
{
    $campo = strtolower((string)$campo);
    return array_key_exists($campo, $row_index) ? $row_index[$campo] : null;
}

function is_safe_identifier($name)
{
    return is_string($name) && preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

function quote_identifier($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}


function get_existing_table_columns(mysqli $mysqli, $table_name)
{
    if (!is_safe_identifier($table_name)) {
        return [];
    }

    $result = $mysqli->query('SHOW COLUMNS FROM ' . quote_identifier($table_name));
    if (!$result) {
        return [];
    }

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $field = (string)($row['Field'] ?? '');
        if ($field !== '') {
            $columns[strtolower($field)] = $field;
        }
    }

    return $columns;
}

function get_configured_columns(array $gui_config, array $extra_columns = [])
{
    $columns = [];
    foreach ($gui_config as $field) {
        $campo = trim((string)($field['campo'] ?? ''));
        if ($campo !== '' && is_safe_identifier($campo)) {
            $columns[strtolower($campo)] = $campo;
        }
    }

    foreach ($extra_columns as $campo) {
        $campo = trim((string)$campo);
        if ($campo !== '' && is_safe_identifier($campo)) {
            $columns[strtolower($campo)] = $campo;
        }
    }

    return array_values($columns);
}

function get_legacy_display_columns($table_name)
{
    if ($table_name === 'C_FINANCIERA_EFECTIVA_CAMPO') {
        return [
            'documento',
            'NOMBRE',
            'DPTO',
            'MONTOCAMPANA',
            'TRAMOINICIAL',
            'PRODUCTO',
            'SUBPRODUCTO',
            'FECHACASTIGO',
            'MONEDA',
            'SALDOPORPAGAR',
            'DEUDATOTAL',
            'CAPITAL'
        ];
    }

    if (in_array($table_name, ['C_PICHINCHA_DINERS_REFINANCIADOS', 'C_PICHINCHA_DINERS_NO_REFINANCIADOS', 'C_EFECTIVA_VENTA'], true)) {
        return [];
    }

    return [
        'documento',
        'NOMBRE',
        'DPTO',
        'MONTOCAMPANA',
        'CAMPANA',
        'TRAMOFINAL',
        'DIASATRASO',
        'CUOPAC',
        'CUOPAG',
        'CUOVEN',
        'CUOPEN',
        'FECHAVEN',
        'MONEDA',
        'VALORCUOTA',
        'FECHAULTPA',
        'MONTOACOBRAR',
        'SALDO',
        'DEUDATOTAL',
        'CAPITAL'
    ];
}

function column_exists_ci(array $existing_columns, $column)
{
    return isset($existing_columns[strtolower((string)$column)]);
}

function real_column_ci(array $existing_columns, $column)
{
    $key = strtolower((string)$column);
    return $existing_columns[$key] ?? null;
}

function add_select_column_if_exists(array &$select_parts, array $existing_columns, $source)
{
    $real = real_column_ci($existing_columns, $source);
    if ($real === null) {
        return false;
    }

    $expr = quote_identifier($real);
    if (!in_array($expr, $select_parts, true)) {
        $select_parts[] = $expr;
    }
    return true;
}

function build_store_equivalent_select_parts($table_name, array $existing_columns)
{
    $select_parts = [];

    // No se usan alias SQL. Se traen columnas físicas y las equivalencias se resuelven en PHP.
    if ($table_name === 'C_FINANCIERA_EFECTIVA_CAMPO') {
        foreach ([
            'id',
            'identificador',
            'documento',
            'NOMBRE',
            'NUMEROCUENTA',
            'PRODUCTO',
            'SUBPRODUCTO',
            'FECHAULTPA',
            'ENTIREPORTADAS',
            'CAPITAL',
            'DIASATRASO',
            'MONTOCAMPANA',
            'ANOCASTIGO',
            'FECHAVEN',
            'DPTO',
            'TRAMOINICIAL'
        ] as $column) {
            add_select_column_if_exists($select_parts, $existing_columns, $column);
        }
        return $select_parts;
    }

    // Equivalente a GetCuentasGeneral: la consulta ya era SELECT *.
    if (in_array($table_name, ['C_PICHINCHA_DINERS_REFINANCIADOS', 'C_PICHINCHA_DINERS_NO_REFINANCIADOS', 'C_EFECTIVA_VENTA'], true)) {
        foreach ($existing_columns as $real) {
            $select_parts[] = quote_identifier($real);
        }
        return $select_parts;
    }

    // No se usan alias SQL. Se traen columnas físicas y las equivalencias se resuelven en PHP.
    foreach ([
        'identificador',
        'PRODUCTO',
        'SUBPRODUCTO',
        'documento',
        'NOMBRE',
        'DPTO',
        'MONTOCAMPANA',
        'TRAMOINICIAL',
        'TRAMOFINAL',
        'DIASATRASO',
        'CUOPAC',
        'CUOPAG',
        'CUOVEN',
        'CUOPEN',
        'FECHAVEN',
        'MONEDA',
        'FECHAULTPA',
        'MONTOACOBRAR',
        'DEUDATOTAL',
        'CAPITAL'
    ] as $column) {
        add_select_column_if_exists($select_parts, $existing_columns, $column);
    }

    return $select_parts;
}

function normalize_date_only($value)
{
    if ($value === null || $value === '') {
        return '';
    }

    $value = (string)$value;
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $match)) {
        return $match[0];
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('Y-m-d', $timestamp);
}

function get_equivalent_value(array $row_index, $table_name, $campo)
{
    $campo_upper = strtoupper((string)$campo);

    if ($table_name === 'C_FINANCIERA_EFECTIVA_CAMPO') {
        switch ($campo_upper) {
            case 'PRODUCTO':
                return '';
            case 'SUBPRODUCTO':
                return get_row_value($row_index, 'PRODUCTO');
            case 'MONEDA':
                return normalize_date_only(get_row_value($row_index, 'FECHAULTPA'));
            case 'DEUDATOTAL':
                return get_row_value($row_index, 'ENTIREPORTADAS');
            case 'SALDOPORPAGAR':
                return get_row_value($row_index, 'DIASATRASO');
            case 'MONTOCAMPANA':
                return 0;
            case 'FECHACASTIGO':
                return normalize_date_only(get_row_value($row_index, 'FECHAVEN'));
        }
    } elseif (!in_array($table_name, ['C_PICHINCHA_DINERS_REFINANCIADOS', 'C_PICHINCHA_DINERS_NO_REFINANCIADOS', 'C_EFECTIVA_VENTA'], true)) {
        switch ($campo_upper) {
            case 'PRODUCTO':
                $producto = get_row_value($row_index, 'PRODUCTO');
                $subproducto = get_row_value($row_index, 'SUBPRODUCTO');
                return trim((string)$producto) !== '' || trim((string)$subproducto) !== ''
                    ? trim(implode(' - ', array_filter([(string)$producto, (string)$subproducto], static function ($v) { return trim($v) !== ''; })))
                    : '';
            case 'CAMPANA':
                return get_row_value($row_index, 'MONTOCAMPANA');
            case 'SALDO':
                return get_row_value($row_index, 'MONTOACOBRAR');
            case 'VALORCUOTA':
                return '';
        }
    }

    return get_row_value($row_index, $campo);
}

function field_available_for_display(array $row_index, $table_name, $campo)
{
    $campo_upper = strtoupper((string)$campo);

    if ($table_name === 'C_FINANCIERA_EFECTIVA_CAMPO') {
        $source_map = [
            'PRODUCTO' => true,
            'SUBPRODUCTO' => array_key_exists('producto', $row_index),
            'MONEDA' => array_key_exists('fechaultpa', $row_index),
            'DEUDATOTAL' => array_key_exists('entireportadas', $row_index),
            'SALDOPORPAGAR' => array_key_exists('diasatraso', $row_index),
            'MONTOCAMPANA' => true,
            'FECHACASTIGO' => array_key_exists('fechaven', $row_index),
        ];
        if (array_key_exists($campo_upper, $source_map)) {
            return (bool)$source_map[$campo_upper];
        }
    } elseif (!in_array($table_name, ['C_PICHINCHA_DINERS_REFINANCIADOS', 'C_PICHINCHA_DINERS_NO_REFINANCIADOS', 'C_EFECTIVA_VENTA'], true)) {
        $source_map = [
            'PRODUCTO' => array_key_exists('producto', $row_index) || array_key_exists('subproducto', $row_index),
            'CAMPANA' => array_key_exists('montocampana', $row_index),
            'SALDO' => array_key_exists('montoacobrar', $row_index),
            'VALORCUOTA' => true,
        ];
        if (array_key_exists($campo_upper, $source_map)) {
            return (bool)$source_map[$campo_upper];
        }
    }

    return array_key_exists(strtolower((string)$campo), $row_index);
}

function fetch_dynamic_accounts(mysqli $mysqli, $table_name, array $gui_config, $dni)
{
    if (!is_safe_identifier($table_name)) {
        return false;
    }

    $existing_columns = get_existing_table_columns($mysqli, $table_name);
    if (empty($existing_columns) || !column_exists_ci($existing_columns, 'documento')) {
        return false;
    }

    $select_parts = build_store_equivalent_select_parts($table_name, $existing_columns);
    if (empty($select_parts)) {
        return false;
    }

    $sql = 'SELECT ' . implode(', ', $select_parts) .
        ' FROM ' . quote_identifier($table_name) .
        ' WHERE ' . quote_identifier(real_column_ci($existing_columns, 'documento')) . ' = ?';

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('[CONSULTA_CUENTAS] Error preparando SELECT dinámico: ' . $mysqli->error . ' SQL=' . $sql);
        return false;
    }

    $stmt->bind_param('s', $dni);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

function gui_config_by_field(array $gui_config)
{
    $map = [];
    foreach ($gui_config as $field) {
        $campo = strtolower((string)($field['campo'] ?? ''));
        if ($campo !== '' && !isset($map[$campo])) {
            $map[$campo] = $field;
        }
    }
    return $map;
}

function first_existing_field(array $row_index, array $fields)
{
    foreach ($fields as $field) {
        $key = strtolower((string)$field);
        if (array_key_exists($key, $row_index)) {
            return $field;
        }
    }
    return null;
}

function render_corporate_assets_once()
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    echo '<style>
        .tabla-principal { border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .tabla-principal thead th { background: #ed1c24; color: #fff; border-color: #ed1c24; letter-spacing: .03em; text-transform: uppercase; }
        .tabla-principal td, .tabla-principal th { vertical-align: middle !important; }
        .detalle-celda { background: #f7f8fa; padding: 14px !important; }
        .detalle-tabla { margin: 0; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #fff; }
        .detalle-tabla tr:nth-child(even) { background: #f3f4f6; }
        .detalle-tabla tr:nth-child(odd) { background: #ffffff; }
        .detalle-label { font-weight: 700; color: #111827; width: 230px; border-right: 1px solid #e5e7eb !important; }
        .detalle-value { color: #111827; text-align: center; }
        .detalle-label-inner { display: inline-flex; align-items: center; gap: 8px; }
        .detalle-icon { width: 17px; height: 17px; color: #ed1c24; stroke-width: 2.2; }
        .btn-corporativo { background: #ed1c24 !important; border-color: #ed1c24 !important; color: #fff !important; font-weight: 600; border-radius: 8px; }
        .btn-corporativo:hover { filter: brightness(.92); color: #fff !important; }
        .btn-ver-detalle { background: #9da0a2 !important; border-color: #9da0a2 !important; color: #fff !important; border-radius: 8px; }
    </style>';
}

function icon_for_field($campo, $type = 'TEXT')
{
    $campo = strtoupper((string)$campo);
    $type = strtoupper((string)$type);

    if (strpos($campo, 'DOC') !== false || $campo === 'DNI' || $campo === 'RUC') return 'badge-check';
    if (strpos($campo, 'NOMBRE') !== false) return 'user';
    if (strpos($campo, 'DPTO') !== false || strpos($campo, 'DEPART') !== false || strpos($campo, 'PROV') !== false || strpos($campo, 'DIST') !== false) return 'map-pin';
    if (strpos($campo, 'FECHA') !== false || strpos($campo, 'F.') === 0) return 'calendar-days';
    if (strpos($campo, 'MONTO') !== false || strpos($campo, 'SALDO') !== false || strpos($campo, 'DEUDA') !== false || strpos($campo, 'CAPITAL') !== false || $type === 'NUMBER') return 'banknote';
    if (strpos($campo, 'CUO') !== false) return 'list-checks';
    if (strpos($campo, 'PRODUCT') !== false) return 'package';
    if (strpos($campo, 'TRAMO') !== false || strpos($campo, 'RANGO') !== false || strpos($campo, 'CLUSTER') !== false) return 'layers';
    return 'circle-check';
}

function format_gui_value($value, $type, $campo = '')
{
    if ($value === null || $value === '') {
        return '';
    }

    $type = strtoupper((string)$type);
    $campo_upper = strtoupper((string)$campo);
    $legacy_numeric_fields = [
        'MONTOCAMPANA', 'CAMPANA', 'SALDOPORPAGAR', 'DEUDATOTAL',
        'CAPITAL', 'SALDO', 'MONTOACOBRAR'
    ];

    if ((in_array($type, ['NUMBER', 'DECIMAL', 'MONEY', 'CURRENCY'], true) || in_array($campo_upper, $legacy_numeric_fields, true)) && is_numeric($value)) {
        return number_format((float)$value, 2, '.', ',');
    }

    return (string)$value;
}

function render_one_detail_row($campo, $label, $value, $color, $width = 130, $type = 'TEXT')
{
    $value = format_gui_value($value, $type, $campo);
    $style = '';
    if (is_string($color) && trim($color) !== '') {
        $style = ' style="background-color: ' . h(trim($color)) . ';"';
    }

    echo '<tr' . $style . '>';
    echo '<td class="detalle-label" style="width: ' . (int)$width . 'px;">';
    echo '<span class="detalle-label-inner"><i class="detalle-icon" data-lucide="' . h(icon_for_field($campo, $type)) . '"></i>' . h($label) . '</span>';
    echo '</td>';
    echo '<td class="detalle-value">' . h($value) . '</td>';
    echo '</tr>';
}

function render_dynamic_rows(array $row, array $gui_config, $table_name)
{
    if (empty($gui_config)) {
        return false;
    }

    $row_index = build_row_index($row);
    $config_map = gui_config_by_field($gui_config);
    $printed = 0;

    if ($table_name === 'C_FINANCIERA_EFECTIVA_CAMPO') {
        $display_plan = [
            ['documento'],
            ['NOMBRE'],
            ['DPTO'],
            ['MONTOCAMPANA'],
            ['TRAMOINICIAL'],
            ['PRODUCTO'],
            ['SUBPRODUCTO'],
            ['FECHACASTIGO'],
            ['MONEDA'],
            ['SALDOPORPAGAR'],
            ['DEUDATOTAL'],
            ['CAPITAL']
        ];
    } elseif (in_array($table_name, ['C_PICHINCHA_DINERS_REFINANCIADOS', 'C_PICHINCHA_DINERS_NO_REFINANCIADOS', 'C_EFECTIVA_VENTA'], true)) {
        $excluded = [
            'id',
            'numero_cuenta',
            'producto',
            'subproducto',
            'moneda',
            'saldoporpagar',
            'montocampana',
            'pordescuento',
            'nomcampana',
            'anocastigo',
            'fechacastigo',
            'rango',
            'fechaultpa',
            'agencia',
            'riesgo',
            'estado',
            'calsbs',
            'entireportadas',
            'edad',
            'estadociv',
            'ingresos',
            'fechaulti',
            'cuopac',
            'cuopag',
            'cuoven',
            'cuopen'
        ];
        $display_plan = [];
        foreach ($gui_config as $field) {
            $campo = strtolower((string)($field['campo'] ?? ''));
            if ($campo !== '' && !in_array($campo, $excluded, true)) {
                $display_plan[] = [$field['campo']];
            }
        }
    } else {
        $display_plan = [
            ['documento'],
            ['NOMBRE'],
            ['DPTO'],
            ['CAMPANA', 'MONTOCAMPANA'],
            ['TRAMOFINAL'],
            ['DIASATRASO'],
            ['CUOPAC'],
            ['CUOPAG'],
            ['CUOVEN'],
            ['CUOPEN'],
            ['FECHAVEN'],
            ['MONEDA'],
            ['VALORCUOTA'],
            ['FECHAULTPA'],
            ['SALDO', 'MONTOACOBRAR'],
            ['DEUDATOTAL'],
            ['CAPITAL']
        ];
    }

    foreach ($display_plan as $field_options) {
        $campo = null;
        foreach ($field_options as $option_field) {
            if (field_available_for_display($row_index, $table_name, $option_field)) {
                $campo = $option_field;
                break;
            }
        }
        if ($campo === null) {
            continue;
        }

        $value = get_equivalent_value($row_index, $table_name, $campo);
        $config = $config_map[strtolower((string)$campo)] ?? null;
        if ($config === null) {
            foreach ($field_options as $option_field) {
                $config_key = strtolower((string)$option_field);
                if (isset($config_map[$config_key])) {
                    $config = $config_map[$config_key];
                    break;
                }
            }
        }

        $alias = $config['alias'] ?? $campo;
        $color = $config['color'] ?? '';
        $width = $config['width'] ?? 230;
        $type = $config['type'] ?? 'TEXT';

        render_one_detail_row($campo, $alias, $value, $color, (int)$width, $type);
        $printed++;
    }

    return $printed > 0;
}

$metadata = get_table_metadata($mysqli, $id_tabla_raw);
$id_table = $metadata['id_table'];
$id_tabla = $metadata['nombre_tabla'];
$gui_config = get_gui_config($mysqli, $id_table);

$inicio_query = microtime(true);
$result = fetch_dynamic_accounts($mysqli, $id_tabla, $gui_config, $dni);

$tiempo_query = microtime(true) - $inicio_query;
error_log(sprintf('[CONSULTA_CUENTAS] %s → tiempo de query: %.3f s (DNI=%s)', $id_tabla, $tiempo_query, $dni));

if ($result && $result->num_rows > 0) {
    render_corporate_assets_once();
    echo '<table class="table table-bordered table-striped tabla-principal">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>PRODUCTO</th>';
    echo '<th>OK</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        if ($id_tabla == 'C_PICHINCHA_DINERS_REFINANCIADOS' || $id_tabla == 'C_PICHINCHA_DINERS_NO_REFINANCIADOS' || $id_tabla == 'C_EFECTIVA_VENTA') {
            $row = array_change_key_case($row, CASE_LOWER);
        }

        $row_index = build_row_index($row);
        $identificador = get_row_value($row_index, 'identificador');
        $producto = get_equivalent_value($row_index, $id_tabla, 'PRODUCTO');

        echo '<tr>';
        echo '<td>' . h($identificador) . '</td>';
        echo '<td>' . h($producto) . '</td>';
        echo '<td><button class="btn btn-ver-detalle ver-detalles-btn" data-id="' . h($identificador) . '"><i data-lucide="eye" style="width:16px;height:16px;"></i></button></td>';
        echo '</tr>';

        echo '<tr class="detalle-cuenta" style="display: none;">';
        echo '<td colspan="3" class="detalle-celda">';

        echo '<table class="table table-bordered detalle-tabla">';
        echo '<tbody>';

        render_dynamic_rows($row, $gui_config, $id_tabla);

        $url_destino = 'agregargestion2.php?id_tabla=' . urlencode($id_tabla) . '&identificador=' . urlencode((string)$identificador) . '&id_cartera=' . urlencode((string)$id_cartera);

        // ==========================================================================================================
        echo '<tr><td colspan="2" style="text-align:center; padding:16px;"><a class="btn btn-corporativo" href="' . h($url_destino) . '"><i data-lucide="plus-circle" style="width:16px;height:16px;vertical-align:-3px;margin-right:6px;"></i>Agregar Gestión</a></td></tr>';
        // ==========================================================================================================

        echo '</tbody>';
        echo '</table>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';

    echo '</table>';
} else {
    echo '<p>No se encontraron resultados.</p>';
}

echo '<script>if (window.lucide) { lucide.createIcons(); }</script>';

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}

$tiempo_total = microtime(true) - $inicio_total;
error_log(sprintf('[CONSULTA_CUENTAS] tiempo total de ejecución: %.3f s (DNI=%s, Usuario=%d)', $tiempo_total, $dni, $id_usuario));
