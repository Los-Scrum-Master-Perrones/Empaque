<?php

/**
 * Prueba LOCAL del modulo "Ordenes" de Empaque:
 *
 *   1) API nueva de facturacion  -> GET /api/empaque/ordenes (todas, con ceros)
 *   2) genera viñetas por area   -> li-N (LIMPIEZA) / pr-N (PRENSADO)
 *   3) escanea y valida que SOLO salgan las actividades de su area
 *   4) registra la viñeta con un documento -> se manda al ERP (mock 8099)
 *
 * Requiere:
 *   php -S 127.0.0.1:8099 tests/local/mock_erp/router.php
 *   php -S 127.0.0.1:8082 -t ../facturaccion_plasencia/public
 *
 *   php tests/local/test_ordenes_vinetas.php
 *
 * NO toca el ERP real: usa el mock y borra todo lo que crea al final.
 */

$base = __DIR__.'/../../';
require $base.'vendor/autoload.php';
$app = require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\VinetaController as ApiVinetas;
use App\Http\Controllers\Api\VinetaRegistroController as ApiRegistros;
use App\Http\Controllers\OrdenEmpaqueController;
use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use App\Models\VinetaRegistro;
use App\Services\OrdenEmpaqueService;
use App\Services\VinetaAreaService;
use Illuminate\Http\Request;

$u = App\Models\User::query()->first();
if ($u) {
    auth()->login($u);
}

$API_KEY = 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d';

config([
    'services.programaciones.api_url' => 'http://127.0.0.1:8082/api',
    'services.programaciones.api_key' => $API_KEY,
    'services.erp.base_url' => 'http://127.0.0.1:8099/api',
    'services.erp.api_key' => $API_KEY,
    'services.erp.dry_run' => false,
]);

$ok = 0;
$fallos = [];

function chk(string $que, bool $cond, string $det = ''): void
{
    global $ok, $fallos;

    if ($cond) {
        $ok++;
        echo "  PASS  $que".($det !== '' ? "  [$det]" : '')."\n";
    } else {
        $fallos[] = $que;
        echo "  FAIL  $que".($det !== '' ? "  [$det]" : '')."\n";
    }
}

$creados = ['vinetas_por_orden' => [], 'vinetas' => [], 'registros' => []];

