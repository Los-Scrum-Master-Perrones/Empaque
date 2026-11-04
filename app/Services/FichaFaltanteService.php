<?php

namespace App\Services;

use App\Models\FichaActividadIgnorada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Combinaciones (producto + actividad + tipo de empaque) que se registraron
 * en el ERP y NO tienen ficha de empaque (materiales).
 *
 * Lo calcula facturacion_plasencia (alla tiene acceso al ERP); aqui solo se
 * consulta por HTTP y se cachea para no pegarle en cada pantalla.
 *
 * Endpoint: GET {api_url}/empaque/ficha-faltante?fecha=Y-m-d&sucursal=2
 */
class FichaFaltanteService
{
    private const CACHE_TTL_SECONDS = 600;

    /**
     * Listado SIN las actividades ignoradas (lo que ve la campanita y la app).
     *
     * @return Collection<int, object>
     */
    public function listar(?string $fecha = null, bool $forceRefresh = false): Collection
    {
        $items = $this->listarSinFiltrar($fecha, $forceRefresh);
        $ignoradas = self::codigosIgnorados();

        if ($ignoradas === []) {
            return $items;
        }

        return $items
            ->reject(fn ($item) => in_array((int) $item->actividad, $ignoradas, true))
            ->values();
    }

    /**
     * Actividades distintas que salen en el listado (incluye las ignoradas)
     * para el boton de "ignorar actividades".
     *
     * @return Collection<int, object>
     */
    public function actividadesDisponibles(?string $fecha = null, bool $forceRefresh = false): Collection
    {
        $items = $this->listarSinFiltrar($fecha, $forceRefresh);
        $ignoradas = self::codigosIgnorados();

        return $items
            ->groupBy('actividad')
            ->map(function ($grupo, $codigo) use ($ignoradas) {
                $primero = $grupo->first();

                return (object) [
                    'codigo' => (int) $codigo,
                    'nombre' => trim((string) ($primero->actividad_nombre ?? '')),
                    'registros' => $grupo->count(),
                    'puros' => (float) $grupo->sum('puros'),
                    'ignorada' => in_array((int) $codigo, $ignoradas, true),
                ];
            })
            ->sortBy('codigo')
            ->values();
    }

