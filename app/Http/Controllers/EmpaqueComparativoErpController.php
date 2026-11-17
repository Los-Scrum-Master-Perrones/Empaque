<?php

namespace App\Http\Controllers;

use App\Services\ErpComparativoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comparativo ERP vs sistema de Empaque.
 *
 * Baja del ERP (solo lectura) lo del dia a tablas espejo y muestra las
 * diferencias: documentos, detalle por empleado/actividad y planificacion.
 */
class EmpaqueComparativoErpController extends Controller
{
    public function index(Request $request, ErpComparativoService $servicio)
    {
        $fecha = $this->fecha($request->get('fecha'));
        $documento = trim((string) $request->get('documento', ''));
        $sucursal = (int) ($request->get('sucursal') ?: config('services.erp.default_sucursal', 2));
        if ($sucursal <= 0) {
            $sucursal = 2;
        }

        $tieneEspejo = Schema::hasTable('erp_empaques_documentos')
            && Schema::hasTable('erp_empaques_actividades')
            && Schema::hasTable('erp_empaques_detalles')
            && Schema::hasTable('erp_empaques_planificacion');

        $descargado = false;
        $errorDescarga = null;
        $resultado = ['documentos' => [], 'detalle' => [], 'planificacion' => [], 'resumen' => []];
        $ultimaDescarga = null;

        if ($tieneEspejo) {
            $descargado = $servicio->yaDescargada($fecha, $sucursal);

            $ultimaDescarga = DB::table('erp_empaques_documentos')
                ->where('sucursal', $sucursal)
                ->whereDate('fecha', $fecha)
                ->max('descargado_en');

            if ($ultimaDescarga === null && Schema::hasTable('erp_empaques_descargas')) {
                $ultimaDescarga = DB::table('erp_empaques_descargas')
                    ->where('sucursal', $sucursal)
                    ->whereDate('fecha', $fecha)
                    ->value('descargado_en');
            }

            $errorDescarga = null;

            // "Ver" (refrescar=1) siempre vuelve a bajar del ERP, por si algo
            // cambio alla (horas ajustadas, documento anulado, etc). Sin ese
            // parametro, solo baja si nunca se bajo o si la copia tiene mas de
            // 2 minutos.
            // Ultimo intento (aunque haya fallado): si hace menos de 60 s que
            // se intento y salio mal, no se vuelve a pegar al ERP en cada carga.
            $ultimoIntentoOk = false;
            $ultimoIntentoReciente = false;

            if (Schema::hasTable('erp_empaques_descargas')) {
                $intento = DB::table('erp_empaques_descargas')
                    ->where('sucursal', $sucursal)
                    ->whereDate('fecha', $fecha)
                    ->first();

                if ($intento && $intento->descargado_en) {
                    $ultimoIntentoOk = (bool) $intento->ok;
                    $ultimoIntentoReciente = ! $ultimoIntentoOk
                        && \Illuminate\Support\Carbon::parse($intento->descargado_en)->diffInSeconds(now()) < 60;
                }
            }

            $hayQueBajar = (! $descargado
                || $request->boolean('refrescar')
                || ($ultimaDescarga !== null
                    && \Illuminate\Support\Carbon::parse($ultimaDescarga)->diffInSeconds(now()) > 120))
                && (! $ultimoIntentoReciente || $request->boolean('refrescar'));

            if ($hayQueBajar) {
                $descarga = $servicio->descargar($fecha, $sucursal);

                if ($descarga['success'] ?? false) {
                    $descargado = true;
                    $ultimaDescarga = now();
                } else {
                    // Fallo la baja: si ya habia datos se quedan (y se avisa),
                    // para no dejar la pagina en blanco.
                    $errorDescarga = $descarga['message'] ?? 'No se pudo descargar.';
                }
            }

            if ($descargado) {
                $resultado = $servicio->comparar($fecha, $sucursal);

                if ($documento !== '') {
                    $resultado['detalle'] = array_values(array_filter(
                        $resultado['detalle'],
                        fn (array $d) => (string) $d['numero'] === $documento
                    ));
                }
            }
        }

        $totalesEmpleados = $this->totalesPorEmpleado($resultado['detalle']);

        return view('costos-empaque.comparativo', [
            'fecha' => $fecha,
            'documento' => $documento,
            'sucursal' => $sucursal,
            'tieneEspejo' => $tieneEspejo,
            'descargado' => $descargado,
            'ultimaDescarga' => $ultimaDescarga,
            'documentos' => $resultado['documentos'],
            'detalle' => $resultado['detalle'],
            'planificacion' => $resultado['planificacion'],
            'resumen' => $resultado['resumen'],
            'totalesEmpleados' => $totalesEmpleados,
            'descargaError' => $errorDescarga,
            'sinDocumentos' => (bool) $descargado && empty($resultado['documentos']),
        ]);
    }

