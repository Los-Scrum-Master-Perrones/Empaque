<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte por orden | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $total = $totales ?: ['puros_programados' => 0, 'rezago' => 0, 'anillado' => 0, 'llenado' => 0, 'avance' => 0, 'cajas' => 0, 'programaciones' => 0];

    $avanceColor = function ($avance) {
        if ($avance >= 100) {
            return 'text-[#047857]';
        }

        return $avance > 0 ? 'text-[#b45309]' : 'text-[#64748b]';
    };
@endphp

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

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <div class="min-w-0">
                                    <h1 class="theme-title text-lg sm:text-xl font-black">Reporte por orden</h1>
                                    <p class="theme-text text-xs sm:text-sm">
                                        Programado (facturación Plasencia) contra lo hecho en empaque.
                                        <span class="font-bold">{{ $periodoLabel }}</span>
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="GET" action="{{ route('vineta-registros.reporte-orden') }}" class="flex flex-wrap items-end gap-1.5">
                                        <div>
                                            <label for="anio" class="theme-text block text-[11px] font-semibold mb-1">Año</label>
                                            <select id="anio" name="anio"
                                                    class="theme-input rounded-xl px-2 py-1.5 text-xs sm:text-sm">
                                                @foreach($anios as $anioOpcion)
                                                    <option value="{{ $anioOpcion }}" @selected($anioOpcion === $anio)>{{ $anioOpcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="mes" class="theme-text block text-[11px] font-semibold mb-1">Desde el mes</label>
                                            <select id="mes" name="mes"
                                                    class="theme-input rounded-xl px-2 py-1.5 text-xs sm:text-sm">
                                                <option value="">Todo el año</option>
                                                @foreach($meses as $indice => $nombre)
                                                    <option value="{{ $indice + 1 }}" @selected(($indice + 1) === $mes)>{{ $nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-xl bg-[#0f172a] px-4 py-1.5 text-xs sm:text-sm font-semibold text-white transition hover:bg-[#1e293b]">
                                            Filtrar
                                        </button>

                                        <a href="{{ route('vineta-registros.reporte-orden.export', ['anio' => $anio, 'mes' => $mes]) }}"
                                           class="inline-flex items-center justify-center rounded-xl border theme-border bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-[#4f46e5] transition hover:bg-[#eef2ff]">
                                            Exportar Excel
                                        </a>

                                        <a href="{{ route('vineta-registros.index') }}"
                                           class="inline-flex items-center justify-center rounded-xl border theme-border bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-[#0b1220] transition hover:bg-[#f1f5f9]">
                                            Volver
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if($error)
                            <div class="theme-card rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-4">
                                <p class="text-sm font-black text-[#b91c1c]">No se pudo consultar la API de programaciones</p>
                                <p class="text-xs text-[#7f1d1d] mt-1">{{ $error }}</p>
                                <p class="text-xs text-[#7f1d1d] mt-2">
                                    Revisá <span class="font-mono">PROGRAMACIONES_API_URL</span> en el .env de Empaque.
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Órdenes</p>
                                <p id="repoResumenOrdenes" class="theme-title text-xl font-black">{{ number_format($ordenes) }}</p>
                                <p class="theme-text text-[11px]"><span id="repoResumenItems">{{ number_format($items) }}</span> items</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Puros programados</p>
                                <p id="repoResumenPuros" class="theme-title text-xl font-black">{{ number_format($total['puros_programados']) }}</p>
                                <p class="theme-text text-[11px]"><span id="repoResumenCajas">{{ number_format($total['cajas']) }}</span> cajas</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Rezagado</p>
                                <p id="repoResumenRezago" class="theme-title text-xl font-black">{{ number_format($total['rezago']) }}</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Anillado</p>
                                <p id="repoResumenAnillado" class="theme-title text-xl font-black">{{ number_format($total['anillado']) }}</p>
                            </div>

                            <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                                <p class="theme-text text-[11px] font-black uppercase tracking-wide">Llenado / Avance</p>
                                <p id="repoResumenLlenado" class="theme-title text-xl font-black">{{ number_format($total['llenado']) }}</p>
                                <p id="repoResumenAvance" class="text-[11px] font-black {{ $avanceColor($total['avance']) }}">{{ $total['avance'] }}%</p>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <div class="flex flex-wrap items-end justify-between gap-2 mb-2">
                                <div>
                                    <p class="theme-title text-sm font-black">Filtros</p>
                                    <p class="theme-text text-[11px]">
                                        <span id="repoContadorFilas">{{ number_format($items) }}</span> items visibles
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button" class="reporte-orden-chip is-active" data-filtro-avance="todos">Todos</button>
                                    <button type="button" class="reporte-orden-chip" data-filtro-avance="con">Con avance</button>
                                    <button type="button" class="reporte-orden-chip" data-filtro-avance="cero">Solo 0 / 0 / 0</button>
                                    <button type="button" class="reporte-orden-chip" id="repoLimpiarFiltros">Limpiar</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                                <div>
                                    <label for="repoFiltroOrden" class="theme-text block text-[11px] font-semibold mb-1">Orden sis.</label>
                                    <input type="text" id="repoFiltroOrden" data-filtro-campo="orden" placeholder="Ej. 3548"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroCliente" class="theme-text block text-[11px] font-semibold mb-1">Orden cliente</label>
                                    <input type="text" id="repoFiltroCliente" data-filtro-campo="cliente" placeholder="Ej. FTT-1831"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroItem" class="theme-text block text-[11px] font-semibold mb-1">Item</label>
                                    <input type="text" id="repoFiltroItem" data-filtro-campo="item" placeholder="Item"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroCodigo" class="theme-text block text-[11px] font-semibold mb-1">Cód. producto</label>
                                    <input type="text" id="repoFiltroCodigo" data-filtro-campo="codigo" placeholder="Ej. P-02334"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroMarca" class="theme-text block text-[11px] font-semibold mb-1">Marca</label>
                                    <input type="text" id="repoFiltroMarca" data-filtro-campo="marca" placeholder="Marca"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroNombre" class="theme-text block text-[11px] font-semibold mb-1">Nombre</label>
                                    <input type="text" id="repoFiltroNombre" data-filtro-campo="nombre" placeholder="Nombre"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroVitola" class="theme-text block text-[11px] font-semibold mb-1">Vitola</label>
                                    <input type="text" id="repoFiltroVitola" data-filtro-campo="vitola" placeholder="Ej. 6X52"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroCapa" class="theme-text block text-[11px] font-semibold mb-1">Capa</label>
                                    <input type="text" id="repoFiltroCapa" data-filtro-campo="capa" placeholder="Ej. Corojo"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>

                                <div>
                                    <label for="repoFiltroTipo" class="theme-text block text-[11px] font-semibold mb-1">Tipo empaque</label>
                                    <input type="text" id="repoFiltroTipo" data-filtro-campo="tipo" placeholder="Ej. CAJAS 1/20"
                                           class="theme-input w-full rounded-xl px-2 py-1.5 text-xs">
                                </div>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-2">
                            <div class="reporte-orden-scroll">
                                <table id="reporteOrdenTable" class="reporte-orden-table text-sm">
                                    <thead>
                                        <tr>
                                            <th class="repo-col-orden">Orden sis.</th>
                                            <th class="repo-col-cliente">Orden cliente</th>
                                            <th>Item</th>
                                            <th>Cód. producto</th>
                                            <th>Marca</th>
                                            <th>Nombre</th>
                                            <th>Vitola</th>
                                            <th>Capa</th>
                                            <th>Tipo empaque</th>
                                            <th class="reporte-orden-num">Prog.</th>
                                            <th class="reporte-orden-num">Cajas</th>
                                            <th class="reporte-orden-num">Puros prog.</th>
                                            <th class="reporte-orden-num">Rezagado</th>
                                            <th class="reporte-orden-num">Anillado</th>
                                            <th class="reporte-orden-num">Llenado</th>
                                            <th class="reporte-orden-num">Avance</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse($grupos as $grupo)
                                            @foreach($grupo['clientes'] as $cliente)
                                                @foreach($cliente['items'] as $item)
                                                    <tr class="reporte-orden-item"
                                                        data-orden="{{ $grupo['orden_sistema'] }}"
                                                        data-cliente="{{ $cliente['orden_cliente'] }}"
                                                        data-item="{{ $item['item'] }}"
                                                        data-codigo="{{ $item['codigo_producto'] }}"
                                                        data-marca="{{ $item['marca'] }}"
                                                        data-nombre="{{ $item['nombre'] }}"
                                                        data-vitola="{{ $item['vitola'] }}"
                                                        data-capa="{{ $item['capa'] }}"
                                                        data-tipo="{{ $item['tipo_empaque'] }}"
                                                        data-total-key="{{ $grupo['orden_sistema'].'|'.$cliente['orden_cliente'] }}"
                                                        data-programaciones="{{ $item['programaciones'] }}"
                                                        data-cajas="{{ $item['cajas'] }}"
                                                        data-puros="{{ $item['puros_programados'] }}"
                                                        data-rezago="{{ $item['rezago'] }}"
                                                        data-anillado="{{ $item['anillado'] }}"
                                                        data-llenado="{{ $item['llenado'] }}">
                                                        <td class="repo-col-orden">{{ $grupo['orden_sistema'] }}</td>
                                                        <td class="repo-col-cliente">{{ $cliente['orden_cliente'] }}</td>
                                                        <td class="font-mono text-xs">{{ $item['item'] }}</td>
                                                        <td class="font-mono text-xs">{{ $item['codigo_producto'] }}</td>
                                                        <td>{{ $item['marca'] }}</td>
                                                        <td>{{ $item['nombre'] }}</td>
                                                        <td>{{ $item['vitola'] }}</td>
                                                        <td>{{ $item['capa'] }}</td>
                                                        <td>{{ $item['tipo_empaque'] }}</td>
                                                        <td class="reporte-orden-num">{{ number_format($item['programaciones']) }}</td>
                                                        <td class="reporte-orden-num">{{ number_format($item['cajas']) }}</td>
                                                        <td class="reporte-orden-num font-semibold">{{ number_format($item['puros_programados']) }}</td>
                                                        <td class="reporte-orden-num">{{ number_format($item['rezago']) }}</td>
                                                        <td class="reporte-orden-num">{{ number_format($item['anillado']) }}</td>
                                                        <td class="reporte-orden-num">{{ number_format($item['llenado']) }}</td>
                                                        <td class="reporte-orden-num font-black {{ $avanceColor($item['avance']) }}">{{ $item['avance'] }}%</td>
                                                    </tr>
                                                @endforeach

                                                <tr class="reporte-orden-total"
                                                    data-total-key="{{ $grupo['orden_sistema'].'|'.$cliente['orden_cliente'] }}">
                                                    <td colspan="9">Total orden {{ $grupo['orden_sistema'] }} / {{ $cliente['orden_cliente'] }}</td>
                                                    <td class="reporte-orden-num" data-total="programaciones">{{ number_format($cliente['totales']['programaciones']) }}</td>
                                                    <td class="reporte-orden-num" data-total="cajas">{{ number_format($cliente['totales']['cajas']) }}</td>
                                                    <td class="reporte-orden-num" data-total="puros">{{ number_format($cliente['totales']['puros_programados']) }}</td>
                                                    <td class="reporte-orden-num" data-total="rezago">{{ number_format($cliente['totales']['rezago']) }}</td>
                                                    <td class="reporte-orden-num" data-total="anillado">{{ number_format($cliente['totales']['anillado']) }}</td>
                                                    <td class="reporte-orden-num" data-total="llenado">{{ number_format($cliente['totales']['llenado']) }}</td>
                                                    <td class="reporte-orden-num font-black {{ $avanceColor($cliente['totales']['avance']) }}" data-total="avance">{{ $cliente['totales']['avance'] }}%</td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="16" class="py-6 text-center theme-text">
                                                    No hay programaciones en este periodo.
                                                </td>
                                            </tr>
                                        @endforelse

                                        <tr id="repoSinResultados" class="hidden">
                                            <td colspan="16" class="py-6 text-center theme-text">
                                                Ningún item coincide con los filtros.
                                            </td>
                                        </tr>
                                    </tbody>

                                    @if(! empty($grupos))
                                        <tfoot>
                                            <tr>
                                                <td colspan="9">
                                                    TOTAL GENERAL (<span data-total-general="ordenes">{{ $ordenes }}</span> órdenes)
                                                </td>
                                                <td class="reporte-orden-num" data-total-general="programaciones">{{ number_format($total['programaciones']) }}</td>
                                                <td class="reporte-orden-num" data-total-general="cajas">{{ number_format($total['cajas']) }}</td>
                                                <td class="reporte-orden-num" data-total-general="puros">{{ number_format($total['puros_programados']) }}</td>
                                                <td class="reporte-orden-num" data-total-general="rezago">{{ number_format($total['rezago']) }}</td>
                                                <td class="reporte-orden-num" data-total-general="anillado">{{ number_format($total['anillado']) }}</td>
                                                <td class="reporte-orden-num" data-total-general="llenado">{{ number_format($total['llenado']) }}</td>
                                                <td class="reporte-orden-num font-black {{ $avanceColor($total['avance']) }}" data-total-general="avance">{{ $total['avance'] }}%</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const tabla = document.getElementById('reporteOrdenTable');

            if (!tabla) {
                return;
            }

            const campos = Array.from(document.querySelectorAll('[data-filtro-campo]'));
            const chips = Array.from(document.querySelectorAll('[data-filtro-avance]'));
            const limpiar = document.getElementById('repoLimpiarFiltros');
            const contador = document.getElementById('repoContadorFilas');
            const sinResultados = document.getElementById('repoSinResultados');
            const filasItem = Array.from(tabla.querySelectorAll('tr.reporte-orden-item'));
            const filasTotal = Array.from(tabla.querySelectorAll('tr.reporte-orden-total'));

            let filtroAvance = 'todos';

            const normalizar = (valor) => (valor || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();

            const numero = (fila, campo) => Number(fila.dataset[campo] || 0);
            const formatear = (valor) => Number(valor || 0).toLocaleString('es-HN');

            const claseAvance = (avance) => {
                if (avance >= 100) {
                    return 'text-[#047857]';
                }

                return avance > 0 ? 'text-[#b45309]' : 'text-[#64748b]';
            };

            const calcularAvance = (puros, rezago, anillado, llenado) => {
                if (!puros || puros <= 0) {
                    return 0;
                }

                const base = llenado > 0 ? llenado : (anillado > 0 ? anillado : rezago);

                return Math.round((base / puros) * 1000) / 10;
            };

            const pintarAvance = (celda, avance) => {
                if (!celda) {
                    return;
                }

                celda.textContent = String(Number(avance.toFixed(1))) + '%';
                celda.classList.remove('text-[#047857]', 'text-[#b45309]', 'text-[#64748b]');
                celda.classList.add(claseAvance(avance));
            };

            const coincide = (fila) => {
                for (const campo of campos) {
                    const valor = normalizar(campo.value);

                    if (!valor) {
                        continue;
                    }

                    if (normalizar(fila.dataset[campo.dataset.filtroCampo]).indexOf(valor) === -1) {
                        return false;
                    }
                }

                if (filtroAvance !== 'todos') {
                    const hecho = numero(fila, 'rezago') + numero(fila, 'anillado') + numero(fila, 'llenado');

                    if (filtroAvance === 'cero' && hecho !== 0) {
                        return false;
                    }

                    if (filtroAvance === 'con' && hecho === 0) {
                        return false;
                    }
                }

                return true;
            };

            const aplicar = () => {
                const acumulados = new Map();
                const ordenes = new Set();
                const general = { programaciones: 0, cajas: 0, puros: 0, rezago: 0, anillado: 0, llenado: 0 };
                const camposTotales = ['programaciones', 'cajas', 'puros', 'rezago', 'anillado', 'llenado'];
                let visibles = 0;

                filasItem.forEach((fila) => {
                    const visible = coincide(fila);

                    fila.classList.toggle('hidden', !visible);

                    if (!visible) {
                        return;
                    }

                    visibles++;
                    ordenes.add(fila.dataset.orden);

                    const llave = fila.dataset.totalKey;
                    const acumulado = acumulados.get(llave) || {
                        programaciones: 0, cajas: 0, puros: 0, rezago: 0, anillado: 0, llenado: 0,
                    };

                    camposTotales.forEach((campo) => {
                        const valor = numero(fila, campo);
                        acumulado[campo] += valor;
                        general[campo] += valor;
                    });

                    acumulados.set(llave, acumulado);
                });

                filasTotal.forEach((fila) => {
                    const acumulado = acumulados.get(fila.dataset.totalKey);

                    fila.classList.toggle('hidden', !acumulado);

                    if (!acumulado) {
                        return;
                    }

                    camposTotales.forEach((campo) => {
                        const celda = fila.querySelector('[data-total="' + campo + '"]');

                        if (celda) {
                            celda.textContent = formatear(acumulado[campo]);
                        }
                    });

                    pintarAvance(
                        fila.querySelector('[data-total="avance"]'),
                        calcularAvance(acumulado.puros, acumulado.rezago, acumulado.anillado, acumulado.llenado)
                    );
                });

                document.querySelectorAll('[data-total-general]').forEach((celda) => {
                    const campo = celda.dataset.totalGeneral;

                    if (campo === 'avance') {
                        pintarAvance(celda, calcularAvance(general.puros, general.rezago, general.anillado, general.llenado));

                        return;
                    }

                    if (campo === 'ordenes') {
                        celda.textContent = formatear(ordenes.size);

                        return;
                    }

                    celda.textContent = formatear(general[campo]);
                });

                const resumen = {
                    repoResumenOrdenes: ordenes.size,
                    repoResumenItems: visibles,
                    repoResumenPuros: general.puros,
                    repoResumenCajas: general.cajas,
                    repoResumenRezago: general.rezago,
                    repoResumenAnillado: general.anillado,
                    repoResumenLlenado: general.llenado,
                };

                Object.entries(resumen).forEach(([id, valor]) => {
                    const el = document.getElementById(id);

                    if (el) {
                        el.textContent = formatear(valor);
                    }
                });

                pintarAvance(
                    document.getElementById('repoResumenAvance'),
                    calcularAvance(general.puros, general.rezago, general.anillado, general.llenado)
                );

                if (contador) {
                    contador.textContent = formatear(visibles);
                }

                sinResultados?.classList.toggle('hidden', visibles !== 0);
            };

            let temporizador = null;

            const programar = () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(aplicar, 120);
            };

            campos.forEach((campo) => campo.addEventListener('input', programar));

            chips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    filtroAvance = chip.dataset.filtroAvance;

                    chips.forEach((otro) => otro.classList.toggle('is-active', otro === chip));
                    aplicar();
                });
            });

            limpiar?.addEventListener('click', () => {
                campos.forEach((campo) => {
                    campo.value = '';
                });

                filtroAvance = 'todos';
                chips.forEach((chip) => chip.classList.toggle('is-active', chip.dataset.filtroAvance === 'todos'));
                aplicar();
            });

            aplicar();
        })();
    </script>
</body>
</html>
