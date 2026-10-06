<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Dashboard | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .dashboard-shell {
            background: #ffffff;
        }

        html.dark-navy .dashboard-shell {
            background: #111c33;
        }

        .dashboard-panel {
            background: #ffffff;
            border: none !important;
            box-shadow: none !important;
        }

        html.dark-navy .dashboard-panel {
            background: #111c33;
            border: none !important;
            box-shadow: none !important;
        }

        .dashboard-welcome {
            background: #ffffff;
            border: none !important;
            box-shadow: none !important;
        }

        html.dark-navy .dashboard-welcome {
            background: #111c33;
            border: none !important;
            box-shadow: none !important;
        }

        /* ── Welcome banner avatar + slide reveal animation ── */
        .welcome-avatar-wrapper {
            opacity: 0;
            transform: scale(0.6);
            animation: welcome-avatar-pop 0.5s cubic-bezier(0.34, 1.45, 0.64, 1) 0.15s forwards;
        }

        .welcome-text-reveal {
            opacity: 0;
            transform: translateX(-28px);
            animation: welcome-slide-right 0.75s cubic-bezier(0.16, 1, 0.3, 1) 0.35s forwards;
        }

        @keyframes welcome-avatar-pop {
            0% {
                opacity: 0;
                transform: scale(0.6);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes welcome-slide-right {
            0% {
                opacity: 0;
                transform: translateX(-28px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .dashboard-kicker {
            color: #2563eb;
        }

        html.dark-navy .dashboard-kicker {
            color: #38bdf8;
        }

        /* ── Modern Borderless Hero Stats ── */
        .hero-stats-strip {
            background: transparent;
            border: none;
        }

        .hero-stat-clean {
            background: transparent;
            border: none;
            position: relative;
            transition: transform .25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .hero-stat-clean:hover {
            transform: translateY(-3px);
        }

        .metric-number-reveal {
            display: inline-block;
            opacity: 0;
            transform: translateY(8px);
            transition:
                opacity .6s cubic-bezier(.16, 1, .3, 1),
                transform .6s cubic-bezier(.16, 1, .3, 1);
            transition-delay: calc(var(--reveal-delay, 0ms) + var(--item-delay, 100ms) + 50ms);
        }

        .reveal-on-scroll.is-visible .metric-number-reveal {
            opacity: 1;
            transform: translateY(0);
        }

        .dashboard-primary-action {
            background: #0b1220;
            color: #ffffff !important;
        }

        .dashboard-primary-action:hover {
            background: #111c33;
            color: #ffffff !important;
        }

        html.dark-navy .dashboard-primary-action {
            background: #38bdf8;
            color: #0b1220 !important;
        }

        html.dark-navy .dashboard-primary-action:hover {
            background: #7dd3fc;
            color: #0b1220 !important;
        }

        .process-date-input {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #0b1220;
            color-scheme: light;
            cursor: pointer;
            font-size: .75rem;
            font-weight: 800;
            min-height: 2.5rem;
            outline: none;
            padding: .55rem .75rem;
            transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease;
        }

        .process-date-input:hover,
        .process-date-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .09);
        }

        .process-date-input:disabled {
            cursor: wait;
            opacity: .62;
        }

        .process-date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        html.dark-navy .process-date-input {
            background: #16233d;
            border-color: #263650;
            color: #f8fafc;
            color-scheme: dark;
        }

        html.dark-navy .process-date-input:hover,
        html.dark-navy .process-date-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .08);
        }

        .area-date-range-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }

        html.dark-navy .area-date-range-container {
            background: #16233d;
            border-color: #263650;
        }

        .area-date-range-container input[type="date"] {
            color: #0b1220;
            background: transparent;
            color-scheme: light;
        }

        html.dark-navy .area-date-range-container input[type="date"] {
            color: #f8fafc;
            background: transparent;
            color-scheme: dark;
        }

        .process-chart-wrap {
            min-height: 240px;
            position: relative;
        }

        .process-chart-wrap #processChart {
            transition: opacity .18s ease;
        }

        .area-card-rezago {
            --area-color: #2563eb;
            --area-soft: rgba(37, 99, 235, .12);
            --area-border: rgba(37, 99, 235, .38);
        }

        .area-card-anillado {
            --area-color: #0891b2;
            --area-soft: rgba(8, 145, 178, .12);
            --area-border: rgba(8, 145, 178, .38);
        }

        .area-card-llenado {
            --area-color: #6366f1;
            --area-soft: rgba(99, 102, 241, .12);
            --area-border: rgba(99, 102, 241, .38);
        }

        .area-card-limpieza {
            --area-color: #8b5cf6;
            --area-soft: rgba(139, 92, 246, .12);
            --area-border: rgba(139, 92, 246, .38);
        }

        html.dark-navy .area-card-rezago {
            --area-color: #38bdf8;
            --area-soft: rgba(56, 189, 248, .13);
            --area-border: rgba(56, 189, 248, .38);
        }

        html.dark-navy .area-card-anillado {
            --area-color: #22d3ee;
            --area-soft: rgba(34, 211, 238, .12);
            --area-border: rgba(34, 211, 238, .38);
        }

        html.dark-navy .area-card-llenado {
            --area-color: #818cf8;
            --area-soft: rgba(129, 140, 248, .14);
            --area-border: rgba(129, 140, 248, .38);
        }

        html.dark-navy .area-card-limpieza {
            --area-color: #a78bfa;
            --area-soft: rgba(167, 139, 250, .14);
            --area-border: rgba(167, 139, 250, .38);
        }

        .area-card {
            overflow: hidden;
            position: relative;
            transition: transform .2s ease;
            min-width: 0;
            border: none !important;
            box-shadow: none !important;
        }

        .area-card::before {
            background: var(--area-color);
            content: '';
            height: 4px;
            inset: 0 0 auto;
            position: absolute;
        }

        .area-card::after {
            display: none;
        }

        .area-card:hover {
            border: none !important;
            box-shadow: none !important;
            transform: translateY(-2px);
        }

        .area-card .area-icon {
            animation: area-float 4.2s ease-in-out infinite;
            transition: transform .25s ease;
        }

        .area-card:hover .area-icon {
            animation-play-state: paused;
            transform: rotate(-7deg) scale(1.08);
        }

        .area-card .area-progress {
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 1s cubic-bezier(.22, 1, .36, 1);
            transition-delay: calc(var(--item-delay, 80ms) + 260ms);
        }

        .reveal-on-scroll.is-visible .area-card .area-progress {
            transform: scaleX(1);
        }

        @keyframes area-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* ── Animated area icons (White & Dark Blue Palette) ── */
        .area-icon-animated {
            --anim-icon-primary: #0f172a;
            --anim-icon-secondary: #ffffff;
            color: var(--anim-icon-primary);
        }

        html.dark-navy .area-icon-animated {
            --anim-icon-primary: #ffffff;
            --anim-icon-secondary: #0f172a;
            color: var(--anim-icon-primary);
        }

        /* ── 1. LIMPIEZA: puro horizontal, mano con paño pule y destellos brotan en secuencia ── */
        .area-anim-clean-cigar path,
        .area-anim-clean-cigar line {
            stroke-dasharray: 80;
            stroke-dashoffset: 80;
            animation: anim-draw-stroke 0.5s ease-out 0.15s forwards;
        }
        .area-anim-clean-wipe {
            animation: anim-clean-wipe-glide 1.1s ease-in-out 0.55s forwards;
        }
        .area-anim-clean-sparkle-1 {
            animation: anim-shine-star 0.65s ease-out 1.0s forwards;
        }
        .area-anim-clean-sparkle-2 {
            animation: anim-shine-star 0.65s ease-out 1.25s forwards;
        }
        .area-anim-clean-sparkle-3 {
            animation: anim-shine-star 0.65s ease-out 1.5s forwards;
        }

        /* ── 2. REZAGO: 3 puros verticales, mano selecciona y se retira ── */
        .area-anim-cigar-a path,
        .area-anim-cigar-a line {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: anim-draw-stroke 0.4s ease-out 1.9s forwards;
        }
        .area-anim-cigar-b path,
        .area-anim-cigar-b line {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: anim-draw-stroke 0.4s ease-out 2.15s forwards;
        }
        .area-anim-cigar-c path,
        .area-anim-cigar-c line {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: anim-draw-stroke 0.4s ease-out 2.4s forwards;
        }
        .area-anim-hand-pick {
            animation: anim-hand-pick-and-fade 1.0s ease-in-out 2.7s forwards;
        }
        .area-anim-cigar-b {
            transform-origin: center bottom;
            animation: anim-cigar-lift 0.6s cubic-bezier(.34,1.35,.64,1) 3.1s forwards;
        }

        /* ── 3. ANILLADO: puro horizontal, mano coloca anillo y se desvanece ── */
        .area-anim-cigar2 path,
        .area-anim-cigar2 line {
            stroke-dasharray: 80;
            stroke-dashoffset: 80;
            animation: anim-draw-stroke 0.5s ease-out 3.65s forwards;
        }
        .area-anim-hand-ring {
            animation: anim-hand-place-and-fade 1.0s ease-in-out 4.0s forwards;
        }
        .area-anim-ring-painted {
            animation: anim-ring-painted-drop 0.55s cubic-bezier(.34,1.3,.64,1) 4.1s forwards;
        }
        .area-anim-shine-star {
            animation: anim-shine-star 0.65s ease-out 4.65s forwards;
        }

        /* ── 4. LLENADO: caja moderna, puros entrando en cascada, mano se retira ── */
        .area-anim-cigar-box path,
        .area-anim-cigar-box rect {
            stroke-dasharray: 90;
            stroke-dashoffset: 90;
            animation: anim-draw-stroke 0.55s ease-out 5.15s forwards;
        }
        .area-anim-pack-hand {
            animation: anim-hand-pack-glide 1.3s ease-in-out 5.45s forwards;
        }
        .area-anim-cigar-in-1 {
            animation: anim-cigar-into-box 0.4s cubic-bezier(.22,1,.36,1) 5.5s forwards;
        }
        .area-anim-cigar-in-2 {
            animation: anim-cigar-into-box 0.4s cubic-bezier(.22,1,.36,1) 5.85s forwards;
        }
        .area-anim-cigar-in-3 {
            animation: anim-cigar-into-box 0.4s cubic-bezier(.22,1,.36,1) 6.2s forwards;
        }
        .area-anim-box-sparkle {
            animation: anim-shine-star 0.65s ease-out 6.55s forwards;
        }

        /* ── Keyframes Limpios ── */
        @keyframes anim-draw-stroke {
            to { stroke-dashoffset: 0; }
        }
        @keyframes anim-clean-wipe-glide {
            0%   { opacity: 0; transform: translateX(-8px) translateY(-3px); }
            25%  { opacity: 1; transform: translateX(-2px) translateY(0); }
            70%  { opacity: 1; transform: translateX(7px) translateY(1px); }
            100% { opacity: 0; transform: translateX(12px) translateY(-3px); }
        }
        @keyframes anim-hand-pick-and-fade {
            0%   { opacity: 0; transform: translateY(-8px); }
            35%  { opacity: 1; transform: translateY(0); }
            70%  { opacity: 1; transform: translateY(-2px); }
            100% { opacity: 0; transform: translateY(-8px); }
        }
        @keyframes anim-cigar-lift {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-8.5px); filter: drop-shadow(0 6px 10px rgba(0,0,0,0.18)); }
        }
        @keyframes anim-hand-place-and-fade {
            0%   { opacity: 0; transform: translateY(-8px); }
            35%  { opacity: 1; transform: translateY(0); }
            70%  { opacity: 1; transform: translateY(2px); }
            100% { opacity: 0; transform: translateY(-6px); }
        }
        @keyframes anim-ring-painted-drop {
            0%   { opacity: 0; transform: translateY(-14px) scale(0.9); }
            40%  { opacity: 1; }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes anim-shine-star {
            0%   { opacity: 0; transform: scale(0.2) rotate(0deg); }
            50%  { opacity: 1; transform: scale(1.2) rotate(45deg); }
            100% { opacity: 0; transform: scale(1.5) rotate(90deg); }
        }
        @keyframes anim-cigar-into-box {
            0%   { opacity: 0; transform: translateY(-16px) scale(0.95); }
            40%  { opacity: 1; }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes anim-hand-pack-glide {
            0%   { opacity: 0; transform: translateY(-6px); }
            20%  { opacity: 1; transform: translateY(0); }
            75%  { opacity: 1; transform: translateY(3px); }
            100% { opacity: 0; transform: translateY(-4px); }
        }



        html.dark-navy .area-card:hover {
            border: none !important;
            box-shadow: none !important;
        }

        .dashboard-tabs {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .dashboard-tab {
            color: #64748b;
            transition: background-color .18s ease, color .18s ease, box-shadow .18s ease;
        }

        .dashboard-tab:hover {
            color: #0f172a;
        }

        .dashboard-tab.is-active {
            background: #0b1220;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(11, 18, 32, .18);
        }

        .dashboard-tab.is-active:hover {
            background: #111c33;
            color: #ffffff !important;
        }

        html.dark-navy .dashboard-tabs {
            background: #16233d;
            border-color: #263650;
        }

        html.dark-navy .dashboard-tab {
            color: #94a3b8;
        }

        html.dark-navy .dashboard-tab:hover {
            color: #e2e8f0;
        }

        html.dark-navy .dashboard-tab.is-active {
            background: #38bdf8;
            color: #0b1220 !important;
            box-shadow: none;
        }

        html.dark-navy .dashboard-tab.is-active:hover {
            background: #7dd3fc;
            color: #0b1220 !important;
        }

        .ranking-row {
            transition: background-color .18s ease, transform .18s ease;
        }

        .ranking-row:hover {
            background: #eff6ff;
            transform: translateX(3px);
        }

        html.dark-navy .ranking-row:hover {
            background: #172845;
        }

        html.dark-navy .ranking-list,
        html.dark-navy .ranking-list > *,
        html.dark-navy .recent-table-body,
        html.dark-navy .recent-table-body > * {
            border-color: transparent !important;
        }

        html.dark-navy .ranking-row {
            border: 0 !important;
        }

        html.dark-navy .ranking-position {
            color: #020617 !important;
        }

        .ranking-top-1 {
            background: linear-gradient(90deg, rgba(29, 78, 216, .12), rgba(14, 165, 233, .055) 72%, transparent);
            box-shadow: inset 3px 0 0 #1d4ed8;
            position: relative;
        }

        .ranking-top-1:hover {
            background: linear-gradient(90deg, rgba(29, 78, 216, .17), rgba(14, 165, 233, .085) 72%, transparent);
        }

        .ranking-top-1 .ranking-position {
            background: #1d4ed8 !important;
            border: 1px solid #1e40af;
            box-shadow: inset 0 -3px 0 #38bdf8;
            color: #ffffff !important;
        }

        html.dark-navy .ranking-top-1 {
            background: linear-gradient(90deg, rgba(56, 189, 248, .11), rgba(37, 99, 235, .07) 72%, transparent);
            box-shadow: inset 3px 0 0 #38bdf8;
        }

        html.dark-navy .ranking-top-1:hover {
            background: linear-gradient(90deg, rgba(56, 189, 248, .16), rgba(37, 99, 235, .10) 72%, transparent);
        }

        html.dark-navy .ranking-top-1 .ranking-position {
            background: #38bdf8 !important;
            border-color: #0ea5e9;
            box-shadow: inset 0 -3px 0 #2563eb;
            color: #0b1220 !important;
        }

        html.dark-navy .dark-clean-panel,
        html.dark-navy .dark-clean-panel > header,
        html.dark-navy .dark-clean-panel thead {
            border-color: transparent !important;
        }

        html.dark-navy .area-progress-track {
            background: #263650 !important;
        }

        .area-metrics > * {
            border-color: rgba(226, 232, 240, .75);
            min-width: 0;
        }

        html.dark-navy .area-metrics > * {
            border-color: rgba(56, 189, 248, .08) !important;
        }

        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(24px) scale(.988);
            transition:
                opacity .62s cubic-bezier(.22, 1, .36, 1),
                transform .62s cubic-bezier(.22, 1, .36, 1);
            transition-delay: var(--reveal-delay, 0ms);
            will-change: opacity, transform;
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .reveal-on-scroll .reveal-item {
            opacity: 0;
            transform: translateY(12px);
            transition:
                opacity .5s cubic-bezier(.22, 1, .36, 1),
                transform .5s cubic-bezier(.22, 1, .36, 1);
            transition-delay: calc(var(--reveal-delay, 0ms) + var(--item-delay, 80ms));
        }

        .reveal-on-scroll.is-visible .reveal-item {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal-on-scroll,
            .reveal-on-scroll .reveal-item {
                opacity: 1;
                transform: none;
                transition: none;
            }
            .area-card, .ranking-row { transition: none; }
            .area-card .area-icon { animation: none; }
            .area-card .area-progress { transform: scaleX(1); transition: none; }
            .process-chart-wrap::after { animation: none; }
        }
    </style>
</head>

<body class="dashboard-shell text-gray-800 transition-colors duration-300">

@php
    $areasMes = $resumenAreas['mes'] ?? [];
    $totalMesPuros = max((int) collect($areasMes)->sum('puros'), 1);
    $totalMesAreas = max((int) collect($areasMes)->sum('actividades'), 1);
    $hayProduccion = ($produccionTotal['registros'] ?? 0) > 0;
    $periodos = ['dia' => 'Día', 'mes' => 'Mes', 'anio' => 'Año'];
    $periodLabels = [
        'dia' => ucfirst($selectedMonth->locale('es')->translatedFormat('F Y')),
        'mes' => (string) $selectedYear,
        'anio' => max($selectedYear - 4, 2000).' - '.$selectedYear,
    ];
    $defaultRankingArea = collect($rankingEmpleados)
        ->first(fn ($group) => count($group['rows'] ?? []) > 0)['key'] ?? 'rezago';
@endphp

<script>
    window.rankingEmployeeSection = function() {
        return {
            rankingArea: '{{ $defaultRankingArea }}',
            selectedMonth: '{{ $selectedMonth->format('Y-m') }}',
            currentLoadedMonth: '{{ $selectedMonth->format('Y-m') }}',
            monthLabel: '{{ ucfirst($selectedMonth->locale('es')->translatedFormat('F Y')) }}',
            rankingData: @json($rankingEmpleados),
            loading: false,
            error: false,
            get areas() {
                return Object.values(this.rankingData || {});
            },
            async loadMonth(month) {
                if (!month || month === this.currentLoadedMonth) return;
                this.loading = true;
                this.error = false;
                try {
                    const endpoint = new URL(@json(route('dashboard')), window.location.origin);
                    endpoint.searchParams.set('mes', month);
                    endpoint.searchParams.set('ranking_mes', '1');

                    const response = await fetch(endpoint.toString(), {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error(`Error ${response.status}`);
                    const payload = await response.json();
                    if (payload.ok && payload.ranking) {
                        this.rankingData = payload.ranking;
                        this.selectedMonth = payload.mes;
                        this.currentLoadedMonth = payload.mes;
                        if (payload.label) {
                            this.monthLabel = payload.label;
                        }
                    } else {
                        throw new Error('Respuesta inválida');
                    }
                } catch (e) {
                    console.error('Error loading ranking month:', e);
                    this.error = true;
                } finally {
                    this.loading = false;
                }
            }
        };
    };
</script>

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: false,
    produccion: true
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1 min-w-0">
        @include('layouts.topbar', [
            'title' => 'Dashboard',
            'description' => 'Resumen operativo por área'
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-5 lg:space-y-6">

                <section class="dashboard-welcome reveal-on-scroll rounded-[1.75rem] p-5 lg:p-6 overflow-hidden relative">
                    <div class="relative grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center gap-5 lg:gap-8">
                        <div class="reveal-item min-w-0" style="--item-delay: 80ms">
                            <div class="flex items-center gap-3.5 sm:gap-4">
                                {{-- Avatar / Foto de perfil o inicial --}}
                                <div class="welcome-avatar-wrapper shrink-0">
                                    @if (Auth::user()->photo)
                                        <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                                             class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl object-cover shadow-sm ring-2 ring-blue-500/20"
                                             alt="Foto de perfil">
                                    @else
                                        <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl bg-[#0f172a] text-white flex items-center justify-center text-lg lg:text-xl font-black shadow-sm ring-2 ring-slate-300/40">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Textos con animación deslizante a la derecha --}}
                                <div class="welcome-text-reveal min-w-0">
                                    <p class="theme-text text-xs lg:text-sm font-black uppercase tracking-wider text-[#2563eb]">
                                        Buenos días,
                                    </p>
                                    <h1 class="theme-title text-2xl lg:text-3xl font-black tracking-tight truncate leading-tight mt-0.5">
                                        {{ Auth::user()->name }}
                                    </h1>
                                </div>
                            </div>

                            <div class="theme-text mt-3.5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold">
                                <span>{{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}</span>
                                <span class="hidden sm:inline h-1 w-1 rounded-full bg-[#93c5fd]"></span>
                                <span>{{ $today->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span>
                                <span class="hidden sm:inline h-1 w-1 rounded-full bg-[#93c5fd]"></span>
                                <span>{{ number_format($produccionMes['empleados'] ?? 0) }} empleados este mes</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('vineta-registros.index') }}" class="dashboard-primary-action gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl text-sm font-semibold transition">
                                    Ver registros
                                </a>
                                <a href="{{ route('vineta-registros.reporte-semanal') }}" class="theme-button-secondary gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                    Reporte semanal
                                </a>
                            </div>
                        </div>

                        <div class="hero-stats-strip flex items-center justify-between sm:justify-end gap-3 sm:gap-5 lg:gap-7 divide-x theme-border pt-4 xl:pt-0 border-t xl:border-t-0 theme-border">
                            {{-- HOY --}}
                            <div class="hero-stat-clean reveal-item text-left min-w-0" style="--item-delay: 130ms">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#2563eb] animate-pulse"></span>
                                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em]">Hoy</span>
                                </div>
                                <p class="theme-title text-lg sm:text-xl md:text-2xl lg:text-[1.7rem] xl:text-[1.85rem] font-black tracking-tight metric-number-reveal leading-tight">{{ number_format($produccionHoy['puros'] ?? 0) }}</p>
                                <p class="theme-text text-[10px] sm:text-[11px] font-bold mt-0.5">puros</p>
                            </div>

                            {{-- MES --}}
                            <div class="hero-stat-clean reveal-item pl-3.5 sm:pl-5 lg:pl-7 text-left min-w-0" style="--item-delay: 180ms">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#0891b2]"></span>
                                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em]">Mes</span>
                                </div>
                                <p class="theme-title text-lg sm:text-xl md:text-2xl lg:text-[1.7rem] xl:text-[1.85rem] font-black tracking-tight metric-number-reveal leading-tight">{{ number_format($produccionMes['puros'] ?? 0) }}</p>
                                <p class="theme-text text-[10px] sm:text-[11px] font-bold mt-0.5">puros</p>
                            </div>

                            {{-- AÑO --}}
                            <div class="hero-stat-clean reveal-item pl-3.5 sm:pl-5 lg:pl-7 text-left min-w-0" style="--item-delay: 230ms">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#6366f1]"></span>
                                    <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em]">Año</span>
                                </div>
                                <p class="theme-title text-lg sm:text-xl md:text-2xl lg:text-[1.7rem] xl:text-[1.85rem] font-black tracking-tight metric-number-reveal leading-tight">{{ number_format($produccionAnio['puros'] ?? 0) }}</p>
                                <p class="theme-text text-[10px] sm:text-[11px] font-bold mt-0.5">puros</p>
                            </div>
                        </div>
                    </div>
                </section>

                @unless($hayProduccion)
                    <div class="dashboard-panel reveal-on-scroll rounded-3xl p-8 text-center">
                        <p class="theme-title font-black">Sin producción registrada</p>
                    </div>
                @endunless

                <section class="reveal-on-scroll space-y-3" style="--reveal-delay: 40ms">
                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3 px-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="theme-title text-lg font-black">Resumen por área</h2>
                            <span id="areaSummaryPeriodBadge" class="theme-badge inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wider text-[#2563eb] bg-[#eff6ff] border-[#bfdbfe]">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span id="areaSummaryPeriodLabel">{{ $periodLabels['dia'] }}</span>
                            </span>
                        </div>

                        {{-- Selector de Rango de Fecha --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="area-date-range-container inline-flex items-center gap-2 rounded-2xl border px-3 py-1.5 shadow-sm">
                                <div class="flex items-center gap-1.5">
                                    <label for="areaRangeFrom" class="theme-text text-[10px] font-black uppercase tracking-wider opacity-70">Desde</label>
                                    <input
                                        type="date"
                                        id="areaRangeFrom"
                                        value="{{ $selectedMonth->copy()->startOfMonth()->format('Y-m-d') }}"
                                        max="{{ $today->format('Y-m-d') }}"
                                        class="rounded-xl text-xs py-1 px-2 border-0 cursor-pointer font-bold outline-none"
                                        title="Fecha inicial"
                                    >
                                </div>
                                <span class="theme-text text-xs opacity-40 font-black">—</span>
                                <div class="flex items-center gap-1.5">
                                    <label for="areaRangeTo" class="theme-text text-[10px] font-black uppercase tracking-wider opacity-70">Hasta</label>
                                    <input
                                        type="date"
                                        id="areaRangeTo"
                                        value="{{ $selectedMonth->copy()->endOfMonth()->min($today)->format('Y-m-d') }}"
                                        max="{{ $today->format('Y-m-d') }}"
                                        class="rounded-xl text-xs py-1 px-2 border-0 cursor-pointer font-bold outline-none"
                                        title="Fecha final"
                                    >
                                </div>
                            </div>

                            <button
                                type="button"
                                id="areaRangeCurrentMonthBtn"
                                data-month-start="{{ $selectedMonth->copy()->startOfMonth()->format('Y-m-d') }}"
                                data-month-end="{{ $selectedMonth->copy()->endOfMonth()->min($today)->format('Y-m-d') }}"
                                class="dashboard-tab is-active rounded-xl px-3 py-2 text-xs font-black transition border theme-border"
                                title="Mes actual"
                            >
                                Mes actual
                            </button>

                            <button
                                type="button"
                                id="areaRangeTodayBtn"
                                data-today="{{ $today->format('Y-m-d') }}"
                                class="dashboard-tab rounded-xl px-3 py-2 text-xs font-black transition border theme-border"
                                title="Solo hoy"
                            >
                                Hoy
                            </button>
                        </div>
                    </div>

                    <div id="areaSummaryCardsContainer" class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 sm:gap-3 xl:gap-3.5 2xl:gap-4 transition-opacity duration-200">
                        @foreach($areasMes as $area)
                            @php($share = round(($area['puros'] / $totalMesPuros) * 100))
                            <article id="areaCard_{{ $area['key'] }}" class="area-card area-card-{{ $area['key'] }} reveal-item dashboard-panel rounded-2xl lg:rounded-[1.4rem] xl:rounded-[1.5rem] p-3 sm:p-3.5 lg:p-4 xl:p-4 2xl:p-5.5 min-w-0" style="--item-delay: {{ 80 + ($loop->index * 70) }}ms">
                                <div class="relative z-10 min-w-0">
                                    <div class="flex items-center justify-between min-w-0">
                                        <h3 class="theme-title text-sm sm:text-base xl:text-lg font-black tracking-tight truncate">{{ $area['label'] }}</h3>
                                    </div>

                                    <div class="mt-3 sm:mt-3.5 lg:mt-4 flex items-end justify-between gap-1.5 sm:gap-2">
                                        <div class="min-w-0">
                                            <p data-area-metric="puros" class="theme-title text-xl sm:text-2xl lg:text-[1.65rem] xl:text-[1.75rem] 2xl:text-3xl leading-none font-black metric-number-reveal truncate">{{ number_format($area['puros']) }}</p>
                                            <p class="theme-text mt-1 text-[11px] sm:text-xs font-bold truncate">Puros</p>
                                        </div>
                                        <div class="area-icon area-icon-animated flex items-center justify-center shrink-0" data-area="{{ $area['key'] }}" data-area-index="{{ $loop->index }}">
                                            @if($area['key'] === 'limpieza')
                                                {{-- Limpieza: puro horizontal, mano con paño pule y destellos brotan en secuencia --}}
                                                <svg class="h-9 w-9 sm:h-10 sm:w-10 lg:h-11 lg:w-11 xl:h-12 xl:w-12 2xl:h-14 2xl:w-14 area-anim-svg" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    {{-- Puro horizontal estilizado --}}
                                                    <g class="area-anim-clean-cigar">
                                                        <path d="M5 14 C2 14 2 22 5 22 H31 C32 22 33 19 33 18 C33 17 32 14 31 14 H5 Z" fill="currentColor" fill-opacity="0.08"/>
                                                        <line x1="5" y1="14" x2="5" y2="22" opacity="0.6"/>
                                                        <line x1="14" y1="14" x2="12" y2="22" opacity="0.4"/>
                                                        <line x1="22" y1="14" x2="20" y2="22" opacity="0.4"/>
                                                        <line x1="31" y1="14" x2="31" y2="22" stroke-width="1.8"/>
                                                    </g>

                                                    {{-- Mano con paño de limpieza puliendo el puro de izquierda a derecha --}}
                                                    <g class="area-anim-clean-wipe" opacity="0">
                                                        <path d="M14 4 V8 M18 4 V8" stroke-width="1.4"/>
                                                        <path d="M11 8 C13 6 19 6 21 8" stroke-width="1.6"/>
                                                        <path d="M10 8 C9 11 11 13 14 13 H18 C21 13 23 11 22 8" fill="var(--anim-icon-secondary)" stroke="currentColor" stroke-width="1.3"/>
                                                        <path d="M7 11 C10 10 14 12 18 10" stroke-dasharray="1.5 2" stroke-width="1.2" opacity="0.6"/>
                                                    </g>

                                                    {{-- Destellos secuenciales al pulir --}}
                                                    <path class="area-anim-clean-sparkle-1" d="M10 8 L11 10 L13 11 L11 12 L10 14 L9 12 L7 11 L9 10 Z" fill="currentColor" opacity="0"/>
                                                    <path class="area-anim-clean-sparkle-2" d="M22 6 L23 8.5 L25.5 9.5 L23 10.5 L22 13 L21 10.5 L18.5 9.5 L21 8.5 Z" fill="currentColor" opacity="0"/>
                                                    <path class="area-anim-clean-sparkle-3" d="M28 21 L28.8 22.5 L30.5 23 L28.8 23.5 L28 25 L27.2 23.5 L25.5 23 L27.2 22.5 Z" fill="currentColor" opacity="0"/>
                                                </svg>
                                            @elseif($area['key'] === 'rezago')
                                                {{-- Rezago: 3 puros verticales estilizados y limpios, el central se levanta como seleccionado --}}
                                                <svg class="h-9 w-9 sm:h-10 sm:w-10 lg:h-11 lg:w-11 xl:h-12 xl:w-12 2xl:h-14 2xl:w-14 area-anim-svg" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    {{-- Puro Izquierdo --}}
                                                    <g class="area-anim-cigar-a">
                                                        <path d="M5 11 C5 7 9 7 9 11 V31 H5 Z" fill="currentColor" fill-opacity="0.08"/>
                                                        <line x1="5" y1="11" x2="9" y2="11" opacity="0.6"/>
                                                        <line x1="5" y1="19" x2="9" y2="22" opacity="0.4"/>
                                                        <line x1="5" y1="24" x2="9" y2="27" opacity="0.4"/>
                                                    </g>

                                                    {{-- Puro Central (se eleva notablemente al ser seleccionado) --}}
                                                    <g class="area-anim-cigar-b">
                                                        <path d="M16 11 C16 7 20 7 20 11 V31 H16 Z" fill="currentColor" fill-opacity="0.16"/>
                                                        <line x1="16" y1="11" x2="20" y2="11" opacity="0.7"/>
                                                        <line x1="16" y1="19" x2="20" y2="22" opacity="0.5"/>
                                                        <line x1="16" y1="24" x2="20" y2="27" opacity="0.5"/>
                                                    </g>

                                                    {{-- Puro Derecho --}}
                                                    <g class="area-anim-cigar-c">
                                                        <path d="M27 11 C27 7 31 7 31 11 V31 H27 Z" fill="currentColor" fill-opacity="0.08"/>
                                                        <line x1="27" y1="11" x2="31" y2="11" opacity="0.6"/>
                                                        <line x1="27" y1="19" x2="31" y2="22" opacity="0.4"/>
                                                        <line x1="27" y1="24" x2="31" y2="27" opacity="0.4"/>
                                                    </g>

                                                    {{-- Mano que baja a escoger y se retira limpiamente --}}
                                                    <g class="area-anim-hand-pick" opacity="0">
                                                        <path d="M18 1 V6" stroke-width="1.8"/>
                                                        <path d="M14 6 C15.5 4.5 17 5 18 6 C19 5 20.5 4.5 22 6" stroke-width="1.6"/>
                                                    </g>
                                                </svg>
                                            @elseif($area['key'] === 'anillado')
                                                {{-- Anillado: puro horizontal limpio, mano coloca anillo y se desvanece por completo --}}
                                                <svg class="h-9 w-9 sm:h-10 sm:w-10 lg:h-11 lg:w-11 xl:h-12 xl:w-12 2xl:h-14 2xl:w-14 area-anim-svg" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    {{-- Puro Horizontal con proporciones limpias --}}
                                                    <g class="area-anim-cigar2">
                                                        <path d="M6 14 C2 14 2 22 6 22 H32 V14 H6 Z" fill="currentColor" fill-opacity="0.08"/>
                                                        <line x1="6" y1="14" x2="6" y2="22" opacity="0.6"/>
                                                        <line x1="20" y1="14" x2="17" y2="22" opacity="0.4"/>
                                                        <line x1="27" y1="14" x2="24" y2="22" opacity="0.4"/>
                                                        <line x1="32" y1="14" x2="32" y2="22" stroke-width="1.8"/>
                                                    </g>

                                                    {{-- Anillo pintado --}}
                                                    <g class="area-anim-ring-painted" opacity="0">
                                                        <rect x="9.5" y="12" width="7" height="12" rx="2" fill="currentColor" stroke="currentColor" stroke-width="1"/>
                                                        <ellipse cx="13" cy="18" rx="1.8" ry="2.8" fill="var(--anim-icon-secondary)" stroke="none"/>
                                                        <circle cx="13" cy="18" r="0.9" fill="currentColor"/>
                                                    </g>

                                                    {{-- Destello al colocar el anillo que se apaga solo --}}
                                                    <path class="area-anim-shine-star" d="M13 7.5 L14 10 L16.5 11 L14 12 L13 14.5 L12 12 L9.5 11 L12 10 Z" fill="currentColor" opacity="0"/>

                                                    {{-- Mano que baja y se desvanece al 100% --}}
                                                    <g class="area-anim-hand-ring" opacity="0">
                                                        <path d="M10 1 V6 M16 1 V6" stroke-width="1.4"/>
                                                        <path d="M9 6 C10.5 4.5 14.5 4.5 16 6" stroke-width="1.6"/>
                                                    </g>
                                                </svg>
                                            @else
                                                {{-- Llenado: caja moderna estilizada y limpia, puros entrando ordenadamente --}}
                                                <svg class="h-9 w-9 sm:h-10 sm:w-10 lg:h-11 lg:w-11 xl:h-12 xl:w-12 2xl:h-14 2xl:w-14 area-anim-svg" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    {{-- Caja de puros estilizada y limpia sin manchas --}}
                                                    <g class="area-anim-cigar-box">
                                                        {{-- Tapa abierta limpia --}}
                                                        <path d="M 4 14 L 8 4 H 32 L 28 14" stroke-width="1.6"/>
                                                        {{-- Cuerpo de la caja --}}
                                                        <rect x="4" y="14" width="28" height="18" rx="2.5" fill="none" stroke-width="1.6"/>
                                                        <line x1="4" y1="14" x2="32" y2="14" stroke-width="1.6"/>
                                                    </g>

                                                    {{-- Puros con su anilla entrando ordenadamente a la caja --}}
                                                    <g class="area-anim-cigar-in-1" opacity="0">
                                                        <rect x="6.5" y="26" width="23" height="4.5" rx="2" fill="currentColor" fill-opacity="0.14" stroke-width="1.2"/>
                                                        <rect x="10.5" y="25.5" width="4" height="5.5" rx="1" fill="currentColor" stroke="none"/>
                                                        <circle cx="12.5" cy="28.25" r="0.7" fill="var(--anim-icon-secondary)" stroke="none"/>
                                                    </g>

                                                    <g class="area-anim-cigar-in-2" opacity="0">
                                                        <rect x="6.5" y="20.5" width="23" height="4.5" rx="2" fill="currentColor" fill-opacity="0.16" stroke-width="1.2"/>
                                                        <rect x="10.5" y="20" width="4" height="5.5" rx="1" fill="currentColor" stroke="none"/>
                                                        <circle cx="12.5" cy="22.75" r="0.7" fill="var(--anim-icon-secondary)" stroke="none"/>
                                                    </g>

                                                    <g class="area-anim-cigar-in-3" opacity="0">
                                                        <rect x="6.5" y="15" width="23" height="4.5" rx="2" fill="currentColor" fill-opacity="0.18" stroke-width="1.2"/>
                                                        <rect x="10.5" y="14.5" width="4" height="5.5" rx="1" fill="currentColor" stroke="none"/>
                                                        <circle cx="12.5" cy="17.25" r="0.7" fill="var(--anim-icon-secondary)" stroke="none"/>
                                                    </g>

                                                    {{-- Mano que empaca y luego se retira completamente --}}
                                                    <g class="area-anim-pack-hand" opacity="0">
                                                        <path d="M 18 1 V 6" stroke-width="1.6"/>
                                                        <path d="M 14 6 C 15.5 4.5 17 5 18 6 C 19 5 20.5 4.5 22 6" stroke-width="1.6"/>
                                                    </g>

                                                    {{-- Destello final de caja llena que se desvanece --}}
                                                    <path class="area-anim-box-sparkle" d="M 28 8 L 29 10 L 31 11 L 29 12 L 28 14 L 27 12 L 25 11 L 27 10 Z" fill="currentColor" opacity="0"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="area-progress-track mt-3 sm:mt-3.5 lg:mt-4 h-1.5 overflow-hidden rounded-full bg-slate-200/70" role="progressbar" aria-label="Participación de {{ $area['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $share }}">
                                        <div data-area-metric="progress" class="area-progress h-full rounded-full transition-all duration-500" style="width: {{ $share }}%; background: var(--area-color)"></div>
                                    </div>

                                    <dl class="area-metrics mt-3 sm:mt-3.5 lg:mt-4 grid grid-cols-2 divide-x theme-border text-center">
                                        <div class="pr-1 sm:pr-1.5 xl:pr-2 min-w-0">
                                            <dt class="theme-text text-[8.5px] sm:text-[9px] xl:text-[9.5px] 2xl:text-[10px] font-bold uppercase tracking-tight truncate" title="Empleados">Empleados</dt>
                                            <dd data-area-metric="empleados" class="theme-title mt-0.5 text-xs sm:text-[13px] xl:text-sm 2xl:text-base font-black truncate">{{ number_format($area['empleados']) }}</dd>
                                        </div>
                                        <div class="pl-1 sm:pl-1.5 xl:pl-2 min-w-0">
                                            <dt class="theme-text text-[8.5px] sm:text-[9px] xl:text-[9.5px] 2xl:text-[10px] font-bold uppercase tracking-tight truncate" title="Actividades">Actividades</dt>
                                            <dd data-area-metric="actividades" class="theme-title mt-0.5 text-xs sm:text-[13px] xl:text-sm 2xl:text-base font-black truncate">{{ number_format($area['actividades']) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 70ms">
                    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-5 py-4 lg:px-6 border-b theme-border">
                        <div class="flex items-center gap-3">
                            <h2 class="theme-title text-lg font-black">Producción por área</h2>
                            <span id="trendPeriodLabel" class="theme-badge hidden sm:inline-flex rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wider">{{ $periodLabels['dia'] }}</span>
                        </div>
                        <div class="dashboard-tabs inline-flex self-start rounded-xl p-1">
                            @foreach($periodos as $key => $label)
                                <button type="button" data-trend-period="{{ $key }}" class="dashboard-tab {{ $key === 'dia' ? 'is-active' : '' }} rounded-lg px-4 py-2 text-xs font-black">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </header>
                    <div class="px-2 pb-2 pt-3 sm:px-4 lg:px-5">
                        <div id="areaTrendChart" class="w-full h-[390px]"></div>
                    </div>
                </section>

                <div class="grid grid-cols-1 xl:grid-cols-[minmax(300px,.72fr)_minmax(0,1.28fr)] items-start gap-5 lg:gap-6">
                    <section class="dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 90ms">
                        <header class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b theme-border">
                            <h2 class="theme-title text-lg font-black">Producción diaria por área</h2>
                            <label for="processDate" class="sr-only">Seleccionar fecha</label>
                            <input
                                id="processDate"
                                type="date"
                                value="{{ $selectedDay->format('Y-m-d') }}"
                                max="{{ $today->format('Y-m-d') }}"
                                data-current-date="{{ $selectedDay->format('Y-m-d') }}"
                                class="process-date-input rounded-xl text-xs"
                            >
                        </header>

                        <div class="p-4 lg:p-5">
                            <div id="processChartWrap" class="process-chart-wrap relative min-h-[240px]">
                                <!-- Spinner centered in chart, transparent without dark background -->
                                <div id="processDateSpinner" class="hidden absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                                    <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-blue-600 dark:border-sky-400 border-t-transparent"></div>
                                </div>
                                <div id="processChart" class="w-full h-[240px]"></div>
                            </div>
                            <div id="processLegend" class="mt-1"></div>
                            <p id="processDateError" class="mt-3 hidden text-center text-xs font-bold text-red-600" role="alert">
                                No se pudo actualizar la fecha.
                            </p>
                        </div>
                    </section>

                    <section
                        x-data="rankingEmployeeSection()"
                        class="dark-clean-panel dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden"
                        style="--reveal-delay: 110ms"
                    >
                        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b theme-border">
                            <h2 class="theme-title text-lg font-black">Ranking de empleados</h2>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="dashboard-tabs inline-flex self-start rounded-xl p-1">
                                    <template x-for="grupo in areas" :key="grupo.key">
                                        <button
                                            type="button"
                                            @click="rankingArea = grupo.key"
                                            :class="rankingArea === grupo.key ? 'is-active' : ''"
                                            class="dashboard-tab rounded-lg px-3 py-2 text-xs font-black"
                                            x-text="grupo.label"
                                        ></button>
                                    </template>
                                </div>
                                <div>
                                    <label for="rankingMonth" class="sr-only">Seleccionar mes</label>
                                    <input
                                        id="rankingMonth"
                                        type="month"
                                        :value="selectedMonth"
                                        max="{{ $today->format('Y-m') }}"
                                        @change="loadMonth($event.target.value)"
                                        @input="loadMonth($event.target.value)"
                                        class="process-date-input rounded-xl text-xs"
                                        :disabled="loading"
                                        title="Seleccionar mes para el ranking"
                                    >
                                </div>
                            </div>
                        </header>

                        <div class="p-3 lg:p-4 relative min-h-[300px]">
                            <!-- Spinner centered in ranking list, transparent without dark background -->
                            <div x-show="loading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                                <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-blue-600 dark:border-sky-400 border-t-transparent"></div>
                            </div>
                            <template x-for="grupo in areas" :key="grupo.key">
                                <div x-show="rankingArea === grupo.key" class="ranking-list space-y-0.5">
                                    <template x-if="grupo.rows && grupo.rows.length > 0">
                                        <div>
                                            <template x-for="(empleado, index) in grupo.rows" :key="empleado.codigo || index">
                                                <div
                                                    class="ranking-row flex items-center gap-3 rounded-xl px-3"
                                                    :class="index === 0 ? 'ranking-top-1 py-2' : 'py-1.5'"
                                                >
                                                    <div
                                                        class="ranking-position shrink-0 flex items-center justify-center font-black text-white"
                                                        :class="[
                                                            'area-card-' + grupo.key,
                                                            index === 0 ? 'h-9 w-9 rounded-xl text-sm' : 'h-7 w-7 rounded-lg text-[11px]'
                                                        ]"
                                                        :style="'background: ' + (grupo.color || 'var(--area-color)')"
                                                        x-text="index + 1"
                                                    ></div>
                                                    <div class="min-w-0 flex-1">
                                                        <p
                                                            class="theme-title truncate font-black"
                                                            :class="index === 0 ? 'text-sm' : 'text-[13px]'"
                                                            x-text="empleado.nombre"
                                                        ></p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p
                                                            class="theme-title font-black"
                                                            :class="index === 0 ? 'text-base' : 'text-sm'"
                                                            x-text="Number(empleado.actividades).toLocaleString('es-HN')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="!grupo.rows || grupo.rows.length === 0">
                                        <div class="py-16 text-center">
                                            <p class="theme-text text-sm font-bold" x-text="'Sin producción en ' + (grupo.label ? grupo.label.toLowerCase() : '')"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <p x-show="error" x-cloak class="mt-3 text-center text-xs font-bold text-red-600" role="alert">
                                No se pudo actualizar el ranking para el mes seleccionado.
                            </p>
                        </div>
                    </section>
                </div>

                <section class="dark-clean-panel dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 110ms">
                    <header class="px-5 py-4 lg:px-6 border-b theme-border">
                        <h2 class="theme-title text-lg font-black">Actividad reciente</h2>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="theme-soft border-b theme-border">
                                <tr>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Viñeta</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Actividad</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Marca</th>
                                    <th class="px-6 py-3 text-right theme-text text-[11px] font-black uppercase tracking-wider">Puros</th>
                                </tr>
                            </thead>
                            <tbody class="recent-table-body">
                                @forelse($ultimosRegistros as $registro)
                                    <tr class="theme-row transition hover:bg-[#eff6ff]">
                                        <td class="px-6 py-4 theme-title font-black whitespace-nowrap">{{ $registro['vineta'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['fecha'] }} {{ $registro['hora'] }}</td>
                                        <td class="px-6 py-4 theme-title font-bold whitespace-nowrap">{{ $registro['empleado'] }}</td>
                                        <td class="px-6 py-4 theme-text min-w-[220px]">{{ $registro['actividad'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['marca'] }}</td>
                                        <td class="px-6 py-4 theme-title font-black text-right">{{ number_format($registro['puros']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center theme-text">Sin movimientos</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trends = @json($tendenciasAreas);
        const summaries = @json($resumenAreas);
        const periodLabels = @json($periodLabels);
        const dashboardDataUrl = @json(route('dashboard'));
        let trendChart = null;
        let processChart = null;
        let currentTrendPeriod = 'dia';
        const currentProcessPeriod = 'dia';

        function chartTheme() {
            const isDark = document.documentElement.classList.contains('dark-navy');

            return {
                text: isDark ? '#e2e8f0' : '#0f172a',
                muted: isDark ? '#94a3b8' : '#64748b',
                grid: isDark ? 'rgba(148, 163, 184, .13)' : '#e2e8f0',
                tooltip: isDark ? 'dark' : 'light',
                empty: isDark ? '#263650' : '#dbeafe',
                areas: isDark
                    ? { limpieza: '#a78bfa', rezago: '#38bdf8', anillado: '#22d3ee', llenado: '#818cf8' }
                    : { limpieza: '#8b5cf6', rezago: '#2563eb', anillado: '#0891b2', llenado: '#6366f1' }
            };
        }

        function baseChartOptions(theme) {
            return {
                fontFamily: 'inherit',
                foreColor: theme.text,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 520,
                    dynamicAnimation: { enabled: true, speed: 280 }
                }
            };
        }

        function trendAreas(period) {
            return Object.entries(trends[period]?.areas || {}).map(([key, area]) => ({ ...area, key }));
        }

        function areaColor(key) {
            return chartTheme().areas[key] || '#2563eb';
        }

        function trendSeries(period) {
            return trendAreas(period).map(area => ({ name: area.label, data: area.data }));
        }

        function processRows(period) {
            return Object.values(summaries[period] || {});
        }

        function processState(period) {
            const rows = processRows(period);
            const total = rows.reduce((sum, row) => sum + Number(row.puros || 0), 0);

            return {
                rows,
                total,
                series: total > 0 ? rows.map(row => Number(row.puros || 0)) : [1],
                labels: total > 0 ? rows.map(row => row.label) : ['Sin producción'],
                colors: total > 0 ? rows.map(row => areaColor(row.key)) : [chartTheme().empty]
            };
        }

        function setActiveTab(selector, period, datasetKey) {
            document.querySelectorAll(selector).forEach(button => {
                button.classList.toggle('is-active', button.dataset[datasetKey] === period);
            });
        }

        function renderProcessLegend(period) {
            const container = document.querySelector('#processLegend');
            const state = processState(period);

            if (! container) {
                return;
            }

            container.innerHTML = state.rows.map(row => {
                const percentage = state.total > 0 ? Math.round((Number(row.puros || 0) / state.total) * 100) : 0;

                return `
                    <div class="flex items-center gap-3 px-2 py-3">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:${areaColor(row.key)}"></span>
                        <span class="theme-title flex-1 text-sm font-bold">${row.label}</span>
                        <span class="theme-text text-xs font-black">${percentage}%</span>
                        <span class="theme-title min-w-[84px] text-right text-sm font-black">${Number(row.puros || 0).toLocaleString('es-HN')}</span>
                    </div>
                `;
            }).join('');
        }

        function renderCharts() {
            const theme = chartTheme();
            const trendElement = document.querySelector('#areaTrendChart');
            const processElement = document.querySelector('#processChart');

            if (trendElement) {
                const areas = trendAreas(currentTrendPeriod);
                trendChart = new ApexCharts(trendElement, {
                    chart: { ...baseChartOptions(theme), height: 390, type: 'area' },
                    series: trendSeries(currentTrendPeriod),
                    colors: areas.map(area => areaColor(area.key)),
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: .2, opacityFrom: .30, opacityTo: .025, stops: [0, 82, 100] }
                    },
                    markers: { size: 0, hover: { size: 5 } },
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        fontSize: '12px',
                        fontWeight: 700,
                        labels: { colors: theme.muted },
                        markers: { width: 9, height: 9, radius: 9 },
                        itemMargin: { horizontal: 12, vertical: 4 }
                    },
                    xaxis: {
                        categories: trends[currentTrendPeriod].labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: theme.muted, fontSize: '11px', fontWeight: 600 } },
                        tooltip: { enabled: false }
                    },
                    yaxis: {
                        min: 0,
                        labels: {
                            style: { colors: theme.muted, fontSize: '11px', fontWeight: 600 },
                            formatter: value => Math.round(value).toLocaleString('es-HN')
                        }
                    },
                    grid: { borderColor: theme.grid, strokeDashArray: 5, padding: { left: 8, right: 16 } },
                    tooltip: {
                        theme: theme.tooltip,
                        shared: true,
                        intersect: false,
                        y: { formatter: value => Number(value).toLocaleString('es-HN') + ' puros' }
                    }
                });

                trendChart.render();
            }

            if (processElement) {
                const state = processState(currentProcessPeriod);
                processChart = new ApexCharts(processElement, {
                    chart: { ...baseChartOptions(theme), height: 240, type: 'donut' },
                    series: state.series,
                    labels: state.labels,
                    colors: state.colors,
                    stroke: { width: 0 },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    plotOptions: {
                        pie: {
                            expandOnClick: false,
                            donut: {
                                size: '76%',
                                labels: {
                                    show: true,
                                    name: { show: true, color: theme.muted, fontSize: '12px', fontWeight: 700, offsetY: -5 },
                                    value: {
                                        show: true,
                                        color: theme.text,
                                        fontSize: '24px',
                                        fontWeight: 900,
                                        offsetY: 3,
                                        formatter: value => state.total > 0 ? Number(value).toLocaleString('es-HN') : '0'
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Total',
                                        color: theme.muted,
                                        fontSize: '12px',
                                        fontWeight: 700,
                                        formatter: () => state.total.toLocaleString('es-HN')
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        enabled: state.total > 0,
                        theme: theme.tooltip,
                        y: { formatter: value => Number(value).toLocaleString('es-HN') + ' puros' }
                    }
                });

                processChart.render();
                renderProcessLegend(currentProcessPeriod);
            }
        }

        function updateProcessChart() {
            const state = processState(currentProcessPeriod);
            const theme = chartTheme();

            processChart?.updateOptions({
                labels: state.labels,
                colors: state.colors,
                tooltip: {
                    enabled: state.total > 0,
                    theme: theme.tooltip,
                    y: { formatter: value => Number(value).toLocaleString('es-HN') + ' puros' }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                name: { color: theme.muted },
                                value: {
                                    color: theme.text,
                                    formatter: value => state.total > 0 ? Number(value).toLocaleString('es-HN') : '0'
                                },
                                total: {
                                    color: theme.muted,
                                    formatter: () => state.total.toLocaleString('es-HN')
                                }
                            }
                        }
                    }
                }
            }, false, true);
            processChart?.updateSeries(state.series, true);
            renderProcessLegend(currentProcessPeriod);
        }

        async function loadProcessDate(date) {
            const input = document.querySelector('#processDate');
            const spinner = document.querySelector('#processDateSpinner');
            const error = document.querySelector('#processDateError');

            if (! input || ! date) {
                return;
            }

            const previousDate = input.dataset.currentDate;
            input.disabled = true;
            if (spinner) spinner.classList.remove('hidden');
            error?.classList.add('hidden');

            try {
                const endpoint = new URL(dashboardDataUrl, window.location.origin);
                endpoint.searchParams.set('fecha', date);
                endpoint.searchParams.set('distribucion_diaria', '1');

                const response = await fetch(endpoint.toString(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (! response.ok) {
                    throw new Error(`Dashboard request failed with status ${response.status}`);
                }

                const payload = await response.json();

                if (! Array.isArray(payload.areas) || ! payload.fecha) {
                    throw new Error('Dashboard response is invalid');
                }

                summaries.dia = payload.areas;
                input.value = payload.fecha;
                input.dataset.currentDate = payload.fecha;
                updateProcessChart();

                const pageUrl = new URL(window.location.href);
                pageUrl.searchParams.set('fecha', payload.fecha);
                pageUrl.searchParams.delete('distribucion_diaria');
                window.history.replaceState({}, '', pageUrl);
            } catch {
                input.value = previousDate;
                error?.classList.remove('hidden');
            } finally {
                input.disabled = false;
                if (spinner) spinner.classList.add('hidden');
            }
        }

        function updateTrend(period) {
            currentTrendPeriod = period;
            const areas = trendAreas(period);
            setActiveTab('[data-trend-period]', period, 'trendPeriod');
            document.querySelector('#trendPeriodLabel').textContent = periodLabels[period];

            trendChart?.updateOptions({
                colors: areas.map(area => areaColor(area.key)),
                xaxis: { categories: trends[period].labels }
            }, false, true);
            trendChart?.updateSeries(trendSeries(period), true);
        }

        function updateChartsTheme() {
            const theme = chartTheme();
            const trendColors = trendAreas(currentTrendPeriod).map(area => areaColor(area.key));
            const process = processState(currentProcessPeriod);

            trendChart?.updateOptions({
                colors: trendColors,
                chart: { foreColor: theme.text },
                grid: { borderColor: theme.grid },
                xaxis: { labels: { style: { colors: theme.muted } } },
                yaxis: { labels: { style: { colors: theme.muted } } },
                legend: { labels: { colors: theme.muted } },
                tooltip: { theme: theme.tooltip }
            }, false, true);

            processChart?.updateOptions({
                colors: process.colors,
                chart: { foreColor: theme.text },
                tooltip: { theme: theme.tooltip },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                name: { color: theme.muted },
                                value: { color: theme.text },
                                total: { color: theme.muted }
                            }
                        }
                    }
                }
            }, false, true);

            renderProcessLegend(currentProcessPeriod);
        }

        function revealDashboard() {
            const elements = document.querySelectorAll('.reveal-on-scroll');

            if (! ('IntersectionObserver' in window)) {
                elements.forEach(element => {
                    element.classList.add('is-visible');
                });

                return;
            }

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (! entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');

                    if (entry.target.querySelector('#areaTrendChart')) {
                        trendChart?.updateSeries(trendSeries(currentTrendPeriod), true);
                    }

                    if (entry.target.querySelector('#processChart')) {
                        processChart?.updateSeries(processState(currentProcessPeriod).series, true);
                    }

                    observer.unobserve(entry.target);
                });
            }, {
                threshold: .12,
                rootMargin: '0px 0px -48px 0px'
            });

            elements.forEach(element => observer.observe(element));
        }

        document.querySelectorAll('[data-trend-period]').forEach(button => {
            button.addEventListener('click', () => updateTrend(button.dataset.trendPeriod));
        });

        document.querySelector('#processDate')?.addEventListener('change', event => {
            const date = event.currentTarget.value;

            if (! date) {
                event.currentTarget.value = event.currentTarget.dataset.currentDate;

                return;
            }

            loadProcessDate(date);
        });

        // ── Resumen de áreas por rango de fecha en vivo ──
        const areaRangeFrom = document.getElementById('areaRangeFrom');
        const areaRangeTo = document.getElementById('areaRangeTo');
        const areaRangeCurrentMonthBtn = document.getElementById('areaRangeCurrentMonthBtn');
        const areaRangeTodayBtn = document.getElementById('areaRangeTodayBtn');
        const areaSummaryPeriodLabel = document.getElementById('areaSummaryPeriodLabel');
        const areaSummaryCardsContainer = document.getElementById('areaSummaryCardsContainer');

        let loadingAreaRange = false;

        async function loadAreaRangeSummary(desde, hasta, isResetMonth = false, isToday = false) {
            if (loadingAreaRange) return;
            loadingAreaRange = true;

            if (areaRangeFrom) areaRangeFrom.disabled = true;
            if (areaRangeTo) areaRangeTo.disabled = true;
            if (areaSummaryCardsContainer) {
                areaSummaryCardsContainer.style.opacity = '0.45';
                areaSummaryCardsContainer.style.pointerEvents = 'none';
            }

            try {
                const endpoint = new URL(dashboardDataUrl, window.location.origin);
                endpoint.searchParams.set('resumen_rango', '1');
                if (!isResetMonth && desde && hasta) {
                    endpoint.searchParams.set('fecha_desde', desde);
                    endpoint.searchParams.set('fecha_hasta', hasta);
                }

                const response = await fetch(endpoint.toString(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Error ${response.status}`);
                }

                const data = await response.json();

                if (data.ok && data.areas) {
                    if (areaSummaryPeriodLabel && data.label) {
                        areaSummaryPeriodLabel.textContent = data.label;
                    }

                    if (data.fecha_desde && areaRangeFrom) {
                        areaRangeFrom.value = data.fecha_desde;
                    }
                    if (data.fecha_hasta && areaRangeTo) {
                        areaRangeTo.value = data.fecha_hasta;
                    }

                    if (areaRangeCurrentMonthBtn) {
                        areaRangeCurrentMonthBtn.classList.toggle('is-active', !data.is_custom_range || isResetMonth);
                    }
                    if (areaRangeTodayBtn) {
                        areaRangeTodayBtn.classList.toggle('is-active', isToday);
                    }

                    Object.keys(data.areas).forEach(key => {
                        const area = data.areas[key];
                        const card = document.getElementById(`areaCard_${key}`);
                        if (!card) return;

                        const purEl = card.querySelector('[data-area-metric="puros"]');
                        if (purEl) purEl.textContent = area.puros_formatted;

                        const progEl = card.querySelector('[data-area-metric="progress"]');
                        if (progEl) {
                            progEl.style.width = `${area.share}%`;
                            progEl.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', area.share);
                        }

                        const empEl = card.querySelector('[data-area-metric="empleados"]');
                        if (empEl) empEl.textContent = area.empleados_formatted;

                        const actEl = card.querySelector('[data-area-metric="actividades"]');
                        if (actEl) actEl.textContent = area.actividades_formatted;

                        const regEl = card.querySelector('[data-area-metric="registros"]');
                        if (regEl) regEl.textContent = area.registros_formatted;
                    });
                }
            } catch (err) {
                console.error('Error al actualizar resumen de áreas:', err);
            } finally {
                loadingAreaRange = false;
                if (areaRangeFrom) areaRangeFrom.disabled = false;
                if (areaRangeTo) areaRangeTo.disabled = false;
                if (areaSummaryCardsContainer) {
                    areaSummaryCardsContainer.style.opacity = '1';
                    areaSummaryCardsContainer.style.pointerEvents = 'auto';
                }
            }
        }

        areaRangeFrom?.addEventListener('change', () => {
            const desde = areaRangeFrom.value;
            const hasta = areaRangeTo.value;
            if (desde && hasta) {
                loadAreaRangeSummary(desde, hasta);
            }
        });

        areaRangeTo?.addEventListener('change', () => {
            const desde = areaRangeFrom.value;
            const hasta = areaRangeTo.value;
            if (desde && hasta) {
                loadAreaRangeSummary(desde, hasta);
            }
        });

        areaRangeCurrentMonthBtn?.addEventListener('click', () => {
            const start = areaRangeCurrentMonthBtn.dataset.monthStart;
            const end = areaRangeCurrentMonthBtn.dataset.monthEnd;
            loadAreaRangeSummary(start, end, true, false);
        });

        areaRangeTodayBtn?.addEventListener('click', () => {
            const today = areaRangeTodayBtn.dataset.today;
            loadAreaRangeSummary(today, today, false, true);
        });

        revealDashboard();

        if (typeof ApexCharts !== 'undefined') {
            renderCharts();
        }

        new MutationObserver(updateChartsTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    });
</script>

</body>
</html>
