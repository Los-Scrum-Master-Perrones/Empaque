<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('documento_empaques')) {
            Schema::create('documento_empaques', function (Blueprint $table) {
                $table->id();
                $table->string('numero', 50)->index();
                $table->string('descripcion', 255)->nullable();
                $table->text('observaciones')->nullable();
                $table->decimal('total', 10, 2)->default(0);
                $table->date('fecha')->index();
                $table->unsignedInteger('sucursal')->default(2)->index();
                $table->unsignedInteger('forma_pago')->default(2);
                $table->unsignedInteger('bodega_detalle')->default(218);
                $table->string('estado', 50)->default('activo');
                $table->timestamps();

                $table->index(['numero', 'fecha', 'sucursal'], 'doc_empaque_num_fecha_suc_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: only drop if explicitly reversing
        Schema::dropIfExists('documento_empaques');
    }
};
