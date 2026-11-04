<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas {{ $titulo ?? '' }} | Sistema de Empaque</title>

    <style>
        /* Mismo formato de viñeta que facturación_plasencia
           (Exports/vinetas-empaque-export-pdf.blade.php):
           3 por fila, 18 por página, 204px de alto, QR arriba a la derecha. */
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #111;
            background: #f3f4f6;
            font-size: 10px;
        }

        .barra {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 14px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
        }

        .barra h1 { margin: 0; font-size: 15px; font-weight: 800; }

        .barra .acciones { display: flex; gap: 8px; }

        .barra button,
        .barra a {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .barra a.secundario {
            background: #fff;
            color: #2563eb;
        }

        .hoja {
            background: #fff;
            margin: 10px auto;
            padding: 4px;
            max-width: 21.59cm;
        }

        .container {
            position: relative;
            width: 32%;
            height: 204px;
            float: left;
            margin: 4px;
            padding: 5px 4px;
            border: 1px solid #ccc;
            border-radius: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
            line-height: 1.35;
        }

        .container .codigo {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
            padding-right: 78px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .container .area {
            display: inline-block;
            margin: 1px 0 2px;
            font-size: 8.5px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .container .qr {
            position: absolute;
            top: 24px;
            right: 5px;
            width: 72px;
            height: 72px;
            background: #fff;
            line-height: 0;
        }

        .container .qr svg,
        .container .qr img {
            width: 72px !important;
            height: 72px !important;
            display: block;
        }

        .container p { margin: 1px 0; }

        .container .etiqueta { font-size: 0.8em; }

        .container b, .container u { font-size: 1em; }

        .clear { clear: both; }

        .salto { page-break-before: always; break-before: page; }

        .vacio {
            padding: 40px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }

        @page {
            margin: 0.2cm;
            size: letter;
        }

        @media print {
            body { background: #fff; }
            .barra { display: none !important; }
            .hoja { margin: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <h1>Viñetas {{ $titulo ?? '' }} — {{ $vinetas->count() }} en total</h1>

        <div class="acciones">
            <a class="secundario" href="{{ route('ordenes.index') }}">Volver a Órdenes</a>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    @if ($vinetas->isEmpty())
        <div class="vacio">No se encontraron viñetas para imprimir.</div>
    @else
        <div class="hoja">
            @foreach ($vinetas as $key => $vineta)
                <div class="container">
                    <span class="codigo"><u>{{ $vineta->codigo_qr }}____________________</u></span>

                    @if (filled($vineta->area))
                        <span class="area">{{ strtoupper((string) $vineta->area) }}</span>
                    @endif

                    <span class="qr" data-qr="{{ $vineta->codigo_qr }}"></span>

                    <p><span class="etiqueta">Item:</span> <b><u>{{ $vineta->item }}</u></b></p>
                    <p><span class="etiqueta">Fecha:</span> ______________</p>
                    <p><span class="etiqueta">Puros:</span> ______</p>
                    <p>
                        <span class="etiqueta">OS:</span> <b>{{ $vineta->orden_del_sistema }}</b>
                        <span class="etiqueta">OC:</span> <b>{{ $vineta->orden }}</b>
                    </p>
                    <p><span class="etiqueta">Marca:</span> <b style="font-size: 0.95em">{{ $vineta->marca }}</b></p>
                    <p><span class="etiqueta">Vitola:</span> <b>{{ $vineta->vitola }}</b> <u>{{ $vineta->nombre }}</u></p>
                    <p><span class="etiqueta">Capa:</span> <b>{{ $vineta->capa }}</b></p>
                    <p><span class="etiqueta">Empaque:</span> <b>{{ $vineta->tipo_empaque }}</b></p>
                    <p><span class="etiqueta">Empleado:</span> _____________</p>
                </div>

                @if ((($key + 1) % 3) === 0)
                    <div class="clear"></div>
                @endif

                @if ((($key + 1) % 18) === 0 && ($key + 1) !== $vinetas->count())
                    <div class="clear"></div>
                    <div class="salto"></div>
                @endif
            @endforeach

            <div class="clear"></div>
        </div>
    @endif

    {{-- Libreria LOCAL (public/js/qrcode-generator.js): no depende de internet. --}}
    <script src="{{ asset('js/qrcode-generator.js') }}"></script>
    <script>
        // El QR lleva el CODIGO de la viñeta (li-1, br-1, pr-1...), que es lo
        // que reconoce el escaner (API de Empaque y la app).
        document.addEventListener('DOMContentLoaded', function () {
            var destinos = document.querySelectorAll('.qr[data-qr]');

            if (typeof qrcode !== 'function') {
                return;
            }

            destinos.forEach(function (destino) {
                try {
                    var qr = qrcode(0, 'M');
                    qr.addData(String(destino.dataset.qr));
                    qr.make();
                    destino.innerHTML = qr.createSvgTag({ cellSize: 3, margin: 0, scalable: true });
                } catch (e) {
                    console.error('QR', e);
                }
            });

            // NO se abre el dialogo solo: se acumulan viñetas de varias ordenes
            // y se imprimen todas juntas al pulsar "Imprimir".
        });
    </script>
</body>
</html>
