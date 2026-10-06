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
        if (Schema::hasTable('vineta_registros')) {
            Schema::table('vineta_registros', function (Blueprint $table) {
                if (!Schema::hasColumn('vineta_registros', 'documento_empaque_id')) {
                    $table->foreignId('documento_empaque_id')
                        ->nullable()
                        ->after('empleado_id')
                        ->constrained('documento_empaques')
                        ->nullOnDelete();
                }
                if (!Schema::hasColumn('vineta_registros', 'documento_numero')) {
                    $table->string('documento_numero', 50)->nullable()->after('documento_empaque_id')->index();
                }
                if (!Schema::hasColumn('vineta_registros', 'sucursal')) {
                    $table->unsignedInteger('sucursal')->nullable()->default(2)->after('documento_numero');
                }
                if (!Schema::hasColumn('vineta_registros', 'erp_enviado')) {
                    $table->boolean('erp_enviado')->default(false)->after('sucursal');
                }
                if (!Schema::hasColumn('vineta_registros', 'erp_enviado_en')) {
                    $table->timestamp('erp_enviado_en')->nullable()->after('erp_enviado');
                }
                if (!Schema::hasColumn('vineta_registros', 'erp_respuesta')) {
                    $table->json('erp_respuesta')->nullable()->after('erp_enviado_en');
                }
            });
        }

        if (Schema::hasTable('empleado_horas_ordinarias')) {
            Schema::table('empleado_horas_ordinarias', function (Blueprint $table) {
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'documento_empaque_id')) {
                    $table->foreignId('documento_empaque_id')
                        ->nullable()
                        ->after('empleado_id')
                        ->constrained('documento_empaques')
                        ->nullOnDelete();
                }
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'documento_numero')) {
                    $table->string('documento_numero', 50)->nullable()->after('documento_empaque_id')->index();
                }
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'sucursal')) {
                    $table->unsignedInteger('sucursal')->nullable()->default(2)->after('documento_numero');
                }
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'erp_enviado')) {
                    $table->boolean('erp_enviado')->default(false)->after('sucursal');
                }
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'erp_enviado_en')) {
                    $table->timestamp('erp_enviado_en')->nullable()->after('erp_enviado');
                }
                if (!Schema::hasColumn('empleado_horas_ordinarias', 'erp_respuesta')) {
                    $table->json('erp_respuesta')->nullable()->after('erp_enviado_en');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vineta_registros')) {
            Schema::table('vineta_registros', function (Blueprint $table) {
                if (Schema::hasColumn('vineta_registros', 'documento_empaque_id')) {
                    $table->dropForeign(['documento_empaque_id']);
                    $table->dropColumn('documento_empaque_id');
                }
                $columns = ['documento_numero', 'sucursal', 'erp_enviado', 'erp_enviado_en', 'erp_respuesta'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('vineta_registros', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('empleado_horas_ordinarias')) {
            Schema::table('empleado_horas_ordinarias', function (Blueprint $table) {
                if (Schema::hasColumn('empleado_horas_ordinarias', 'documento_empaque_id')) {
                    $table->dropForeign(['documento_empaque_id']);
                    $table->dropColumn('documento_empaque_id');
                }
                $columns = ['documento_numero', 'sucursal', 'erp_enviado', 'erp_enviado_en', 'erp_respuesta'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('empleado_horas_ordinarias', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
