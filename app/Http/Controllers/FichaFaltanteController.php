<?php

namespace App\Http\Controllers;

use App\Services\FichaFaltanteService;
use Illuminate\Http\Request;

/**
 * Avisos de FICHA DE EMPAQUE: combinaciones (codigo de producto + actividad +
 * tipo de empaque) que se registraron en el ERP y no tienen materiales
 * capturados en la ficha.
 *
 * Solo lectura: lee del ERP a traves de la API de facturacion.
 */
class FichaFaltanteController extends Controller
{
    public function __construct(
        private readonly FichaFaltanteService $service
    ) {}

    public function index(Request $request)
    {
        // Sin fecha = desde el 1 de octubre hasta hoy (lo acumulado).
        $fecha = trim((string) $request->get('fecha', ''));

        if ($fecha !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = '';
        }

        if ($request->boolean('refresh')) {
            $this->service->refrescar($fecha !== '' ? $fecha : null);
        }

        $faltantes = $this->service->listar($fecha !== '' ? $fecha : null);
        $error = $this->service->ultimoError($fecha !== '' ? $fecha : null);

        return view('ficha-faltante.index', [
            'faltantes' => $faltantes,
            'actividades' => $this->service->actividadesDisponibles($fecha !== '' ? $fecha : null),
            'fecha' => $fecha,
            'error' => $error,
        ]);
    }

    /**
     * Ignora (o vuelve a mostrar) una actividad en la campanita.
     * POST /ficha-faltante/ignorar
     */
    public function ignorar(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'integer', 'min:1'],
            'ignorar' => ['required', 'boolean'],
            'nombre' => ['nullable', 'string', 'max:190'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $this->service->ignorar(
            (int) $data['codigo'],
            (bool) $data['ignorar'],
            $data['nombre'] ?? null,
            optional($request->user())->id
        );

        $fecha = (string) ($data['fecha'] ?? '');

        return redirect()
            ->route('ficha-faltante.index', $fecha !== '' ? ['fecha' => $fecha] : [])
            ->with('status', (bool) $data['ignorar']
                ? 'Actividad ignorada: ya no aparece en la lista.'
                : 'Actividad restaurada en la lista.');
    }

    /**
     * Ficha de un producto para varias actividades (lo consulta el modal de
     * "Generar viñetas" y la app al escanear).
     * GET /ficha-faltante/estado?producto=P-02000&tipo_empaque=CAJAS 1/10&actividades=133,157
     */
    public function estado(Request $request)
    {
        $producto = trim((string) $request->get('producto', ''));
        $tipoEmpaque = trim((string) $request->get('tipo_empaque', ''));
        $texto = trim((string) $request->get('actividades', ''));

        $actividades = array_values(array_filter(array_map(
            fn ($v) => (int) trim($v),
            explode(',', $texto)
        )));

        $lista = $this->service->estadoProducto($producto, $tipoEmpaque, $actividades);

        return response()->json([
            'success' => true,
            'producto' => $producto,
            'actividades' => $lista,
            'faltan' => count(array_filter($lista, fn ($a) => ! $a['tiene_ficha'])),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Cuantas combinaciones faltan (para la campanita del topbar).
     * GET /ficha-faltante/count
     */
    public function contar(Request $request)
    {
        $fecha = trim((string) $request->get('fecha', ''));

        if ($fecha !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = '';
        }

        $faltantes = $this->service->listar($fecha !== '' ? $fecha : null);

        return response()->json([
            'total' => $faltantes->count(),
            'fecha' => $fecha !== '' ? $fecha : 'desde 01/10',
            'error' => $this->service->ultimoError($fecha),
            // Primeras 10 para el desplegable de la campanita.
            'items' => $faltantes->take(10)->map(fn ($f) => [
                'producto' => $f->producto,
                'actividad' => $f->actividad.' '.$f->actividad_nombre,
                'empaque' => $f->tipo_empaque.' '.$f->tipo_empaque_nombre,
                'puros' => (int) $f->puros,
                'ultima' => (string) ($f->ultima ?? ''),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
