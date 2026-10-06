<?php

namespace App\Http\Controllers;

use App\Services\VinetaPendienteService;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VinetaPendienteController extends Controller
{
    public function __construct(
        private readonly VinetaPendienteService $service
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
        $orden = $request->get('orden', 'id');
        $direccion = strtolower((string) $request->get('direccion', 'asc'));

        $ordenesPermitidos = [
            'id',
            'codigo_qr',
            'fecha',
            'presentacion',
            'item',
            'orden_del_sistema',
            'marca',
            'nombre',
            'capa',
            'vitola',
            'tipo_empaque',
            'codigo_producto',
            'mes',
            'orden',
            'cantidad_puros',
            'estado',
        ];

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'id';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'asc';
        }

        $pendientes = $this->service->getPendientes($request->boolean('refresh'));

        // Filtrado en colección
        $filtrados = $pendientes->filter(function ($row) use (
            $buscar,
            $marca,
            $nombre,
            $codigoProducto,
            $item,
            $ordenDelSistema,
            $ordenCliente
        ) {
            if ($buscar !== '') {
                $cleanBuscar = ltrim($buscar, '#');
                $matchesQr = stripos($row->codigo_qr, $buscar) !== false;
                $matchesOrden = stripos($row->orden, $buscar) !== false;
                $matchesOs = stripos($row->orden_del_sistema, $buscar) !== false;
                $matchesId = ctype_digit($cleanBuscar) && (int) $row->id === (int) $cleanBuscar;

                if (! ($matchesQr || $matchesOrden || $matchesOs || $matchesId)) {
                    return false;
                }
            }

            if ($marca !== '' && stripos($row->marca, $marca) === false) {
                return false;
            }

            if ($nombre !== '' && stripos($row->nombre, $nombre) === false) {
                return false;
            }

            if ($codigoProducto !== '' && stripos($row->codigo_producto, $codigoProducto) === false) {
                return false;
            }

            if ($item !== '' && stripos($row->item, $item) === false) {
                return false;
            }

            if ($ordenDelSistema !== '' && stripos($row->orden_del_sistema, $ordenDelSistema) === false) {
                return false;
            }

            if ($ordenCliente !== '' && stripos($row->orden, $ordenCliente) === false) {
                return false;
            }

            return true;
        });

        // Ordenamiento
        $isDesc = $direccion === 'desc';
        if (in_array($orden, ['id', 'cantidad_puros'], true)) {
            $ordenados = $isDesc
                ? $filtrados->sortByDesc(fn ($row) => (int) ($row->{$orden} ?? 0))
                : $filtrados->sortBy(fn ($row) => (int) ($row->{$orden} ?? 0));
        } else {
            $ordenados = $isDesc
                ? $filtrados->sortByDesc(fn ($row) => mb_strtolower((string) ($row->{$orden} ?? '')))
                : $filtrados->sortBy(fn ($row) => mb_strtolower((string) ($row->{$orden} ?? '')));
        }

        $total = $ordenados->count();
        $perPageInput = $request->get('per_page', 10);
        $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 10);
        $pageSize = PerPageOptions::pageSize($perPageSelected, $total);

        $currentPage = (int) $request->get('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        $itemsPagina = $ordenados->slice(($currentPage - 1) * $pageSize, $pageSize)->values();

        $vinetasPendientes = new LengthAwarePaginator(
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

        if ($request->ajax()) {
            return view('vinetas-pendientes.partials.tabla', compact(
                'vinetasPendientes',
                'orden',
                'direccion',
                'perPageOptions',
                'perPageSelected'
            ))->render();
        }

        return view('vinetas-pendientes.index', compact(
            'vinetasPendientes',
            'orden',
            'direccion',
            'perPageOptions',
            'perPageSelected'
        ));
    }
}
