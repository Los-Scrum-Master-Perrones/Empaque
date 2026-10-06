<?php

namespace App\Http\Controllers;

use App\Models\Vineta;
use App\Services\VinetaApiService;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VinetaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $marca = trim((string) $request->get('marca', ''));
        $nombre = trim((string) $request->get('nombre', ''));
        $codigoProducto = trim((string) $request->get('codigo_producto', ''));
        $item = trim((string) $request->get('item', ''));
        $capa = trim((string) $request->get('capa', ''));
        $ordenDelSistema = trim((string) $request->get('orden_del_sistema', ''));
        $ordenCliente = trim((string) $request->get('orden_cliente', ''));
        $orden = $request->get('orden', 'api_id');
        $direccion = $request->get('direccion', 'desc');

        $ordenesPermitidos = [
            'api_id',
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
            'impreso',
        ];

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'api_id';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'desc';
        }

        $presentacionPorCodigo = DB::table('productos')
            ->join('presentaciones', 'presentaciones.id', '=', 'productos.presentacion_id')
            ->selectRaw('productos.codigo_producto, MIN(presentaciones.nombre) as presentacion')
            ->whereNotNull('productos.codigo_producto')
            ->groupBy('productos.codigo_producto');
        $columnaOrden = $orden === 'presentacion'
            ? 'presentaciones_producto.presentacion'
            : 'vinetas.'.$orden;
        $query = Vineta::query()
            ->leftJoinSub($presentacionPorCodigo, 'presentaciones_producto', function ($join) {
                $join->on('presentaciones_producto.codigo_producto', '=', 'vinetas.codigo_producto');
            })
            ->select([
                'vinetas.api_id',
                'vinetas.fecha',
                'presentaciones_producto.presentacion',
                'vinetas.marca',
                'vinetas.nombre',
                'vinetas.capa',
                'vinetas.vitola',
                'vinetas.tipo_empaque',
                'vinetas.codigo_producto',
                'vinetas.item',
                'vinetas.orden_del_sistema',
                'vinetas.mes',
                'vinetas.orden',
                'vinetas.cantidad_puros',
                'vinetas.estado',
                'vinetas.impreso',
            ])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where('vinetas.api_id', ltrim($buscar, '#'));
            })
            ->when($marca !== '', function ($query) use ($marca) {
                $query->where('vinetas.marca', 'like', "%{$marca}%");
            })
            ->when($nombre !== '', function ($query) use ($nombre) {
                $query->where('vinetas.nombre', 'like', "%{$nombre}%");
            })
            ->when($codigoProducto !== '', function ($query) use ($codigoProducto) {
                $query->where('vinetas.codigo_producto', 'like', "%{$codigoProducto}%");
            })
            ->when($item !== '', function ($query) use ($item) {
                $query->where('vinetas.item', 'like', "%{$item}%");
            })
            ->when($capa !== '', function ($query) use ($capa) {
                $query->where('vinetas.capa', 'like', "%{$capa}%");
            })
            ->when($ordenDelSistema !== '', function ($query) use ($ordenDelSistema) {
                $query->where('vinetas.orden_del_sistema', 'like', "%{$ordenDelSistema}%");
            })
            ->when($ordenCliente !== '', function ($query) use ($ordenCliente) {
                $query->where('vinetas.orden', 'like', "%{$ordenCliente}%");
            })
            ->orderBy($columnaOrden, $direccion);

        $perPageInput = $request->get('per_page', 10);
        $perPageSelected = 10;

        $vinetas = $query
            ->toBase()
            ->paginate(function (int $total) use ($perPageInput, &$perPageSelected) {
                $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 10);

                return PerPageOptions::pageSize($perPageSelected, $total);
            })
            ->appends($request->query());
        $perPageOptions = PerPageOptions::forTotal($vinetas->total());

        if ($request->ajax()) {
            return view('vinetas.partials.tabla', compact(
                'vinetas',
                'orden',
                'direccion',
                'perPageOptions',
                'perPageSelected'
            ))->render();
        }

        return view('vinetas.index', compact(
            'vinetas',
            'orden',
            'direccion',
            'perPageOptions',
            'perPageSelected'
        ));
    }

    public function sincronizar(VinetaApiService $vinetaApiService)
    {
        $resultado = $vinetaApiService->sincronizar();

        if (! $resultado['ok']) {
            return redirect()
                ->back()
                ->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->back()
            ->with('success', $resultado['mensaje'] . " Procesadas: {$resultado['total']}. Omitidas: {$resultado['omitidos']}.");
    }

    public function notificaciones(Request $request, VinetaApiService $vinetaApiService)
    {
        return response()->json(
            $vinetaApiService->resumenImpresasNuevas($request->boolean('force'))
        );
    }
}
