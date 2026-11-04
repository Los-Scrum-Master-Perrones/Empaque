<?php

/**
 * Areas que se pueden generar desde Empaque (no vienen de facturacion).
 *
 * Cada area tiene:
 *  - titulo      : como se muestra la viñeta ("LIMPIEZA", "BROCHA")
 *  - prefijo     : prefijo del codigo QR (LI-1, BR-1, PR-1...)
 *  - actividades : codigos de actividad (tabla actividades.codigo_actividad)
 *                  que se pueden marcar al escanear esa viñeta.
 *
 * La viñeta es EXCLUSIVA de su area: al escanearla SOLO se pueden elegir las
 * actividades de esa lista. Las viñetas que vienen de facturacion no tienen
 * area y siguen funcionando como siempre (actividades por producto).
 */
return [
    'LIMPIEZA' => [
        'titulo' => 'LIMPIEZA',
        'prefijo' => 'li',
        'actividades' => [103, 129, 130, 176, 177, 181, 182, 183, 193],
    ],

    'BROCHA' => [
        'titulo' => 'BROCHA',
        'prefijo' => 'br',
        'actividades' => [
            92, 94, 95, 96, 97, 111, 112, 125, 126, 130,
            176, 177, 179, 181, 182, 183, 187, 192, 193,
        ],
    ],

    'PRENSADO' => [
        'titulo' => 'PRENSADO',
        'prefijo' => 'pr',
        'actividades' => [1],
    ],

    'SELLADO' => [
        'titulo' => 'SELLADO',
        'prefijo' => 'se',
        'actividades' => [5, 151, 170, 173, 180, 185, 186, 189, 200, 201],
    ],

    'CELOFANADO' => [
        'titulo' => 'CELOFANADO',
        'prefijo' => 'ce',
        'actividades' => [
            4, 5, 14, 111, 132, 133, 134, 135, 136, 143,
            186, 188, 189, 190, 191,
        ],
    ],

    'ANILLADO' => [
        'titulo' => 'ANILLADO',
        'prefijo' => 'an',
        'actividades' => [
            3, 6, 7, 11, 13, 132, 133, 134, 135, 136,
            138, 139, 140, 143, 151, 155, 156, 169, 170,
            172, 173, 186, 188, 190, 191, 195,
        ],
    ],

    'LLENADO' => [
        'titulo' => 'LLENADO',
        'prefijo' => 'll',
        'actividades' => [
            92, 96, 97, 98, 99, 125, 137, 144, 145, 146,
            147, 148, 149, 152, 157, 158, 159, 160, 164,
            165, 167, 174, 178, 179, 184, 187, 196, 197,
            198, 199,
        ],
    ],

    'TUBOS' => [
        'titulo' => 'TUBOS',
        'prefijo' => 'tu',
        'actividades' => [101, 138, 139, 140, 153, 166, 167, 169, 195],
    ],
];
