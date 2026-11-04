<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VinetaPorOrden extends Model
{
    protected $table = 'vinetas_por_orden';

    protected $fillable = [
        'codigo_qr',
        'api_id',
        'id_pendiente_empaque',
        'id_detalle_programacion',
        'fecha',
        'cantidad_puros',
        'estado',
        'impreso',
        'impreso_en',
        'api_created_at',
        'api_updated_at',
        'item',
        'orden_del_sistema',
        'mes',
        'orden',
        'marca',
        'nombre',
        'capa',
        'vitola',
        'tipo_empaque',
        'area',
        'codigo_producto',
        'raw_payload',
    ];

    protected $casts = [
        'api_id' => 'integer',
        'fecha' => 'date',
        'cantidad_puros' => 'integer',
        'impreso' => 'boolean',
        'impreso_en' => 'datetime',
        'api_created_at' => 'datetime',
        'api_updated_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
