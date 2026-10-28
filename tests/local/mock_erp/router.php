<?php

/**
 * ERP local de mentira (mock) para probar TODO el flujo de Empaque sin
 * tocar el ERP de verdad (192.168.0.11 / 192.168.2.7).
 *
 *   php -S 127.0.0.1:8099 router.php
 *
 * - GET  /api/empaque-erp/todo   -> sirve las tablas espejo de Empaque
 * - POST /api/<cualquiera>       -> valida X-Api-Key, guarda la peticion en
 *                                   erp_mock_log.jsonl y responde como el ERP
 * - documento que no existe      -> error 404 documento_no_existe
 */

$API_KEY = 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d';
$BASE = dirname(__DIR__, 3).'/storage/app/mock_erp'; // log y estado (storage, sin versionar)
if (! is_dir($BASE)) {
    @mkdir($BASE, 0775, true);
}

$LOG = $BASE.'/erp_mock_log.jsonl';
$ESTADO = $BASE.'/erp_mock_estado.json';   // forma_pago + horas simuladas

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Api-Key, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$recurso = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cuerpo = file_get_contents('php://input');

// Autenticacion
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== $API_KEY) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'api_key', 'message' => 'Clave invalida.']);
    exit;
}

function leerEstado(string $archivo): array
{
    if (! is_file($archivo)) {
        return [];
    }

    $datos = json_decode((string) file_get_contents($archivo), true);

    return is_array($datos) ? $datos : [];
}

