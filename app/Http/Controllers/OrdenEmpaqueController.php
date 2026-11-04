<?php

namespace App\Http\Controllers;

use App\Services\OrdenEmpaqueService;
use App\Services\VinetaAreaService;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Pantalla "Ordenes": TODAS las ordenes de empaque que vienen de
 * facturacion_plasencia, incluidas las que ya estan en cero (se muestran
 * tachadas). Desde aqui se generan las viñetas por area (limpieza,
 * prensado, sellado, celofanado, ... o "de todo tipo").
 */
class OrdenEmpaqueController extends Controller
{
    /** Clave de sesion con el ultimo lote impreso (para reimprimir). */
    public const SESION_ULTIMO_LOTE = 'vinetas_ultimo_lote';

    private const CAMPOS_ORDEN = [
        'id_pendiente',
        'orden_del_sistema',
        'orden',
        'item',
        'codigo_producto',
        'presentacion',
        'marca',
        'nombre',
        'vitola',
        'capa',
        'tipo_empaque',
        'mes',
        'pendiente',
        'saldo',
    ];

    public function __construct(
        private readonly OrdenEmpaqueService $service,
        private readonly VinetaAreaService $areas
    ) {}

    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $marca = trim((string) $request->get('marca', ''));
        $nombre = trim((string) $request->get('nombre', ''));
        $codigoProducto = trim((string) $request->get('codigo_producto', ''));
        $item = trim((string) $request->get('item', ''));
        $ordenDelSistema = trim((string) $request->get('orden_del_sistema', ''));
        $ordenCliente = trim((string) $request->get('orden_cliente', ''));
        $tipoEmpaque = trim((string) $request->get('tipo_empaque', ''));
        $soloConSaldo = $request->boolean('solo_con_saldo');
        $orden = (string) $request->get('orden', 'orden_del_sistema');
        $direccion = strtolower((string) $request->get('direccion', 'asc'));

        if (! in_array($orden, self::CAMPOS_ORDEN, true)) {
            $orden = 'orden_del_sistema';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'asc';
        }

        if ($request->boolean('refresh')) {
            $this->service->refrescar();
        }

        $todas = $this->service->getOrdenes();
        $error = $this->service->ultimoError();

        $filtrados = $todas->filter(function ($row) use (
            $buscar,
            $marca,
            $nombre,
            $codigoProducto,
            $item,
            $ordenDelSistema,
            $ordenCliente,
            $tipoEmpaque,
            $soloConSaldo
        ) {
            if ($soloConSaldo && $row->sin_saldo) {
                return false;
            }

            if ($buscar !== '') {
                $aguja = mb_strtolower($buscar);
                $campos = [
                    $row->orden_del_sistema,
                    $row->orden,
                    $row->item,
                    $row->codigo_producto,
                    $row->marca,
                    $row->nombre,
                    $row->cliente,
                    $row->referencia,
                    (string) $row->id_pendiente,
                ];

                $coincide = false;

                foreach ($campos as $campo) {
                    if ($campo !== '' && mb_strpos(mb_strtolower((string) $campo), $aguja) !== false) {
                        $coincide = true;
                        break;
                    }
                }

                if (! $coincide) {
                    return false;
                }
            }

            foreach ([
                [$marca, $row->marca],
                [$nombre, $row->nombre],
                [$codigoProducto, $row->codigo_producto],
                [$item, $row->item],
                [$ordenDelSistema, $row->orden_del_sistema],
                [$ordenCliente, $row->orden],
                [$tipoEmpaque, $row->tipo_empaque],
            ] as [$aguja, $valor]) {
                if ($aguja !== '' && mb_strpos(mb_strtolower((string) $valor), mb_strtolower($aguja)) === false) {
                    return false;
                }
            }

            return true;
        });

        $isDesc = $direccion === 'desc';

        if (in_array($orden, ['id_pendiente', 'pendiente', 'saldo'], true)) {
            $ordenados = $isDesc
                ? $filtrados->sortByDesc(fn ($row) => (int) ($row->{$orden} ?? 0))
                : $filtrados->sortBy(fn ($row) => (int) ($row->{$orden} ?? 0));
        } else {
            $ordenados = $isDesc
                ? $filtrados->sortByDesc(fn ($row) => mb_strtolower((string) ($row->{$orden} ?? '')))
                : $filtrados->sortBy(fn ($row) => mb_strtolower((string) ($row->{$orden} ?? '')));
        }

        $ordenados = $ordenados->values();

