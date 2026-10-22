<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas espejo del ERP para comparar lo que se registro en el sistema de
 * Empaque contra lo que quedo en el ERP (documentos, actividades, detalle y
 * planificacion del dia). Solo se llenan descargando del ERP (lectura).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('erp_empaques_documentos')) {
            Schema::create('erp_empaques_documentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sucursal')->default(2);
                $table->string('numero', 20);
                $table->date('fecha');
                $table->unsignedInteger('area')->nullable();
                $table->string('nombre_area', 120)->nullable();
                $table->text('observaciones')->nullable();
                $table->boolean('aplicada')->default(false);
                $table->boolean('anulado')->default(false);
                $table->timestamp('descargado_en')->nullable();
                $table->timestamps();

                $table->unique(['sucursal', 'numero']);
                $table->index(['fecha', 'sucursal']);
            });
        }

        if (! Schema::hasTable('erp_empaques_actividades')) {
            Schema::create('erp_empaques_actividades', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sucursal')->default(2);
                $table->string('numero', 20);
                $table->unsignedInteger('actividad');
                $table->string('nombre_actividad', 150)->nullable();
                $table->unsignedTinyInteger('forma_pago')->nullable();
                $table->unsignedInteger('bodega')->nullable();
                $table->unsignedInteger('bodega_material')->nullable();
                $table->boolean('ultima')->default(false);
                $table->timestamp('descargado_en')->nullable();
                $table->timestamps();

                $table->unique(['sucursal', 'numero', 'actividad']);
            });
        }

        if (! Schema::hasTable('erp_empaques_detalles')) {
            Schema::create('erp_empaques_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sucursal')->default(2);
                $table->string('numero', 20);
                $table->unsignedInteger('actividad');
                $table->string('orden', 20)->default('');
                $table->string('empleado', 20)->default('');
                $table->string('nombre_empleado', 150)->nullable();
                $table->unsignedInteger('linea')->default(0);
                $table->string('producto', 40)->default('');
                $table->string('sku', 40)->default('');
                $table->decimal('puros', 14, 4)->default(0);
                $table->decimal('pagadas', 14, 4)->default(0);
                $table->decimal('control_calidad', 14, 4)->default(0);
                $table->decimal('horas', 12, 4)->default(0);
                $table->decimal('precio', 14, 4)->default(0);
                $table->decimal('salario', 14, 4)->default(0);
                $table->unsignedInteger('bodega')->nullable();
                $table->unsignedInteger('tipo_empaque')->nullable();
                $table->timestamp('descargado_en')->nullable();
                $table->timestamps();

                $table->index(['sucursal', 'numero'], 'erp_det_numero_idx');
                $table->index(['sucursal', 'numero', 'actividad', 'orden', 'empleado', 'linea', 'producto'], 'erp_det_llave_idx');
            });
        }

        if (! Schema::hasTable('erp_empaques_planificacion')) {
            Schema::create('erp_empaques_planificacion', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('sucursal')->default(2);
                $table->string('orden', 20);
                $table->unsignedInteger('linea')->default(0);
                $table->date('fecha');
                $table->unsignedInteger('actividad');
                $table->decimal('a_producir', 14, 4)->default(0);
                $table->decimal('producida', 14, 4)->default(0);
                $table->unsignedInteger('personal')->default(0);
                $table->boolean('ultima')->default(false);
                $table->timestamp('descargado_en')->nullable();
                $table->timestamps();

                $table->index(['sucursal', 'orden', 'linea', 'fecha', 'actividad'], 'erp_plan_llave_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_empaques_planificacion');
        Schema::dropIfExists('erp_empaques_detalles');
        Schema::dropIfExists('erp_empaques_actividades');
        Schema::dropIfExists('erp_empaques_documentos');
    }
};
