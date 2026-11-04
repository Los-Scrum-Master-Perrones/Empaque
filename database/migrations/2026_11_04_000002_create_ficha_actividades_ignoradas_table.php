<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Actividades que se IGNORAN en la campanita de fichas de empaque
 * (ej. Rezagado). Lo ignorado deja de aparecer en la lista y en el contador.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ficha_actividades_ignoradas')) {
            Schema::create('ficha_actividades_ignoradas', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('codigo_actividad')->unique();
                $table->string('nombre', 191)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Rezagado viene ignorado por defecto.
        $existe = DB::table('ficha_actividades_ignoradas')
            ->where('codigo_actividad', 2)
            ->exists();

        if (! $existe) {
            DB::table('ficha_actividades_ignoradas')->insert([
                'codigo_actividad' => 2,
                'nombre' => 'Rezagado',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_actividades_ignoradas');
    }
};
