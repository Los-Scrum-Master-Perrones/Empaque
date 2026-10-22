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
            }
        }

        return view('costos-empaque.comparativo', [
            'fecha' => $fecha,
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
