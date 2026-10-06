<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use App\Models\VinetaRegistro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VinetaController extends Controller
{
    private string $catalogoUrl = 'http://192.168.2.7:8080/api/clase_producto/empaque';

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr' => ['required', 'string', 'max:1000'],
        ]);

        $candidates = $this->extractCandidates($data['qr']);
        $scannedAt = now('America/Tegucigalpa');

        foreach ($candidates as $candidate) {
            $vineta = $this->findVineta($candidate);

            if ($vineta) {
                return response()->json([
                    'message' => 'Viñeta encontrada.',
                    'matched_by' => $candidate,
                    'vineta' => $this->vinetaPayload($vineta, $scannedAt),
                ]);
            }
        }

        return response()->json([
            'message' => 'No se encontró una viñeta con el QR escaneado.',
            'qr' => $data['qr'],
        ], 404);
    }

    public function actividades(Vineta $vineta): JsonResponse
    {
        $producto = $this->findProducto($vineta);

        if ($producto && $producto->actividades->isNotEmpty()) {
            return response()->json([
                'message' => 'Actividades encontradas.',
                'source' => 'local',
                'product' => $this->productoPayload($producto),
                'activities' => $this->actividadesFromProducto($producto, $vineta->tipo_empaque),
            ]);
        }

        $productoPorCaracteristicas = $this->findProductoPorCaracteristicas($vineta, $producto?->id);

        if ($productoPorCaracteristicas) {
            return response()->json([
                'message' => 'Actividades encontradas por capa, vitola y empaque.',
                'source' => 'local_attributes',
                'product' => $this->vinetaProductoPayload($vineta),
                'activities' => $this->actividadesFromProducto($productoPorCaracteristicas, $vineta->tipo_empaque),
            ]);
        }

        if ($producto) {
            return response()->json([
                'message' => 'No se encontraron actividades para el producto de esta viñeta.',
                'source' => 'local',
                'product' => $this->productoPayload($producto),
                'activities' => [],
            ]);
        }

        $externalItems = $this->externalCatalogItems();
        $external = $this->findExternalProduct($vineta, $externalItems);

        if ($external && ! empty($external['actividades'])) {
            return response()->json([
                'message' => 'Actividades encontradas desde la API externa.',
                'source' => 'external',
                'product' => $this->externalProductoPayload($external),
                'activities' => $this->actividadesFromExternalProduct($external, $vineta->tipo_empaque),
            ]);
        }

        $externalPorCaracteristicas = $this->findExternalProductPorCaracteristicas($vineta, $externalItems);

        if ($externalPorCaracteristicas) {
            return response()->json([
                'message' => 'Actividades encontradas desde la API externa por capa, vitola y empaque.',
                'source' => 'external_attributes',
                'product' => $this->vinetaProductoPayload($vineta),
                'activities' => $this->actividadesFromExternalProduct($externalPorCaracteristicas, $vineta->tipo_empaque),
            ]);
        }

        return response()->json([
            'message' => 'No se encontraron actividades para el producto de esta viñeta.',
            'source' => 'none',
            'product' => $this->vinetaProductoPayload($vineta),
            'activities' => [],
        ]);
    }

    private function extractCandidates(string $qr): array
    {
        $qr = trim($qr);
        $candidates = [$qr];

        $decodedJson = json_decode($qr, true);

        if (is_array($decodedJson)) {
            foreach (['api_id', 'id', 'vineta_id', 'codigo_producto', 'item', 'orden', 'orden_del_sistema', 'id_pendiente_empaque'] as $key) {
                $value = Arr::get($decodedJson, $key);

                if (is_scalar($value)) {
                    $candidates[] = (string) $value;
                }
            }
        }

        $urlParts = parse_url($qr);

        if (is_array($urlParts)) {
            if (! empty($urlParts['query'])) {
                parse_str($urlParts['query'], $query);

                foreach (['api_id', 'id', 'vineta_id', 'codigo_producto', 'item', 'orden', 'orden_del_sistema', 'id_pendiente_empaque'] as $key) {
                    if (isset($query[$key]) && is_scalar($query[$key])) {
                        $candidates[] = (string) $query[$key];
                    }
                }
            }

            if (! empty($urlParts['path'])) {
                $candidates[] = basename($urlParts['path']);
            }
        }

        return collect($candidates)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isVinetaPorOrden(Vineta $vineta): bool
    {
        $idPendiente = strtolower(trim((string) $vineta->id_pendiente_empaque));
        if (str_starts_with($idPendiente, 'or-') || str_starts_with($idPendiente, 'o-') || str_starts_with($idPendiente, 'li-')) {
            return true;
        }

        return VinetaPorOrden::query()
            ->whereRaw('LOWER(codigo_qr) = ?', [$idPendiente])
            ->exists();
    }

    private function findVineta(string $candidate): ?Vineta
    {
        $candidate = trim($candidate);
        $candidateClean = ltrim($candidate, '#');
        $candidateLower = strtolower($candidate);

        if ($candidate === '') {
            return null;
        }

        // 1. Direct match by api_id (the primary unique identifier for individual viñetas)
        if (ctype_digit($candidateClean)) {
            $vineta = Vineta::query()->where('api_id', (int) $candidateClean)->first();
            if ($vineta) {
                return $vineta;
            }
        }

        // 2. Viñeta por orden formats (OR-1, O-1, etc.)
        if (preg_match('/^o(?:r)?-(\d+)$/i', $candidate, $m)) {
            $vpo = VinetaPorOrden::query()
                ->where(function ($query) use ($m) {
                    $query->whereRaw('LOWER(codigo_qr) = ?', ['or-' . $m[1]])
                        ->orWhereRaw('LOWER(codigo_qr) = ?', ['o-' . $m[1]])
                        ->orWhere('id', (int) $m[1]);
                })
                ->first();

            if ($vpo) {
                return Vineta::updateOrCreate(
                    ['id_pendiente_empaque' => $vpo->codigo_qr],
                    [
                        'api_id' => $vpo->api_id ?: (900000 + $vpo->id),
                        'item' => $vpo->item,
                        'codigo_producto' => $vpo->codigo_producto,
                        'orden_del_sistema' => $vpo->orden_del_sistema,
                        'mes' => $vpo->mes,
                        'orden' => $vpo->orden,
                        'marca' => $vpo->marca,
                        'nombre' => $vpo->nombre,
                        'capa' => $vpo->capa,
                        'vitola' => $vpo->vitola,
                        'tipo_empaque' => $vpo->tipo_empaque,
                        'estado' => $vpo->estado ?: 'activo',
                        'impreso' => true,
                    ]
                );
            }

            $vineta = Vineta::query()
                ->where(function ($query) use ($m) {
                    $query->whereRaw('LOWER(id_pendiente_empaque) = ?', ['or-' . $m[1]])
                        ->orWhereRaw('LOWER(id_pendiente_empaque) = ?', ['o-' . $m[1]]);
                })
                ->first();

            if ($vineta) {
                return $vineta;
            }
        }

        // 3. Viñeta pendiente format (LI-1, li-1, etc.)
        if (preg_match('/^li-(\d+)$/i', $candidate, $m)) {
            $num = (int) $m[1];
            $vineta = Vineta::query()
                ->whereRaw('LOWER(id_pendiente_empaque) = ?', ['li-' . $num])
                ->first();

            if ($vineta) {
                return $vineta;
            }

            $pendienteService = app(\App\Services\VinetaPendienteService::class);
            $item = $pendienteService->findByQrOrNumber($num);

            if ($item) {
                return Vineta::updateOrCreate(
                    ['id_pendiente_empaque' => 'li-' . $num],
                    [
                        'api_id' => 800000 + $num,
                        'item' => $item->item,
                        'codigo_producto' => $item->codigo_producto,
                        'orden_del_sistema' => $item->orden_del_sistema,
                        'mes' => $item->mes,
                        'orden' => $item->orden,
                        'marca' => $item->marca,
                        'nombre' => $item->nombre,
                        'capa' => $item->capa,
                        'vitola' => $item->vitola,
                        'tipo_empaque' => $item->tipo_empaque,
                        'cantidad_puros' => $item->cantidad_puros,
                        'estado' => 'activo',
                        'impreso' => true,
                    ]
                );
            }
        }

        // 3. Exact match in VinetaPorOrden by codigo_qr
        $vpo = VinetaPorOrden::query()
            ->whereRaw('LOWER(codigo_qr) = ?', [$candidateLower])
            ->first();

        if ($vpo) {
            return Vineta::updateOrCreate(
                ['id_pendiente_empaque' => $vpo->codigo_qr],
                [
                    'api_id' => $vpo->api_id ?: (900000 + $vpo->id),
                    'item' => $vpo->item,
                    'codigo_producto' => $vpo->codigo_producto,
                    'orden_del_sistema' => $vpo->orden_del_sistema,
                    'mes' => $vpo->mes,
                    'orden' => $vpo->orden,
                    'marca' => $vpo->marca,
                    'nombre' => $vpo->nombre,
                    'capa' => $vpo->capa,
                    'vitola' => $vpo->vitola,
                    'tipo_empaque' => $vpo->tipo_empaque,
                    'estado' => $vpo->estado ?: 'activo',
                    'impreso' => true,
                ]
            );
        }

        // 4. Exact match by id_pendiente_empaque on Vineta
        $vineta = Vineta::query()
            ->where(function ($query) use ($candidate, $candidateLower) {
                $query->where('id_pendiente_empaque', $candidate)
                    ->orWhereRaw('LOWER(id_pendiente_empaque) = ?', [$candidateLower]);
            })
            ->first();

        if ($vineta) {
            return $vineta;
        }

        // 5. Fallback match by secondary fields (id_detalle_programacion, codigo_producto, item, orden, orden_del_sistema)
        $vineta = Vineta::query()
            ->where(function ($query) use ($candidate) {
                $query->where('id_detalle_programacion', $candidate)
                    ->orWhere('codigo_producto', $candidate)
                    ->orWhere('item', $candidate)
                    ->orWhere('orden', $candidate)
                    ->orWhere('orden_del_sistema', $candidate);
            })
            ->first();

        if ($vineta) {
            return $vineta;
        }

        // 6. Fallback match in VinetaPorOrden by secondary fields
        $vpo = VinetaPorOrden::query()
            ->where(function ($query) use ($candidate) {
                $query->where('id_pendiente_empaque', $candidate)
                    ->orWhere('codigo_producto', $candidate)
                    ->orWhere('item', $candidate);
            })
            ->first();

        if ($vpo) {
            return Vineta::updateOrCreate(
                ['id_pendiente_empaque' => $vpo->codigo_qr],
                [
                    'api_id' => $vpo->api_id ?: (900000 + $vpo->id),
                    'item' => $vpo->item,
                    'codigo_producto' => $vpo->codigo_producto,
                    'orden_del_sistema' => $vpo->orden_del_sistema,
                    'mes' => $vpo->mes,
                    'orden' => $vpo->orden,
                    'marca' => $vpo->marca,
                    'nombre' => $vpo->nombre,
                    'capa' => $vpo->capa,
                    'vitola' => $vpo->vitola,
                    'tipo_empaque' => $vpo->tipo_empaque,
                    'estado' => $vpo->estado ?: 'activo',
                    'impreso' => true,
                ]
            );
        }

        return null;
    }

    public function resolveUltimaCantidadPuros(Vineta $vineta): ?int
    {
        // 1. Buscar en registros activos de esta viñeta específica (por vineta_id o api_id)
        $ultimoRegistroVineta = VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where('cantidad_puros', '>', 0)
            ->where(function ($query) use ($vineta) {
                $query->where('vineta_id', $vineta->id);
                if ($vineta->api_id) {
                    $query->orWhere('vineta_api_id', $vineta->api_id);
                }
            })
            ->latest('id')
            ->value('cantidad_puros');

        if ($ultimoRegistroVineta !== null && $ultimoRegistroVineta > 0) {
            return (int) $ultimoRegistroVineta;
        }

        // 2. Coincidencia estricta: producto_item, orden_del_sistema, orden y producto_codigo
        $query = VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where('cantidad_puros', '>', 0);

        if (!empty($vineta->item) && !empty($vineta->orden_del_sistema) && !empty($vineta->orden) && !empty($vineta->codigo_producto)) {
            $strict = (clone $query)
                ->where('producto_item', $vineta->item)
                ->where('orden_del_sistema', $vineta->orden_del_sistema)
                ->where('orden', $vineta->orden)
                ->where('producto_codigo', $vineta->codigo_producto)
                ->latest('id')
                ->value('cantidad_puros');

            if ($strict !== null && $strict > 0) {
                return (int) $strict;
            }
        }

        // 3. Coincidencia secundaria: item y orden del sistema
        if (!empty($vineta->item) && !empty($vineta->orden_del_sistema)) {
            $secundario = (clone $query)
                ->where('producto_item', $vineta->item)
                ->where('orden_del_sistema', $vineta->orden_del_sistema)
                ->latest('id')
                ->value('cantidad_puros');

            if ($secundario !== null && $secundario > 0) {
                return (int) $secundario;
            }
        }

        // 4. Coincidencia por código de producto y orden del sistema
        if (!empty($vineta->codigo_producto) && !empty($vineta->orden_del_sistema)) {
            $terciario = (clone $query)
                ->where('producto_codigo', $vineta->codigo_producto)
                ->where('orden_del_sistema', $vineta->orden_del_sistema)
                ->latest('id')
                ->value('cantidad_puros');

            if ($terciario !== null && $terciario > 0) {
                return (int) $terciario;
            }
        }

        return $vineta->cantidad_puros;
    }

    private function vinetaPayload(Vineta $vineta, $scannedAt = null): array
    {
        $scannedAt ??= now('America/Tegucigalpa');
        $producto = $this->findProducto($vineta);
        $isPorOrden = $this->isVinetaPorOrden($vineta);

        return [
            'id' => $vineta->id,
            'api_id' => $vineta->api_id !== null ? (int) $vineta->api_id : null,
            'codigo_qr' => $vineta->id_pendiente_empaque,
            'id_pendiente_empaque' => $vineta->id_pendiente_empaque,
            'id_detalle_programacion' => $vineta->id_detalle_programacion,
            'es_por_orden' => $isPorOrden,
            'fecha' => $vineta->fecha?->format('Y-m-d'),
            'marca' => $vineta->marca,
            'nombre' => $vineta->nombre,
            'capa' => $vineta->capa,
            'vitola' => $vineta->vitola,
            'tipo_empaque' => $vineta->tipo_empaque,
            'presentacion' => $producto?->presentacion?->nombre,
            'codigo_producto' => $vineta->codigo_producto,
            'item' => $vineta->item,
            'orden_del_sistema' => $vineta->orden_del_sistema,
            'mes' => $vineta->mes,
            'orden' => $vineta->orden,
            'cantidad_puros' => $this->resolveUltimaCantidadPuros($vineta) ?? $vineta->cantidad_puros,
            'estado' => $vineta->estado,
            'impreso' => (bool) $vineta->impreso,
            'api_created_at' => $vineta->api_created_at?->toISOString(),
            'api_updated_at' => $vineta->api_updated_at?->toISOString(),
            'updated_at' => $vineta->updated_at?->toISOString(),
            'escaneado_en' => $scannedAt->toIso8601String(),
            'fecha_escaneo' => $scannedAt->format('Y-m-d'),
            'hora_escaneo' => $scannedAt->format('H:i:s'),
            'escaneado_en_texto' => $scannedAt->format('d/m/Y h:i A'),
            'proceso' => $this->procesoVinetaPayload($vineta),
        ];
    }

    private function procesoVinetaPayload(Vineta $vineta): array
    {
        $isPorOrden = $this->isVinetaPorOrden($vineta);
        $registros = VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where(function ($query) use ($vineta) {
                $query->where('vineta_id', $vineta->id);

                if ($vineta->api_id && $vineta->fecha) {
                    $query->orWhere(function ($query) use ($vineta) {
                        $query->where('vineta_api_id', $vineta->api_id)
                            ->whereDate('vineta_fecha', $vineta->fecha->format('Y-m-d'));
                    });
                }
            })
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get();
        $grupos = [
            'rezago' => null,
            'anillado' => null,
            'llenado' => null,
        ];
        $actividadesPorGrupo = [
            'rezago' => [],
            'anillado' => [],
            'llenado' => [],
        ];
        $registrosPayload = [];

        foreach ($registros as $registro) {
            $grupo = $this->grupoActividadProceso(
                $registro->actividad_nombre,
                $registro->actividad_tipo_empaque,
                $registro->actividad_codigo
            );

            $payload = [
                'id' => $registro->id,
                'actividad_id' => $registro->actividad_id,
                'actividad_api_id' => $registro->actividad_api_id,
                'actividad_codigo' => $registro->actividad_codigo,
                'actividad_nombre' => $registro->actividad_nombre,
                'grupo' => $grupo,
                'empleado' => $registro->empleado_nombre,
                'empleado_codigo' => $registro->empleado_codigo,
                'fecha' => $registro->fechaHoraRegistroTexto(),
            ];

            if ($grupo && array_key_exists($grupo, $grupos)) {
                $grupos[$grupo] = $registro;
                $actividadesPorGrupo[$grupo][] = $payload;
            }

            $registrosPayload[] = $payload;
        }

        if ($isPorOrden) {
            return [
                'puede_llenar' => true,
                'mensaje_bloqueo_llenado' => null,
                'es_por_orden' => true,
                'pasos' => [
                    $this->pasoProcesoPayload('rezago', 'Rezago', null, false, $actividadesPorGrupo['rezago']),
                    $this->pasoProcesoPayload('anillado', 'Anillado', null, false, $actividadesPorGrupo['anillado']),
                    $this->pasoProcesoPayload('llenado', 'Llenado', null, false, $actividadesPorGrupo['llenado']),
                ],
                'registros' => $registrosPayload,
            ];
        }

        return [
            'puede_llenar' => true,
            'mensaje_bloqueo_llenado' => null,
            'pasos' => [
                $this->pasoProcesoPayload('rezago', 'Rezago', $grupos['rezago'], true, $actividadesPorGrupo['rezago']),
                $this->pasoProcesoPayload('anillado', 'Anillado', $grupos['anillado'], false, $actividadesPorGrupo['anillado']),
                $this->pasoProcesoPayload('llenado', 'Llenado', $grupos['llenado'], false, $actividadesPorGrupo['llenado']),
            ],
            'registros' => $registrosPayload,
        ];
    }

    private function pasoProcesoPayload(string $key, string $label, ?VinetaRegistro $registro, bool $opcional, array $actividades = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'completado' => $registro !== null,
            'opcional' => $opcional,
            'actividad' => $registro?->actividad_nombre,
            'empleado' => $registro?->empleado_nombre,
            'fecha' => $registro?->fechaHoraRegistroTexto(),
            'actividades' => $actividades,
        ];
    }

    private function grupoActividadProceso(?string $nombre, ?string $tipoEmpaque = null, ?string $codigo = null): ?string
    {
        $nombreNorm = $this->normalizeMatchValue($nombre ?? '');
        $texto = $this->normalizeMatchValue(implode(' ', array_filter([$nombre, $tipoEmpaque, $codigo])));

        if ($texto === '') {
            return null;
        }

        if (str_contains($nombreNorm, 'rezag') || str_contains($nombreNorm, 'rezad') || str_contains($nombreNorm, 'resag')) {
            return 'rezago';
        }

        if (
            str_contains($nombreNorm, 'anill')
            || str_contains($nombreNorm, 'anil')
            || str_contains($nombreNorm, 'celof')
            || str_contains($nombreNorm, 'sello')
            || str_contains($nombreNorm, 'esponj')
            || str_contains($nombreNorm, 'lamina')
        ) {
            return 'anillado';
        }

        if (
            str_contains($nombreNorm, 'llenad')
            || str_contains($nombreNorm, 'kretek')
            || str_contains($nombreNorm, 'petaca')
            || str_contains($nombreNorm, 'sampler')
            || str_contains($nombreNorm, 'display')
            || str_contains($nombreNorm, 'bolsa')
            || str_contains($nombreNorm, 'sellado')
            || (str_contains($nombreNorm, 'sell') && ! str_contains($nombreNorm, 'celof') && ! str_contains($nombreNorm, 'anill'))
            || (str_contains($nombreNorm, 'paquete') && str_contains($nombreNorm, 'tubo'))
        ) {
            return 'llenado';
        }

        if (str_contains($texto, 'rezag') || str_contains($texto, 'rezad') || str_contains($texto, 'resag')) {
            return 'rezago';
        }

        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'anil')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'sello')
            || str_contains($texto, 'esponj')
            || str_contains($texto, 'lamina')
        ) {
            return 'anillado';
        }

        if (
            str_contains($texto, 'llenad')
            || str_contains($texto, 'kretek')
            || str_contains($texto, 'petaca')
            || str_contains($texto, 'sampler')
            || str_contains($texto, 'display')
            || str_contains($texto, 'bolsa')
            || str_contains($texto, 'sellado')
            || (str_contains($texto, 'sell') && ! str_contains($texto, 'celof') && ! str_contains($texto, 'anill'))
            || (str_contains($texto, 'paquete') && str_contains($texto, 'tubo'))
        ) {
            return 'llenado';
        }

        return null;
    }




    private function findProducto(Vineta $vineta): ?Producto
    {
        foreach (['item' => $vineta->item, 'codigo_producto' => $vineta->codigo_producto] as $column => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $productos = Producto::query()
                ->with(['actividades', 'tipoEmpaque', 'capa', 'vitola', 'presentacion'])
                ->where($column, $value)
                ->get();

            if ($productos->isNotEmpty()) {
                return $productos->first(fn (Producto $producto) => $this->productoCoincideCaracteristicas($producto, $vineta))
                    ?? $productos->first();
            }
        }

        return null;
    }

    private function productoCoincideCaracteristicas(Producto $producto, Vineta $vineta): bool
    {
        $item = trim((string) $vineta->item);

        if ($item !== '' && trim((string) $producto->item) === $item) {
            return true;
        }

        return $this->lowerTrim($producto->capa?->nombre) === $this->lowerTrim($vineta->capa)
            && $this->lowerTrim($producto->vitola?->nombre) === $this->lowerTrim($vineta->vitola)
            && $this->lowerTrim($producto->tipoEmpaque?->nombre) === $this->lowerTrim($vineta->tipo_empaque);
    }

    private function findProductoPorCaracteristicas(Vineta $vineta, ?int $excludeProductoId = null): ?Producto
    {
        $capa = $this->normalizeMatchValue($vineta->capa);
        $vitola = $this->normalizeMatchValue($vineta->vitola);
        $tipoEmpaque = $this->normalizeMatchValue($vineta->tipo_empaque);

        if ($capa === '' || $vitola === '' || $tipoEmpaque === '') {
            return null;
        }

        $nombre = $this->lowerTrim($vineta->nombre);

        return Producto::query()
            ->with(['actividades', 'tipoEmpaque', 'capa', 'vitola', 'presentacion'])
            ->whereHas('actividades')
            ->when($excludeProductoId, fn ($query) => $query->where('id', '!=', $excludeProductoId))
            ->whereHas('capa', fn ($query) => $query->whereRaw('LOWER(TRIM(nombre)) = ?', [$this->lowerTrim($vineta->capa)]))
            ->whereHas('vitola', fn ($query) => $query->whereRaw('LOWER(TRIM(nombre)) = ?', [$this->lowerTrim($vineta->vitola)]))
            ->whereHas('tipoEmpaque', fn ($query) => $query->whereRaw('LOWER(TRIM(nombre)) = ?', [$this->lowerTrim($vineta->tipo_empaque)]))
            ->when($nombre !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(productos.nombre)) = ?', [$nombre]))
            ->first();
    }

    private function productoPayload(Producto $producto): array
    {
        return [
            'id' => $producto->id,
            'api_id_producto' => $producto->api_id_producto,
            'item' => $producto->item,
            'codigo_producto' => $producto->codigo_producto,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'tipo_empaque' => $producto->tipoEmpaque?->nombre,
            'presentacion' => $producto->presentacion?->nombre,
        ];
    }

    private function vinetaProductoPayload(Vineta $vineta): array
    {
        return [
            'id' => null,
            'api_id_producto' => null,
            'item' => $vineta->item,
            'codigo_producto' => $vineta->codigo_producto,
            'nombre' => $vineta->nombre,
            'descripcion' => null,
            'tipo_empaque' => $vineta->tipo_empaque,
            'presentacion' => null,
        ];
    }

    private function actividadesFromProducto(Producto $producto, ?string $tipoEmpaqueFiltro = null): array
    {
        $tipoEmpaqueIds = $producto->actividades
            ->pluck('pivot.tipo_empaque_id')
            ->filter()
            ->unique()
            ->values();

        $tiposEmpaque = TipoEmpaque::query()
            ->whereIn('id', $tipoEmpaqueIds)
            ->pluck('nombre', 'id');
        $tipoEmpaqueFiltro = $this->lowerTrim($tipoEmpaqueFiltro);

        $actividades = $producto->actividades
            ->filter(function ($actividad) use ($tiposEmpaque, $tipoEmpaqueFiltro) {
                // Solo actividades activas para este producto
                $activo = $actividad->pivot?->activo;
                if ($activo !== null && ($activo === false || $activo === 0 || $activo === '0')) {
                    return false;
                }

                if ($tipoEmpaqueFiltro === '') {
                    return true;
                }

                $tipoEmpaqueId = $actividad->pivot?->tipo_empaque_id;
                $tipoEmpaqueNombre = $tipoEmpaqueId ? $tiposEmpaque->get($tipoEmpaqueId) : null;
                if (! $tipoEmpaqueNombre) {
                    return true;
                }

                return $this->lowerTrim($tipoEmpaqueNombre) === $tipoEmpaqueFiltro;
            });

        if ($actividades->isEmpty() && $producto->actividades->isNotEmpty()) {
            $actividades = $producto->actividades->filter(function ($actividad) {
                $activo = $actividad->pivot?->activo;
                return $activo === null || ($activo !== false && $activo !== 0 && $activo !== '0');
            });
        }

        return $actividades
            ->sort(function ($a, $b) {
                $fechaA = $a->pivot?->ultimo_escaneo_en ? strtotime((string) $a->pivot->ultimo_escaneo_en) : 0;
                $fechaB = $b->pivot?->ultimo_escaneo_en ? strtotime((string) $b->pivot->ultimo_escaneo_en) : 0;

                if ($fechaA !== $fechaB) {
                    return $fechaB <=> $fechaA; // Más reciente primero
                }

                $updA = $a->pivot?->updated_at ? strtotime((string) $a->pivot->updated_at) : 0;
                $updB = $b->pivot?->updated_at ? strtotime((string) $b->pivot->updated_at) : 0;

                if ($updA !== $updB) {
                    return $updB <=> $updA;
                }

                return strnatcasecmp($a->nombre ?? '', $b->nombre ?? '');
            })
            ->values()
            ->map(function ($actividad) use ($tiposEmpaque) {
                $tipoEmpaqueId = $actividad->pivot?->tipo_empaque_id;

                return [
                    'id' => $actividad->id,
                    'api_id_actividad' => $actividad->api_id_actividad,
                    'codigo_actividad' => $actividad->codigo_actividad,
                    'nombre' => $actividad->nombre,
                    'tipo_empaque' => $tipoEmpaqueId ? $tiposEmpaque->get($tipoEmpaqueId) : null,
                    'precio_mo' => (string) ($actividad->pivot?->precio_mo ?? '0'),
                ];
            })
            ->all();
    }


    private function findExternalProduct(Vineta $vineta, array $items): ?array
    {
        try {
            foreach (['codigo_producto', 'item', 'orden_del_sistema', 'orden_del_sitema', 'orden'] as $key) {
                $candidate = match ($key) {
                    'codigo_producto' => $vineta->codigo_producto,
                    'item' => $vineta->item,
                    'orden_del_sistema', 'orden_del_sitema' => $vineta->orden_del_sistema,
                    'orden' => $vineta->orden,
                    default => null,
                };

                $candidate = trim((string) $candidate);

                if ($candidate === '') {
                    continue;
                }

                foreach ($items as $item) {
                    if (! is_array($item) || empty($item['actividades'])) {
                        continue;
                    }

                    if (trim((string) ($item[$key] ?? '')) === $candidate) {
                        return $item;
                    }
                }
            }

            $nombre = $this->lowerTrim($vineta->nombre);
            $capa = $this->normalizeMatchValue($vineta->capa);
            $vitola = $this->normalizeMatchValue($vineta->vitola);
            $tipoEmpaque = $this->normalizeMatchValue($vineta->tipo_empaque);

            if ($nombre === '' || $capa === '' || $vitola === '' || $tipoEmpaque === '') {
                return null;
            }

            foreach ($items as $item) {
                if (! is_array($item) || empty($item['actividades'])) {
                    continue;
                }

                if (
                    $this->lowerTrim($item['nombre'] ?? null) === $nombre
                    && $this->normalizeMatchValue($item['capa'] ?? null) === $capa
                    && $this->normalizeMatchValue($item['vitola'] ?? null) === $vitola
                    && $this->normalizeMatchValue($item['tipo_empaque'] ?? null) === $tipoEmpaque
                ) {
                    return $item;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function findExternalProductPorCaracteristicas(Vineta $vineta, array $items): ?array
    {
        try {
            $capa = $this->normalizeMatchValue($vineta->capa);
            $vitola = $this->normalizeMatchValue($vineta->vitola);
            $tipoEmpaque = $this->normalizeMatchValue($vineta->tipo_empaque);

            if ($capa === '' || $vitola === '' || $tipoEmpaque === '') {
                return null;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (
                    $this->normalizeMatchValue($item['capa'] ?? null) === $capa
                    && $this->normalizeMatchValue($item['vitola'] ?? null) === $vitola
                    && $this->normalizeMatchValue($item['tipo_empaque'] ?? null) === $tipoEmpaque
                    && ! empty($item['actividades'])
                ) {
                    return $item;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function externalCatalogItems(): array
    {
        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->get(env('API_CATALOGO_URL', $this->catalogoUrl));
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $items = $response->json('data') ?? $response->json();

        return is_array($items) ? $items : [];
    }

    private function externalProductoPayload(array $item): array
    {
        return [
            'id' => null,
            'api_id_producto' => $item['id_producto'] ?? null,
            'item' => $item['item'] ?? null,
            'codigo_producto' => $item['codigo_producto'] ?? null,
            'nombre' => $item['nombre'] ?? null,
            'descripcion' => $item['des'] ?? null,
            'tipo_empaque' => $item['tipo_empaque'] ?? null,
            'presentacion' => $item['presentacion'] ?? null,
        ];
    }

    private function actividadesFromExternalProduct(array $item, ?string $tipoEmpaqueFiltro = null): array
    {
        $tipoEmpaqueFiltro = $this->lowerTrim($tipoEmpaqueFiltro);

        return collect($item['actividades'] ?? [])
            ->filter(fn ($actividad) => is_array($actividad))
            ->filter(function (array $actividad) use ($tipoEmpaqueFiltro) {
                return $tipoEmpaqueFiltro === ''
                    || $this->lowerTrim($actividad['tipo_empaque'] ?? null) === $tipoEmpaqueFiltro;
            })
            ->map(fn ($actividad) => [
                'id' => null,
                'api_id_actividad' => $actividad['id_actividad'] ?? null,
                'codigo_actividad' => $actividad['codigo_actividad'] ?? null,
                'nombre' => $actividad['nombre_actividad'] ?? null,
                'tipo_empaque' => $actividad['tipo_empaque'] ?? null,
                'precio_mo' => (string) ($actividad['precio_mo'] ?? '0'),
            ])
            ->sortBy('nombre')
            ->values()
            ->all();
    }

    private function normalizeMatchValue($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = Str::ascii(Str::lower($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function lowerTrim($value): string
    {
        return Str::lower(trim((string) $value));
    }
}
