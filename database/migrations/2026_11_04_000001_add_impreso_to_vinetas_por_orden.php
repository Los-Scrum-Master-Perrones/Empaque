<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca si una viñeta generada en Empaque ya se imprimió.
 *
 * Igual que facturación: se generan (impreso = 0) y luego un solo botón
 * imprime TODO lo que está pendiente (impreso = 0) y lo marca (impreso = 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinetas_por_orden', function (Blueprint $table) {
            if (! Schema::hasColumn('vinetas_por_orden', 'impreso')) {
                $table->boolean('impreso')->default(false)->index()->after('estado');
            }

            if (! Schema::hasColumn('vinetas_por_orden', 'impreso_en')) {
                $table->timestamp('impreso_en')->nullable()->after('impreso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vinetas_por_orden', function (Blueprint $table) {
            if (Schema::hasColumn('vinetas_por_orden', 'impreso_en')) {
                $table->dropColumn('impreso_en');
            }

            if (Schema::hasColumn('vinetas_por_orden', 'impreso')) {
                $table->dropIndex(['impreso']);
                $table->dropColumn('impreso');
            }
        });
    }
};
