<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $estado = $request->get('estado');
        $cargo = $request->get('cargo');
        $area = $request->get('area');
        $orden = $request->get('orden', 'nombre');
        $direccion = $request->get('direccion', 'asc');

        $ordenesPermitidos = [
            'codigo',
            'nombre',
            'cargo',
            'area',
            'fecha_ingreso',
            'activo',
        ];

        if (! in_array($orden, $ordenesPermitidos)) {
            $orden = 'nombre';
        }

        if (! in_array($direccion, ['asc', 'desc'])) {
            $direccion = 'asc';
        }

        $query = Empleado::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('cargo', 'like', "%{$buscar}%")
                        ->orWhere('area', 'like', "%{$buscar}%");
                });
            })
            ->when($estado === 'activos', function ($query) {
                $query->where('activo', true);
            })
            ->when($estado === 'baja', function ($query) {
                $query->where('activo', false);
            })
            ->when($cargo, function ($query) use ($cargo) {
                $query->where('cargo', $cargo);
            })
            ->when($area, function ($query) use ($area) {
                $query->where('area', $area);
            })
            ->orderBy($orden, $direccion);

        $perPageInput = $request->get('per_page', 10);

        if ($perPageInput === 'all') {
            $perPage = max((clone $query)->count(), 1);
        } else {
            $perPage = (int) $perPageInput;

            if (! in_array($perPage, [10, 25, 50, 100])) {
                $perPage = 10;
            }
        }

        $empleados = $query
            ->paginate($perPage)
            ->appends($request->query());

        $cargos = Empleado::query()
            ->whereNotNull('cargo')
            ->select('cargo')
            ->distinct()
            ->orderBy('cargo')
            ->pluck('cargo');

        $areas = Empleado::query()
            ->whereNotNull('area')
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        if ($request->ajax()) {
            return view('empleados.partials.tabla', compact(
                'empleados',
                'orden',
                'direccion'
            ))->render();
        }

        return view('empleados.index', compact(
            'empleados',
            'cargos',
            'areas',
            'orden',
            'direccion'
        ));
    }

    /**
     * Hoja imprimible con el QR de los empleados seleccionados.
     */
    public function qr(Request $request)
    {
        $codigos = $request->input('codigos', []);

        if (is_string($codigos)) {
            $codigos = explode(',', $codigos);
        }

        $codigos = collect($codigos)
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return redirect()
                ->route('empleados.index')
                ->with('error', 'Selecciona al menos un empleado para generar los QR.');
        }

        $empleados = Empleado::query()
            ->whereIn('codigo', $codigos)
            ->orderBy('nombre')
            ->get();

        if ($empleados->isEmpty()) {
            return redirect()
                ->route('empleados.index')
                ->with('error', 'No se encontraron empleados con los códigos seleccionados.');
        }

        return view('empleados.qr', compact('empleados'));
    }

    public function sincronizar()
    {
        try {
            $url = env('API_EMPLEADOS_URL', 'http://192.168.2.7:8080/api/nomina/empaque');

            $response = Http::timeout(60)
                ->connectTimeout(15)
                ->get($url);

            if (! $response->successful()) {
                return redirect()
                    ->back()
                    ->with('error', 'La API de empleados respondió con error: ' . $response->status());
            }

            $json = $response->json();

            /*
             * Soporta dos formatos:
             * 1. [ {...}, {...} ]
             * 2. { "data": [ {...}, {...} ] }
             */
            $empleadosApi = $json['data'] ?? $json;

            if (! is_array($empleadosApi)) {
                return redirect()
                    ->back()
                    ->with('error', 'La respuesta de la API de empleados no tiene un formato válido.');
            }

            $procesados = 0;
            $omitidos = 0;

            foreach ($empleadosApi as $item) {
                if (empty($item['codigo'])) {
                    $omitidos++;
                    continue;
                }

                $fechaBaja = ! empty($item['fecha_baja'])
                    ? Carbon::parse($item['fecha_baja'])
                    : null;

                Empleado::updateOrCreate(
                    [
                        'codigo' => trim($item['codigo']),
                    ],
                    [
                        'nombre' => trim($item['nombre'] ?? 'Sin nombre'),
                        'fecha_ingreso' => ! empty($item['fecha_ingreso'])
                            ? Carbon::parse($item['fecha_ingreso'])
                            : null,
                        'cargo' => $item['cargo'] ?? null,
                        'fecha_baja' => $fechaBaja,
                        'area' => $item['area'] ?? null,
                        'activo' => is_null($fechaBaja),
                    ]
                );

                $procesados++;
            }

            return redirect()
                ->back()
                ->with('success', "Empleados sincronizados correctamente. Procesados: {$procesados}. Omitidos: {$omitidos}.");

        } catch (ConnectionException $e) {
            return redirect()
                ->back()
                ->with('error', 'No se pudo conectar con la API de empleados. Revisa la red, la IP 192.168.2.7 y el puerto 8080.');

        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al sincronizar empleados: ' . $e->getMessage());
        }
    }
}
