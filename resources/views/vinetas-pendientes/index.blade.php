<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas pendientes | Sistema de Empaque</title>

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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas pendientes
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Viñetas pendientes de empaque para actividades de limpieza (sincronizadas desde API).
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $vinetasPendientes->total() }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vinetas-pendientes.index') }}"
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
                                    <a href="{{ route('vinetas-pendientes.index') }}"
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
                            <div id="vinetasPendientesTableContainer">
                                @include('vinetas-pendientes.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetasPendientesTableLoader"
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
                    Cargando viñetas pendientes...
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

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetasPendientesTableContainer');
            const getFilterForm = () => document.querySelector('.vinetas-ajax-filter-form');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;

            let stickyHeaderClone = null;
            let stickyHeaderEventsBound = false;
            let floatingScroll = document.getElementById('vinetasPendientesFloatingScroll');
            let floatingScrollEventsBound = false;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;

            const getTableScroll = () => document.querySelector('#vinetasPendientesTableContainer .vinetas-table-scroll');

            const restoreTableScroll = (scrollLeft) => {
                const scroll = getTableScroll();
                if (!scroll) return;

                const maxScrollLeft = Math.max(scroll.scrollWidth - scroll.clientWidth, 0);
                scroll.scrollLeft = Math.min(scrollLeft, maxScrollLeft);

                floatingScroll = document.getElementById('vinetasPendientesFloatingScroll');
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

                if (!scroll || !table || !header || !stickyHeaderClone) return;

                const tableRect = table.getBoundingClientRect();
                const headerRect = header.getBoundingClientRect();
                const scrollRect = scroll.getBoundingClientRect();
                const topbarBottom = getTopbarBottom();

                const shouldShow = headerRect.top < topbarBottom
                    && tableRect.bottom > topbarBottom + headerRect.height;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) return;

                stickyHeaderClone.style.top = `${topbarBottom}px`;
                stickyHeaderClone.style.left = `${scrollRect.left}px`;
                stickyHeaderClone.style.width = `${scrollRect.width}px`;

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
                    }
                });
            };

            const syncFloatingScrollFromTable = () => {
                const scroll = getTableScroll();
                if (!scroll || !floatingScroll || syncingFloatingScroll) return;

                syncingFloatingScroll = true;
                floatingScroll.scrollLeft = scroll.scrollLeft;
                syncingFloatingScroll = false;
            };

            const updateFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                floatingScroll = document.getElementById('vinetasPendientesFloatingScroll');

                if (!scroll || !table || !floatingScroll) return;

                const rect = scroll.getBoundingClientRect();
                const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
                const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > getTopbarBottom();

                floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

                if (!hasHorizontalOverflow || !isTableVisible) return;

                const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');
                if (inner) {
                    inner.style.width = `${table.scrollWidth}px`;
                }

                syncFloatingScrollFromTable();
            };

            const initTableFeatures = () => {
                removeStickyHeaderClone();

                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');
                floatingScroll = document.getElementById('vinetasPendientesFloatingScroll');

                if (!scroll || !table || !header || !floatingScroll) return;

                stickyHeaderClone = document.createElement('div');
                stickyHeaderClone.className = 'vinetas-sticky-header-clone';
                stickyHeaderClone.innerHTML = `
                    <div class="vinetas-sticky-header-inner">
                        <table class="w-full text-sm">${header.outerHTML}</table>
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

                scroll.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    syncFloatingScrollFromTable();
                }, { passive: true });

                if (boundFloatingScroll !== floatingScroll) {
                    floatingScroll.addEventListener('scroll', () => {
                        const currentScroll = getTableScroll();
                        if (!currentScroll || syncingFloatingScroll) return;

                        syncingFloatingScroll = true;
                        currentScroll.scrollLeft = floatingScroll.scrollLeft;
                        syncStickyHeaderClone();
                        syncingFloatingScroll = false;
                    }, { passive: true });

                    boundFloatingScroll = floatingScroll;
                }

                if (!stickyHeaderEventsBound) {
                    window.addEventListener('scroll', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    }, { passive: true });

                    window.addEventListener('resize', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    });

                    window.addEventListener('sidebar-toggled', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    });

                    stickyHeaderEventsBound = true;
                }

                requestAnimationFrame(() => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                });
            };

            const showTableLoader = () => {
                const loader = document.getElementById('vinetasPendientesTableLoader');
                const header = document.querySelector('#vinetasPendientesTableContainer .productos-sticky-head');

                if (loader && header) {
                    const rect = header.getBoundingClientRect();
                    loader.style.top = `${Math.max(rect.bottom + 12, getTopbarBottom() + 12)}px`;
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                getTableContainer()?.querySelector('#vinetasPendientesTableInner')?.classList.add('productos-table-loading');
            };

            const hideTableLoader = () => {
                getTableContainer()?.querySelector('#vinetasPendientesTableInner')?.classList.remove('productos-table-loading');
                const loader = document.getElementById('vinetasPendientesTableLoader');
                loader?.classList.add('hidden');
                loader?.classList.remove('flex');
            };

            const loadTableHtml = async (url, options = {}) => {
                const container = getTableContainer();
                if (!container) return;

                const previousScrollLeft = options.previousScrollLeft ?? 0;
                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Error al actualizar la tabla de viñetas pendientes');
                    }

                    const html = await response.text();
                    container.innerHTML = html;

                    if (options.pushState !== false) {
                        window.history.pushState({}, '', url);
                    }

                    initTableFeatures();
                    restoreTableScroll(previousScrollLeft);
                } catch (error) {
                    console.error(error);
                    window.location.href = url;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('.vinetas-ajax-filter-form, .vinetas-ajax-per-page-form');
                if (!form) return;

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
                if (!link || !link.href) return;

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

            initTableFeatures();
        });
    </script>

    <!-- Modal Ver QR Script -->
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
        });
    </script>
</body>
</html>
