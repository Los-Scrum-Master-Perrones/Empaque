<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Actividades | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: true,
    seguridad: false,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">
        @include('layouts.topbar', [
            'title' => 'Actividades',
            'description' => 'Catálogo de actividades sincronizadas desde la API.'
        ])

    <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto">

                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-visible">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h2 class="theme-title text-lg font-bold text-[#3b2818]">
                                    Listado de actividades
                                </h2>

                                <p class="theme-text text-sm text-gray-500">
                                    Aquí puedes ver las actividades relacionadas a productos.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                @can('productos.sincronizar')
                                <form method="POST" action="{{ route('catalogos.actividades.sincronizar') }}">
                                    @csrf
                                    <button type="submit"
                                            class="gooey-action px-4 py-2 rounded-xl bg-[#2e5c38] text-white text-sm font-semibold hover:bg-[#23472b] transition inline-flex items-center gap-1.5 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Sincronizar actividades
                                    </button>
                                </form>
                                @endcan

                                <form method="GET" action="{{ route('catalogos.actividades.index') }}" class="catalogo-ajax-filter-form flex gap-2">
                                    <div class="relative">
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="Buscar actividad o código"
                                               class="theme-input w-72 rounded-xl border-gray-300 text-sm pr-10 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                        <a href="{{ route('catalogos.actividades.index') }}"
                                           class="catalogo-ajax-clear {{ request('buscar') ? '' : 'hidden' }} absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-[#5b3a1e] hover:bg-[#f3efe7] transition"
                                           title="Limpiar búsqueda">
                                            ×
                                        </a>
                                    </div>

                                    <button type="submit"
                                            class="gooey-action px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                        Buscar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>


                    <div id="catalogoTableContainer">
                    @php
                        $sortLink = function ($campo) {
                            $ordenActual = request('orden', 'nombre');
                            $direccionActual = request('direccion', 'asc');

                            $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

                            return url()->current() . '?' . http_build_query(array_merge(request()->query(), [
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

                    <div class="productos-table-scroll catalogo-table-scroll">
                        <table class="w-full text-sm">
                            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('id') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            ID
                                            <span class="text-xs">{{ $sortIcon('id') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('codigo_actividad') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Código
                                            <span class="text-xs">{{ $sortIcon('codigo_actividad') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('nombre') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Actividad
                                            <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                                        </a>
                                    </th>

                                   <th class="px-6 py-4 text-left font-semibold">
    <a href="{{ $sortLink('productos_count') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
        Productos
        <span class="text-xs">{{ $sortIcon('productos_count') }}</span>
    </a>
</th>

<th class="px-6 py-4 text-left font-semibold">
    <a href="{{ $sortLink('precio_mo') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
        Precio MO
        <span class="text-xs">{{ $sortIcon('precio_mo') }}</span>
    </a>
</th>

<th class="px-6 py-4 text-right font-semibold">
    Acciones
</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($actividades as $actividad)
                                    <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                            <span class="theme-text text-gray-500">
                                                #{{ $actividad->id }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $actividad->codigo_actividad ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="theme-title font-semibold text-[#3b2818]">
                                                {{ $actividad->nombre }}
                                            </p>

                                            <p class="theme-text text-[11px] text-gray-400 mt-0.5">
                                                API ID: {{ $actividad->api_id_actividad }}
                                            </p>
                                        </td>

                                         <td class="px-6 py-4">
                                             <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                 {{ $actividad->productos_count }} producto(s)
                                             </span>
                                         </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $precioBase = (float) ($actividad->precio_mo ?? 0);
                                                if ($precioBase <= 0 && $actividad->precio_min !== null) {
                                                    $precioBase = (float) $actividad->precio_min;
                                                }
                                            @endphp
                                            @if ($precioBase > 0)
                                                <span class="theme-title font-semibold text-[#3b2818]">
                                                    {{ number_format($precioBase, 7) }}
                                                </span>
                                            @else
                                                <span class="theme-text text-gray-400 text-sm">Sin precio</span>
                                            @endif
                                        </td>


                                         <td class="px-6 py-4 text-right">
    <a href="{{ route('catalogos.productos.index', ['actividad_id' => $actividad->id]) }}"
       class="theme-button-secondary inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
        Ver productos
        <span>→</span>
    </a>
</td>
                                    </tr>
                                 @empty
                                     <tr>
                                        <td colspan="6" class="theme-text px-6 py-10 text-center text-gray-500">
                                             No hay actividades registradas.
                                         </td>
                                     </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="theme-soft px-6 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="theme-text text-sm text-gray-500">
                            Mostrando
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $actividades->firstItem() ?? 0 }}</span>
                            a
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $actividades->lastItem() ?? 0 }}</span>
                            de
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $actividades->total() }}</span>
                            actividad(es)
                        </p>

                        <div>
                            {{ $actividades->onEachSide(1)->links('pagination.cafe') }}
                        </div>
                    </div>

                    </div>

                </div>
            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>