        $total = $ordenados->count();
        $perPageInput = $request->get('per_page', 25);
        $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 25);
        $pageSize = PerPageOptions::pageSize($perPageSelected, $total);

        $currentPage = (int) $request->get('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        $itemsPagina = $ordenados->slice(($currentPage - 1) * $pageSize, $pageSize)->values();

        $ordenes = new LengthAwarePaginator(
            $itemsPagina,
            $total,
            $pageSize,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $perPageOptions = PerPageOptions::forTotal($total);
        $areas = $this->areas->areas();

        // Actividades de cada area (para avisar en el modal si falta la ficha).
        $areasActividades = [];

        foreach (array_keys($areas) as $claveArea) {
            $areasActividades[$claveArea] = $this->areas->actividades($claveArea)
                ->map(fn ($a) => ['codigo' => (int) $a->codigo_actividad, 'nombre' => (string) $a->nombre])
                ->values()
                ->all();
        }
        $totalConSaldo = $todas->where('sin_saldo', false)->count();
        $totalEnCero = $todas->where('sin_saldo', true)->count();
        $pendientesImpresion = $this->pendientesDeImprimir()->count();
        $ultimoLote = $request->hasSession()
            ? count((array) $request->session()->get(self::SESION_ULTIMO_LOTE, []))
            : 0;

        if ($request->ajax()) {
            return view('ordenes.partials.tabla', compact(
                'ordenes',
                'orden',
                'direccion',
                'perPageOptions',
                'perPageSelected'
            ))->render();
        }

        return view('ordenes.index', compact(
            'ordenes',
            'orden',
            'direccion',
            'perPageOptions',
            'perPageSelected',
            'areas',
            'areasActividades',
            'error',
            'totalConSaldo',
            'totalEnCero',
            'pendientesImpresion',
            'ultimoLote'
        ));
    }

    /**
     * Genera las viñetas del area elegida para una orden (o varias).
     *
     * No usa la API de pendientes: los datos del producto llegan en el
     * formulario, asi funciona igual para las ordenes que estan en cero.
     */
    public function generar(Request $request)
    {
        $data = $request->validate([
            'id_pendiente' => ['required', 'string', 'max:50'],
            'area' => ['required', 'string', 'max:30'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:500'],
            'cantidad_puros' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'item' => ['nullable', 'string', 'max:255'],
            'codigo_producto' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'vitola' => ['nullable', 'string', 'max:255'],
            'capa' => ['nullable', 'string', 'max:255'],
            'tipo_empaque' => ['nullable', 'string', 'max:255'],
            'orden_del_sistema' => ['nullable', 'string', 'max:255'],
            'orden' => ['nullable', 'string', 'max:255'],
            'mes' => ['nullable', 'string', 'max:255'],
        ]);

        $area = $this->areas->normalizarArea($data['area']);

        if (! $area) {
            return back()->with('error', 'Area no valida.');
        }

        $pendiente = [
            'id_pendiente' => trim((string) $data['id_pendiente']),
            'item' => $data['item'] ?? null,
            'codigo_producto' => $data['codigo_producto'] ?? null,
            'marca' => $data['marca'] ?? null,
            'nombre' => $data['nombre'] ?? null,
            'vitola' => $data['vitola'] ?? null,
            'capa' => $data['capa'] ?? null,
            'tipo_empaque' => $data['tipo_empaque'] ?? null,
            'orden_del_sistema' => $data['orden_del_sistema'] ?? null,
            'orden' => $data['orden'] ?? null,
            'mes' => $data['mes'] ?? null,
        ];

        $resultado = $this->areas->generar(
            $pendiente,
            $area,
            (int) $data['cantidad'],
            (int) ($data['cantidad_puros'] ?? 250)
        );

        if (! $resultado['ok']) {
            return back()->with('error', $resultado['mensaje']);
        }

        // Quedan pendientes de imprimir. NO se abre la hoja todavia: se
        // juntan las de todas las ordenes y se imprimen con un solo boton.
        $pendientes = $this->pendientesDeImprimir()->count();

        return redirect()
            ->route('ordenes.index')
            ->with('success', $resultado['mensaje'].' Quedan '.$pendientes.' viñeta(s) por imprimir.');
    }

    /**
     * Hoja con TODAS las viñetas pendientes de imprimir (de cualquier orden).
     *
     * Igual que facturación: al generar la hoja se marcan como impresas y
     * dejan de salir en la proxima. Se guarda el ultimo lote en sesion para
     * poder reimprimirlo sin volver a generarlas.
     */
    public function impresion(Request $request)
    {
        $pendientes = $this->pendientesDeImprimir()->get();

        if ($pendientes->isEmpty()) {
            return redirect()
                ->route('ordenes.index')
                ->with('error', 'No hay viñetas pendientes de imprimir.');
        }

        $codigos = $pendientes->pluck('codigo_qr')->map(fn ($codigo) => (string) $codigo)->all();

        \App\Models\VinetaPorOrden::query()
            ->whereIn('id', $pendientes->pluck('id'))
            ->update(['impreso' => true, 'impreso_en' => now()]);

        // Respaldo por si hay que reimprimir sin volver a generarlas.
        $request->session()->put(self::SESION_ULTIMO_LOTE, $codigos);

        return view('vinetas-pendientes.imprimir', [
            'vinetas' => $pendientes,
            'titulo' => 'pendientes ('.count($codigos).')',
            'lote' => false,
        ]);
    }

    /**
     * Vuelve a mostrar la ultima hoja impresa (no marca nada, no genera nada).
     */
    public function reimprimir(Request $request)
    {
        $codigos = (array) $request->session()->get(self::SESION_ULTIMO_LOTE, []);

        if ($codigos === []) {
            return redirect()
                ->route('ordenes.index')
                ->with('error', 'No hay una impresión anterior en esta sesión.');
        }

        $vinetas = \App\Models\VinetaPorOrden::query()
            ->whereIn('codigo_qr', $codigos)
            ->orderBy('id')
            ->get();

        return view('vinetas-pendientes.imprimir', [
            'vinetas' => $vinetas,
            'titulo' => 'última impresión ('.count($codigos).')',
            'lote' => false,
        ]);
    }

    /**
     * Viñetas generadas en Empaque que todavia no se han impreso.
     */
    private function pendientesDeImprimir()
    {
        return \App\Models\VinetaPorOrden::query()
            ->whereNotNull('area')
            ->where('impreso', false)
            ->orderBy('id');
    }
}
