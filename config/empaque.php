<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jornada maxima (minutos)
    |--------------------------------------------------------------------------
    | Tope que se puede registrar como jornada / horas ordinarias de un dia.
    | Fijo en 720 (12 h) mientras se define el valor definitivo. Se puede
    | cambiar sin tocar codigo con EMPAQUE_JORNADA_MAX_MINUTOS en el .env.
    */
    'jornada_max_minutos' => (int) env('EMPAQUE_JORNADA_MAX_MINUTOS', 720),
];
