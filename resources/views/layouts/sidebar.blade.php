@php
    $catalogosActivo = request()->routeIs('catalogos.*');
    $vinetasActivo = request()->routeIs('vinetas.*') || request()->routeIs('vinetas-por-orden.*') || request()->routeIs('vinetas-pendientes.*') || request()->routeIs('vineta-registros.*');
@endphp

<div x-data="{
    mobileSidebarOpen: false,
    catalogos: {{ $catalogosActivo ? 'true' : 'false' }},
    vinetas: {{ $vinetasActivo ? 'true' : 'false' }}
}"
     x-effect="document.body.classList.toggle('mobile-sidebar-lock', mobileSidebarOpen)"
     x-on:open-mobile-sidebar.window="mobileSidebarOpen = true"
     x-on:close-mobile-sidebar.window="mobileSidebarOpen = false"
     x-on:keydown.escape.window="mobileSidebarOpen = false"
     x-on:resize.window="if (window.innerWidth >= 768) mobileSidebarOpen = false"
     class="contents">
    <div x-show="mobileSidebarOpen"
         x-transition.opacity
         @click="mobileSidebarOpen = false"
         class="mobile-sidebar-backdrop fixed inset-0 z-50 bg-black/45 backdrop-blur-sm md:hidden"
         style="display: none;"></div>

