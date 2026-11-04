<header class="app-topbar h-16 bg-[#3b2818] border-b border-[#2b1b12] flex items-center justify-between px-4 shadow-sm transition-colors duration-300">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button"
                class="mobile-sidebar-trigger md:hidden inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/35"
                aria-label="Abrir menú"
                aria-controls="appSidebar"
                onclick="window.dispatchEvent(new CustomEvent('open-mobile-sidebar'))">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="min-w-0">
            <h1 class="app-topbar-title text-xl font-bold leading-tight text-white truncate">
                {{ $title ?? 'Dashboard' }}
            </h1>

            <p class="app-topbar-description text-xs leading-tight text-white/60 truncate">
                {{ $description ?? 'Sistema de empaque' }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        @include('layouts.partials.campanita-ficha')

        <label class="theme-switch" title="Cambiar tema">
            <input id="themeToggle" class="theme-switch__input" type="checkbox" role="switch" aria-label="Dark Mode">

            <span class="theme-switch__icon" aria-hidden="true">
                <span class="theme-switch__icon-part theme-switch__icon-part--1"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--2"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--3"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--4"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--5"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--6"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--7"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--8"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--9"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--10"></span>
                <span class="theme-switch__icon-part theme-switch__icon-part--11"></span>
            </span>

            <span class="theme-switch__sr">Dark Mode</span>
        </label>

        <div x-data="{ profileOpen: false }" class="relative">

            <button @click="profileOpen = !profileOpen"
                    class="flex items-center gap-2 rounded-xl hover:bg-white/10 transition px-2.5 py-1.5">

                <div class="hidden lg:block text-right">
                    <p class="text-xs font-semibold leading-tight text-white">
                        {{ Auth::user()->name }}
                    </p>

                    <p class="text-[11px] leading-tight text-white/60">
                        {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                    </p>
                </div>

                @if (Auth::user()->photo)
                    <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                         class="w-9 h-9 rounded-xl object-cover"
                         alt="Usuario">
                @else
                    <div class="w-9 h-9 rounded-xl bg-white text-[#5b3a1e] flex items-center justify-center text-sm font-bold border-2 border-white/30">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-3.5 h-3.5 text-white/70"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="profileOpen"
                 x-transition
                 @click.outside="profileOpen = false"
                 class="app-profile-dropdown absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-[#e5d8c7] overflow-hidden z-50"
                 style="display: none;">

                <div class="app-profile-header p-4 text-center bg-[#fbf8f3]">
                    @if (Auth::user()->photo)
                        <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                             class="w-14 h-14 rounded-xl object-cover mx-auto border-2 border-[#d8c6a3]"
                             alt="Usuario">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold text-lg mx-auto border-2 border-[#d8c6a3]">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif

                    <h3 class="app-profile-name mt-2 font-bold text-sm text-[#3b2818]">
                        {{ Auth::user()->name }}
                    </h3>

                    <p class="app-profile-email text-xs text-gray-500">
                        {{ Auth::user()->email }}
                    </p>
                </div>

                <a href="{{ route('profile.edit') }}"
                   class="app-profile-link flex items-center gap-2 px-4 py-2.5 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                    Mi perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                            class="app-profile-link w-full flex items-center gap-2 px-4 py-2.5 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<svg class="absolute w-0 h-0 overflow-hidden" aria-hidden="true" focusable="false">
    <filter id="gooey-action-filter" x="-50%" y="-50%" width="200%" height="200%">
        <feComponentTransfer>
            <feFuncA type="discrete" tableValues="0 1"></feFuncA>
        </feComponentTransfer>
        <feGaussianBlur stdDeviation="5"></feGaussianBlur>
        <feComponentTransfer>
            <feFuncA type="table" tableValues="-5 11"></feFuncA>
        </feComponentTransfer>
    </filter>
</svg>
