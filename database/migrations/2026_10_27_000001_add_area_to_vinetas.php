<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Area de la viñeta cuando NO viene de facturacion (limpieza, brocha).
 * Null = viñeta normal (la que sincroniza la API de facturacion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinetas', function (Blueprint $table) {
            if (! Schema::hasColumn('vinetas', 'area')) {
                $table->string('area', 30)->nullable()->index()->after('tipo_empaque');
            }
        });

        Schema::table('vinetas_por_orden', function (Blueprint $table) {
            if (! Schema::hasColumn('vinetas_por_orden', 'area')) {
                $table->string('area', 30)->nullable()->index()->after('tipo_empaque');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vinetas', function (Blueprint $table) {
            if (Schema::hasColumn('vinetas', 'area')) {
                $table->dropIndex(['area']);
                $table->dropColumn('area');
            }
        });

        Schema::table('vinetas_por_orden', function (Blueprint $table) {
            if (Schema::hasColumn('vinetas_por_orden', 'area')) {
                $table->dropIndex(['area']);
                $table->dropColumn('area');
            }
        });
    }
};
