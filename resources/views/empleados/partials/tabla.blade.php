@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'nombre');
        $direccionActual = request('direccion', 'asc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return route('empleados.index', array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'nombre');
        $direccionActual = request('direccion', 'asc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };
@endphp

<div id="empleadosTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll empleados-table-scroll">
        <table class="empleados-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                <tr>
                    <th class="px-3 py-3 text-left font-bold w-10">
                        <input type="checkbox"
                               id="empleadosQrTodos"
                               class="w-4 h-4 rounded border-[#cbbba4] accent-[#5b3a1e] cursor-pointer"
                               title="Seleccionar todos los empleados de esta página">
                    </th>

                    <th class="px-3 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('codigo') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Código
                            <span class="text-xs">{{ $sortIcon('codigo') }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-3 text-left font-bold min-w-[180px]">
                        <a href="{{ $sortLink('nombre') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Nombre
                            <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('cargo') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Cargo
                            <span class="text-xs">{{ $sortIcon('cargo') }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-3 text-left font-bold min-w-[140px]">
                        <a href="{{ $sortLink('area') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Área
                            <span class="text-xs">{{ $sortIcon('area') }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('fecha_ingreso') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Fecha ingreso
                            <span class="text-xs">{{ $sortIcon('fecha_ingreso') }}</span>
                        </a>
                    </th>

                    <th class="px-3 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('activo') }}"
                           class="empleados-ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                            Estado
                            <span class="text-xs">{{ $sortIcon('activo') }}</span>
                        </a>
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y theme-divide">
                @forelse ($empleados as $empleado)
                    <tr class="theme-row hover:bg-[#f8fafc] transition">
                        <td class="px-3 py-3 w-10">
                            <input type="checkbox"
                                   class="empleado-qr-check w-4 h-4 rounded border-[#cbbba4] accent-[#5b3a1e] cursor-pointer"
                                   value="{{ $empleado->codigo }}"
                                   title="Seleccionar para generar QR">
                        </td>

                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-bold border border-[#e5d8c7]">
                                {{ $empleado->codigo }}
                            </span>
                        </td>

                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-[#0f172a] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ mb_substr($empleado->nombre, 0, 1) }}
                                </div>

                                <div>
                                    <p class="theme-title font-semibold leading-tight">
                                        {{ $empleado->nombre }}
                                    </p>

                                    @if ($empleado->fecha_baja)
                                        <p class="theme-text text-xs mt-0.5">
                                            Baja: {{ $empleado->fecha_baja->format('d/m/Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-3 py-3 theme-text">
                            {{ $empleado->cargo ?? 'Sin cargo' }}
                        </td>

                        <td class="px-3 py-3 theme-text">
                            {{ $empleado->area ?? 'Sin área' }}
                        </td>

                        <td class="px-3 py-3 whitespace-nowrap theme-text">
                            {{ $empleado->fecha_ingreso ? $empleado->fecha_ingreso->format('d/m/Y') : 'Sin fecha' }}
                        </td>

                        <td class="px-3 py-3 whitespace-nowrap">
                            @if ($empleado->activo)
                                <span class="estado-badge is-active inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border">
                                    <span class="estado-badge-dot w-1.5 h-1.5"></span>
                                    Activo
                                </span>
                            @else
                                <span class="estado-badge is-inactive inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border">
                                    <span class="estado-badge-dot w-1.5 h-1.5"></span>
                                    Baja
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="mx-auto w-14 h-14 rounded-2xl bg-[#f3efe7] border border-[#e5d8c7] flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-[#5b3a1e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                                </svg>
                            </div>

                            <p class="theme-title font-bold">
                                No hay empleados para mostrar
                            </p>

                            <p class="theme-text text-sm mt-1">
                                Sincroniza la API o cambia los filtros de búsqueda.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $empleados->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $empleados->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $empleados->total() }}</span>
                empleado(s)
            </p>

            <form method="GET"
                  action="{{ route('empleados.index') }}"
                  class="per-page-control empleados-ajax-per-page-form">
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
                    <option value="10" @selected(request('per_page', 10) == 10)>10</option>
                    <option value="25" @selected(request('per_page') == 25)>25</option>
                    <option value="50" @selected(request('per_page') == 50)>50</option>
                    <option value="100" @selected(request('per_page') == 100)>100</option>
                    <option value="all" @selected(request('per_page') === 'all')>Todos</option>
                </select>
            </form>
        </div>

        <div class="pagination-cafe empleados-ajax-pagination">
            {{ $empleados->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
