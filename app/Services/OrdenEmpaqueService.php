<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ordenes (pendientes) de empaque que vienen de facturacion_plasencia.
 *
 * Es la MISMA lista que /vinetas-pendientes, pero SIN filtrar: trae tambien
 * las que ya quedaron en cero para que se puedan seguir generando viñetas.
 *
 * Endpoint: GET {api_url}/empaque/ordenes  (header X-Api-Key)
 */
class OrdenEmpaqueService
{
    private const CACHE_KEY = 'ordenes_empaque:api_data:v1';

    private const CACHE_TTL_SECONDS = 60;

    /** Tope de ordenes que se traen por consulta (las de saldo van primero). */
    public const LIMITE = 5000;

    /**
     * @return Collection<int, object>
     */
    public function getOrdenes(bool $forceRefresh = false): Collection
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $rawItems = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->fetchFromApi();
        });

        return collect($rawItems)->map(function ($item) {
            $saldo = (int) ($item['saldo'] ?? 0);

            return (object) [
                'id_pendiente' => (string) ($item['id_pendiente'] ?? ''),
                'item' => trim((string) ($item['item'] ?? '')),
                'codigo_producto' => trim((string) ($item['codigo_producto'] ?? '')),
                'presentacion' => trim((string) ($item['presentacion'] ?? '')),
                'marca' => trim((string) ($item['marca'] ?? '')),
                'nombre' => trim((string) ($item['nombre'] ?? '')),
                'vitola' => trim((string) ($item['vitola'] ?? '')),
                'capa' => trim((string) ($item['capa'] ?? '')),
                'tipo_empaque' => trim((string) ($item['tipo_empaque'] ?? '')),
                'orden_del_sistema' => trim((string) ($item['orden_del_sistema'] ?? '')),
                'orden' => trim((string) ($item['orden'] ?? '')),
                'mes' => trim((string) ($item['mes'] ?? '')),
                'pendiente' => (int) ($item['pendiente'] ?? 0),
                'saldo' => $saldo,
                'paquetes' => (float) ($item['paquetes'] ?? 0),
                'unidades' => (float) ($item['unidades'] ?? 0),
                'cliente' => trim((string) ($item['cliente'] ?? '')),
                'referencia' => trim((string) ($item['referencia'] ?? '')),
                'observacion' => trim((string) ($item['observacion'] ?? '')),
                'sampler' => (bool) ($item['sampler'] ?? false),
                'sin_saldo' => (bool) ($item['sin_saldo'] ?? ($saldo <= 0)),
                'estado' => $saldo > 0 ? 'pendiente' : 'en cero',
            ];
        })->values();
    }

    /**
     * Ultimo error de la API (para mostrarlo en la pantalla).
     */
    public function ultimoError(): ?string
    {
        return Cache::get(self::CACHE_KEY.':error');
    }

    public function refrescar(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.':error');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromApi(): array
    {
        $apiUrl = trim((string) config('services.programaciones.api_url', ''));

        if ($apiUrl === '') {
            $this->recordarError('La API de facturacion no esta configurada (PROGRAMACIONES_API_URL).');

            return [];
        }

        try {
            $response = Http::timeout(20)
                ->connectTimeout(8)
                ->withHeaders([
                    'X-Api-Key' => (string) config('services.programaciones.api_key', ''),
                    'Accept' => 'application/json',
                ])
                ->get(rtrim($apiUrl, '/').'/empaque/ordenes', [
                    // Hay miles de ordenes en cero: se piden las de saldo
                    // primero y un tope para que la pantalla responda rapido.
                    'limite' => self::LIMITE,
                ]);
        } catch (\Throwable $e) {
            Log::warning('API empaque/ordenes excepcion: '.$e->getMessage());
            $this->recordarError('No se pudo conectar con la API de facturacion ('.$apiUrl.').');

            return [];
        }

        if (! $response->successful()) {
            Log::warning('API empaque/ordenes fallo', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 400),
            ]);
            $this->recordarError('La API de facturacion respondio HTTP '.$response->status().'.');

            return [];
        }

        // El servidor puede devolver BOM (\xEF\xBB\xBF) al inicio: si no se
        // quita, json_decode falla y la pantalla se queda vacia.
        $cuerpo = ltrim($response->body(), "\xEF\xBB\xBF \t\n\r\0\x0B");
        $data = json_decode($cuerpo, true);

        if (! is_array($data)) {
            $data = $response->json();
        }

        $registros = is_array($data) ? ($data['registros'] ?? null) : null;

        if (! is_array($registros)) {
            $this->recordarError('La API de facturacion devolvio una respuesta no valida.');

            return [];
        }

        $this->recordarError(null);

        return $registros;
    }

    private function recordarError(?string $mensaje): void
    {
        if ($mensaje === null) {
            Cache::forget(self::CACHE_KEY.':error');

            return;
        }

        Cache::put(self::CACHE_KEY.':error', $mensaje, self::CACHE_TTL_SECONDS);
    }
}
