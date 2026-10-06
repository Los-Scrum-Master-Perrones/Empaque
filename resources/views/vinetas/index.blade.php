<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas | Sistema de Empaque</title>

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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h2m4 0h-2m-4 4h6" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Información de QR sincronizada desde la API de empaque.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $vinetas->total() }}</strong>
                                        </span>
                                    </div>
                                </div>

                                @if(!auth()->user()?->esSoloSupervisor())
                                <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                                    <div class="relative">
                                        <button type="button"
                                                id="vinetasNotificationButton"
                                                data-url="{{ route('vinetas.notificaciones') }}"
                                                class="theme-button-secondary vinetas-notification-button inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold border theme-border transition shadow-sm"
                                                title="Viñetas impresas nuevas"
                                                aria-expanded="false">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a3 3 0 0 1-5.714 0" />
                                            </svg>

                                            <span id="vinetasNotificationLabel">Notificaciones</span>

                                            <span id="vinetasNotificationBadge"
                                                  class="hidden min-w-6 items-center justify-center rounded-full bg-[#f59e0b] px-2 py-0.5 text-xs font-bold text-white">
                                                0
                                            </span>
                                        </button>

                                        <div id="vinetasNotificationMenu"
                                             class="theme-card hidden absolute right-0 top-full z-50 mt-2 w-72 rounded-2xl border theme-border bg-white p-3 shadow-2xl">
                                            <div class="flex items-start gap-3">
                                                <div class="vinetas-notification-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a3 3 0 0 1-5.714 0" />
                                                    </svg>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <p class="theme-title text-sm font-bold">
                                                        Viñetas impresas nuevas
                                                    </p>

                                                    <p id="vinetasNotificationMessage" class="theme-text mt-1 text-xs leading-5">
                                                        Hay viñetas pendientes de sincronizar.
                                                    </p>

                                                    <p id="vinetasNotificationTime" class="theme-text mt-2 text-[11px] opacity-75">
                                                        Última revisión: N/A
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-3 flex items-center justify-between gap-2 border-t theme-border pt-3">
                                                <span class="theme-badge inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold">
                                                    <span id="vinetasNotificationCount">0</span>&nbsp;pendientes
                                                </span>

                                                <button type="button"
                                                        id="vinetasNotificationRefresh"
                                                        class="theme-button-secondary rounded-xl border theme-border px-3 py-1.5 text-xs font-semibold transition">
                                                    Revisar ahora
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST"
                                          action="{{ route('vinetas.sincronizar') }}"
                                          class="form-sincronizar-vinetas w-full sm:w-auto">
                                        @csrf

                                        <button type="submit"
                                                class="gooey-action btn-sincronizar-vinetas inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition disabled:opacity-70 disabled:cursor-not-allowed">
                                            <div class="loader-sincronizar hidden"></div>

                                            <span class="texto-sincronizar-vinetas">
                                                Sincronizar viñetas
                                            </span>
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vinetas.index') }}"
                                  class="vinetas-ajax-filter-form">
                                <div class="vinetas-filter-row flex flex-nowrap items-end gap-1.5 xl:gap-2 w-full">
                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">ID API</label>
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="ID API..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Marca</label>
                                        <input type="text"
                                               name="marca"
                                               value="{{ request('marca') }}"
                                               placeholder="Marca..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Nombre</label>
                                        <input type="text"
                                               name="nombre"
                                               value="{{ request('nombre') }}"
                                               placeholder="Nombre..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Código producto</label>
                                        <input type="text"
                                               name="codigo_producto"
                                               value="{{ request('codigo_producto') }}"
                                               placeholder="Código..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Capa</label>
                                        <input type="text"
                                               name="capa"
                                               value="{{ request('capa') }}"
                                               placeholder="Capa..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Item</label>
                                        <input type="text"
                                               name="item"
                                               value="{{ request('item') }}"
                                               placeholder="Item..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Orden sistema</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Orden sistema..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <label class="theme-text block text-[11px] sm:text-xs font-semibold mb-1 truncate">Orden</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente') }}"
                                               placeholder="Orden..."
                                               class="w-full rounded-xl border theme-border bg-white px-2 py-1.5 text-xs sm:text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>
                                </div>

                                <div class="vinetas-filter-actions flex items-center justify-end gap-2 mt-2.5 pt-0.5">
                                    <a href="{{ route('vinetas.index') }}"
                                       class="vinetas-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-1.5 rounded-xl bg-white text-[#0b1220] text-xs sm:text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition whitespace-nowrap">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-4 py-1.5 rounded-xl bg-[#0f172a] text-white text-xs sm:text-sm font-semibold hover:bg-[#1e293b] transition whitespace-nowrap">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetasTableContainer">
                                @include('vinetas.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetasSyncOverlay"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/45 backdrop-blur-sm px-4">
        <div class="theme-card w-full max-w-md rounded-3xl bg-white border theme-border p-6 shadow-2xl text-center">
            <div class="mx-auto mb-5 flex justify-center">
                <div class="sync-loader"></div>
            </div>

            <h3 class="theme-title text-lg font-bold">
                Sincronizando viñetas
            </h3>

            <p class="theme-text text-sm mt-2">
                Actualizando información de QR desde la API de empaque.
            </p>

            <p class="theme-text text-xs mt-4">
                No cierres esta ventana mientras termina la sincronización.
            </p>
        </div>
    </div>

    <div id="vinetasTableLoader"
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
                    Cargando viñetas...
                </p>
            </div>
        </div>
    </div>

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
                        ID 23792
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

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetasTableContainer');
            const getFilterForm = () => document.querySelector('.vinetas-ajax-filter-form');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;

            let stickyHeaderClone = null;
            let stickyHeaderEventsBound = false;
            let floatingScroll = document.getElementById('vinetasFloatingScroll');
            let floatingScrollEventsBound = false;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;

            const getTableScroll = () => document.querySelector('#vinetasTableContainer .vinetas-table-scroll');

            const restoreTableScroll = (scrollLeft) => {
                const scroll = getTableScroll();

                if (!scroll) {
                    return;
                }

                const maxScrollLeft = Math.max(scroll.scrollWidth - scroll.clientWidth, 0);

                scroll.scrollLeft = Math.min(scrollLeft, maxScrollLeft);

                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                const footer = document.querySelector('#vinetasTableContainer .vinetas-table-footer');
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

                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                stickyHeaderClone.innerHTML = `
                    <div class="vinetas-sticky-header-inner">
                        <table class="w-full text-sm">
                            ${header.outerHTML}
                        </table>
                    </div>
                `;

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];

                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;

                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                    }
                });

                document.body.appendChild(stickyHeaderClone);

                if (!stickyHeaderEventsBound) {
                    document.addEventListener('scroll', syncStickyHeaderClone, { passive: true, capture: true });
                    window.addEventListener('resize', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    });

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

            const showTableLoader = () => {
                const container = getTableContainer();
                const loader = document.getElementById('vinetasTableLoader');

                if (loader) {
                    const header = document.querySelector('#vinetasTableContainer .productos-sticky-head');

                    if (header) {
                        const rect = header.getBoundingClientRect();
                        const top = Math.max(rect.bottom + 12, getTopbarBottom() + 12);

                        loader.style.top = `${top}px`;
                    }

                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                const inner = container?.querySelector('#vinetasTableInner');

                if (inner) {
                    inner.classList.add('productos-table-loading');
                }
            };

            const hideTableLoader = () => {
                const loader = document.getElementById('vinetasTableLoader');
                const inner = document.getElementById('vinetasTableInner');

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

            const loadVinetasTable = async (url, options = {}) => {
                const container = getTableContainer();

                if (!container) {
                    return;
                }

                const scrollLeft = options.preserveHorizontalScroll
                    ? (getTableScroll()?.scrollLeft || floatingScroll?.scrollLeft || 0)
                    : 0;

                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo actualizar la tabla de viñetas');
                    }

                    container.innerHTML = await response.text();
                    initStickyHeaderClone();
                    initFloatingScroll();

                    if (options.preserveHorizontalScroll) {
                        requestAnimationFrame(() => {
                            restoreTableScroll(scrollLeft);
                            requestAnimationFrame(() => restoreTableScroll(scrollLeft));
                        });
                    }

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

                const isSortLink = link.classList.contains('vinetas-ajax-table-link');
                const isPaginationLink = link.closest('#vinetasTableContainer .vinetas-ajax-pagination');
                const isClearLink = link.classList.contains('vinetas-ajax-clear-filters');

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

                loadVinetasTable(link.href, {
                    preserveHorizontalScroll: isSortLink,
                });
            });

            document.addEventListener('submit', (event) => {
                const filterForm = event.target.closest('.vinetas-ajax-filter-form');
                const perPageForm = event.target.closest('.vinetas-ajax-per-page-form');

                if (!filterForm && !perPageForm) {
                    return;
                }

                event.preventDefault();

                const form = filterForm || perPageForm;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                params.delete('page');

                loadVinetasTable(`${form.action}?${params.toString()}`);
            });

            window.addEventListener('popstate', () => {
                loadVinetasTable(window.location.href);
            });

            initStickyHeaderClone();
            initFloatingScroll();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.getElementById('vinetasNotificationButton');
            const badge = document.getElementById('vinetasNotificationBadge');
            const label = document.getElementById('vinetasNotificationLabel');
            const menu = document.getElementById('vinetasNotificationMenu');
            const message = document.getElementById('vinetasNotificationMessage');
            const time = document.getElementById('vinetasNotificationTime');
            const count = document.getElementById('vinetasNotificationCount');
            const refresh = document.getElementById('vinetasNotificationRefresh');

            if (!button || !badge || !label || !menu || !message || !time || !count || !refresh) {
                return;
            }

            const pollMs = 120000;
            let loading = false;
            let pendingCount = 0;

            const closeMenu = () => {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            };

            const toggleMenu = () => {
                const isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden', isOpen);
                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            };

            const setButtonState = (pending) => {
                label.textContent = pending > 0 ? 'Novedades' : 'Notificaciones';
                badge.textContent = pending;
                badge.classList.toggle('hidden', pending <= 0);
                badge.classList.toggle('inline-flex', pending > 0);
                button.classList.toggle('has-pending', pending > 0);
            };

            const updateNotification = (data) => {
                const pending = Number(data.pendientes || 0);
                pendingCount = pending;
                setButtonState(pending);

                if (!data.ok) {
                    message.textContent = data.mensaje || 'No se pudo revisar viñetas impresas nuevas.';
                } else if (pending > 0) {
                    message.textContent = data.mensaje || `${pending} viñetas impresas nuevas pendientes de sincronizar.`;
                } else {
                    message.textContent = data.mensaje || 'No hay viñetas impresas nuevas.';
                }

                count.textContent = pending;
                time.textContent = `Última revisión: ${data.ultima_revision || 'N/A'}`;
                button.title = `${data.mensaje || 'Revisión de viñetas impresas'} Última revisión: ${data.ultima_revision || 'N/A'}`;
            };

            const loadNotification = async (force = false) => {
                if (loading) {
                    return;
                }

                loading = true;
                refresh.disabled = true;
                refresh.classList.add('opacity-70', 'pointer-events-none');

                try {
                    const url = new URL(button.dataset.url, window.location.origin);

                    if (force) {
                        url.searchParams.set('force', '1');
                    }

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo revisar viñetas impresas');
                    }

                    updateNotification(await response.json());
                } catch (error) {
                    console.error(error);
                    pendingCount = 0;
                    setButtonState(0);
                    message.textContent = 'No se pudo revisar viñetas impresas nuevas.';
                    count.textContent = '0';
                    time.textContent = 'Última revisión: N/A';
                    button.title = 'No se pudo revisar viñetas impresas nuevas.';
                } finally {
                    loading = false;
                    refresh.disabled = false;
                    refresh.classList.remove('opacity-70', 'pointer-events-none');
                }
            };

            button.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleMenu();
            });

            menu.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            refresh.addEventListener('click', () => loadNotification(true));

            document.addEventListener('click', closeMenu);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            loadNotification();
            window.setInterval(() => loadNotification(), pollMs);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.form-sincronizar-vinetas').forEach((form) => {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('.btn-sincronizar-vinetas');
                    const text = form.querySelector('.texto-sincronizar-vinetas');
                    const loader = form.querySelector('.loader-sincronizar');
                    const overlay = document.getElementById('vinetasSyncOverlay');

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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const qrModal = document.getElementById('vinetaQrModal');
            const qrCanvas = document.getElementById('modalQrCanvas');
            const qrTitle = document.getElementById('modalQrTitle');

            const openQrModal = (btn) => {
                const apiId = btn.dataset.apiId;
                if (!apiId) return;

                if (qrTitle) qrTitle.textContent = `ID ${apiId}`;

                if (window.QRCode && qrCanvas) {
                    window.QRCode.toCanvas(qrCanvas, String(apiId), {
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
        });
    </script>
</body>
</html>
