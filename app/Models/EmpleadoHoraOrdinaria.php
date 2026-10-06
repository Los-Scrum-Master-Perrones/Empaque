<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoHoraOrdinaria extends Model
{
    protected $table = 'empleado_horas_ordinarias';

    protected $fillable = [
        'empleado_id',
        'documento_empaque_id',
        'documento_numero',
        'sucursal',
        'erp_enviado',
        'erp_enviado_en',
        'erp_respuesta',
        'registrado_por_user_id',
        'empleado_codigo',
        'empleado_nombre',
        'fecha',
        'minutos',
        'observacion',
        'registrado_por_nombre',
    ];

    protected $casts = [
        'fecha' => 'date',
        'minutos' => 'integer',
        'sucursal' => 'integer',
        'erp_enviado' => 'boolean',
        'erp_enviado_en' => 'datetime',
        'erp_respuesta' => 'array',
    ];

    public function documentoEmpaque(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DocumentoEmpaque::class, 'documento_empaque_id');
    }

    public function getReporteTipoAttribute(): string
    {
        return 'hora_ordinaria';
    }

    public function getActividadNombreAttribute(): string
    {
        return 'Hora ordinaria';
    }

    public function getActividadCodigoAttribute(): ?string
    {
        return null;
    }

    public function getActividadTipoEmpaqueAttribute(): ?string
    {
        return null;
    }

    public function getProductoNombreAttribute(): string
    {
        return 'Registro manual';
    }

    public function getProductoCodigoAttribute(): ?string
    {
        return null;
    }

    public function getProductoItemAttribute(): ?string
    {
        return null;
    }

    public function getCodigoVinetaAttribute(): ?string
    {
        return null;
    }

    public function getVinetaApiIdAttribute(): ?int
    {
        return null;
    }

    public function getVinetaIdAttribute(): ?int
    {
        return null;
    }

    public function getCantidadPurosAttribute(): int
    {
        return 0;
    }

    public function getCantidadCajonesAttribute(): int
    {
        return 0;
    }

    public function getCantidadActividadesAttribute(): int
    {
        return 0;
    }

    public function getTotalActividadesAttribute(): int
    {
        return 0;
    }

    public function getPrecioMoAttribute(): float
    {
        return 0;
    }

    public function getTotalMoAttribute(): float
    {
        return 0;
    }

    public function getEstadoAttribute(): string
    {
        return 'activo';
    }

    public function getFechaRegistroAttribute()
    {
        return $this->fecha;
    }

    public function getHoraRegistroAttribute(): ?string
    {
        return $this->created_at?->format('H:i:s');
    }

    public function getMinutosTrabajadosAttribute(): int
    {
        return (int) ($this->minutos ?? 0);
    }

    public function cantidadActividadesValor(): int
    {
        return 0;
    }

    public function tiempoTrabajadoTexto(): string
    {
        return VinetaRegistro::minutosATiempoTexto((int) ($this->minutos ?? 0));
    }

    public function fechaHoraRegistroTexto(): string
    {
        $fecha = $this->fecha?->format('d/m/Y') ?? 'Sin fecha';
        $hora = $this->created_at?->timezone('America/Tegucigalpa')->format('h:i A');

        return $hora ? $fecha . ' ' . $hora : $fecha;
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function responsableTexto(): string
    {
        $nombre = trim((string) ($this->registrado_por_nombre ?: $this->registradoPor?->name));

        return $nombre !== '' ? $nombre : 'N/A';
    }
}

