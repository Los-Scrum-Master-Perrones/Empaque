<?php

namespace App\Http\Controllers;

use App\Services\ErpComparativoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comparativo ERP vs sistema de Empaque.
 *
 * Baja del ERP (solo lectura) lo del dia a tablas espejo y muestra las
 * diferencias: documentos, detalle por empleado/actividad y planificacion.
 */
class EmpaqueComparativoErpController extends Controller
{
    public function index(Request $request, ErpComparativoService $servicio)
    {
        $fecha = $this->fecha($request->get('fecha'));
        $documento = trim((string) $request->get('documento', ''));
        $sucursal = (int) ($request->get('sucursal') ?: config('services.erp.default_sucursal', 2));
        if ($sucursal <= 0) {
            $sucursal = 2;
        }

        $tieneEspejo = Schema::hasTable('erp_empaques_documentos')
            && Schema::hasTable('erp_empaques_actividades')
            && Schema::hasTable('erp_empaques_detalles')
            && Schema::hasTable('erp_empaques_planificacion');

        $descargado = false;
        $resultado = ['documentos' => [], 'detalle' => [], 'planificacion' => [], 'resumen' => []];
        $ultimaDescarga = null;

        if ($tieneEspejo) {
            $descargado = DB::table('erp_empaques_documentos')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->exists();

            $ultimaDescarga = DB::table('erp_empaques_documentos')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->max('descargado_en');

            if ($descargado) {
                $resultado = $servicio->comparar($fecha, $sucursal);

                if ($documento !== '') {
                    $resultado['detalle'] = array_values(array_filter(
                        $resultado['detalle'],
                        fn (array $d) => (string) $d['numero'] === $documento
                    ));
                }
            }
        }

        return view('costos-empaque.comparativo', [
            'fecha' => $fecha,
            'documento' => $documento,
            'sucursal' => $sucursal,
            'tieneEspejo' => $tieneEspejo,
            'descargado' => $descargado,
            'ultimaDescarga' => $ultimaDescarga,
            'documentos' => $resultado['documentos'],
            'detalle' => $resultado['detalle'],
            'planificacion' => $resultado['planificacion'],
            'resumen' => $resultado['resumen'],
        ]);
    }

    public function descargar(Request $request, ErpComparativoService $servicio)
    {
        $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
        ]);

        $fecha = (string) $request->get('fecha');
        $sucursal = (int) ($request->get('sucursal') ?: config('services.erp.default_sucursal', 2));
        if ($sucursal <= 0) {
            $sucursal = 2;
        }

        $resultado = $servicio->descargar($fecha, $sucursal);

        if (! ($resultado['success'] ?? false)) {
            return redirect()
                ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
                ->with('error', $resultado['message'] ?? 'No se pudo descargar del ERP.');
        }

        return redirect()
            ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
            ->with('success', sprintf(
                'Descargado del ERP: %d documentos, %d actividades, %d filas de detalle, %d de planificacion.',
                $resultado['documentos'],
                $resultado['actividades'],
                $resultado['detalles'],
                $resultado['planificacion']
            ));
    }

    /**
     * Sincroniza UN registro: manda al ERP los puros y horas del sistema
     * (y el precio). Si la fila no existe en el ERP, la registra.
     */
    public function sincronizar(Request $request, \App\Services\ErpApiService $erp)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
            'numero' => ['required', 'string', 'max:20'],
            'actividad' => ['required', 'integer'],
            'orden' => ['required', 'string', 'max:20'],
            'empleado' => ['required', 'string', 'max:20'],
            'producto' => ['nullable', 'string', 'max:40'],
        ]);

        $sucursal = (int) ($data['sucursal'] ?? config('services.erp.default_sucursal', 2)) ?: 2;
        $numero = trim((string) $data['numero']);
        $orden = trim((string) $data['orden']);
        $empleado = trim((string) $data['empleado']);
        $producto = trim((string) ($data['producto'] ?? ''));

        $registros = \App\Models\VinetaRegistro::query()
            ->where('estado', \App\Models\VinetaRegistro::ESTADO_ACTIVO)
            ->where('sucursal', $sucursal)
            ->where('documento_numero', $numero)
            ->where('actividad_codigo', (int) $data['actividad'])
            ->where('empleado_codigo', $empleado)
            ->whereDate('fecha_registro', $data['fecha'])
            ->where(function ($q) use ($orden) {
                $q->where('orden_del_sistema', $orden)
                    ->orWhere('orden_del_sistema', ltrim($orden, '0'))
                    ->orWhereRaw('LPAD(TRIM(orden_del_sistema), 10, "0") = ?', [$orden]);
            })
            ->when($producto !== '', fn ($q) => $q->where('producto_codigo', $producto))
            ->get();

        if ($registros->isEmpty()) {
            return back()->with('error', 'No hay registros en el sistema para esa fila.');
        }

        $puros = (int) $registros->sum('cantidad_puros');
        $minutos = (int) $registros->sum(fn ($r) => (int) ($r->minutos_trabajados ?? 0));
        $horas = round($minutos / 60, 2);
        $precio = (float) ($registros->max('precio_mo') ?? 0);

        $resultado = $erp->fijarHorasVineta($registros->first(), $horas, $sucursal, $puros, $precio > 0 ? $precio : null);

        $ok = ! empty($resultado['success']);
        $mensaje = $resultado['message'] ?? ($resultado['error'] ?? 'Sin respuesta del ERP.');

        return back()->with(
            $ok ? 'success' : 'error',
            ($ok ? 'Sincronizado: ' : 'No se pudo sincronizar: ')
            ."doc $numero · act {$data['actividad']} · emp $empleado · $puros puros / ".number_format($horas, 2).' h'
            ." — $mensaje"
        );
    }

    private function fecha($valor): string
    {
        $valor = trim((string) $valor);

        if ($valor !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $valor, 'America/Tegucigalpa')->toDateString();
            } catch (\Throwable) {
                // usa hoy
            }
        }

        return Carbon::now('America/Tegucigalpa')->toDateString();
    }
}
