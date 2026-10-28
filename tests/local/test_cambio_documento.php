<?php
$base = __DIR__ . '/../../'; require $base.'/vendor/autoload.php';
$app=require $base.'/bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Http\Controllers\Api\VinetaRegistroController;
use App\Models\VinetaRegistro;
use App\Models\Vineta;
use Illuminate\Http\Request;

$u = App\Models\User::query()->first(); if ($u) auth()->login($u);
config(['services.erp.base_url' => 'http://127.0.0.1:8099/api',
        'services.erp.api_key' => 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d',
        'services.erp.dry_run' => false]);
$LOG = storage_path('app/mock_erp/erp_mock_log.jsonl');
if (is_file($LOG)) unlink($LOG);

$ctrl = app(VinetaRegistroController::class);
function leerLog(): array {
    $f = storage_path('app/mock_erp/erp_mock_log.jsonl');
    if (! is_file($f)) return [];
    return array_map(fn($l)=>json_decode($l,true), array_filter(explode("\n", trim((string) file_get_contents($f)))));
}
function probar(callable $fn): array {
    try { $r = $fn(); return [$r->getStatusCode(), (string) $r->getContent()]; }
    catch (Illuminate\Validation\ValidationException $e) { return [422, json_encode(['errors'=>$e->errors()])]; }
    catch (Throwable $e) { return [500, $e->getMessage()]; }
}
$ok=0; $fallos=[];
function chk(string $q, bool $cond, string $d=''): void { global $ok,$fallos; if($cond){$ok++;echo "  PASS  $q".($d?"  [$d]":"")."\n";}else{$fallos[]=$q;echo "  FAIL  $q".($d?"  [$d]":"")."\n";} }

$idsVineta = [];
$n = 0;
$crear = function (string $doc, bool $erpEnviado) use (&$idsVineta, &$n) {
    $n++;
    $vineta = Vineta::create([
        'api_id' => 9900000 + random_int(1, 899999),
        'fecha' => '2026-10-21',
        'cantidad_puros' => 500,
        'estado' => 'activo',
        'impreso' => false,
        'marca' => 'PRUEBA',
        'nombre' => 'PRUEBA',
        'capa' => 'X',
        'vitola' => 'X',
        'tipo_empaque' => 'MAZOS 1/20',
        'codigo_producto' => 'P-0001',
        'item' => '47604630',
        'orden_del_sistema' => '0000003593',
        'orden' => 'FTT-PRUEBA',
        'mes' => 'PRUEBA',
    ]);
    $idsVineta[] = $vineta->id;

    return VinetaRegistro::create([
        'vineta_id' => $vineta->id,
        'fecha_registro' => '2026-10-21',
        'hora_registro' => '10:00:00',
        'empleado_codigo' => '1128',
        'empleado_nombre' => 'CALDERON GRISELDA DEL CARMEN',
        'actividad_id' => 1,
        'actividad_codigo' => '2',
        'actividad_nombre' => 'Rezagado',
        'actividad_api_id' => 1,
        'producto_codigo' => 'P-0001',
        'producto_item' => '47604630',
        'orden_del_sistema' => '0000003593',
        'cantidad_puros' => 500,
        'cantidad_actividades' => 500,
        'minutos_trabajados' => 60,
        'estado' => 'activo',
        'sucursal' => 2,
        'documento_numero' => $doc,
        'erp_enviado' => $erpEnviado,
        'precio_mo' => 0.1,
        'codigo_vineta' => 'TST-'.$n,
        'registrado_en' => date('c'),
        'registrado_por_nombre' => 'TEST',
        'total_actividades' => 500,
        'total_mo' => 50,
        'modo_registro' => 'por_tarea',
        'raw_payload' => ['modo_registro' => 'por_tarea'],
    ]);
};
$edicion = fn (VinetaRegistro $r, string $docNuevo) => probar(fn () => $ctrl->update(Request::create('/x/registros/'.$r->id, 'PUT', [
    'fecha_registro' => $r->fecha_registro->format('Y-m-d'),
    'hora_registro' => '10:00:00',
    'cantidad_puros' => $r->cantidad_puros,
    'empleado_codigo' => '1128',
    'minutos_trabajados' => 60,
    'documento_numero' => $docNuevo,
    'sucursal' => 2,
]), $r));

