<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        try {
            $response = Http::timeout(120)
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
            Log::error('Comparativo ERP descarga: '.$e->getMessage());

            return ['success' => false, 'message' => 'No se pudo conectar con el ERP: '.$e->getMessage()];
        }

        $data = $response->json();

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'El ERP respondio: '.($data['message'] ?? 'respuesta no valida (HTTP '.$response->status().')'),
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

        return [
            'success' => true,
            'message' => 'Descargado del ERP.',
            'documentos' => count($documentos),
            'actividades' => count($actividades),
            'detalles' => count($detalles),
            'planificacion' => count($planificacion),
        ];
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
        foreach ($sistema as $r) {
            $mapaSistema[$llave($r->numero, $r->actividad, $r->orden, $r->empleado, $r->producto)] = $r;
        }

        $filas = [];

        foreach ($mapaSistema as $k => $s) {
            $e = $mapaErp[$k] ?? null;
            $purosS = (int) $s->puros;
            $horasS = round(((int) $s->minutos) / 60, 2);
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
                'erp_puros' => $purosE,
                'erp_horas' => $horasE,
                'erp_precio' => $e ? (float) $e->precio : null,
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
                'erp_puros' => (int) round((float) $e->puros),
                'erp_horas' => round((float) $e->horas, 2),
                'erp_precio' => (float) $e->precio,
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
