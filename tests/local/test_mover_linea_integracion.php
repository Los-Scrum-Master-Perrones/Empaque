<?php

/**
 * PRUEBA DE INTEGRACION (local): editar una viñeta que quedo mezclada en la
 * linea de otra orden del cliente.
 *
 *   Empaque API update -> facturacion local (8082) -> ERP mock (erp_taosa_mock)
 *
 * El ERP mock se siembra con: linea 125 = OC INT-H-1472 y 175 = OC INT-H-1476,
 * y una fila mala de 520 puros en la 125 (las dos viñetas sumadas).
 *
 * Requiere la facturacion local arriba en 8082.
 * Al final deja el ERP mock y Empaque como estaban.
 */

$base = __DIR__.'/../../';
require $base.'vendor/autoload.php';
$app = require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\VinetaRegistroController as Api;
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

$pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=erp_taosa_mock;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$TABLAS = ['Produccion_EmpaquesDiariaG', 'Produccion_EmpaquesDiariaG_Actividades', 'Produccion_EmpaquesDiariaG_Detalles', 'Produccion_OrdenTrabajoPlaneacionEmpaques', 'Facturas_Detalles', 'Produccion_OrdenTrabajo'];
$backup = [];
foreach ($TABLAS as $t) {
    $backup[$t] = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
}

$ok = 0;
$fallos = [];

function chk(string $q, bool $c, string $d = ''): void
{
    global $ok, $fallos;

    if ($c) {
        $ok++;
        echo "  PASS  $q".($d !== '' ? "  [$d]" : '')."\n";
    } else {
        $fallos[] = $q;
        echo "  FAIL  $q".($d !== '' ? "  [$d]" : '')."\n";
    }
}

$DOC = '0000011215';
$ORD = '0000003610';
$EMP = '3764';
$ACT = 132;
$SKU = '47801651';
$PROD = 'P-23956';
$OC = 'INT-H-1476';