$ids = [];
try {
    echo "== A) estaba EN el ERP (erp_enviado) y cambia de documento ==\n";
    $a = $crear('0000011105', true); $ids[] = $a->id;
    [$st, $body] = $edicion($a, '0000011106');
    $a->refresh();
    $log = leerLog();
    $quitas = array_values(array_filter($log, fn($l)=>($l['ruta']??'')==='ajustar-horas-vineta'));
    $altas  = array_values(array_filter($log, fn($l)=>($l['ruta']??'')==='registrar-vineta-erp'));
    $docQuita = $quitas ? ($quitas[count($quitas)-1]['datos']['documento'] ?? '') : '';
    $purosQuita = $quitas ? ($quitas[count($quitas)-1]['datos']['delta_puros'] ?? null) : null;
    $horasQuita = $quitas ? ($quitas[count($quitas)-1]['datos']['delta_horas'] ?? null) : null;
    $docAlta = $altas ? ($altas[count($altas)-1]['datos']['documento'] ?? '') : '';
    chk('queda en el documento nuevo', $a->documento_numero === '0000011106', 'HTTP '.$st.' doc '.$a->documento_numero);
    chk('quita del documento VIEJO (delta -500 puros / -1 h)', $docQuita === '0000011105' && (int) $purosQuita === -500 && abs((float) $horasQuita - (-1.0)) < 0.001, "doc $docQuita delta_puros ".var_export($purosQuita,true).' delta_horas '.var_export($horasQuita,true));
    chk('da de alta en el documento NUEVO', $docAlta === '0000011106', "alta $docAlta");

    echo "\n== B) sin erp_enviado pero si en el ERP (espejo) ==\n";
    $b = $crear('0000011105', false); $ids[] = $b->id;
    DB::table('erp_empaques_detalles')->insert([
        'sucursal'=>2,'numero'=>'0000011105','actividad'=>2,'orden'=>'0000003593','empleado'=>'1128',
        'linea'=>1,'producto'=>'P-0001','sku'=>'47604630','puros'=>500,'pagadas'=>500,'control_calidad'=>500,
        'horas'=>1,'precio'=>0.1,'salario'=>0,'bodega'=>218,'tipo_empaque'=>4,
        'created_at'=>date('c'),'updated_at'=>date('c'),
    ]);
    [$st, $body] = $edicion($b, '0000011107');
    $b->refresh();
    $quitas = array_values(array_filter(leerLog(), fn($l)=>($l['ruta']??'')==='ajustar-horas-vineta'));
    $docQuita = $quitas ? ($quitas[count($quitas)-1]['datos']['documento'] ?? '') : '';
    chk('lo detecta por el espejo y quita del viejo', $docQuita === '0000011105', 'quitó de '.$docQuita);
    chk('registro en el documento nuevo', $b->documento_numero === '0000011107', 'HTTP '.$st.' doc '.$b->documento_numero);

    echo "\n== C) ERP caido: NO se cambia el documento ==\n";
    $c = $crear('0000011105', true); $ids[] = $c->id;
    config(['services.erp.base_url' => 'http://127.0.0.1:1/api']);
    [$st, $body] = $edicion($c, '0000011108');
    $c->refresh();
    chk('422 y mensaje de error', $st === 422, 'HTTP '.$st.' '.substr($body,0,140));
    chk('el documento NO cambio', $c->documento_numero === '0000011105', 'doc '.$c->documento_numero);
    config(['services.erp.base_url' => 'http://127.0.0.1:8099/api']);

    echo "\n== D) documento vacio -> se asigna y se registra ==\n";
    $d = $crear('', false); $ids[] = $d->id;
    $quitasAntes = count(array_filter(leerLog(), fn($l)=>($l['ruta']??'')==='ajustar-horas-vineta'));
    [$st, $body] = $edicion($d, '0000011109');
    $d->refresh();
    $log = leerLog();
    $altas = array_values(array_filter($log, fn($l)=>($l['ruta']??'')==='registrar-vineta-erp'));
    $quitas = array_values(array_filter($log, fn($l)=>($l['ruta']??'')==='ajustar-horas-vineta'));
    chk('queda con el documento', $d->documento_numero === '0000011109', 'HTTP '.$st.' doc '.$d->documento_numero);
    chk('se registra en el ERP', $altas !== [] && ($altas[count($altas)-1]['datos']['documento'] ?? '') === '0000011109');
    chk('NO resta nada (antes no estaba en el ERP)', count(array_filter(leerLog(), fn($l)=>($l['ruta']??'')==='ajustar-horas-vineta')) === $quitasAntes, 'quitas antes '.$quitasAntes);

    echo "\n== E) mismo documento: no toca el ERP ==\n";
    $e = $crear('0000011105', true); $ids[] = $e->id;
    $antesLog = count(leerLog());
    [$st, $body] = $edicion($e, '0000011105');
    chk('sin llamadas al ERP si no cambia', count(leerLog()) === $antesLog, 'HTTP '.$st.' '.substr($body,0,80));
} finally {
    VinetaRegistro::whereIn('id', $ids)->forceDelete();
    Vineta::whereIn('id', $idsVineta)->forceDelete();
    // barrido: por si alguna corrida murio a medias
    $huerfanas = App\Models\Vineta::where('marca', 'PRUEBA')->whereBetween('api_id', [9900000, 9999999])->pluck('id');
    App\Models\VinetaRegistro::whereIn('vineta_id', $huerfanas)->delete();
    App\Models\Vineta::whereIn('id', $huerfanas)->delete();

    DB::table('erp_empaques_detalles')->where('numero','0000011105')->where('empleado','1128')->where('actividad',2)->delete();
    echo "\nlimpieza hecha\n";
}

echo "\n===============================\n";
echo "PASAN: $ok | FALLAN: ".count($fallos)."\n";
foreach ($fallos as $f) echo "  - $f\n";
exit($fallos ? 1 : 0);
