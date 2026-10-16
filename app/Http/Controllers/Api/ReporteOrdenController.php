<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VinetaRegistro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteOrdenController extends Controller
{
    /**
     * Reporte por orden: lo programado (base_2) contra lo hecho en empaque.
     *
     * GET /api/reporte-orden?anio=2026&mes=9
     *
     * El periodo filtra las programaciones con "desde ese mes/año en adelante":
     * con mes=1 trae de enero en adelante; con mes=2, de febrero en adelante.
     * Sin "mes" trae todo el año en adelante.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'anio' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->armarReporte($data),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * @param  array{anio?:int,mes?:int}  $filtros
     */
    private function armarReporte(array $filtros): array
    {
        $hoy = now('America/Tegucigalpa');
        $anio = (int) ($filtros['anio'] ?? $hoy->year);
        $mes = (int) ($filtros['mes'] ?? 0);

        if ($anio < 2020 || $anio > 2100) {
            $anio = (int) $hoy->year;
        }

        if ($mes < 1 || $mes > 12) {
            $mes = 0;
        }

        $desde = $mes > 0
            ? Carbon::create($anio, $mes, 1, 0, 0, 0, 'America/Tegucigalpa')
            : Carbon::create($anio, 1, 1, 0, 0, 0, 'America/Tegucigalpa');

        $periodoLabel = $mes > 0
            ? 'DESDE '.$desde->locale('es')->translatedFormat('F Y').' EN ADELANTE'
            : 'DESDE '.$anio.' EN ADELANTE';

        $programado = $this->consultaProgramado($desde);

        $ordenes = $programado
            ->pluck('orden_sistema')
            ->map(fn ($orden) => trim((string) $orden))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hecho = $this->consultaHecho($ordenes);

        // Acumula lo programado por orden del sistema + orden del cliente + item.
        // Esos 3 campos son la llave real: la misma orden del sistema puede tener
        // varias ordenes de cliente y el mismo item repetirse en cada una.
        $grupos = [];

        foreach ($programado as $fila) {
            $ordenSistema = trim((string) $fila->orden_sistema);
            $ordenCliente = trim((string) $fila->orden_cliente);
            $item = trim((string) $fila->item);

            if (! isset($grupos[$ordenSistema])) {
                $grupos[$ordenSistema] = [
                    'orden_sistema' => $ordenSistema,
                    'clientes' => [],
                    'totales' => $this->totalesEnCero(),
                ];
            }

            if (! isset($grupos[$ordenSistema]['clientes'][$ordenCliente])) {
                $grupos[$ordenSistema]['clientes'][$ordenCliente] = [
                    'orden_cliente' => $ordenCliente,
                    'items' => [],
                    'totales' => $this->totalesEnCero(),
                ];
            }

            if (! isset($grupos[$ordenSistema]['clientes'][$ordenCliente]['items'][$item])) {
                $grupos[$ordenSistema]['clientes'][$ordenCliente]['items'][$item] = [
                    'item' => $item,
                    'codigo_producto' => (string) ($fila->codigo_producto ?? ''),
                    'marca' => (string) ($fila->marca ?? ''),
                    'nombre' => (string) ($fila->nombre ?? ''),
                    'vitola' => (string) ($fila->vitola ?? ''),
                    'capa' => (string) ($fila->capa ?? ''),
                    'tipo_empaque' => (string) ($fila->tipo_empaque ?? ''),
                    'programaciones' => 0,
                    'cajas' => 0,
                    'puros_programados' => 0,
                    'rezago' => 0,
                    'anillado' => 0,
                    'llenado' => 0,
                    'avance' => 0.0,
                ];
            }

            $ref = &$grupos[$ordenSistema]['clientes'][$ordenCliente]['items'][$item];
            $ref['programaciones'] += (int) $fila->programaciones;
            $ref['cajas'] += (int) $fila->cajas;
            $ref['puros_programados'] += (int) round((float) $fila->puros_programados);
            unset($ref);
        }

        $totales = $this->totalesEnCero();

        foreach ($grupos as $ordenSistema => $grupo) {
            foreach ($grupo['clientes'] as $ordenCliente => $cliente) {
                foreach ($cliente['items'] as $item => $datos) {
                    $real = $hecho->get($ordenSistema.'|'.$ordenCliente.'|'.$item);

                    $rezago = (int) ($real->rezago ?? 0);
                    $anillado = (int) ($real->anillado ?? 0);
                    $llenado = (int) ($real->llenado ?? 0);

                    $itemFinal = $datos;
                    $itemFinal['rezago'] = $rezago;
                    $itemFinal['anillado'] = $anillado;
                    $itemFinal['llenado'] = $llenado;
                    $itemFinal['avance'] = $this->calcularAvance([
                        'puros_programados' => $datos['puros_programados'],
                        'rezago' => $rezago,
                        'anillado' => $anillado,
                        'llenado' => $llenado,
                    ]);

                    $grupos[$ordenSistema]['clientes'][$ordenCliente]['items'][$item] = $itemFinal;

                    foreach ([
                        &$grupos[$ordenSistema]['clientes'][$ordenCliente]['totales'],
                        &$grupos[$ordenSistema]['totales'],
                        &$totales,
                    ] as &$acumulado) {
                        $this->sumarTotales(
                            $acumulado,
                            (int) $datos['programaciones'],
                            (int) $datos['cajas'],
                            (int) $datos['puros_programados'],
                            $rezago,
                            $anillado,
                            $llenado
                        );
                    }
                    unset($acumulado);
                }

                $grupos[$ordenSistema]['clientes'][$ordenCliente]['items'] = array_values($grupos[$ordenSistema]['clientes'][$ordenCliente]['items']);
                $grupos[$ordenSistema]['clientes'][$ordenCliente]['totales']['avance'] = $this->calcularAvance($grupos[$ordenSistema]['clientes'][$ordenCliente]['totales']);
            }

            $grupos[$ordenSistema]['clientes'] = array_values($grupos[$ordenSistema]['clientes']);
            $grupos[$ordenSistema]['totales']['avance'] = $this->calcularAvance($grupos[$ordenSistema]['totales']);
        }

        $totales['avance'] = $this->calcularAvance($totales);

        return [
            'filtro' => [
                'anio' => $anio,
                'mes' => $mes,
                'desde' => $desde->toDateString(),
                'periodo_label' => $periodoLabel,
            ],
            'ordenes' => count($grupos),
            'items' => array_sum(array_map(
                fn (array $grupo) => array_sum(array_map(fn (array $cliente) => count($cliente['items']), $grupo['clientes'])),
                $grupos
            )),
            'totales' => $totales,
            'grupos' => array_values($grupos),
        ];
    }

    /**
     * Lo programado en facturacion_plasencia (base_2).
     */
    private function consultaProgramado(Carbon $desde)
    {
        return DB::connection('programaciones')
            ->table('detalle_programacion as dp')
            ->join('pendiente as p', 'p.id_pendiente', '=', 'dp.id_pendiente')
            ->leftJoin('marca_productos as m', 'm.id_marca', '=', 'p.marca')
            ->leftJoin('nombre_productos as n', 'n.id_nombre', '=', 'p.nombre')
            ->leftJoin('vitola_productos as v', 'v.id_vitola', '=', 'p.vitola')
            ->leftJoin('capa_productos as c', 'c.id_capa', '=', 'p.capa')
            ->leftJoin('tipo_empaques as te', 'te.id_tipo_empaque', '=', 'p.tipo_empaque')
            ->whereNotNull('dp.numero_orden')
            ->where('dp.numero_orden', '<>', '')
            ->where('dp.created_at', '>=', $desde->toDateTimeString())
            ->groupBy(
                'dp.numero_orden',
                'dp.orden',
                'p.item',
                'p.codigo_productos',
                'm.marca',
                'n.nombre',
                'v.vitola',
                'c.capa',
                'te.tipo_empaque'
            )
            ->selectRaw('dp.numero_orden as orden_sistema')
            ->selectRaw('dp.orden as orden_cliente')
            ->selectRaw('p.item as item')
            ->selectRaw('p.codigo_productos as codigo_producto')
            ->selectRaw('m.marca as marca')
            ->selectRaw('n.nombre as nombre')
            ->selectRaw('v.vitola as vitola')
            ->selectRaw('c.capa as capa')
            ->selectRaw('te.tipo_empaque as tipo_empaque')
            ->selectRaw('COUNT(*) as programaciones')
            ->selectRaw('COALESCE(SUM(dp.cant_cajas), 0) as cajas')
            ->selectRaw('COALESCE(SUM(dp.saldo), 0) as puros_programados')
            ->orderBy('dp.numero_orden')
            ->orderBy('p.item')
            ->get();
    }

    /**
     * Lo hecho en empaque, por orden del sistema + item.
     *
     * @param  array<int,string>  $ordenes
     */
    private function consultaHecho(array $ordenes)
    {
        if ($ordenes === []) {
            return collect();
        }

        $grupoSql = $this->grupoActividadCaseExpression();

        return VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereIn('orden_del_sistema', $ordenes)
            ->selectRaw('TRIM(orden_del_sistema) as orden_sistema')
            ->selectRaw('TRIM(orden) as orden_cliente')
            ->selectRaw('TRIM(producto_item) as item')
            ->selectRaw("COALESCE(SUM(CASE WHEN $grupoSql = 'rezago' THEN cantidad_puros ELSE 0 END), 0) as rezago")
            ->selectRaw("COALESCE(SUM(CASE WHEN $grupoSql = 'anillado' THEN cantidad_puros ELSE 0 END), 0) as anillado")
            ->selectRaw("COALESCE(SUM(CASE WHEN $grupoSql = 'llenado' THEN cantidad_puros ELSE 0 END), 0) as llenado")
            ->groupBy(
                DB::raw('TRIM(orden_del_sistema)'),
                DB::raw('TRIM(orden)'),
                DB::raw('TRIM(producto_item)')
            )
            ->get()
            ->keyBy(fn ($row) => $row->orden_sistema.'|'.$row->orden_cliente.'|'.$row->item);
    }

    /**
     * @return array<string,int|float>
     */
    private function totalesEnCero(): array
    {
        return [
            'programaciones' => 0,
            'cajas' => 0,
            'puros_programados' => 0,
            'rezago' => 0,
            'anillado' => 0,
            'llenado' => 0,
            'avance' => 0.0,
        ];
    }

    /**
     * @param  array<string,int|float>  $totales
     */
    private function sumarTotales(array &$totales, int $programaciones, int $cajas, int $puros, int $rezago, int $anillado, int $llenado): void
    {
        $totales['programaciones'] += $programaciones;
        $totales['cajas'] += $cajas;
        $totales['puros_programados'] += $puros;
        $totales['rezago'] += $rezago;
        $totales['anillado'] += $anillado;
        $totales['llenado'] += $llenado;
    }

    /**
     * Avance: el grupo mas avanzado con datos (llenado > anillado > rezago)
     * sobre los puros programados.
     *
     * @param  array<string,int|float>  $totales
     */
    private function calcularAvance(array $totales): float
    {
        $puros = (int) ($totales['puros_programados'] ?? 0);

        if ($puros <= 0) {
            return 0.0;
        }

        $llenado = (int) ($totales['llenado'] ?? 0);
        $anillado = (int) ($totales['anillado'] ?? 0);
        $rezago = (int) ($totales['rezago'] ?? 0);

        $base = $llenado > 0 ? $llenado : ($anillado > 0 ? $anillado : $rezago);

        return round($base / $puros * 100, 1);
    }

    /**
     * Clasifica una actividad de empaque en rezago / anillado / llenado.
     */
    private function grupoActividadCaseExpression(): string
    {
        $nombre = "LOWER(COALESCE(vineta_registros.actividad_nombre, ''))";

        return "CASE
            WHEN $nombre LIKE '%rezag%' OR $nombre LIKE '%rezad%' OR $nombre LIKE '%resag%' THEN 'rezago'
            WHEN $nombre LIKE '%anill%' OR $nombre LIKE '%anil%' OR $nombre LIKE '%celof%' OR $nombre LIKE '%cello%' OR $nombre LIKE '%sello%' OR $nombre LIKE '%esponj%' OR $nombre LIKE '%lamina%' OR $nombre LIKE '%tapon%' OR $nombre LIKE '%banda%' OR $nombre LIKE '%cinta%' THEN 'anillado'
            WHEN $nombre LIKE '%llenad%' OR $nombre LIKE '%kretek%' OR $nombre LIKE '%display%' OR $nombre LIKE '%bolsa%' OR $nombre LIKE '%caja%' OR $nombre LIKE '%paquet%' OR $nombre LIKE '%sellado%' OR $nombre LIKE '%costura%' OR $nombre LIKE '%jarra%' OR $nombre LIKE '%petaca%' OR $nombre LIKE '%sampler%' OR $nombre LIKE '%tubo%' THEN 'llenado'
            ELSE 'otros'
        END";
    }
}
