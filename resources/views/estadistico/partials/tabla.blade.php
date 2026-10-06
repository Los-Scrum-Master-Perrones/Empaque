@php
    $queryParams = request()->except(['orden', 'direccion', 'page']);
    $sortLink = function (string $columna) use ($queryParams, $orden, $direccion) {
        $nuevaDireccion = ($orden === $columna && $direccion === 'asc') ? 'desc' : 'asc';
        return route('estadistico.index', array_merge($queryParams, [
            'orden' => $columna,
            'direccion' => $nuevaDireccion,
            'page' => 1,
        ]));
    };
    $sortIcon = function (string $columna) use ($orden, $direccion) {
        if ($orden !== $columna) {
            return '↕';
        }
        return $direccion === 'asc' ? '↑' : '↓';
    };
@endphp

<div id="estadisticoTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll overflow-x-auto">
        <table class="vinetas-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head">
                <tr>
                    {{-- 1. Orden del sistema --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_del_sistema') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb]">
                            Orden del sistema
                            <span class="text-xs">{{ $sortIcon('orden_del_sistema') }}</span>
                        </a>
                    </th>

                    {{-- 2. Orden del cliente --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_cliente') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb]">
                            Orden del cliente
                            <span class="text-xs">{{ $sortIcon('orden_cliente') }}</span>
                        </a>
                    </th>

                    {{-- 3. Código producto --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('producto_codigo') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb]">
                            Código producto
                            <span class="text-xs">{{ $sortIcon('producto_codigo') }}</span>
                        </a>
                    </th>

                    {{-- 4. Item --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('producto_item') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb]">
                            Item
                            <span class="text-xs">{{ $sortIcon('producto_item') }}</span>
                        </a>
                    </th>

                    {{-- 5. Descripción --}}
                    <th class="px-4 py-3 text-left font-bold min-w-[220px]">
                        Descripción
                    </th>

                    {{-- 6. Tipo de empaque --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('tipo_empaque') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb]">
                            Tipo de empaque
                            <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                        </a>
                    </th>

                    {{-- 7. Actividad --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        Actividad
                    </th>

                    {{-- 8. Cantidad procesada --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('subtotal') }}"
                           class="estadistico-ajax-table-link inline-flex items-center gap-1.5 hover:text-[#2563eb] justify-end">
                            Cantidad procesada
                            <span class="text-xs">{{ $sortIcon('subtotal') }}</span>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y theme-divide">
                @forelse ($grupos as $grupo)
                    @php
                        $actividadesCount = count($grupo->actividades);
                    @endphp
                    @foreach ($grupo->actividades as $index => $act)
                        <tr class="vinetas-table-row transition theme-row">
                            @if ($index === 0)
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top theme-title font-semibold whitespace-nowrap">
                                    {{ $grupo->orden_del_sistema }}
                                </td>
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top theme-text font-medium whitespace-nowrap">
                                    {{ $grupo->orden_cliente }}
                                </td>
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top font-mono text-xs theme-text whitespace-nowrap">
                                    {{ $grupo->producto_codigo }}
                                </td>
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top font-mono text-xs font-bold theme-title whitespace-nowrap">
                                    {{ $grupo->producto_item }}
                                </td>
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top min-w-[220px]">
                                    <div class="theme-title font-semibold leading-snug">
                                        {{ $grupo->titulo }}
                                    </div>
                                    @if ($grupo->detalles)
                                        <div class="theme-text text-xs mt-0.5">
                                            {{ $grupo->detalles }}
                                        </div>
                                    @endif
                                </td>
                                <td rowspan="{{ $actividadesCount }}"
                                    class="px-4 py-3 align-top whitespace-nowrap">
                                    <span class="theme-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                        {{ $grupo->tipo_empaque }}
                                    </span>
                                </td>
                            @endif

                            {{-- Actividad individual --}}
                            <td class="px-4 py-2.5 theme-text font-medium">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 shrink-0"></span>
                                        <span class="font-semibold">{{ $act->actividad_nombre }}</span>
                                    </span>

                                    <button type="button"
                                            class="btn-ver-detalle-actividad inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition theme-button-secondary border theme-border hover:opacity-90 active:scale-95 shadow-xs cursor-pointer shrink-0"
                                            title="Ver personas y viñetas de {{ $act->actividad_nombre }}"
                                            data-group-os="{{ $grupo->orden_del_sistema !== 'N/A' ? $grupo->orden_del_sistema : '' }}"
                                            data-group-oc="{{ $grupo->orden_cliente !== 'N/A' ? $grupo->orden_cliente : '' }}"
                                            data-group-codigo="{{ $grupo->producto_codigo !== 'N/A' ? $grupo->producto_codigo : '' }}"
                                            data-group-item="{{ $grupo->producto_item !== 'N/A' ? $grupo->producto_item : '' }}"
                                            data-group-empaque="{{ $grupo->tipo_empaque !== 'N/A' ? $grupo->tipo_empaque : '' }}"
                                            data-actividad="{{ $act->actividad_nombre }}"
                                            data-cantidad="{{ number_format($act->cantidad) }}"
                                            data-titulo="{{ $grupo->titulo }}"
                                            data-detalles="{{ $grupo->detalles }}">
                                        <svg class="w-3.5 h-3.5 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detalles</span>
                                    </button>
                                </div>
                            </td>

                            {{-- Cantidad procesada de la actividad --}}
                            <td class="px-4 py-2.5 text-right font-semibold tabular-nums theme-title">
                                {{ number_format($act->cantidad) }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Fila de Subtotal por grupo --}}
                    <tr class="vinetas-subtotal-row theme-soft border-t border-b theme-border font-bold">
                        <td colspan="6" class="px-4 py-2.5 text-right text-xs uppercase tracking-wider theme-text font-bold">
                            Subtotal {{ $grupo->orden_del_sistema !== 'N/A' ? 'OS: ' . $grupo->orden_del_sistema : '' }} {{ $grupo->producto_item !== 'N/A' ? '(Item ' . $grupo->producto_item . ')' : '' }}
                        </td>
                        <td class="px-4 py-2.5 text-left text-xs uppercase font-extrabold theme-title">
                            Total Procesado
                        </td>
                        <td class="px-4 py-2.5 text-right font-black tabular-nums theme-title text-sm">
                            {{ number_format($grupo->subtotal) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-semibold theme-title">No hay datos estadísticos disponibles</p>
                                <p class="text-xs text-gray-400">Intenta ajustar los filtros de búsqueda, fechas o empleado.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Floating scrollbar --}}
    <div id="estadisticoFloatingScroll"
         class="vinetas-floating-scrollbar"
         aria-hidden="true">
        <div class="vinetas-floating-scrollbar-inner"></div>
    </div>

    {{-- Footer con paginación --}}
    <div class="vineta-registros-table-footer theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $grupos->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $grupos->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $grupos->total() }}</span>
                grupo(s)
            </p>

            <form method="GET"
                  action="{{ route('estadistico.index') }}"
                  class="per-page-control estadistico-ajax-per-page-form">
                @foreach (request()->except(['per_page', 'page']) as $key => $val)
                    @if (is_array($val))
                        @foreach ($val as $subVal)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                        @endforeach
                    @elseif ($val !== null && $val !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach

                <label class="per-page-label">Mostrar:</label>
                <select name="per_page"
                        onchange="this.form.requestSubmit()"
                        class="per-page-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPageSelected === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <span class="per-page-label">por pág.</span>
            </form>
        </div>

        <div class="estadistico-ajax-pagination">
            {{ $grupos->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
