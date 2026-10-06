<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas registradas | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $hasHorasOrdinarias = $hasHorasOrdinarias ?? false;
    $today = now('America/Tegucigalpa');
    $defaultExportStart = request('fecha_desde', $today->copy()->startOfWeek()->toDateString());
    $defaultExportEnd = request('fecha_hasta', $today->toDateString());
    $editDefaultSubtitle = ($hasMinutosTrabajados ?? false)
        ? 'Actualiza fecha, hora, cantidad, tiempo y empleado.'
        : 'Actualiza fecha, hora, cantidad y empleado.';
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

                        <div id="vinetaRegistrosSummaryContainer">
                            @include('vineta-registros.partials.resumen')
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vineta-registros.index') }}"
                                  class="vineta-registros-filter-form vineta-registros-ajax-filter-form">

                                <div class="vineta-registros-filter-row flex flex-nowrap items-end gap-1 sm:gap-1.5 w-full min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="ID viñeta">ID viñeta</label>
                                        <input type="text"
                                               name="id_vineta"
                                               value="{{ request('id_vineta') }}"
                                               placeholder="ID..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Item">Item</label>
                                        <input type="text"
                                               name="item"
                                               value="{{ request('item') }}"
                                               placeholder="Item..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Orden sistema">Orden sist.</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Sistema..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Orden cliente">Orden clie.</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente') }}"
                                               placeholder="Cliente..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Código producto">Código prod.</label>
                                        <input type="text"
                                               name="codigo_producto"
                                               value="{{ request('codigo_producto') }}"
                                               placeholder="Código..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Empleado">Empleado</label>
                                        <input type="text"
                                               name="empleado"
                                               value="{{ request('empleado') }}"
                                               placeholder="Empleado..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Capa: filtro después de Empleado --}}
                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Capa">Capa</label>
                                        <input type="text"
                                               name="capa"
                                               value="{{ request('capa') }}"
                                               placeholder="Capa..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Fecha Inicio">Desde</label>
                                        <input type="date"
                                               name="fecha_desde"
                                               id="filtroFechaDesde"
                                               value="{{ request('fecha_desde') }}"
                                               class="w-full rounded-xl border theme-border bg-white px-1 sm:px-1.5 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Fecha Fin">Hasta</label>
                                        <input type="date"
                                               name="fecha_hasta"
                                               id="filtroFechaHasta"
                                               value="{{ request('fecha_hasta') }}"
                                               class="w-full rounded-xl border theme-border bg-white px-1 sm:px-1.5 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-[1.25_1_0%]">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate" title="Documento ERP">Documento</label>
                                        <select name="documento"
                                                id="filtroDocumento"
                                                @disabled(!request('fecha_desde') && !request('fecha_hasta'))
                                                class="w-full rounded-xl border theme-border bg-white px-1.5 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition disabled:opacity-50 disabled:cursor-not-allowed">
                                            @if(!request('fecha_desde') && !request('fecha_hasta'))
                                                <option value="">Seleccione fecha primero</option>
                                            @else
                                                <option value="" @selected(($documentoSeleccionado ?? '') === '')>Todos los documentos</option>
                                                <option value="sin_documento" @selected(($documentoSeleccionado ?? '') === 'sin_documento')>Sin documento</option>
                                                @foreach(($documentosDisponibles ?? collect()) as $doc)
                                                    <option value="{{ $doc->numero }}" @selected(($documentoSeleccionado ?? '') === (string)$doc->numero)>
                                                        Doc. #{{ $doc->numero }}{{ $doc->descripcion ? ' - ' . \Illuminate\Support\Str::limit($doc->descripcion, 24) : '' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <div class="vineta-registros-filter-actions flex items-center justify-end gap-2 mt-2.5 pt-0.5">
                                    <a href="{{ route('vineta-registros.index') }}"
                                       class="vineta-registros-ajax-clear gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-white text-[#0b1220] text-xs sm:text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition whitespace-nowrap">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-4 py-1.5 rounded-xl bg-[#0f172a] text-white text-xs sm:text-sm font-semibold hover:bg-[#1e293b] transition whitespace-nowrap">
                                        Filtrar
                                    </button>

                                    <button type="button"
                                            id="vinetaRegistrosExportOpen"
                                            class="gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-white text-[#5b3a1e] text-xs sm:text-sm font-semibold border theme-border hover:bg-[#f3efe7] transition whitespace-nowrap">
                                        Exportar Excel
                                    </button>

                                    <button type="button"
                                            id="vinetaRegistrosWeeklyReportOpen"
                                            class="gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-white text-[#0f766e] text-xs sm:text-sm font-semibold border theme-border hover:bg-[#ecfdf5] transition whitespace-nowrap">
                                        Reporte semanal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetaRegistrosTableContainer">
                                @include('vineta-registros.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetaRegistrosExportModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-lg overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">Exportar Excel</p>
                    <h2 class="theme-title mt-1 text-xl font-black">Selecciona el periodo</h2>
                    <p class="theme-text mt-1 text-sm">La primera hoja mostrará subtotales por empleado y producto; el detalle original se conservará en otra hoja.</p>
                </div>
                <button type="button"
                        data-vineta-registros-modal-close="vinetaRegistrosExportModal"
                        class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]"
                        aria-label="Cerrar exportación">×</button>
            </div>

            <form method="GET" action="{{ route('vineta-registros.export') }}" class="px-5 py-5">
                @foreach(request()->except(['page', 'fecha_desde', 'fecha_hasta']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="export_fecha_desde" class="theme-text mb-1 block text-xs font-bold">Desde</label>
                        <input id="export_fecha_desde"
                               type="date"
                               name="fecha_desde"
                               value="{{ $defaultExportStart }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                    <div>
                        <label for="export_fecha_hasta" class="theme-text mb-1 block text-xs font-bold">Hasta</label>
                        <input id="export_fecha_hasta"
                               type="date"
                               name="fecha_hasta"
                               value="{{ $defaultExportEnd }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            data-vineta-registros-modal-close="vinetaRegistrosExportModal"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">Cancelar</button>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#5b3a1e] px-5 py-3 text-sm font-black text-white transition hover:bg-[#3b2818]">Descargar Excel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="vinetaRegistrosWeeklyReportModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-lg overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">Reporte semanal</p>
                    <h2 class="theme-title mt-1 text-xl font-black">Selecciona el periodo</h2>
                    <p class="theme-text mt-1 text-sm">El reporte incluirá únicamente los registros dentro de estas fechas.</p>
                </div>
                <button type="button"
                        data-vineta-registros-modal-close="vinetaRegistrosWeeklyReportModal"
                        class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]"
                        aria-label="Cerrar reporte semanal">×</button>
            </div>

            <form method="GET" action="{{ route('vineta-registros.reporte-semanal') }}" class="px-5 py-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="weekly_fecha_desde" class="theme-text mb-1 block text-xs font-bold">Desde</label>
                        <input id="weekly_fecha_desde"
                               type="date"
                               name="fecha_desde"
                               value="{{ $defaultExportStart }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                    <div>
                        <label for="weekly_fecha_hasta" class="theme-text mb-1 block text-xs font-bold">Hasta</label>
                        <input id="weekly_fecha_hasta"
                               type="date"
                               name="fecha_hasta"
                               value="{{ $defaultExportEnd }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            data-vineta-registros-modal-close="vinetaRegistrosWeeklyReportModal"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">Cancelar</button>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#5b3a1e] px-5 py-3 text-sm font-black text-white transition hover:bg-[#3b2818]">Generar reporte</button>
                </div>
            </form>
        </div>
    </div>

    <div id="seguimientoVinetaModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card flex max-h-[94vh] w-[92vw] max-w-6xl flex-col overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="border-b theme-border px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="theme-text text-xs font-black uppercase tracking-wide">
                            Seguimiento del cajón
                        </p>

                        <h2 id="seguimientoVinetaTitle" class="theme-title mt-1 text-xl font-black">
                            Viñeta
                        </h2>

                        <div id="seguimientoVinetaSubtitle" class="vineta-modal-info mt-3">
                            Actividades realizadas a esta viñeta.
                        </div>
                    </div>

                    <button type="button"
                            id="seguimientoVinetaClose"
                            class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                        ×
                    </button>
                </div>

                <div class="mt-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="grid grid-cols-3 gap-2 flex-1">
                        <div class="theme-badge rounded-2xl border px-3 py-2">
                            <p class="theme-text text-[11px] font-semibold">Movimientos</p>
                            <p id="seguimientoVinetaMovimientos" class="theme-title text-lg font-black">0</p>
                        </div>

                        <div class="theme-badge rounded-2xl border px-3 py-2">
                            <p class="theme-text text-[11px] font-semibold">Activos</p>
                            <p id="seguimientoVinetaActivos" class="theme-title text-lg font-black">0</p>
                        </div>

                        <div class="theme-badge rounded-2xl border px-3 py-2">
                            <p class="theme-text text-[11px] font-semibold">Puros cajón</p>
                            <p id="seguimientoVinetaPuros" class="theme-title text-lg font-black">0</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-start md:self-center shrink-0">
                        <span class="theme-text text-xs font-black uppercase tracking-wider">Vista:</span>
                        <div class="vineta-view-mode-container inline-flex rounded-2xl p-1 border theme-border">
                            <button type="button"
                                    class="vineta-view-mode-btn rounded-xl px-3 py-1.5 text-xs font-black transition flex items-center gap-1.5 active"
                                    data-mode="timeline"
                                    title="Línea de tiempo vertical detallada">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0-12a2 2 0 100-4 2 2 0 000 4zm0 12a2 2 0 100 4 2 2 0 000-4zM6 12h12" />
                                </svg>
                                Línea de tiempo
                            </button>
                            <button type="button"
                                    class="vineta-view-mode-btn rounded-xl px-3 py-1.5 text-xs font-bold transition flex items-center gap-1.5"
                                    data-mode="grid"
                                    title="Cuadrícula de tarjetas ordenadas">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                </svg>
                                Cuadrícula
                            </button>
                            <button type="button"
                                    class="vineta-view-mode-btn rounded-xl px-3 py-1.5 text-xs font-bold transition flex items-center gap-1.5"
                                    data-mode="horizontal"
                                    title="Carrusel horizontal deslizable">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                Horizontal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="seguimientoScrollArea" class="overflow-y-auto px-5 py-5 bg-white">
                <div id="seguimientoVinetaTimeline" class="space-y-0"></div>
            </div>
        </div>
    </div>

    <div id="editVinetaRegistroModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-xl rounded-3xl border theme-border bg-white shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">
                        Editar registro
                    </p>

                    <h2 id="editRegistroTitle" class="theme-title mt-1 text-xl font-black">
                        Viñeta registrada
                    </h2>

                     <p id="editRegistroSubtitle" class="theme-text mt-1 text-sm">
                         Actualiza fecha, hora, cantidad{{ $hasMinutosTrabajados ? ', tiempo' : '' }} y empleado.
                     </p>
                </div>

                <button type="button"
                        id="editVinetaRegistroClose"
                        class="theme-button-secondary inline-flex h-10 w-10 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                    ×
                </button>
            </div>

            <form id="editVinetaRegistroForm" method="POST" action="" class="px-5 py-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_fecha_registro" class="theme-text mb-1 block text-xs font-bold">
                            Fecha
                        </label>

                        <input id="edit_fecha_registro"
                               type="date"
                               name="fecha_registro"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_registro" class="theme-text mb-1 block text-xs font-bold">
                            Hora
                        </label>

                        <input id="edit_hora_registro"
                               type="time"
                               name="hora_registro"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_cantidad_puros" class="theme-text mb-1 block text-xs font-bold">
                            Cantidad puros
                        </label>

                        <input id="edit_cantidad_puros"
                               type="number"
                               name="cantidad_puros"
                               min="1"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    @if ($hasMinutosTrabajados)
                        <div id="edit_minutos_trabajados_group">
                            <label for="edit_minutos_trabajados" class="theme-text mb-1 block text-xs font-bold">
                                Minutos trabajados
                            </label>

                            <input id="edit_minutos_trabajados"
                                   type="number"
                                   name="minutos_trabajados"
                                   min="1"
                                   max="570"
                                   required
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">

                            <p class="theme-text mt-1 text-[11px] font-semibold">
                                Meta diaria: 570 min (9 h 30 min).
                            </p>
                        </div>
                    @endif

                    <div>
                        <label for="edit_empleado_codigo" class="theme-text mb-1 block text-xs font-bold">
                            Código empleado
                        </label>

                        <input id="edit_empleado_codigo"
                               type="text"
                               name="empleado_codigo"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="theme-soft mt-4 rounded-2xl border theme-border px-4 py-3">
                    <p class="theme-text text-xs font-bold">
                        Empleado seleccionado
                    </p>

                    <p id="editEmpleadoNombre" class="theme-title mt-1 font-black">
                        N/A
                    </p>

                    <p id="editEmpleadoEstado" class="theme-text mt-1 text-xs font-semibold">
                        Ingresa un código para validar el empleado.
                    </p>
                </div>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button"
                            id="editVinetaRegistroCancel"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#0f172a] px-5 py-3 text-sm font-black text-white transition hover:bg-[#1e293b]">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>



    <div id="editHoraOrdinariaModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-xl rounded-3xl border theme-border bg-white shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">
                        Editar hora ordinaria
                    </p>

                    <h2 id="editHoraOrdinariaTitle" class="theme-title mt-1 text-xl font-black">
                        Registro manual
                    </h2>

                    <p class="theme-text mt-1 text-sm">
                        Actualiza empleado, fecha, tiempo y observación.
                    </p>
                </div>

                <button type="button"
                        id="editHoraOrdinariaClose"
                        class="theme-button-secondary inline-flex h-10 w-10 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                    ×
                </button>
            </div>

            <form id="editHoraOrdinariaForm" method="POST" action="" class="px-5 py-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_hora_ord_empleado_codigo" class="theme-text mb-1 block text-xs font-bold">
                            Código empleado
                        </label>

                        <input id="edit_hora_ord_empleado_codigo"
                               type="text"
                               name="empleado_codigo"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_fecha" class="theme-text mb-1 block text-xs font-bold">
                            Fecha
                        </label>

                        <input id="edit_hora_ord_fecha"
                               type="date"
                               name="fecha"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_horas" class="theme-text mb-1 block text-xs font-bold">
                            Horas
                        </label>

                        <input id="edit_hora_ord_horas"
                               type="number"
                               name="horas"
                               min="0"
                               max="9"
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_minutos" class="theme-text mb-1 block text-xs font-bold">
                            Minutos
                        </label>

                        <input id="edit_hora_ord_minutos"
                               type="number"
                               name="minutos"
                               min="0"
                               max="59"
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="edit_hora_ord_observacion" class="theme-text mb-1 block text-xs font-bold">
                            Observación
                        </label>

                        <textarea id="edit_hora_ord_observacion"
                                  name="observacion"
                                  rows="4"
                                  required
                                  class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20"></textarea>
                    </div>
                </div>

                <p class="theme-text mt-3 text-[11px] font-semibold">
                    Tiempo máximo por registro: 570 min (9 h 30 min).
                </p>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button"
                            id="editHoraOrdinariaCancel"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#0f172a] px-5 py-3 text-sm font-black text-white transition hover:bg-[#1e293b]">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modals = {
                export: document.getElementById('vinetaRegistrosExportModal'),
                weekly: document.getElementById('vinetaRegistrosWeeklyReportModal'),
            };

            const openModal = (modal) => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const closeModal = (modal) => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            document.getElementById('vinetaRegistrosExportOpen')?.addEventListener('click', () => openModal(modals.export));
            document.getElementById('vinetaRegistrosWeeklyReportOpen')?.addEventListener('click', () => openModal(modals.weekly));

            document.querySelectorAll('[data-vineta-registros-modal-close]').forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(document.getElementById(button.dataset.vinetaRegistrosModalClose));
                });
            });

            Object.values(modals).forEach((modal) => {
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    Object.values(modals).forEach(closeModal);
                }
            });
        });
    </script>

    <div id="vinetaRegistrosTableLoader"
         class="productos-table-loader hidden"
         role="status"
         aria-live="polite">
        <div class="productos-table-loader-card theme-card theme-shadow">
            <div class="productos-table-loader-icon"><span></span></div>
            <div class="text-left">
                <p class="theme-title text-sm font-bold leading-tight">Actualizando tabla</p>
                <p class="theme-text text-xs leading-tight mt-0.5">Cargando registros...</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetaRegistrosTableContainer');
            const getSummaryContainer = () => document.getElementById('vinetaRegistrosSummaryContainer');
            const getFilterForm = () => document.querySelector('.vineta-registros-ajax-filter-form');
            const getTableScroll = () => document.querySelector('#vinetaRegistrosTableContainer .vineta-registros-table-scroll');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;
            let stickyHeaderClone = null;
            let floatingScroll = null;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;
            let windowEventsBound = false;

            const removeStickyHeaderClone = () => {
                stickyHeaderClone?.remove();
                stickyHeaderClone = null;
            };

            const syncStickyHeaderClone = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header || !stickyHeaderClone) {
                    return;
                }

                const tableRect = table.getBoundingClientRect();
                const headerRect = header.getBoundingClientRect();
                const scrollRect = scroll.getBoundingClientRect();
                const topbarBottom = getTopbarBottom();
                const shouldShow = headerRect.top < topbarBottom
                    && tableRect.bottom > topbarBottom + headerRect.height;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    return;
                }

                stickyHeaderClone.style.top = `${topbarBottom}px`;
                stickyHeaderClone.style.left = `${scrollRect.left}px`;
                stickyHeaderClone.style.width = `${scrollRect.width}px`;

                const cloneTable = stickyHeaderClone.querySelector('table');
                if (cloneTable) {
                    cloneTable.style.width = `${table.scrollWidth}px`;
                    cloneTable.style.minWidth = `${table.scrollWidth}px`;
                    cloneTable.style.transform = `translateX(${-scroll.scrollLeft}px)`;
                }

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];
                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;
                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                        th.style.maxWidth = `${width}px`;
                    }
                });
            };

            const syncFloatingScrollFromTable = () => {
                const scroll = getTableScroll();

                if (!scroll || !floatingScroll || syncingFloatingScroll) {
                    return;
                }

                syncingFloatingScroll = true;
                floatingScroll.scrollLeft = scroll.scrollLeft;
                syncingFloatingScroll = false;
            };

            const updateFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                floatingScroll = document.getElementById('vinetaRegistrosFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

                const rect = scroll.getBoundingClientRect();
                const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
                const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > getTopbarBottom();

                floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

                if (!hasHorizontalOverflow || !isTableVisible) {
                    return;
                }

                const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');

                if (inner) {
                    inner.style.width = `${table.scrollWidth}px`;
                }

                syncFloatingScrollFromTable();
            };

            const initTableFeatures = () => {
                removeStickyHeaderClone();

                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');
                floatingScroll = document.getElementById('vinetaRegistrosFloatingScroll');

                if (!scroll || !table || !header || !floatingScroll) {
                    return;
                }

                stickyHeaderClone = document.createElement('div');
                stickyHeaderClone.className = 'vinetas-sticky-header-clone';
                stickyHeaderClone.innerHTML = `
                    <div class="vinetas-sticky-header-inner">
                        <table class="w-full text-sm">${header.outerHTML}</table>
                    </div>
                `;

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];

                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;

                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                    }
                });

                document.body.appendChild(stickyHeaderClone);

                scroll.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    syncFloatingScrollFromTable();
                }, { passive: true });

                if (boundFloatingScroll !== floatingScroll) {
                    floatingScroll.addEventListener('scroll', () => {
                        const currentScroll = getTableScroll();

                        if (!currentScroll || syncingFloatingScroll) {
                            return;
                        }

                        syncingFloatingScroll = true;
                        currentScroll.scrollLeft = floatingScroll.scrollLeft;
                        syncStickyHeaderClone();
                        syncingFloatingScroll = false;
                    }, { passive: true });
                    boundFloatingScroll = floatingScroll;
                }

                if (!windowEventsBound) {
                    window.addEventListener('scroll', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    }, { passive: true });

                    window.addEventListener('resize', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    });

                    let sidebarAnimFrame = null;
                    const animateStickyHeader = () => {
                        const startTime = performance.now();
                        const duration = 350;
                        const step = (now) => {
                            syncStickyHeaderClone();
                            updateFloatingScroll();
                            if (now - startTime < duration) {
                                sidebarAnimFrame = requestAnimationFrame(step);
                            }
                        };
                        if (sidebarAnimFrame) cancelAnimationFrame(sidebarAnimFrame);
                        sidebarAnimFrame = requestAnimationFrame(step);
                    };

                    window.addEventListener('sidebar-toggled', animateStickyHeader);
                    window.addEventListener('open-mobile-sidebar', animateStickyHeader);
                    window.addEventListener('close-mobile-sidebar', animateStickyHeader);
                    windowEventsBound = true;
                }

                if (window.ResizeObserver) {
                    const ro = new ResizeObserver(() => {
                        requestAnimationFrame(() => {
                            syncStickyHeaderClone();
                            updateFloatingScroll();
                        });
                    });
                    const containerEl = getTableContainer();
                    if (containerEl) ro.observe(containerEl);
                    const mainEl = document.querySelector('main');
                    if (mainEl) ro.observe(mainEl);
                }

                requestAnimationFrame(() => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                });
            };

            const showTableLoader = () => {
                const loader = document.getElementById('vinetaRegistrosTableLoader');
                const header = document.querySelector('#vinetaRegistrosTableContainer .productos-sticky-head');

                if (loader && header) {
                    const rect = header.getBoundingClientRect();
                    loader.style.top = `${Math.max(rect.bottom + 12, getTopbarBottom() + 12)}px`;
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                getTableContainer()?.querySelector('#vinetaRegistrosTableInner')?.classList.add('productos-table-loading');
                getSummaryContainer()?.classList.add('productos-table-loading');
            };

            const hideTableLoader = () => {
                getTableContainer()?.querySelector('#vinetaRegistrosTableInner')?.classList.remove('productos-table-loading');
                getSummaryContainer()?.classList.remove('productos-table-loading');

                window.setTimeout(() => {
                    const loader = document.getElementById('vinetaRegistrosTableLoader');
                    loader?.classList.add('hidden');
                    loader?.classList.remove('flex');
                }, 160);
            };

            const loadTable = async (url, preserveHorizontalScroll = false) => {
                const container = getTableContainer();
                const summaryContainer = getSummaryContainer();

                if (!container || !summaryContainer) {
                    return;
                }

                const scrollLeft = preserveHorizontalScroll ? (getTableScroll()?.scrollLeft || 0) : 0;
                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo actualizar la tabla de viñetas registradas');
                    }

                    const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const summaryTemplate = responseDocument.getElementById('vinetaRegistrosSummaryResponse');
                    const tableTemplate = responseDocument.getElementById('vinetaRegistrosTableResponse');

                    if (!summaryTemplate || !tableTemplate) {
                        throw new Error('La respuesta no contiene el resumen y la tabla de viñetas registradas');
                    }

                    summaryContainer.innerHTML = summaryTemplate.innerHTML;
                    container.innerHTML = tableTemplate.innerHTML;
                    document.dispatchEvent(new CustomEvent('vineta-registros-table-updated'));
                    initTableFeatures();

                    if (preserveHorizontalScroll) {
                        requestAnimationFrame(() => {
                            const scroll = getTableScroll();

                            if (scroll) {
                                scroll.scrollLeft = Math.min(scrollLeft, Math.max(scroll.scrollWidth - scroll.clientWidth, 0));
                                syncStickyHeaderClone();
                                updateFloatingScroll();
                            }
                        });
                    }

                    window.history.pushState({}, '', url);
                } catch (error) {
                    console.error(error);
                    window.location.href = url;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a');

                if (!link) {
                    return;
                }

                const isSortLink = link.classList.contains('vineta-registros-ajax-table-link');
                const isPaginationLink = link.closest('#vinetaRegistrosTableContainer .vineta-registros-ajax-pagination');
                const isClearLink = link.classList.contains('vineta-registros-ajax-clear');

                if (!isSortLink && !isPaginationLink && !isClearLink) {
                    return;
                }

                event.preventDefault();

                if (isClearLink) {
                    getFilterForm()?.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                    const docSelect = document.getElementById('filtroDocumento');
                    if (docSelect) {
                        docSelect.innerHTML = '<option value="">Seleccione fecha primero</option>';
                        docSelect.disabled = true;
                    }
                }

                loadTable(link.href, isSortLink);
            });

            document.addEventListener('submit', (event) => {
                const formEliminar = event.target.closest('.form-eliminar-vineta-registro');
                if (formEliminar) {
                    event.preventDefault();
                    appSwal({
                        title: '¿Eliminar registro?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formEliminar.submit();
                        }
                    });
                    return;
                }

                const formEliminarHora = event.target.closest('.form-eliminar-hora-ordinaria');
                if (formEliminarHora) {
                    event.preventDefault();
                    appSwal({
                        title: '¿Eliminar hora ordinaria?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formEliminarHora.submit();
                        }
                    });
                    return;
                }

                const filterForm = event.target.closest('.vineta-registros-ajax-filter-form');
                const perPageForm = event.target.closest('.vineta-registros-ajax-per-page-form');

                if (!filterForm && !perPageForm) {
                    return;
                }

                event.preventDefault();

                const form = filterForm || perPageForm;
                const params = new URLSearchParams(new FormData(form));
                params.delete('page');
                loadTable(`${form.action}?${params.toString()}`);
            });

            const fechaDesdeInput = document.getElementById('filtroFechaDesde');
            const fechaHastaInput = document.getElementById('filtroFechaHasta');
            const documentoSelect = document.getElementById('filtroDocumento');

            const actualizarDocumentosPorFecha = async () => {
                const desde = fechaDesdeInput?.value || '';
                const hasta = fechaHastaInput?.value || '';

                if (!documentoSelect) return;

                if (!desde && !hasta) {
                    documentoSelect.innerHTML = '<option value="">Seleccione fecha primero</option>';
                    documentoSelect.disabled = true;
                    return;
                }

                documentoSelect.disabled = true;
                const currentVal = documentoSelect.value;
                documentoSelect.innerHTML = '<option value="">Cargando documentos...</option>';

                try {
                    const url = new URL(@json(route('vineta-registros.documentos')), window.location.origin);
                    if (desde) url.searchParams.set('fecha_desde', desde);
                    if (hasta) url.searchParams.set('fecha_hasta', hasta);

                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        const docs = await res.json();
                        let html = '<option value="">Todos los documentos</option><option value="sin_documento">Sin documento</option>';
                        docs.forEach(doc => {
                            const desc = doc.descripcion ? ' - ' + (doc.descripcion.length > 24 ? doc.descripcion.substring(0, 24) + '...' : doc.descripcion) : '';
                            const selected = (currentVal === String(doc.numero)) ? ' selected' : '';
                            html += `<option value="${doc.numero}"${selected}>Doc. #${doc.numero}${desc}</option>`;
                        });
                        documentoSelect.innerHTML = html;
                        documentoSelect.disabled = false;
                    }
                } catch (e) {
                    console.error('Error cargando documentos por fecha:', e);
                    documentoSelect.disabled = false;
                }
            };

            fechaDesdeInput?.addEventListener('change', actualizarDocumentosPorFecha);
            fechaHastaInput?.addEventListener('change', actualizarDocumentosPorFecha);

            window.addEventListener('popstate', () => loadTable(window.location.href));
            initTableFeatures();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('seguimientoVinetaModal');
            const title = document.getElementById('seguimientoVinetaTitle');
            const subtitle = document.getElementById('seguimientoVinetaSubtitle');
            const movimientos = document.getElementById('seguimientoVinetaMovimientos');
            const activos = document.getElementById('seguimientoVinetaActivos');
            const puros = document.getElementById('seguimientoVinetaPuros');
            const timelineContainer = document.getElementById('seguimientoVinetaTimeline');
            let timelines = {};
            let summaries = {};
            const numberFormat = new Intl.NumberFormat('es-HN');
            const seguimientoUrlTemplate = @json(route('vineta-registros.seguimiento', ['vineta' => '__VINETA_ID__']));

            const refreshSeguimientoData = () => {
                const data = document.getElementById('vinetaRegistrosSeguimientoData');

                if (!data) {
                    return;
                }

                try {
                    const parsed = JSON.parse(data.textContent);
                    timelines = parsed.timelines || {};
                    summaries = parsed.summaries || {};
                } catch (error) {
                    console.error('No se pudo actualizar el seguimiento de viñetas.', error);
                }
            };

            refreshSeguimientoData();
            document.addEventListener('vineta-registros-table-updated', refreshSeguimientoData);

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            let currentTimelineItems = [];
            let currentTimelineMode = localStorage.getItem('vineta_seguimiento_mode') || '';
            const horizontalNav = document.getElementById('vinetaHorizontalNav');
            const viewModeButtons = document.querySelectorAll('.vineta-view-mode-btn');

            const updateViewButtons = (mode) => {
                viewModeButtons.forEach((btn) => {
                    const isActive = btn.dataset.mode === mode;
                    btn.classList.toggle('active', isActive);
                });
            };

            const setTimelineMode = (mode) => {
                currentTimelineMode = mode;
                localStorage.setItem('vineta_seguimiento_mode', mode);
                updateViewButtons(mode);
                if (currentTimelineItems && currentTimelineItems.length) {
                    renderTimeline(currentTimelineItems);
                }
            };

            viewModeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setTimelineMode(btn.dataset.mode);
                });
            });

            const renderTimeline = (items) => {
                currentTimelineItems = items;
                if (!items.length) {
                    timelineContainer.innerHTML = `
                        <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                            <p class="theme-title font-black">Sin movimientos</p>
                            <p class="theme-text mt-1 text-sm">Todavía no hay actividades registradas para esta viñeta.</p>
                        </div>
                    `;
                    return;
                }

                const savedMode = localStorage.getItem('vineta_seguimiento_mode');
                const effectiveMode = currentTimelineMode || savedMode || (items.length > 3 ? 'timeline' : 'horizontal');
                currentTimelineMode = effectiveMode;
                updateViewButtons(effectiveMode);

                if (effectiveMode === 'grid') {
                    timelineContainer.innerHTML = `
                        <div class="vineta-grid-list">
                            ${items.map((item, index) => {
                                const isLast = index === items.length - 1;
                                const isAnulado = item.estado === 'anulado';
                                const stateClass = isAnulado ? 'is-anulado' : (isLast ? 'is-current' : 'is-complete');
                                const statusText = isAnulado ? 'Anulado' : (isLast ? 'Ultimo movimiento' : 'Completado');
                                const paso = index + 1;
                                const initial = escapeHtml(String(item.empleado || '?').slice(0, 1).toUpperCase());

                                return `
                                    <div class="vineta-delivery-card vineta-grid-card ${stateClass}">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 border-b theme-border pb-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="vineta-grid-step-badge ${stateClass}">${isAnulado ? '!' : paso}</span>
                                                    <span class="vineta-delivery-status ${stateClass}">
                                                        ${statusText}
                                                    </span>
                                                </div>
                                                <span class="vineta-delivery-kicker text-[11px]">Paso ${paso}</span>
                                            </div>

                                            <div class="mt-2.5">
                                                <h4 class="vineta-delivery-title text-sm font-black line-clamp-2" title="${escapeHtml(item.actividad || '')}">
                                                    ${escapeHtml(item.actividad || 'Actividad sin nombre')}
                                                </h4>
                                                <p class="vineta-delivery-date text-[11px] mt-1">
                                                    ${escapeHtml(item.fecha || 'N/A')}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <div class="vineta-delivery-worker">
                                                <span class="vineta-delivery-worker-avatar">
                                                    ${initial}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate">${escapeHtml(item.empleado || 'N/A')}</p>
                                                    <span>Cód. ${escapeHtml(item.empleado_codigo || 'N/A')}</span>
                                                </div>
                                                ${item.puros ? `<span class="text-[11px] font-black theme-title ml-auto shrink-0">${numberFormat.format(item.puros)} p</span>` : ''}
                                            </div>

                                            ${item.motivo_anulacion ? `
                                                <p class="vineta-timeline-alert mt-2.5 rounded-xl border px-2.5 py-1.5 text-xs font-semibold">
                                                    Anulado: ${escapeHtml(item.motivo_anulacion)}
                                                </p>
                                            ` : ''}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    `;
                } else if (effectiveMode === 'horizontal') {
                    timelineContainer.innerHTML = `
                        <div class="vineta-horizontal-scroll" id="vinetaHorizontalScrollContainer">
                            <div class="vineta-horizontal-track">
                                ${items.map((item, index) => {
                                    const isLast = index === items.length - 1;
                                    const isAnulado = item.estado === 'anulado';
                                    const stateClass = isAnulado ? 'is-anulado' : (isLast ? 'is-current' : 'is-complete');
                                    const statusText = isAnulado ? 'Anulado' : (isLast ? 'Ultimo movimiento' : 'Completado');
                                    const paso = index + 1;
                                    const initial = escapeHtml(String(item.empleado || '?').slice(0, 1).toUpperCase());

                                    return `
                                        <div class="vineta-horizontal-step ${stateClass} ${isLast ? 'is-last' : ''}">
                                            <div class="vineta-horizontal-rail">
                                                <span class="vineta-delivery-dot">${isAnulado ? '!' : paso}</span>
                                                ${isLast ? '' : '<span class="vineta-horizontal-line" aria-hidden="true"></span>'}
                                            </div>

                                            <div class="vineta-delivery-card flex flex-col justify-between h-full">
                                                <div class="vineta-delivery-card-head">
                                                    <div class="min-w-0">
                                                        <div class="vineta-delivery-kicker">
                                                            <span>Paso ${paso}</span>
                                                            <span class="vineta-delivery-status ${stateClass}">
                                                                ${statusText}
                                                            </span>
                                                        </div>

                                                        <p class="vineta-delivery-title" style="min-height: 2.8rem;">
                                                            ${escapeHtml(item.actividad || 'Actividad sin nombre')}
                                                        </p>

                                                        <p class="vineta-delivery-date">
                                                            ${escapeHtml(item.fecha || 'N/A')}
                                                        </p>
                                                    </div>

                                                    <div class="vineta-delivery-worker mt-2">
                                                        <span class="vineta-delivery-worker-avatar">
                                                            ${initial}
                                                        </span>

                                                        <div class="min-w-0">
                                                            <p>${escapeHtml(item.empleado || 'N/A')}</p>
                                                            <span>Cód. ${escapeHtml(item.empleado_codigo || 'N/A')}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                ${item.motivo_anulacion ? `
                                                    <p class="vineta-timeline-alert mt-3 rounded-xl border px-3 py-2 text-xs font-semibold">
                                                        Anulado: ${escapeHtml(item.motivo_anulacion)}
                                                    </p>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;
                } else {
                    timelineContainer.innerHTML = `
                        <div class="vineta-timeline-vlist">
                            ${items.map((item, index) => {
                                const isLast = index === items.length - 1;
                                const isAnulado = item.estado === 'anulado';
                                const stateClass = isAnulado ? 'is-anulado' : (isLast ? 'is-current' : 'is-complete');
                                const statusText = isAnulado ? 'Anulado' : (isLast ? 'Ultimo movimiento' : 'Completado');
                                const paso = index + 1;
                                const initial = escapeHtml(String(item.empleado || '?').slice(0, 1).toUpperCase());

                                return `
                                    <div class="vineta-vitem ${stateClass} ${isLast ? 'is-last' : ''}">
                                        <div class="vineta-vitem-dot">${isAnulado ? '!' : paso}</div>

                                        <div class="vineta-vcard">
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b theme-border pb-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="vineta-delivery-kicker text-xs font-black">Paso ${paso}</span>
                                                    <span class="vineta-delivery-status ${stateClass}">
                                                        ${statusText}
                                                    </span>
                                                </div>
                                                <div class="vineta-delivery-date flex items-center gap-1.5 text-xs font-bold">
                                                    <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                                        <path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/>
                                                    </svg>
                                                    ${escapeHtml(item.fecha || 'N/A')}
                                                </div>
                                            </div>

                                            <div class="mt-3 flex flex-col md:flex-row md:items-center justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="vineta-delivery-title text-base font-black">
                                                        ${escapeHtml(item.actividad || 'Actividad sin nombre')}
                                                    </h4>

                                                    <div class="vineta-delivery-worker mt-2.5 max-w-sm">
                                                        <span class="vineta-delivery-worker-avatar">
                                                            ${initial}
                                                        </span>
                                                        <div class="min-w-0">
                                                            <p>${escapeHtml(item.empleado || 'N/A')}</p>
                                                            <span>Código: ${escapeHtml(item.empleado_codigo || 'N/A')}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                                    ${item.puros ? `
                                                        <div class="theme-badge shrink-0 rounded-2xl border px-3 py-2 text-center">
                                                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide">Puros</span>
                                                            <strong class="text-sm font-black theme-title">${numberFormat.format(item.puros)}</strong>
                                                        </div>
                                                    ` : ''}
                                                    ${item.tiempo_trabajado_texto ? `
                                                        <div class="theme-badge shrink-0 rounded-2xl border px-3 py-2 text-center">
                                                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide">Tiempo</span>
                                                            <strong class="text-sm font-black theme-title">${escapeHtml(item.tiempo_trabajado_texto)}</strong>
                                                        </div>
                                                    ` : ''}
                                                </div>
                                            </div>

                                            ${item.motivo_anulacion ? `
                                                <p class="vineta-timeline-alert mt-3 rounded-xl border px-3 py-2 text-xs font-semibold">
                                                    Anulado: ${escapeHtml(item.motivo_anulacion)}
                                                </p>
                                            ` : ''}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    `;
                }
            };

            const renderSeguimiento = (items, summary) => {
                title.textContent = summary.vineta || 'Viñeta';
                subtitle.innerHTML = [
                    ['Producto', summary.producto || 'Sin producto'],
                    ['Código', summary.producto_codigo || 'N/A'],
                    ['Item', summary.producto_item || 'N/A'],
                    ['Marca', summary.marca || 'N/A'],
                    ['Orden', summary.orden || 'N/A'],
                    ['Fecha viñeta', summary.vineta_fecha || 'N/A'],
                ].map(([label, value]) => `
                    <span class="vineta-modal-info-chip">
                        <strong>${escapeHtml(label)}</strong>
                        <span>${escapeHtml(value)}</span>
                    </span>
                `).join('');
                movimientos.textContent = numberFormat.format(summary.movimientos || items.length || 0);
                activos.textContent = numberFormat.format(summary.activos || 0);
                puros.textContent = numberFormat.format(summary.puros || 0);
                renderTimeline(items);
            };

            const loadSeguimiento = async (vinetaId) => {
                const response = await fetch(
                    seguimientoUrlTemplate.replace('__VINETA_ID__', encodeURIComponent(vinetaId)),
                    {headers: {'Accept': 'application/json'}}
                );

                if (!response.ok) {
                    throw new Error('No se pudo cargar el seguimiento de la viñeta.');
                }

                const data = await response.json();
                timelines[vinetaId] = data.timeline || [];
                summaries[vinetaId] = data.summary || {};

                return {
                    items: timelines[vinetaId],
                    summary: summaries[vinetaId],
                };
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('.vineta-registro-seguimiento');

                if (!button) {
                    return;
                }

                const vinetaId = String(button.dataset.vinetaId || '');
                let items = timelines[vinetaId] || [];
                let summary = summaries[vinetaId] || {};
                const hasPreloadedData = Object.prototype.hasOwnProperty.call(timelines, vinetaId)
                    && Object.prototype.hasOwnProperty.call(summaries, vinetaId);

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                if (!hasPreloadedData) {
                    title.textContent = 'Cargando seguimiento...';
                    subtitle.textContent = 'Consultando los movimientos de la viñeta.';
                    movimientos.textContent = '...';
                    activos.textContent = '...';
                    puros.textContent = '...';
                    timelineContainer.innerHTML = `
                        <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                            <p class="theme-title font-black">Cargando movimientos...</p>
                        </div>
                    `;

                    try {
                        ({items, summary} = await loadSeguimiento(vinetaId));
                    } catch (error) {
                        console.error(error);
                        title.textContent = 'No se pudo cargar el seguimiento';
                        subtitle.textContent = 'Intenta nuevamente.';
                        movimientos.textContent = '0';
                        activos.textContent = '0';
                        puros.textContent = '0';
                        timelineContainer.innerHTML = `
                            <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                                <p class="theme-title font-black">Error al cargar los movimientos</p>
                            </div>
                        `;
                        return;
                    }
                }

                renderSeguimiento(items, summary);
            });

            document.getElementById('seguimientoVinetaClose').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('editVinetaRegistroModal');
            const form = document.getElementById('editVinetaRegistroForm');
            const title = document.getElementById('editRegistroTitle');
            const subtitle = document.getElementById('editRegistroSubtitle');
            const empleadoNombre = document.getElementById('editEmpleadoNombre');
            const empleadoEstado = document.getElementById('editEmpleadoEstado');
            const empleadoLookupUrl = @json(route('vineta-registros.empleado'));
            const editDefaultSubtitle = @json($editDefaultSubtitle);
            let empleadoLookupTimer = null;
            let empleadoLookupController = null;
            const fields = {
                 fecha: document.getElementById('edit_fecha_registro'),
                 hora: document.getElementById('edit_hora_registro'),
                 cantidad: document.getElementById('edit_cantidad_puros'),
                  minutos: document.getElementById('edit_minutos_trabajados'),
                  minutosGroup: document.getElementById('edit_minutos_trabajados_group'),
                 empleado: document.getElementById('edit_empleado_codigo'),
             };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const setEmpleadoStatus = (nombre, estado, type = 'neutral') => {
                empleadoNombre.textContent = nombre;
                empleadoEstado.textContent = estado;
                empleadoEstado.classList.remove('text-emerald-600', 'text-rose-600', 'text-amber-600');

                if (type === 'success') {
                    empleadoEstado.classList.add('text-emerald-600');
                }

                if (type === 'error') {
                    empleadoEstado.classList.add('text-rose-600');
                }

                if (type === 'warning') {
                    empleadoEstado.classList.add('text-amber-600');
                }
            };

            const lookupEmpleado = () => {
                const codigo = fields.empleado.value.trim();

                window.clearTimeout(empleadoLookupTimer);

                if (empleadoLookupController) {
                    empleadoLookupController.abort();
                }

                if (!codigo) {
                    setEmpleadoStatus('N/A', 'Ingresa un código para validar el empleado.');
                    return;
                }

                setEmpleadoStatus('Buscando...', 'Consultando empleado...', 'warning');

                empleadoLookupTimer = window.setTimeout(async () => {
                    empleadoLookupController = new AbortController();

                    try {
                        const url = new URL(empleadoLookupUrl, window.location.origin);
                        url.searchParams.set('codigo', codigo);

                        const response = await fetch(url, {
                            headers: {'Accept': 'application/json'},
                            signal: empleadoLookupController.signal,
                        });

                        const data = await response.json();

                        if (!response.ok || !data.employee) {
                            setEmpleadoStatus('No encontrado', 'No se encontró un empleado con ese código.', 'error');
                            return;
                        }

                        if (!data.employee.activo) {
                            setEmpleadoStatus(data.employee.nombre, 'Empleado inactivo.', 'error');
                            return;
                        }

                        setEmpleadoStatus(data.employee.nombre, `Código ${data.employee.codigo} validado.`, 'success');
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }

                        setEmpleadoStatus('No validado', 'No se pudo consultar el empleado.', 'error');
                    }
                }, 280);
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.vineta-registro-edit');

                if (!button) {
                    return;
                }

                    const isPorHora = button.dataset.porHora === '1';

                    form.action = button.dataset.action;
                      fields.fecha.value = button.dataset.fecha || '';
                      fields.hora.value = button.dataset.hora || '';
                      fields.cantidad.value = button.dataset.cantidad || '';
                      if (fields.minutos) {
                          fields.minutos.value = isPorHora ? '' : (button.dataset.minutos || '');
                          fields.minutos.disabled = isPorHora;
                          fields.minutos.required = ! isPorHora;
                      }

                      if (fields.minutosGroup) {
                          fields.minutosGroup.classList.toggle('hidden', isPorHora);
                      }
                     fields.empleado.value = button.dataset.empleadoCodigo || '';
                    setEmpleadoStatus(
                        button.dataset.empleadoNombre || 'N/A',
                        button.dataset.empleadoCodigo ? `Código ${button.dataset.empleadoCodigo} validado.` : 'Ingresa un código para validar el empleado.',
                        button.dataset.empleadoCodigo ? 'success' : 'neutral'
                    );
                    title.textContent = button.dataset.vineta || 'Viñeta registrada';
                    subtitle.textContent = button.dataset.actividad || editDefaultSubtitle;

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
            });

            fields.empleado.addEventListener('input', lookupEmpleado);

            document.getElementById('editVinetaRegistroClose').addEventListener('click', closeModal);
            document.getElementById('editVinetaRegistroCancel').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            @if ($errors->any())
                appSwal({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    html: @json($errors->all()).join('<br>'),
                });
            @endif
        });
    </script>



    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('editHoraOrdinariaModal');
            const form = document.getElementById('editHoraOrdinariaForm');
            const title = document.getElementById('editHoraOrdinariaTitle');
            const fields = {
                empleado: document.getElementById('edit_hora_ord_empleado_codigo'),
                fecha: document.getElementById('edit_hora_ord_fecha'),
                horas: document.getElementById('edit_hora_ord_horas'),
                minutos: document.getElementById('edit_hora_ord_minutos'),
                observacion: document.getElementById('edit_hora_ord_observacion'),
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.hora-ordinaria-edit');

                if (!button) {
                    return;
                }

                    form.action = button.dataset.action || '';
                    fields.empleado.value = button.dataset.empleadoCodigo || '';
                    fields.fecha.value = button.dataset.fecha || '';
                    fields.horas.value = button.dataset.horas || '';
                    fields.minutos.value = button.dataset.minutos || '';
                    fields.observacion.value = button.dataset.observacion || '';
                    title.textContent = button.dataset.empleadoNombre || 'Registro manual';

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
            });

            document.getElementById('editHoraOrdinariaClose').addEventListener('click', closeModal);
            document.getElementById('editHoraOrdinariaCancel').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>

