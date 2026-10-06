<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private string $timezone = 'America/Tegucigalpa';

    private array $areasProduccion = [
        'limpieza' => ['label' => 'Limpieza', 'color' => '#8b5cf6'],
        'rezago' => ['label' => 'Rezago', 'color' => '#2563eb'],
        'anillado' => ['label' => 'Anillado', 'color' => '#0891b2'],
        'llenado' => ['label' => 'Llenado', 'color' => '#6366f1'],
    ];

    public function index(Request $request)
    {
        $today = Carbon::now($this->timezone)->startOfDay();
        $selectedDay = $this->dateParam($request->get('fecha'), $today);
        $maxFutureDay = $today->copy()->addDays(14);
        $selectedDay = $selectedDay->gt($maxFutureDay) ? $today->copy() : $selectedDay;
        $selectedMonth = $this->monthParam($request->get('mes'), $today);
        $selectedYear = $this->yearParam($request->get('anio'), $today);
        $dayStart = $selectedDay->copy()->startOfDay();
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();
        $yearStart = Carbon::create($selectedYear, 1, 1, 0, 0, 0, $this->timezone)->startOfDay();
        $yearEnd = Carbon::create($selectedYear, 12, 31, 23, 59, 59, $this->timezone)->startOfDay();

        $hasRegistros = Schema::hasTable('vineta_registros');
        $hasHorasOrdinarias = Schema::hasTable('empleado_horas_ordinarias');
        $hasCantidadActividades = $hasRegistros && Schema::hasColumn('vineta_registros', 'cantidad_actividades');
        $hasMinutosTrabajados = $hasRegistros && Schema::hasColumn('vineta_registros', 'minutos_trabajados');

        $context = compact('hasRegistros', 'hasHorasOrdinarias', 'hasCantidadActividades', 'hasMinutosTrabajados');
        $dailyAreaSummary = $this->areaSummary($dayStart, $dayStart, $context);

        if ($request->boolean('distribucion_diaria')) {
            return response()->json([
                'fecha' => $selectedDay->format('Y-m-d'),
                'areas' => array_values($dailyAreaSummary),
            ]);
        }

        if ($request->boolean('ranking_mes') || ($request->ajax() && $request->has('ranking_mes'))) {
            $rankingMonth = $this->monthParam($request->get('mes'), $today);
            $from = $rankingMonth->copy()->startOfMonth();
            $to = $rankingMonth->copy()->endOfMonth();

            $ranking = $this->topEmployeesByArea($from, $to, $context);
            $monthLabel = ucfirst($rankingMonth->locale('es')->translatedFormat('F Y'));

            return response()->json([
                'ok' => true,
                'mes' => $rankingMonth->format('Y-m'),
                'label' => $monthLabel,
                'ranking' => $ranking,
            ]);
        }

        if ($request->boolean('resumen_rango') || ($request->ajax() && $request->has('fecha_desde') && $request->has('fecha_hasta'))) {
            $fechaDesdeStr = $request->get('fecha_desde');
            $fechaHastaStr = $request->get('fecha_hasta');
            $isCustomRange = ! empty($fechaDesdeStr) && ! empty($fechaHastaStr);

            if ($isCustomRange) {
                $rangeFrom = $this->dateParam($fechaDesdeStr, $monthStart)->startOfDay();
                $rangeTo = $this->dateParam($fechaHastaStr, $monthEnd)->startOfDay();

                if ($rangeFrom->gt($rangeTo)) {
                    [$rangeFrom, $rangeTo] = [$rangeTo, $rangeFrom];
                }

                $labelPeriodo = $rangeFrom->format('d/m/Y') === $rangeTo->format('d/m/Y')
                    ? $rangeFrom->format('d/m/Y')
                    : $rangeFrom->format('d/m/Y').' - '.$rangeTo->format('d/m/Y');
            } else {
                $rangeFrom = $monthStart->copy();
                $rangeTo = $monthEnd->copy();
                $labelPeriodo = ucfirst($selectedMonth->locale('es')->translatedFormat('F Y'));
            }

            $areasSummary = $this->areaSummary($rangeFrom, $rangeTo, $context);
            $totalPuros = max((int) collect($areasSummary)->sum('puros'), 1);
            $totalActividades = max((int) collect($areasSummary)->sum('actividades'), 1);

            $formattedAreas = [];
            foreach ($areasSummary as $key => $area) {
                $share = round(($area['puros'] / $totalPuros) * 100);
                $formattedAreas[$key] = [
                    'key' => $area['key'],
                    'label' => $area['label'],
                    'color' => $area['color'],
                    'actividades' => (int) $area['actividades'],
                    'actividades_formatted' => number_format((int) $area['actividades']),
                    'empleados' => (int) $area['empleados'],
                    'empleados_formatted' => number_format((int) $area['empleados']),
                    'registros' => (int) $area['registros'],
                    'registros_formatted' => number_format((int) $area['registros']),
                    'puros' => (int) $area['puros'],
                    'puros_formatted' => number_format((int) $area['puros']),
                    'share' => $share,
                ];
            }

            return response()->json([
                'ok' => true,
                'is_custom_range' => $isCustomRange,
                'label' => $labelPeriodo,
                'fecha_desde' => $rangeFrom->format('Y-m-d'),
                'fecha_hasta' => $rangeTo->format('Y-m-d'),
                'total_actividades' => $totalActividades,
                'total_puros' => $totalPuros,
                'areas' => $formattedAreas,
            ]);
        }

        $produccionHoy = $this->productionSummary($dayStart, $dayStart, $context);
        $produccionMes = $this->productionSummary($monthStart, $monthEnd, $context);
        $produccionAnio = $this->productionSummary($yearStart, $yearEnd, $context);
        $produccionTotal = $this->productionSummary(null, null, $context);

        $resumenAreas = [
            'dia' => $dailyAreaSummary,
            'mes' => $this->areaSummary($monthStart, $monthEnd, $context),
            'anio' => $this->areaSummary($yearStart, $yearEnd, $context),
        ];
        $tendenciasAreas = [
            'dia' => $this->areaTrendByDay($monthStart, $monthEnd, $context),
            'mes' => $this->areaTrendByMonth($yearStart, $yearEnd, $context),
            'anio' => $this->areaTrendByYear($selectedYear, $context),
        ];
        $rankingEmpleados = $this->topEmployeesByArea($monthStart, $monthEnd, $context);
        $ultimosRegistros = $this->latestProductionRecords($context);

        return view('dashboard', compact(
            'today',
            'selectedDay',
            'selectedMonth',
            'selectedYear',
            'produccionHoy',
            'produccionMes',
            'produccionAnio',
            'produccionTotal',
            'resumenAreas',
            'tendenciasAreas',
            'rankingEmpleados',
            'ultimosRegistros'
        ));
    }

    private function dateParam($value, Carbon $fallback): Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $value, $this->timezone);

            return $date ?: $fallback->copy();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function monthParam($value, Carbon $fallback): Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m', (string) $value, $this->timezone);

            return $date ? $date->startOfMonth() : $fallback->copy()->startOfMonth();
        } catch (\Throwable) {
            return $fallback->copy()->startOfMonth();
        }
    }

    private function yearParam($value, Carbon $fallback): int
    {
        $year = (int) $value;

        return $year >= 2020 && $year <= $fallback->copy()->addYear()->year
            ? $year
            : (int) $fallback->format('Y');
    }

    private function productionSummary(?Carbon $from, ?Carbon $to, array $context): array
    {
        $row = (object) [
            'registros' => 0,
            'puros' => 0,
            'cajones' => 0,
            'actividades' => 0,
            'minutos_cajones' => 0,
            'monto' => 0,
            'empleados' => 0,
        ];

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);

            $row = $this->activeRecordsQuery($from, $to)
                ->selectRaw('COUNT(*) as registros')
                ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_puros), 0) as puros')
                ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_cajones), 0) as cajones')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw('0 as minutos_cajones')
                ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_puros * COALESCE(vineta_registros.precio_mo, 0)), 0) as monto')
                ->selectRaw("COUNT(DISTINCT COALESCE(NULLIF(vineta_registros.empleado_codigo, ''), NULLIF(vineta_registros.empleado_nombre, ''))) as empleados")
                ->first();
        }

        $minutos = 0;
        $horas = 0;

        return [
            'registros' => (int) $row->registros,
            'puros' => (int) $row->puros,
            'cajones' => (int) $row->cajones,
            'actividades' => (int) $row->actividades,
            'minutos' => $minutos,
            'minutos_cajones' => (int) $row->minutos_cajones,
            'minutos_ordinarios' => 0,
            'tiempo' => VinetaRegistro::minutosATiempoTexto($minutos),
            'horas' => $horas,
            'monto' => (float) $row->monto,
            'empleados' => (int) $row->empleados,
            'actividades_por_hora' => $horas > 0 ? round(((int) $row->actividades) / $horas, 1) : 0,
        ];
    }

    private function areaSummary(?Carbon $from, ?Carbon $to, array $context): array
    {
        $summary = $this->emptyAreaSummary();

        if (! $context['hasRegistros']) {
            return $summary;
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
        $activityGroup = $this->activityGroupCaseExpression();

        $rows = $this->matchedRecordsQuery($from, $to)
            ->selectRaw("$activityGroup as grupo")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_puros), 0) as puros')
            ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_cajones), 0) as cajones')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->selectRaw("COUNT(DISTINCT COALESCE(NULLIF(vineta_registros.empleado_codigo, ''), NULLIF(vineta_registros.empleado_nombre, ''))) as empleados")
            ->groupByRaw($activityGroup)
            ->get();

        foreach ($rows as $row) {
            $grupo = (string) $row->grupo;

            if (! isset($summary[$grupo])) {
                continue;
            }

            $summary[$grupo]['registros'] = (int) $row->registros;
            $summary[$grupo]['puros'] = (int) $row->puros;
            $summary[$grupo]['cajones'] = (int) $row->cajones;
            $summary[$grupo]['actividades'] = (int) $row->actividades;
            $summary[$grupo]['empleados'] = (int) $row->empleados;
        }

        return $summary;
    }

    private function emptyAreaSummary(): array
    {
        return collect($this->areasProduccion)
            ->map(fn (array $area, string $key) => [
                'key' => $key,
                'label' => $area['label'],
                'color' => $area['color'],
                'registros' => 0,
                'puros' => 0,
                'cajones' => 0,
                'actividades' => 0,
                'empleados' => 0,
            ])
            ->all();
    }

    private function areaBreakdownFromSummary(array $summary): array
    {
        $rows = collect($summary)
            ->values()
            ->map(fn (array $area) => [
                'grupo' => $area['label'],
                'key' => $area['key'],
                'registros' => $area['registros'],
                'puros' => $area['puros'],
                'cajones' => $area['cajones'],
                'actividades' => $area['actividades'],
                'color' => $area['color'],
            ])
            ->all();

        return [
            'labels' => array_column($rows, 'grupo'),
            'data' => array_column($rows, 'puros'),
            'rows' => $rows,
        ];
    }

    private function areaTrendByDay(Carbon $from, Carbon $to, array $context): array
    {
        $keys = [];
        $labels = [];

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            $keys[] = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
        }

        return $this->areaTrend($keys, $labels, 'vineta_registros.fecha_registro', $from, $to, $context);
    }

    private function areaTrendByMonth(Carbon $from, Carbon $to, array $context): array
    {
        $keys = range(1, 12);
        $labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $monthExpr = $this->monthExpression('vineta_registros.fecha_registro');

        return $this->areaTrend($keys, $labels, $monthExpr, $from, $to, $context);
    }

    private function areaTrendByYear(int $selectedYear, array $context): array
    {
        $startYear = max($selectedYear - 4, 2000);
        $keys = range($startYear, $selectedYear);
        $labels = array_map(fn (int $year) => (string) $year, $keys);
        $from = Carbon::create($startYear, 1, 1, 0, 0, 0, $this->timezone)->startOfDay();
        $to = Carbon::create($selectedYear, 12, 31, 23, 59, 59, $this->timezone)->startOfDay();
        $yearExpr = $this->yearExpression('vineta_registros.fecha_registro');

        return $this->areaTrend($keys, $labels, $yearExpr, $from, $to, $context);
    }

    private function areaTrend(array $keys, array $labels, string $periodExpression, Carbon $from, Carbon $to, array $context): array
    {
        $series = collect($this->areasProduccion)
            ->mapWithKeys(fn (array $area, string $key) => [$key => array_fill(0, count($keys), 0)])
            ->all();

        if ($context['hasRegistros']) {
            $activityGroup = $this->activityGroupCaseExpression();
            $keyIndexes = array_flip(array_map('strval', $keys));

            $rows = $this->matchedRecordsQuery($from, $to)
                ->selectRaw("$periodExpression as periodo")
                ->selectRaw("$activityGroup as grupo")
                ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_puros), 0) as puros')
                ->groupByRaw($periodExpression)
                ->groupByRaw($activityGroup)
                ->get();

            foreach ($rows as $row) {
                $grupo = (string) $row->grupo;
                $periodo = (string) $row->periodo;

                if (! isset($series[$grupo], $keyIndexes[$periodo])) {
                    continue;
                }

                $series[$grupo][$keyIndexes[$periodo]] = (int) $row->puros;
            }
        }

        return [
            'labels' => $labels,
            'areas' => collect($this->areasProduccion)
                ->mapWithKeys(fn (array $area, string $key) => [$key => [
                    'label' => $area['label'],
                    'color' => $area['color'],
                    'data' => $series[$key],
                ]])
                ->all(),
        ];
    }

    private function dailyTrend(Carbon $from, Carbon $to, array $context): array
    {
        $labels = [];
        $keys = [];

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            $key = $date->format('Y-m-d');
            $keys[] = $key;
            $labels[] = $date->format('d/m');
        }

        $rows = collect();

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
            $minutesExpression = $context['hasMinutosTrabajados']
                ? 'COALESCE(SUM(minutos_trabajados), 0)'
                : '0';

            $rows = $this->activeRecordsQuery($from, $to)
                ->selectRaw('fecha_registro as fecha')
                ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw("$minutesExpression as minutos")
                ->groupBy('fecha_registro')
                ->get()
                ->keyBy(fn ($row) => (string) $row->fecha);
        }

        $ordinaryMinutes = $this->ordinaryMinutesByDate($from, $to, $context['hasHorasOrdinarias']);

        return [
            'labels' => $labels,
            'actividades' => array_map(fn ($key) => (int) ($rows[$key]->actividades ?? 0), $keys),
            'puros' => array_map(fn ($key) => (int) ($rows[$key]->puros ?? 0), $keys),
            'horas' => array_map(function ($key) use ($rows, $ordinaryMinutes) {
                $minutes = (int) ($rows[$key]->minutos ?? 0) + (int) ($ordinaryMinutes[$key] ?? 0);

                return round($minutes / 60, 1);
            }, $keys),
        ];
    }

    private function monthlyTrend(Carbon $today, array $context): array
    {
        $yearStart = $today->copy()->startOfYear();
        $labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $months = range(1, 12);
        $rows = collect();

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
            $minutesExpression = $context['hasMinutosTrabajados']
                ? 'COALESCE(SUM(minutos_trabajados), 0)'
                : '0';
            $monthExpr = $this->monthExpression('fecha_registro');

            $rows = $this->activeRecordsQuery($yearStart, $today)
                ->selectRaw("{$monthExpr} as mes")
                ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw("$minutesExpression as minutos")
                ->groupByRaw($monthExpr)
                ->get()
                ->keyBy(fn ($row) => (int) $row->mes);
        }

        $ordinaryMinutes = $this->ordinaryMinutesByMonth($yearStart, $today, $context['hasHorasOrdinarias']);

        return [
            'labels' => $labels,
            'actividades' => array_map(fn ($month) => (int) ($rows[$month]->actividades ?? 0), $months),
            'puros' => array_map(fn ($month) => (int) ($rows[$month]->puros ?? 0), $months),
            'horas' => array_map(function ($month) use ($rows, $ordinaryMinutes) {
                $minutes = (int) ($rows[$month]->minutos ?? 0) + (int) ($ordinaryMinutes[$month] ?? 0);

                return round($minutes / 60, 1);
            }, $months),
        ];
    }

    private function topEmployees(Carbon $from, Carbon $to, array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
        $minutesExpression = $context['hasMinutosTrabajados']
            ? 'COALESCE(SUM(minutos_trabajados), 0)'
            : '0';

        $rows = $this->activeRecordsQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF(empleado_codigo, ''), 'N/A') as codigo")
            ->selectRaw("COALESCE(NULLIF(empleado_nombre, ''), 'Empleado') as nombre")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->selectRaw("$minutesExpression as minutos")
            ->groupBy('empleado_codigo', 'empleado_nombre')
            ->orderByDesc('actividades')
            ->limit(8)
            ->get();

        return $rows->map(fn ($row) => [
            'codigo' => $row->codigo,
            'nombre' => $row->nombre,
            'registros' => (int) $row->registros,
            'puros' => (int) $row->puros,
            'actividades' => (int) $row->actividades,
            'tiempo' => VinetaRegistro::minutosATiempoTexto((int) $row->minutos),
        ])->all();
    }

    private function topEmployeesByArea(Carbon $from, Carbon $to, array $context): array
    {
        $ranking = collect($this->areasProduccion)
            ->mapWithKeys(fn (array $area, string $key) => [$key => [
                'key' => $key,
                'label' => $area['label'],
                'color' => $area['color'],
                'rows' => [],
            ]])
            ->all();

        if (! $context['hasRegistros']) {
            return $ranking;
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
        $activityGroup = $this->activityGroupCaseExpression();

        $rows = $this->matchedRecordsQuery($from, $to)
            ->selectRaw("$activityGroup as grupo")
            ->selectRaw("COALESCE(NULLIF(vineta_registros.empleado_codigo, ''), 'N/A') as codigo")
            ->selectRaw("COALESCE(NULLIF(vineta_registros.empleado_nombre, ''), 'Empleado') as nombre")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(vineta_registros.cantidad_puros), 0) as puros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->groupByRaw($activityGroup)
            ->groupBy('vineta_registros.empleado_codigo', 'vineta_registros.empleado_nombre')
            ->orderByDesc('actividades')
            ->get();

        foreach ($rows->groupBy('grupo') as $grupo => $items) {
            if (! isset($ranking[$grupo])) {
                continue;
            }

            $ranking[$grupo]['rows'] = $items
                ->take(10)
                ->map(fn ($row) => [
                    'codigo' => $row->codigo,
                    'nombre' => $row->nombre,
                    'registros' => (int) $row->registros,
                    'puros' => (int) $row->puros,
                    'actividades' => (int) $row->actividades,
                ])
                ->values()
                ->all();
        }

        return $ranking;
    }

    private function topActivities(Carbon $from, Carbon $to, array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);

        return $this->activeRecordsQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF(actividad_nombre, ''), 'Actividad') as nombre")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->groupBy('actividad_nombre')
            ->orderByDesc('actividades')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'nombre' => $row->nombre,
                'registros' => (int) $row->registros,
                'puros' => (int) $row->puros,
                'actividades' => (int) $row->actividades,
            ])
            ->all();
    }

    private function processBreakdown(Carbon $from, Carbon $to, array $context): array
    {
        return $this->areaBreakdownFromSummary($this->areaSummary($from, $to, $context));
    }

    private function latestProductionRecords(array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        return $this->matchedRecordsQuery()
            ->orderByDesc('vineta_registros.fecha_registro')
            ->orderByDesc('vineta_registros.hora_registro')
            ->orderByDesc('vineta_registros.id')
            ->limit(8)
            ->get([
                'vineta_registros.vineta_api_id',
                'vineta_registros.codigo_vineta',
                'vineta_registros.fecha_registro',
                'vineta_registros.hora_registro',
                'vineta_registros.empleado_nombre',
                'vineta_registros.actividad_nombre',
                'vineta_registros.marca',
                'vineta_registros.cantidad_puros',
            ])
            ->map(fn ($row) => [
                'vineta' => $row->vineta_api_id ? '#'.$row->vineta_api_id : ($row->codigo_vineta ?: 'N/A'),
                'fecha' => Carbon::parse($row->fecha_registro)->format('d/m/Y'),
                'hora' => $row->hora_registro ? Carbon::parse($row->hora_registro)->format('h:i A') : 'N/A',
                'empleado' => $row->empleado_nombre ?: 'Empleado',
                'actividad' => $row->actividad_nombre ?: 'Actividad',
                'marca' => $row->marca ?: 'Sin marca',
                'puros' => (int) $row->cantidad_puros,
            ])
            ->all();
    }

    private function activeRecordsQuery(?Carbon $from = null, ?Carbon $to = null)
    {
        return DB::table('vineta_registros')
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->when($from, fn ($query) => $query->whereDate('fecha_registro', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('fecha_registro', '<=', $to->toDateString()));
    }

    private function matchedRecordsQuery(?Carbon $from = null, ?Carbon $to = null)
    {
        $activityGroup = $this->activityGroupCaseExpression();

        return DB::table('vineta_registros')
            ->where('vineta_registros.estado', VinetaRegistro::ESTADO_ACTIVO)
            ->when($from, fn ($query) => $query->whereDate('vineta_registros.fecha_registro', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('vineta_registros.fecha_registro', '<=', $to->toDateString()))
            ->whereRaw("$activityGroup IN ('limpieza', 'rezago', 'anillado', 'llenado')");
    }

    private function ordinaryMinutes(?Carbon $from, ?Carbon $to, bool $hasHorasOrdinarias): int
    {
        if (! $hasHorasOrdinarias) {
            return 0;
        }

        return (int) DB::table('empleado_horas_ordinarias')
            ->when($from, fn ($query) => $query->whereDate('fecha', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('fecha', '<=', $to->toDateString()))
            ->sum('minutos');
    }

    private function ordinaryMinutesByDate(Carbon $from, Carbon $to, bool $hasHorasOrdinarias): array
    {
        if (! $hasHorasOrdinarias) {
            return [];
        }

        return DB::table('empleado_horas_ordinarias')
            ->whereDate('fecha', '>=', $from->toDateString())
            ->whereDate('fecha', '<=', $to->toDateString())
            ->selectRaw('fecha, COALESCE(SUM(minutos), 0) as minutos')
            ->groupBy('fecha')
            ->pluck('minutos', 'fecha')
            ->map(fn ($minutes) => (int) $minutes)
            ->all();
    }

    private function ordinaryMinutesByMonth(Carbon $from, Carbon $to, bool $hasHorasOrdinarias): array
    {
        if (! $hasHorasOrdinarias) {
            return [];
        }

        $monthExpr = $this->monthExpression('fecha');

        return DB::table('empleado_horas_ordinarias')
            ->whereDate('fecha', '>=', $from->toDateString())
            ->whereDate('fecha', '<=', $to->toDateString())
            ->selectRaw("{$monthExpr} as mes, COALESCE(SUM(minutos), 0) as minutos")
            ->groupByRaw($monthExpr)
            ->pluck('minutos', 'mes')
            ->map(fn ($minutes) => (int) $minutes)
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }

    private function yearExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', {$column}) AS INTEGER)"
            : "YEAR({$column})";
    }

    private function activityExpression(bool $hasCantidadActividades, string $table = 'vineta_registros'): string
    {
        if (! $hasCantidadActividades) {
            return "$table.cantidad_puros";
        }

        return "$table.cantidad_puros * CASE WHEN $table.cantidad_actividades IS NULL OR $table.cantidad_actividades < 1 THEN 1 ELSE $table.cantidad_actividades END";
    }

    private function concatExpression(array $columns, string $separator = ' '): string
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $coalesced = array_map(fn ($col) => "COALESCE({$col}, '')", $columns);

        if ($isSqlite) {
            return '(' . implode(" || '{$separator}' || ", $coalesced) . ')';
        }

        return 'CONCAT(' . implode(", '{$separator}', ", $coalesced) . ')';
    }

    private function activityGroupCaseExpression(): string
    {
        $nombre = "LOWER(COALESCE(vineta_registros.actividad_nombre, ''))";
        $text = "LOWER(" . $this->concatExpression([
            'vineta_registros.actividad_nombre',
            'vineta_registros.actividad_tipo_empaque',
            'vineta_registros.actividad_codigo',
        ]) . ")";

        return "CASE
            WHEN $nombre LIKE '%limpi%' AND $nombre NOT LIKE '%llenado de bolsa%' THEN 'limpieza'
            WHEN $nombre LIKE '%rezag%' OR $nombre LIKE '%rezad%' OR $nombre LIKE '%resag%' OR $nombre LIKE '%rezurado%' OR $nombre LIKE '%rasurado%' THEN 'rezago'
            WHEN $nombre LIKE '%anill%' OR $nombre LIKE '%anil%' OR $nombre LIKE '%celof%' OR $nombre LIKE '%cello%' OR $nombre LIKE '%sello%' OR $nombre LIKE '%esponj%' OR $nombre LIKE '%lamina%' OR $nombre LIKE '%lámina%' OR $nombre LIKE '%tapon%' OR $nombre LIKE '%tapón%' OR $nombre LIKE '%banda%' OR $nombre LIKE '%cinta%' OR $nombre LIKE '%rolado%' THEN 'anillado'
            WHEN ($nombre LIKE '%llenad%' OR $nombre LIKE '%kretek%' OR $nombre LIKE '%display%' OR $nombre LIKE '%bolsa%' OR $nombre LIKE '%bolsas%' OR $nombre LIKE '%caja%' OR $nombre LIKE '%paquet%' OR $nombre LIKE '%sellado%' OR $nombre LIKE '%costura%' OR $nombre LIKE '%jarra%' OR $nombre LIKE '%petaca%' OR $nombre LIKE '%sampler%' OR ($nombre LIKE '%paquete%' AND $nombre LIKE '%tubo%') OR $nombre LIKE '%doblado de bolsa%') AND $nombre NOT LIKE '%anill%' AND $nombre NOT LIKE '%celof%' AND $nombre NOT LIKE '%cello%' AND $nombre NOT LIKE '%lamina%' AND $nombre NOT LIKE '%esponj%' THEN 'llenado'
            WHEN $text LIKE '%limpi%' AND $text NOT LIKE '%llenado de bolsa%' THEN 'limpieza'
            WHEN $text LIKE '%rezag%' OR $text LIKE '%rezad%' OR $text LIKE '%resag%' OR $text LIKE '%rezurado%' OR $text LIKE '%rasurado%' THEN 'rezago'
            WHEN $text LIKE '%anill%' OR $text LIKE '%anil%' OR $text LIKE '%celof%' OR $text LIKE '%cello%' OR $text LIKE '%sello%' OR $text LIKE '%esponj%' OR $text LIKE '%lamina%' OR $text LIKE '%lámina%' OR $text LIKE '%tapon%' OR $text LIKE '%tapón%' OR $text LIKE '%banda%' OR $text LIKE '%cinta%' OR $text LIKE '%rolado%' THEN 'anillado'
            WHEN ($text LIKE '%llenad%' OR $text LIKE '%kretek%' OR $text LIKE '%display%' OR $text LIKE '%bolsa%' OR $text LIKE '%bolsas%' OR $text LIKE '%caja%' OR $text LIKE '%paquet%' OR $text LIKE '%sellado%' OR $text LIKE '%costura%' OR $text LIKE '%jarra%' OR $text LIKE '%petaca%' OR $text LIKE '%sampler%' OR ($text LIKE '%paquete%' AND $text LIKE '%tubo%') OR $text LIKE '%doblado de bolsa%') AND $text NOT LIKE '%anill%' AND $text NOT LIKE '%celof%' AND $text NOT LIKE '%cello%' AND $text NOT LIKE '%lamina%' AND $text NOT LIKE '%esponj%' THEN 'llenado'
            ELSE 'otros'
        END";
    }

    private function employeeGroupCaseExpression(): string
    {
        $text = "LOWER(" . $this->concatExpression([
            'empleados_dashboard.cargo',
            'empleados_dashboard.area',
            'vineta_registros.empleado_nombre',
        ]) . ")";

        return "CASE
            WHEN vineta_registros.empleado_codigo IN ('8219', '8217') OR empleados_dashboard.codigo IN ('8219', '8217') THEN 'rezago'
            WHEN $text LIKE '%rezag%' OR $text LIKE '%rezad%' OR $text LIKE '%resag%' THEN 'rezago'
            WHEN $text LIKE '%limpia%' OR $text LIKE '%limpi%' THEN 'limpieza'
            WHEN $text LIKE '%anill%' OR $text LIKE '%anil%' OR $text LIKE '%celof%' OR $text LIKE '%sello%' OR $text LIKE '%esponj%' OR $text LIKE '%lamina%' OR $text LIKE '%lámina%' OR $text LIKE '%pega%' OR $text LIKE '%etiquet%' THEN 'anillado'
            WHEN $text LIKE '%llenad%' OR $text LIKE '%embasad%' OR $text LIKE '%paquet%' OR $text LIKE '%sellado%' OR $text LIKE '%display%' OR $text LIKE '%bolsa%' OR $text LIKE '%kretek%' OR $text LIKE '%petaca%' OR $text LIKE '%sampler%' THEN 'llenado'
            ELSE 'otros'
        END";
    }

    private function processCaseExpression(): string
    {
        $nombre = "LOWER(COALESCE(actividad_nombre, ''))";
        $text = "LOWER(" . $this->concatExpression([
            'actividad_nombre',
            'actividad_tipo_empaque',
            'actividad_codigo',
        ]) . ")";

        return "CASE
            WHEN $nombre LIKE '%limpi%' AND $nombre NOT LIKE '%llenado de bolsa%' THEN 'Limpieza'
            WHEN $nombre LIKE '%rezag%' OR $nombre LIKE '%rezad%' OR $nombre LIKE '%resag%' OR $nombre LIKE '%rezurado%' OR $nombre LIKE '%rasurado%' THEN 'Rezago'
            WHEN $nombre LIKE '%anill%' OR $nombre LIKE '%anil%' OR $nombre LIKE '%celof%' OR $nombre LIKE '%cello%' OR $nombre LIKE '%sello%' OR $nombre LIKE '%esponj%' OR $nombre LIKE '%lamina%' OR $nombre LIKE '%lámina%' OR $nombre LIKE '%tapon%' OR $nombre LIKE '%tapón%' OR $nombre LIKE '%banda%' OR $nombre LIKE '%cinta%' OR $nombre LIKE '%rolado%' THEN 'Anillado'
            WHEN ($nombre LIKE '%llenad%' OR $nombre LIKE '%kretek%' OR $nombre LIKE '%display%' OR $nombre LIKE '%bolsa%' OR $nombre LIKE '%bolsas%' OR $nombre LIKE '%caja%' OR $nombre LIKE '%paquet%' OR $nombre LIKE '%sellado%' OR $nombre LIKE '%costura%' OR $nombre LIKE '%jarra%' OR $nombre LIKE '%petaca%' OR $nombre LIKE '%sampler%' OR ($nombre LIKE '%paquete%' AND $nombre LIKE '%tubo%') OR $nombre LIKE '%doblado de bolsa%') AND $nombre NOT LIKE '%anill%' AND $nombre NOT LIKE '%celof%' AND $nombre NOT LIKE '%cello%' AND $nombre NOT LIKE '%lamina%' AND $nombre NOT LIKE '%esponj%' THEN 'Llenado'
            WHEN $text LIKE '%limpi%' AND $text NOT LIKE '%llenado de bolsa%' THEN 'Limpieza'
            WHEN $text LIKE '%rezag%' OR $text LIKE '%rezad%' OR $text LIKE '%resag%' OR $text LIKE '%rezurado%' OR $text LIKE '%rasurado%' THEN 'Rezago'
            WHEN $text LIKE '%anill%' OR $text LIKE '%anil%' OR $text LIKE '%celof%' OR $text LIKE '%cello%' OR $text LIKE '%sello%' OR $text LIKE '%esponj%' OR $text LIKE '%lamina%' OR $text LIKE '%lámina%' OR $text LIKE '%tapon%' OR $text LIKE '%tapón%' OR $text LIKE '%banda%' OR $text LIKE '%cinta%' OR $text LIKE '%rolado%' THEN 'Anillado'
            WHEN ($text LIKE '%llenad%' OR $text LIKE '%kretek%' OR $text LIKE '%display%' OR $text LIKE '%bolsa%' OR $text LIKE '%bolsas%' OR $text LIKE '%caja%' OR $text LIKE '%paquet%' OR $text LIKE '%sellado%' OR $text LIKE '%costura%' OR $text LIKE '%jarra%' OR $text LIKE '%petaca%' OR $text LIKE '%sampler%' OR ($text LIKE '%paquete%' AND $text LIKE '%tubo%') OR $text LIKE '%doblado de bolsa%') AND $text NOT LIKE '%anill%' AND $text NOT LIKE '%celof%' AND $text NOT LIKE '%cello%' AND $text NOT LIKE '%lamina%' AND $text NOT LIKE '%esponj%' THEN 'Llenado'
            ELSE 'Otros'
        END";
    }


}
