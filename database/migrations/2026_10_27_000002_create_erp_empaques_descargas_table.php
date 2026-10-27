<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de las descargas del ERP al comparativo.
 *
 * Permite saber, por fecha y sucursal, si ya se descargo (incluso cuando el
 * ERP no devuelve documentos ese dia) y cuando fue la ultima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_empaques_descargas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sucursal')->default(2);
            $table->date('fecha');
            $table->unsignedInteger('documentos')->default(0);
            $table->unsignedInteger('detalles')->default(0);
            $table->unsignedInteger('planificacion')->default(0);
            $table->boolean('ok')->default(false);
            $table->string('mensaje')->nullable();
            $table->timestamp('descargado_en')->nullable();
            $table->timestamps();

            $table->unique(['sucursal', 'fecha']);
            $table->index(['sucursal', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_empaques_descargas');
    }
};
