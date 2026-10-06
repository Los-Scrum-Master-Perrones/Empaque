<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use App\Support\PerPageOptions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstadisticoController extends Controller
{
    public function index(Request $request)
    {
        $migrationPending = ! Schema::hasTable('vineta_registros');

        if ($migrationPending) {
            $viewData = [
                'grupos' => new LengthAwarePaginator([], 0, 25, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'orden' => 'orden_del_sistema',
                'direccion' => 'asc',
                'migrationPending' => true,
                'perPageOptions' => PerPageOptions::forTotal(0),
                'perPageSelected' => 25,
            ];

            if ($request->ajax()) {
                return view('estadistico.partials.tabla', $viewData)->render();
            }

            return view('estadistico.index', $viewData);
        }

        $fechaInicio = trim((string) $request->get('fecha_inicio', $request->get('fecha_desde', '')));
        $fechaFin = trim((string) $request->get('fecha_fin', $request->get('fecha_hasta', '')));
        $ordenDelSistema = trim((string) $request->get('orden_del_sistema', ''));
        $ordenCliente = trim((string) $request->get('orden_cliente', $request->get('orden', '')));
        $item = trim((string) $request->get('item', $request->get('producto_item', '')));
        $empleado = trim((string) $request->get('empleado', ''));

        $orden = $request->get('orden', 'orden_del_sistema');
        $direccion = strtolower((string) $request->get('direccion', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = DB::table('vineta_registros')
            ->where('vineta_registros.estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereNull('vineta_registros.anulado_en');

        if ($fechaInicio !== '') {
            $query->whereDate('vineta_registros.fecha_registro', '>=', $fechaInicio);
        }

        if ($fechaFin !== '') {
            $query->whereDate('vineta_registros.fecha_registro', '<=', $fechaFin);
        }

        if ($ordenDelSistema !== '') {
            $query->where('vineta_registros.orden_del_sistema', 'like', "%{$ordenDelSistema}%");
        }

        if ($ordenCliente !== '') {
            $query->where('vineta_registros.orden', 'like', "%{$ordenCliente}%");
        }

        if ($item !== '') {
            $query->where('vineta_registros.producto_item', 'like', "%{$item}%");
        }

        if ($empleado !== '') {
            $query->where(function ($q) use ($empleado) {
                $q->where('vineta_registros.empleado_codigo', 'like', "%{$empleado}%")
                    ->orWhere('vineta_registros.empleado_nombre', 'like', "%{$empleado}%");
            });
        }

        $query->select([
            'vineta_registros.orden_del_sistema',
            'vineta_registros.orden as orden_cliente',
            'vineta_registros.producto_codigo',
            'vineta_registros.producto_item',
            'vineta_registros.producto_nombre',
            'vineta_registros.marca',
            'vineta_registros.vitola',
            'vineta_registros.capa',
            'vineta_registros.tipo_empaque',
            'vineta_registros.actividad_nombre',
            DB::raw('SUM(vineta_registros.cantidad_puros) as cantidad_procesada'),
        ])
        ->groupBy([
            'vineta_registros.orden_del_sistema',
            'vineta_registros.orden',
            'vineta_registros.producto_codigo',
            'vineta_registros.producto_item',
            'vineta_registros.producto_nombre',
            'vineta_registros.marca',
            'vineta_registros.vitola',
            'vineta_registros.capa',
            'vineta_registros.tipo_empaque',
            'vineta_registros.actividad_nombre',
        ])
        ->orderBy('vineta_registros.orden_del_sistema', 'asc')
        ->orderBy('vineta_registros.orden', 'asc')
        ->orderBy('vineta_registros.producto_item', 'asc')
        ->orderBy('vineta_registros.actividad_nombre', 'asc');

        $rows = $query->get();

        $cleanText = function (?string $val): ?string {
            $t = trim((string) $val);
            if ($t === '' || in_array(strtolower($t), ['ninguna', 'ninguno', 'n/a', 'na', 'null', 'sin producto', 'sin nombre'], true)) {
                return null;
            }
            return $t;
        };

        // Agrupación en memoria de las filas agregadas (ultra rápido)
        $grupos = $rows->groupBy(function ($item) {
            return implode('||', [
                $item->orden_del_sistema ?? '',
                $item->orden_cliente ?? '',
                $item->producto_item ?? '',
                $item->producto_codigo ?? '',
                $item->tipo_empaque ?? '',
            ]);
        })->map(function ($items) use ($cleanText) {
            $first = $items->first();
            $subtotal = (int) $items->sum('cantidad_procesada');

            $nombre = $cleanText($first->producto_nombre);
            $marca = $cleanText($first->marca);
            $vitola = $cleanText($first->vitola);
            $capa = $cleanText($first->capa);

            // Título principal (marca si existe, o nombre)
            $titulo = $marca ?: ($nombre ?: 'N/A');

            // Detalle secundario (evita repetir si nombre == marca, o si nombre es vacío)
            $nombreExtra = ($nombre && strtolower($nombre) !== strtolower((string) $marca)) ? $nombre : null;
            $detalles = collect([$nombreExtra, $vitola, $capa])->filter()->join(' • ');

            // Descripción combinada
            $descParts = array_filter([$marca, $nombreExtra, $vitola, $capa]);
            $descripcion = ! empty($descParts) ? implode(' ', $descParts) : 'N/A';

            return (object) [
                'orden_del_sistema' => $first->orden_del_sistema ?: 'N/A',
                'orden_cliente' => $first->orden_cliente ?: 'N/A',
                'producto_codigo' => $first->producto_codigo ?: 'N/A',
                'producto_item' => $first->producto_item ?: 'N/A',
                'titulo' => $titulo,
                'detalles' => $detalles,
                'descripcion' => $descripcion,
                'tipo_empaque' => $first->tipo_empaque ?: 'N/A',
                'actividades' => $items->map(fn ($i) => (object) [
                    'actividad_nombre' => $i->actividad_nombre,
                    'cantidad' => (int) $i->cantidad_procesada,
                ])->values(),
                'subtotal' => $subtotal,
            ];
        })->values();

        // Ordenamiento a nivel de grupo
        if (in_array($orden, ['orden_del_sistema', 'orden_cliente', 'producto_codigo', 'producto_item', 'tipo_empaque', 'subtotal'], true)) {
            $grupos = $direccion === 'desc'
                ? $grupos->sortByDesc($orden, SORT_NATURAL | SORT_FLAG_CASE)->values()
                : $grupos->sortBy($orden, SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        $totalGrupos = $grupos->count();

        // Paginación de grupos
        $perPageOptions = PerPageOptions::forTotal($totalGrupos);
        $perPageSelected = PerPageOptions::resolve($request->get('per_page', 25), $totalGrupos, 25);
        $perPage = PerPageOptions::pageSize($perPageSelected, $totalGrupos);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $paginatedItems = $grupos->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedGrupos = new LengthAwarePaginator(
            $paginatedItems,
            $totalGrupos,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $viewData = [
            'grupos' => $paginatedGrupos,
            'orden' => $orden,
            'direccion' => $direccion,
            'migrationPending' => false,
            'perPageOptions' => $perPageOptions,
            'perPageSelected' => $perPageSelected,
        ];

        if ($request->ajax()) {
            return view('estadistico.partials.tabla', $viewData)->render();
        }

        return view('estadistico.index', $viewData);
    }

    public function detalleActividad(Request $request)
    {
        $groupOS = trim((string) $request->get('group_orden_sistema', ''));
        $groupOC = trim((string) $request->get('group_orden_cliente', ''));
        $groupCodigo = trim((string) $request->get('group_producto_codigo', ''));
        $groupItem = trim((string) $request->get('group_producto_item', ''));
        $groupEmpaque = trim((string) $request->get('group_tipo_empaque', ''));
        $actividad = trim((string) $request->get('actividad_nombre', ''));

        $fechaInicio = trim((string) $request->get('fecha_inicio', $request->get('fecha_desde', '')));
        $fechaFin = trim((string) $request->get('fecha_fin', $request->get('fecha_hasta', '')));
        $empleado = trim((string) $request->get('empleado', ''));

        $titulo = trim((string) $request->get('titulo', ''));
        $detalles = trim((string) $request->get('detalles', ''));

        $query = DB::table('vineta_registros')
            ->where('vineta_registros.estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereNull('vineta_registros.anulado_en');

        if ($actividad !== '') {
            $query->where('vineta_registros.actividad_nombre', $actividad);
        }

        if ($groupOS !== '' && $groupOS !== 'N/A') {
            $query->where('vineta_registros.orden_del_sistema', $groupOS);
        } elseif ($groupOS === 'N/A' || $groupOS === '') {
            $query->where(function ($q) {
                $q->whereNull('vineta_registros.orden_del_sistema')
                  ->orWhere('vineta_registros.orden_del_sistema', '');
            });
        }

        if ($groupOC !== '' && $groupOC !== 'N/A') {
            $query->where('vineta_registros.orden', $groupOC);
        } elseif ($groupOC === 'N/A' || $groupOC === '') {
            $query->where(function ($q) {
                $q->whereNull('vineta_registros.orden')
                  ->orWhere('vineta_registros.orden', '');
            });
        }

        if ($groupItem !== '' && $groupItem !== 'N/A') {
            $query->where('vineta_registros.producto_item', $groupItem);
        } elseif ($groupItem === 'N/A' || $groupItem === '') {
            $query->where(function ($q) {
                $q->whereNull('vineta_registros.producto_item')
                  ->orWhere('vineta_registros.producto_item', '');
            });
        }

        if ($groupCodigo !== '' && $groupCodigo !== 'N/A') {
            $query->where('vineta_registros.producto_codigo', $groupCodigo);
        } elseif ($groupCodigo === 'N/A' || $groupCodigo === '') {
            $query->where(function ($q) {
                $q->whereNull('vineta_registros.producto_codigo')
                  ->orWhere('vineta_registros.producto_codigo', '');
            });
        }

        if ($groupEmpaque !== '' && $groupEmpaque !== 'N/A') {
            $query->where('vineta_registros.tipo_empaque', $groupEmpaque);
        } elseif ($groupEmpaque === 'N/A' || $groupEmpaque === '') {
            $query->where(function ($q) {
                $q->whereNull('vineta_registros.tipo_empaque')
                  ->orWhere('vineta_registros.tipo_empaque', '');
            });
        }

        if ($fechaInicio !== '') {
            $query->whereDate('vineta_registros.fecha_registro', '>=', $fechaInicio);
        }

        if ($fechaFin !== '') {
            $query->whereDate('vineta_registros.fecha_registro', '<=', $fechaFin);
        }

        if ($empleado !== '') {
            $query->where(function ($q) use ($empleado) {
                $q->where('vineta_registros.empleado_codigo', 'like', "%{$empleado}%")
                    ->orWhere('vineta_registros.empleado_nombre', 'like', "%{$empleado}%");
            });
        }

        $registros = $query->select([
            'vineta_registros.id',
            'vineta_registros.vineta_id',
            'vineta_registros.vineta_api_id',
            'vineta_registros.codigo_vineta',
            'vineta_registros.empleado_codigo',
            'vineta_registros.empleado_nombre',
            'vineta_registros.cantidad_puros',
            'vineta_registros.fecha_registro',
            'vineta_registros.hora_registro',
            'vineta_registros.registrado_en',
        ])
        ->orderBy('vineta_registros.empleado_nombre', 'asc')
        ->orderBy('vineta_registros.fecha_registro', 'desc')
        ->orderBy('vineta_registros.hora_registro', 'desc')
        ->get();

        $totalPuros = (int) $registros->sum('cantidad_puros');
        $totalVinetas = $registros->count();

        // Agrupación por empleado
        $empleados = $registros->groupBy(function ($r) {
            $code = trim((string) $r->empleado_codigo);
            $name = trim((string) $r->empleado_nombre);
            return $code . '||' . $name;
        })->map(function ($items) {
            $first = $items->first();
            return (object) [
                'codigo' => $first->empleado_codigo ?: 'S/C',
                'nombre' => $first->empleado_nombre ?: 'Sin nombre asignado',
                'total_puros' => (int) $items->sum('cantidad_puros'),
                'total_vinetas' => $items->count(),
                'vinetas' => $items->map(function ($v) {
                    $fechaFormateada = $v->fecha_registro
                        ? Carbon::parse($v->fecha_registro)->format('d/m/Y')
                        : 'S/F';
                    $horaFormateada = $v->hora_registro
                        ? Carbon::parse($v->hora_registro)->format('h:i A')
                        : '';
                    return (object) [
                        'id_registro' => $v->id,
                        'vineta_id' => $v->vineta_id,
                        'vineta_api_id' => $v->vineta_api_id,
                        'id_display' => $v->vineta_api_id ?: ($v->vineta_id ?: 'N/A'),
                        'codigo_vineta' => $v->codigo_vineta ?: 'N/A',
                        'fecha' => $fechaFormateada,
                        'hora' => $horaFormateada,
                        'cantidad_puros' => (int) $v->cantidad_puros,
                    ];
                })->values(),
            ];
        })->sortByDesc('total_puros')->values();

        $viewData = [
            'actividad' => $actividad ?: 'Actividad sin nombre',
            'orden_del_sistema' => $groupOS,
            'orden_cliente' => $groupOC,
            'producto_codigo' => $groupCodigo,
            'producto_item' => $groupItem,
            'tipo_empaque' => $groupEmpaque,
            'titulo' => $titulo,
            'detalles' => $detalles,
            'total_puros' => $totalPuros,
            'total_vinetas' => $totalVinetas,
            'total_personas' => $empleados->count(),
            'empleados' => $empleados,
        ];

        return view('estadistico.partials.modal-detalle', $viewData)->render();
    }
}
