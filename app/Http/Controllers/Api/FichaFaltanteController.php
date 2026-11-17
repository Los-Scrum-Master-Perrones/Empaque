<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FichaFaltanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lista de combinaciones (producto + actividad + tipo de empaque) que se
 * registraron y NO tienen ficha de empaque en el ERP.
 *
 * La usa la campanita de la app de Android: barre todos los dias desde
 * el 1 de octubre hasta hoy (sin fecha) o solo el dia que se mande.
 *
 * Trae tambien las actividades que salen en la lista para poder IGNORAR
 * las que no interesan (ej. Rezagado); lo ignorado ya no se muestra.
 *
 * Solo lectura, excepto el toggle de actividades ignoradas.
 */
class FichaFaltanteController extends Controller
{
    public function __construct(
        private readonly FichaFaltanteService $service
    ) {}

    /**
     * GET /api/ficha-faltante?fecha=YYYY-MM-DD&limit=200
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $fecha = $data['fecha'] ?? null;
        $limite = (int) ($data['limit'] ?? 200);

        $faltantes = $this->service->listar($fecha);
        $actividades = $this->service->actividadesDisponibles($fecha);

        return response()->json([
            'success' => true,
            'desde' => '2026-10-01',
            'fecha' => $fecha ?? '',
            'total' => $faltantes->count(),
            'error' => $this->service->ultimoError($fecha),
            'items' => $faltantes->take($limite)->map(fn ($f) => [
                'producto' => (string) $f->producto,
                'producto_nombre' => (string) ($f->producto_nombre ?? ''),
                'sku' => (string) $f->sku,
                'capa' => (string) ($f->capa ?? ''),
                'actividad' => (int) $f->actividad,
                'actividad_nombre' => (string) $f->actividad_nombre,
                'tipo_empaque' => (int) $f->tipo_empaque,
                'tipo_empaque_nombre' => (string) $f->tipo_empaque_nombre,
                'puros' => (int) $f->puros,
                'ultima' => (string) ($f->ultima ?? ''),
            ])->values(),
            // Actividades que salen en la lista (incluye las ignoradas).
            'actividades' => $actividades->map(fn ($a) => [
                'codigo' => (int) $a->codigo,
                'nombre' => (string) $a->nombre,
                'registros' => (int) $a->registros,
                'ignorada' => (bool) $a->ignorada,
            ])->values(),
            'ignoradas' => $actividades
                ->where('ignorada', true)
                ->pluck('codigo')
                ->map(fn ($c) => (int) $c)
                ->values(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * POST /api/ficha-faltante/actividades/ignorar
     * { "codigo": 2, "ignorar": true, "nombre": "Rezagado" }
     *
     * ignorar=false la vuelve a mostrar.
     */
    public function ignorarActividad(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'integer', 'min:1'],
            'ignorar' => ['required', 'boolean'],
            'nombre' => ['nullable', 'string', 'max:190'],
        ]);

        $this->service->ignorar(
            (int) $data['codigo'],
            (bool) $data['ignorar'],
            $data['nombre'] ?? null,
            optional($request->user())->id
        );

        return response()->json([
            'success' => true,
            'codigo' => (int) $data['codigo'],
            'ignorar' => (bool) $data['ignorar'],
            'ignoradas' => FichaFaltanteService::codigosIgnorados(),
        ])->header('Cache-Control', 'no-store');
    }
}
