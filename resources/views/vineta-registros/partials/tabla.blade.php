@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'fecha_registro');
        $direccionActual = request('direccion', 'desc');
        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return route('vineta-registros.index', array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'fecha_registro');
        $direccionActual = request('direccion', 'desc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };

    $emptyColspan = ($hasMinutosTrabajados ?? false) ? 24 : 23;
@endphp


<script id="vinetaRegistrosSeguimientoData" type="application/json">@json([
    'timelines' => $seguimientoTimelineMap,
    'summaries' => $seguimientoResumenMap,
])</script>

<div id="vinetaRegistrosTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll vineta-registros-table-scroll">
        <table class="vinetas-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
            <tr>
                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('fecha_registro') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Fecha
                        <span class="text-xs">{{ $sortIcon('fecha_registro') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('vineta_api_id') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Viñeta
                        <span class="text-xs">{{ $sortIcon('vineta_api_id') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('producto_item') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Item
                        <span class="text-xs">{{ $sortIcon('producto_item') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('presentacion') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Presentación
                        <span class="text-xs">{{ $sortIcon('presentacion') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('producto_codigo') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Código de producto
                        <span class="text-xs">{{ $sortIcon('producto_codigo') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('marca') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Marca
                        <span class="text-xs">{{ $sortIcon('marca') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold min-w-[220px]">
                    <a href="{{ $sortLink('producto_nombre') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Nombre
                        <span class="text-xs">{{ $sortIcon('producto_nombre') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('vitola') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Vitola
                        <span class="text-xs">{{ $sortIcon('vitola') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('capa') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Capa
                        <span class="text-xs">{{ $sortIcon('capa') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('tipo_empaque') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Tipo de empaque
                        <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('orden_del_sistema') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Orden del sistema
                        <span class="text-xs">{{ $sortIcon('orden_del_sistema') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('orden') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Orden del cliente
                        <span class="text-xs">{{ $sortIcon('orden') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold min-w-[180px]">
                    <a href="{{ $sortLink('actividad_nombre') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Actividad
                        <span class="text-xs">{{ $sortIcon('actividad_nombre') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-bold min-w-[190px]">
                    <a href="{{ $sortLink('empleado_nombre') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Empleado
                        <span class="text-xs">{{ $sortIcon('empleado_nombre') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('cantidad_puros') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Puros
                        <span class="text-xs">{{ $sortIcon('cantidad_puros') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('cantidad_cajones') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Cajones
                        <span class="text-xs">{{ $sortIcon('cantidad_cajones') }}</span>
                    </a>
                </th>

                @if ($hasMinutosTrabajados)
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('minutos_trabajados') }}"
                           class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Tiempo
                            <span class="text-xs">{{ $sortIcon('minutos_trabajados') }}</span>
                        </a>
                    </th>
                @endif

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('cantidad_actividades') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Cant. act.
                        <span class="text-xs">{{ $sortIcon('cantidad_actividades') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Total act.</th>

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('precio_mo') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Precio
                        <span class="text-xs">{{ $sortIcon('precio_mo') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Total</th>
                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">Estado</th>
                <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                    <a href="{{ $sortLink('registrado_por_nombre') }}"
                       class="vineta-registros-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                        Responsable
                        <span class="text-xs">{{ $sortIcon('registrado_por_nombre') }}</span>
                    </a>
                </th>
                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">Acciones</th>
            </tr>

            </thead>

            <tbody class="divide-y theme-divide">
            @forelse ($registros as $registro)
                @php
                    $isHoraOrdinaria = ($registro->reporte_tipo ?? 'vineta') === 'hora_ordinaria';
                    $isRegistroPorHora = ! $isHoraOrdinaria && method_exists($registro, 'esPorHoraOrdinario') && $registro->esPorHoraOrdinario();
                    $horaOrdinariaMinutos = $isHoraOrdinaria ? (int) ($registro->minutos ?? 0) : 0;
                    $horaOrdinariaHoras = intdiv($horaOrdinariaMinutos, 60);
                    $horaOrdinariaResto = $horaOrdinariaMinutos % 60;
                @endphp
                <tr class="vinetas-table-row transition {{ ($isHoraOrdinaria || $isRegistroPorHora) ? 'vinetas-row-no-hover' : 'theme-row' }}">
                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $registro->fechaHoraRegistroTexto() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($isHoraOrdinaria)
                            <div class="flex flex-col gap-1 items-start">
                                <span class="vinetas-id-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">Ordinaria</span>
                                @if (!empty($registro->documento_numero))
                                    <span class="vinetas-doc-badge inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold border">Doc. #{{ $registro->documento_numero }}</span>
                                @endif
                            </div>
                        @else
                            <div class="flex flex-col gap-1 items-start">
                                <span class="vinetas-id-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                    @if ($registro->codigo_vineta && (str_starts_with(strtolower($registro->codigo_vineta), 'o-') || str_starts_with(strtolower($registro->codigo_vineta), 'or-') || str_starts_with(strtolower($registro->codigo_vineta), 'l-') || str_starts_with(strtolower($registro->codigo_vineta), 'p-') || str_starts_with(strtolower($registro->codigo_vineta), 'r-')))
                                        ID {{ $registro->codigo_vineta }}
                                    @elseif ((string)$registro->actividad_codigo === '103' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'limpiez') || str_contains(strtolower($registro->actividad_nombre ?? ''), 'limpiad'))
                                        ID {{ (str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'l-')) ? $registro->codigo_vineta : 'l-1' }}
                                    @elseif ((string)$registro->actividad_codigo === '1' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'prens'))
                                        ID {{ (str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'p-')) ? $registro->codigo_vineta : 'p-1' }}
                                    @elseif ((string)$registro->actividad_codigo === '171' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'rola'))
                                        ID {{ (str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'r-')) ? $registro->codigo_vineta : 'r-1' }}
                                    @else
                                        ID {{ $registro->vineta_api_id ?? $registro->vineta_id }}
                                    @endif
                                </span>
                                @if (!empty($registro->documento_numero))
                                    <span class="vinetas-doc-badge inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold border">Doc. #{{ $registro->documento_numero }}</span>
                                @else
                                    <span class="vinetas-no-doc-badge inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border">Sin doc.</span>
                                @endif
                                @if ($isRegistroPorHora)
                                    <span class="theme-badge inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-black border">Por hora</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->productoItemReporte() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $isHoraOrdinaria ? 'N/A' : ($registro->producto?->presentacion?->nombre ?? 'N/A') }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->productoCodigoReporte() }}
                    </td>

                    <td class="px-4 py-3 theme-title font-semibold whitespace-nowrap">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->marcaReporte() }}
                    </td>

                    <td class="px-4 py-3 min-w-[220px] theme-text">
                        <p class="leading-tight">
                            {{ $isHoraOrdinaria ? 'Hora ordinaria' : $registro->productoNombreReporte() }}
                        </p>
                        @if ($isHoraOrdinaria)
                            <p class="theme-text text-xs mt-0.5">{{ $registro->observacion }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->vitolaReporte() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->capaReporte() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->tipoEmpaqueReporte() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->ordenDelSistemaReporte() }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                        {{ $isHoraOrdinaria ? 'N/A' : $registro->ordenReporte() }}
                    </td>

                    <td class="px-4 py-3 min-w-[180px]">
                        <p class="theme-title font-semibold leading-tight">{{ $registro->actividad_nombre }}</p>
                        <p class="theme-text text-xs mt-0.5">
                            @if ($isHoraOrdinaria)
                                Registro manual
                            @elseif ($isRegistroPorHora)
                                Por hora ordinario
                            @else
                                {{ $registro->actividad_codigo ?? $registro->actividad_tipo_empaque ?? 'N/A' }}
                            @endif
                        </p>
                    </td>

                    <td class="px-4 py-3 min-w-[190px]">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#0f172a] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ mb_substr($registro->empleado_nombre, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="theme-title font-semibold leading-tight truncate">{{ $registro->empleado_nombre }}</p>
                                <p class="theme-text text-xs mt-0.5">{{ $registro->empleado_codigo }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-right theme-title font-semibold whitespace-nowrap">{{ number_format($registro->cantidad_puros) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap theme-text">{{ number_format($registro->cantidad_cajones) }}</td>

                    @if ($hasMinutosTrabajados)
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-title font-semibold">
                            {{ method_exists($registro, 'tiempoTrabajadoReporteTexto') ? $registro->tiempoTrabajadoReporteTexto() : $registro->tiempoTrabajadoTexto() }}
                        </td>
                    @endif

                    <td class="px-4 py-3 text-right whitespace-nowrap theme-text">{{ number_format($registro->cantidadActividadesValor()) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap theme-title font-semibold">{{ number_format($registro->total_actividades) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap theme-text">{{ number_format((float) ($isHoraOrdinaria ? 0 : ($registro->precioMoEfectivo() ?? 0)), 7) }}</td>
                    <td class="px-4 py-3 text-right theme-title font-semibold whitespace-nowrap">{{ number_format($registro->total_mo, 2) }}</td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-badge vinetas-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border">
                            {{ ucfirst($registro->estado) }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap theme-text">
                        {{ $registro->responsableTexto() }}
                    </td>

                    <td class="px-3 py-3 text-right whitespace-nowrap">

                        @if ($isHoraOrdinaria)
                            @if(!auth()->user()?->esSoloSupervisor())
                                <div class="vineta-actions inline-flex items-center justify-end overflow-hidden rounded-xl border theme-border">
                                    <button type="button"
                                            title="Editar hora ordinaria"
                                            aria-label="Editar hora ordinaria"
                                            class="hora-ordinaria-edit vineta-action-btn vineta-action-btn-secondary"
                                            data-action="{{ route('vineta-registros.horas-ordinarias.update', $registro) }}"
                                            data-fecha="{{ optional($registro->fecha)->format('Y-m-d') }}"
                                            data-horas="{{ $horaOrdinariaHoras }}"
                                            data-minutos="{{ $horaOrdinariaResto }}"
                                            data-empleado-codigo="{{ $registro->empleado_codigo }}"
                                            data-empleado-nombre="{{ $registro->empleado_nombre }}"
                                            data-observacion="{{ $registro->observacion }}">
                                        Editar
                                    </button>
                                    <form method="POST"
                                          action="{{ route('vineta-registros.horas-ordinarias.destroy', $registro) }}"
                                          class="form-eliminar-hora-ordinaria inline-flex">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Eliminar hora ordinaria"
                                                aria-label="Eliminar hora ordinaria"
                                                class="vineta-action-btn vineta-action-btn-danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="theme-text text-xs opacity-40">-</span>
                            @endif
                        @else
                            <div class="vineta-actions inline-flex items-center justify-end overflow-hidden rounded-xl border theme-border">
                                <button type="button"
                                        title="Ver seguimiento"
                                        aria-label="Ver seguimiento"
                                        class="vineta-registro-seguimiento vineta-action-btn vineta-action-btn-primary"
                                        data-vineta-id="{{ $registro->vineta_id }}">
                                    Seguimiento
                                </button>
                                @if(!auth()->user()?->esSoloSupervisor())
                                <button type="button"
                                        title="Editar registro"
                                        aria-label="Editar registro"
                                        class="vineta-registro-edit vineta-action-btn vineta-action-btn-secondary"
                                        data-action="{{ route('vineta-registros.update', $registro) }}"
                                        data-fecha="{{ optional($registro->fecha_registro)->format('Y-m-d') }}"
                                        data-hora="{{ substr((string) $registro->hora_registro, 0, 5) }}"
                                        data-cantidad="{{ $registro->cantidad_puros }}"
                                        data-minutos="{{ $registro->minutos_trabajados ?? '' }}"
                                        data-por-hora="{{ $isRegistroPorHora ? '1' : '0' }}"
                                        data-empleado-codigo="{{ $registro->empleado_codigo }}"
                                        data-empleado-nombre="{{ $registro->empleado_nombre }}"
                                        data-vineta="ID {{ ($registro->codigo_vineta && (str_starts_with(strtolower($registro->codigo_vineta), 'o-') || str_starts_with(strtolower($registro->codigo_vineta), 'or-') || str_starts_with(strtolower($registro->codigo_vineta), 'l-') || str_starts_with(strtolower($registro->codigo_vineta), 'p-') || str_starts_with(strtolower($registro->codigo_vineta), 'r-'))) ? $registro->codigo_vineta : (((string)$registro->actividad_codigo === '103' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'limpiez') || str_contains(strtolower($registro->actividad_nombre ?? ''), 'limpiad')) ? ((str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'l-')) ? $registro->codigo_vineta : 'l-1') : (((string)$registro->actividad_codigo === '1' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'prens')) ? ((str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'p-')) ? $registro->codigo_vineta : 'p-1') : (((string)$registro->actividad_codigo === '171' || str_contains(strtolower($registro->actividad_nombre ?? ''), 'rola')) ? ((str_starts_with(strtolower($registro->codigo_vineta ?? ''), 'r-')) ? $registro->codigo_vineta : 'r-1') : ($registro->vineta_api_id ?? $registro->vineta_id)))) }}"
                                        data-actividad="{{ $registro->actividad_nombre }}">
                                    Editar
                                </button>
                                <form method="POST"
                                      action="{{ route('vineta-registros.destroy', $registro) }}"
                                      class="form-eliminar-vineta-registro inline-flex">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="Eliminar registro"
                                            aria-label="Eliminar registro"
                                            class="vineta-action-btn vineta-action-btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $emptyColspan }}" class="px-4 py-12 text-center">
                        <p class="theme-title font-bold">No hay viñetas registradas</p>
                        <p class="theme-text text-sm mt-1">Guarda registros desde el móvil o cambia los filtros de búsqueda.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div id="vinetaRegistrosFloatingScroll"
         class="vinetas-floating-scrollbar"
         aria-hidden="true">
        <div class="vinetas-floating-scrollbar-inner"></div>
    </div>

    <div class="vineta-registros-table-footer theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->total() }}</span>
                registro(s)
            </p>

            <form method="GET"
                  action="{{ route('vineta-registros.index') }}"
                  class="per-page-control vineta-registros-ajax-per-page-form">
                @foreach(request()->except('per_page', 'page') as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label class="per-page-label">Mostrar:</label>
                <select name="per_page"
                        onchange="this.form.requestSubmit()"
                        class="per-page-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPageSelected === $option)>{{ $option }}</option>
                    @endforeach
                    <option value="all" @selected($perPageSelected === 'all')>Todos</option>
                </select>
            </form>
        </div>

        <div class="pagination-cafe vineta-registros-ajax-pagination">
            {{ $registros->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
