<?php

namespace App\Services;

use App\Models\DocumentoEmpaque;
use App\Models\VinetaRegistro;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $defaultSucursal;
    protected int $defaultFormaPago;
    protected int $defaultBodegaDetalle;
    protected bool $dryRun;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.erp.base_url', 'http://192.168.2.7:8080/api'), '/');
        $this->apiKey = (string) config('services.erp.api_key', 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d');
        $this->defaultSucursal = (int) config('services.erp.default_sucursal', 2);
        $this->defaultFormaPago = (int) config('services.erp.default_forma_pago', 2);
        $this->defaultBodegaDetalle = (int) config('services.erp.default_bodega_detalle', 218);
        $this->dryRun = (bool) config('services.erp.dry_run', false);
    }

    public function esDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Fetch packaging documents for a given date and branch from the ERP API
     * and synchronize them into the local database.
     */
    public function obtenerDocumentosEmpaques(string $fecha, int $sucursal = 2): array
    {
        $url = "{$this->baseUrl}/documentos-empaques";

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($url, [
                    'fecha' => $fecha,
                    'sucursal' => $sucursal,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $documentosRaw = $data['documentos'] ?? [];

                $documentosSincronizados = [];
                foreach ($documentosRaw as $doc) {
                    $numero = trim((string) ($doc['numero'] ?? ''));
                    if ($numero === '') {
                        continue;
                    }

                    $model = DocumentoEmpaque::updateOrCreate(
                        [
                            'numero' => $numero,
                            'fecha' => $fecha,
                            'sucursal' => $sucursal,
                        ],
                        [
                            'descripcion' => $doc['descripcion'] ?? null,
                            'observaciones' => $doc['observaciones'] ?? null,
                            'total' => $doc['total'] ?? 0,
                            'forma_pago' => $this->defaultFormaPago,
                            'bodega_detalle' => $this->defaultBodegaDetalle,
                            'estado' => 'activo',
                        ]
                    );

                    $documentosSincronizados[] = [
                        'id' => $model->id,
                        'numero' => $model->numero,
                        'descripcion' => $model->descripcion,
                        'observaciones' => $model->observaciones,
                        'total' => (int) $model->total,
                    ];
                }

                return [
                    'success' => true,
                    'fecha' => $fecha,
                    'sucursal' => $sucursal,
                    'total' => count($documentosSincronizados),
                    'documentos' => $documentosSincronizados,
                ];
            }

            Log::warning('ERP documentos-empaques failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ERP documentos-empaques exception: ' . $e->getMessage());
        }

        // Fallback to locally cached documents
        $locales = DocumentoEmpaque::where('fecha', $fecha)
            ->where('sucursal', $sucursal)
            ->orderBy('numero')
            ->get();

        return [
            'success' => true,
            'fecha' => $fecha,
            'sucursal' => $sucursal,
            'total' => $locales->count(),
            'documentos' => $locales->map(fn ($doc) => [
                'id' => $doc->id,
                'numero' => $doc->numero,
                'descripcion' => $doc->descripcion,
                'observaciones' => $doc->observaciones,
                'total' => (int) $doc->total,
            ])->values()->all(),
        ];
    }

    /**
     * Submit a viñeta registration directly to ERP endpoint.
     */
    public function registrarVinetaErp(array $payload): array
    {
        $url = "{$this->baseUrl}/registrar-vineta-erp";

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $data = $response->json();

            if (!is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Respuesta no válida del ERP: ' . $response->body(),
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('ERP registrar-vineta-erp exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al comunicar con ERP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format and send a single VinetaRegistro to ERP.
     */
    public function enviarRegistroVineta(VinetaRegistro $registro, ?string $documentoNumero = null, ?int $sucursal = null): array
    {
        $doc = $documentoNumero ?? $registro->documento_numero;
        $suc = $sucursal ?? $registro->sucursal ?? $this->defaultSucursal;

        if (!$doc) {
            return [
                'success' => false,
                'message' => 'No se especificó número de documento para el registro en ERP.',
            ];
        }

        $minutosPorVineta = $registro->minutos_trabajados === null
            ? 0.0
            : round((int) $registro->minutos_trabajados / 60, 2);

        // El ERP no debe guardar cantidades muy pequeñas (ej. 0.03 h): si hay
        // tiempo trabajado pero es menor a un cuarto de hora, se manda 0.25 h.
        // (El minimo es solo para el ERP; la jornada queda normal en el sistema.)
        if ($minutosPorVineta > 0 && $minutosPorVineta < 0.25) {
            $minutosPorVineta = 0.25;
        }

        $payload = [
            'dry_run' => $this->dryRun,
            'fecha' => $registro->fecha_registro?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'sucursal' => (int) $suc,
            'documento' => (string) $doc,
            'forma_pago' => $this->defaultFormaPago,
            'bodega_detalle' => $this->defaultBodegaDetalle,
            'vineta' => [
                'id_vineta' => (string) ($registro->vineta_api_id ?? $registro->codigo_vineta ?? $registro->id),
                'item' => (string) ($registro->productoItemReporte() !== 'N/A' ? $registro->productoItemReporte() : ($registro->producto_item ?? '')),
                'codigo_producto' => (string) ($registro->productoCodigoReporte() !== 'N/A' ? $registro->productoCodigoReporte() : ($registro->producto_codigo ?? '')),
                'orden_del_sistema' => (string) ($registro->ordenDelSistemaReporte() !== 'N/A' ? $registro->ordenDelSistemaReporte() : ($registro->orden_del_sistema ?? '')),
                'orden_del_cliente' => (string) ($registro->ordenReporte() !== 'N/A' ? $registro->ordenReporte() : ($registro->orden ?? '')),
                'codigo_actividad' => (int) $registro->actividad_codigo,
                'actividad' => (string) $registro->actividad_nombre,
                'empleado_codigo' => (string) $registro->empleado_codigo,
                'empleado_nombre' => (string) $registro->empleado_nombre,
                'cantidad_puros' => (int) $registro->cantidad_puros,
                'minutos_por_vineta' => (float) $minutosPorVineta,
            ],
        ];

        $resultado = $this->registrarVinetaErp($payload);

        $exito = !empty($resultado['success']) || !empty($resultado['ya_registrada']);

        // En modo prueba (dry_run) el ERP valida pero no inserta: el registro local
        // NO se marca como enviado, para poder reenviarlo cuando se apague el modo.
        $enviadoReal = $exito && ! $this->dryRun;

        $registro->update([
            'documento_numero' => $doc,
            'sucursal' => $suc,
            'erp_enviado' => $enviadoReal,
            'erp_enviado_en' => $enviadoReal ? now() : null,
            'erp_respuesta' => $resultado,
        ]);

        return $resultado;
    }

    /**
     * Fija las horas EXACTAS de una fila ya registrada en el ERP.
     * Lo usa el boton "Aplicar ajuste de horas" despues de repartir la jornada.
     */
    public function fijarHorasVineta(VinetaRegistro $registro, float $horas, ?int $sucursal = null, ?int $puros = null): array
    {
        $doc = trim((string) $registro->documento_numero);

        if ($doc === '') {
            return ['success' => false, 'message' => 'El registro no tiene documento.'];
        }

        $payload = [
            'dry_run' => $this->dryRun,
            'sucursal' => (int) ($sucursal ?? $registro->sucursal ?? $this->defaultSucursal),
            'documento' => $doc,
            'codigo_actividad' => (int) $registro->actividad_codigo,
            'numero_de_orden' => (string) $registro->orden_del_sistema,
            'empleado_codigo' => (string) $registro->empleado_codigo,
            'horas' => round($horas, 2),
            'motivo' => 'Aplicar ajuste de horas desde app_empaque',
        ];

        if ($puros !== null) {
            $payload['cantidad_puros'] = (int) $puros;
        }

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/fijar-horas-vineta", $payload);

            $data = $response->json();

            if (! is_array($data)) {
                return ['success' => false, 'message' => 'Respuesta no válida del ERP: '.$response->body()];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('ERP fijar-horas-vineta exception: '.$e->getMessage());

            return ['success' => false, 'message' => 'Error al comunicar con ERP: '.$e->getMessage()];
        }
    }

    public function ajustarHorasVineta(VinetaRegistro $registro, float $deltaHoras, ?int $sucursal = null): array
    {
        $doc = trim((string) $registro->documento_numero);

        if ($doc === '') {
            return ['success' => false, 'message' => 'El registro no tiene documento: no hay nada que ajustar en el ERP.'];
        }

        if (abs($deltaHoras) < 0.005) {
            return ['success' => true, 'cambiado' => false, 'message' => 'Sin diferencia de horas.'];
        }

        $payload = [
            'dry_run' => $this->dryRun,
            'sucursal' => (int) ($sucursal ?? $registro->sucursal ?? $this->defaultSucursal),
            'documento' => $doc,
            'codigo_actividad' => (int) $registro->actividad_codigo,
            'numero_de_orden' => (string) $registro->orden_del_sistema,
            'empleado_codigo' => (string) $registro->empleado_codigo,
            'delta_horas' => round($deltaHoras, 2),
            'motivo' => 'Edicion de hora desde app_empaque',
        ];

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/ajustar-horas-vineta", $payload);

            $data = $response->json();

            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Respuesta no válida del ERP: '.$response->body(),
                ];
            }

            $previo = is_array($registro->erp_respuesta) ? $registro->erp_respuesta : [];
            $previo['ajustes_horas'] = array_slice(
                array_merge($previo['ajustes_horas'] ?? [], [[
                    'delta_horas' => $payload['delta_horas'],
                    'en' => now()->toIso8601String(),
                    'resultado' => $data,
                ]]),
                -5
            );
            $registro->update(['erp_respuesta' => $previo]);

            return $data;
        } catch (\Throwable $e) {
            Log::error('ERP ajustar-horas-vineta exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al comunicar con ERP: '.$e->getMessage(),
            ];
        }
    }
}
