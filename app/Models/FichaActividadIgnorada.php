<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Actividad que NO se muestra en la campanita de fichas de empaque
 * (ej. Rezagado: no pide materiales, asi que no se avisa).
 */
class FichaActividadIgnorada extends Model
{
    use HasFactory;

    protected $table = 'ficha_actividades_ignoradas';

    protected $fillable = [
        'codigo_actividad',
        'nombre',
        'created_by',
    ];

    protected $casts = [
        'codigo_actividad' => 'integer',
        'created_by' => 'integer',
    ];
}
