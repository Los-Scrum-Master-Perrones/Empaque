<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppUpdateController extends Controller
{
    /**
     * Path al archivo de configuración de versión.
     */
    private string $versionFilePath = 'app-version.json';

    /**
     * Obtiene la información de la última versión de la app móvil.
     */
    public function check(Request $request): JsonResponse
    {
        $info = $this->getVersionInfo($request);

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    /**
     * Descarga el APK solicitado.
     */
    public function download(Request $request, ?string $file = null)
    {
        $info = $this->getVersionInfo($request);
        $fileName = $file ?? ($info['apk_filename'] ?? 'app-release.apk');

        // Limpiar nombre de archivo por seguridad
        $fileName = basename($fileName);
        if (!str_ends_with(strtolower($fileName), '.apk')) {
            $fileName .= '.apk';
        }

        // Buscar en posibles ubicaciones
        $possiblePaths = [
            public_path('downloads/' . $fileName),
            public_path('apks/' . $fileName),
            storage_path('app/public/apks/' . $fileName),
            storage_path('app/apks/' . $fileName),
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                // El APK tiene que ser un ZIP (empieza con "PK"). Si se subio
                // como texto (BOM UTF-8) Android lo rechaza con
                // "Se produjo un error durante el análisis del paquete".
                $inicio = '';
                $handle = @fopen($path, 'rb');
                if ($handle) {
                    $inicio = (string) fread($handle, 2);
                    fclose($handle);
                }

                if ($inicio !== 'PK') {
                    return response()->json([
                        'success' => false,
                        'message' => "El APK '{$fileName}' del servidor está dañado (no es un archivo válido). Vuelve a subirlo en modo binario.",
                    ], 500);
                }

                return response()->download($path, $fileName, [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "El archivo APK '{$fileName}' no se encuentra en el servidor. Colócalo en 'public/downloads/' o 'storage/app/public/apks/'.",
        ], 404);
    }

    /**
     * Sube un archivo (APK o app-version.json) por PUT con el cuerpo binario.
     * Asi no se guarda como texto (que le mete BOM y daña el APK).
     *
     * PUT /api/app/upload/{file}
     * Header: X-Upload-Key
     */
    public function upload(Request $request, ?string $file = null)
    {
        $claveEsperada = (string) config('services.erp.api_key');
        $clave = (string) $request->header('X-Upload-Key', '');

        if ($claveEsperada === '' || ! hash_equals($claveEsperada, $clave)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 401);
        }

        $fileName = basename((string) $file);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return response()->json(['success' => false, 'message' => 'Nombre de archivo inválido.'], 422);
        }

        if (! in_array($ext, ['apk', 'json'], true)) {
            return response()->json(['success' => false, 'message' => 'Solo se aceptan .apk o .json.'], 422);
        }

        $destino = public_path('downloads/' . $fileName);

        if (! is_dir(dirname($destino))) {
            @mkdir(dirname($destino), 0775, true);
        }

        $in = @fopen('php://input', 'rb');
        $out = @fopen($destino, 'wb');

        if (! $in || ! $out) {
            return response()->json(['success' => false, 'message' => 'No se pudo escribir el archivo en el servidor.'], 500);
        }

        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        clearstatcache(true, $destino);
        $size = (int) @filesize($destino);

        // Validar que llegó completo y con el contenido correcto.
        $handle = @fopen($destino, 'rb');
        $inicio = $handle ? (string) fread($handle, 2) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($ext === 'apk' && $inicio !== 'PK') {
            @unlink($destino);

            return response()->json([
                'success' => false,
                'message' => 'El APK llegó dañado (no empieza con PK). No se guardó.',
            ], 422);
        }

        if ($ext === 'json' && json_decode((string) @file_get_contents($destino), true) === null) {
            @unlink($destino);

            return response()->json([
                'success' => false,
                'message' => 'El JSON llegó dañado. No se guardó.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Archivo {$fileName} guardado correctamente.",
            'archivo' => $fileName,
            'bytes' => $size,
            'sha256' => hash_file('sha256', $destino),
            'inicio' => bin2hex($inicio),
        ], 200);
    }

    /**
     * Carga o genera la información de versión actual.
     */
    private function getVersionInfo(Request $request): array
    {
        $default = [
            'version_name' => '2.0.0',
            'version_code' => 24,
            'release_notes' => "Versión 2.0.0 (Build 23)\n• Ranking mensual de empleados por área (Anillado, Rezago y Llenado) con podio Top 3\n• Filtro estricto por puesto de trabajo (anilladoras/celofanadoras en Anillado, rezagadoras y 8219/8217 en Rezago, llenadoras de paquetes en Llenado)\n• Selector interactivo de mes",
            'apk_filename' => 'app-arm64-v8a-release.apk',
            'force_update' => false,
            'published_at' => '2026-09-02 08:30:00',
            'download_url' => null,
            'split_apks' => [
                'arm64-v8a' => null,
                'armeabi-v7a' => null,
                'universal' => null,
            ],
        ];

        $possibleJsonPaths = [
            public_path('downloads/app-version.json'),
            public_path('app-version.json'),
            storage_path('app/app-version.json'),
            storage_path('app/private/app-version.json'),
            base_path('app-version.json'),
        ];

        foreach ($possibleJsonPaths as $jsonPath) {
            if (File::exists($jsonPath)) {
                try {
                    $saved = json_decode(File::get($jsonPath), true) ?: [];
                    if (!empty($saved)) {
                        $default = array_merge($default, $saved);
                        break;
                    }
                } catch (\Throwable $e) {
                    // Usar valores por defecto si hay error al parsear
                }
            }
        }

        // Resolver URLs dinámicas basadas en el host actual de la petición
        $baseUrl = $request->getSchemeAndHttpHost();

        if (empty($default['download_url'])) {
            $default['download_url'] = $baseUrl . '/api/app/download/' . ($default['apk_filename'] ?? 'app-release.apk');
        }

        $arm64File = $default['apk_filename'] ?? 'app-arm64-v8a-release.apk';
        $armFile = $default['apk_filename_armeabi_v7a'] ?? 'app-armeabi-v7a-release.apk';
        $universalFile = $default['apk_filename_universal'] ?? 'app-release.apk';

        $default['split_apks'] = [
            'arm64-v8a' => $baseUrl . '/api/app/download/' . $arm64File,
            'armeabi-v7a' => $baseUrl . '/api/app/download/' . $armFile,
            'universal' => $baseUrl . '/api/app/download/' . $universalFile,
        ];

        return $default;
    }
}