try {
    // Lineas de la orden en el ERP
    $pdo->exec("INSERT INTO `Produccion_OrdenTrabajo` (`Numero_de_Documento`,`CodigoSucursal`,`NumDocProviene`) VALUES ('$ORD',2,'0000007925')");

    foreach ([[125, 'INT-H-1472'], [175, 'INT-H-1476']] as [$l, $oc]) {
        $pdo->exec("INSERT INTO `Facturas_Detalles` (`CodigoSucursal`,`Numero_de_Documento`,`Numero`,`NumeroOrden`,`CodigoSKU`,`Codigo_Producto`) VALUES (2,'0000007925',$l,'$oc','$SKU','$PROD')");
    }

    // Documento + fila mala (520 = las dos viñetas en la 125)
    $pdo->exec("INSERT INTO `Produccion_EmpaquesDiariaG` (`CodigoSucursal`,`Numero_de_Documento`,`Fecha`,`CodigoArea`,`NombreArea`,`Aplicada`,`Observaciones`,`AplicaControlCalidad`,`Anulado`) VALUES (2,'$DOC','2026-11-03',1,'PRUEBA',0,'prueba',0,0)");
    $pdo->exec("INSERT INTO `Produccion_EmpaquesDiariaG_Actividades` (`CodigoSucursal`,`Numero_de_Documento`,`CodigoActividad`,`FormaPagoManoObra`,`Bodega`,`BodegaMaterial`,`isUltimaActividad`) VALUES (2,'$DOC',$ACT,2,0,0,0)");
    $pdo->exec("INSERT INTO `Produccion_EmpaquesDiariaG_Detalles` (`CodigoSucursal`,`Numero_de_Documento`,`CodigoActividad`,`Numero_de_Orden`,`CodigoEmpleado`,`Numero`,`CantidadTrabajadas`,`CantidadPagadas`,`CantidadControlCalidad`,`HorasTrabajadas`,`CodigoSKU`,`Codigo_Producto`,`Nombre_Empleado`,`Precio_Unitario`,`SalarioMensual`,`Bodega`,`CodigoTipoEmpaque`)
        VALUES (2,'$DOC',$ACT,'$ORD','$EMP',125,520,520,520,12.00,'$SKU','$PROD','CASTILLO DULCE MARIA',0.2699,0,218,4)");

    $fila = function (int $linea) use ($pdo, $DOC, $ACT, $ORD, $EMP) {
        $st = $pdo->prepare('SELECT CantidadTrabajadas, HorasTrabajadas, Nombre_Empleado FROM Produccion_EmpaquesDiariaG_Detalles WHERE CodigoSucursal=2 AND Numero_de_Documento=? AND CodigoActividad=? AND Numero_de_Orden=? AND CodigoEmpleado=? AND Numero=?');
        $st->execute([$DOC, $ACT, $ORD, $EMP, $linea]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    };

    echo "== 0. Fila mala en el ERP ==\n";
    chk('125 tiene 520 / 12h', $fila(125) !== null && (float) $fila(125)['CantidadTrabajadas'] === 520.0, json_encode($fila(125)));
    chk('175 no existe', $fila(175) === null);

    // Viñeta en Empaque: se sabe que esta en la linea 125
    $vineta = Vineta::create([
        'api_id' => 9966666, 'fecha' => '2026-11-03', 'cantidad_puros' => 260, 'estado' => 'activo', 'impreso' => true,
        'marca' => 'PRUEBA', 'nombre' => 'PRUEBA', 'capa' => 'X', 'vitola' => 'X', 'tipo_empaque' => 'MAZOS 1/20',
        'codigo_producto' => $PROD, 'item' => $SKU, 'orden_del_sistema' => $ORD, 'orden' => $OC, 'mes' => 'X',
    ]);

    $registro = VinetaRegistro::create([
        'vineta_id' => $vineta->id,
        'modo_registro' => 'por_tarea',
        'actividad_codigo' => (string) $ACT,
        'actividad_nombre' => 'Anillo,Celofan,Cello',
        'actividad_tipo_empaque' => 'MAZOS 1/20',
        'empleado_codigo' => $EMP,
        'empleado_nombre' => 'CASTILLO DULCE MARIA',
        'cantidad_puros' => 260,
        'cantidad_actividades' => 260,
        'minutos_trabajados' => 360,
        'fecha_registro' => '2026-11-03',
        'hora_registro' => '09:00:00',
        'documento_numero' => $DOC,
        'orden_del_sistema' => $ORD,
        'orden' => $OC,
        'sucursal' => 2,
        'estado' => 'activo',
        'registrado_en' => '2026-11-03 09:00:00',
        'erp_enviado' => true,
        'erp_respuesta' => ['numero' => 125, 'success' => true],
    ]);

    echo "\n== 1. Editar la viñeta (guardar sin cambiar nada) ==\n";

    $api = app(Api::class);
    $res = $api->update(Request::create('/x', 'PUT', [
        'fecha_registro' => '2026-11-03',
        'hora_registro' => '09:00:00',
        'cantidad_puros' => 260,
        'cantidad_actividades' => 260,
        'minutos_trabajados' => 360,
        'empleado_codigo' => $EMP,
        'modo_registro' => 'por_tarea',
        'actividad_codigo' => (string) $ACT,
        'actividad_nombre' => 'Anillo,Celofan,Cello',
        'actividad_tipo_empaque' => 'MAZOS 1/20',
        'documento_numero' => $DOC,
        'sucursal' => 2,
    ]), $registro->refresh());

    $body = json_decode((string) $res->getContent(), true) ?: [];

    chk('la edicion responde 200', $res->getStatusCode() === 200, 'HTTP '.$res->getStatusCode());
    chk('el ERP responde al mover', ! empty($body['erp_mover_linea']['success']), json_encode($body['erp_mover_linea'] ?? null));

    echo "\n== 2. Como quedo el ERP ==\n";
    $f125 = $fila(125);
    $f175 = $fila(175);

    chk('125 quedo en 260 / 6h', $f125 !== null && (float) $f125['CantidadTrabajadas'] === 260.0, json_encode($f125));
    chk('175 tiene 260 / 6h', $f175 !== null && (float) $f175['CantidadTrabajadas'] === 260.0, json_encode($f175));
    chk('175 con el nombre correcto', $f175 !== null && trim((string) $f175['Nombre_Empleado']) === 'CASTILLO DULCE MARIA', (string) ($f175['Nombre_Empleado'] ?? ''));

    // La viñeta ya sabe que esta en la 175
    $registro->refresh();
    chk('la viñeta guarda la linea nueva', (int) (($registro->erp_respuesta['numero'] ?? 0)) === 175, (string) ($registro->erp_respuesta['numero'] ?? ''));

    echo "\n== 3. Volver a editar: no debe sumar otra vez ==\n";

    $api->update(Request::create('/x', 'PUT', [
        'fecha_registro' => '2026-11-03', 'hora_registro' => '09:00:00',
        'cantidad_puros' => 260, 'cantidad_actividades' => 260, 'minutos_trabajados' => 360,
        'empleado_codigo' => $EMP, 'modo_registro' => 'por_tarea',
        'actividad_codigo' => (string) $ACT, 'actividad_nombre' => 'Anillo,Celofan,Cello',
        'actividad_tipo_empaque' => 'MAZOS 1/20', 'documento_numero' => $DOC, 'sucursal' => 2,
    ]), $registro->refresh());

    chk('125 sigue en 260', (float) $fila(125)['CantidadTrabajadas'] === 260.0, json_encode($fila(125)));
    chk('175 sigue en 260 (no suma)', (float) $fila(175)['CantidadTrabajadas'] === 260.0, json_encode($fila(175)));
} catch (Throwable $e) {
    echo "\nEXCEPCION: ".$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()."\n";
    $fallos[] = 'excepcion';
} finally {
    foreach (array_reverse($TABLAS) as $t) {
        $pdo->exec("DELETE FROM `$t`");

        if (! empty($backup[$t])) {
            $cols = array_keys($backup[$t][0]);
            $colsSql = '`'.implode('`,`', $cols).'`';
            $marcas = '('.implode(',', array_fill(0, count($cols), '?')).')';
            $ins = $pdo->prepare("INSERT INTO `$t` ($colsSql) VALUES $marcas");

            foreach ($backup[$t] as $f) {
                $ins->execute(array_values($f));
            }
        }
    }

    if (isset($vineta)) {
        VinetaRegistro::where('vineta_id', $vineta->id)->forceDelete();
        Vineta::where('id', $vineta->id)->forceDelete();
    }

    echo "\n(ERP mock y Empaque restaurados)\n";
    echo 'PASAN: '.$ok.' | FALLAN: '.count($fallos)."\n";
}

exit($fallos === [] ? 0 : 1);
