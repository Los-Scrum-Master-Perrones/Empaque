<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vineta extends Model
{
    protected $fillable = [
        'api_id',
        'id_pendiente_empaque',
        'id_detalle_programacion',
        'fecha',
        'cantidad_puros',
        'estado',
        'impreso',
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
        'api_created_at' => 'datetime',
        'api_updated_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function registros()
    {
        return $this->hasMany(VinetaRegistro::class);
    }
}
