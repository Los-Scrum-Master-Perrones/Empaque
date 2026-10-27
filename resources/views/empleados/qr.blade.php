<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR de empleados | Sistema de Empaque</title>

    @vite(['resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
            color: #0b1220;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        }

        .qr-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            background: #ffffff;
            border-bottom: 1px solid #dbe3f0;
        }

        .qr-toolbar h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .qr-toolbar p {
            margin: 2px 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .qr-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid #dbe3f0;
            background: #ffffff;
            color: #0b1220;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .qr-btn-primary {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .qr-hoja {
            display: grid;
            grid-template-columns: repeat(2, 92mm);
            gap: 2mm;
            justify-content: center;
            padding: 8mm 4mm 12mm;
        }

        .qr-badge {
            width: 92mm;
            height: 54mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 1.2mm;
            padding: 2.5mm 4mm;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4mm;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .qr-badge-nombre {
            margin: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #0b1220;
        }

        .qr-badge-canvas {
            display: block;
            width: 28mm;
            height: 28mm;
        }

        .qr-badge-pie {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-badge-codigo {
            font-size: 16pt;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.5px;
            color: #1d4ed8;
        }

        .qr-badge-area {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46mm;
            padding: 1.2mm 5mm;
            border-radius: 999px;
            background: #1d4ed8;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .qr-error {
            margin: 24px auto;
            max-width: 520px;
            padding: 16px;
            border-radius: 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 700;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .qr-toolbar {
                display: none !important;
            }

            .qr-hoja {
                padding: 0;
                gap: 3mm;
            }

            .qr-badge {
                border: 1px dashed #cbd5e1;
            }
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }
    </style>
</head>
<body>
    <div class="qr-toolbar">
        <div>
            <h1>QR de empleados ({{ $empleados->count() }})</h1>
            <p>Usá Imprimir y elegí "Guardar como PDF" o la impresora. Cada hoja trae 10 carnets.</p>
        </div>

        <div class="qr-toolbar-actions">
            <a href="{{ route('empleados.index') }}" class="qr-btn">Volver</a>
            <button type="button" class="qr-btn qr-btn-primary" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    <div class="qr-hoja" id="qrHoja">
        @foreach ($empleados as $empleado)
            <div class="qr-badge">
                <span class="qr-badge-area">Empaque</span>

                <canvas class="qr-badge-canvas"
                        data-codigo="{{ $empleado->codigo }}"
                        width="420"
                        height="420"></canvas>

                <p class="qr-badge-nombre">{{ $empleado->nombre }}</p>

                <div class="qr-badge-pie">
                    <span class="qr-badge-codigo">{{ $empleado->codigo }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lienzos = Array.from(document.querySelectorAll('.qr-badge-canvas'));

            if (!lienzos.length) {
                return;
            }

            if (!window.QRCode) {
                const aviso = document.createElement('div');
                aviso.className = 'qr-error';
                aviso.textContent = 'No se pudo cargar el generador de QR. Recargá la página.';
                document.getElementById('qrHoja').before(aviso);

                return;
            }

            lienzos.forEach((lienzo) => {
                window.QRCode.toCanvas(lienzo, lienzo.dataset.codigo, {
                    width: 420,
                    margin: 1,
                    errorCorrectionLevel: 'M',
                    color: {
                        dark: '#000000',
                        light: '#ffffff',
                    },
                }, (error) => {
                    if (error) {
                        console.error('QR empleado ' + lienzo.dataset.codigo, error);

                        return;
                    }

                    // qrcode.js fija width/height inline; la hoja controla el tamaño en mm.
                    lienzo.removeAttribute('style');
                });
            });
        });
    </script>
</body>
</html>
