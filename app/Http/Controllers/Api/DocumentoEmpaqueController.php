<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentoEmpaque;
use App\Models\VinetaRegistro;
use App\Services\ErpApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentoEmpaqueController extends Controller
{
    public function __construct(
        protected ErpApiService $erpService
    ) {}

    /**
     * Get list of packaging documents for a given date and branch.
     * GET /api/documentos-empaques?fecha=YYYY-MM-DD&sucursal=2
     */
    public function index(Request $request): JsonResponse
    {
        $fecha = $request->query('fecha', Carbon::now('America/Tegucigalpa')->toDateString());
        $sucursal = (int) $request->query('sucursal', config('services.erp.default_sucursal', 2));

        $resultado = $this->erpService->obtenerDocumentosEmpaques($fecha, $sucursal);

        return response()->json($resultado);
    }

    /**
     * Aplica las horas y puros al ERP para TODAS las viñetas de un documento.
     *
     * POST /api/documentos-empaques/{documento}/aplicar-horas-erp
     * Body: { fecha?, sucursal?, dry_run? }
     */
    public function aplicarHorasErp(Request $request, string $documento): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $documento = trim($documento);
        if ($documento === '') {
            return response()->json(['message' => 'Documento inválido.'], 422);
        }

        $registros = VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where('documento_numero', $documento)
            ->when(! empty($data['fecha']), fn ($q) => $q->whereDate('fecha_registro', $data['fecha']))
            ->when(! empty($data['sucursal']), fn ($q) => $q->where('sucursal', (int) $data['sucursal']))
            ->orderBy('id')
            ->get();

        if ($registros->isEmpty()) {
            return response()->json([
                'message' => 'El documento no tiene viñetas enviadas al ERP para aplicar.',
                'total' => 0,
                'registros' => 0,
                'aplicados' => 0,
                'ya_iguales' => 0,
                'rechazados' => 0,
                'errores' => 0,
                'detalle' => [],
            ], 200);
        }

        $grupos = $registros->groupBy(fn (VinetaRegistro $r) => implode('|', [
            trim((string) $r->documento_numero),
            (int) $r->actividad_codigo,
            trim((string) $r->orden_del_sistema),
            trim((string) $r->empleado_codigo),
            trim((string) $r->producto_item),
            trim((string) $r->producto_codigo),
        ]));

        $aplicados = 0;
        $yaIguales = 0;
        $rechazados = 0;
        $errores = 0;
        $detalle = [];

        foreach ($grupos as $items) {
            $primero = $items->first();
            $minutos = (int) $items->sum(fn (VinetaRegistro $r) => (int) ($r->minutos_trabajados ?? 0));
            $puros = (int) $items->sum('cantidad_puros');
            $horas = round($minutos / 60, 2);

            $resultado = $this->erpService->fijarHorasVineta($primero, $horas, $data['sucursal'] ?? null, $puros);

            $estado = 'error';
            if (! empty($resultado['success'])) {
                if (! empty($resultado['insertado'])) {
                    $estado = 'creado';
                    $aplicados++;
                } elseif (! empty($resultado['cambiado'])) {
                    $estado = 'aplicado';
                    $aplicados++;
                } else {
                    $estado = 'ya_igual';
                    $yaIguales++;
                }
            } elseif (! empty($resultado['error'])) {
                $estado = in_array($resultado['error'], ['documento_aplicado', 'documento_anulado', 'fila_ambigua'], true)
                    ? 'rechazado'
                    : 'error';
                $estado === 'rechazado' ? $rechazados++ : $errores++;
            } else {
                $errores++;
            }

            $detalle[] = [
                'actividad' => $primero->actividad_nombre,
                'orden' => $primero->orden_del_sistema,
                'empleado' => $primero->empleado_codigo,
                'registros' => $items->count(),
                'horas' => $horas,
                'puros' => $puros,
                'estado' => $estado,
                'mensaje' => $resultado['message'] ?? ($resultado['error'] ?? null),
            ];
        }

        return response()->json([
            'message' => "Documento $documento: $aplicados actualizados, $yaIguales ya estaban igual, $rechazados rechazados, $errores con error.",
            'documento' => $documento,
            'total' => $grupos->count(),
            'registros' => $registros->count(),
            'aplicados' => $aplicados,
            'ya_iguales' => $yaIguales,
            'rechazados' => $rechazados,
            'errores' => $errores,
            'detalle' => $detalle,
        ], 200);
    }

    /**
     * Register a viñeta directly into ERP.
     * POST /api/registrar-vineta-erp
     */
    public function registrarVinetaErp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fecha' => ['required'],
            'sucursal' => ['required'],
            'documento' => ['required'],
            'forma_pago' => ['required'],
            'bodega_detalle' => ['required'],
            'vineta' => ['required', 'array'],
            'vineta.id_vineta' => ['required'],
            'vineta.item' => ['required'],
            'vineta.codigo_producto' => ['required'],
            'vineta.orden_del_sistema' => ['required'],
            'vineta.orden_del_cliente' => ['required'],
            'vineta.codigo_actividad' => ['required'],
            'vineta.actividad' => ['required'],
            'vineta.empleado_codigo' => ['required'],
            'vineta.empleado_nombre' => ['required'],
            'vineta.cantidad_puros' => ['required'],
            'vineta.minutos_por_vineta' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación al enviar al ERP.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->all();
        $resultado = $this->erpService->registrarVinetaErp($payload);

        // Update local VinetaRegistro if it exists
        $idVineta = $payload['vineta']['id_vineta'] ?? null;
        if ($idVineta) {
            $registro = VinetaRegistro::where('vineta_api_id', $idVineta)
                ->orWhere('codigo_vineta', (string) $idVineta)
                ->orWhere('id', $idVineta)
                ->latest('id')
                ->first();

            if ($registro) {
                $exito = !empty($resultado['success']) || !empty($resultado['ya_registrada']);
                $registro->update([
                    'documento_numero' => $payload['documento'] ?? $registro->documento_numero,
                    'sucursal' => (int) ($payload['sucursal'] ?? $registro->sucursal ?? 2),
                    'erp_enviado' => $exito,
                    'erp_enviado_en' => now(),
                    'erp_respuesta' => $resultado,
                ]);
            }
        }

        $status = (!empty($resultado['success']) || !empty($resultado['ya_registrada'])) ? 200 : 400;
        return response()->json($resultado, $status);
    }
}
