<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas {{ $titulo ?? '' }} | Sistema de Empaque</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #111;
            background: #f3f4f6;
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

        .ticket {
            position: relative;
            float: left;
            width: 32%;
            height: 204px;
            margin: 4px;
            padding: 5px 6px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 10px;
            line-height: 1.35;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ticket .codigo {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-decoration: underline;
            letter-spacing: 0.4px;
            padding-right: 74px;
        }

        .ticket .area {
            display: inline-block;
            margin-top: 2px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #0f172a;
        }

        .ticket .qr {
            position: absolute;
            top: 22px;
            right: 5px;
            width: 70px;
            height: 70px;
            background: #fff;
            line-height: 0;
        }

        .ticket .qr canvas { width: 70px !important; height: 70px !important; }

        .ticket p { margin: 1px 0; }

        .ticket .etiqueta { font-size: 8px; color: #444; }

        .ticket b, .ticket u { font-size: 10px; }

        .linea { clear: both; }

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
            .barra { display: none; }
            .hoja { margin: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <h1>Viñetas {{ $titulo ?? '' }} — {{ $vinetas->count() }} en total</h1>

        <div class="acciones">
            <a class="secundario" href="{{ route('vinetas-pendientes.index') }}">Volver</a>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    @if ($vinetas->isEmpty())
        <div class="vacio">No se encontraron viñetas para imprimir.</div>
    @else
        <div class="hoja">
            @foreach ($vinetas as $key => $vineta)
                <div class="ticket">
                    <span class="codigo">{{ $vineta->codigo_qr }}</span>
                    <span class="area">{{ strtoupper((string) ($vineta->area ?: $titulo)) }}</span>

                    <div class="qr" data-qr="{{ $vineta->codigo_qr }}"></div>

                    <p><span class="etiqueta">Item:</span> <b><u>{{ $vineta->item }}</u></b></p>
                    <p><span class="etiqueta">Fecha:</span> ______________</p>
                    <p><span class="etiqueta">Puros:</span> ______</p>
                    <p>
                        <span class="etiqueta">OS:</span> <b>{{ $vineta->orden_del_sistema }}</b>
                        <span class="etiqueta">OC:</span> <b>{{ $vineta->orden }}</b>
                    </p>
                    <p><span class="etiqueta">Marca:</span> <b>{{ $vineta->marca }}</b></p>
                    <p><span class="etiqueta">Vitola:</span> <b>{{ $vineta->vitola }}</b> <u>{{ $vineta->nombre }}</u></p>
                    <p><span class="etiqueta">Capa:</span> <b>{{ $vineta->capa }}</b></p>
                    <p><span class="etiqueta">Empaque:</span> <b>{{ $vineta->tipo_empaque }}</b></p>
                    <p><span class="etiqueta">Empleado:</span> _____________</p>
                </div>

                @if ((($key + 1) % 3) === 0)
                    <div class="linea"></div>
                @endif

                @if ((($key + 1) % 18) === 0 && ($key + 1) !== $vinetas->count())
                    <div class="linea"></div>
                    <div class="salto"></div>
                @endif
            @endforeach

            <div class="linea"></div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var destinos = document.querySelectorAll('.qr[data-qr]');

            if (!window.QRCode) {
                return;
            }

            destinos.forEach(function (destino) {
                var canvas = document.createElement('canvas');

                window.QRCode.toCanvas(canvas, String(destino.dataset.qr), {
                    width: 140,
                    margin: 0,
                    color: { dark: '#000000', light: '#ffffff' }
                }, function (error) {
                    if (error) {
                        console.error(error);
                        return;
                    }

                    destino.appendChild(canvas);
                });
            });

            // Deja que se pinten los QR antes de abrir el dialogo de impresion.
            window.setTimeout(function () {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
