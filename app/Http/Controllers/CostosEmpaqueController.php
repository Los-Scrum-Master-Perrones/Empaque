<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CostosEmpaqueController extends Controller
{
    private static ?bool $hasTableVinetaRegistros = null;
    private static ?bool $hasCantidadActividadesCol = null;

    public function index(Request $request)
    {
        if (self::$hasTableVinetaRegistros === null) {
            self::$hasTableVinetaRegistros = Schema::hasTable('vineta_registros');
        }
        $migrationPending = ! self::$hasTableVinetaRegistros;

        if ($migrationPending) {
            $perPageOptions = PerPageOptions::forTotal(0);
            $perPageSelected = PerPageOptions::resolve($request->get('per_page', 25), 0, 25);
            $perPage = PerPageOptions::pageSize($perPageSelected, 0);

            $viewData = [
                'filas' => new LengthAwarePaginator([], 0, $perPage, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'totales' => [
                    'cantidad_trabajada' => 0,
                    'cantidad_pagada' => 0,
                    'total_mod' => 0,
                    'cantidad_ctrl_calidad' => 0,
                    'h_trabajada' => 0,
                ],
                'orden' => 'fecha_registro',
                'direccion' => 'desc',
                'migrationPending' => true,
                'perPageOptions' => $perPageOptions,
                'perPageSelected' => $perPageSelected,
            ];

            if ($request->ajax()) {
                return view('costos-empaque.partials.tabla', $viewData)->render();
            }

            return view('costos-empaque.index', $viewData);
        }

        $fechaDesde = trim((string) $request->get('fecha_desde', ''));
        $fechaHasta = trim((string) $request->get('fecha_hasta', ''));
        $empleado = trim((string) $request->get('empleado', ''));

        $orden = $request->get('orden', 'fecha_registro');
        $direccion = strtolower((string) $request->get('direccion', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (self::$hasCantidadActividadesCol === null) {
            self::$hasCantidadActividadesCol = Schema::hasColumn('vineta_registros', 'cantidad_actividades');
        }
        $hasCantidadActividades = self::$hasCantidadActividadesCol;
        $activityMultiplier = $hasCantidadActividades
            ? 'CASE WHEN vineta_registros.cantidad_actividades IS NULL OR vineta_registros.cantidad_actividades < 1 THEN 1 ELSE vineta_registros.cantidad_actividades END'
            : '1';

        $activityExpr = "vineta_registros.cantidad_puros * ($activityMultiplier)";
        $totalModExpr = "($activityExpr) * COALESCE(vineta_registros.precio_mo, 0)";

        $query = DB::table('vineta_registros')
            ->leftJoin('productos', 'productos.id', '=', 'vineta_registros.producto_id')
            ->leftJoin('presentaciones', 'presentaciones.id', '=', 'productos.presentacion_id')
            ->where('vineta_registros.estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereNull('vineta_registros.anulado_en');

        if ($fechaDesde !== '') {
            $query->where('vineta_registros.fecha_registro', '>=', $fechaDesde);
        }

        if ($fechaHasta !== '') {
            $query->where('vineta_registros.fecha_registro', '<=', $fechaHasta);
        }

        if ($empleado !== '') {
            $query->where(function ($q) use ($empleado) {
                $q->where('vineta_registros.empleado_codigo', 'like', "%{$empleado}%")
                    ->orWhere('vineta_registros.empleado_nombre', 'like', "%{$empleado}%");
            });
        }

        $query->groupBy([
            'vineta_registros.fecha_registro',
            'vineta_registros.empleado_codigo',
            'vineta_registros.empleado_nombre',
            'vineta_registros.producto_item',
            'presentaciones.nombre',
            'vineta_registros.producto_codigo',
            'vineta_registros.marca',
            'vineta_registros.producto_nombre',
            'vineta_registros.vitola',
            'vineta_registros.capa',
            'vineta_registros.tipo_empaque',
            'vineta_registros.orden_del_sistema',
            'vineta_registros.orden',
            'vineta_registros.actividad_nombre',
            'vineta_registros.actividad_codigo',
            'vineta_registros.precio_mo',
        ]);

        $query->select([
            'vineta_registros.fecha_registro',
            'vineta_registros.empleado_codigo',
            'vineta_registros.empleado_nombre',
            'vineta_registros.producto_item',
            DB::raw("COALESCE(presentaciones.nombre, 'N/A') as presentacion"),
            'vineta_registros.producto_codigo',
            'vineta_registros.marca',
            'vineta_registros.producto_nombre',
            'vineta_registros.vitola',
            'vineta_registros.capa',
            'vineta_registros.tipo_empaque',
            'vineta_registros.orden_del_sistema',
            'vineta_registros.orden as orden_cliente',
            'vineta_registros.actividad_nombre',
            'vineta_registros.actividad_codigo',
            'vineta_registros.precio_mo as precio_unitario',
            DB::raw("COALESCE(SUM({$activityExpr}), 0) as cantidad_trabajada"),
            DB::raw("COALESCE(SUM({$activityExpr}), 0) as cantidad_pagada"),
            DB::raw("COALESCE(SUM({$totalModExpr}), 0) as total_mod"),
            DB::raw("COALESCE(SUM({$activityExpr}), 0) as cantidad_ctrl_calidad"),
            DB::raw("COALESCE(SUM(vineta_registros.cantidad_cajones), 0) as h_trabajada"),
            DB::raw("COUNT(vineta_registros.id) as total_registros"),
        ]);

        $columnasOrdenables = [
            'fecha' => ['vineta_registros.fecha_registro'],
            'fecha_registro' => ['vineta_registros.fecha_registro'],
            'empleado' => ['vineta_registros.empleado_nombre', 'vineta_registros.empleado_codigo'],
            'item' => ['vineta_registros.producto_item'],
            'producto_item' => ['vineta_registros.producto_item'],
            'presentacion' => ['presentacion'],
            'codigo_producto' => ['vineta_registros.producto_codigo'],
            'producto_codigo' => ['vineta_registros.producto_codigo'],
            'marca' => ['vineta_registros.marca'],
            'nombre' => ['vineta_registros.producto_nombre'],
            'producto_nombre' => ['vineta_registros.producto_nombre'],
            'vitola' => ['vineta_registros.vitola'],
            'capa' => ['vineta_registros.capa'],
            'tipo_empaque' => ['vineta_registros.tipo_empaque'],
            'orden_del_sistema' => ['vineta_registros.orden_del_sistema'],
            'orden_cliente' => ['vineta_registros.orden'],
            'orden' => ['vineta_registros.orden'],
            'actividad' => ['vineta_registros.actividad_nombre'],
            'actividad_nombre' => ['vineta_registros.actividad_nombre'],
            'cantidad_trabajada' => ['cantidad_trabajada'],
            'cantidad_pagada' => ['cantidad_pagada'],
            'precio_unitario' => ['precio_unitario'],
            'total_mod' => ['total_mod'],
            'cantidad_ctrl_calidad' => ['cantidad_ctrl_calidad'],
            'h_trabajada' => ['h_trabajada'],
        ];

        if (array_key_exists($orden, $columnasOrdenables)) {
            foreach ($columnasOrdenables[$orden] as $col) {
                $query->orderBy($col, $direccion);
            }
        } else {
            $query->orderBy('vineta_registros.fecha_registro', 'desc')
                ->orderBy('vineta_registros.empleado_nombre', 'asc')
                ->orderBy('vineta_registros.producto_item', 'asc');
        }

        $allRows = $query->get();

        $totales = [
            'cantidad_trabajada' => (int) $allRows->sum('cantidad_trabajada'),
            'cantidad_pagada' => (int) $allRows->sum('cantidad_pagada'),
            'total_mod' => (float) $allRows->sum('total_mod'),
            'cantidad_ctrl_calidad' => (int) $allRows->sum('cantidad_ctrl_calidad'),
            'h_trabajada' => (float) $allRows->sum('h_trabajada'),
        ];

        $totalFilas = $allRows->count();
        $perPageOptions = PerPageOptions::forTotal($totalFilas);
        $perPageSelected = PerPageOptions::resolve($request->get('per_page', 25), $totalFilas, 25);
        $perPage = PerPageOptions::pageSize($perPageSelected, $totalFilas);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $paginatedItems = $allRows->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $filas = new LengthAwarePaginator(
            $paginatedItems,
            $totalFilas,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $viewData = [
            'filas' => $filas,
            'totales' => $totales,
            'orden' => $orden,
            'direccion' => $direccion,
            'migrationPending' => false,
            'perPageOptions' => $perPageOptions,
            'perPageSelected' => $perPageSelected,
        ];

        if ($request->ajax()) {
            return view('costos-empaque.partials.tabla', $viewData)->render();
        }

        return view('costos-empaque.index', $viewData);
    }
}
