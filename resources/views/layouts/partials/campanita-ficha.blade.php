{{-- Campanita: avisos de productos SIN FICHA de empaque para esa actividad --}}
<div id="campanitaFicha" class="relative" x-data="{ abierto: false }">
    <button type="button"
            @click="abierto = !abierto"
            title="Productos sin ficha de empaque"
            class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl text-white/85 transition hover:bg-white/10 hover:text-white">

        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0" />
        </svg>

        <span id="campanitaFichaBadge"
              class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 items-center justify-center rounded-full bg-[#dc2626] text-[10px] font-bold text-white shadow"
              aria-live="polite">0</span>
    </button>

    <div x-show="abierto"
         x-transition
         @click.outside="abierto = false"
         class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-200 bg-white shadow-2xl z-[60] overflow-hidden"
         style="display: none;">

        <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
            <p class="text-xs font-bold text-slate-800">Productos sin ficha de empaque</p>
            <span id="campanitaFichaTotal" class="text-[11px] font-bold text-red-600">0</span>
        </div>

        <div id="campanitaFichaLista" class="max-h-72 overflow-y-auto divide-y divide-slate-100">
            <p class="px-3 py-6 text-center text-xs text-slate-500">Revisando...</p>
        </div>

        <a href="{{ route('ficha-faltante.index') }}"
           class="block border-t border-slate-200 px-3 py-2 text-center text-xs font-bold text-[#0f766e] hover:bg-slate-50">
            Ver todo el detalle
        </a>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var badge = document.getElementById('campanitaFichaBadge');
            var lista = document.getElementById('campanitaFichaLista');
            var total = document.getElementById('campanitaFichaTotal');

            if (!badge || !lista) {
                return;
            }

            // Si la pantalla trae ?fecha=YYYY-MM-DD se usa esa fecha.
            var fecha = new URLSearchParams(window.location.search).get('fecha') || '';
            var url = @json(route('ficha-faltante.count')) + (fecha ? '?fecha=' + encodeURIComponent(fecha) : '');

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var n = Number(data && data.total ? data.total : 0);
                    var items = (data && data.items) || [];

                    if (total) {
                        total.textContent = n + ' combinacion(es)';
                    }

                    if (n <= 0) {
                        lista.innerHTML = '<p style="padding:14px;text-align:center;font-size:12px;color:#15803d;font-weight:700">Todo bien: todos tienen ficha</p>';

                        return;
                    }

                    badge.textContent = n > 99 ? '99+' : String(n);
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                    badge.title = n + ' producto(s) sin ficha de empaque';

                    lista.innerHTML = items.map(function (it) {
                        return '<div style="padding:8px 12px;border-left:3px solid #ef4444">'
                            + '<p style="font-size:12px;font-weight:800;color:#0f172a;margin:0">' + (it.producto || '') + '</p>'
                            + (it.producto_nombre
                                ? '<p style="font-size:11px;color:#0f172a;margin:2px 0 0;font-weight:600">' + it.producto_nombre + '</p>'
                                : '')
                            + (it.sku
                                ? '<p style="font-size:10.5px;color:#475569;margin:1px 0 0">SKU ' + it.sku + '</p>'
                                : '')
                            + (it.capa
                                ? '<p style="font-size:10.5px;color:#475569;margin:1px 0 0">Capa ' + it.capa + '</p>'
                                : '')
                            + '<p style="font-size:11px;color:#475569;margin:2px 0 0">' + (it.actividad || '') + '</p>'
                            + '<p style="font-size:11px;color:#475569;margin:0">Empaque ' + (it.empaque || '') + '</p>'
                            + '<p style="font-size:11px;color:#b91c1c;margin:3px 0 0;font-weight:800">'
                            + (it.ultima ? 'Fecha ' + it.ultima + ' · ' : '')
                            + 'NO EXISTE FICHA</p>'
                            + '</div>';
                    }).join('');
                })
                .catch(function () {
                    lista.innerHTML = '<p style="padding:14px;text-align:center;font-size:12px;color:#b91c1c">No se pudo consultar</p>';
                });
        });
    </script>
@endonce
