<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas por orden | Sistema de Empaque</title>

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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas por orden
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Gestión de viñetas asignadas por orden y códigos QR únicos.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $vinetasPorOrden->total() }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vinetas-por-orden.index') }}"
                                  class="vinetas-ajax-filter-form">
                                <div class="vinetas-filter-row grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-2 items-end">
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Código QR / ID</label>
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="QR o ID..."
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

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Item</label>
                                        <input type="text"
                                               name="item"
                                               value="{{ request('item') }}"
                                               placeholder="Item..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden sistema</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Orden sistema..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente') }}"
                                               placeholder="Orden..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>
                                </div>

                                <div class="vinetas-filter-actions mt-3"
                                     style="display: flex !important; grid-column: auto !important; justify-content: flex-end; gap: 0.5rem; padding-top: 0 !important;">
                                    <a href="{{ route('vinetas-por-orden.index') }}"
                                       class="vinetas-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2 rounded-xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition"
                                       style="width: auto !important; white-space: nowrap;">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-3 py-2 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition"
                                            style="width: auto !important; white-space: nowrap;">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetasPorOrdenTableContainer">
                                @include('vinetas-por-orden.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetasPorOrdenTableLoader"
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
                    Cargando viñetas por orden...
                </p>
            </div>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetasPorOrdenTableContainer');
            const getFilterForm = () => document.querySelector('.vinetas-ajax-filter-form');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;

            let stickyHeaderClone = null;
            let stickyHeaderEventsBound = false;
            let floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');
            let floatingScrollEventsBound = false;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;

            const getTableScroll = () => document.querySelector('#vinetasPorOrdenTableContainer .vinetas-table-scroll');

            const restoreTableScroll = (scrollLeft) => {
                const scroll = getTableScroll();

                if (!scroll) {
                    return;
                }

                const maxScrollLeft = Math.max(scroll.scrollWidth - scroll.clientWidth, 0);

                scroll.scrollLeft = Math.min(scrollLeft, maxScrollLeft);

                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (floatingScroll) {
                    floatingScroll.scrollLeft = scroll.scrollLeft;
                }

                syncStickyHeaderClone();
                updateFloatingScroll();
            };

            const removeStickyHeaderClone = () => {
                stickyHeaderClone?.remove();
                stickyHeaderClone = null;
            };

            const syncStickyHeaderClone = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header || !stickyHeaderClone) {
                    return;
                }

                const scrollRect = scroll.getBoundingClientRect();
                const tableRect = table.getBoundingClientRect();
                const headerRect = header.getBoundingClientRect();
                const headerHeight = headerRect.height;
                const topbarBottom = getTopbarBottom();
                const shouldShow = headerRect.top < topbarBottom
                    && tableRect.bottom > topbarBottom + headerHeight;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    return;
                }

                stickyHeaderClone.style.left = `${scrollRect.left}px`;
                stickyHeaderClone.style.width = `${scrollRect.width}px`;
                stickyHeaderClone.style.top = `${topbarBottom}px`;

                const cloneTable = stickyHeaderClone.querySelector('table');
                if (cloneTable) {
                    cloneTable.style.width = `${table.scrollWidth}px`;
                    cloneTable.style.minWidth = `${table.scrollWidth}px`;
                    cloneTable.style.transform = `translateX(${-scroll.scrollLeft}px)`;
                }

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];
                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;
                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                        th.style.maxWidth = `${width}px`;
                    }
                });
            };

            const syncFloatingScrollFromTable = () => {
                const scroll = getTableScroll();

                if (!scroll || !floatingScroll || syncingFloatingScroll) {
                    return;
                }

                syncingFloatingScroll = true;
                floatingScroll.scrollLeft = scroll.scrollLeft;
                syncingFloatingScroll = false;
            };

            const updateFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');

                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

                const rect = scroll.getBoundingClientRect();
                const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
                const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > 80;

                floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

                if (!hasHorizontalOverflow || !isTableVisible) {
                    return;
                }

                floatingScroll.style.width = '100%';

                const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');

                if (inner) {
                    inner.style.width = `${table.scrollWidth}px`;
                }

                syncFloatingScrollFromTable();
            };

            const initFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

                scroll.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    syncFloatingScrollFromTable();
                }, { passive: true });

                if (boundFloatingScroll !== floatingScroll) {
                    floatingScroll.addEventListener('scroll', () => {
                        const currentScroll = getTableScroll();

                        if (!currentScroll || syncingFloatingScroll) {
                            return;
                        }

                        syncingFloatingScroll = true;
                        currentScroll.scrollLeft = floatingScroll.scrollLeft;
                        syncStickyHeaderClone();
                        syncingFloatingScroll = false;
                    }, { passive: true });

                    boundFloatingScroll = floatingScroll;
                }

                if (!floatingScrollEventsBound) {
                    window.addEventListener('scroll', updateFloatingScroll, { passive: true });
                    window.addEventListener('resize', () => requestAnimationFrame(updateFloatingScroll));
                    floatingScrollEventsBound = true;
                }

                requestAnimationFrame(updateFloatingScroll);
            };

            const initStickyHeaderClone = () => {
                removeStickyHeaderClone();

                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header) {
                    return;
                }

                stickyHeaderClone = document.createElement('div');
                stickyHeaderClone.className = 'vinetas-sticky-header-clone';

                const cloneTable = document.createElement('table');
                cloneTable.className = table.className;
                cloneTable.appendChild(header.cloneNode(true));
                stickyHeaderClone.appendChild(cloneTable);

                document.body.appendChild(stickyHeaderClone);

                if (!stickyHeaderEventsBound) {
                    window.addEventListener('scroll', syncStickyHeaderClone, { passive: true });
                    window.addEventListener('resize', syncStickyHeaderClone);

                    let sidebarAnimFrame = null;
                    const animateStickyHeader = () => {
                        const startTime = performance.now();
                        const duration = 350;
                        const step = (now) => {
                            syncStickyHeaderClone();
                            updateFloatingScroll();
                            if (now - startTime < duration) {
                                sidebarAnimFrame = requestAnimationFrame(step);
                            }
                        };
                        if (sidebarAnimFrame) cancelAnimationFrame(sidebarAnimFrame);
                        sidebarAnimFrame = requestAnimationFrame(step);
                    };

                    window.addEventListener('sidebar-toggled', animateStickyHeader);
                    window.addEventListener('open-mobile-sidebar', animateStickyHeader);
                    window.addEventListener('close-mobile-sidebar', animateStickyHeader);
                    stickyHeaderEventsBound = true;
                }

                if (window.ResizeObserver) {
                    const ro = new ResizeObserver(() => {
                        requestAnimationFrame(() => {
                            syncStickyHeaderClone();
                            updateFloatingScroll();
                        });
                    });
                    const containerEl = getTableContainer();
                    if (containerEl) ro.observe(containerEl);
                    const mainEl = document.querySelector('main');
                    if (mainEl) ro.observe(mainEl);
                }

                syncStickyHeaderClone();
            };

            const initTableFeatures = () => {
                initFloatingScroll();
                initStickyHeaderClone();
            };

            const showTableLoader = () => {
                document.getElementById('vinetasPorOrdenTableLoader')?.classList.remove('hidden');
            };

            const hideTableLoader = () => {
                document.getElementById('vinetasPorOrdenTableLoader')?.classList.add('hidden');
            };

            const loadTableHtml = async (targetUrl, { pushState = true, previousScrollLeft = null } = {}) => {
                const container = getTableContainer();

                if (!container) {
                    return;
                }

                const currentScrollLeft = previousScrollLeft ?? (getTableScroll()?.scrollLeft || 0);

                showTableLoader();

                try {
                    const response = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al cargar la tabla de viñetas por orden.');
                    }

                    const html = await response.text();
                    container.innerHTML = html;

                    if (pushState) {
                        window.history.pushState({}, '', targetUrl);
                    }

                    initTableFeatures();
                    restoreTableScroll(currentScrollLeft);
                } catch (error) {
                    window.location.href = targetUrl;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('.vinetas-ajax-filter-form, .vinetas-ajax-per-page-form');

                if (!form) {
                    return;
                }

                event.preventDefault();

                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (typeof value === 'string' && value.trim() !== '') {
                        params.append(key, value.trim());
                    }
                }

                const targetUrl = `${form.action.split('?')[0]}?${params.toString()}`;
                loadTableHtml(targetUrl, { previousScrollLeft: getTableScroll()?.scrollLeft || 0 });
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest('.vinetas-ajax-pagination a, .vinetas-ajax-table-link, .vinetas-ajax-clear-filters');

                if (!link || !link.href) {
                    return;
                }

                event.preventDefault();

                if (link.classList.contains('vinetas-ajax-clear-filters')) {
                    const form = getFilterForm();
                    if (form) {
                        form.querySelectorAll('input[type="text"]').forEach((input) => {
                            input.value = '';
                        });
                    }
                }

                loadTableHtml(link.href, { previousScrollLeft: getTableScroll()?.scrollLeft || 0 });
            });

            window.addEventListener('popstate', () => {
                loadTableHtml(window.location.href, { pushState: false });
            });

            window.reloadVinetasPorOrdenTable = () => {
                loadTableHtml(window.location.href, { pushState: false });
            };

            initTableFeatures();
        });
    </script>

    <!-- Modal Ver QR -->
    <div id="vinetaQrModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-xs overflow-hidden rounded-3xl border theme-border shadow-2xl">
            <div class="flex items-center justify-between border-b theme-border px-5 py-3.5">
                <div class="flex items-center gap-2">
                    <span class="theme-badge inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold border">
                        QR
                    </span>
                    <h2 class="theme-title text-base font-bold" id="modalQrTitle">
                        QR
                    </h2>
                </div>
                <button type="button"
                        id="vinetaQrModalClose"
                        class="theme-button-secondary inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border theme-border text-base font-bold transition hover:opacity-80 cursor-pointer"
                        aria-label="Cerrar">×</button>
            </div>

            <div class="p-6 flex flex-col items-center justify-center">
                <!-- QR Code (fondo blanco para lectura óptima del escáner) -->
                <div class="p-3 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-center">
                    <canvas id="modalQrCanvas" class="w-52 h-52 block"></canvas>
                </div>

                <div class="w-full mt-5">
                    <button type="button"
                            id="vinetaQrModalCloseBtn"
                            class="w-full py-2 px-3 rounded-xl border theme-border text-xs font-semibold theme-button-secondary transition text-center cursor-pointer">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos Dark Navy para el modal de Crear Viñeta */
        html.dark-navy #crearVinetaPorOrdenModal .theme-card {
            background-color: #111c33 !important;
            border-color: #263650 !important;
            color: #e5e7eb !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-header,
        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-footer {
            background-color: #16233d !important;
            border-color: #263650 !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-body {
            background-color: #111c33 !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-input {
            background-color: rgba(15, 23, 42, 0.95) !important;
            border-color: #263650 !important;
            color: #f8fafc !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-input:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal .crear-modal-badge {
            background-color: #16233d !important;
            border-color: #263650 !important;
            color: #94a3b8 !important;
        }

        html.dark-navy #crearVinetaPorOrdenModal #crearModalErrorAlert {
            background-color: rgba(153, 27, 27, 0.25) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
            color: #fca5a5 !important;
        }

        /* Estilos Light para el modal de Crear Viñeta */
        html:not(.dark-navy) #crearVinetaPorOrdenModal .theme-card {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #0b1220 !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-header,
        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-footer {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-body {
            background-color: #ffffff !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-input {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0b1220 !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal .crear-modal-badge {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #475569 !important;
        }

        html:not(.dark-navy) #crearVinetaPorOrdenModal #crearModalErrorAlert {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #991b1b !important;
        }
    </style>

    <!-- Modal Crear Viñeta por Orden -->
    @if(!auth()->user()?->esSoloSupervisor())
    <div id="crearVinetaPorOrdenModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm overflow-y-auto">
        <div class="theme-card w-full max-w-2xl rounded-3xl border theme-border shadow-2xl overflow-hidden my-auto max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="crear-modal-header flex items-start justify-between border-b theme-border px-6 py-4 shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="crear-modal-badge inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold border">
                            Crear Viñeta
                        </span>
                        <span class="text-xs theme-text font-medium">
                            A partir del registro <strong id="crearModalOrigenBadge" class="theme-title font-bold">#</strong>
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="crear-modal-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border">
                            Nuevo ID: <strong id="crearModalNuevoId" class="theme-title font-bold">#{{ $siguienteId ?? '' }}</strong>
                        </span>
                        <span class="crear-modal-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border">
                            Nuevo QR: <strong id="crearModalNuevoQr" class="theme-title font-bold">{{ $siguienteQr ?? '' }}</strong>
                        </span>
                    </div>
                </div>

                <button type="button"
                        id="crearVinetaPorOrdenClose"
                        class="theme-button-secondary inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border theme-border text-xl font-bold transition hover:opacity-80 cursor-pointer"
                        aria-label="Cerrar">×</button>
            </div>

            <!-- Modal Form Body -->
            <form id="crearVinetaPorOrdenForm" method="POST" action="{{ route('vinetas-por-orden.store') }}" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <input type="hidden" name="origen_id" id="modal_origen_id">
                <input type="hidden" name="fecha" id="modal_fecha">
                <input type="hidden" name="cantidad_puros" id="modal_cantidad_puros">
                <input type="hidden" name="estado" id="modal_estado">

                <div class="crear-modal-body p-6 overflow-y-auto space-y-4 flex-1">
                    <!-- Error Alert -->
                    <div id="crearModalErrorAlert" class="hidden p-3 rounded-xl text-xs font-semibold flex items-start gap-2.5 border">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span id="crearModalErrorText"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Item</label>
                            <input type="text"
                                   name="item"
                                   id="modal_item"
                                   placeholder="Item..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Código producto</label>
                            <input type="text"
                                   name="codigo_producto"
                                   id="modal_codigo_producto"
                                   placeholder="P-01947..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Marca</label>
                            <input type="text"
                                   name="marca"
                                   id="modal_marca"
                                   placeholder="Marca..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Nombre</label>
                            <input type="text"
                                   name="nombre"
                                   id="modal_nombre"
                                   placeholder="Nombre..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Vitola</label>
                            <input type="text"
                                   name="vitola"
                                   id="modal_vitola"
                                   placeholder="6-1/8X50..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Capa</label>
                            <input type="text"
                                   name="capa"
                                   id="modal_capa"
                                   placeholder="INDONESIA..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Orden sistema</label>
                            <input type="text"
                                   name="orden_del_sistema"
                                   id="modal_orden_del_sistema"
                                   placeholder="3606..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Orden cliente</label>
                            <input type="text"
                                   name="orden"
                                   id="modal_orden"
                                   placeholder="111394..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Tipo de empaque</label>
                            <input type="text"
                                   name="tipo_empaque"
                                   id="modal_tipo_empaque"
                                   placeholder="Display/24..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>

                        <div>
                            <label class="theme-text block text-xs font-bold mb-1">Mes</label>
                            <input type="text"
                                   name="mes"
                                   id="modal_mes"
                                   placeholder="MAYO 2026..."
                                   class="crear-modal-input theme-input w-full rounded-xl border px-3 py-2 text-xs sm:text-sm theme-title outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="crear-modal-footer flex items-center justify-end gap-3 border-t theme-border px-6 py-3.5 shrink-0">
                    <button type="button"
                            id="crearVinetaPorOrdenCancel"
                            class="px-4 py-2 rounded-xl border theme-border text-xs sm:text-sm font-semibold theme-button-secondary transition text-center cursor-pointer">
                        Cancelar
                    </button>

                    <button type="submit"
                            id="btnGuardarVinetaOrden"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-[#0f172a] text-white text-xs sm:text-sm font-bold hover:bg-[#1e293b] shadow-sm transition cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Guardar Viñeta</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const qrModal = document.getElementById('vinetaQrModal');
            const qrCanvas = document.getElementById('modalQrCanvas');
            const qrTitle = document.getElementById('modalQrTitle');

            const openQrModal = (btn) => {
                const qrCode = btn.dataset.qrCode;
                if (!qrCode) return;

                if (qrTitle) qrTitle.textContent = qrCode;

                if (window.QRCode && qrCanvas) {
                    window.QRCode.toCanvas(qrCanvas, String(qrCode), {
                        width: 208,
                        margin: 1,
                        color: {
                            dark: '#000000',
                            light: '#ffffff'
                        }
                    }, function (error) {
                        if (error) console.error(error);
                    });
                }

                if (qrModal) {
                    qrModal.classList.remove('hidden');
                    qrModal.classList.add('flex');
                }
            };

            const closeQrModal = () => {
                if (qrModal) {
                    qrModal.classList.add('hidden');
                    qrModal.classList.remove('flex');
                }
            };

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-ver-qr');
                if (btn) {
                    e.preventDefault();
                    openQrModal(btn);
                }
            });

            document.getElementById('vinetaQrModalClose')?.addEventListener('click', closeQrModal);
            document.getElementById('vinetaQrModalCloseBtn')?.addEventListener('click', closeQrModal);

            qrModal?.addEventListener('click', (e) => {
                if (e.target === qrModal) {
                    closeQrModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && qrModal && !qrModal.classList.contains('hidden')) {
                    closeQrModal();
                }
            });

            // Lógica para Modal Crear Viñeta por Orden
            const crearModal = document.getElementById('crearVinetaPorOrdenModal');
            const crearForm = document.getElementById('crearVinetaPorOrdenForm');
            const errorAlert = document.getElementById('crearModalErrorAlert');
            const errorText = document.getElementById('crearModalErrorText');
            const btnGuardar = document.getElementById('btnGuardarVinetaOrden');

            let registroOriginal = {};

            const openCrearModal = async (btn) => {
                if (errorAlert) errorAlert.classList.add('hidden');

                registroOriginal = {
                    id: btn.dataset.id || '',
                    codigo_qr: btn.dataset.codigoQr || '',
                    fecha: btn.dataset.fecha || '',
                    item: (btn.dataset.item || '').trim(),
                    codigo_producto: (btn.dataset.codigoProducto || '').trim(),
                    marca: (btn.dataset.marca || '').trim(),
                    nombre: (btn.dataset.nombre || '').trim(),
                    vitola: (btn.dataset.vitola || '').trim(),
                    capa: (btn.dataset.capa || '').trim(),
                    orden_del_sistema: (btn.dataset.ordenDelSistema || '').trim(),
                    orden: (btn.dataset.orden || '').trim(),
                    tipo_empaque: (btn.dataset.tipoEmpaque || '').trim(),
                    mes: (btn.dataset.mes || '').trim(),
                    cantidad_puros: parseInt(btn.dataset.cantidadPuros || '0', 10),
                    estado: (btn.dataset.estado || 'activo').trim().toLowerCase(),
                };

                document.getElementById('modal_origen_id').value = registroOriginal.id;
                document.getElementById('modal_fecha').value = registroOriginal.fecha;
                document.getElementById('modal_cantidad_puros').value = registroOriginal.cantidad_puros;
                document.getElementById('modal_estado').value = registroOriginal.estado || 'activo';

                document.getElementById('modal_item').value = registroOriginal.item;
                document.getElementById('modal_codigo_producto').value = registroOriginal.codigo_producto;
                document.getElementById('modal_marca').value = registroOriginal.marca;
                document.getElementById('modal_nombre').value = registroOriginal.nombre;
                document.getElementById('modal_vitola').value = registroOriginal.vitola;
                document.getElementById('modal_capa').value = registroOriginal.capa;
                document.getElementById('modal_orden_del_sistema').value = registroOriginal.orden_del_sistema;
                document.getElementById('modal_orden').value = registroOriginal.orden;
                document.getElementById('modal_tipo_empaque').value = registroOriginal.tipo_empaque;
                document.getElementById('modal_mes').value = registroOriginal.mes;

                document.getElementById('crearModalOrigenBadge').textContent = '#' + registroOriginal.id + ' (' + (registroOriginal.codigo_qr || 'N/A') + ')';

                try {
                    const resp = await fetch('{{ route('vinetas-por-orden.siguiente-info') }}');
                    if (resp.ok) {
                        const info = await resp.json();
                        document.getElementById('crearModalNuevoId').textContent = '#' + info.siguiente_id;
                        document.getElementById('crearModalNuevoQr').textContent = info.siguiente_qr;
                    }
                } catch (e) {
                    console.error('Error fetching siguiente info:', e);
                }

                if (crearModal) {
                    crearModal.classList.remove('hidden');
                    crearModal.classList.add('flex');
                }
            };

            const closeCrearModal = () => {
                if (crearModal) {
                    crearModal.classList.add('hidden');
                    crearModal.classList.remove('flex');
                }
            };

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-crear-vineta-orden');
                if (btn) {
                    e.preventDefault();
                    openCrearModal(btn);
                }
            });

            document.getElementById('crearVinetaPorOrdenClose')?.addEventListener('click', closeCrearModal);
            document.getElementById('crearVinetaPorOrdenCancel')?.addEventListener('click', closeCrearModal);

            crearModal?.addEventListener('click', (e) => {
                if (e.target === crearModal) {
                    closeCrearModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && crearModal && !crearModal.classList.contains('hidden')) {
                    closeCrearModal();
                }
            });

            crearForm?.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (errorAlert) errorAlert.classList.add('hidden');

                const currentData = {
                    item: document.getElementById('modal_item').value.trim(),
                    codigo_producto: document.getElementById('modal_codigo_producto').value.trim(),
                    marca: document.getElementById('modal_marca').value.trim(),
                    nombre: document.getElementById('modal_nombre').value.trim(),
                    vitola: document.getElementById('modal_vitola').value.trim(),
                    capa: document.getElementById('modal_capa').value.trim(),
                    orden_del_sistema: document.getElementById('modal_orden_del_sistema').value.trim(),
                    orden: document.getElementById('modal_orden').value.trim(),
                    tipo_empaque: document.getElementById('modal_tipo_empaque').value.trim(),
                    mes: document.getElementById('modal_mes').value.trim(),
                };

                let hasChanges = false;
                for (const key of Object.keys(currentData)) {
                    if (currentData[key].toLowerCase() !== (registroOriginal[key] || '').toLowerCase()) {
                        hasChanges = true;
                        break;
                    }
                }

                if (!hasChanges) {
                    if (errorAlert && errorText) {
                        errorText.textContent = 'No puedes crear la viñeta con exactamente los mismos datos. Debes modificar al menos un campo.';
                        errorAlert.classList.remove('hidden');
                    }
                    return false;
                }

                btnGuardar.disabled = true;
                const originalBtnHtml = btnGuardar.innerHTML;
                btnGuardar.innerHTML = '<span>Guardando...</span>';

                try {
                    const formData = new FormData(crearForm);
                    const response = await fetch(crearForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        const msg = result.errors?.general?.[0] || result.message || 'Error al guardar la viñeta.';
                        if (errorAlert && errorText) {
                            errorText.textContent = msg;
                            errorAlert.classList.remove('hidden');
                        }
                        return;
                    }

                    closeCrearModal();

                    if (typeof mostrarToast === 'function') {
                        mostrarToast('success', result.message);
                    } else if (typeof appSwal === 'function') {
                        appSwal({ icon: 'success', title: 'Éxito', text: result.message });
                    }

                    if (window.reloadVinetasPorOrdenTable) {
                        window.reloadVinetasPorOrdenTable();
                    } else {
                        window.location.reload();
                    }
                } catch (err) {
                    console.error(err);
                    if (errorAlert && errorText) {
                        errorText.textContent = 'Ocurrió un error al procesar la solicitud.';
                        errorAlert.classList.remove('hidden');
                    }
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = originalBtnHtml;
                }
            });
        });
    </script>
</body>
</html>
