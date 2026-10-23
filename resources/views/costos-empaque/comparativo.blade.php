<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativo ERP | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="vinetas-page min-h-screen theme-bg antialiased">
    <div
        x-data="{ sidebarOpen: false, catalogos: false, seguridad: false, produccion: true }"
        class="min-h-screen flex theme-bg">

        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('layouts.topbar')

            <main class="flex-1 min-w-0">
                <section class="app-content-compact">
                    <div class="w-full max-w-none space-y-3">

                        {{-- Titulo y filtros --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3.5 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-3">
                                <div class="min-w-0">
                                    <h1 class="theme-title text-lg sm:text-xl font-black tracking-tight">Comparativo ERP</h1>
                                    <p class="theme-text text-xs mt-0.5">
                                        Baja del ERP (solo lectura) los documentos, actividades, detalle y planificación del día
                                        y los compara contra lo registrado en el sistema.
                                    </p>
                                </div>

                                <form method="GET" action="{{ route('costos-empaque.comparativo') }}"
                                      class="flex flex-wrap items-end gap-2">
                                    <div class="min-w-0">
                                        <label for="fecha" class="theme-text block text-[11px] font-semibold mb-1">Fecha</label>
                                        <input type="date" name="fecha" id="fecha" value="{{ $fecha }}"
                                               class="rounded-xl border theme-border bg-white px-2 py-1.5 text-sm theme-title">
                                    </div>

                                    <div class="min-w-0">
                                        <label for="sucursal" class="theme-text block text-[11px] font-semibold mb-1">Sucursal</label>
                                        <input type="number" name="sucursal" id="sucursal" value="{{ $sucursal }}" min="1"
                                               class="w-20 rounded-xl border theme-border bg-white px-2 py-1.5 text-sm theme-title">
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition">
                                        Ver
                                    </button>
                                </form>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('costos-empaque.comparativo.descargar') }}">
                                    @csrf
                                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                                    <input type="hidden" name="sucursal" value="{{ $sucursal }}">
                                    <button type="submit"
                                            class="inline-flex items-center justify-center px-4 py-1.5 rounded-xl bg-[#0e7490] text-white text-sm font-semibold hover:bg-[#155e75] transition">
                                        Descargar del ERP ({{ $fecha }})
                                    </button>
                                </form>

                                @if($ultimaDescarga)
                                    <span class="theme-text text-xs">
                                        Última descarga: {{ \Illuminate\Support\Carbon::parse($ultimaDescarga)->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if(! $tieneEspejo)
                            <div class="theme-card bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-3 text-sm">
                                Faltan las tablas espejo. Corré <code>php artisan migrate</code> en el sistema de Empaque.
                            </div>
                        @elseif(! $descargado)
                            <div class="theme-card bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-3 text-sm">
                                Todavía no se bajó nada del ERP para {{ $fecha }}. Usá el botón
                                <strong>Descargar del ERP</strong>.
                            </div>
                        @else
                            {{-- Resumen --}}
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                                @foreach([
                                    ['Faltan en el ERP', $resumen['faltan_en_erp'] ?? 0, '#b91c1c'],
                                    ['Sobran en el ERP', $resumen['sobran_en_erp'] ?? 0, '#7c2d12'],
                                    ['Diferencias', $resumen['diferencias'] ?? 0, '#b45309'],
                                    ['Sin planificación', $resumen['ignorados_erp'] ?? 0, '#1d4ed8'],
                                ] as [$titulo, $valor, $color])
                                    <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                        <div class="theme-text text-[11px] font-black uppercase tracking-[.12em]">{{ $titulo }}</div>
                                        <div class="text-2xl font-black tabular-nums" style="color: {{ $color }}">{{ number_format($valor) }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Documentos --}}
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow">
                                <div class="px-3 py-2 border-b theme-border flex items-center justify-between">
                                    <h2 class="theme-title text-sm font-black">Documentos del día</h2>
                                    <input type="text" id="filtroDocs" placeholder="Filtrar..."
                                           oninput="filtrarTabla('tablaDocs', this.value)"
                                           class="rounded-lg border theme-border bg-white px-2 py-1 text-xs theme-title w-40">
                                    <span class="theme-text text-xs">ERP: {{ $resumen['documentos_erp'] ?? 0 }} | Sistema: {{ $resumen['documentos_sistema'] ?? 0 }}</span>
                                </div>
                                <div class="overflow-x-auto max-h-72">
                                    <table id="tablaDocs" class="min-w-full text-xs">
                                        <thead class="theme-title sticky top-0">
                                            <tr class="bg-[#e8ecf1]">
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Documento</th>
                                                <th class="px-2 py-1.5 text-center" colspan="3" style="background:#dbeafe">SISTEMA</th>
                                                <th class="px-2 py-1.5 text-center" colspan="3" style="background:#dcfce7">ERP</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Dónde está</th>
                                            </tr>
                                            <tr>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Viñetas</th>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Puros</th>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Horas</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Filas</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Puros</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Horas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($documentos as $d)
                                                @php
                                                    $difPurosDoc = (int) $d['sistema_puros'] !== (int) ($d['erp_puros'] ?? 0);
                                                    $difHorasDoc = abs((float) $d['sistema_horas'] - (float) ($d['erp_horas'] ?? 0)) > 0.01;
                                                    $celdaDifDoc = 'background:#fecaca;color:#991b1b;font-weight:700;';
                                                @endphp
                                                <tr class="border-t theme-border {{ $d['origen'] === 'solo_sistema' ? 'bg-red-50' : ($d['origen'] === 'solo_erp' ? 'bg-orange-50' : '') }}">
                                                    <td class="px-2 py-1 font-semibold">{{ $d['numero'] }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="background:#eff6ff" data-col="1" data-v="{{ (int) $d['sistema_vinetas'] }}">{{ number_format($d['sistema_vinetas']) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPurosDoc ? $celdaDifDoc : 'background:#eff6ff' }}" data-col="2" data-v="{{ (int) $d['sistema_puros'] }}">{{ number_format($d['sistema_puros']) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difHorasDoc ? $celdaDifDoc : 'background:#eff6ff' }}" data-col="3" data-fmt="dec" data-v="{{ (float) $d['sistema_horas'] }}">{{ number_format($d['sistema_horas'], 2) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="background:#f0fdf4" data-col="4" data-v="{{ (int) ($d['erp_vinetas'] ?? 0) }}">{{ number_format($d['erp_vinetas'] ?? 0) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPurosDoc ? $celdaDifDoc : 'background:#f0fdf4' }}" data-col="5" data-v="{{ (int) ($d['erp_puros'] ?? 0) }}">{{ number_format($d['erp_puros'] ?? 0) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difHorasDoc ? $celdaDifDoc : 'background:#f0fdf4' }}" data-col="6" data-fmt="dec" data-v="{{ (float) ($d['erp_horas'] ?? 0) }}">{{ number_format($d['erp_horas'] ?? 0, 2) }}</td>
                                                    <td class="px-2 py-1 text-[11px]">
                                                        @if($d['origen'] === 'ambos') En los dos
                                                        @elseif($d['origen'] === 'solo_sistema') Solo en el sistema (falta en ERP)
                                                        @else Solo en el ERP
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="px-2 py-3 text-center theme-text">Sin documentos.</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-[#0f172a] text-white font-black">
                                                <td class="px-2 py-1.5 text-right">TOTAL GENERAL</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="1">{{ number_format(collect($documentos)->sum('sistema_vinetas')) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="2">{{ number_format(collect($documentos)->sum('sistema_puros')) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="3" data-fmt="dec">{{ number_format(collect($documentos)->sum('sistema_horas'), 2) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="4">{{ number_format(collect($documentos)->sum('erp_vinetas')) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="5">{{ number_format(collect($documentos)->sum('erp_puros')) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="6" data-fmt="dec">{{ number_format(collect($documentos)->sum('erp_horas'), 2) }}</td>
                                                <td class="px-2 py-1.5 text-[11px]"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            {{-- Detalle --}}
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow">
                                <div class="px-3 py-2 border-b theme-border">
                                    <h2 class="theme-title text-sm font-black">Detalle por documento + actividad + orden + empleado + producto</h2>
                                    <input type="text" id="filtroDetalle" placeholder="Filtrar..."
                                           oninput="filtrarTabla('tablaDetalle', this.value)"
                                           class="mt-1 rounded-lg border theme-border bg-white px-2 py-1 text-xs theme-title w-52">
                                    <p class="theme-text text-[11px]">
                                        <span class="px-1 rounded" style="background:#dbeafe">Azul = sistema</span>
                                        <span class="px-1 rounded" style="background:#dcfce7">Verde = ERP</span>
                                        <span class="px-1 rounded" style="background:#fecaca">Rojo = valor distinto</span>
                                        · Rojo/fila = falta en el ERP · Naranja/fila = sobra en el ERP
                                    </p>
                                </div>
                                <div class="overflow-x-auto max-h-[520px]">
                                    <table id="tablaDetalle" class="min-w-full text-xs">
                                        <thead class="theme-title sticky top-0">
                                            <tr class="bg-[#e8ecf1]">
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Doc</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Act</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Orden</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Empleado</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Producto</th>
                                                <th class="px-2 py-1.5 text-center" colspan="3" style="background:#dbeafe">SISTEMA</th>
                                                <th class="px-2 py-1.5 text-center" colspan="3" style="background:#dcfce7">ERP</th>
                                                <th class="px-2 py-1.5 text-left" rowspan="2">Estado</th>
                                            </tr>
                                            <tr>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Puros</th>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Horas</th>
                                                <th class="px-2 py-1 text-right" style="background:#dbeafe">Precio</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Puros</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Horas</th>
                                                <th class="px-2 py-1 text-right" style="background:#dcfce7">Precio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $gruposDetalle = collect($detalle)->groupBy('numero'); @endphp
                                            @forelse($gruposDetalle as $numeroDoc => $filasDoc)
                                                @foreach($filasDoc as $d)
                                                @php
                                                    $difPuros = (int) $d['sistema_puros'] !== (int) $d['erp_puros'];
                                                    $difHoras = abs((float) $d['sistema_horas'] - (float) $d['erp_horas']) > 0.01;
                                                    $difPrecio = $d['sistema_precio'] !== null
                                                        && $d['erp_precio'] !== null
                                                        && abs((float) $d['sistema_precio'] - (float) $d['erp_precio']) > 0.0001;
                                                    $celdaDif = 'background:#fecaca;color:#991b1b;font-weight:700;';
                                                @endphp
                                                <tr class="border-t theme-border {{ $d['estado'] === 'solo_sistema' ? 'bg-red-50' : ($d['estado'] === 'solo_erp' ? 'bg-orange-50' : '') }}">
                                                    <td class="px-2 py-1">{{ $d['numero'] }}</td>
                                                    <td class="px-2 py-1">{{ $d['actividad'] }}</td>
                                                    <td class="px-2 py-1">{{ $d['orden'] }}</td>
                                                    <td class="px-2 py-1">{{ $d['empleado'] }} {{ \Illuminate\Support\Str::limit((string) $d['nombre_empleado'], 22) }}</td>
                                                    <td class="px-2 py-1">{{ $d['producto'] }}</td>

                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPuros ? $celdaDif : 'background:#eff6ff' }}" data-col="5" data-v="{{ (int) $d['sistema_puros'] }}">{{ number_format($d['sistema_puros']) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difHoras ? $celdaDif : 'background:#eff6ff' }}" data-col="6" data-fmt="dec" data-v="{{ (float) $d['sistema_horas'] }}">{{ number_format($d['sistema_horas'], 2) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPrecio ? $celdaDif : 'background:#eff6ff' }}" data-col="7" data-fmt="dec" data-v="{{ (float) ($d['sistema_precio'] ?? 0) }}">{{ $d['sistema_precio'] !== null ? number_format($d['sistema_precio'], 4) : '—' }}</td>

                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPuros ? $celdaDif : 'background:#f0fdf4' }}" data-col="8" data-v="{{ (int) $d['erp_puros'] }}">{{ number_format($d['erp_puros']) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difHoras ? $celdaDif : 'background:#f0fdf4' }}" data-col="9" data-fmt="dec" data-v="{{ (float) $d['erp_horas'] }}">{{ number_format($d['erp_horas'], 2) }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" style="{{ $difPrecio ? $celdaDif : 'background:#f0fdf4' }}" data-col="10" data-fmt="dec" data-v="{{ (float) ($d['erp_precio'] ?? 0) }}">{{ $d['erp_precio'] !== null ? number_format($d['erp_precio'], 4) : '—' }}</td>

                                                    <td class="px-2 py-1 text-[11px]">
                                                        @if($d['estado'] === 'solo_sistema') Falta en ERP
                                                        @elseif($d['estado'] === 'solo_erp') Sobra en ERP
                                                        @elseif($d['estado'] === 'diferencia') Diferencia
                                                        @else OK
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach

                                            @php
                                                $totPurosSis = $filasDoc->sum('sistema_puros');
                                                $totHorasSis = $filasDoc->sum('sistema_horas');
                                                $totPurosErp = $filasDoc->sum('erp_puros');
                                                $totHorasErp = $filasDoc->sum('erp_horas');
                                                $difTotPuros = (int) $totPurosSis !== (int) $totPurosErp;
                                                $difTotHoras = abs((float) $totHorasSis - (float) $totHorasErp) > 0.01;
                                            @endphp
                                            <tr class="bg-[#e8ecf1] font-black border-t-2 border-slate-400" data-no-sum="1">
                                                <td colspan="5" class="px-2 py-1.5 text-right">TOTAL DOC {{ $numeroDoc }} ({{ $filasDoc->count() }} filas)</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" style="{{ $difTotPuros ? 'background:#fecaca;color:#991b1b' : '' }}">{{ number_format($totPurosSis) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" style="{{ $difTotHoras ? 'background:#fecaca;color:#991b1b' : '' }}">{{ number_format($totHorasSis, 2) }}</td>
                                                <td class="px-2 py-1.5"></td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" style="{{ $difTotPuros ? 'background:#fecaca;color:#991b1b' : '' }}">{{ number_format($totPurosErp) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" style="{{ $difTotHoras ? 'background:#fecaca;color:#991b1b' : '' }}">{{ number_format($totHorasErp, 2) }}</td>
                                                <td class="px-2 py-1.5"></td>
                                                <td class="px-2 py-1.5 text-[11px]">
                                                    @if($difTotPuros || $difTotHoras) Diferencia @else OK @endif
                                                </td>
                                            </tr>
                                            @empty
                                                <tr><td colspan="12" class="px-2 py-3 text-center theme-text">Sin filas.</td></tr>
                                            @endforelse

                                            @if($gruposDetalle->isNotEmpty())
                                                @php
                                                    $gPurosSis = collect($detalle)->sum('sistema_puros');
                                                    $gHorasSis = collect($detalle)->sum('sistema_horas');
                                                    $gPurosErp = collect($detalle)->sum('erp_puros');
                                                    $gHorasErp = collect($detalle)->sum('erp_horas');
                                                @endphp
                                                <tr class="bg-[#0f172a] text-white font-black" data-total-row="1">
                                                    <td colspan="5" class="px-2 py-1.5 text-right">TOTAL GENERAL ({{ count($detalle) }} filas)</td>
                                                    <td class="px-2 py-1.5 text-right tabular-nums" data-total="5">{{ number_format($gPurosSis) }}</td>
                                                    <td class="px-2 py-1.5 text-right tabular-nums" data-total="6" data-fmt="dec">{{ number_format($gHorasSis, 2) }}</td>
                                                    <td class="px-2 py-1.5"></td>
                                                    <td class="px-2 py-1.5 text-right tabular-nums" data-total="8">{{ number_format($gPurosErp) }}</td>
                                                    <td class="px-2 py-1.5 text-right tabular-nums" data-total="9" data-fmt="dec">{{ number_format($gHorasErp, 2) }}</td>
                                                    <td class="px-2 py-1.5"></td>
                                                    <td class="px-2 py-1.5 text-[11px]"></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Planificacion --}}
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow">
                                <div class="px-3 py-2 border-b theme-border">
                                    <h2 class="theme-title text-sm font-black">Detalle del ERP vs planificación del día</h2>
                                    <input type="text" id="filtroPlan" placeholder="Filtrar..."
                                           oninput="filtrarTabla('tablaPlan', this.value)"
                                           class="mt-1 rounded-lg border theme-border bg-white px-2 py-1 text-xs theme-title w-52">
                                    <p class="theme-text text-[11px]">Azul = esa actividad/orden/línea no está planificada para ese día</p>
                                </div>
                                <div class="overflow-x-auto max-h-96">
                                    <table id="tablaPlan" class="min-w-full text-xs">
                                        <thead class="bg-[#e8ecf1] theme-title sticky top-0">
                                            <tr>
                                                <th class="px-2 py-1.5 text-left">Doc</th>
                                                <th class="px-2 py-1.5 text-left">Actividad</th>
                                                <th class="px-2 py-1.5 text-left">Orden</th>
                                                <th class="px-2 py-1.5 text-right">Línea</th>
                                                <th class="px-2 py-1.5 text-right">Puros</th>
                                                <th class="px-2 py-1.5 text-left">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($planificacion as $p)
                                                <tr class="border-t theme-border {{ $p['estado'] === 'sin_planificacion' ? 'bg-blue-50' : '' }}">
                                                    <td class="px-2 py-1">{{ $p['numero'] }}</td>
                                                    <td class="px-2 py-1">{{ $p['actividad'] }}</td>
                                                    <td class="px-2 py-1">{{ $p['orden'] }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" data-col="3" data-v="{{ (int) $p['linea'] }}">{{ $p['linea'] }}</td>
                                                    <td class="px-2 py-1 text-right tabular-nums" data-col="4" data-v="{{ (int) $p['puros'] }}">{{ number_format($p['puros']) }}</td>
                                                    <td class="px-2 py-1 text-[11px]">
                                                        {{ $p['estado'] === 'sin_planificacion' ? 'Sin planificación ese día' : 'En planificación' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="px-2 py-3 text-center theme-text">Sin filas.</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-[#0f172a] text-white font-black">
                                                <td colspan="3" class="px-2 py-1.5 text-right">TOTAL GENERAL</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="3">{{ number_format(collect($planificacion)->sum('linea')) }}</td>
                                                <td class="px-2 py-1.5 text-right tabular-nums" data-total="4">{{ number_format(collect($planificacion)->sum('puros')) }}</td>
                                                <td class="px-2 py-1.5"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        function fmtNum(v, dec) {
            return String(dec ? v.toFixed(2) : Math.round(v)).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function sumarTabla(tablaId) {
            const tabla = document.getElementById(tablaId);
            if (!tabla) return;

            const totales = {};

            tabla.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.dataset.noSum === '1' || tr.dataset.totalRow === '1') return;
                if (tr.style.display === 'none') return;

                tr.querySelectorAll('[data-v]').forEach(function (td) {
                    const c = td.dataset.col;
                    totales[c] = (totales[c] || 0) + (parseFloat(td.dataset.v) || 0);
                });
            });

            tabla.querySelectorAll('tfoot [data-total]').forEach(function (td) {
                const c = td.dataset.total;
                td.textContent = fmtNum(totales[c] || 0, td.dataset.fmt === 'dec');
            });

            const filasVisibles = Array.from(tabla.querySelectorAll('tbody tr'))
                .filter(function (tr) { return tr.style.display !== 'none' && tr.dataset.noSum !== '1' && tr.dataset.totalRow !== '1'; });

            tabla.querySelectorAll('tbody [data-total]').forEach(function (td) {
                const c = td.dataset.total;
                td.textContent = fmtNum(totales[c] || 0, td.dataset.fmt === 'dec');
            });

            const etiqueta = tabla.querySelector('tbody [data-total-row="1"] td');
            if (etiqueta && etiqueta.textContent.indexOf('TOTAL GENERAL') === 0) {
                etiqueta.textContent = 'TOTAL GENERAL (' + filasVisibles.length + ' filas)';
            }
        }

        function filtrarTabla(tablaId, valor) {
            const tabla = document.getElementById(tablaId);
            if (!tabla) return;

            const q = (valor || '').toLowerCase().trim();

            tabla.querySelectorAll('tbody tr').forEach(function (tr) {
                if (tr.dataset.totalRow === '1') return;
                tr.style.display = (q === '' || tr.innerText.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
            });

            sumarTabla(tablaId);
        }

        document.addEventListener('DOMContentLoaded', function () {
            sumarTabla('tablaDocs');
            sumarTabla('tablaDetalle');
            sumarTabla('tablaPlan');
        });
    </script>
    @include('layouts.flash')
</body>
</html>
