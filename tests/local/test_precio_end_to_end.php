<?php

/**
 * Prueba PUNTA A PUNTA del PRECIO de la actividad, tal como lo manda la app:
 *
 *   Empaque (Api\VinetaRegistroController@store)
 *      -> HTTP a facturacion_plasencia (127.0.0.1:8082)
 *         -> ERP local (erp_taosa_mock, MySQL)
 *
 * Verifica que en el ERP quede el precio del catalogo de Empaque
 * (actividades.precio_mo) y NO el 0.075 viejo.
 *
 * Requiere facturacion local arriba:
 *   cd ../facturaccion_plasencia && php artisan serve --port=8082
 *
 *   php tests/local/test_precio_end_to_end.php
 */

$base = __DIR__.'/../../';
require $base.'vendor/autoload.php';
$app = require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\VinetaRegistroController as ApiRegistros;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Http\Request;

$API_KEY = 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d';

config([
    'services.erp.base_url' => 'http://127.0.0.1:8082/api',
    'services.erp.api_key' => $API_KEY,
    'services.erp.dry_run' => false,
]);

$u = App\Models\User::query()->first();
if ($u) {
    auth()->login($u);
}

// Conexion directa SOLO para respaldar/verificar el ERP local
$pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=erp_taosa_mock;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$TABLAS = ['Produccion_EmpaquesDiariaG', 'Produccion_EmpaquesDiariaG_Actividades', 'Produccion_EmpaquesDiariaG_Detalles', 'Produccion_OrdenTrabajoPlaneacionEmpaques'];
$backup = [];
foreach ($TABLAS as $t) {
    $backup[$t] = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
}

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

$DOC = '0000011085';
$SUC = 2;
$EMP = '1128';

try {
    // Precio del catalogo de Empaque para la actividad 132
    $actividad = DB::table('actividades')->where('codigo_actividad', '132')->first();
    $precioCatalogo = (float) ($actividad->precio_mo ?? 0);

    chk('la actividad 132 tiene precio en Empaque', $precioCatalogo > 0, (string) $precioCatalogo);

    // Viñeta "normal" (como la que escanea la app)
    $apiId = random_int(8800000, 8899999);
    $vineta = Vineta::create([
        'api_id' => $apiId,
        'fecha' => '2026-11-03',
        'cantidad_puros' => 250,
        'estado' => 'activo',
        'impreso' => true,
        'item' => '11606678',
        'codigo_producto' => 'P-23994',
        'marca' => 'PRUEBA PRECIO',
        'nombre' => 'PRUEBA',
        'capa' => 'X',
        'vitola' => 'X',
        'tipo_empaque' => 'MAZOS 1/25',
        'orden_del_sistema' => '3593',
        'orden' => 'PRECIO-1',
        'mes' => 'NOVIEMBRE',
    ]);

    echo "== Registrar desde la app (Empaque -> facturacion -> ERP) ==\n";

    $request = Request::create('/api/vineta-registros', 'POST', [
        'vineta_id' => $vineta->id,
        'modo_registro' => 'por_tarea',
        'actividad_codigo' => '132',
        'actividad_nombre' => (string) $actividad->nombre,
        'actividad_tipo_empaque' => 'MAZOS 1/25',
        'empleado_codigo' => $EMP,
        'empleado_nombre' => 'CALDERON GRISELDA DEL CARMEN',
        'cantidad_puros' => 250,
        'cantidad_actividades' => 250,
        'minutos_trabajados' => 60,
        'fecha_registro' => '2026-11-03',
        'hora_registro' => '09:00:00',
        'documento_numero' => $DOC,
        'sucursal' => $SUC,
    ]);

    $api = app(ApiRegistros::class);
    $resp = $api->store($request, $vineta);
    $body = json_decode((string) $resp->getContent(), true) ?: [];

    $registro = VinetaRegistro::where('vineta_id', $vineta->id)->latest('id')->first();

    chk('el registro se crea', $registro !== null, 'HTTP '.$resp->getStatusCode());
    chk('queda marcado como enviado al ERP', $registro && (bool) $registro->erp_enviado, var_export($registro?->erp_enviado, true));
    // Se guarda con 4 decimales (igual que la columna del ERP).
    chk('el registro guarda el precio de la actividad', $registro && abs((float) $registro->precio_mo - round($precioCatalogo, 4)) < 0.00001, (string) $registro?->precio_mo);

    echo "\n== Que quedo grabado en el ERP ==\n";

    $st = $pdo->prepare('SELECT Numero, CodigoActividad, CodigoEmpleado, Precio_Unitario, CantidadTrabajadas, HorasTrabajadas FROM Produccion_EmpaquesDiariaG_Detalles WHERE CodigoSucursal = ? AND Numero_de_Documento = ? AND CodigoEmpleado = ? AND CodigoActividad = 132 ORDER BY Numero');
    $st->execute([$SUC, $DOC, $EMP]);
    $filas = $st->fetchAll(PDO::FETCH_ASSOC);

    chk('el ERP tiene la fila del empleado', $filas !== [], count($filas).' fila(s)');

    $precioErp = $filas === [] ? 0.0 : (float) $filas[0]['Precio_Unitario'];

    printf("  catalogo Empaque: %.7f | ERP: %.4f\n", $precioCatalogo, $precioErp);

    chk('el ERP recibe el precio del catalogo (no 0.075)', abs($precioErp - round($precioCatalogo, 4)) < 0.0002, (string) $precioErp);
    chk('el precio NO es el viejo 0.0750', abs($precioErp - 0.0750) > 0.0001, (string) $precioErp);
    chk('los puros llegan completos', (int) $filas[0]['CantidadTrabajadas'] === 250, (string) ($filas[0]['CantidadTrabajadas'] ?? ''));
} catch (Throwable $e) {
    echo "\nEXCEPCION: ".$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()."\n";
    $fallos[] = 'excepcion';
} finally {
    // Restaurar ERP local
    foreach (array_reverse($TABLAS) as $t) {
        $pdo->exec("DELETE FROM `$t`");

        if (! empty($backup[$t])) {
            $cols = array_keys($backup[$t][0]);
            $colsSql = '`'.implode('`, `', $cols).'`';
            $marcas = '('.implode(',', array_fill(0, count($cols), '?')).')';
            $ins = $pdo->prepare("INSERT INTO `$t` ($colsSql) VALUES $marcas");

            foreach ($backup[$t] as $fila) {
                $ins->execute(array_values($fila));
            }
        }
    }

    // Borrar lo creado en Empaque
    if (isset($vineta)) {
        VinetaRegistro::where('vineta_id', $vineta->id)->delete();
        Vineta::where('id', $vineta->id)->delete();
    }

    echo "\n(ERP local y Empaque restaurados)\n";
    echo 'PASAN: '.$ok.' | FALLAN: '.count($fallos)."\n";
}

exit($fallos === [] ? 0 : 1);
