<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes | Sistema de Empaque</title>

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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Órdenes
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Todas las órdenes de empaque (también las que están en cero) para generar viñetas.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2 items-center">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Órdenes: <strong class="theme-title">{{ $ordenes->total() }}</strong>
                                        </span>
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Con saldo: <strong class="theme-title">{{ $totalConSaldo }}</strong>
                                        </span>
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            En cero: <strong class="theme-title">{{ $totalEnCero }}</strong>
                                        </span>
                                        <a href="{{ route('ordenes.index', array_merge(request()->query(), ['refresh' => 1])) }}"
                                           class="theme-button-secondary inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border theme-border transition">
                                            ⟳ Actualizar
                                        </a>

                                        @if (($pendientesImpresion ?? 0) > 0)
                                            <a href="{{ route('ordenes.impresion') }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white transition"
                                               style="background:#0f766e">
                                                Imprimir pendientes ({{ $pendientesImpresion }})
                                            </a>
                                        @endif

                                        @if (($ultimoLote ?? 0) > 0)
                                            <a href="{{ route('ordenes.impresion.ultima') }}"
                                               class="theme-button-secondary inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border theme-border transition">
                                                ⟲ Reimprimir última ({{ $ultimoLote }})
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!empty($error))
                                <div class="mt-3 rounded-xl border border-[#fecaca] bg-[#fef2f2] px-3 py-2 text-xs font-semibold text-[#b91c1c]">
                                    {{ $error }}
                                </div>
                            @endif
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('ordenes.index') }}"
                                  class="vinetas-ajax-filter-form">
                                <div class="vinetas-filter-row grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-2 items-end">
                                    <div class="xl:col-span-2">
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Buscar</label>
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="Orden, item, código, marca..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden sistema</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Orden..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden cliente</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente') }}"
                                               placeholder="Orden cliente..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Marca</label>
                                        <input type="text"
                                               name="marca"
                                               value="{{ request('marca') }}"
                                               placeholder="Marca..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Nombre</label>
                                        <input type="text"
                                               name="nombre"
                                               value="{{ request('nombre') }}"
                                               placeholder="Nombre..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Código producto</label>
                                        <input type="text"
                                               name="codigo_producto"
                                               value="{{ request('codigo_producto') }}"
                                               placeholder="Código..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="flex items-center">
                                        <label class="inline-flex items-center gap-2 text-xs font-semibold theme-text">
                                            <input type="checkbox"
                                                   name="solo_con_saldo"
                                                   value="1"
                                                   class="rounded border theme-border"
                                                   @checked(request()->boolean('solo_con_saldo'))>
                                            Solo con saldo
                                        </label>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button type="submit"
                                                class="gooey-action inline-flex items-center justify-center px-3 py-2 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition"
                                                style="width: auto !important; white-space: nowrap;">
                                            Filtrar
                                        </button>
                                        <a href="{{ route('ordenes.index') }}"
                                           class="theme-button-secondary inline-flex items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold border theme-border transition">
                                            Limpiar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-hidden">
                            <div id="ordenesTableContainer">
                                @include('ordenes.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    {{-- Modal Generar viñetas --}}
    <div id="ordenGenerarModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-md overflow-hidden rounded-3xl border theme-border shadow-2xl">
            <div class="flex items-center justify-between border-b theme-border px-5 py-3.5">
                <div class="flex items-center gap-2">
                    <span class="theme-badge inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold border">
                        Viñetas
                    </span>
                    <h2 class="theme-title text-base font-bold">Generar viñetas</h2>
                </div>
                <button type="button"
                        id="ordenGenerarModalClose"
                        class="theme-button-secondary inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border theme-border text-base font-bold transition hover:opacity-80 cursor-pointer"
                        aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="{{ route('ordenes.generar') }}" class="p-5 space-y-3">
                @csrf

                <input type="hidden" name="id_pendiente" id="generar_id_pendiente">
                <input type="hidden" name="item" id="generar_item">
                <input type="hidden" name="codigo_producto" id="generar_codigo_producto">
                <input type="hidden" name="marca" id="generar_marca">
                <input type="hidden" name="nombre" id="generar_nombre">
                <input type="hidden" name="vitola" id="generar_vitola">
                <input type="hidden" name="capa" id="generar_capa">
                <input type="hidden" name="tipo_empaque" id="generar_tipo_empaque">
                <input type="hidden" name="orden_del_sistema" id="generar_orden_del_sistema">
                <input type="hidden" name="orden" id="generar_orden">
                <input type="hidden" name="mes" id="generar_mes">

                <div class="rounded-xl border theme-border theme-soft px-3 py-2">
                    <p class="theme-text text-xs">Orden</p>
                    <p class="theme-title text-sm font-bold" id="generar_resumen">—</p>
                </div>

                <div>
                    <label for="generar_area" class="theme-text block text-xs font-semibold mb-1">Área de la viñeta</label>
                    <select name="area" id="generar_area"
                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                        @foreach($areas as $clave => $config)
                            <option value="{{ $clave }}">{{ $config['titulo'] ?? $clave }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="generar_cantidad" class="theme-text block text-xs font-semibold mb-1">Cantidad de viñetas</label>
                        <input type="number" name="cantidad" id="generar_cantidad" min="1" max="500" value="1" required
                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title">
                    </div>

                    <div>
                        <label for="generar_cantidad_puros" class="theme-text block text-xs font-semibold mb-1">Puros por viñeta</label>
                        <input type="number" name="cantidad_puros" id="generar_cantidad_puros" min="0" max="100000" value="250" required
                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button"
                            id="ordenGenerarModalCancel"
                            class="theme-button-secondary px-4 py-2 rounded-xl text-sm font-semibold border theme-border transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white transition"
                            style="background:#0f766e">
                        Generar e imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('ordenGenerarModal');

            if (!modal) {
                return;
            }

            const setValue = (id, value) => {
                const input = document.getElementById(id);

                if (input) {
                    input.value = value || '';
                }
            };

            const abrir = (btn) => {
                setValue('generar_id_pendiente', btn.dataset.idPendiente);
                setValue('generar_item', btn.dataset.item);
                setValue('generar_codigo_producto', btn.dataset.codigoProducto);
                setValue('generar_marca', btn.dataset.marca);
                setValue('generar_nombre', btn.dataset.nombre);
                setValue('generar_vitola', btn.dataset.vitola);
                setValue('generar_capa', btn.dataset.capa);
                setValue('generar_tipo_empaque', btn.dataset.tipoEmpaque);
                setValue('generar_orden_del_sistema', btn.dataset.ordenSistema);
                setValue('generar_orden', btn.dataset.orden);
                setValue('generar_mes', btn.dataset.mes);

                const resumen = document.getElementById('generar_resumen');

                if (resumen) {
                    resumen.textContent = [
                        'OS ' + (btn.dataset.ordenSistema || '-'),
                        'OC ' + (btn.dataset.orden || '-'),
                        btn.dataset.item || '',
                        btn.dataset.marca || '',
                        btn.dataset.nombre || '',
                    ].filter(Boolean).join(' · ');
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');

            };

            const cerrar = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-generar-vinetas-orden');

                if (btn) {
                    e.preventDefault();
                    abrir(btn);
                }
            });

            document.getElementById('ordenGenerarModalClose')?.addEventListener('click', cerrar);
            document.getElementById('ordenGenerarModalCancel')?.addEventListener('click', cerrar);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    cerrar();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    cerrar();
                }
            });
        });
    </script>
</body>
</html>
