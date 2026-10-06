@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'id');
        $direccionActual = request('direccion', 'desc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return route('vinetas-por-orden.index', array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'id');
        $direccionActual = request('direccion', 'desc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };
@endphp

<div id="vinetasPorOrdenTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
        <table class="vinetas-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                <tr>
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('fecha') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Fecha
                             <span class="text-xs">{{ $sortIcon('fecha') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('id') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             ID
                             <span class="text-xs">{{ $sortIcon('id') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('codigo_qr') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Código QR
                             <span class="text-xs">{{ $sortIcon('codigo_qr') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('item') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Item
                            <span class="text-xs">{{ $sortIcon('item') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('presentacion') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Presentación
                             <span class="text-xs">{{ $sortIcon('presentacion') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('codigo_producto') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Código de producto
                             <span class="text-xs">{{ $sortIcon('codigo_producto') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('marca') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Marca
                             <span class="text-xs">{{ $sortIcon('marca') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('nombre') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Nombre
                             <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('vitola') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Vitola
                             <span class="text-xs">{{ $sortIcon('vitola') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('capa') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Capa
                             <span class="text-xs">{{ $sortIcon('capa') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_del_sistema') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Orden del sistema
                             <span class="text-xs">{{ $sortIcon('orden_del_sistema') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Orden del cliente
                             <span class="text-xs">{{ $sortIcon('orden') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('tipo_empaque') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Tipo de empaque
                             <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('mes') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                             Mes
                             <span class="text-xs">{{ $sortIcon('mes') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('cantidad_puros') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Puros
                            <span class="text-xs">{{ $sortIcon('cantidad_puros') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('estado') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Estado
                            <span class="text-xs">{{ $sortIcon('estado') }}</span>
                        </a>
                    </th>

                    @if(!auth()->user()?->esSoloSupervisor())
                    <th class="px-4 py-3 text-center font-bold whitespace-nowrap">
                        Acción
                    </th>
                    @endif
                </tr>
            </thead>

            <tbody class="divide-y theme-divide">
                @forelse ($vinetasPorOrden as $vineta)
                    <tr class="vinetas-table-row theme-row transition">
                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->fecha ? date('d/m/Y', strtotime((string) $vineta->fecha)) : 'Sin fecha' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="theme-badge vinetas-id-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                #{{ $vineta->id }}
                            </span>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="theme-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border text-[#2563eb] bg-[#eff6ff]">
                                    {{ $vineta->codigo_qr ?? 'N/A' }}
                                </span>
                                @if(!empty($vineta->codigo_qr))
                                    <button type="button"
                                            class="btn-ver-qr theme-button-secondary inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg border theme-border transition cursor-pointer"
                                            data-qr-code="{{ $vineta->codigo_qr }}"
                                            title="Ver código QR">
                                        <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                        <span>Ver QR</span>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $vineta->item ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->presentacion ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $vineta->codigo_producto ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $vineta->marca ?? 'Sin marca' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->nombre ?? 'Sin nombre' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->vitola ?? 'Sin vitola' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->capa ?? 'Sin capa' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $vineta->orden_del_sistema ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $vineta->orden ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->tipo_empaque ?? 'Sin empaque' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $vineta->mes ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ number_format($vineta->cantidad_puros ?? 0) }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="theme-badge vinetas-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border">
                                {{ $vineta->estado ?? 'N/A' }}
                            </span>
                        </td>

                        @if(!auth()->user()?->esSoloSupervisor())
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <button type="button"
                                    class="btn-crear-vineta-orden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-[#0f172a] text-white hover:bg-[#1e293b] shadow-sm transition cursor-pointer"
                                    data-id="{{ $vineta->id }}"
                                    data-codigo-qr="{{ $vineta->codigo_qr ?? '' }}"
                                    data-fecha="{{ $vineta->fecha ? date('Y-m-d', strtotime((string) $vineta->fecha)) : '' }}"
                                    data-item="{{ $vineta->item ?? '' }}"
                                    data-presentacion="{{ $vineta->presentacion ?? '' }}"
                                    data-codigo-producto="{{ $vineta->codigo_producto ?? '' }}"
                                    data-marca="{{ $vineta->marca ?? '' }}"
                                    data-nombre="{{ $vineta->nombre ?? '' }}"
                                    data-vitola="{{ $vineta->vitola ?? '' }}"
                                    data-capa="{{ $vineta->capa ?? '' }}"
                                    data-orden-del-sistema="{{ $vineta->orden_del_sistema ?? '' }}"
                                    data-orden="{{ $vineta->orden ?? '' }}"
                                    data-tipo-empaque="{{ $vineta->tipo_empaque ?? '' }}"
                                    data-mes="{{ $vineta->mes ?? '' }}"
                                    data-cantidad-puros="{{ $vineta->cantidad_puros ?? 0 }}"
                                    data-estado="{{ $vineta->estado ?? 'activo' }}"
                                    title="Crear viñeta a partir de este registro">
                                <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Crear</span>
                            </button>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ !auth()->user()?->esSoloSupervisor() ? 17 : 16 }}" class="px-4 py-12 text-center">
                            <p class="theme-title font-bold">
                                No hay viñetas por orden para mostrar
                            </p>

                            <p class="theme-text text-sm mt-1">
                                No se encontraron registros con los filtros actuales.
                            </p>
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    <div id="vinetasPorOrdenFloatingScroll"
         class="vinetas-floating-scrollbar"
         aria-hidden="true">
        <div class="vinetas-floating-scrollbar-inner"></div>
    </div>

    <div class="vinetas-table-footer theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $vinetasPorOrden->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $vinetasPorOrden->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $vinetasPorOrden->total() }}</span>
                viñeta(s) por orden
            </p>

            <form method="GET"
                  action="{{ route('vinetas-por-orden.index') }}"
                  class="per-page-control vinetas-ajax-per-page-form">
                @foreach(request()->except('per_page', 'page') as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label class="per-page-label">
                    Mostrar:
                </label>

                <select name="per_page"
                        onchange="this.form.requestSubmit()"
                        class="per-page-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPageSelected === $option)>{{ $option }}</option>
                    @endforeach
                    <option value="all" @selected($perPageSelected === 'all')>Todos</option>
                </select>
            </form>
        </div>

        <div class="pagination-cafe vinetas-ajax-pagination">
            {{ $vinetasPorOrden->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
