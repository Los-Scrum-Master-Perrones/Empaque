<?php

namespace App\Services;

use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Viñetas que se generan DENTRO de Empaque (no vienen de facturacion):
 * limpieza y brocha. Se crean en vinetas_por_orden con un codigo propio
 * (li-1, br-1) y se espejan en vinetas para que se escaneen como las demas.
 *
 * Las viñetas normales (las que sincroniza la API de facturacion) NO se
 * tocan: su area queda en null.
 */
class VinetaAreaService
{
    public const AREA_LIMPIEZA = 'LIMPIEZA';

    public const AREA_BROCHA = 'BROCHA';

    /** Rango de api_id para viñetas locales (no choca con las de facturacion). */
    private const API_ID_BASE = 900000;

    /**
     * @return array<string, array{titulo:string, prefijo:string, actividades:array<int>}>
     */
    public function areas(): array
    {
        return (array) config('empaque_areas', []);
    }

    public function normalizarArea(?string $area): ?string
    {
        $texto = strtoupper(trim((string) $area));

        return array_key_exists($texto, $this->areas()) ? $texto : null;
    }

    public function titulo(?string $area): ?string
    {
        $area = $this->normalizarArea($area);

        return $area ? (string) ($this->areas()[$area]['titulo'] ?? $area) : null;
    }

    /**
     * Genera N viñetas del area indicada para un pendiente de empaque.
     *
     * @param  array<string, mixed>  $pendiente  datos del pendiente (id_pendiente, item, marca, ...)
     * @return array{ok:bool, codigos:array<int,string>, mensaje:string}
     */
    public function generar(array $pendiente, string $area, int $cantidad, int $purosPorVineta = 250): array
    {
        $area = $this->normalizarArea($area);

        if (! $area) {
            return ['ok' => false, 'codigos' => [], 'mensaje' => 'Area no valida.'];
        }

        $cantidad = max(1, min($cantidad, 500));
        $purosPorVineta = max(0, $purosPorVineta);
        $prefijo = (string) $this->areas()[$area]['prefijo'];
        $siguiente = $this->siguienteNumero($prefijo);
        $codigos = [];

        try {
            DB::transaction(function () use ($pendiente, $area, $cantidad, $purosPorVineta, $prefijo, $siguiente, &$codigos) {
                $fecha = Carbon::now('America/Tegucigalpa')->format('Y-m-d');
                $idPendiente = trim((string) ($pendiente['id_pendiente'] ?? ''));

                for ($i = 1; $i <= $cantidad; $i++) {
                    $codigo = $prefijo.'-'.($siguiente + $i);

                    $vpo = VinetaPorOrden::create([
                        'codigo_qr' => $codigo,
                        'api_id' => null,
                        'id_pendiente_empaque' => $idPendiente !== '' ? $idPendiente : null,
                        'fecha' => $fecha,
                        'cantidad_puros' => $purosPorVineta,
                        'estado' => 'activo',
                        // Nace pendiente de imprimir (como facturación):
                        // despues un solo boton imprime todas las pendientes.
                        'impreso' => false,
                        'item' => $this->texto($pendiente['item'] ?? null),
                        'orden_del_sistema' => $this->texto($pendiente['orden_del_sistema'] ?? null),
                        'mes' => $this->texto($pendiente['mes'] ?? null),
                        'orden' => $this->texto($pendiente['orden'] ?? null),
                        'marca' => $this->texto($pendiente['marca'] ?? null),
                        'nombre' => $this->texto($pendiente['nombre'] ?? null),
                        'capa' => $this->texto($pendiente['capa'] ?? null),
                        'vitola' => $this->texto($pendiente['vitola'] ?? null),
                        'tipo_empaque' => $this->texto($pendiente['tipo_empaque'] ?? null),
                        'codigo_producto' => $this->texto($pendiente['codigo_producto'] ?? null),
                        'area' => $area,
                    ]);

                    // Espejo en vinetas para que el escaneo movil la encuentre.
                    Vineta::updateOrCreate(
                        ['id_pendiente_empaque' => $codigo],
                        [
                            'api_id' => self::API_ID_BASE + $vpo->id,
                            'id_detalle_programacion' => $idPendiente !== '' ? $idPendiente : null,
                            'fecha' => $fecha,
                            'cantidad_puros' => $purosPorVineta,
                            'estado' => 'activo',
                            'impreso' => true,
                            'item' => $vpo->item,
                            'orden_del_sistema' => $vpo->orden_del_sistema,
                            'mes' => $vpo->mes,
                            'orden' => $vpo->orden,
                            'marca' => $vpo->marca,
                            'nombre' => $vpo->nombre,
                            'capa' => $vpo->capa,
                            'vitola' => $vpo->vitola,
                            'tipo_empaque' => $vpo->tipo_empaque,
                            'codigo_producto' => $vpo->codigo_producto,
                            'area' => $area,
                        ]
                    );

                    $codigos[] = $codigo;
                }
            });
        } catch (\Throwable $e) {
            Log::error('VinetaAreaService@generar: '.$e->getMessage(), [
                'area' => $area,
                'pendiente' => $pendiente['id_pendiente'] ?? null,
            ]);

            return ['ok' => false, 'codigos' => [], 'mensaje' => 'No se pudieron generar las viñetas.'];
        }

        $titulo = $this->titulo($area);
        $mensaje = $cantidad === 1
            ? "Viñeta de {$titulo} generada: {$codigos[0]}."
            : $cantidad.' viñetas de '.$titulo.' generadas: '.$codigos[0].' a '.end($codigos).'.';

        return ['ok' => true, 'codigos' => $codigos, 'mensaje' => $mensaje];
    }

    /**
     * Actividades permitidas para un area (del catalogo local).
     *
     * @return Collection<int, \App\Models\Actividad>
     */
    public function actividades(?string $area): Collection
    {
        $area = $this->normalizarArea($area);

        if (! $area) {
            return collect();
        }

        // La viñeta es EXCLUSIVA de su area: solo estas actividades.
        $codigos = (array) ($this->areas()[$area]['actividades'] ?? []);

        if ($codigos === []) {
            return collect();
        }

        return \App\Models\Actividad::query()
            ->whereIn('codigo_actividad', $codigos)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Cual es la siguiente numeracion libre para el prefijo (li-7 -> 8).
     */
    public function siguienteNumero(string $prefijo): int
    {
        $prefijo = strtolower(trim($prefijo));
        $maximo = 0;

        $codigos = VinetaPorOrden::query()
            ->whereNotNull('codigo_qr')
            ->pluck('codigo_qr');

        foreach ($codigos as $codigo) {
            if (preg_match('/^'.preg_quote($prefijo, '/').'-(\d+)$/i', trim((string) $codigo), $m)) {
                $maximo = max($maximo, (int) $m[1]);
            }
        }

        return $maximo;
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
