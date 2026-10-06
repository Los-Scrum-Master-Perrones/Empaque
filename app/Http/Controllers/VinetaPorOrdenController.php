<?php

namespace App\Http\Controllers;

use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VinetaPorOrdenController extends Controller
{
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
        $direccion = $request->get('direccion', 'desc');

        $ordenesPermitidos = [
            'id',
            'codigo_qr',
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
        ];

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'id';
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
            : 'vinetas_por_orden.'.$orden;

        $query = VinetaPorOrden::query()
            ->leftJoinSub($presentacionPorCodigo, 'presentaciones_producto', function ($join) {
                $join->on('presentaciones_producto.codigo_producto', '=', 'vinetas_por_orden.codigo_producto');
            })
            ->select([
                'vinetas_por_orden.id',
                'vinetas_por_orden.codigo_qr',
                'vinetas_por_orden.api_id',
                'vinetas_por_orden.fecha',
                'presentaciones_producto.presentacion',
                'vinetas_por_orden.marca',
                'vinetas_por_orden.nombre',
                'vinetas_por_orden.capa',
                'vinetas_por_orden.vitola',
                'vinetas_por_orden.tipo_empaque',
                'vinetas_por_orden.codigo_producto',
                'vinetas_por_orden.item',
                'vinetas_por_orden.orden_del_sistema',
                'vinetas_por_orden.mes',
                'vinetas_por_orden.orden',
                'vinetas_por_orden.cantidad_puros',
                'vinetas_por_orden.estado',
            ])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $cleanBuscar = ltrim($buscar, '#');
                $query->where(function ($query) use ($buscar, $cleanBuscar) {
                    $query->where('vinetas_por_orden.codigo_qr', 'like', "%{$buscar}%")
                        ->orWhere('vinetas_por_orden.orden', 'like', "%{$buscar}%")
                        ->orWhere('vinetas_por_orden.orden_del_sistema', 'like', "%{$buscar}%");

                    if (ctype_digit($cleanBuscar)) {
                        $query->orWhere('vinetas_por_orden.id', (int) $cleanBuscar)
                            ->orWhere('vinetas_por_orden.api_id', (int) $cleanBuscar);
                    }
                });
            })
            ->when($marca !== '', function ($query) use ($marca) {
                $query->where('vinetas_por_orden.marca', 'like', "%{$marca}%");
            })
            ->when($nombre !== '', function ($query) use ($nombre) {
                $query->where('vinetas_por_orden.nombre', 'like', "%{$nombre}%");
            })
            ->when($codigoProducto !== '', function ($query) use ($codigoProducto) {
                $query->where('vinetas_por_orden.codigo_producto', 'like', "%{$codigoProducto}%");
            })
            ->when($item !== '', function ($query) use ($item) {
                $query->where('vinetas_por_orden.item', 'like', "%{$item}%");
            })
            ->when($ordenDelSistema !== '', function ($query) use ($ordenDelSistema) {
                $query->where('vinetas_por_orden.orden_del_sistema', 'like', "%{$ordenDelSistema}%");
            })
            ->when($ordenCliente !== '', function ($query) use ($ordenCliente) {
                $query->where('vinetas_por_orden.orden', 'like', "%{$ordenCliente}%");
            })
            ->orderBy($columnaOrden, $direccion);

        $perPageInput = $request->get('per_page', 10);
        $perPageSelected = 10;

        $vinetasPorOrden = $query
            ->toBase()
            ->paginate(function (int $total) use ($perPageInput, &$perPageSelected) {
                $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 10);

                return PerPageOptions::pageSize($perPageSelected, $total);
            })
            ->appends($request->query());

        $perPageOptions = PerPageOptions::forTotal($vinetasPorOrden->total());
        $siguienteId = $this->calcularSiguienteId();
        $siguienteQr = "or-{$siguienteId}";

        if ($request->ajax()) {
            return view('vinetas-por-orden.partials.tabla', compact(
                'vinetasPorOrden',
                'orden',
                'direccion',
                'perPageOptions',
                'perPageSelected',
                'siguienteId',
                'siguienteQr'
            ))->render();
        }

        return view('vinetas-por-orden.index', compact(
            'vinetasPorOrden',
            'orden',
            'direccion',
            'perPageOptions',
            'perPageSelected',
            'siguienteId',
            'siguienteQr'
        ));
    }

    public function siguienteInfo()
    {
        $siguienteId = $this->calcularSiguienteId();

        return response()->json([
            'siguiente_id' => $siguienteId,
            'siguiente_qr' => "or-{$siguienteId}",
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'origen_id' => ['nullable', 'integer'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'item' => ['nullable', 'string', 'max:255'],
            'codigo_producto' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'vitola' => ['nullable', 'string', 'max:255'],
            'capa' => ['nullable', 'string', 'max:255'],
            'orden_del_sistema' => ['nullable', 'string', 'max:255'],
            'orden' => ['nullable', 'string', 'max:255'],
            'tipo_empaque' => ['nullable', 'string', 'max:255'],
            'mes' => ['nullable', 'string', 'max:255'],
            'cantidad_puros' => ['nullable', 'integer', 'min:0'],
            'estado' => ['nullable', 'string', 'max:50'],
        ]);

        $origen = ! empty($data['origen_id']) ? VinetaPorOrden::find($data['origen_id']) : null;

        if ($origen && $this->sonRegistrosIdenticos($origen, $data)) {
            $mensaje = 'No puedes crear la viñeta con exactamente los mismos datos. Debes modificar al menos un campo.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $mensaje,
                    'errors' => [
                        'general' => [$mensaje],
                    ],
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['general' => $mensaje]);
        }

        $siguienteId = $this->calcularSiguienteId();
        $codigoQr = "or-{$siguienteId}";

        $vpo = VinetaPorOrden::create([
            'codigo_qr' => $codigoQr,
            'api_id' => $siguienteId,
            'fecha' => ! empty($data['fecha']) ? $data['fecha'] : ($origen?->fecha ? $origen->fecha->format('Y-m-d') : null),
            'item' => $data['item'] ?? null,
            'codigo_producto' => $data['codigo_producto'] ?? null,
            'marca' => $data['marca'] ?? null,
            'nombre' => $data['nombre'] ?? null,
            'vitola' => $data['vitola'] ?? null,
            'capa' => $data['capa'] ?? null,
            'orden_del_sistema' => $data['orden_del_sistema'] ?? null,
            'orden' => $data['orden'] ?? null,
            'tipo_empaque' => $data['tipo_empaque'] ?? null,
            'mes' => $data['mes'] ?? null,
            'cantidad_puros' => isset($data['cantidad_puros']) && $data['cantidad_puros'] !== ''
                ? (int) $data['cantidad_puros']
                : ($origen?->cantidad_puros ?? 0),
            'estado' => ! empty($data['estado'])
                ? $data['estado']
                : ($origen?->estado ?: 'activo'),
        ]);

        // Sincronizar tabla Vineta para escaneo móvil
        Vineta::updateOrCreate(
            ['id_pendiente_empaque' => $codigoQr],
            [
                'api_id' => $vpo->api_id,
                'item' => $vpo->item,
                'codigo_producto' => $vpo->codigo_producto,
                'orden_del_sistema' => $vpo->orden_del_sistema,
                'mes' => $vpo->mes,
                'orden' => $vpo->orden,
                'marca' => $vpo->marca,
                'nombre' => $vpo->nombre,
                'capa' => $vpo->capa,
                'vitola' => $vpo->vitola,
                'tipo_empaque' => $vpo->tipo_empaque,
                'cantidad_puros' => $vpo->cantidad_puros,
                'fecha' => $vpo->fecha,
                'estado' => $vpo->estado ?: 'activo',
                'impreso' => true,
            ]
        );

        $mensajeExito = "Viñeta por orden #{$vpo->id} ({$vpo->codigo_qr}) creada exitosamente.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensajeExito,
                'vineta' => $vpo,
            ]);
        }

        return back()->with('success', $mensajeExito);
    }

    private function calcularSiguienteId(): int
    {
        $maxId = (int) VinetaPorOrden::max('id');
        $maxApiId = (int) VinetaPorOrden::max('api_id');
        $maxQrNum = 0;

        $qrs = VinetaPorOrden::whereNotNull('codigo_qr')->pluck('codigo_qr');
        foreach ($qrs as $qr) {
            if (preg_match('/^or-(\d+)$/i', trim((string) $qr), $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxQrNum) {
                    $maxQrNum = $num;
                }
            }
        }

        return max($maxId, $maxApiId, $maxQrNum) + 1;
    }

    private function sonRegistrosIdenticos(VinetaPorOrden $origen, array $data): bool
    {
        $camposTexto = [
            'item',
            'codigo_producto',
            'marca',
            'nombre',
            'vitola',
            'capa',
            'orden_del_sistema',
            'orden',
            'tipo_empaque',
            'mes',
        ];

        foreach ($camposTexto as $campo) {
            $valOrigen = trim((string) ($origen->{$campo} ?? ''));
            $valNuevo = trim((string) ($data[$campo] ?? ''));
            if (strcasecmp($valOrigen, $valNuevo) !== 0) {
                return false;
            }
        }

        if (array_key_exists('estado', $data) && ! empty($data['estado'])) {
            $estadoOrigen = trim((string) ($origen->estado ?? ''));
            $estadoNuevo = trim((string) ($data['estado'] ?? ''));
            if (strcasecmp($estadoOrigen, $estadoNuevo) !== 0) {
                return false;
            }
        }

        if (array_key_exists('fecha', $data) && ! empty($data['fecha'])) {
            $fechaOrigen = $origen->fecha ? $origen->fecha->format('Y-m-d') : '';
            $fechaNueva = trim((string) $data['fecha']);
            if ($fechaOrigen !== $fechaNueva) {
                return false;
            }
        }

        if (array_key_exists('cantidad_puros', $data) && $data['cantidad_puros'] !== '' && $data['cantidad_puros'] !== null) {
            $purosOrigen = $origen->cantidad_puros !== null ? (int) $origen->cantidad_puros : 0;
            $purosNuevo = (int) $data['cantidad_puros'];
            if ($purosOrigen !== $purosNuevo) {
                return false;
            }
        }

        return true;
    }
}
