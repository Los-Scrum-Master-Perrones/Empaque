<div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3.5 sm:p-4">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        {{-- Header: Icon, Title & Description --}}
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <div class="section-title-icon vinetas-header-icon w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 0 1 2-2Z" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h1 class="theme-title text-lg sm:text-xl font-black tracking-tight leading-tight">
                        Viñetas registradas
                    </h1>

                    <p class="theme-text text-xs mt-0.5 truncate">
                        Registros móviles y horas ordinarias.
                    </p>
                </div>
            </div>
        </div>

        {{-- Stats Strip: Clean, Borderless, Spaced for Millions --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 pt-3 xl:pt-0 border-t xl:border-t-0 theme-border shrink-0">
            {{-- Registros --}}
            <div class="text-left sm:text-right min-w-[5.5rem] sm:min-w-[7rem]">
                <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Registros</span>
                </div>
                <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['registros']) }}</p>
            </div>

            {{-- Puros --}}
            <div class="text-left sm:text-right min-w-[6.5rem] sm:min-w-[8.5rem]">
                <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Puros</span>
                </div>
                <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['puros']) }}</p>
            </div>

            {{-- Cajones --}}
            <div class="text-left sm:text-right min-w-[5.5rem] sm:min-w-[7rem]">
                <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Cajones</span>
                </div>
                <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['cajones']) }}</p>
            </div>

            {{-- Total act. --}}
            <div class="text-left sm:text-right min-w-[6.5rem] sm:min-w-[8.5rem]">
                <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Total act.</span>
                </div>
                <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['actividades']) }}</p>
            </div>
        </div>
    </div>
</div>