    /**
     * Ajusta las horas de UN empleado en el dia: le manda al ERP las horas
     * reales del sistema (documento por documento) para que desaparezca la
     * diferencia.
     */
    public function sincronizarHoras(Request $request, \App\Services\ErpApiService $erp)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
            'empleado' => ['required', 'string', 'max:20'],
        ]);

        $fecha = (string) $data['fecha'];
        $sucursal = (int) ($data['sucursal'] ?: config('services.erp.default_sucursal', 2)) ?: 2;
        $empleado = trim((string) $data['empleado']);

        // Horas del empleado en el dia, por documento: lo de las viñetas (los
        // minutos reales) MAS las horas ordinarias (el "al dia", que las viñetas
        // por hora no traen).
        $porDocumento = [];

        DB::table('vineta_registros')
            ->selectRaw('TRIM(documento_numero) AS numero, SUM(COALESCE(minutos_trabajados, 0)) AS minutos')
            ->where('estado', 'activo')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha_registro', $fecha)
            ->where('empleado_codigo', $empleado)
            ->whereNotNull('documento_numero')
            ->where('documento_numero', '<>', '')
            ->groupBy('documento_numero')
            ->get()
            ->each(function ($f) use (&$porDocumento) {
                $porDocumento[trim((string) $f->numero)] = ['minutos' => (int) $f->minutos];
            });

        DB::table('empleado_horas_ordinarias')
            ->selectRaw('TRIM(documento_numero) AS numero, SUM(minutos) AS minutos')
            ->where('sucursal', $sucursal)
            ->whereDate('fecha', $fecha)
            ->where('empleado_codigo', $empleado)
            ->whereNotNull('documento_numero')
            ->where('documento_numero', '<>', '')
            ->groupBy('documento_numero')
            ->get()
            ->each(function ($f) use (&$porDocumento) {
                $numero = trim((string) $f->numero);
                $porDocumento[$numero]['minutos'] = ($porDocumento[$numero]['minutos'] ?? 0) + (int) $f->minutos;
            });

        if ($porDocumento === []) {
            return $this->volverConRefrescar("El empleado {$empleado} no tiene registros con documento el {$fecha}.", 'error');
        }

        $documentos = collect($porDocumento)->map(fn ($v, $k) => (object) ['documento_numero' => $k, 'minutos' => $v['minutos']]);

        $ajustados = [];
        $fallidos = [];

        foreach ($documentos as $doc) {
            $horas = round($doc->minutos / 60, 2);
            $respuesta = $erp->fijarHorasEmpleadoDocumento($empleado, (string) $doc->documento_numero, $horas, $sucursal);
            $mensaje = (string) ($respuesta['message'] ?? ($respuesta['error'] ?? 'sin respuesta'));

            if ($respuesta['success'] ?? false) {
                $ajustados[] = trim((string) $doc->documento_numero).' a '.number_format($horas, 2).' h';

                // Deja constancia de que las horas del dia llegaron al ERP.
                DB::table('empleado_horas_ordinarias')
                    ->where('sucursal', $sucursal)
                    ->whereDate('fecha', $fecha)
                    ->where('empleado_codigo', $empleado)
                    ->where('documento_numero', trim((string) $doc->documento_numero))
                    ->update([
                        'erp_enviado' => 1,
                        'erp_enviado_en' => now(),
                        'erp_respuesta' => json_encode(['origen' => 'comparativo:sincronizar-horas', 'horas' => $horas]),
                        'updated_at' => now(),
                    ]);
            } else {
                $fallidos[] = trim((string) $doc->documento_numero).': '.$mensaje;
            }
        }

        if ($ajustados !== [] && $fallidos === []) {
            return $this->volverConRefrescar("Empleado {$empleado} ajustado en ".count($ajustados).' documento(s): '.implode(' | ', $ajustados).'.', 'success');
        }

        if ($ajustados !== [] && $fallidos !== []) {
            return $this->volverConRefrescar('Ajustado: '.implode(' | ', $ajustados).' || NO ajustado: '.implode(' | ', $fallidos), 'success');
        }

        return $this->volverConRefrescar('No se pudo ajustar: '.implode(' | ', $fallidos), 'error');
    }

    /**
     * Suma de puros y de horas por empleado (sistema vs ERP).
     *
     * @return array{filas: array<int, array<string, mixed>>, total: array<string, float|int>}
     */
    private function totalesPorEmpleado(array $detalle): array
    {
        $acumulado = [];

        foreach ($detalle as $d) {
            $codigo = trim((string) ($d['empleado'] ?? ''));
            $nombre = trim((string) ($d['nombre_empleado'] ?? ''));
            $llave = $codigo.'|'.mb_strtolower($nombre);

            if (! isset($acumulado[$llave])) {
                $acumulado[$llave] = [
                    'empleado' => $codigo,
                    'nombre_empleado' => $nombre,
                    'filas' => 0,
                    'sistema_puros' => 0,
                    'sistema_minutos' => 0.0,
                    'sistema_horas' => 0.0,
                    'erp_puros' => 0,
                    'erp_horas' => 0.0,
                ];
            }

            $acumulado[$llave]['filas']++;
            $acumulado[$llave]['sistema_puros'] += (int) ($d['sistema_puros'] ?? 0);
            $acumulado[$llave]['sistema_minutos'] += (float) ($d['sistema_minutos'] ?? 0);
            $acumulado[$llave]['sistema_horas'] += (float) ($d['sistema_horas'] ?? 0);
            $acumulado[$llave]['erp_puros'] += (int) ($d['erp_puros'] ?? 0);
            $acumulado[$llave]['erp_horas'] += (float) ($d['erp_horas'] ?? 0);
        }

        $total = [
            'empleados' => 0,
            'filas' => 0,
            'sistema_puros' => 0,
            'sistema_horas' => 0.0,
            'erp_puros' => 0,
            'erp_horas' => 0.0,
        ];

        $filas = [];

        foreach ($acumulado as $fila) {
            // Horas desde los minutos REALES, no de la suma de filas ya
            // redondeadas (eso generaba diferencias de 0.01 sin motivo).
            $fila['sistema_horas'] = ($fila['sistema_minutos'] > 0)
                ? round($fila['sistema_minutos'] / 60, 2)
                : round($fila['sistema_horas'], 2);

            $fila['erp_horas'] = round($fila['erp_horas'], 2);
            $fila['dif_puros'] = (int) $fila['sistema_puros'] - (int) $fila['erp_puros'];
            $fila['dif_horas'] = round($fila['sistema_horas'] - $fila['erp_horas'], 2);
            $fila['estado'] = ($fila['dif_puros'] === 0 && abs($fila['dif_horas']) <= 0.01)
                ? 'ok'
                : 'diferencia';

            $total['filas'] += $fila['filas'];
            $total['sistema_puros'] += $fila['sistema_puros'];
            $total['sistema_horas'] += $fila['sistema_horas'];
            $total['erp_puros'] += $fila['erp_puros'];
            $total['erp_horas'] += $fila['erp_horas'];

            $filas[] = $fila;
        }

        // Mayores primero.
        usort($filas, fn (array $a, array $b) => [$b['sistema_puros'], $b['erp_puros']] <=> [$a['sistema_puros'], $a['erp_puros']]);

        $total['empleados'] = count($filas);
        $total['sistema_horas'] = round($total['sistema_horas'], 2);
        $total['erp_horas'] = round($total['erp_horas'], 2);
        $total['dif_puros'] = (int) $total['sistema_puros'] - (int) $total['erp_puros'];
        $total['dif_horas'] = round($total['sistema_horas'] - $total['erp_horas'], 2);

        return ['filas' => $filas, 'total' => $total];
    }

    public function descargar(Request $request, ErpComparativoService $servicio)
    {
        $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
        ]);

        $fecha = (string) $request->get('fecha');
        $sucursal = (int) ($request->get('sucursal') ?: config('services.erp.default_sucursal', 2));
        if ($sucursal <= 0) {
            $sucursal = 2;
        }

        $resultado = $servicio->descargar($fecha, $sucursal);

        if (! ($resultado['success'] ?? false)) {
            return redirect()
                ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
                ->with('error', $resultado['message'] ?? 'No se pudo descargar del ERP.');
        }

        return redirect()
            ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
            ->with('success', sprintf(
                'Descargado del ERP: %d documentos, %d actividades, %d filas de detalle, %d de planificacion.',
                $resultado['documentos'],
                $resultado['actividades'],
                $resultado['detalles'],
                $resultado['planificacion']
            ));
    }

    /**
     * Agrega al ERP las filas de planificacion que faltan para el dia,
     * tomando las combinaciones (orden + linea + actividad) del detalle.
     * Solo inserta: nunca actualiza ni borra.
     */
    public function sincronizarPlanificacion(Request $request, ErpComparativoService $servicio)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $fecha = (string) $data['fecha'];
        $sucursal = (int) ($data['sucursal'] ?: config('services.erp.default_sucursal', 2));
        if ($sucursal <= 0) {
            $sucursal = 2;
        }

        $dryRun = (bool) ($data['dry_run'] ?? true);

        $resultado = $servicio->sincronizarPlanificacion($fecha, $sucursal, $dryRun);

        if (! ($resultado['success'] ?? false)) {
            return redirect()
                ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
                ->with('error', $resultado['message'] ?? 'No se pudo sincronizar la planificacion.');
        }

        $faltantes = (int) ($resultado['faltantes'] ?? 0);
        $insertadas = (int) ($resultado['insertadas'] ?? 0);

        if (! $dryRun) {
            // Refrescar el espejo para que el comparativo ya muestre las filas nuevas.
            $servicio->descargar($fecha, $sucursal);

            return redirect()
                ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
                ->with('success', "Se agregaron {$insertadas} filas de planificacion al ERP para {$fecha} (faltaban {$faltantes}).");
        }

        return redirect()
            ->route('costos-empaque.comparativo', ['fecha' => $fecha, 'sucursal' => $sucursal])
            ->with('success', "Simulacion: faltan {$faltantes} filas de planificacion para {$fecha}. No se escribio nada en el ERP.");
    }

    /**
     * Sincroniza UN registro: manda al ERP los puros y horas del sistema
     * (y el precio). Si la fila no existe en el ERP, la registra.
     */
    public function sincronizar(Request $request, \App\Services\ErpApiService $erp)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'sucursal' => ['nullable', 'integer'],
            'numero' => ['required', 'string', 'max:20'],
            'actividad' => ['required', 'integer'],
            'orden' => ['required', 'string', 'max:20'],
            'empleado' => ['required', 'string', 'max:20'],
            'producto' => ['nullable', 'string', 'max:40'],
        ]);

        $sucursal = (int) ($data['sucursal'] ?? config('services.erp.default_sucursal', 2)) ?: 2;
        $numero = trim((string) $data['numero']);
        $orden = trim((string) $data['orden']);
        $empleado = trim((string) $data['empleado']);
        $producto = trim((string) ($data['producto'] ?? ''));

        $registros = \App\Models\VinetaRegistro::query()
            ->where('estado', \App\Models\VinetaRegistro::ESTADO_ACTIVO)
            ->where('sucursal', $sucursal)
            ->where('documento_numero', $numero)
            ->where('actividad_codigo', (int) $data['actividad'])
            ->where('empleado_codigo', $empleado)
            ->whereDate('fecha_registro', $data['fecha'])
            ->where(function ($q) use ($orden) {
                $q->where('orden_del_sistema', $orden)
                    ->orWhere('orden_del_sistema', ltrim($orden, '0'))
                    ->orWhereRaw('LPAD(TRIM(orden_del_sistema), 10, "0") = ?', [$orden]);
            })
            ->when($producto !== '', fn ($q) => $q->where('producto_codigo', $producto))
            ->get();

        if ($registros->isEmpty()) {
            return $this->volverConRefrescar('No hay registros en el sistema para esa fila.', 'error');
        }

        $puros = (int) $registros->sum('cantidad_puros');
        $minutos = (int) $registros->sum(fn ($r) => (int) ($r->minutos_trabajados ?? 0));
        $horas = round($minutos / 60, 2);
        $precio = (float) ($registros->max('precio_mo') ?? 0);

        $resultado = $erp->fijarHorasVineta($registros->first(), $horas, $sucursal, $puros, $precio > 0 ? $precio : null);

        $ok = ! empty($resultado['success']);
        $mensaje = $resultado['message'] ?? ($resultado['error'] ?? 'Sin respuesta del ERP.');

        return $this->volverConRefrescar(
            ($ok ? 'Sincronizado: ' : 'No se pudo sincronizar: ')
            ."doc $numero · act {$data['actividad']} · emp $empleado · $puros puros / ".number_format($horas, 2).' h'
            ." - $mensaje",
            $ok ? 'success' : 'error'
        );
    }

    /**
     * Regresa al comparativo (a la misma fecha/documento) y fuerza que vuelva
     * a bajar del ERP, para ver el resultado del cambio recien hecho.
     */
    private function volverConRefrescar(string $mensaje, string $tipo)
    {
        $url = back()->getTargetUrl();

        if (! preg_match('/[?&]refrescar=1/', $url)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'refrescar=1';
        }

        return redirect()->to($url)->with($tipo, $mensaje);
    }

    private function fecha($valor): string
    {
        $valor = trim((string) $valor);

        if ($valor !== '') {
            try {
                return Carbon::createFromFormat('Y-m-d', $valor, 'America/Tegucigalpa')->toDateString();
            } catch (\Throwable) {
                // usa hoy
            }
        }

        return Carbon::now('America/Tegucigalpa')->toDateString();
    }
}
