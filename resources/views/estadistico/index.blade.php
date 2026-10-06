<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadístico | Sistema de Empaque</title>

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

                        {{-- Header --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3.5 sm:p-4">
                            <div class="flex items-center gap-3">
                                <div class="section-title-icon vinetas-header-icon w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>

                                <div>
                                    <h1 class="theme-title text-lg sm:text-xl font-black tracking-tight leading-tight whitespace-nowrap">
                                        Estadístico
                                    </h1>
                                    <p class="theme-text text-xs text-slate-500 dark:text-slate-400">
                                        Producción de empaque agrupada por orden del cliente, orden del sistema e item
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Filter Card (Filtros de rango de fecha, orden sistema, orden cliente, item y empleado) --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <form id="estadisticoFilterForm"
                                  method="GET"
                                  action="{{ route('estadistico.index') }}"
                                  class="space-y-3">

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 items-end">
                                    {{-- Fecha inicio --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Fecha inicio</label>
                                        <input type="date"
                                               name="fecha_inicio"
                                               value="{{ request('fecha_inicio', request('fecha_desde')) }}"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-2.5 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Fecha final --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Fecha final</label>
                                        <input type="date"
                                               name="fecha_fin"
                                               value="{{ request('fecha_fin', request('fecha_hasta')) }}"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-2.5 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Orden del sistema --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Orden del sistema</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Ej: 3585"
                                               autocomplete="off"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Orden del cliente --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Orden del cliente</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente', request('orden')) }}"
                                               placeholder="Ej: FTT-1869"
                                               autocomplete="off"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Item --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Item</label>
                                        <input type="text"
                                               name="item"
                                               value="{{ request('item', request('producto_item')) }}"
                                               placeholder="Ej: 47801610"
                                               autocomplete="off"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    {{-- Empleado (código o nombre) --}}
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 truncate">Empleado</label>
                                        <input type="text"
                                               name="empleado"
                                               value="{{ request('empleado') }}"
                                               placeholder="Código o nombre..."
                                               autocomplete="off"
                                               class="theme-input w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>
                                </div>

                                {{-- Botones de acción --}}
                                <div class="flex items-center justify-end gap-2 pt-2 border-t theme-border">
                                    <a href="{{ route('estadistico.index') }}"
                                       class="estadistico-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white text-[#0b1220] text-sm font-bold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-5 py-2 rounded-xl bg-[#0f172a] text-white text-sm font-black hover:bg-[#1e293b] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Main Table Container --}}
                        <div id="estadisticoTableContainer" class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible relative">
                            @include('estadistico.partials.tabla')
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    {{-- Modal Detalle de Actividad en Estadístico --}}
    <div id="estadisticoDetalleModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-3 py-4 sm:px-4 sm:py-6 backdrop-blur-sm transition-opacity duration-200"
         tabindex="-1"
         aria-modal="true"
         role="dialog">
        <div class="theme-card flex max-h-[92vh] w-[95vw] max-w-4xl flex-col overflow-hidden rounded-3xl shadow-2xl border-0">
            {{-- Header --}}
            <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-4 shrink-0">
                <div class="min-w-0">
                    <p class="theme-text text-xs font-black uppercase tracking-wider">Desglose de Producción</p>
                    <h2 id="estadisticoModalTitle" class="theme-title text-lg sm:text-xl font-black truncate mt-0.5">
                        Detalle de Actividad
                    </h2>
                </div>
                <button type="button"
                        id="estadisticoModalCloseBtn"
                        class="theme-button-secondary inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border-0 text-xl font-black transition hover:opacity-80 active:scale-95 cursor-pointer"
                        aria-label="Cerrar modal">
                    ×
                </button>
            </div>

            {{-- Body scrollable --}}
            <div id="estadisticoModalBody" class="overflow-y-auto px-4 pb-5 pt-1 sm:px-6 sm:pb-6 flex-1 min-h-[300px]">
                {{-- Contenido dinámico --}}
            </div>
        </div>
    </div>

    <style>
        /* Quitar todos los bordes de contenedores, badges y tablas dentro del modal */
        #estadisticoDetalleModal *:not(.animate-spin) {
            border-width: 0 !important;
            border-style: none !important;
            border-color: transparent !important;
        }

        /* Estilos específicos para el modal en tema claro */
        html:not(.dark-navy) #estadisticoDetalleModal .theme-card {
            background-color: #ffffff !important;
            color: #0b1220 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-soft {
            background-color: #f4f7fb !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-title {
            color: #0b1220 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-text {
            color: #64748b !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-badge {
            background-color: #e0f2fe !important;
            color: #0b1220 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-button-secondary {
            background-color: #f1f5f9 !important;
            color: #0b1220 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .accent-sky {
            color: #0284c7 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-table-head {
            background-color: #e8eef5 !important;
            color: #0b1220 !important;
        }
        html:not(.dark-navy) #estadisticoDetalleModal .theme-row:hover {
            background-color: #f8fafc !important;
        }

        /* Tema oscuro (dark-navy) */
        html.dark-navy #estadisticoDetalleModal .theme-card {
            background-color: #111c33 !important;
            color: #e5e7eb !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-soft {
            background-color: #16233d !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-title {
            color: #f8fafc !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-text {
            color: #94a3b8 !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-badge {
            background-color: #1e2f4f !important;
            color: #38bdf8 !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-button-secondary {
            background-color: #16233d !important;
            color: #e5e7eb !important;
        }
        html.dark-navy #estadisticoDetalleModal .accent-sky {
            color: #38bdf8 !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-table-head {
            background-color: #1b2b4a !important;
            color: #e5e7eb !important;
        }
        html.dark-navy #estadisticoDetalleModal .theme-row:hover {
            background-color: #172845 !important;
        }
    </style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let stickyHeaderClone = null;
        let floatingScroll = null;
        let boundFloatingScroll = null;
        let syncingFloatingScroll = false;
        let windowEventsBound = false;

        const getTableContainer = () => document.getElementById('estadisticoTableContainer');
        const getTableScroll = () => getTableContainer()?.querySelector('.productos-table-scroll');

        const getTopbarBottom = () => {
            const topbar = document.querySelector('header, .app-topbar, [data-topbar]');
            return topbar ? Math.max(0, topbar.getBoundingClientRect().bottom) : 0;
        };

        const removeStickyHeaderClone = () => {
            if (stickyHeaderClone && stickyHeaderClone.parentNode) {
                stickyHeaderClone.parentNode.removeChild(stickyHeaderClone);
            }
            stickyHeaderClone = null;
        };

        const syncStickyHeaderClone = () => {
            const scroll = getTableScroll();
            const table = scroll?.querySelector('.vinetas-table');
            const header = table?.querySelector('thead');

            if (!scroll || !table || !header || !stickyHeaderClone) {
                return;
            }

            const tableRect = table.getBoundingClientRect();
            const headerRect = header.getBoundingClientRect();
            const scrollRect = scroll.getBoundingClientRect();
            const topbarBottom = getTopbarBottom();

            const shouldShow = headerRect.top < topbarBottom
                && tableRect.bottom > topbarBottom + headerRect.height;

            stickyHeaderClone.classList.toggle('is-visible', shouldShow);

            if (!shouldShow) {
                return;
            }

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
            floatingScroll = document.getElementById('estadisticoFloatingScroll');

            if (!scroll || !table || !floatingScroll) {
                return;
            }

            const rect = scroll.getBoundingClientRect();
            const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
            const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > getTopbarBottom();

            floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

            if (!hasHorizontalOverflow || !isTableVisible) {
                return;
            }

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
            floatingScroll = document.getElementById('estadisticoFloatingScroll');

            if (!scroll || !table || !header || !floatingScroll) {
                return;
            }

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
                    th.style.maxWidth = `${width}px`;
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

            if (!windowEventsBound) {
                window.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                }, { passive: true });

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
                windowEventsBound = true;
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

            requestAnimationFrame(() => {
                syncStickyHeaderClone();
                updateFloatingScroll();
            });
        };

        async function loadUrl(url) {
            const container = getTableContainer();
            if (!container) return;

            const currentScrollLeft = getTableScroll()?.scrollLeft || 0;
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });
                if (!response.ok) throw new Error('Error en la carga');
                const html = await response.text();
                container.innerHTML = html;
                window.history.pushState({}, '', url);

                initTableFeatures();

                const newScroll = getTableScroll();
                if (newScroll) {
                    newScroll.scrollLeft = currentScrollLeft;
                }
            } catch (err) {
                window.location.href = url;
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }

        document.addEventListener('click', (e) => {
            const link = e.target.closest('.estadistico-ajax-table-link, .estadistico-ajax-pagination a, .estadistico-ajax-clear-filters');
            if (link && link.href) {
                e.preventDefault();
                loadUrl(link.href);
            }
        });

        document.addEventListener('submit', (e) => {
            const form = e.target.closest('#estadisticoFilterForm, .estadistico-ajax-per-page-form');
            if (form) {
                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (typeof value === 'string' && value.trim() !== '') {
                        params.append(key, value.trim());
                    }
                }
                loadUrl(`${form.action.split('?')[0]}?${params.toString()}`);
            }
        });

        // ============================================
        // Modal Detalle de Actividad
        // ============================================
        const modal = document.getElementById('estadisticoDetalleModal');
        const modalBody = document.getElementById('estadisticoModalBody');
        const modalTitle = document.getElementById('estadisticoModalTitle');
        const modalCloseBtn = document.getElementById('estadisticoModalCloseBtn');
        const detalleBaseUrl = '{{ route("estadistico.detalle-actividad") }}';

        const openModal = () => {
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-ver-detalle-actividad');
            if (!btn) return;

            e.preventDefault();

            const actividad = btn.dataset.actividad || 'Actividad';
            const cantidad = btn.dataset.cantidad || '';
            const titulo = btn.dataset.titulo || '';
            const detalles = btn.dataset.detalles || '';

            if (modalTitle) {
                modalTitle.textContent = actividad;
            }

            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 gap-3">
                        <div class="w-10 h-10 border-4 border-sky-500/20 border-t-sky-500 rounded-full animate-spin"></div>
                        <p class="theme-text text-sm font-semibold animate-pulse">Cargando desglose de personas y viñetas...</p>
                    </div>
                `;
            }

            openModal();

            const params = new URLSearchParams();
            params.set('group_orden_sistema', btn.dataset.groupOs || '');
            params.set('group_orden_cliente', btn.dataset.groupOc || '');
            params.set('group_producto_codigo', btn.dataset.groupCodigo || '');
            params.set('group_producto_item', btn.dataset.groupItem || '');
            params.set('group_tipo_empaque', btn.dataset.groupEmpaque || '');
            params.set('actividad_nombre', actividad);
            params.set('titulo', titulo);
            params.set('detalles', detalles);

            // Respetar filtros activos de la pantalla
            const filterForm = document.getElementById('estadisticoFilterForm');
            if (filterForm) {
                const formData = new FormData(filterForm);
                const fechaInicio = formData.get('fecha_inicio');
                const fechaFin = formData.get('fecha_fin');
                const emp = formData.get('empleado');
                if (fechaInicio && typeof fechaInicio === 'string' && fechaInicio.trim()) {
                    params.set('fecha_inicio', fechaInicio.trim());
                }
                if (fechaFin && typeof fechaFin === 'string' && fechaFin.trim()) {
                    params.set('fecha_fin', fechaFin.trim());
                }
                if (emp && typeof emp === 'string' && emp.trim()) {
                    params.set('empleado', emp.trim());
                }
            }

            try {
                const response = await fetch(`${detalleBaseUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('Error al consultar el detalle');
                }

                const html = await response.text();
                if (modalBody) {
                    modalBody.innerHTML = html;
                }
            } catch (err) {
                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="theme-soft rounded-2xl border theme-border p-8 text-center">
                            <svg class="w-10 h-10 text-rose-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="theme-title text-base font-bold">No se pudo cargar la información</p>
                            <p class="theme-text text-xs mt-1">Ocurrió un error al consultar las viñetas. Por favor, intenta de nuevo.</p>
                        </div>
                    `;
                }
            }
        });

        initTableFeatures();
    });
</script>
</body>
</html>
