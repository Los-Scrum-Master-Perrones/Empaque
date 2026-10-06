<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoEmpaque extends Model
{
    protected $table = 'documento_empaques';

    protected $fillable = [
        'numero',
        'descripcion',
        'observaciones',
        'total',
        'fecha',
        'sucursal',
        'forma_pago',
        'bodega_detalle',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'sucursal' => 'integer',
        'forma_pago' => 'integer',
        'bodega_detalle' => 'integer',
        'total' => 'decimal:2',
    ];


    public function vinetaRegistros(): HasMany
    {
        return $this->hasMany(VinetaRegistro::class, 'documento_empaque_id');
    }

    public function horasOrdinarias(): HasMany
    {
        return $this->hasMany(EmpleadoHoraOrdinaria::class, 'documento_empaque_id');
    }
}
