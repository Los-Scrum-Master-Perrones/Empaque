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
 * Solo lectura.
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

        return response()->json([
            'success' => true,
            'desde' => '2026-10-01',
            'fecha' => $fecha ?? '',
            'total' => $faltantes->count(),
            'error' => $this->service->ultimoError($fecha),
            'items' => $faltantes->take($limite)->map(fn ($f) => [
                'producto' => (string) $f->producto,
                'sku' => (string) $f->sku,
                'actividad' => (int) $f->actividad,
                'actividad_nombre' => (string) $f->actividad_nombre,
                'tipo_empaque' => (int) $f->tipo_empaque,
                'tipo_empaque_nombre' => (string) $f->tipo_empaque_nombre,
                'puros' => (int) $f->puros,
                'ultima' => (string) ($f->ultima ?? ''),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
