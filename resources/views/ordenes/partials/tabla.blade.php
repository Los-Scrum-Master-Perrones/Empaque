@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'orden_del_sistema');
        $direccionActual = request('direccion', 'asc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return route('ordenes.index', array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'orden_del_sistema');
        $direccionActual = request('direccion', 'asc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };
@endphp

<div id="ordenesTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
        <table class="vinetas-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                <tr>
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_del_sistema') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Orden sistema
                            <span class="text-xs">{{ $sortIcon('orden_del_sistema') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Orden cliente
                            <span class="text-xs">{{ $sortIcon('orden') }}</span>
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
                        <a href="{{ $sortLink('codigo_producto') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Código producto
                            <span class="text-xs">{{ $sortIcon('codigo_producto') }}</span>
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
                        <a href="{{ $sortLink('tipo_empaque') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Tipo empaque
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
                        <a href="{{ $sortLink('pendiente') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Pendiente
                            <span class="text-xs">{{ $sortIcon('pendiente') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('saldo') }}"
                           class="vinetas-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Saldo
                            <span class="text-xs">{{ $sortIcon('saldo') }}</span>
                        </a>
                    </th>

                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        Estado
                    </th>

                    <th class="px-4 py-3 text-center font-bold whitespace-nowrap">
                        Generar
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y theme-divide">
                @forelse ($ordenes as $fila)
                    @php
                        $enCero = (bool) ($fila->sin_saldo ?? false);
                        $tachado = $enCero ? 'line-through opacity-60' : '';
                    @endphp

                    <tr class="vinetas-table-row theme-row transition {{ $enCero ? 'theme-soft' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold {{ $tachado }}">
                            {{ $fila->orden_del_sistema !== '' ? $fila->orden_del_sistema : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold {{ $tachado }}">
                            {{ $fila->orden !== '' ? $fila->orden : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->item !== '' ? $fila->item : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold {{ $tachado }}">
                            {{ $fila->codigo_producto !== '' ? $fila->codigo_producto : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->presentacion !== '' ? $fila->presentacion : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold {{ $tachado }}">
                            {{ $fila->marca !== '' ? $fila->marca : 'Sin marca' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->nombre !== '' ? $fila->nombre : 'Sin nombre' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->vitola !== '' ? $fila->vitola : 'Sin vitola' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->capa !== '' ? $fila->capa : 'Sin capa' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->tipo_empaque !== '' ? $fila->tipo_empaque : 'Sin empaque' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-text {{ $tachado }}">
                            {{ $fila->mes !== '' ? $fila->mes : 'N/A' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold {{ $tachado }}">
                            {{ number_format($fila->pendiente ?? 0) }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap theme-title font-bold {{ $tachado }}">
                            {{ number_format($fila->saldo ?? 0) }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($enCero)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border"
                                      style="background:#fef2f2;border-color:#fecaca;color:#b91c1c">
                                    EN CERO
                                </span>
                            @else
                                <span class="theme-badge vinetas-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border">
                                    {{ $fila->estado ?? 'pendiente' }}
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <button type="button"
                                    class="btn-generar-vinetas-orden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white transition"
                                    style="background:#0f766e"
                                    title="Generar viñetas para esta orden"
                                    data-id-pendiente="{{ $fila->id_pendiente }}"
                                    data-item="{{ $fila->item }}"
                                    data-codigo-producto="{{ $fila->codigo_producto }}"
                                    data-marca="{{ $fila->marca }}"
                                    data-nombre="{{ $fila->nombre }}"
                                    data-vitola="{{ $fila->vitola }}"
                                    data-capa="{{ $fila->capa }}"
                                    data-tipo-empaque="{{ $fila->tipo_empaque }}"
                                    data-orden-sistema="{{ $fila->orden_del_sistema }}"
                                    data-orden="{{ $fila->orden }}"
                                    data-mes="{{ $fila->mes }}">
                                <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Viñetas</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="px-4 py-12 text-center">
                            <p class="theme-title font-bold">
                                No hay órdenes para mostrar
                            </p>

                            <p class="theme-text text-sm mt-1">
                                No se encontraron registros con los filtros actuales o la API de facturación no respondió.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="vinetas-table-footer theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $ordenes->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $ordenes->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $ordenes->total() }}</span>
                orden(es)
            </p>

            <form method="GET"
                  action="{{ route('ordenes.index') }}"
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
            {{ $ordenes->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