<aside
    id="appSidebar"
    x-init="
        const savedSidebar = localStorage.getItem('sidebarOpen');

        if (savedSidebar !== null) {
            sidebarOpen = savedSidebar === 'true';
        }

        $watch('sidebarOpen', value => {
            localStorage.setItem('sidebarOpen', value);
            window.dispatchEvent(new CustomEvent('sidebar-toggled', { detail: { open: value } }));
        });

        requestAnimationFrame(() => {
            document.documentElement.classList.remove('sidebar-preload', 'sidebar-preload-collapsed', 'sidebar-preload-expanded');
        });
    "
    @click="if (window.innerWidth < 768 && $event.target.closest('a')) mobileSidebarOpen = false"
    :class="[
        sidebarOpen ? 'w-72 sidebar-expanded' : 'w-20 sidebar-collapsed',
        mobileSidebarOpen ? 'mobile-sidebar-open' : 'mobile-sidebar-closed'
    ]"
    class="app-sidebar hidden md:block shrink-0 bg-[#3b2818] text-white">
    <div class="app-sidebar-header h-20 flex items-center">
        <div class="sidebar-brand flex items-center gap-3">
            <div class="w-12 h-12  flex items-center justify-center shadow-sm">
                <img src="{{ asset('img/plasencia-logo.png') }}"
                     class="h-12 w-auto object-contain"
                     alt="Logo">
            </div>

            <div>
                <p class="text-sl font-bold leading-tight">Sistema</p>
                <p class="text-sm text-white/60 leading-tight">Empaque</p>
            </div>
        </div>
        <button @click="
                    if (window.innerWidth < 768) {
                        mobileSidebarOpen = false;
                    } else {
                        sidebarOpen = !sidebarOpen;
                        localStorage.setItem('sidebarOpen', sidebarOpen);
                    }
                "
                class="app-sidebar-toggle sidebar-menu-toggle w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/10 text-white transition duration-200"
                :class="(mobileSidebarOpen || sidebarOpen) ? 'is-open' : 'is-closed'"
                aria-label="Alternar menú">
            <span class="sidebar-menu-toggle__line"></span>
            <span class="sidebar-menu-toggle__line"></span>
            <span class="sidebar-menu-toggle__line"></span>
        </button>
    </div>

    <nav class="py-5 pl-4 pr-0">
        <div class="sidebar-hover-highlight" aria-hidden="true"></div>

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           data-sidebar-tooltip="Dashboard"
           class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('dashboard') && !request('tab') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
            </svg>

             <span class="sidebar-label">Dashboard</span>
        </a>

        {{-- Usuarios --}}
        @can('usuarios.ver')
            <a href="{{ route('usuarios.index') }}"
               data-sidebar-tooltip="Usuarios"
               class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('usuarios.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-5a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z" />
                </svg>

                 <span class="sidebar-label">Usuarios</span>
            </a>
        @endcan

        {{-- Roles --}}
        @can('roles.ver')
            <a href="{{ route('roles.index') }}"
               data-sidebar-tooltip="Roles"
               class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('roles.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 21v-2a6 6 0 0 1 12 0v2M19 8l1.5 1.5L23 7" />
                </svg>

                 <span class="sidebar-label">Roles</span>
            </a>
        @endcan

        {{-- Permisos --}}
        @can('roles.ver')
            <a href="{{ route('permisos.index') }}"
               data-sidebar-tooltip="Permisos"
               class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('permisos.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4" />
                </svg>

                 <span class="sidebar-label">Permisos</span>
            </a>
        @endcan

        {{-- Catálogos --}}
        @can('catalogos.ver')
   <button type="button"
        data-sidebar-tooltip="Catálogos"
        @click="
            if (!sidebarOpen) {
                sidebarOpen = true;
                catalogos = true;
            } else {
                catalogos = !catalogos;
            }
        "
        class="sidebar-nav-item w-full flex items-center justify-between px-4 py-3 rounded-l-2xl transition
        {{ $catalogosActivo ? 'sidebar-active' : 'sidebar-link' }}">

                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 6v12" />
                    </svg>

                     <span class="sidebar-label">Catálogos</span>
                </div>

                <svg :class="catalogos ? 'rotate-180' : ''"
                     class="sidebar-disclosure-icon w-4 h-4 transition-transform"
                     xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div :class="catalogos && (sidebarOpen || mobileSidebarOpen) ? 'sidebar-submenu-open max-h-[26rem] opacity-100 mt-2' : 'sidebar-submenu-closed max-h-0 opacity-0 mt-0 pointer-events-none'"
                 class="sidebar-submenu ml-8 mr-4 space-y-1 text-sm overflow-hidden max-h-0 opacity-0">

                @can('productos.ver')
                    <a href="{{ route('catalogos.productos.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.productos.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Productos
                    </a>
                @endcan

                @can('marcas.ver')
                    <a href="{{ route('catalogos.marcas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.marcas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Marcas
                    </a>
                @endcan

                @can('vitolas.ver')
                    <a href="{{ route('catalogos.vitolas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.vitolas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Vitolas
                    </a>
                @endcan

                @can('capas.ver')
                    <a href="{{ route('catalogos.capas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.capas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Capas
                    </a>
                @endcan

                @can('actividades.ver')
                    <a href="{{ route('catalogos.actividades.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.actividades.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Actividades
                    </a>
                @endcan

                <a href="{{ route('catalogos.empresas.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.empresas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Empresas
                </a>

                <a href="{{ route('catalogos.presentaciones.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.presentaciones.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Presentaciones
                </a>

                <a href="{{ route('catalogos.tipo-empaques.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.tipo-empaques.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Tipos de empaque
                </a>
            </div>
        @endcan
        {{-- Empleados --}}
        <a href="{{ route('empleados.index') }}"
           data-sidebar-tooltip="Empleados"
           class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('empleados.*') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 1a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>

            <span class="sidebar-label">Empleados</span>
        </a>

        {{-- Viñetas --}}
        <button type="button"
            data-sidebar-tooltip="Viñetas"
            @click="
                if (!sidebarOpen) {
                    sidebarOpen = true;
                    vinetas = true;
                } else {
                    vinetas = !vinetas;
                }
            "
            class="sidebar-nav-item w-full flex items-center justify-between px-4 py-3 rounded-l-2xl transition
            {{ $vinetasActivo ? 'sidebar-active' : 'sidebar-link' }}">

            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h2m4 0h-2m-4 4h6" />
                </svg>

                <span class="sidebar-label">Viñetas</span>
            </div>

            <svg :class="vinetas ? 'rotate-180' : ''"
                 class="sidebar-disclosure-icon w-4 h-4 transition-transform"
                 xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div :class="vinetas && (sidebarOpen || mobileSidebarOpen) ? 'sidebar-submenu-open max-h-[22rem] opacity-100 mt-2' : 'sidebar-submenu-closed max-h-0 opacity-0 mt-0 pointer-events-none'"
             class="sidebar-submenu ml-8 mr-4 space-y-1 text-sm overflow-hidden max-h-0 opacity-0">

            <a href="{{ route('vinetas.index') }}"
               class="block px-4 py-2 rounded-xl transition {{ (request()->routeIs('vinetas.*') && !request()->routeIs('vinetas-por-orden.*') && !request()->routeIs('vinetas-pendientes.*')) ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                Viñetas
            </a>

            <a href="{{ route('vinetas-por-orden.index') }}"
               class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('vinetas-por-orden.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                Viñetas por orden
            </a>

            <a href="{{ route('vinetas-pendientes.index') }}"
               class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('vinetas-pendientes.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                Viñetas pendientes
            </a>

            <a href="{{ route('vineta-registros.index') }}"
               class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('vineta-registros.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                Viñetas registradas
            </a>
        </div>

        {{-- Estadístico --}}
        <a href="{{ route('estadistico.index') }}"
           data-sidebar-tooltip="Estadístico"
           class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('estadistico.*') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>

            <span class="sidebar-label">Estadístico</span>
        </a>

        {{-- Costos empaque --}}
        @if(!auth()->user()?->esSoloSupervisor())
        <a href="{{ route('costos-empaque.index') }}"
           data-sidebar-tooltip="Costos empaque"
           class="sidebar-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('costos-empaque.*') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <span class="sidebar-label">Costos empaque</span>
        </a>
        @endif

        {{-- Mi perfil --}}
        <a href="{{ route('profile.edit') }}"
           data-sidebar-tooltip="Mi perfil"
           class="sidebar-nav-item sidebar-profile-link w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('profile.*') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 21a8 8 0 0 1 16 0" />
            </svg>

            <span class="sidebar-label">Mi perfil</span>
        </a>

    </nav>
</aside>

<div aria-hidden="true"
     :class="sidebarOpen ? 'w-72' : 'w-20'"
     class="app-sidebar-spacer hidden md:block shrink-0"></div>
</div>
