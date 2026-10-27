<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Descarga los datos del dia del ERP (documentos, actividades, detalle y
 * planificacion) a tablas espejo locales y los compara contra lo registrado
 * en el sistema de Empaque.
 *
 * El ERP SOLO se lee (via la API de facturacion, /api/empaque-erp/todo).
 */
class ErpComparativoService
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.erp.base_url', 'http://127.0.0.1:8080/api'), '/');
        $this->apiKey = (string) config('services.erp.api_key', '');
    }

    /**
     * Descarga del ERP y guarda en las tablas espejo.
     */
    public function descargar(string $fecha, int $sucursal = 2): array
    {
        $respuesta = null;
        $ultimoError = '';

        // Reintento: a veces la API del ERP falla (timeout/rede) y la fecha
        // se queda "sin datos" aunque existan documentos.
        for ($intento = 1; $intento <= 2; $intento++) {
            try {
                $respuesta = Http::timeout(120)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'X-Api-Key' => $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl.'/empaque-erp/todo', [
                        'fecha' => $fecha,
                        'sucursal' => $sucursal,
                    ]);
            } catch (\Throwable $e) {
                $respuesta = null;
                $ultimoError = $e->getMessage();
                Log::warning("Comparativo ERP descarga (intento $intento): ".$ultimoError);

                if ($intento < 2) {
                    sleep(2);
                }

                continue;
            }

            if (! $respuesta->failed()) {
                break;
            }

            $ultimoError = 'HTTP '.$respuesta->status();

            if ($intento < 2) {
                sleep(2);
            }
        }

        if ($respuesta === null) {
            Log::error('Comparativo ERP descarga: '.$ultimoError);

            $this->registrarDescarga($fecha, $sucursal, null, false, 'No se pudo conectar con el ERP: '.$ultimoError);

            return ['success' => false, 'message' => 'No se pudo conectar con el ERP: '.$ultimoError];
        }

        $response = $respuesta;

        $data = $response->json();

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            $mensaje = is_array($data)
                ? ($data['message'] ?? 'respuesta no valida')
                : 'respuesta no valida';

            if ($response->status() === 404) {
                $mensaje = 'El ERP no tiene la ruta /api/empaque-erp/todo (HTTP 404). '
                    .'Falta subir EmpaqueErpApiController.php + routes/api.php y correr php artisan route:clear en facturacion.';
            } elseif ($response->status() === 401) {
                $mensaje = 'El ERP rechazo la clave (HTTP 401). Revisa ERP_API_KEY en el .env de Empaque.';
            }

            $this->registrarDescarga(
                $fecha,
                $sucursal,
                null,
                false,
                'El ERP respondio: '.$mensaje.' (HTTP '.$response->status().')'
            );

            return [
                'success' => false,
                'message' => 'El ERP respondio: '.$mensaje.' (HTTP '.$response->status().')',
            ];
        }

        $documentos = $data['documentos'] ?? [];
        $actividades = $data['actividades'] ?? [];
        $detalles = $data['detalles'] ?? [];
        $planificacion = $data['planificacion'] ?? [];
        $ahora = now();

        DB::transaction(function () use ($fecha, $sucursal, $documentos, $actividades, $detalles, $planificacion, $ahora) {
            $numeros = array_map(fn ($d) => (string) $d['numero'], $documentos);

            // Se reemplaza lo del dia (no se toca nada del ERP).
            DB::table('erp_empaques_documentos')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->delete();

            if ($numeros !== []) {
                DB::table('erp_empaques_actividades')->where('sucursal', $sucursal)->whereIn('numero', $numeros)->delete();
                DB::table('erp_empaques_detalles')->where('sucursal', $sucursal)->whereIn('numero', $numeros)->delete();
            }

            DB::table('erp_empaques_planificacion')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->delete();

            foreach (array_chunk($documentos, 200) as $lote) {
                DB::table('erp_empaques_documentos')->insert(array_map(fn ($d) => [
                    'sucursal' => (int) $d['sucursal'],
                    'numero' => (string) $d['numero'],
                    'fecha' => $d['fecha'],
                    'area' => $d['area'] ?? null,
                    'nombre_area' => $d['nombre_area'] ?? null,
                    'observaciones' => $d['observaciones'] ?? null,
                    'aplicada' => (bool) ($d['aplicada'] ?? false),
                    'anulado' => (bool) ($d['anulado'] ?? false),
                    'descargado_en' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], $lote));
            }

            foreach (array_chunk($actividades, 200) as $lote) {
                DB::table('erp_empaques_actividades')->insert(array_map(fn ($a) => [
                    'sucursal' => (int) $a['sucursal'],
                    'numero' => (string) $a['numero'],
                    'actividad' => (int) $a['actividad'],
                    'nombre_actividad' => $a['nombre_actividad'] ?? null,
                    'forma_pago' => $a['forma_pago'] ?? null,
                    'bodega' => $a['bodega'] ?? null,
                    'bodega_material' => $a['bodega_material'] ?? null,
                    'ultima' => (bool) ($a['ultima'] ?? false),
                    'descargado_en' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], $lote));
            }

            foreach (array_chunk($detalles, 200) as $lote) {
                DB::table('erp_empaques_detalles')->insert(array_map(fn ($x) => [
                    'sucursal' => (int) $x['sucursal'],
                    'numero' => (string) $x['numero'],
                    'actividad' => (int) $x['actividad'],
                    'orden' => (string) ($x['orden'] ?? ''),
                    'empleado' => (string) ($x['empleado'] ?? ''),
                    'nombre_empleado' => $x['nombre_empleado'] ?? null,
                    'linea' => (int) ($x['linea'] ?? 0),
                    'producto' => (string) ($x['producto'] ?? ''),
                    'sku' => (string) ($x['sku'] ?? ''),
                    'puros' => (float) ($x['puros'] ?? 0),
                    'pagadas' => (float) ($x['pagadas'] ?? 0),
                    'control_calidad' => (float) ($x['control_calidad'] ?? 0),
                    'horas' => (float) ($x['horas'] ?? 0),
                    'precio' => (float) ($x['precio'] ?? 0),
                    'salario' => (float) ($x['salario'] ?? 0),
                    'bodega' => $x['bodega'] ?? null,
                    'tipo_empaque' => $x['tipo_empaque'] ?? null,
                    'descargado_en' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], $lote));
            }

            foreach (array_chunk($planificacion, 200) as $lote) {
                DB::table('erp_empaques_planificacion')->insert(array_map(fn ($p) => [
                    'sucursal' => (int) $p['sucursal'],
                    'orden' => (string) $p['orden'],
                    'linea' => (int) ($p['linea'] ?? 0),
                    'fecha' => $p['fecha'],
                    'actividad' => (int) $p['actividad'],
                    'a_producir' => (float) ($p['a_producir'] ?? 0),
                    'producida' => (float) ($p['producida'] ?? 0),
                    'personal' => (int) ($p['personal'] ?? 0),
                    'ultima' => (bool) ($p['ultima'] ?? false),
                    'descargado_en' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ], $lote));
            }
        });

        $conteos = [
            'documentos' => count($documentos),
            'detalles' => count($detalles),
            'planificacion' => count($planificacion),
        ];

        $this->registrarDescarga($fecha, $sucursal, $conteos, true, 'Descargado del ERP.');

        return [
            'success' => true,
            'message' => 'Descargado del ERP.',
            'documentos' => $conteos['documentos'],
            'actividades' => count($actividades),
            'detalles' => $conteos['detalles'],
            'planificacion' => $conteos['planificacion'],
        ];
    }

    /**
     * Ya se intento descargar esta fecha/sucursal y salio bien?
     * (incluso si el ERP no tiene documentos ese dia).
     */
    public function yaDescargada(string $fecha, int $sucursal): bool
    {
        try {
            if (Schema::hasTable('erp_empaques_descargas')) {
                return DB::table('erp_empaques_descargas')
                    ->where('sucursal', $sucursal)
                    ->whereDate('fecha', $fecha)
                    ->where('ok', true)
                    ->exists();
            }

            return DB::table('erp_empaques_documentos')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Deja constancia de la descarga (con exito o no) para que la pantalla
     * sepa si falta bajar esa fecha.
     *
     * @param  array{documentos?:int, detalles?:int, planificacion?:int}|null  $conteos
     */
    public function registrarDescarga(string $fecha, int $sucursal, ?array $conteos, bool $ok, string $mensaje): void
    {
        if (! Schema::hasTable('erp_empaques_descargas')) {
            return;
        }

        try {
            DB::table('erp_empaques_descargas')->updateOrInsert(
                ['sucursal' => $sucursal, 'fecha' => $fecha],
                [
                    'documentos' => (int) ($conteos['documentos'] ?? 0),
                    'detalles' => (int) ($conteos['detalles'] ?? 0),
                    'planificacion' => (int) ($conteos['planificacion'] ?? 0),
                    'ok' => $ok,
                    'mensaje' => mb_substr($mensaje, 0, 250),
                    'descargado_en' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Comparativo ERP: no se pudo registrar la descarga: '.$e->getMessage());
        }
    }

    /**
     * Compara lo del ERP (tablas espejo) contra lo registrado en Empaque.
     *
     * @return array{
     *   documentos: array,
     *   detalle: array,
     *   planificacion: array,
     *   resumen: array
     * }
     */
    public function comparar(string $fecha, int $sucursal = 2): array
    {
        $documentos = $this->compararDocumentos($fecha, $sucursal);
        $detalle = $this->compararDetalle($fecha, $sucursal);
        $planificacion = $this->compararPlanificacion($fecha, $sucursal);

        return [
            'documentos' => $documentos,
            'detalle' => $detalle,
            'planificacion' => $planificacion,
            'resumen' => [
                'documentos_erp' => count(array_filter($documentos, fn ($d) => $d['origen'] !== 'sistema')),
                'documentos_sistema' => count(array_filter($documentos, fn ($d) => $d['origen'] !== 'erp')),
                'detalle_erp' => count(array_filter($detalle, fn ($d) => $d['estado'] !== 'solo_sistema')),
                'detalle_sistema' => count(array_filter($detalle, fn ($d) => $d['estado'] !== 'solo_erp')),
                'faltan_en_erp' => count(array_filter($detalle, fn ($d) => $d['estado'] === 'solo_sistema')),
                'sobran_en_erp' => count(array_filter($detalle, fn ($d) => $d['estado'] === 'solo_erp')),
                'diferencias' => count(array_filter($detalle, fn ($d) => $d['estado'] === 'diferencia')),
                'ignorados_erp' => count(array_filter($planificacion, fn ($p) => $p['estado'] === 'sin_planificacion')),
            ],
        ];
    }

    /**
     * Normaliza el numero de orden: el ERP lo tiene con ceros (0000003597) y
     * el sistema a veces sin ellos (3597).
     */
    private function normalizarOrden($valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '';
        }

        return is_numeric($texto)
            ? str_pad((string) (int) $texto, 10, '0', STR_PAD_LEFT)
            : $texto;
    }
    private function compararDocumentos(string $fecha, int $sucursal): array
    {
        $erp = DB::table('erp_empaques_documentos')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('numero');

        $sistema = DB::table('vineta_registros')
            ->select('documento_numero', DB::raw('COUNT(*) AS vinetas'), DB::raw('SUM(cantidad_puros) AS puros'), DB::raw('SUM(COALESCE(minutos_trabajados,0)) AS minutos'))
            ->where('estado', 'activo')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha_registro', $fecha)
            ->whereNotNull('documento_numero')
            ->where('documento_numero', '<>', '')
            ->groupBy('documento_numero')
            ->get()
            ->keyBy('documento_numero');

        // Totales del ERP por documento (puros / horas / filas).
        $totalesErp = DB::table('erp_empaques_detalles as d')
            ->join('erp_empaques_documentos as doc', function ($join) {
                $join->on('doc.numero', '=', 'd.numero')->on('doc.sucursal', '=', 'd.sucursal');
            })
            ->where('d.sucursal', $sucursal)
            ->whereDate('doc.fecha', $fecha)
            ->selectRaw('d.numero, COUNT(*) AS filas, SUM(d.puros) AS puros, SUM(d.horas) AS horas')
            ->groupBy('d.numero')
            ->get()
            ->keyBy('numero');

        $filas = [];
        $numeros = array_unique(array_merge($erp->keys()->all(), $sistema->keys()->all()));
        sort($numeros);

        foreach ($numeros as $numero) {
            $e = $erp->get($numero);
            $s = $sistema->get($numero);

            $estado = match (true) {
                $e && ! $s => 'solo_erp',
                $s && ! $e => 'solo_sistema',
                default => 'ambos',
            };

            $tot = $totalesErp->get($numero);

            $filas[] = [
                'numero' => (string) $numero,
                'origen' => $estado,
                'erp_vinetas' => $tot ? (int) $tot->filas : 0,
                'erp_puros' => $tot ? (int) round((float) $tot->puros) : 0,
                'erp_horas' => $tot ? round((float) $tot->horas, 2) : 0,
                'sistema_vinetas' => (int) ($s->vinetas ?? 0),
                'sistema_puros' => (int) ($s->puros ?? 0),
                'sistema_horas' => round(((int) ($s->minutos ?? 0)) / 60, 2),
                'observaciones' => $e->observaciones ?? null,
                'aplicada' => $e ? (bool) $e->aplicada : null,
            ];
        }

        return $filas;
    }

    /**
     * Compara el detalle del ERP contra lo registrado en el sistema, por la
     * llave documento + actividad + orden + empleado + producto.
     */
    private function compararDetalle(string $fecha, int $sucursal): array
    {
        $erp = DB::table('erp_empaques_detalles as d')
            ->join('erp_empaques_documentos as doc', function ($join) {
                $join->on('doc.numero', '=', 'd.numero')->on('doc.sucursal', '=', 'd.sucursal');
            })
            ->where('d.sucursal', $sucursal)
            ->whereDate('doc.fecha', $fecha)
            ->selectRaw('d.numero, d.actividad, d.orden, d.empleado, d.producto, d.nombre_empleado,
                SUM(d.puros) AS puros, SUM(d.horas) AS horas, COUNT(*) AS filas, MAX(d.precio) AS precio, MAX(d.salario) AS salario')
            ->groupBy('d.numero', 'd.actividad', 'd.orden', 'd.empleado', 'd.producto', 'd.nombre_empleado')
            ->get();

        $sistema = DB::table('vineta_registros')
            ->selectRaw('documento_numero AS numero, actividad_codigo AS actividad, orden_del_sistema AS orden,
                empleado_codigo AS empleado, producto_codigo AS producto, MAX(empleado_nombre) AS nombre_empleado,
                SUM(cantidad_puros) AS puros, SUM(COALESCE(minutos_trabajados,0)) AS minutos, COUNT(*) AS filas,
                MAX(precio_mo) AS precio')
            ->where('estado', 'activo')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha_registro', $fecha)
            ->whereNotNull('documento_numero')
            ->where('documento_numero', '<>', '')
            ->groupBy('documento_numero', 'actividad_codigo', 'orden_del_sistema', 'empleado_codigo', 'producto_codigo')
            ->get();

        $llave = fn ($numero, $actividad, $orden, $empleado, $producto) => implode('|', [
            trim((string) $numero),
            (int) $actividad,
            $this->normalizarOrden($orden),
            trim((string) $empleado),
            trim((string) $producto),
        ]);

        $mapaErp = [];
        foreach ($erp as $r) {
            $mapaErp[$llave($r->numero, $r->actividad, $r->orden, $r->empleado, $r->producto)] = $r;
        }

        $mapaSistema = [];
        $porEmpleado = [];

        foreach ($sistema as $r) {
            $k = $llave($r->numero, $r->actividad, $r->orden, $r->empleado, $r->producto);
            $mapaSistema[$k] = $r;
            $llaveEmpleado = trim((string) $r->numero).'|'.trim((string) $r->empleado);
            $porEmpleado[$llaveEmpleado][] = $k;
        }

        // Horas ordinarias (gente "al dia"): sus viñetas llegan POR HORA, o sea
        // sin minutos_trabajados. Sin esto, el sistema se ve en 0 h aunque la
        // persona si trabajo. Se suman al empleado en ese documento (a la fila
        // con mas puros) para que el comparativo y los totales los muestren.
        $ordinarias = DB::table('empleado_horas_ordinarias')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha', $fecha)
            ->whereNotNull('documento_numero')
            ->where('documento_numero', '<>', '')
            ->selectRaw('TRIM(documento_numero) AS numero, TRIM(empleado_codigo) AS empleado, SUM(minutos) AS minutos')
            ->groupBy('documento_numero', 'empleado_codigo')
            ->get();

        foreach ($ordinarias as $o) {
            $minutosOrd = (float) $o->minutos;

            if ($minutosOrd <= 0) {
                continue;
            }

            $candidatos = $porEmpleado[trim((string) $o->numero).'|'.trim((string) $o->empleado)] ?? [];

            if ($candidatos === []) {
                continue;
            }

            // Se reparte COMO LO HACE EL ERP: en centesimas de hora,
            // proporcional a los puros de cada fila, y el resto (por el
            // redondeo hacia abajo) va de 1 en 1 a la fila con mas puros.
            // Así las horas por fila del sistema quedan iguales a las del ERP.
            $totalCentesimas = (int) round(($minutosOrd / 60) * 100);
            $purosTotal = 0.0;

            foreach ($candidatos as $k) {
                $purosTotal += max((float) ($mapaSistema[$k]->puros ?? 0), 0);
            }

            $reparto = [];
            $asignado = 0;

            foreach ($candidatos as $i => $k) {
                $puros = max((float) ($mapaSistema[$k]->puros ?? 0), 0);
                $parte = ($purosTotal > 0)
                    ? (int) floor($totalCentesimas * ($puros / $purosTotal))
                    : (int) floor($totalCentesimas / count($candidatos));

                $reparto[$i] = $parte;
                $asignado += $parte;
            }

            $resto = $totalCentesimas - $asignado;

            if ($resto > 0) {
                $orden = array_keys($candidatos);
                usort($orden, fn ($a, $b) => ((float) ($mapaSistema[$candidatos[$b]]->puros ?? 0)) <=> ((float) ($mapaSistema[$candidatos[$a]]->puros ?? 0)));
                $pos = 0;

                while ($resto > 0) {
                    $reparto[$orden[$pos % count($orden)]]++;
                    $resto--;
                    $pos++;
                }
            }

            foreach ($candidatos as $i => $k) {
                // 1 centesima de hora = 0.6 minuto (sin redondear, para que
                // horas de la fila y horas del empleado calen exacto).
                $mapaSistema[$k]->minutos = ((float) ($mapaSistema[$k]->minutos ?? 0))
                    + ($reparto[$i] * 0.6);
            }
        }

        $filas = [];

        foreach ($mapaSistema as $k => $s) {
            $e = $mapaErp[$k] ?? null;
            $purosS = (int) $s->puros;
            $horasS = round(((float) $s->minutos) / 60, 2);
            $purosE = $e ? (int) round((float) $e->puros) : 0;
            $horasE = $e ? round((float) $e->horas, 2) : 0;

            $estado = 'ambos';
            if (! $e) {
                $estado = 'solo_sistema';
            } elseif ($purosS !== $purosE || abs($horasS - $horasE) > 0.01) {
                $estado = 'diferencia';
            }

            $filas[] = [
                'numero' => trim((string) $s->numero),
                'actividad' => (int) $s->actividad,
                'orden' => trim((string) $s->orden),
                'empleado' => trim((string) $s->empleado),
                'nombre_empleado' => $s->nombre_empleado,
                'producto' => trim((string) $s->producto),
                'sistema_puros' => $purosS,
                'sistema_horas' => $horasS,
                'sistema_minutos' => (float) $s->minutos,
                'erp_puros' => $purosE,
                'erp_horas' => $horasE,
                'erp_precio' => $e ? (float) $e->precio : null,
                'erp_salario' => $e ? (float) $e->salario : null,
                'sistema_precio' => $s->precio !== null ? (float) $s->precio : null,
                'estado' => $estado,
            ];

            unset($mapaErp[$k]);
        }

        // Los que estan en el ERP y no en el sistema
        foreach ($mapaErp as $e) {
            $filas[] = [
                'numero' => trim((string) $e->numero),
                'actividad' => (int) $e->actividad,
                'orden' => trim((string) $e->orden),
                'empleado' => trim((string) $e->empleado),
                'nombre_empleado' => $e->nombre_empleado,
                'producto' => trim((string) $e->producto),
                'sistema_puros' => 0,
                'sistema_horas' => 0,
                'sistema_minutos' => 0,
                'erp_puros' => (int) round((float) $e->puros),
                'erp_horas' => round((float) $e->horas, 2),
                'erp_precio' => (float) $e->precio,
                'erp_salario' => (float) $e->salario,
                'sistema_precio' => null,
                'estado' => 'solo_erp',
            ];
        }

        usort($filas, fn ($a, $b) => [$a['numero'], $a['actividad'], $a['empleado']] <=> [$b['numero'], $b['actividad'], $b['empleado']]);

        return $filas;
    }

    /**
     * Filas del detalle del ERP que no tienen planificacion ese dia.
     */
    private function compararPlanificacion(string $fecha, int $sucursal): array
    {
        $plan = DB::table('erp_empaques_planificacion')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha', $fecha)
            ->get()
            ->map(fn ($p) => $this->normalizarOrden($p->orden).'|'.(int) $p->linea.'|'.(int) $p->actividad)
            ->flip();

        $filas = DB::table('erp_empaques_detalles as d')
            ->join('erp_empaques_documentos as doc', function ($join) {
                $join->on('doc.numero', '=', 'd.numero')->on('doc.sucursal', '=', 'd.sucursal');
            })
            ->where('d.sucursal', $sucursal)
            ->whereDate('doc.fecha', $fecha)
            ->selectRaw('d.numero, d.actividad, d.orden, d.linea, COUNT(*) AS filas, SUM(d.puros) AS puros, MAX(d.nombre_empleado) AS nombre_empleado')
            ->groupBy('d.numero', 'd.actividad', 'd.orden', 'd.linea')
            ->get();

        $salida = [];

        foreach ($filas as $f) {
            $k = $this->normalizarOrden($f->orden).'|'.(int) $f->linea.'|'.(int) $f->actividad;
            $enPlan = $plan->has($k);

            $salida[] = [
                'numero' => trim((string) $f->numero),
                'actividad' => (int) $f->actividad,
                'orden' => trim((string) $f->orden),
                'linea' => (int) $f->linea,
                'puros' => (int) round((float) $f->puros),
                'personal' => 0,
                'estado' => $enPlan ? 'en_planificacion' : 'sin_planificacion',
            ];
        }

        usort($salida, fn ($a, $b) => [$a['estado'] === 'sin_planificacion' ? 0 : 1, $a['numero'], $a['orden'], $a['linea']] <=> [$b['estado'] === 'sin_planificacion' ? 0 : 1, $b['numero'], $b['orden'], $b['linea']]);

        return $salida;
    }
}