function guardarLog(string $archivo, array $fila): void
{
    @file_put_contents($archivo, json_encode($fila, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND | LOCK_EX);
}

function guardarEstado(string $archivo, array $estado): void
{
    @file_put_contents($archivo, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$esApi = str_starts_with($recurso, '/api/');
$ruta = $esApi ? substr($recurso, strlen('/api/')) : $recurso;

// ---------------------------------------------------------------- GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $ruta === 'empaque-erp/todo') {
    $fecha = $_GET['fecha'] ?? null;
    $sucursal = (int) ($_GET['sucursal'] ?? 2);

    if (! $fecha) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Falta la fecha.']);
        exit;
    }

    // Los datos salen de las tablas espejo de Empaque (lo que ya se bajo del
    // ERP real): asi el comparativo local trabaja con informacion real.
    $dsn = 'mysql:host=127.0.0.1;port=3308;dbname=facturuacion_empaque_taopar;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $q = function (string $sql, array $params) use ($pdo) {
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    };

    $documentos = $q('SELECT sucursal, numero, fecha, area, nombre_area, observaciones, aplicada, anulado
        FROM erp_empaques_documentos WHERE sucursal = ? AND fecha = ?', [$sucursal, $fecha]);
    $nums = array_column($documentos, 'numero');

    $actividades = [];
    $detalles = [];

    if ($nums !== []) {
        $marcas = implode(',', array_fill(0, count($nums), '?'));
        $actividades = $q("SELECT sucursal, numero, actividad, nombre_actividad, forma_pago, bodega, bodega_material, ultima
            FROM erp_empaques_actividades WHERE sucursal = ? AND numero IN ($marcas)", array_merge([$sucursal], $nums));
        $detalles = $q("SELECT sucursal, numero, actividad, orden, empleado, nombre_empleado, linea, producto, sku,
            puros, pagadas, control_calidad, horas, precio, salario, bodega, tipo_empaque
            FROM erp_empaques_detalles WHERE sucursal = ? AND numero IN ($marcas)", array_merge([$sucursal], $nums));
    }

    $planificacion = $q('SELECT sucursal, orden, linea, fecha, actividad, a_producir, producida, personal, ultima
        FROM erp_empaques_planificacion WHERE sucursal = ? AND fecha = ?', [$sucursal, $fecha]);

    guardarLog($LOG, [
        'en' => date('c'),
        'metodo' => 'GET',
        'ruta' => $ruta,
        'params' => $_GET,
        'respuesta' => ['documentos' => count($documentos), 'detalles' => count($detalles)],
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Datos del ERP (mock).',
        'fecha' => $fecha,
        'sucursal' => $sucursal,
        'documentos' => $documentos,
        'actividades' => $actividades,
        'detalles' => $detalles,
        'planificacion' => $planificacion,
    ]);
    exit;
}

// ---------------------------------------------------------------- POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode($cuerpo ?: '[]', true);
    $datos = is_array($datos) ? $datos : [];

    $documento = trim((string) ($datos['documento'] ?? ''));
    $estado = leerEstado($ESTADO);
    $dryRun = (bool) ($datos['dry_run'] ?? false);

    guardarLog($LOG, [
        'en' => date('c'),
        'metodo' => 'POST',
        'ruta' => $ruta,
        'dry_run' => $dryRun,
        'datos' => $datos,
    ]);

    // Documento que no existe -> mismo error que el ERP real.
    if ($documento !== '' && ($ruta === 'fijar-horas-vineta' || $ruta === 'fijar-horas-empleado-documento' || $ruta === 'documento-empaque-forma-pago')) {
        $dsn = 'mysql:host=127.0.0.1;port=3308;dbname=facturuacion_empaque_taopar;charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $st = $pdo->prepare('SELECT COUNT(*) FROM erp_empaques_documentos WHERE numero = ?');
            $st->execute([$documento]);
            $existe = (int) $st->fetchColumn() > 0;
        } catch (Throwable) {
            $existe = true; // si no se puede consultar, no se inventa un error
        }

        if (! $existe) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'documento_no_existe',
                'message' => 'El documento no existe en el ERP.',
            ]);
            exit;
        }
    }

    // Fija horas de un empleado en un documento (como el ERP real: reparto
    // en centesimas de hora proporcional a los puros).
    if ($ruta === 'fijar-horas-empleado-documento') {
        $doc = str_pad($documento, 10, '0', STR_PAD_LEFT);
        $emp = trim((string) ($datos['empleado_codigo'] ?? ''));
        $horas = round((float) ($datos['horas'] ?? 0), 2);
        $clave = $doc.'|'.$emp;

        if (! $dryRun) {
            $ds = 'mysql:host=127.0.0.1;port=3308;dbname=facturuacion_empaque_taopar;charset=utf8mb4';
            $pdo2 = new PDO($ds, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $st = $pdo2->prepare('SELECT numero, actividad, orden, empleado, puros FROM erp_empaques_detalles WHERE sucursal = ? AND numero = ? AND empleado = ?');
            $st->execute([(int) ($datos['sucursal'] ?? 2), $doc, $emp]);
            $filas = $st->fetchAll(PDO::FETCH_ASSOC);

            if ($filas !== []) {
                $totalCent = (int) round($horas * 100);
                $purosTotal = array_sum(array_column($filas, 'puros'));
                $reparto = [];
                $asignado = 0;

                foreach ($filas as $i => $fila) {
                    $parte = $purosTotal > 0
                        ? (int) floor($totalCent * ((float) $fila['puros'] / $purosTotal))
                        : (int) floor($totalCent / count($filas));
                    $reparto[$i] = $parte;
                    $asignado += $parte;
                }

                $resto = $totalCent - $asignado;

                if ($resto > 0) {
                    $orden = array_keys($filas);
                    usort($orden, fn ($a, $b) => (float) $filas[$b]['puros'] <=> (float) $filas[$a]['puros']);
                    $pos = 0;
                    while ($resto > 0) {
                        $reparto[$orden[$pos % count($orden)]]++;
                        $resto--;
                        $pos++;
                    }
                }

                foreach ($filas as $i => $fila) {
                    $estado['horas'][$clave.'|'.(int) $fila['numero']] = round($reparto[$i] / 100, 2);
                }
                guardarEstado($ESTADO, $estado);
            }
        }

        echo json_encode([
            'success' => true,
            'cambiado' => ! $dryRun,
            'dry_run' => $dryRun,
            'message' => $dryRun ? 'Simulación: no se modificó el ERP.' : 'Horas del empleado fijadas en el ERP.',
            'resultado' => ['documento' => $doc, 'empleado' => $emp, 'horas' => $horas, 'filas' => 0],
        ]);
        exit;
    }

    if ($ruta === 'fijar-horas-vineta' || $ruta === 'ajustar-horas-vineta') {
        echo json_encode([
            'success' => true,
            'cambiado' => ! $dryRun,
            'insertado' => false,
            'dry_run' => $dryRun,
            'message' => $dryRun ? 'Simulación: no se modificó el ERP.' : 'Horas y puros fijados en el ERP.',
        ]);
        exit;
    }

    if ($ruta === 'documento-empaque-forma-pago') {
        $doc = str_pad($documento, 10, '0', STR_PAD_LEFT);
        $forma = (int) ($datos['forma_pago'] ?? 2);

        if (! $dryRun) {
            $estado['forma_pago'][$doc] = $forma;

            if (! empty($datos['horas_cero'])) {
                foreach (array_keys($estado['horas'] ?? []) as $k) {
                    if (str_starts_with($k, $doc.'|')) {
                        $estado['horas'][$k] = 0.0;
                    }
                }
            }

            guardarEstado($ESTADO, $estado);
        }

        echo json_encode([
            'success' => true,
            'cambiado' => ! $dryRun,
            'dry_run' => $dryRun,
            'message' => $dryRun ? 'Simulación: no se modificó el ERP.' : 'Forma de pago actualizada en el ERP.',
        ]);
        exit;
    }

    if ($ruta === 'mover-actividad-vineta' || $ruta === 'registrar-vineta-erp') {
        echo json_encode([
            'success' => true,
            'cambiado' => ! $dryRun,
            'insertado' => $ruta === 'registrar-vineta-erp',
            'dry_run' => $dryRun,
            'message' => $dryRun ? 'Simulación: no se modificó el ERP.' : 'OK (mock).',
        ]);
        exit;
    }

    // Ruta desconocida: se contesta igual que el ERP real (error)
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'ruta_no_existe', 'message' => 'La ruta no existe en el ERP (mock).']);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Recurso no encontrado (mock).']);
