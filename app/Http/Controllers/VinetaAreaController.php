<?php

namespace App\Http\Controllers;

use App\Services\VinetaAreaService;
use App\Services\VinetaPendienteService;
use Illuminate\Http\Request;

/**
 * Genera viñetas DENTRO de Empaque para un pendiente de empaque:
 * limpieza (y despues brocha). No toca las viñetas que vienen de
 * facturacion; solo agrega viñetas locales escaneables.
 */
class VinetaAreaController extends Controller
{
    public function __construct(
        private readonly VinetaAreaService $areas,
        private readonly VinetaPendienteService $pendientes
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_pendiente' => ['required', 'string', 'max:50'],
            'area' => ['required', 'string', 'max:30'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:500'],
            'cantidad_puros' => ['nullable', 'integer', 'min:0', 'max:100000'],
            // Respaldo por si la API de pendientes no responde: los datos de la fila.
            'item' => ['nullable', 'string', 'max:255'],
            'codigo_producto' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'vitola' => ['nullable', 'string', 'max:255'],
            'capa' => ['nullable', 'string', 'max:255'],
            'tipo_empaque' => ['nullable', 'string', 'max:255'],
            'orden_del_sistema' => ['nullable', 'string', 'max:255'],
            'orden' => ['nullable', 'string', 'max:255'],
            'mes' => ['nullable', 'string', 'max:255'],
        ]);

        $area = $this->areas->normalizarArea($data['area']);

        if (! $area) {
            return back()->with('error', 'Area no valida.');
        }

        $pendiente = $this->datosPendiente($data);

        $resultado = $this->areas->generar(
            $pendiente,
            $area,
            (int) $data['cantidad'],
            (int) ($data['cantidad_puros'] ?? 250)
        );

        if (! $resultado['ok']) {
            return back()->with('error', $resultado['mensaje']);
        }

        // Va directo a la hoja de impresion (mismo formato que facturacion).
        return redirect()
            ->route('vinetas-pendientes.imprimir', ['codigos' => $resultado['codigos']])
            ->with('success', $resultado['mensaje']);
    }

    /**
     * Hoja de impresion de las viñetas generadas (formato igual al de
     * facturacion), con el QR listo para escanear.
     */
    public function imprimir(Request $request)
    {
        $data = $request->validate([
            'codigos' => ['required', 'array', 'min:1'],
            'codigos.*' => ['string', 'max:30'],
        ]);

        $vinetas = \App\Models\VinetaPorOrden::query()
            ->whereIn('codigo_qr', $data['codigos'])
            ->orderBy('id')
            ->get();

        // Al generar la hoja quedan marcadas como impresas (igual que
        // facturación), asi no vuelven a salir en "Imprimir pendientes".
        if ($vinetas->isNotEmpty()) {
            \App\Models\VinetaPorOrden::query()
                ->whereIn('id', $vinetas->pluck('id'))
                ->where('impreso', false)
                ->update(['impreso' => true, 'impreso_en' => now()]);
        }

        return view('vinetas-pendientes.imprimir', [
            'vinetas' => $vinetas,
            'titulo' => $this->areas->titulo($vinetas->first()?->area),
        ]);
    }

    /**
     * Arma los datos del pendiente: primero intenta la API de pendientes
     * (la misma que usa la pantalla), si no, usa lo que venga del formulario.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function datosPendiente(array $data): array
    {
        $idPendiente = trim((string) $data['id_pendiente']);
        $encontrado = null;

        try {
            $encontrado = $this->pendientes->getPendientes()->first(function ($fila) use ($idPendiente) {
                return trim((string) ($fila->id_pendiente ?? '')) === $idPendiente;
            });
        } catch (\Throwable) {
            $encontrado = null;
        }

        if ($encontrado) {
            return [
                'id_pendiente' => $idPendiente,
                'item' => $encontrado->item ?? null,
                'codigo_producto' => $encontrado->codigo_producto ?? null,
                'marca' => $encontrado->marca ?? null,
                'nombre' => $encontrado->nombre ?? null,
                'vitola' => $encontrado->vitola ?? null,
                'capa' => $encontrado->capa ?? null,
                'tipo_empaque' => $encontrado->tipo_empaque ?? null,
                'orden_del_sistema' => $encontrado->orden_del_sistema ?? null,
                'orden' => $encontrado->orden ?? null,
                'mes' => $encontrado->mes ?? null,
            ];
        }

        return [
            'id_pendiente' => $idPendiente,
            'item' => $data['item'] ?? null,
            'codigo_producto' => $data['codigo_producto'] ?? null,
            'marca' => $data['marca'] ?? null,
            'nombre' => $data['nombre'] ?? null,
            'vitola' => $data['vitola'] ?? null,
            'capa' => $data['capa'] ?? null,
            'tipo_empaque' => $data['tipo_empaque'] ?? null,
            'orden_del_sistema' => $data['orden_del_sistema'] ?? null,
            'orden' => $data['orden'] ?? null,
            'mes' => $data['mes'] ?? null,
        ];
    }
}
