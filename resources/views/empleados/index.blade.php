<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen theme-bg antialiased">
    <div
        x-data="{
            sidebarOpen: false,
            catalogos: {{ request()->routeIs('catalogos.*') ? 'true' : 'false' }},
            seguridad: {{ request()->routeIs('usuarios.*') || request()->routeIs('roles.*') || request()->routeIs('permisos.*') ? 'true' : 'false' }},
            produccion: {{ request()->routeIs('empleados.*') ? 'true' : 'false' }}
        }"
        class="min-h-screen flex theme-bg">

        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('layouts.topbar')

            <main class="flex-1 min-w-0">
                <section class="app-content-compact">
                    <div class="w-full max-w-none space-y-3">

                        {{-- Encabezado --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">

                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#f3efe7] border border-[#e5d8c7] flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-[#5b3a1e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 1a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Empleados
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Personal sincronizado desde la API de nómina del área de empaque.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $empleados->total() }}</strong>
                                        </span>

                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Cargos: <strong class="theme-title">{{ $cargos->count() }}</strong>
                                        </span>

                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Áreas: <strong class="theme-title">{{ $areas->count() }}</strong>
                                        </span>
                                    </div>
                                </div>

                                @if(!auth()->user()?->esSoloSupervisor())
                                <form method="POST"
                                      action="{{ route('empleados.sincronizar') }}"
                                      class="form-sincronizar w-full sm:w-auto">
                                    @csrf

                                    <button type="submit"
                                            class="gooey-action btn-sincronizar inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition disabled:opacity-70 disabled:cursor-not-allowed">
                                        <div class="loader-sincronizar hidden"></div>

                                        <span class="texto-sincronizar">
                                            Sincronizar empleados
                                        </span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>

                        {{-- Filtros --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('empleados.index') }}"
                                  class="empleados-ajax-filter-form grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-2 items-end">

                                <div class="xl:col-span-2">
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Buscar
                                    </label>

                                    <input type="text"
                                           name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="Código, nombre, cargo o área..."
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#5b3a1e]/20 focus:border-[#5b3a1e] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Estado
                                    </label>

                                    <select name="estado"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#5b3a1e]/20 focus:border-[#5b3a1e] outline-none transition">
                                        <option value="">Todos</option>
                                        <option value="activos" @selected(request('estado') === 'activos')>
                                            Activos
                                        </option>
                                        <option value="baja" @selected(request('estado') === 'baja')>
                                            Baja
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Cargo
                                    </label>

                                    <select name="cargo"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#5b3a1e]/20 focus:border-[#5b3a1e] outline-none transition">
                                        <option value="">Todos</option>

                                        @foreach ($cargos as $cargo)
                                            <option value="{{ $cargo }}" @selected(request('cargo') === $cargo)>
                                                {{ $cargo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Área
                                    </label>

                                    <select name="area"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#5b3a1e]/20 focus:border-[#5b3a1e] outline-none transition">
                                        <option value="">Todas</option>

                                        @foreach ($areas as $area)
                                            <option value="{{ $area }}" @selected(request('area') === $area)>
                                                {{ $area }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="sm:col-span-2 xl:col-span-1 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 pt-1">
                                    <a href="{{ route('empleados.index') }}"
                                       class="empleados-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2 rounded-xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-3 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Tabla --}}
                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="empleadosTableContainer">
                                @include('empleados.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    {{-- Overlay sincronización --}}
    <div id="syncOverlay"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/45 backdrop-blur-sm px-4">
        <div class="theme-card w-full max-w-md rounded-3xl bg-white border theme-border p-6 shadow-2xl text-center">
            <div class="mx-auto mb-5 flex justify-center">
                <div class="sync-loader"></div>
            </div>

            <h3 class="theme-title text-lg font-bold">
                Sincronizando empleados
            </h3>

            <p class="theme-text text-sm mt-2">
                Actualizando empleados desde la API de nómina.
            </p>

            <p class="theme-text text-xs mt-4">
                No cierres esta ventana mientras termina la sincronización.
            </p>
        </div>
    </div>

    {{-- Loader AJAX bajo header de tabla --}}
    <div id="empleadosTableLoader"
         class="productos-table-loader hidden"
         role="status"
         aria-live="polite">

        <div class="productos-table-loader-card theme-card theme-shadow">
            <div class="productos-table-loader-icon">
                <span></span>
            </div>

            <div class="text-left">
                <p class="theme-title text-sm font-bold leading-tight">
                    Actualizando tabla
                </p>

                <p class="theme-text text-xs leading-tight mt-0.5">
                    Cargando empleados...
                </p>
            </div>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('empleadosTableContainer');
            const getFilterForm = () => document.querySelector('.empleados-ajax-filter-form');

            const showTableLoader = () => {
                const container = getTableContainer();
                const loader = document.getElementById('empleadosTableLoader');

                if (loader) {
                    const header = document.querySelector('#empleadosTableContainer .productos-sticky-head');

                    if (header) {
                        const rect = header.getBoundingClientRect();
                        const top = Math.max(rect.bottom + 12, 78);

                        loader.style.top = `${top}px`;
                    }

                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                const inner = container?.querySelector('#empleadosTableInner');

                if (inner) {
                    inner.classList.add('productos-table-loading');
                }
            };

            const hideTableLoader = () => {
                const loader = document.getElementById('empleadosTableLoader');
                const inner = document.getElementById('empleadosTableInner');

                if (inner) {
                    inner.classList.remove('productos-table-loading');
                }

                if (loader) {
                    setTimeout(() => {
                        loader.classList.add('hidden');
                        loader.classList.remove('flex');
                    }, 160);
                }
            };

            const loadEmpleadosTable = async (url) => {
                const container = getTableContainer();

                if (!container) {
                    return;
                }

                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo actualizar la tabla de empleados');
                    }

                    container.innerHTML = await response.text();
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

                const isSortLink = link.classList.contains('empleados-ajax-table-link');
                const isPaginationLink = link.closest('#empleadosTableContainer .empleados-ajax-pagination');
                const isClearLink = link.classList.contains('empleados-ajax-clear-filters');

                if (!isSortLink && !isPaginationLink && !isClearLink) {
                    return;
                }

                event.preventDefault();

                if (isClearLink) {
                    const form = getFilterForm();

                    form?.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                }

                loadEmpleadosTable(link.href);
            });

            document.addEventListener('submit', (event) => {
                const filterForm = event.target.closest('.empleados-ajax-filter-form');
                const perPageForm = event.target.closest('.empleados-ajax-per-page-form');

                if (!filterForm && !perPageForm) {
                    return;
                }

                event.preventDefault();

                const form = filterForm || perPageForm;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                params.delete('page');

                loadEmpleadosTable(`${form.action}?${params.toString()}`);
            });

            window.addEventListener('popstate', () => {
                loadEmpleadosTable(window.location.href);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.form-sincronizar').forEach((form) => {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('.btn-sincronizar');
                    const text = form.querySelector('.texto-sincronizar');
                    const loader = form.querySelector('.loader-sincronizar');
                    const overlay = document.getElementById('syncOverlay');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('pointer-events-none', 'opacity-80');
                    }

                    if (text) {
                        text.textContent = 'Sincronizando...';
                    }

                    if (loader) {
                        loader.classList.remove('hidden');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');
                        overlay.classList.add('flex');
                    }
                });
            });
        });
    </script>
</body>
</html>