    /**
     * Codigos de actividad ignorados en la campanita.
     *
     * @return array<int, int>
     */
    public static function codigosIgnorados(): array
    {
        try {
            return FichaActividadIgnorada::query()
                ->pluck('codigo_actividad')
                ->map(fn ($codigo) => (int) $codigo)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Ignora (o vuelve a mostrar) una actividad en la campanita.
     */
    public function ignorar(int $codigo, bool $ignorar, ?string $nombre = null, ?int $userId = null): void
    {
        if ($codigo <= 0) {
            return;
        }

        if ($ignorar) {
            FichaActividadIgnorada::query()->updateOrCreate(
                ['codigo_actividad' => $codigo],
                ['nombre' => $nombre !== null ? mb_substr(trim($nombre), 0, 190) : null, 'created_by' => $userId]
            );

            return;
        }

        FichaActividadIgnorada::query()->where('codigo_actividad', $codigo)->delete();
    }

    /**
     * Listado completo (sin aplicar las actividades ignoradas).
     *
     * @return Collection<int, object>
     */
    private function listarSinFiltrar(?string $fecha = null, bool $forceRefresh = false): Collection
    {
        // Sin fecha = acumulado desde el 1 de octubre hasta hoy.
        $key = 'ficha_faltante:'.($fecha ?: 'desde-2026-10-01');

        if ($forceRefresh) {
            Cache::forget($key);
            Cache::forget($key.':error');
        }

        $raw = Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($fecha) {
            return $this->fetch($fecha);
        });

        return collect($raw)->map(function ($item) {
            return (object) [
                'producto' => trim((string) ($item['producto'] ?? '')),
                'sku' => trim((string) ($item['sku'] ?? '')),
                'actividad' => (int) ($item['actividad'] ?? 0),
                'actividad_nombre' => trim((string) ($item['actividad_nombre'] ?? '')),
                'tipo_empaque' => (int) ($item['tipo_empaque'] ?? 0),
                'tipo_empaque_nombre' => trim((string) ($item['tipo_empaque_nombre'] ?? '')),
                'filas' => (int) ($item['filas'] ?? 0),
                'puros' => (float) ($item['puros'] ?? 0),
                'nombre_empleado' => trim((string) ($item['nombre_empleado'] ?? '')),
                'ultima' => trim((string) ($item['ultima'] ?? '')),
            ];
        })->values();
    }

    /**
     * Cuantas combinaciones faltan (para la campanita).
     */
    public function contar(?string $fecha = null): int
    {
        try {
            return $this->listar($fecha)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Ficha de UN producto para varias actividades (aviso al generar/escanear).
     *
     * @param  array<int, int>  $actividades
     * @return array<int, array{actividad:int, materiales:int, tiene_ficha:bool}>
     */
    public function estadoProducto(string $producto, ?string $tipoEmpaque, array $actividades): array
    {
        $producto = trim($producto);

        if ($producto === '' || $actividades === []) {
            return [];
        }

        $clave = 'ficha_estado:'.md5($producto.'|'.(string) $tipoEmpaque.'|'.implode(',', $actividades));

        // Cache corto: si capturan la ficha, el aviso desaparece rapido.
        $raw = Cache::remember($clave, 60, function () use ($producto, $tipoEmpaque, $actividades) {
            $apiUrl = trim((string) config('services.programaciones.api_url', ''));

            if ($apiUrl === '') {
                return [];
            }

            try {
                $response = Http::timeout(15)
                    ->connectTimeout(5)
                    ->withHeaders([
                        'X-Api-Key' => (string) config('services.programaciones.api_key', ''),
                        'Accept' => 'application/json',
                    ])
                    ->get(rtrim($apiUrl, '/').'/empaque/ficha-estado', [
                        'producto' => $producto,
                        'tipo_empaque' => (string) $tipoEmpaque,
                        'sucursal' => (int) config('services.erp.default_sucursal', 2),
                        'actividades' => implode(',', $actividades),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('API ficha-estado excepcion: '.$e->getMessage());

                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            $cuerpo = ltrim((string) $response->body(), "\xEF\xBB\xBF \t\n\r\0\x0B");
            $data = json_decode($cuerpo, true);

            if (! is_array($data)) {
                $data = $response->json();
            }

            $lista = is_array($data) ? ($data['actividades'] ?? null) : null;

            return is_array($lista) ? $lista : [];
        });

        return array_map(fn ($a) => [
            'actividad' => (int) ($a['actividad'] ?? 0),
            'materiales' => (int) ($a['materiales'] ?? 0),
            'tiene_ficha' => (bool) ($a['tiene_ficha'] ?? false),
            // false = la actividad no pide ficha (rezagado, limpieza...)
            'requiere_ficha' => (bool) ($a['requiere_ficha'] ?? true),
            // ok = se puede registrar
            'ok' => (bool) ($a['ok'] ?? ($a['tiene_ficha'] ?? false)),
        ], (array) $raw);
    }

    /**
     * ¿Se puede registrar esa actividad para ese producto/empaque?
     * Devuelve true/false, o null si no se pudo consultar (no bloquear).
     */
    public function permiteActividad(string $producto, ?string $tipoEmpaque, int $actividad): ?bool
    {
        if (trim($producto) === '' || $actividad <= 0) {
            return null;
        }

        $estado = $this->estadoProducto($producto, $tipoEmpaque, [$actividad]);

        if ($estado === []) {
            return null;   // no se pudo consultar: no se bloquea
        }

        return (bool) ($estado[0]['ok'] ?? true);
    }

    public function ultimoError(?string $fecha = null): ?string
    {
        return Cache::get('ficha_faltante:'.($fecha ?: 'desde-2026-10-01').':error');
    }

    public function refrescar(?string $fecha = null): void
    {
        $this->listar($fecha, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(?string $fecha): array
    {
        $apiUrl = trim((string) config('services.programaciones.api_url', ''));

        if ($apiUrl === '') {
            $this->recordarError($fecha, 'La API de facturacion no esta configurada.');

            return [];
        }

        try {
            $response = Http::timeout(25)
                ->connectTimeout(8)
                ->withHeaders([
                    'X-Api-Key' => (string) config('services.programaciones.api_key', ''),
                    'Accept' => 'application/json',
                ])
                ->get(rtrim($apiUrl, '/').'/empaque/ficha-faltante', array_filter([
                    'fecha' => $fecha,
                    'sucursal' => (int) config('services.erp.default_sucursal', 2),
                ], fn ($v) => $v !== null && $v !== ''));
        } catch (\Throwable $e) {
            Log::warning('API ficha-faltante excepcion: '.$e->getMessage());
            $this->recordarError($fecha, 'No se pudo conectar con la API de facturacion.');

            return [];
        }

        if (! $response->successful()) {
            Log::warning('API ficha-faltante fallo', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);
            $this->recordarError($fecha, 'La API respondio HTTP '.$response->status().'.');

            return [];
        }

        // El servidor puede mandar BOM: se quita antes de decodificar.
        $cuerpo = ltrim((string) $response->body(), "\xEF\xBB\xBF \t\n\r\0\x0B");
        $data = json_decode($cuerpo, true);

        if (! is_array($data)) {
            $data = $response->json();
        }

        $faltantes = is_array($data) ? ($data['faltantes'] ?? null) : null;

        if (! is_array($faltantes)) {
            $this->recordarError($fecha, (string) (is_array($data) ? ($data['message'] ?? '') : 'Respuesta no valida de la API.'));

            return [];
        }

        if (empty($data['success'])) {
            $this->recordarError($fecha, (string) ($data['message'] ?? 'La API no devolvio la ficha.'));

            return [];
        }

        $this->recordarError($fecha, null);

        return $faltantes;
    }

    private function recordarError(?string $fecha, ?string $mensaje): void
    {
        $key = 'ficha_faltante:'.($fecha ?: 'desde-2026-10-01').':error';

        if ($mensaje === null || $mensaje === '') {
            Cache::forget($key);

            return;
        }

        Cache::put($key, $mensaje, self::CACHE_TTL_SECONDS);
    }
}
