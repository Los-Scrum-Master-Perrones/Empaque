<?php

/**
 * Prueba local del CRUD de una viñeta (crear -> editar -> borrar -> borrar
 * otra vez) con el ERP simulado en 127.0.0.1:8099.
 *
 *   1) php -S 127.0.0.1:8099 tests/local/mock_erp/router.php
 *   2) php tests/local/test_crud_vineta.php
 */

$base = __DIR__ . '/../../';
require $base.'vendor/autoload.php';
$app = require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\VinetaRegistroController as ApiRegistros;
use App\Http\Controllers\VinetaRegistroController as WebRegistros;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$u = App\Models\User::query()->first();
if ($u) auth()->login($u);

config([
    'services.erp.base_url' => 'http://127.0.0.1:8099/api',
    'services.erp.api_key' => 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d',
    'services.erp.dry_run' => false,
]);

// Ficha de empaque del producto de prueba (la API la exige al registrar).
config(['database.connections.erp_mock_test' => [
    'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '3308',
    'database' => 'erp_taosa_mock', 'username' => 'root', 'password' => '',
    'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
]]);
$fichaMock = DB::connection('erp_mock_test');
Cache::flush();
$fichaProducto = 'P-0001';
$fichaActividades = [2, 103];

foreach ($fichaActividades as $act) {
    $fichaMock->table('Produccion_FichaEmpaqueAct')->updateOrInsert(
        [
            'CodigoSucursal' => 2,
            'Codigo_Producto' => $fichaProducto,
            'CodigoActividad' => $act,
            'CodigoTipoEmpaque' => 4,
            'Codigo_ProductoEmp' => 'ME-TEST',
        ],
        ['Cantidad' => 100, 'Lote' => 'MATERIAL_EMPAQUE', 'isUnidadxEmpaque' => 0]
    );
}

$LOG = storage_path('app/mock_erp/erp_mock_log.jsonl');
if (! is_file(dirname($LOG))) { @mkdir(dirname($LOG), 0775, true); }
if (is_file($LOG)) unlink($LOG);

function leerLog(): array
{
    $f = storage_path('app/mock_erp/erp_mock_log.jsonl');
    if (! is_file($f)) return [];
    return array_map(fn($l) => json_decode($l, true), array_filter(explode("\n", trim((string) file_get_contents($f)))));
}

function probar(callable $fn): array
{
    try {
        $r = $fn();
        return [$r->getStatusCode(), (string) $r->getContent()];
    } catch (ValidationException $e) {
        return [422, json_encode(['errors' => $e->errors()])];
    } catch (Throwable $e) {
        return [500, $e->getMessage()];
    }
}

$ok = 0;
$fallos = [];
function chk(string $que, bool $cond, string $det = ''): void
{
    global $ok, $fallos;
    if ($cond) { $ok++; echo "  PASS  $que".($det !== '' ? "  [$det]" : '')."\n"; }
    else { $fallos[] = $que; echo "  FAIL  $que".($det !== '' ? "  [$det]" : '')."\n"; }
}

$api = app(ApiRegistros::class);
$web = app(WebRegistros::class);

$vinetaId = random_int(9901000, 9989999);
$vineta = Vineta::create([
    'api_id' => $vinetaId,
    'fecha' => '2026-10-27',
    'cantidad_puros' => 400,
    'estado' => 'activo',
    'impreso' => false,
    'marca' => 'PRUEBA CRUD',
    'nombre' => 'PRUEBA CRUD',
    'capa' => 'X',
    'vitola' => 'X',
    'tipo_empaque' => 'MAZOS 1/20',
    'codigo_producto' => 'P-0001',
    'item' => '47604630',
    'orden_del_sistema' => '0000003593',
    'orden' => 'FTT-CRUD',
    'mes' => 'PRUEBA',
]);

$ids = [];
$borrados = [];

