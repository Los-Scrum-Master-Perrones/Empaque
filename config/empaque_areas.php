<?php

/**
 * Areas que se pueden generar desde Empaque (no vienen de facturacion).
 *
 * Cada area tiene:
 *  - titulo      : como se muestra la viñeta ("LIMPIEZA", "BROCHA")
 *  - prefijo     : prefijo del codigo QR (LI-1, BR-1)
 *  - actividades : codigos de actividad (tabla actividades.codigo_actividad)
 *                  que se pueden marcar al escanear esa viñeta.
 */
return [
    'LIMPIEZA' => [
        'titulo' => 'LIMPIEZA',
        'prefijo' => 'li',
        'actividades' => [103],
    ],

    'BROCHA' => [
        'titulo' => 'BROCHA',
        'prefijo' => 'br',
        'actividades' => [
            92, 94, 95, 96, 97, 111, 112, 125, 126, 130,
            176, 177, 179, 181, 182, 183, 187, 192, 193,
        ],
    ],
];
