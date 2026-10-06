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
