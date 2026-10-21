<?php

/**
 * Quita el BOM (EF BB BF) que la subida de archivos le mete a los .php y .json
 * del proyecto. Un BOM en un .php imprime 3 bytes antes de cada respuesta
 * (rompe el JSON que consume la app) y en un .json rompe la lectura.
 *
 * Uso:  http://192.168.2.7:8081/fix-bom.php?key=CLAVE
 * Luego BORRAR este archivo del servidor.
 */

$claveEsperada = 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d';

if (($_GET['key'] ?? '') !== $claveEsperada) {
    http_response_code(403);
    exit('No autorizado');
}

header('Content-Type: application/json; charset=utf-8');

$base = dirname(__DIR__);
$saltar = ['vendor', 'node_modules', 'storage', '.git'];

$arreglados = [];
$revisados = 0;

try {
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            function ($file) use ($saltar) {
                if ($file->isDir()) {
                    return ! in_array($file->getFilename(), $saltar, true);
                }

                return true;
            }
        )
    );

    foreach ($it as $archivo) {
        if (! $archivo->isFile()) {
            continue;
        }

        $ruta = $archivo->getPathname();

        if (! preg_match('/\.(php|json)$/i', $archivo->getFilename())) {
            continue;
        }

        $revisados++;
        $contenido = @file_get_contents($ruta);

        if ($contenido === false || substr($contenido, 0, 3) !== "\xEF\xBB\xBF") {
            continue;
        }

        if (@file_put_contents($ruta, substr($contenido, 3)) !== false) {
            $arreglados[] = str_replace($base, '', $ruta);
        }
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'ok' => true,
    'revisados' => $revisados,
    'arreglados' => $arreglados,
    'mensaje' => 'BOM quitado. Ahora borra este archivo y corre: php artisan route:clear && php artisan config:clear && php artisan view:clear',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