try {
    // ---------------------------------------------------------------- CREAR
    echo "== 1. CREAR (con documento, se registra en el ERP) ==\n";
    [$st, $body] = probar(fn () => $api->store(Request::create('/api/vineta-registros', 'POST', [
        'vineta_id' => $vineta->id,
        'modo_registro' => 'por_tarea',
        'actividad_codigo' => '2',
        'actividad_nombre' => 'Rezagado',
        'actividad_tipo_empaque' => 'MAZOS 1/20',
        'empleado_codigo' => '1128',
        'empleado_nombre' => 'CALDERON GRISELDA DEL CARMEN',
        'cantidad_puros' => 400,
        'cantidad_actividades' => 400,
        'minutos_trabajados' => 60,
        'fecha_registro' => '2026-10-27',
        'hora_registro' => '09:00:00',
        'documento_numero' => '0000011105',
        'sucursal' => 2,
    ]), $vineta));

    $registro = VinetaRegistro::where('vineta_id', $vineta->id)->where('estado', 'activo')->first();
    chk('el registro se crea', $registro !== null, "HTTP $st");
    if ($registro) {
        $ids[] = $registro->id;
        chk('queda con 400 puros y 60 min', (int) $registro->cantidad_puros === 400 && (int) $registro->minutos_trabajados === 60);
        chk('queda con el documento', $registro->documento_numero === '0000011105', $registro->documento_numero);
        chk('se marco enviado al ERP', (bool) $registro->erp_enviado, 'estado '.var_export($registro->erp_enviado, true));
    }
    $alta = array_values(array_filter(leerLog(), fn($l) => ($l['ruta'] ?? '') === 'registrar-vineta-erp'));
    chk('el ERP recibio el alta del documento 11105', $alta !== [] && ($alta[count($alta)-1]['datos']['documento'] ?? '') === '0000011105',
        $alta ? 'envio '.json_encode($alta[count($alta)-1]['datos']['documento'] ?? null) : 'sin log');

    if (! $registro) { throw new RuntimeException('sin registro no sigue la prueba'); }

    // ---------------------------------------------------------------- EDITAR
    echo "\n== 2. EDITAR puros y horas ==\n";
    [$st, $body] = probar(fn () => $api->update(Request::create('/api/vineta-registros/'.$registro->id, 'PATCH', [
        'fecha_registro' => '2026-10-27',
        'hora_registro' => '09:00:00',
        'cantidad_puros' => 550,
        'minutos_trabajados' => 120,
        'empleado_codigo' => '1128',
        'documento_numero' => '0000011105',
        'sucursal' => 2,
    ]), $registro->refresh()));

    $payload = json_decode($body, true) ?: [];
    $ajuste = $payload['erp_ajuste_horas'] ?? null;
    chk('la edicion guarda los nuevos valores', $st === 200 && (int) $registro->refresh()->cantidad_puros === 550
        && (int) $registro->refresh()->minutos_trabajados === 120, "HTTP $st");
    $log = leerLog();
    $ajustes = array_values(array_filter($log, fn($l) => ($l['ruta'] ?? '') === 'ajustar-horas-vineta'));
    $ultimo = $ajustes ? $ajustes[count($ajustes)-1]['datos'] : [];
    chk('mando el ajuste al ERP (puros +150, horas +1)', (int) ($ultimo['delta_puros'] ?? 0) === 150
        && abs((float) ($ultimo['delta_horas'] ?? 0) - 1.0) < 0.001,
        'delta_puros '.var_export($ultimo['delta_puros'] ?? null, true).' delta_horas '.var_export($ultimo['delta_horas'] ?? null, true));

    echo "\n== 3. EDITAR la actividad (mover en el ERP) ==\n";
    [$st, $body] = probar(fn () => $api->update(Request::create('/api/vineta-registros/'.$registro->id, 'PATCH', [
        'fecha_registro' => '2026-10-27',
        'hora_registro' => '09:00:00',
        'cantidad_puros' => 550,
        'minutos_trabajados' => 120,
        'empleado_codigo' => '1128',
        'actividad_codigo' => '103',
        'actividad_nombre' => 'Limpieza de Puros',
        'documento_numero' => '0000011105',
        'sucursal' => 2,
    ]), $registro->refresh()));

    $log = leerLog();
    $moves = array_values(array_filter($log, fn($l) => ($l['ruta'] ?? '') === 'mover-actividad-vineta'));
    $m = $moves ? $moves[count($moves)-1]['datos'] : [];
    chk('se movio la actividad en el ERP (destino 103)', $st === 200
        && (int) ($m['actividad_destino'] ?? 0) === 103, "HTTP $st destino ".var_export($m['actividad_destino'] ?? null, true));
    chk('el registro quedo con la actividad nueva', $registro->refresh()->actividad_codigo === '103'
        && $registro->refresh()->actividad_nombre === 'Limpieza de Puros', $registro->actividad_nombre);

    // ---------------------------------------------------------------- BORRAR
    echo "\n== 4. BORRAR ==\n";
    $llamadasAntes = count(leerLog());
    [$st, $body] = probar(fn () => $web->destroy($registro->refresh()));
    $borrados[] = $registro->id;
    chk('el registro desaparece (borrado fisico)', VinetaRegistro::find($registro->id) === null, "HTTP $st");
    chk('borrar NO toca el ERP (no manda nada)', count(leerLog()) === $llamadasAntes, 'llamadas '.$llamadasAntes.' -> '.count(leerLog()));

    echo "\n== 5. BORRAR OTRA VEZ / editar tras borrar ==\n";
    [$st, $body] = probar(fn () => $web->destroy($registro->refresh()));
    chk('volver a borrar no revienta (idempotente)', $st < 500, "HTTP $st");
    chk('el registro sigue inexistente', VinetaRegistro::find($registro->id) === null);

    [$st, $body] = probar(fn () => $api->update(Request::create('/api/vineta-registros/'.$registro->id, 'PATCH', [
        'fecha_registro' => '2026-10-27', 'hora_registro' => '09:00:00', 'cantidad_puros' => 10,
        'minutos_trabajados' => 10, 'empleado_codigo' => '1128',
    ]), $registro->refresh()));
    chk('editar tras borrar NO lo resucita', VinetaRegistro::find($registro->id) === null, "HTTP $st ".substr($body, 0, 90));
} finally {
    VinetaRegistro::whereIn('id', $ids)->forceDelete();
    VinetaRegistro::where('vineta_id', $vineta->id)->forceDelete();
    $vineta->forceDelete();
    echo "\nlimpieza hecha\n";
}

echo "\n===============================\n";
echo "PASAN: $ok | FALLAN: ".count($fallos)."\n";
foreach ($fallos as $f) echo "  - $f\n";
exit($fallos ? 1 : 0);
