<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de empaque | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

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

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="section-title-icon vinetas-header-icon w-9 h-9 rounded-xl flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Ficha de empaque faltante
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Productos registrados en el ERP sin materiales capturados en la ficha.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Faltan: <strong class="theme-title">{{ $faltantes->count() }}</strong>
                                        </span>

                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Fecha: <strong class="theme-title">{{ $fecha !== '' ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'desde 01/10' }}</strong>
                                        </span>

                                        <form method="GET" action="{{ route('ficha-faltante.index') }}" class="flex items-center gap-2">
                                            <input type="date" name="fecha" value="{{ $fecha }}"
                                                   class="rounded-xl border theme-border bg-white px-3 py-1.5 text-xs theme-title">
                                            <button type="submit"
                                                    class="theme-button-secondary inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold border theme-border transition">
                                                Ver
                                            </button>
                                        </form>

                                        <a href="{{ route('ficha-faltante.index', ['fecha' => $fecha, 'refresh' => 1]) }}"
                                           class="theme-button-secondary inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border theme-border transition">
                                            ⟳ Actualizar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            @if(!empty($error))
                                <div class="mt-3 rounded-xl border border-[#fecaca] bg-[#fef2f2] px-3 py-2 text-xs font-semibold text-[#b91c1c]">
                                    {{ $error }}
                                </div>
                            @endif
                        </div>

                        @if($actividades->isNotEmpty())
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="theme-title text-sm font-bold">Actividades que salen en la lista</p>
                                        <p class="theme-text text-xs mt-0.5">
                                            Oculta las que no interesan (ej. Rezagado). Lo oculto no se muestra ni se cuenta.
                                        </p>
                                    </div>

                                    <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                        Ocultas: <strong class="theme-title">{{ $actividades->where('ignorada', true)->count() }}</strong>
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($actividades as $actividad)
                                        <form method="POST" action="{{ route('ficha-faltante.ignorar') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="codigo" value="{{ $actividad->codigo }}">
                                            <input type="hidden" name="nombre" value="{{ $actividad->nombre }}">
                                            <input type="hidden" name="fecha" value="{{ $fecha }}">
                                            <input type="hidden" name="ignorar" value="{{ $actividad->ignorada ? 0 : 1 }}">

                                            <button type="submit"
                                                    class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-bold transition
                                                           {{ $actividad->ignorada ? 'border-[#fecaca] bg-[#fef2f2] text-[#b91c1c]' : 'theme-border theme-button-secondary' }}"
                                                    title="{{ $actividad->ignorada ? 'Volver a mostrar' : 'Ignorar esta actividad' }}">
                                                <span>{{ $actividad->ignorada ? '🚫' : '👁' }}</span>
                                                <span>
                                                    {{ $actividad->codigo }} {{ $actividad->nombre !== '' ? $actividad->nombre : 'Actividad' }}
                                                </span>
                                                <span class="theme-text font-semibold">({{ $actividad->registros }})</span>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-hidden">
                            <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
                                <table class="vinetas-table w-full text-sm">
                                    <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">Fecha</th>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">Código producto</th>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">SKU / Item</th>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">Actividad</th>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">Tipo empaque</th>
                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Filas</th>
                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Puros</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y theme-divide">
                                        @forelse ($faltantes as $fila)
                                            <tr class="vinetas-table-row theme-row transition">
                                                <td class="px-4 py-3 whitespace-nowrap theme-text">{{ $fila->ultima !== '' ? $fila->ultima : '—' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">{{ $fila->producto }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap theme-text">{{ $fila->sku !== '' ? $fila->sku : 'N/A' }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap theme-text">
                                                    <span class="font-semibold theme-title">{{ $fila->actividad }}</span>
                                                    {{ $fila->actividad_nombre }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap theme-text">
                                                    <span class="font-semibold theme-title">{{ $fila->tipo_empaque }}</span>
                                                    {{ $fila->tipo_empaque_nombre }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right theme-text">{{ number_format($fila->filas) }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right theme-title font-semibold">{{ number_format($fila->puros) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-12 text-center">
                                                    <p class="theme-title font-bold">Todo bien: no falta ninguna ficha</p>
                                                    <p class="theme-text text-sm mt-1">
                                                        Todas las combinaciones de producto, actividad y tipo de empaque tienen materiales capturados.
                                                    </p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    @include('layouts.flash')
</body>
</html>
