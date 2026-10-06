<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VinetaPendienteService
{
    private const API_URL = 'http://192.168.2.7:8080/api/pendiente/empaque/listar';
    private const CACHE_KEY = 'vinetas_pendientes:api_data:v1';
    private const CACHE_TTL_SECONDS = 30;

    /**
     * Obtiene y formatea las viñetas pendientes desde la API externa.
     *
     * @param bool $forceRefresh
     * @return Collection
     */
    public function getPendientes(bool $forceRefresh = false): Collection
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $rawItems = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->fetchFromApi();
        });

        return collect($rawItems)->map(function ($item, $index) {
            $seqId = $index + 1;

            return (object) [
                'id' => $seqId,
                'codigo_qr' => 'li-' . $seqId,
                'id_pendiente' => $item['id_pendiente'] ?? null,
                'fecha' => null,
                'item' => trim((string) ($item['item'] ?? '')),
                'presentacion' => trim((string) ($item['presentacion'] ?? '')),
                'codigo_producto' => trim((string) ($item['codigo_productos'] ?? $item['codigo_producto'] ?? '')),
                'marca' => trim((string) ($item['marca'] ?? '')),
                'nombre' => trim((string) ($item['nombre'] ?? '')),
                'vitola' => trim((string) ($item['vitola'] ?? '')),
                'capa' => trim((string) ($item['capa'] ?? '')),
                'orden_del_sistema' => trim((string) ($item['orden_del_sitema'] ?? $item['orden_del_sistema'] ?? '')),
                'orden' => trim((string) ($item['orden'] ?? '')),
                'tipo_empaque' => trim((string) ($item['tipo_empaque'] ?? '')),
                'mes' => trim((string) ($item['mes'] ?? '')),
                'cantidad_puros' => isset($item['saldo']) ? (int) $item['saldo'] : (isset($item['pendiente']) ? (int) $item['pendiente'] : 0),
                'estado' => 'activo',
                'raw_payload' => $item,
            ];
        });
    }

    /**
     * Busca un pendiente por su número correlativo o código QR (ej: 11 o "li-11").
     */
    public function findByQrOrNumber(string|int $identifier): ?object
    {
        $id = null;
        if (is_numeric($identifier)) {
            $id = (int) $identifier;
        } elseif (preg_match('/^li-(\d+)$/i', trim($identifier), $m)) {
            $id = (int) $m[1];
        }

        if ($id === null) {
            return null;
        }

        return $this->getPendientes()->firstWhere('id', $id);
    }

    private function fetchFromApi(): array
    {
        try {
            $response = Http::timeout(6)
                ->connectTimeout(3)
                ->get(self::API_URL);

            if ($response->successful()) {
                $json = $response->json();
                $data = $json['data'] ?? $json;
                if (is_array($data)) {
                    return $data;
                }
            }

            Log::warning('Respuesta no exitosa de API pendiente empaque', [
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Error consultando API pendiente empaque: ' . $e->getMessage());
        }

        return [];
    }
}
