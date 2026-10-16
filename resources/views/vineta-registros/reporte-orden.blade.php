<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte por orden | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $total = $totales ?: ['puros_programados' => 0, 'rezago' => 0, 'anillado' => 0, 'llenado' => 0, 'avance' => 0, 'cajas' => 0, 'programaciones' => 0];

    $avanceColor = function ($avance) {
        if ($avance >= 100) {
            return 'text-[#047857]';
        }

        return $avance > 0 ? 'text-[#b45309]' : 'text-[#64748b]';
    };
@endphp

<body class="vinetas-page min-h-screen theme-bg antialiased">
    <div
        x-data="{
            sidebarOpen: false,
            catalogos: false,
            seguridad: false,
            produccion: true
        }"
        class="min-h-screen flex theme-bg">

        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('layouts.topbar')

            <main class="flex-1 min-w-0">
                <section class="app-content-compact">
                    <div class="w-full max-w-none space-y-3">

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <div class="min-w-0">
                                    <h1 class="theme-title text-lg sm:text-xl font-black">Reporte por orden</h1>
                                    <p class="theme-text text-xs sm:text-sm">
                                        Programado (facturación Plasencia) contra lo hecho en empaque.
                                        <span class="font-bold">{{ $periodoLabel }}</span>
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="GET" action="{{ route('vineta-registros.reporte-orden') }}" class="flex flex-wrap items-end gap-1.5">
                                        <div>
                                            <label for="anio" class="theme-text block text-[11px] font-semibold mb-1">Año</label>
                                            <select id="anio" name="anio"
                                                    class="rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                                                @foreach($anios as $anioOpcion)
                                                    <option value="{{ $anioOpcion }}" @selected($anioOpcion === $anio)>{{ $anioOpcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="mes" class="theme-text block text-[11px] font-semibold mb-1">Desde el mes</label>
                                            <select id="mes" name="mes"
                                                    class="rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                                                <option value="">Todo el año</option>
                                                @foreach($meses as $indice => $nombre)
                                                    <option value="{{ $indice + 1 }}" @selected(($indice + 1) === $mes)>{{ $nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-xl bg-[#0f172a] px-4 py-1.5 text-xs sm:text-sm font-semibold text-white transition hover:bg-[#1e293b]">
                                            Filtrar
                                        </button>

                                        <a href="{{ route('vineta-registros.reporte-orden.export', ['anio' => $anio, 'mes' => $mes]) }}"
                                           class="inline-flex items-center justify-center rounded-xl border theme-border bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-[#4f46e5] transition hover:bg-[#eef2ff]">
                                            Exportar Excel
                                        </a>

                                        <a href="{{ route('vineta-registros.index') }}"
                                           class="inline-flex items-center justify-center rounded-xl border theme-border bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-[#0b1220] transition hover:bg-[#f1f5f9]">
                                            Volver
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if($error)
                            <div class="theme-card rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-4">
                                <p class="text-sm font-black text-[#b91c1c]">No se pudo consultar la API de programaciones</p>
                                <p class="text-xs text-[#7f1d1d] mt-1">{{ $error }}</p>
                                <p class="text-xs text-[#7f1d1d] mt-2">
                                    Revisá <span class="font-mono">PROGRAMACIONES_API_URL</span> en el .env de Empaque.
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Órdenes</p>
                                <p class="theme-title text-xl font-black">{{ number_format($ordenes) }}</p>
                                <p class="theme-text text-[11px]">{{ number_format($items) }} items</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Puros programados</p>
                                <p class="theme-title text-xl font-black">{{ number_format($total['puros_programados']) }}</p>
                                <p class="theme-text text-[11px]">{{ number_format($total['cajas']) }} cajas</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Rezagado</p>
                                <p class="theme-title text-xl font-black">{{ number_format($total['rezago']) }}</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Anillado</p>
                                <p class="theme-title text-xl font-black">{{ number_format($total['anillado']) }}</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Llenado / Avance</p>
                                <p class="theme-title text-xl font-black">{{ number_format($total['llenado']) }}</p>
                                <p class="text-[11px] font-black {{ $avanceColor($total['avance']) }}">{{ $total['avance'] }}%</p>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-2">
                            <div class="table-responsive" style="overflow:auto;max-height:calc(100vh - 320px);border-radius:24px;border:1px solid #dee2e6;box-shadow:0 4px 12px rgba(0,0,0,0.05);padding:12px;background:white;">
                                <table class="vinetas-table w-full text-sm">
                                    <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]"
                                           style="position:sticky;top:0;z-index:2;">
                                        <tr>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Orden sis.</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Orden cliente</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Item</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Cód. producto</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Marca</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Nombre</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Vitola</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Capa</th>
                                            <th class="text-left px-2 py-2 whitespace-nowrap">Tipo empaque</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Prog.</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Cajas</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Puros prog.</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Rezagado</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Anillado</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Llenado</th>
                                            <th class="text-right px-2 py-2 whitespace-nowrap">Avance</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y theme-divide">
                                        @forelse($grupos as $grupo)
                                            @foreach($grupo['clientes'] as $cliente)
                                                @foreach($cliente['items'] as $item)
                                                    <tr class="theme-row">
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $grupo['orden_sistema'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $cliente['orden_cliente'] }}</td>
                                                        <td class="px-2 py-1.5 font-mono text-xs whitespace-nowrap">{{ $item['item'] }}</td>
                                                        <td class="px-2 py-1.5 font-mono text-xs whitespace-nowrap">{{ $item['codigo_producto'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $item['marca'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $item['nombre'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $item['vitola'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $item['capa'] }}</td>
                                                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $item['tipo_empaque'] }}</td>
                                                        <td class="px-2 py-1.5 text-right">{{ number_format($item['programaciones']) }}</td>
                                                        <td class="px-2 py-1.5 text-right">{{ number_format($item['cajas']) }}</td>
                                                        <td class="px-2 py-1.5 text-right font-semibold">{{ number_format($item['puros_programados']) }}</td>
                                                        <td class="px-2 py-1.5 text-right">{{ number_format($item['rezago']) }}</td>
                                                        <td class="px-2 py-1.5 text-right">{{ number_format($item['anillado']) }}</td>
                                                        <td class="px-2 py-1.5 text-right">{{ number_format($item['llenado']) }}</td>
                                                        <td class="px-2 py-1.5 text-right font-black {{ $avanceColor($item['avance']) }}">{{ $item['avance'] }}%</td>
                                                    </tr>
                                                @endforeach

                                                <tr class="bg-[#f8fafc] font-bold">
                                                    <td class="px-2 py-1.5 whitespace-nowrap" colspan="9">
                                                        Total orden {{ $grupo['orden_sistema'] }} / {{ $cliente['orden_cliente'] }}
                                                    </td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['programaciones']) }}</td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['cajas']) }}</td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['puros_programados']) }}</td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['rezago']) }}</td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['anillado']) }}</td>
                                                    <td class="px-2 py-1.5 text-right">{{ number_format($cliente['totales']['llenado']) }}</td>
                                                    <td class="px-2 py-1.5 text-right font-black {{ $avanceColor($cliente['totales']['avance']) }}">{{ $cliente['totales']['avance'] }}%</td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="16" class="px-2 py-6 text-center theme-text">
                                                    No hay programaciones en este periodo.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>

                                    @if(! empty($grupos))
                                        <tfoot style="position:sticky;bottom:0;z-index:1;background:#eff6ff;font-weight:bold">
                                            <tr>
                                                <td class="px-2 py-2" colspan="9">TOTAL GENERAL ({{ $ordenes }} órdenes)</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['programaciones']) }}</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['cajas']) }}</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['puros_programados']) }}</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['rezago']) }}</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['anillado']) }}</td>
                                                <td class="px-2 py-2 text-right">{{ number_format($total['llenado']) }}</td>
                                                <td class="px-2 py-2 text-right font-black {{ $avanceColor($total['avance']) }}">{{ $total['avance'] }}%</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
