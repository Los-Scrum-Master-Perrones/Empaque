<?php

/**
 * Prueba local: el boton "Aplicar" separa por ORDEN DEL CLIENTE.
 *
 *   Empaque "Aplicar" -> facturacion local (8082) -> ERP mock
 *
 * Arranque: linea 125 con 520 puros (dos OC sumadas), 175 no existe.
 * Se crean 2 viñetas (260 cada una, una por OC) y se aplica.
 * Esperado: 125 = 260 y 175 = 260 (con los nombres correctos).
 */

$base = __DIR__.'/../../';
require $base.'vendor/autoload.php';
$app = require $base.'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\DocumentoEmpaqueController;
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

try {
    $pdo->exec("INSERT INTO `Produccion_OrdenTrabajo` (`Numero_de_Documento`,`CodigoSucursal`,`NumDocProviene`) VALUES ('$ORD',2,'0000007925')");

    foreach ([[125, 'INT-H-1472'], [175, 'INT-H-1476']] as [$l, $oc]) {
        $pdo->exec("INSERT INTO `Facturas_Detalles` (`CodigoSucursal`,`Numero_de_Documento`,`Numero`,`NumeroOrden`,`CodigoSKU`,`Codigo_Producto`) VALUES (2,'0000007925',$l,'$oc','$SKU','$PROD')");
    }

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

    echo "== 0. Arranque (520 en la 125) ==\n";
    chk('125 = 520', (float) $fila(125)['CantidadTrabajadas'] === 520.0, json_encode($fila(125)));
    chk('175 no existe', $fila(175) === null);

    // Dos viñetas, una por orden del cliente (como en la realidad)
    $vinetas = [];
    $vineta = null;

    $crear = function (string $oc) use (&$vinetas, &$vineta, $DOC, $ORD, $EMP, $ACT, $PROD, $SKU) {
        $vineta = Vineta::create([
            'api_id' => 9950000 + count($vinetas) + 1, 'fecha' => '2026-11-03', 'cantidad_puros' => 260,
            'estado' => 'activo', 'impreso' => true, 'marca' => 'PRUEBA', 'nombre' => 'PRUEBA',
            'capa' => 'X', 'vitola' => 'X', 'tipo_empaque' => 'MAZOS 1/20',
            'codigo_producto' => $PROD, 'item' => $SKU, 'orden_del_sistema' => $ORD, 'orden' => $oc, 'mes' => 'X',
        ]);
        $vinetas[] = $vineta->id;

        return VinetaRegistro::create([
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
            'orden' => $oc,
            'sucursal' => 2,
            'estado' => 'activo',
            'registrado_en' => '2026-11-03 09:00:00',
            'erp_enviado' => true,
            'erp_respuesta' => ['numero' => 125, 'success' => true],
        ]);
    };

    $ids[] = $crear('INT-H-1472')->id;
    $ids[] = $crear('INT-H-1476')->id;

    echo "\n== 1. Boton Aplicar ==\n";

    $ctrl = app(DocumentoEmpaqueController::class);
    $req = Request::create('/api/documentos-empaques/'.$DOC.'/aplicar-horas-erp', 'POST', [
        'fecha' => null, 'sucursal' => 2, 'dry_run' => false,
    ]);
    $req->headers->set('Accept', 'application/json');

    $res = $ctrl->aplicarHorasErp($req, $DOC);
    $body = json_decode((string) $res->getContent(), true) ?: [];

    chk('aplicar responde ok', isset($body['aplicados']) || isset($body['detalle']), 'HTTP '.$res->getStatusCode());
    printf("  aplicados: %s | ya iguales: %s | rechazados: %s\n", $body['aplicados'] ?? '?', $body['ya_iguales'] ?? '?', $body['rechazados'] ?? '?');

    foreach (($body['detalle'] ?? []) as $d) {
        echo '  detalle: '.json_encode($d, JSON_UNESCAPED_UNICODE)."\n";
    }

    echo "\n== 2. Como quedo el ERP ==\n";
    $f125 = $fila(125);
    $f175 = $fila(175);

    chk('125 = 260', $f125 !== null && (float) $f125['CantidadTrabajadas'] === 260.0, json_encode($f125));
    chk('175 = 260', $f175 !== null && (float) $f175['CantidadTrabajadas'] === 260.0, json_encode($f175));
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

    if (isset($ids)) {
        VinetaRegistro::whereIn('id', $ids)->forceDelete();
    }

    if (isset($vinetas)) {
        Vineta::whereIn('id', $vinetas)->forceDelete();
    }

    echo "\n(ERP mock y Empaque restaurados)\n";
    echo 'PASAN: '.$ok.' | FALLAN: '.count($fallos)."\n";
}

exit($fallos === [] ? 0 : 1);