try {
    // ------------------------------------------------------------ 1. API
    echo "== 1. API de facturacion: TODAS las ordenes ==\n";

    $servicio = app(OrdenEmpaqueService::class);
    $servicio->refrescar();
    $ordenes = $servicio->getOrdenes(true);

    chk('la API responde y trae ordenes', $ordenes->count() > 0, $ordenes->count().' filas');
    chk('el error de la API queda vacio', $servicio->ultimoError() === null, (string) $servicio->ultimoError());
    chk('trae ordenes con saldo', $ordenes->where('sin_saldo', false)->count() > 0, $ordenes->where('sin_saldo', false)->count().' con saldo');
    chk('trae ordenes en cero (tachadas)', $ordenes->where('sin_saldo', true)->count() > 0, $ordenes->where('sin_saldo', true)->count().' en cero');

    $conSaldo = $ordenes->firstWhere('sin_saldo', false);

    chk('la orden elegida tiene datos del producto', $conSaldo && $conSaldo->orden_del_sistema !== '' && $conSaldo->marca !== '', $conSaldo ? 'OS '.$conSaldo->orden_del_sistema.' · '.$conSaldo->marca : 'sin fila');

    if (! $conSaldo) {
        throw new RuntimeException('No hay ordenes con saldo para probar.');
    }

    // -------------------------------------------------- 2. GENERAR VIÑETAS
    echo "\n== 2. Generar viñetas por area (exclusivas) ==\n";

    $areas = app(VinetaAreaService::class);

    $pendiente = [
        'id_pendiente' => $conSaldo->id_pendiente,
        'item' => $conSaldo->item,
        'codigo_producto' => $conSaldo->codigo_producto,
        'marca' => $conSaldo->marca,
        'nombre' => $conSaldo->nombre,
        'vitola' => $conSaldo->vitola,
        'capa' => $conSaldo->capa,
        'tipo_empaque' => $conSaldo->tipo_empaque,
        'orden_del_sistema' => $conSaldo->orden_del_sistema,
        'orden' => $conSaldo->orden,
        'mes' => $conSaldo->mes,
    ];

    $limpieza = $areas->generar($pendiente, 'LIMPIEZA', 2, 200);
    chk('genero 2 viñetas de LIMPIEZA', $limpieza['ok'] && count($limpieza['codigos']) === 2, implode(', ', $limpieza['codigos']));

    $prensado = $areas->generar($pendiente, 'PRENSADO', 1, 150);
    chk('genero 1 viñeta de PRENSADO', $prensado['ok'] && count($prensado['codigos']) === 1, implode(', ', $prensado['codigos']));

    $codigoLimpieza = $limpieza['codigos'][0] ?? null;
    $codigoPrensado = $prensado['codigos'][0] ?? null;

    // Se registran TODAS para borrarlas al final (si queda una, ensucia la base).
    foreach (array_merge($limpieza['codigos'] ?? [], $prensado['codigos'] ?? []) as $codigo) {
        $creados['vinetas_por_orden'][] = VinetaPorOrden::where('codigo_qr', $codigo)->value('id');
        $creados['vinetas'][] = Vineta::where('id_pendiente_empaque', $codigo)->value('id');
    }

    $vpo = VinetaPorOrden::where('codigo_qr', $codigoLimpieza)->first();

    chk('la viñeta queda con area LIMPIEZA', $vpo && $vpo->area === 'LIMPIEZA', (string) $vpo?->area);
    chk('la viñeta queda ligada a la orden', $vpo && (string) $vpo->id_pendiente_empaque === (string) $conSaldo->id_pendiente, (string) $vpo?->id_pendiente_empaque);
    chk('la viñeta queda con 200 puros', $vpo && (int) $vpo->cantidad_puros === 200, (string) $vpo?->cantidad_puros);
    chk('el codigo es li-N', $vpo && preg_match('/^li-\d+$/', (string) $vpo->codigo_qr) === 1, (string) $vpo?->codigo_qr);

    $vpoPren = VinetaPorOrden::where('codigo_qr', $codigoPrensado)->first();
    chk('el codigo de prensado es pr-N', $vpoPren && preg_match('/^pr-\d+$/', (string) $vpoPren->codigo_qr) === 1, (string) $vpoPren?->codigo_qr);

    $espejo = Vineta::where('id_pendiente_empaque', $codigoLimpieza)->first();
    chk('se espeja en vinetas para el escaneo', $espejo !== null, (string) $espejo?->id_pendiente_empaque);
    chk('el espejo tambien lleva el area', $espejo && $espejo->area === 'LIMPIEZA', (string) $espejo?->area);

    // ---------------------------------------------------------- 3. ESCANEO
    echo "\n== 3. Escaneo normal (mismo QR) ==\n";

    $apiVinetas = app(ApiVinetas::class);
    $scan = $apiVinetas->scan(Request::create('/api/vinetas/scan', 'GET', ['qr' => (string) $codigoLimpieza]));
    $scanBody = json_decode((string) $scan->getContent(), true);

    chk('el escaneo encuentra la viñeta', $scan->getStatusCode() === 200 && ($scanBody['vineta']['codigo_qr'] ?? '') === $codigoLimpieza, 'HTTP '.$scan->getStatusCode());
    chk('el escaneo devuelve el area', ($scanBody['vineta']['area'] ?? null) === 'LIMPIEZA', (string) ($scanBody['vineta']['area'] ?? 'null'));

    $vineta = $espejo;
    $acts = $apiVinetas->actividades($vineta);
    $actsBody = json_decode((string) $acts->getContent(), true);

    chk('las actividades salen del AREA', ($actsBody['source'] ?? '') === 'area', (string) ($actsBody['source'] ?? ''));

    $codigosArea = array_map(fn ($a) => (string) $a['codigo_actividad'], $actsBody['activities'] ?? []);
    $permitidas = config('empaque_areas.LIMPIEZA.actividades', []);
    sort($codigosArea);
    $esperadas = array_map('strval', $permitidas);
    sort($esperadas);

    chk('solo muestra las actividades de LIMPIEZA (exclusivas)', $codigosArea === $esperadas, implode(',', $codigosArea));
    chk('NO muestra la 111 (brocha) en limpieza', ! in_array('111', $codigosArea, true));

    // ------------------------------------------- 4. REGISTRAR -> ERP MOCK
    echo "\n== 4. Registrar la viñeta con documento (se manda al ERP mock) ==\n";

    $log = storage_path('app/mock_erp/erp_mock_log.jsonl');
    $lineasAntes = is_file($log) ? count(array_filter(explode("\n", (string) file_get_contents($log)))) : 0;

    $documento = '0000011215';
    $empleado = '1128';

    $apiRegistros = app(ApiRegistros::class);
    $request = Request::create('/api/vineta-registros', 'POST', [
        'vineta_id' => $vineta->id,
        'modo_registro' => 'por_tarea',
        'actividad_codigo' => '103',
        'actividad_nombre' => 'Limpieza de Puros',
        'actividad_tipo_empaque' => $conSaldo->tipo_empaque,
        'empleado_codigo' => $empleado,
        'empleado_nombre' => 'PRUEBA ORDENES',
        'cantidad_puros' => 200,
        'cantidad_actividades' => 200,
        'minutos_trabajados' => 60,
        'fecha_registro' => '2026-11-03',
        'hora_registro' => '09:00:00',
        'documento_numero' => $documento,
        'sucursal' => 2,
    ]);

    $respuesta = $apiRegistros->store($request, $vineta);
    $respBody = json_decode((string) $respuesta->getContent(), true);

    $registro = VinetaRegistro::where('vineta_id', $vineta->id)->where('estado', 'activo')->latest('id')->first();
    $creados['registros'][] = $registro?->id;

    chk('el registro se crea', $registro !== null, 'HTTP '.$respuesta->getStatusCode());
    chk('queda con el documento del ERP', $registro && (string) $registro->documento_numero === $documento, (string) $registro?->documento_numero);
    chk('queda marcado como enviado al ERP', $registro && (bool) $registro->erp_enviado, var_export($registro?->erp_enviado, true));

    $nuevas = 0;
    $ultimo = null;

    if (is_file($log)) {
        $lineas = array_values(array_filter(explode("\n", (string) file_get_contents($log))));

        if (count($lineas) > $lineasAntes) {
            foreach (array_slice($lineas, $lineasAntes) as $linea) {
                $fila = json_decode($linea, true);

                if (($fila['metodo'] ?? '') === 'POST' && ($fila['ruta'] ?? '') === 'registrar-vineta-erp') {
                    $nuevas++;
                    $ultimo = $fila;
                }
            }
        }
    }

    chk('se mando a registrar-vineta-erp en el ERP', $nuevas > 0, $nuevas.' POST');

    $datosErp = $ultimo['datos'] ?? [];
    $vinetaErp = $datosErp['vineta'] ?? [];

    chk('el ERP recibe el documento', ($datosErp['documento'] ?? '') === $documento, (string) ($datosErp['documento'] ?? ''));
    chk('el ERP recibe la sucursal', (int) ($datosErp['sucursal'] ?? 0) === 2, (string) ($datosErp['sucursal'] ?? ''));
    chk('el ERP recibe la actividad 103', (string) ($vinetaErp['codigo_actividad'] ?? '') === '103', (string) ($vinetaErp['codigo_actividad'] ?? ''));
    chk('el ERP recibe los 200 puros', (int) ($vinetaErp['cantidad_puros'] ?? 0) === 200, (string) ($vinetaErp['cantidad_puros'] ?? ''));
    chk('el ERP recibe el empleado', (string) ($vinetaErp['empleado_codigo'] ?? '') === $empleado, (string) ($vinetaErp['empleado_codigo'] ?? ''));
    chk('el ERP recibe la orden del sistema', (string) ($vinetaErp['orden_del_sistema'] ?? '') !== '', (string) ($vinetaErp['orden_del_sistema'] ?? ''));
    chk('el ERP recibe el codigo de producto', (string) ($vinetaErp['codigo_producto'] ?? '') !== '', (string) ($vinetaErp['codigo_producto'] ?? ''));

    // ------------------------------------- 5. EDITAR (cantidad y horas)
    echo "\n== 5. Editar la viñeta (cantidad y horas) -> ERP ==\n";

    $logEdit = is_file($log) ? count(array_filter(explode("\n", (string) file_get_contents($log)))) : 0;

    $reqEdit = Request::create('/api/vineta-registros/'.$registro->id, 'PUT', [
        'fecha_registro' => '2026-11-03',
        'hora_registro' => '09:00:00',
        'cantidad_puros' => 350,
        'cantidad_actividades' => 350,
        'minutos_trabajados' => 90,
        'empleado_codigo' => $empleado,
        'modo_registro' => 'por_tarea',
        'codigo_actividad' => '103',
        'actividad_nombre' => 'Limpieza de Puros',
        'actividad_tipo_empaque' => $conSaldo->tipo_empaque,
        'documento_numero' => $documento,
        'sucursal' => 2,
    ]);

    $respEdit = $apiRegistros->update($reqEdit, $registro->fresh());
    $registro->refresh();

    chk('la edicion responde sin error', $respEdit->getStatusCode() < 400, 'HTTP '.$respEdit->getStatusCode());
    chk('guarda los 350 puros', (int) $registro->cantidad_puros === 350, (string) $registro->cantidad_puros);
    chk('guarda los 90 minutos', (int) $registro->minutos_trabajados === 90, (string) $registro->minutos_trabajados);

    $llamadasEdit = [];

    if (is_file($log)) {
        $lineas = array_values(array_filter(explode("\n", (string) file_get_contents($log))));

        foreach (array_slice($lineas, $logEdit) as $linea) {
            $fila = json_decode($linea, true);

            if (($fila['metodo'] ?? '') === 'POST') {
                $llamadasEdit[] = $fila;
            }
        }
    }

    $rutasEdit = array_map(fn ($f) => (string) ($f['ruta'] ?? ''), $llamadasEdit);
    chk('se avisa al ERP del cambio (ajustar/mover)', $rutasEdit !== [], implode(', ', $rutasEdit));

    $ajuste = null;

    foreach ($llamadasEdit as $fila) {
        if (($fila['ruta'] ?? '') === 'ajustar-horas-vineta') {
            $ajuste = $fila['datos'] ?? [];
        }
    }

    if ($ajuste !== null) {
        chk('el ERP recibe +150 puros', (int) ($ajuste['delta_puros'] ?? 0) === 150, (string) ($ajuste['delta_puros'] ?? ''));
        chk('el ERP recibe +0.5 h', abs((float) ($ajuste['delta_horas'] ?? 0) - 0.5) < 0.001, (string) ($ajuste['delta_horas'] ?? ''));
        chk('el ajuste va con el documento', (string) ($ajuste['documento'] ?? '') === $documento, (string) ($ajuste['documento'] ?? ''));
    } else {
        chk('se mando el ajuste de horas al ERP', false, 'no hubo ajustar-horas-vineta');
    }

    // ------------------------------------------- 6. LA VIÑETA SALE EN /vinetas
    echo "\n== 6. La viñeta aparece en el listado normal ==\n";

    $listaCtrl = app(App\Http\Controllers\VinetaController::class);
    $reqLista = Request::create('/vinetas', 'GET', ['buscar' => $codigoLimpieza]);
    $lista = $listaCtrl->index($reqLista);
    $htmlLista = $lista->render();

    chk('aparece en /vinetas como las demas', str_contains($htmlLista, (string) $codigoLimpieza), (string) $codigoLimpieza);

    // ------------------------------------------------- 7. ELIMINAR
    echo "\n== 7. Eliminar el registro ==\n";

    $web = app(App\Http\Controllers\VinetaRegistroController::class);
    $web->destroy($registro);

    chk('el registro se borra', VinetaRegistro::find($registro->id) === null, 'id '.$registro->id);

    // ------------------------------------------------- 8. PANTALLA ORDENES
    echo "\n== 8. Pantalla /ordenes ==\n";

    $ctrl = app(OrdenEmpaqueController::class);
    $reqIndex = Request::create('/ordenes', 'GET', ['per_page' => 5]);
    $reqIndex->setLaravelSession(app('session')->driver());
    $vista = $ctrl->index($reqIndex);

    chk('la pantalla responde', $vista instanceof Illuminate\View\View, get_class($vista));
    chk('la vista tiene las areas', isset($vista->getData()['areas']) && count($vista->getData()['areas']) >= 8, (string) count($vista->getData()['areas'] ?? []));
    chk('la vista tiene ordenes paginadas', ($vista->getData()['ordenes'] ?? null) instanceof Illuminate\Pagination\LengthAwarePaginator);

    $render = $vista->render();
    chk('el HTML muestra el boton de viñetas', str_contains($render, 'btn-generar-vinetas-orden'));
    chk('el HTML muestra el modal con las areas', str_contains($render, 'generar_area'));
    chk('el HTML marca las de cero', str_contains($render, 'EN CERO') || $vista->getData()['totalEnCero'] === 0, 'en cero: '.($vista->getData()['totalEnCero'] ?? '?'));
    chk('la vista trae el conteo de pendientes de imprimir', array_key_exists('pendientesImpresion', $vista->getData()), (string) ($vista->getData()['pendientesImpresion'] ?? ''));
    chk('la viñeta generada queda pendiente de imprimir', ($vista->getData()['pendientesImpresion'] ?? 0) >= 3, (string) ($vista->getData()['pendientesImpresion'] ?? 0));
} catch (Throwable $e) {
    echo "\nEXCEPCION: ".$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
    $fallos[] = 'excepcion';
} finally {
    // Limpieza: deja la base como estaba.
    foreach ($creados['registros'] as $id) {
        if ($id) {
            VinetaRegistro::where('id', $id)->delete();
        }
    }

    foreach ($creados['vinetas'] as $id) {
        if ($id) {
            Vineta::where('id', $id)->delete();
        }
    }

    foreach ($creados['vinetas_por_orden'] as $id) {
        if ($id) {
            VinetaPorOrden::where('id', $id)->delete();
        }
    }

    echo "\n===============================\n";
    echo 'PASAN: '.$ok.' | FALLAN: '.count($fallos)."\n";

    if ($fallos !== []) {
        echo 'FALLARON: '.implode(' | ', $fallos)."\n";
    }
}

exit($fallos === [] ? 0 : 1);
