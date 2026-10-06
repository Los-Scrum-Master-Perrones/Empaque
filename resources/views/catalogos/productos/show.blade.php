<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Detalle Producto | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="{{ rtrim(config('services.product_images.base_url'), '/') }}" crossorigin>
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
            'title' => 'Detalle del producto',
            'description' => 'Información completa del producto y actividades relacionadas.'
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                {{-- RESUMEN PRINCIPAL --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="theme-title text-xl font-bold text-[#3b2818]">
                                {{ $producto->nombre ?? 'Sin nombre' }}
                            </h2>

                            <p class="theme-text text-sm text-gray-500 mt-1">
                                Item: {{ $producto->item ?? 'N/A' }} · Código: {{ $producto->codigo_producto ?? 'N/A' }} · API ID: {{ $producto->api_id_producto }}
                            </p>
                        </div>

                        <a href="{{ route('catalogos.productos.index', request()->query()) }}"
                           class="gooey-action px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition text-center">
                            Volver
                        </a>
                    </div>

                    @php
                        $imagenesProductoCarrusel = collect($producto->imagenesEmpaqueUrls())
                            ->map(fn ($url, $index) => [
                                'url' => $url,
                                'tipo' => 'Empaque / caja',
                            ])
                            ->merge(
                                collect($producto->imagenesAnilladoUrls())
                                    ->map(fn ($url, $index) => [
                                        'url' => $url,
                                        'tipo' => 'Anillado',
                                    ])
                            )
                            ->unique('url')
                            ->values()
                            ->all();
                    @endphp

                    <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">
                        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Marca</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->marca?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Empresa</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->empresa?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Vitola</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->vitola?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Capa</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->capa?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Presentación</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->presentacion?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Tipo empaque</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Precio</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ number_format($producto->precio, 2) }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Cantidad bulto</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->cantidad_bulto }}
                            </p>
                        </div>

                        </div>

                        <div class="product-image-card theme-soft rounded-2xl border theme-border overflow-hidden min-h-[320px]"
                             x-data="productImageCarousel(@js($imagenesProductoCarrusel))">
                            <div class="p-4 border-b theme-border flex items-center justify-between gap-3">
                                <div>
                                    <p class="theme-title text-sm font-bold">
                                        Imagenes del producto
                                    </p>
                                    <p class="theme-text mt-1 text-xs">
                                        Empaque y anillado
                                    </p>
                                </div>

                                <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] whitespace-nowrap">
                                    {{ count($imagenesProductoCarrusel) ?: 'Sin' }} {{ count($imagenesProductoCarrusel) === 1 ? 'imagen' : 'imagenes' }}
                                </span>
                            </div>

                            @if ($imagenesProductoCarrusel)
                                <div class="relative p-3">
                                    <a :href="images[index].url" target="_blank" rel="noopener noreferrer" class="block rounded-xl overflow-hidden border theme-border bg-white">
                                        <img :src="currentSrc"
                                             src="{{ $imagenesProductoCarrusel[0]['url'] }}"
                                             class="product-image-preview h-72 w-full object-contain transition-opacity duration-150"
                                             :class="loading ? 'opacity-70' : 'opacity-100'"
                                             alt="Imagen de {{ $producto->nombre ?? 'producto' }}"
                                             loading="eager"
                                             decoding="async"
                                             fetchpriority="high">
                                    </a>

                                    <div x-show="loading"
                                         class="absolute inset-3 rounded-xl bg-white/70 backdrop-blur-[1px] flex items-center justify-center text-xs font-bold text-[#0f172a]"
                                         style="display: none;">
                                        Cargando imagen...
                                    </div>

                                    @if (count($imagenesProductoCarrusel) > 1)
                                        <button type="button"
                                                class="absolute left-5 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-[#0f172a]/80 text-white text-2xl leading-none flex items-center justify-center hover:bg-[#0f172a] transition"
                                                @click="previous()"
                                                aria-label="Imagen anterior">
                                            ‹
                                        </button>

                                        <button type="button"
                                                class="absolute right-5 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-[#0f172a]/80 text-white text-2xl leading-none flex items-center justify-center hover:bg-[#0f172a] transition"
                                                @click="next()"
                                                aria-label="Imagen siguiente">
                                            ›
                                        </button>

                                        <span class="absolute right-5 bottom-5 rounded-full bg-[#0f172a]/80 px-3 py-1 text-xs font-semibold text-white">
                                            <span x-text="index + 1">1</span>/{{ count($imagenesProductoCarrusel) }}
                                        </span>
                                    @endif

                                    <span class="absolute left-5 bottom-5 rounded-full bg-[#0f172a]/80 px-3 py-1 text-xs font-semibold text-white"
                                          x-text="images[index].tipo">
                                        {{ $imagenesProductoCarrusel[0]['tipo'] }}
                                    </span>
                                </div>
                            @else
                                <div class="product-image-empty h-72 flex items-center justify-center text-sm font-semibold">
                                    Sin imagen
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- DATOS ADICIONALES --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border">
                        <h3 class="theme-title text-lg font-bold text-[#3b2818]">
                            Datos adicionales
                        </h3>

                        <p class="theme-text text-sm text-gray-500">
                            Códigos, descripción e indicadores del producto.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Código caja</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->codigo_caja ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Código precio</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->codigo_precio ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Descripción</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->descripcion ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 flex flex-wrap gap-2">
                        <span class="product-flag-badge {{ $producto->anillo ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Anillo: {{ $producto->anillo ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->cello ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Cello: {{ $producto->cello ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->upc ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            UPC: {{ $producto->upc ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->sampler ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Sampler: {{ $producto->sampler ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->caja_local ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Caja local: {{ $producto->caja_local ? 'Sí' : 'No' }}
                        </span>
                    </div>
                </div>

                {{-- ACTIVIDADES --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden" x-data="{
                    loadingActivityId: null,
                    async toggleActividad(actividadId, tipoEmpaqueId, buttonEl) {
                        this.loadingActivityId = actividadId;
                        try {
                            const form = buttonEl.closest('form');
                            const url = form.action;
                            const csrf = form.querySelector('input[name=_token]').value;
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                body: JSON.stringify({ tipo_empaque_id: tipoEmpaqueId })
                            });
                            const data = await response.json();
                            if (data.ok) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Error al cambiar estado.');
                            }
                        } catch (err) {
                            alert('Error de conexión.');
                        } finally {
                            this.loadingActivityId = null;
                        }
                    }
                }">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="theme-title text-lg font-bold text-[#3b2818]">
                                Actividades del producto
                            </h3>

                            <p class="theme-text text-sm text-gray-500">
                                Actividades asociadas desde la API y viñetas escaneadas. Las más recientes y activas tienen prioridad al escanear.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] w-fit">
                                {{ $producto->actividades->count() }} actividad(es)
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="theme-table-head bg-[#f3efe7] text-[#3b2818]">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">Código</th>
                                    <th class="px-6 py-4 text-left font-semibold">Actividad</th>
                                    <th class="px-6 py-4 text-left font-semibold">Tipo empaque</th>
                                    <th class="px-6 py-4 text-left font-semibold">Precio MO</th>
                                    <th class="px-6 py-4 text-left font-semibold">Origen / Último escaneo</th>
                                    <th class="px-6 py-4 text-right font-semibold">Estado / Acción</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($producto->actividades as $actividad)
                                    @php
                                        $tipo = \App\Models\TipoEmpaque::find($actividad->pivot->tipo_empaque_id);
                                        $esActivo = (bool) ($actividad->pivot->activo ?? true);
                                        $ultimoEscaneo = $actividad->pivot->ultimo_escaneo_en ? \Carbon\Carbon::parse($actividad->pivot->ultimo_escaneo_en) : null;
                                        $esEscaneo = ($actividad->pivot->origen ?? 'api') === 'escaneo' || $ultimoEscaneo !== null;
                                    @endphp
                                    <tr class="theme-row border-b border-gray-100 theme-border transition {{ $esActivo ? 'hover:bg-[#faf7f2]' : 'bg-gray-50/70 opacity-60 hover:bg-gray-100/70' }}">
                                        <td class="px-6 py-4">
                                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $actividad->codigo_actividad ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="theme-title font-semibold text-[#3b2818] {{ ! $esActivo ? 'line-through text-gray-500' : '' }}">
                                                {{ $actividad->nombre }}
                                            </p>

                                            <p class="theme-text text-[11px] text-gray-400">
                                                API ID: {{ $actividad->api_id_actividad }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-text text-gray-700 font-medium">
                                                {{ $tipo?->nombre ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-title font-semibold text-[#3b2818]">
                                                {{ number_format((float) $actividad->pivot->precio_mo, 7) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($ultimoEscaneo)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    <span class="text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                                        Escaneado: {{ $ultimoEscaneo->format('d/m/Y h:i A') }}
                                                    </span>
                                                </div>
                                            @elseif ($esEscaneo)
                                                <span class="text-xs font-semibold text-blue-800 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
                                                    Viñeta escaneada
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-500 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">
                                                    Catálogo API
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            @can('productos.sincronizar')
                                                <form method="POST"
                                                      action="{{ route('catalogos.productos.actividades.toggle', ['producto' => $producto->id, 'actividad' => $actividad->id]) }}"
                                                      class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="tipo_empaque_id" value="{{ $actividad->pivot->tipo_empaque_id }}">

                                                    <button type="button"
                                                            @click="toggleActividad({{ $actividad->id }}, {{ $actividad->pivot->tipo_empaque_id ?? 'null' }}, $el)"
                                                            :disabled="loadingActivityId === {{ $actividad->id }}"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-sm {{ $esActivo ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-red-50 hover:text-red-700 hover:border-red-300' : 'bg-gray-200 text-gray-700 border border-gray-300 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300' }}"
                                                            title="{{ $esActivo ? 'Clic para desactivar esta actividad en este producto' : 'Clic para activar esta actividad en este producto' }}">
                                                        <span class="inline-block w-2 h-2 rounded-full {{ $esActivo ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                        <span>{{ $esActivo ? 'Activa (Desactivar)' : 'Inactiva (Activar)' }}</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold {{ $esActivo ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-gray-200 text-gray-700 border border-gray-300' }}">
                                                    <span class="inline-block w-2 h-2 rounded-full {{ $esActivo ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                    <span>{{ $esActivo ? 'Activa' : 'Inactiva' }}</span>
                                                </span>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="theme-text px-6 py-10 text-center text-gray-500">
                                            Este producto no tiene actividades asociadas.
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

@include('layouts.flash')

</body>
</html>
