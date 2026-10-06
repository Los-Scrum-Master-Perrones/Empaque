{{-- Modal Detalle de Actividad en Estadístico (Sin bordes, diseño limpio por superficies) --}}
<div class="space-y-4">
    {{-- Fila única unificada: Contexto de Producto, Actividad y Estadísticas Clave --}}
    <div class="theme-soft p-3.5 sm:p-4 rounded-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        {{-- Información izquierda: Actividad, Empaque, Producto, Ordenes --}}
        <div class="space-y-1.5 min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="theme-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs sm:text-sm font-black">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                    Actividad: {{ $actividad }}
                </span>

                @if ($tipo_empaque && $tipo_empaque !== 'N/A')
                    <span class="theme-badge inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold">
                        {{ $tipo_empaque }}
                    </span>
                @endif

                @if ($titulo && $titulo !== 'N/A')
                    <span class="theme-title text-sm sm:text-base font-black truncate">
                        {{ $titulo }}
                    </span>
                @endif

                @if ($detalles)
                    <span class="theme-text text-xs font-medium truncate">
                        • {{ $detalles }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2.5 text-xs theme-text flex-wrap font-medium">
                @if ($orden_del_sistema && $orden_del_sistema !== 'N/A')
                    <span>OS: <strong class="theme-title font-bold">{{ $orden_del_sistema }}</strong></span>
                @endif
                @if ($orden_cliente && $orden_cliente !== 'N/A')
                    <span class="opacity-50">•</span>
                    <span>OC: <strong class="theme-title font-bold">{{ $orden_cliente }}</strong></span>
                @endif
                @if ($producto_item && $producto_item !== 'N/A')
                    <span class="opacity-50">•</span>
                    <span>Item: <strong class="theme-title font-bold font-mono">{{ $producto_item }}</strong></span>
                @endif
                @if ($producto_codigo && $producto_codigo !== 'N/A')
                    <span class="opacity-50">•</span>
                    <span>Cód: <strong class="theme-title font-bold font-mono">{{ $producto_codigo }}</strong></span>
                @endif
            </div>
        </div>

        {{-- Métricas en la misma fila: Total Puros, Personas y Viñetas --}}
        <div class="flex items-center gap-2 shrink-0 self-start md:self-center">
            <div class="theme-card px-3.5 py-2 rounded-xl text-center shadow-xs min-w-[85px]">
                <span class="text-[10px] font-bold uppercase tracking-wider theme-text block">Total Puros</span>
                <span class="text-base sm:text-lg font-black tabular-nums accent-sky block leading-tight">{{ number_format($total_puros) }}</span>
            </div>

            <div class="theme-card px-3.5 py-2 rounded-xl text-center shadow-xs min-w-[70px]">
                <span class="text-[10px] font-bold uppercase tracking-wider theme-text block">Personas</span>
                <span class="text-base sm:text-lg font-black tabular-nums theme-title block leading-tight">{{ number_format($total_personas) }}</span>
            </div>

            <div class="theme-card px-3.5 py-2 rounded-xl text-center shadow-xs min-w-[70px]">
                <span class="text-[10px] font-bold uppercase tracking-wider theme-text block">Viñetas</span>
                <span class="text-base sm:text-lg font-black tabular-nums theme-title block leading-tight">{{ number_format($total_vinetas) }}</span>
            </div>
        </div>
    </div>

    {{-- Desglose por Persona / Empleado --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between px-1">
            <h3 class="theme-title text-sm sm:text-base font-black flex items-center gap-2">
                <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Personal y Viñetas Procesadas
            </h3>
            <span class="theme-text text-xs font-semibold">
                {{ $total_personas }} {{ $total_personas === 1 ? 'persona registrada' : 'personas registradas' }}
            </span>
        </div>

        @forelse ($empleados as $empIndex => $emp)
            @php
                $words = explode(' ', trim($emp->nombre));
                $initials = '';
                if (count($words) >= 2) {
                    $initials = mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
                } elseif (count($words) === 1 && $words[0] !== '') {
                    $initials = mb_substr($words[0], 0, 2);
                } else {
                    $initials = 'EM';
                }
                $initials = strtoupper($initials);
            @endphp

            <div class="theme-soft rounded-2xl overflow-hidden transition">
                {{-- Encabezado del Empleado --}}
                <div class="p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-700 text-white font-black text-sm flex items-center justify-center shadow-sm shrink-0">
                            {{ $initials }}
                        </div>
                        <div>
                            <div class="theme-title font-black text-sm sm:text-base leading-snug">
                                {{ $emp->nombre }}
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                <span class="theme-badge px-2 py-0.5 rounded-lg text-xs font-mono font-bold">
                                    Cód: {{ $emp->codigo }}
                                </span>
                                <span class="theme-text text-xs">•</span>
                                <span class="theme-text text-xs font-medium">
                                    {{ $emp->total_vinetas }} {{ $emp->total_vinetas === 1 ? 'viñeta' : 'viñetas' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Total de Puros del Empleado --}}
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-bold theme-card shadow-xs text-xs sm:text-sm">
                            <span class="font-black tabular-nums text-sm sm:text-base accent-sky">
                                {{ number_format($emp->total_puros) }}
                            </span>
                            <span class="theme-text font-bold">puros</span>
                        </div>
                    </div>
                </div>

                {{-- Tabla de Viñetas de este Empleado --}}
                <div class="px-3 pb-3 sm:px-4 sm:pb-4">
                    <div class="theme-card rounded-xl overflow-x-auto shadow-xs">
                        <table class="w-full text-xs">
                            <thead class="theme-table-head font-bold">
                                <tr>
                                    <th class="px-3.5 py-2.5 text-left w-12 font-bold">#</th>
                                    <th class="px-3.5 py-2.5 text-left font-bold">ID Viñeta</th>
                                    <th class="px-3.5 py-2.5 text-left font-bold">Fecha</th>
                                    <th class="px-3.5 py-2.5 text-right font-bold">Cantidad de Puros</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($emp->vinetas as $vIndex => $vin)
                                    <tr class="theme-row transition">
                                        <td class="px-3.5 py-2 theme-text font-mono">
                                            {{ $vIndex + 1 }}
                                        </td>
                                        <td class="px-3.5 py-2 font-mono font-bold accent-sky whitespace-nowrap">
                                            #{{ $vin->id_display }}
                                        </td>
                                        <td class="px-3.5 py-2 theme-title font-medium whitespace-nowrap">
                                            {{ $vin->fecha }}
                                        </td>
                                        <td class="px-3.5 py-2 text-right font-black tabular-nums theme-title text-sm whitespace-nowrap">
                                            {{ number_format($vin->cantidad_puros) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="theme-table-head font-bold">
                                <tr>
                                    <td colspan="3" class="px-3.5 py-2 text-right text-[11px] uppercase tracking-wider theme-text">
                                        Subtotal de {{ $emp->nombre }}
                                    </td>
                                    <td class="px-3.5 py-2 text-right font-black tabular-nums accent-sky text-sm">
                                        {{ number_format($emp->total_puros) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="theme-soft rounded-2xl p-8 text-center">
                <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="theme-title text-sm font-bold">No se encontraron viñetas registradas</p>
                <p class="theme-text text-xs mt-1">No hay registros activos que coincidan con los criterios de esta actividad.</p>
            </div>
        @endforelse
    </div>
</div>
